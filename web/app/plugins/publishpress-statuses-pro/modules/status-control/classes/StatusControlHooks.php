<?php
namespace PublishPress\Statuses;

class StatusControlHooks 
{
    function __construct() 
    {
        add_filter('presspermit_default_options', [$this, 'flt_default_statuses_options']);

        // This script executes on plugin load.
        //
        require_once(PUBLISHPRESS_STATUS_CONTROL_ABSPATH . '/db-config.php');

        // Legacy constants from prior versions.
        // This previously exectuted on the init action at priority 10. Move it earlier to ensure our related code doesn't check it before it's set. 
        // Priority 20 allows for possible existing define statements in main body of user-maintained plugin modules.
        add_action('plugins_loaded', function() {
            if (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS')) {
                define('PPS_CUSTOM_PRIVACY_EDIT_CAPS', !defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS') && !empty(\PublishPress_Statuses::instance()->options->custom_privacy_edit_caps));
            } else {
                if (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED')) {
                    define('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED', true);
                }
            }
        }, 20);

        add_action('init', [$this, 'act_register_role_attributes'], 70);

        add_action('init', [$this, 'actMaintenanceTriggers'], 10);

        add_action('init', [$this, 'actRegistrations'], 46);
        add_action('init', [$this, 'act_post_stati_prep'], 48);  // StatusesHooksAdmin::act_process_conditions() follows at priority 49

        add_action('init', [$this, 'act_version_check'], 5);

        add_action('init', [$this, 'act_forceDistinctPostCaps'], 50);

        add_filter('presspermit_pattern_roles_raw', [$this, 'fltPatternRolesRaw']);
        add_filter('presspermit_pattern_roles', [$this, 'fltPatternRoles']);

        add_filter('presspermit_pattern_role_caps', [$this, 'flt_default_rolecaps']);
        add_filter('presspermit_exclude_arbitrary_caps', [$this, 'fltExcludeArbitraryCaps']);

        add_filter('rest_user_query', [$this, 'flt_rest_user_query'], 20, 2);  // todo: relocate?

        if (defined('REVISIONARY_VERSION')) { // note: no equivalent needed for PublishPress Revisions 3
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/Revisionary/CapabilityFilters.php');
            new StatusControl\Revisionary\CapabilityFilters();
        }

        add_filter('user_has_cap', [$this, 'fltPublishPostsContext'], 100, 3);

        add_action('rest_api_init', [$this, 'actRestInit'], 1);

        add_filter('post_row_actions', [$this, 'fixPostRowActions'], 9, 2);
        add_filter('page_row_actions', [$this, 'fixPostRowActions'], 9, 2);

        add_action('presspermit_pro_version_updated', [$this, 'pluginUpdated']);

        add_action('publishpress_statuses_register_visibility_statuses', [$this, 'registerVisibilityStatuses']);
        add_action('publishpress_statuses_register_taxonomies', [$this, 'registerTaxonomies']);
        add_filter('publishpress_statuses_get_default_statuses', [$this, 'fltGetDefaultStatuses'], 10, 2);

        add_filter('presspermit_getItemCondition', [$this, 'fltForceDefaultVisibility'], 10, 4);

        add_filter(
            'presspermit_get_post_statuses',
            function ($statuses, $args, $return, $operator, $params = []) {
                global $pagenow;
                
                if (empty($pagenow) || !in_array($pagenow, ['post.php', 'post-new.php'])) {
                    return $statuses;
                }

                /*
                 * Filter: 'presspermit_limit_editor_post_statuses'
                 * 
                 * ['post_status' => array of selectable status names]
                 * 
                 * Note: statuses may be further limited based on user capabilities / permissions
                 */
                if (!$valid_statuses = apply_filters('presspermit_limit_editor_post_statuses', [], $args)){
                    return $statuses;
                }
                
                if (current_user_can('administrator')) {
                    return $statuses;
                }

                if (!$post_id = \PP_Statuses_Functions::getPostID()) {
                    $post_status = 'draft';
                } else {
                    if (!$post_status = get_post_field('post_status', $post_id)) {
                        return $statuses;
                    }
                }

                if (isset($valid_statuses[$post_status])) {
                    $statuses = array_intersect_key($statuses, array_fill_keys($valid_statuses[$post_status], true));
                }

                return $statuses;
            }
        , 100, 5);
    }

    function act_register_role_attributes()
    {
        // Restriction of read access will be accomplished by post status setting (either private or a custom status registered with private=true) 
        //
        // force_visibility attribute does not impose condition caps, but affects the post_status of published posts.
        \PublishPress\StatusCapabilities::registerAttribute(
            'force_visibility', 
            'post',
            [
                'label' => esc_html__('Force Visibility', 'publishpress-statuses-pro'), 
                'default' => 'none', 
                'suppress_item_edit_ui' => ['object' => true]
            ]
        );

        // register each custom post status as an attribute condition with mapped caps
        PPS::registerPrivacyConditions(\PP_Statuses_Functions::getPostStatuses([], 'object'));
    }

    /**
     * Makes the call to register_post_status to register the user's post visibility statuses.
     *
     * @param array $args
     */
    public function registerVisibilityStatuses($statuses)
    {
        if (!get_option('presspermit_privacy_statuses_enabled', 1)) {
            return;
        }

        if (function_exists('register_post_status')) {
            foreach ($statuses as $status) {
                // Ignore custom moderation statues and all core statuses, which are registered elsewhere.
                if (empty($status->private) || !empty($status->_builtin) || !empty($status->moderation)
                || !empty($status->disabled)
                || in_array($status->slug, ['_pre-publish-alternate', '_disabled'])) {
                    continue;
                }

                register_post_status($status->slug, \PublishPress_Statuses::visibility_status_properties($status));
            }
        }
    }


    function registerTaxonomies() {
        if (get_option('presspermit_privacy_statuses_enabled', 1)) {
            // @todo: check for disable of custom privacy statuses feature
            if (! taxonomy_exists(\PublishPress_Statuses::TAXONOMY_PRIVACY)) {
                register_taxonomy(
                    \PublishPress_Statuses::TAXONOMY_PRIVACY,
                    'post',
                    [
                        'hierarchical' => false,
                        'update_count_callback' => '_update_post_term_count',
                        'label' => __('Post Visibility', 'publishpress-statuses-pro'),
                        'labels' => (object) ['name' => __('Post Visibility', 'publishpress-statuses-pro'), 'singular_name' => __('Post Visibility', 'publishpress-statuses-pro')],
                        'query_var' => false,
                        'rewrite' => false,
                        'show_ui' => false,
                    ]
                );
            }
        }
    }

    public function fltGetDefaultStatuses($statuses, $taxonomy) {
        if (\PublishPress_Statuses::TAXONOMY_PRIVACY != $taxonomy) {
            return $statuses;
        }

        if (!get_option('presspermit_privacy_statuses_enabled', 1)) {
            return [];
        }

        $statuses = [
            'member' => (object) [
                'label' => __('Member', 'publishpress-statuses-pro'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-universal-access-alt',
                'position' => 10,
                'order' => 901,
                'private' => true,
                'pp_builtin' => true,
            ],

            'premium' => (object) [
                'label' => __('Premium', 'publishpress-statuses-pro'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-superhero',
                'position' => 11,
                'order' => 902,
                'private' => true,
                'pp_builtin' => true,
            ],

            'staff' => (object) [
                'label' => __('Staff', 'publishpress-statuses-pro'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-id-alt',
                'position' => 12,
                'order' => 903,
                'private' => true,
                'pp_builtin' => true,
            ],
        ];

        return $statuses;
    }

    function fltForceDefaultVisibility($item_condition, $source_name, $attribute, $args = [])
    {
        // allow any existing page-specific settings to override default forcing
        if (('post' == $source_name) && ('force_visibility' == $attribute) && !$item_condition && isset($args['post_type'])) {
            if (empty($args['assign_for']) || ('item' == $args['assign_for'])) {
                $options = \PublishPress_Statuses::instance()->options;

                if (!empty(\PublishPress_Statuses::instance()->options->force_default_privacy[$args['post_type']])) {
                    $default_privacy = !empty($options->default_privacy[$args['post_type']]) ? $options->default_privacy[$args['post_type']] : 'publish';
                    
                    // only apply if status is currently registered and PP-enabled for the post type
                    if (\PP_Statuses_Functions::getPostStatuses(['name' => $default_privacy, 'post_type' => $args['post_type']])) {
                        if (!empty($args['return_meta']))
                            return (object)['force_status' => $default_privacy, 'force_basis' => 'default'];
                        else
                            return $default_privacy;
                    }
                }
            }
        }

        return $item_condition;
    }

    // Account for list_* capability provision: don't display Preview link if post is not editable
    function fixPostRowActions($actions, $post) {
        $can_edit = current_user_can('edit_post', $post->ID);

        if (in_array($post->post_status, get_post_stati(['public' => true, 'private' => true], 'names', 'OR'))) {
            if (!$can_edit && !current_user_can('read_post', $post->ID)) {
                unset($actions['view']);
            }
        } elseif(!$can_edit && !defined('PUBLISHPRESS_REVISIONS_VERSIONS')) {  // todo: API?
            unset($actions['view']);
        }

        return $actions;
    }

    function fltPublishPostsContext($wp_sitecaps, $orig_reqd_caps, $args)
    {
        $user = publishpress_status_control()->getUser();
        $args = (array)$args;

        $post_id = \PP_Statuses_Functions::getPostID();

        if (($args[1] != $user->ID) || !$post_id || (defined('ET_BUILDER_PLUGIN_VERSION') && !\PP_Statuses_Functions::empty_REQUEST('et_fb'))) {
            return $wp_sitecaps;
        }

        // If we are crediting edit_others_posts capability based on ownership of edit_others_{$status}_posts, 
        // don't honor publish_posts except for own posts
        if ($_post = get_post($post_id)) {
            if ($user->ID != $_post->post_author) {
                if ($type_obj = get_post_type_object($_post->post_type)) {
                    if (isset($type_obj->cap->publish_posts) && ($type_obj->cap->publish_posts == $args[0])) {
                        if (isset($type_obj->cap->edit_others_posts) && empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                            $cap_property = "edit_others_{$_post->post_status}_posts";
                            if (isset($type_obj->cap->$cap_property) && !empty($user->allcaps[$type_obj->cap->$cap_property])) {
                                unset($wp_sitecaps[$type_obj->cap->publish_posts]);
                            }
                        }
                    }
                }
            }
        }

        return $wp_sitecaps;
    }

    function actRestInit()
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/RESTFields.php');
        StatusControl\RESTFields::registerRESTFields();
    }

    function flt_default_statuses_options($def = [])
    {
        $new = [
            'pattern_roles_include_custom_status_rolecaps' => 0,
        ];

        return array_merge($def, $new);
    }

    function actMaintenanceTriggers()
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/Triggers.php');
        new StatusControl\Triggers();
    }

    // Register default custom stati; Additional labels in status registration
    function actRegistrations()
    {
        global $wp_post_statuses;

        if (get_option('presspermit_privacy_statuses_enabled', 1)) {
            // custom private stati
            register_post_status('member', [
                'label' => _x('Member', 'post'),
                'private' => true,
                'label_count' => _n_noop('Member <span class="count">(%s)</span>', 'Member <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);

            register_post_status('premium', [
                'label' => _x('Premium', 'post'),
                'private' => true,
                'label_count' => _n_noop('Premium <span class="count">(%s)</span>', 'Premium <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);

            register_post_status('staff', [
                'label' => _x('Staff', 'post'),
                'private' => true,
                'label_count' => _n_noop('Staff <span class="count">(%s)</span>', 'Staff <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);
        }
    }

    function act_post_stati_prep()
    {
        global $wp_post_statuses;

        // set default properties
        foreach (array_keys($wp_post_statuses) as $status) {
            if (!isset($wp_post_statuses[$status]->moderation))
                $wp_post_statuses[$status]->moderation = false;
        }


        // Apply term meta properties set by PublishPress Statuses @todo: move this into Statuses Pro for tighter integration
        if (taxonomy_exists('post_status')) {
            foreach ($wp_post_statuses as $post_status => $status_obj) {
                if (empty($status_obj->private) || ('private' == $post_status)) {
                    continue;
                }

                if ($term = get_term_by('slug', $post_status, 'post_visibility_pp')) {
                    if ($term_meta = get_term_meta($term->term_id)) {
                        foreach (['color', 'icon', 'labels', 'post_type'] as $prop) {
                            if (isset($term_meta[$prop])) {
                                $value = maybe_unserialize($term_meta[$prop]);                          // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
                                $value = (is_array($value)) ? reset($value) : $value;

                                if (('post_type' != $prop) || !\PP_Statuses_Functions::is_REQUEST('page', 'pp-capabilities')) {
                                    $value = maybe_unserialize($value);                                 // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

                                    if (is_object($value)) {
                                        foreach (get_object_vars($value) as $k => $val) {
                                            $value->$k = sanitize_text_field($val);
                                        }
                                    } elseif (is_array($value)) {
                                        foreach ($value as $k => $val) {
                                            $value[$k] = sanitize_text_field($val);
                                        }
                                    } else {
                                        $value = sanitize_text_field($value);
                                    }
                                    
                                    $wp_post_statuses[$post_status]->$prop = maybe_unserialize($value);  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function act_version_check()
    {
        $last_version = get_option('publishpress_status_control_version');

        if (!$last_version || version_compare(PRESSPERMIT_STATUSES_VERSION, $last_version, '!=')) {
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/Updated.php');
            
            // These maintenance operations only apply when a previous version of PPCS was installed 
            if ($last_version) {
                new StatusControl\Updated($last_version);
            } else {
                // first execution after install
                StatusControl\Updated::populateRoles();

                update_option(
                    'publishpress_status_control_version', 
                    PRESSPERMIT_STATUSES_VERSION
                );

                require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/DatabaseSetup.php');
                new StatusControl\DB\DatabaseSetup($last_version);
            }
        }
    }

    function act_forceDistinctPostCaps()
    {
        global $wp_post_types;

        if (!function_exists('presspermit')) {
            return;
        }

        $pp = presspermit();

        $generic_caps = ['post' => ['set_posts_status' => 'set_posts_status'], 'page' => ['set_posts_status' => 'set_posts_status']];

        // post types which are enabled for PP filtering must have distinct type-related cap definitions
        foreach (array_intersect(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or'), $pp->getEnabledPostTypes()) as $post_type) {
            $type_caps = [];
            
            if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
                continue;
            }

            if ('post' == $post_type) {
                $type_caps['set_posts_status'] = 'set_posts_status';
            } else {
                $type_caps['set_posts_status'] = str_replace('_post', "_$post_type", 'set_posts_status');
            }

            $wp_post_types[$post_type]->cap = (object)array_merge((array)$wp_post_types[$post_type]->cap, $type_caps);

            $plural_type = \PublishPress\Permissions\Capabilities::getPlural($post_type, $wp_post_types[$post_type]);

            $pp->capDefs()->all_type_caps = array_merge($pp->capDefs()->all_type_caps, array_fill_keys($type_caps, true));

            foreach (\PP_Statuses_Functions::getPostStatuses(['moderation' => true, 'post_type' => $post_type, 'disabled' => false]) as $status_name) {
                $cap_property = "set_{$status_name}_posts";
                $wp_post_types[$post_type]->cap->$cap_property = str_replace("_posts", "_{$plural_type}", $cap_property);
            }
        }
    }

    // For optimal flexibility with custom moderation stati (including PublishPress Statuses), dynamically insert a Submitter role 
    // containing the 'set_posts_status' capability.
    //
    // With default Contributor rolecaps, a "Page Contributor - Assigned" role enables the user to edit their own pages 
    // which have been set to assigned status.  
    //
    // "Page Submitter - Assigned" role enables setting their other pages to the Approved status
    //
    // These supplemental roles may be assigned individually or in conjunction
    // Note that the set_posts_status capability is granted implicitly for the 'pending' status, 
    // even if custom capabilities are enabled.
    function flt_default_rolecaps($caps)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION') && !isset($caps['submitter'])) {
            $caps['submitter'] = array_fill_keys(['read', PRESSPERMIT_READ_PUBLIC_CAP, 'set_posts_status'], true);
        }

        return $caps;
    }

    public function fltPatternRolesRaw($roles) {
        return $this->fltPatternRoles($roles, false);
    }

    function fltPatternRoles($roles, $set_labels = false)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            if (!isset($roles['submitter']))
                $roles['submitter'] = (object)[];

            if (!isset($roles['submitter']->labels)) {
                if ($set_labels) {
                    $roles['submitter']->labels = (object)[
                        'name' => esc_html__('Submitters', 'presspermit'), 
                        'singular_name' => esc_html__('Submitter', 'presspermit')
                    ];
                } else {
                    $display_name = 'Submitter';
                    $roles['submitter']->labels = (object)['name' => $display_name, 'singular_name' => $display_name];
                }
            }
        }

        return $roles;
    }

    function fltExcludeArbitraryCaps($caps)
    {
        $excluded = ['pp_define_post_status', 'pp_define_moderation', 'pp_define_privacy'];

        if (!\PublishPress_Statuses::instance()->options->supplemental_cap_moderate_any)
            $excluded [] = 'pp_moderate_any';

        return array_merge($caps, $excluded);
    }

    // Gutenberg: filter post author dropdown 
    function flt_rest_user_query($prepared_args, $request)
    {
        if (isset($prepared_args['who']) && ('authors' == $prepared_args['who'])) {
            if ($post_type = \PP_Statuses_Functions::findPostType()) {
                if ($type_obj = get_post_type_object($post_type)) {
                    if (!current_user_can($type_obj->cap->edit_others_posts)) {
                        global $current_user;
                        $prepared_args['include'] = $current_user->ID;
                    }
                }
            }
        }

        return $prepared_args;
    }

    public function pluginUpdated($prev_version) {
        if (version_compare($prev_version, '3.6.1', '<')) {
            add_action('wp_loaded', function() {
                global $wp_roles;

                // For each status, if "edit_others_{$status_name}_attachments" was set to a role due to past bug, add type-specific status capability if other editing capabilities are present for the post type

                $statuses = get_post_stati(['moderation' => true], 'names', 'or');

                foreach($statuses as $status_name) {
                    $check_cap = "edit_others_{$status_name}_attachments";

                    foreach($wp_roles->roles as $role_name => $_role) {
                        if (!empty($_role['capabilities'][$check_cap])) {
                            $role = @get_role($role_name);
                        
                            // For each post type that has custom capabilities enabled, if the user has "edit_{$status_name}_pages" and edit_others_pages, also set "edit_others_{$status_name}_pages"

                            if (\PublishPress\StatusCapabilities::postStatusHasCustomCaps($status_name)) {
                                foreach(get_post_types(['public' => true], 'object') as $post_type => $type_obj) {
                                    $edit_posts_status_cap = str_replace('edit_', "edit_{$status_name}_", $type_obj->cap->edit_posts);
                                    
                                    if (!empty($type_obj->cap->edit_others_posts) && !empty($_role['capabilities'][$edit_posts_status_cap]) && !empty($_role['capabilities'][$type_obj->cap->edit_others_posts])) {
                                        $edit_others_posts_status_cap = str_replace('edit_others_', "edit_others_{$status_name}_", $type_obj->cap->edit_others_posts);
                                        $role->add_cap($edit_others_posts_status_cap);
                                    }
                                }
                            }
                        }
                    }
                }
            }, 50);
        }
    }
}
