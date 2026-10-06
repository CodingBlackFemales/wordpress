<?php
namespace PublishPress_Statuses;

// Custom Status admin menus, shared javascript and CSS
class Admin
{
    public $menu_slug;
    public $using_permissions_menu;

    function __construct($activated = false) {
        add_action('admin_menu', [$this, 'act_admin_menu'], 21);

        add_filter('plugin_row_meta', 
            function ($links, $file) {
                if (defined('PUBLISHPRESS_STATUSES_FILE') && ($file == plugin_basename(PUBLISHPRESS_STATUSES_FILE))
                || defined('PUBLISHPRESS_STATUSES_PRO_FILE') && ($file == plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE))
                ) {
                    $links[] = '<a href="'. esc_url(admin_url('admin.php?page=publishpress-statuses')) .'">' . esc_html__('Statuses', 'publishpress-statuses') . '</a>';
                    $links[] = '<a href="'. esc_url(admin_url('admin.php?page=publishpress-statuses-settings')) .'">' . esc_html__('Settings', 'publishpress-statuses') . '</a>';

                    if (!defined('PUBLISHPRESS_STATUSES_PRO_FILE')) {
                        $links[] = '<a href="'. esc_url('https://publishpress.com/links/statuses-plugin-row') .'" class="pp-upgrade">' . esc_html__('Upgrade to Pro', 'publishpress-statuses') . '</a>';
                    }
                }

                return $links;
            }, 10, 2
        );

        // Load CSS and JS resources that we probably need
        add_action('admin_print_styles', [$this, 'add_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts']);

        add_filter('display_post_states', [$this, 'fltDisplayPostStates'], 10, 2);

        $last_statuses_version = get_option('publishpress_statuses_version');

        if (!$last_statuses_version || $activated) {
            $this->assignDefaultRoleCapabilities();
        }

        if (!$last_statuses_version || version_compare($last_statuses_version, '1.0.4.2', '<')) {
            if (!defined('PP_STATUSES_DISABLE_PENDING_STATUS_FIX')) {
                global $wpdb;

                // Clean up after Gutenberg integration bug in plugin version < 1.0.4.2

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query("UPDATE $wpdb->posts SET post_status = 'pending' WHERE post_status = '_pending'");
            }
        }

        do_action('publishpress_statuses_version_check');

        $last_statuses_version = get_option('publishpress_statuses_version');

        if (($last_statuses_version != PUBLISHPRESS_STATUSES_VERSION) || (defined('PP_STATUSES_RESET_POSITIONS') && !empty($_REQUEST['pp_reset_status_positions']))) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (version_compare($last_statuses_version, '1.2.0', '<')) {
                if ($role = @get_role('administrator')) {
                    $role->add_cap('pp_unpublish_posts');
                }
            }
            
            if ('1.1.7-beta' == $last_statuses_version) {
                // work around beta bug
                delete_option('publishpress_status_positions');

            } elseif (version_compare($last_statuses_version, '1.1.9', '<=') || (defined('PP_STATUSES_RESET_POSITIONS') && !empty($_REQUEST['pp_reset_status_positions']))) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ($positions = get_option('publishpress_status_positions')) {
                    $current_positions = $positions;
                    
                    $rev_statuses = \PublishPress_Statuses::instance()->getPostStatuses(['for_revision' => true], 'names');
        
                    $ordered_rev_statuses = [];
                    $ordered_disabled_statuses = [];
        
                    $rev_main_pos = array_search('_revision-workflow', $positions);
        
                    // Move Main Rev workflow above Rev statuses
                    foreach ($positions as $k => $status) {
                        if ($k >= $rev_main_pos) {
                            break;
                        }
        
                        if (in_array($status, $rev_statuses) && ('draft-revision' != $status)) {
                            unset($positions['_revision-workflow']);
        
                            $positions = array_merge(
                                array_slice($positions, 0, $k),
                                ['_revision-workflow'],
                                array_slice($positions, $k)
                            );
        
                            break;
                        }
                    }
        
                    reset($positions);
        
                    $rev_main_pos = array_search('_revision-workflow', $positions);
                    $alternate_rev_pos = array_search('_revision-alternate', $positions);
                    $disabled_pos = array_search('_disabled', $positions);
        
                    // Move Alternate Rev workflow above Disabled
                    if ($alternate_rev_pos > $disabled_pos) {
                        $last_rev_status_pos = 0;
        
                        foreach ($positions as $k => $status) {
                            if (in_array($status, $rev_statuses)) {
                                $last_rev_status_pos = $k;
                            }
                        }
        
                        $positions = array_merge(
                            array_slice($positions, 0, $disabled_pos-1),
                            array_slice($positions, $alternate_rev_pos, $last_rev_status_pos - $alternate_rev_status_pos + 1),
                            array_slice($positions, $disabled_pos)
                        );
                    }
        
                    if ($positions !== $current_positions) {
                        update_option('publishpress_status_positions', $positions);
                    }
                }
            }

            if (!$last_statuses_version || version_compare($last_statuses_version, '1.2.0', '<')) {
                // Ensure Visibility Statuses are enabled by default
                if (null === get_option('presspermit_privacy_statuses_enabled', null)) {
                    update_option('presspermit_privacy_statuses_enabled', 1);
                }
            }

            update_option('publishpress_statuses_version', PUBLISHPRESS_STATUSES_VERSION);
        }
    }

    public function assignDefaultRoleCapabilities() {
        global $wp_roles;

        if (empty($wp_roles) || !is_object($wp_roles) || empty($wp_roles->roles)) {
            return;
        }

        $processed_roles = (array) get_option('publishpress_statuses_processed_roles');
        $changed_statuses = [];

        foreach($wp_roles->role_objects as $role_name => $role) {
            $status_taxonomies = apply_filters('publishpress_statuses_taxonomies', ['post_status']);
            
            foreach($status_taxonomies as $taxonomy) {
                foreach (\PublishPress_Statuses::getPostStati(['taxonomy' => $taxonomy], 'names', ['show_disabled' => true]) as $status_name) {

                    if (
                        isset($processed_roles[$role_name]) 
                        && (isset($processed_roles[$role_name][$status_name]) || in_array($status_name, ['pitch', 'in-progress', 'assigned', 'pending', 'approved', 'needs-work', 'rejected']))
                    ) {
                        continue;
                    }
                    
                    // Mirror Planner behavior of enabling standard WP roles to assign statuses, but also grant to other roles based on post / page capabilities
                    if (in_array($status_name, ['pitch', 'in-progress', 'assigned', 'pending'])) {
                        if (!in_array($role_name, ['administrator', 'author', 'editor', 'contributor']) && !$role->has_cap('edit_posts') && !$role->has_cap('edit_pages')) {
                            continue;
                        }
        
                    } elseif (in_array($status_name, ['approved', 'needs-work', 'rejected'])) {
                        if (!in_array($role_name, ['administrator', 'editor']) && !$role->has_cap('edit_others_posts') && !$role->has_cap('edit_others_pages')) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                    
                    $cap_name = 'status_change_' . str_replace('-', '_', $status_name);

                    if (empty($role->capabilties[$cap_name])) {
                        $role->add_cap($cap_name);
                        $changed_statuses [$status_name] = true;
                    }

                    $processed_roles[$role_name][$status_name] = true;
                }
            }

            if (in_array($role_name, ['administrator', 'editor']) || $role->has_cap('publish_posts') || $role->has_cap('publish_pages')) {
                $cap_name = 'pp_bypass_status_sequence';
                if (empty($role->capabilties[$cap_name])) {
                    $role->add_cap($cap_name);
                }
            }

            if (in_array($role_name, ['administrator', 'editor']) || $role->has_cap('edit_posts') || $role->has_cap('edit_pages')) {
                foreach (['draft-revision', 'pending-revision'] as $status_name) {
                    $cap_name = 'status_change_' . str_replace('-', '_', $status_name);

                    if (empty($role->capabilties[$cap_name])) {
                        $role->add_cap($cap_name);
                        $changed_statuses [$status_name] = true;
                    }
                }
            }

            if (in_array($role_name, ['administrator', 'editor']) || $role->has_cap('publish_posts') || $role->has_cap('publish_pages')) {
                $cap_name = 'status_change_future-revision';

                if (empty($role->capabilties[$cap_name])) {
                    $role->add_cap($cap_name);
                    $changed_statuses [$status_name] = true;
                }
            }
        }

        update_option('publishpress_statuses_processed_roles', $processed_roles);

        foreach (array_keys($changed_statuses) as $status_name) {
            \PublishPress_Statuses::updateStatusNumRoles($status_name, ['force_refresh' => true]);
        }
    }

    // status display in Edit Posts table rows
    public static function fltDisplayPostStates($post_states, $post)
    {
        global $wp_post_statuses;

        if (empty($post) || in_array($post->post_status, ['publish', 'private', 'pending', 'draft'])) {
            return $post_states;
        }

        if ('future' == $post->post_status) {  // also display eventual visibility of scheduled post (if non-public)
            if ($scheduled_status = get_post_meta($post->ID, '_scheduled_status', true)) {
                if ('publish' != $scheduled_status) {
                    if ($_scheduled_status_obj = get_post_status_object($scheduled_status)) {
                        $post_states['future'] = $_scheduled_status_obj->label;
                    }
                }
            }
        } elseif (\PP_Statuses_Functions::empty_REQUEST('post_status') 
        || (\PP_Statuses_Functions::REQUEST_key('post_status') != $post->post_status)
        ) {  // if filtering for this status, don't display caption in result rows
            $status_obj = (!empty($wp_post_statuses[$post->post_status])) ? $wp_post_statuses[$post->post_status] : false;

            if ($status_obj) {
                if (!empty($status_obj->private) || (!empty($status_obj->moderation))) {
                    $post_states[$post->post_status] = $status_obj->label;
                }
            }
        }

        return $post_states;
    }

    function add_admin_styles() {
        $plugin_page = \PP_Statuses_Functions::getPluginPage();

        if (0 === strpos($plugin_page, 'publishpress-statuses')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            wp_enqueue_style('publishpress-statuses-tooltips', PUBLISHPRESS_STATUSES_URL . '/common/css/_tooltip.css', [], PUBLISHPRESS_STATUSES_VERSION);
            
            wp_enqueue_style(
                'publishpress-status-admin-css',
                PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-admin.css',
                [],
                PUBLISHPRESS_STATUSES_VERSION
            );

            wp_enqueue_style('publishpress-statuses-admin-common', PUBLISHPRESS_STATUSES_URL . '/common/libs/publishpress/publishpress-admin.css', [], PUBLISHPRESS_STATUSES_VERSION);

            if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                wp_enqueue_style(
                    'publishpress-status-admin-pro-css',
                    PUBLISHPRESS_STATUSES_PRO_URL . 'common/css/custom-status-admin-pro.css',
                    [],
                    PUBLISHPRESS_STATUSES_PRO_VERSION
                );
            }
        }
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */
    public function action_admin_enqueue_scripts()
    {
        global $pagenow, $post;

        if (\PublishPress_Statuses::DisabledForPostType()) {
            return;
        }

        if (!empty($post)) {
            if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
            || \PublishPress_Statuses::isPostBlacklisted($post->ID)
            ) {
                return;
            }
        }

        $plugin_page = \PP_Statuses_Functions::getPluginPage();

        // Scripts and styles needed for Add Status, Edit Status, and possibly Statuses
        if (0 === strpos($plugin_page, 'publishpress-statuses')) {
            wp_enqueue_script(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/libs/icon-picker/icon-picker.js',
                ['jquery'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
            wp_enqueue_style(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/libs/icon-picker/icon-picker.css',
                ['dashicons'],
                PUBLISHPRESS_STATUSES_VERSION,
                'all'
            );

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            wp_enqueue_script(
                'publishpress-status-edit',
                PUBLISHPRESS_STATUSES_URL . "common/js/status-edit{$suffix}.js",
                ['jquery', 'jquery-ui-sortable'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
        }

        // Scripts and styles for Statuses screen
        if ('publishpress-statuses' == $plugin_page
        && in_array(\PP_Statuses_Functions::REQUEST_key('action'), ['', 'statuses'])
        ) {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-datepicker');

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            wp_enqueue_script(
                'ui-touch-punch', 
                PUBLISHPRESS_STATUSES_URL . 'common/libs/jquery.ui.touch-punch/jquery.ui.touch-punch.min.js', 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            wp_enqueue_script(
                'nested-sortable-mjs-pp', 
                PUBLISHPRESS_STATUSES_URL . "common/libs/jquery.mjs.nestedSortable-pp/jquery.mjs.nestedSortable-pp{$suffix}.js", 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );

            wp_enqueue_script(
                'publishpress-custom-status-configure',
                PUBLISHPRESS_STATUSES_URL . "common/js/custom-status-configure{$suffix}.js",
                ['jquery', 'jquery-ui-sortable'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );

            wp_localize_script(
                'publishpress-custom-status-configure',
                'objectL10ncustomstatus',
                [
                    'pp_confirm_delete_status_string' => __(
                        'Are you sure you want to delete the post status? All posts with this status will be assigned to the default status.',
                        'publishpress-statuses'
                    ),
                ]
            );

            if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                wp_enqueue_script(
                    'publishpress-custom-status-configure-pro',
                    PUBLISHPRESS_STATUSES_PRO_URL . "common/js/custom-status-configure-pro{$suffix}.js",
                    ['jquery', 'jquery-ui-sortable'],
                    PUBLISHPRESS_STATUSES_PRO_VERSION,
                    true
                );
            }
        }

        // Custom javascript to modify the post status dropdown where it shows up
        if (self::is_post_management_page() && class_exists('PP_Statuses_Functions')) {
            if (\PP_Statuses_Functions::isBlockEditorActive(['force' => \PublishPress_Statuses::instance()->options->force_editor_detection])) {
                wp_enqueue_style(
                    'publishpress-custom_status-block',
                    PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-block-editor.css',
                    false,
                    PUBLISHPRESS_STATUSES_VERSION,
                    'all'
                );
            } else {
                wp_enqueue_style(
                    'publishpress-custom_status-classic',
                    PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-classic-editor.css',
                    false,
                    PUBLISHPRESS_STATUSES_VERSION,
                    'all'
                );
            }
        }
    }

    /**
     * Primary configuration page for custom status class.
     * Shows form to add new custom statuses on the left and a
     * WP_List_Table with the custom status terms on the right
     */
    public function render_admin_page()
    {
        require_once(__DIR__ . '/StatusesUI.php');
        $ui = \PublishPress_Statuses\StatusesUI::instance();
        $ui->render_admin_page();
    }

    function act_admin_menu()
    {
        $this->menu_slug = 'publishpress-statuses';

        $this->using_permissions_menu = true;

        $check_cap = (current_user_can('manage_options')) ? 'read' : 'pp_manage_statuses';

        add_menu_page(
            esc_html__('Statuses', 'publishpress-statuses'),
            esc_html__('Statuses', 'publishpress-statuses'),
            $check_cap,
            'publishpress-statuses',
            [$this, 'render_admin_page'],
            'dashicons-format-status',
            70
        );

        add_submenu_page(
            'publishpress-statuses',
            esc_html__('Add New', 'publishpress-statuses'), 
            esc_html__('Add New', 'publishpress-statuses'), 
            $check_cap,
            'publishpress-statuses-add-new', 
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'publishpress-statuses',
            esc_html__('Settings'), 
            esc_html__('Settings'), 
            'manage_options',   // @todo: custom capability?
            'publishpress-statuses-settings', 
            [$this, 'render_admin_page']
        );
    }

    /**
     * Check whether custom status stuff should be loaded on this page
     *
     * @todo migrate this to the base module class
     */
    public static function is_post_management_page()
    {
        global $pagenow;

        if (! in_array(\PublishPress_Statuses::getCurrentPostType(), \PublishPress_Statuses::getEnabledPostTypes())) {
            return false;
        }

        $post_type_obj = get_post_type_object(\PublishPress_Statuses::getCurrentPostType());

        if (! current_user_can($post_type_obj->cap->edit_posts)) {
            return false;
        }

        // Disable the scripts for the post page if the plugin Visual Composer is enabled.
        if ('frontend' === \PP_Statuses_Functions::GET_key('vcv-action')) {
            return false;
        }

        // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
        return in_array(
            $pagenow,
            ['post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php']
        );
    }

    // @todo: merge into getPostStatuses() / register_post_status() calls

    public static function set_status_labels($status)
    {
        global $post;

        foreach (['icon', 'color'] as $prop) {
            if (empty($status->$prop)) {
                $status->$prop = '';
            }
        }

        if (empty($status->label)) {
            $status->label = ucwords($status->name);
        }

        if (empty($status->labels)) {
            $status->labels = (object) [];
        }

        if (!isset($status->labels->name)) {
            $status->labels->name = $status->label;
        }

        if (!isset($status->labels->caption)) {
            $status->labels->caption = $status->labels->name;
        }

        if (empty($status->label_count) && empty($status->_builtin)) {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular,WordPress.WP.I18n.NonSingularStringLiteralPlural
            $label = (!empty($status->labels->short)) ? $status->labels->short : $status->label;
            $status->label_count = _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>');
        }

        if (empty($status->labels->publish)) {
            // @todo: redundant with status definition?
            if ('pending' == $status->name) {
                $status->labels->publish = esc_html(__('Submit for Review'));
            } elseif ('approved' == $status->name) {
                $status->labels->publish = esc_html__('Approve', 'publishpress-statuses');
            } elseif ('assigned' == $status->name) {
                $status->labels->publish = esc_html__('Assign', 'publishpress-statuses');
            } elseif ('in-progress' == $status->name) {
                $status->labels->publish = esc_html__('Mark In Progress', 'publishpress-statuses');
            } elseif ('publish' == $status->name) {
                $status->labels->publish = esc_html(__('Publish'));
            } elseif ('future' == $status->name) {
                $status->labels->publish = esc_html(_x('Schedule', 'post action/button label'));
            } else {
                $submit_caption_length_limit = (defined('PP_STATUSES_CLASSIC_EDITOR_MAX_BUTTON_CAPTION_LENGTH'))
                ? PP_STATUSES_CLASSIC_EDITOR_MAX_BUTTON_CAPTION_LENGTH : 16;

                if (strlen($status->label) > $submit_caption_length_limit) {
                    $status->labels->publish = __('Submit');
                } else {
                    // translators: %s is the status label
                    $status->labels->publish = esc_attr(sprintf(__('Set to %s', 'publishpress-statuses'), $status->label));
                }
            }
        }

        if (empty($status->labels->save_as)) {
            if ('pending' == $status->name) {
                $status->labels->save_as = esc_html(__('Save as Pending'));
            } elseif (!in_array($status->name, ['publish', 'private']) && empty($status->public) && empty($status->private)) {
                $status->labels->save_as = esc_attr(sprintf(__('Save as %s', 'publishpress-statuses'), $status->label));
            } else {
                $status->labels->save_as = '';
            }
        }

        if (empty($status->labels->visibility)) {
            if ('publish' == $status->name) {
                $status->labels->visibility = esc_html(__('Published'));

            } elseif (!empty($status->public)) {
                $status->labels->visibility = (!defined('WPLANG') || ('en_EN' == WPLANG))  // translators: %s is the name of a custom public status
                ? esc_attr(sprintf(__('Public (%s)', 'publishpress-statuses'), $status->label))
                : $status->label;  // not currently customizable by Edit Status UI
            
            } elseif (!empty($status->private)) {
                $status->labels->visibility = $status->label;
            }
        }

        return $status;
    }

    public static function get_selectable_statuses($post = false, $args = []) {
        if ($post && is_scalar($post)) {
            $post = get_post($post);
        }

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

		if (!empty($args['post_status'])) {
			$post_status = $args['post_status'];	
		} else {
			$post_status = apply_filters(
				'publishpress_statuses_post_status',
				$post->post_status,
				$post
			);
		}

        $post_type = (!empty($args['post_type'])) ? $args['post_type'] : $post->post_type;

        if (!empty($post)) {
            $post_status = apply_filters('presspermit_editor_ui_status', $post_status, $post, $args);
        }

        if ('auto-draft' == $post_status) {
            $post_status = 'draft';
        }

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        $post_status_obj = apply_filters(
            'publishpress_statuses_get_post_status_object',
            $post_status_obj,
            $post_status,
            $post
        );

        $status_args = array_merge(['moderation' => true, 'internal' => false], compact('post_type'));

        if ($post) {
            $status_args = apply_filters(
                'publishpress_statuses_edit_post_status_args',
                $status_args,
                $post->ID
            );
        }

        $moderation_statuses = \PublishPress_Statuses::getPostStati($status_args, 'object');

        unset($moderation_statuses['future']);

        $default_by_sequence = \PublishPress_Statuses::instance()->workflow_by_sequence;

        if ($post && $is_administrator && $default_by_sequence 
        && empty($post_status_obj->public) && empty($post_status_obj->private) && ('future' != $post_status) 
        && (did_action('load-post.php') || did_action('load-post-new.php')) && !did_action('wp_ajax_pp_get_selectable_statuses')
        ) { // Add this item only for Classic Editor
            $_publish_obj = get_post_status_object('publish');
            $_publish_obj->save_as = __('Publish');
            $_publish_obj->publish = __('Advance Status', 'publishpress-statuses');
            $moderation_statuses['_public'] = $_publish_obj;
        }

        if (!$is_administrator) {
            $moderation_statuses = \PublishPress_Statuses::filterAvailablePostStatuses($moderation_statuses, $post_type, $post_status);
        }

        $moderation_statuses = apply_filters('presspermit_available_moderation_statuses', $moderation_statuses, $moderation_statuses, $post);

		$moderation_statuses = apply_filters(
			'publishpress_statuses_available_moderation_statuses', 
			array_merge(['draft' => get_post_status_object('draft')], $moderation_statuses), 
			$post
		);

        $_args = [];

        if (!empty($post_status_obj) && !empty($post_status_obj->name)) {
            // Don't exclude the current status, regardless of other arguments
            $_args['include_status'] = $post_status_obj->name;

            if ($post) {
                if ($default_by_sequence && \PublishPress_Statuses::instance()->options->status_dropdown_show_current_branch_only) {
                    if (!empty($post_status_obj->status_parent)) {
                        // If current status is a sub-status, only offer:
                        // * other sub-statuses in the same workflow branch
                        // * next status after current status
                        $_args['status_parent'] = $post_status_obj->status_parent;

                        if ($status_obj = \PublishPress_Statuses::getNextStatusObject($post->ID, compact('moderation_statuses', 'default_by_sequence', 'post_status'))) {
                            $_args['whitelist_status'] = $status_obj->name;
                        }
                    } else {
                        // If current status is in main workflow, only display:
                        // * other top level workflow statuses
                        // * sub-statuses of the current status
                        $_args['status_parent'] = '';

                        if ($status_children = \PublishPress_Statuses::getStatusChildren($post_status_obj->name, $moderation_statuses)) {
                            // These statuses will not be added to the array if already removed by filterAvailablePostStatuses(),
                            // but will be exempted from the top level status_parent requirement
                            $_args['whitelist_status'] = array_keys($status_children);
                        }
                    }
                }
            }
        }

        $moderation_statuses = \PublishPress_Statuses::orderStatuses($moderation_statuses, $_args);

        return $moderation_statuses;
    }

    // Archive the wp_terms description field as stored by PublishPress Planner, then import encoded properties from it
    public static function apply_status_maintenance($terms, $taxonomy) {
        global $pagenow;

        static $busy;

        if (!empty($busy)) {
            return $terms;
        }

        $busy = true;

        if (!is_admin()
        || (!empty($pagenow) && in_array($pagenow, ['index.php', 'plugins.php', 'plugin-install.php', 'plugin-editor.php', 'update.php', 'update-core.php', 'options.php', 'options-general.php', 'themes.php', 'theme-editor.php', 'customize.php', 'users.php', 'user-new.php']))
        || (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses'))
        ) {
            $busy = false;
            return $terms;
        }

        // This is the only status taxonomy that existed in Planner 3.x
        // The related Permissions Pro Status Control properties are also based on these Planner 3.x status definitions.
        if ((\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH != $taxonomy)) {
            $busy = false;
        	return $terms;
    	}
    
        if (!$terms) {
            if (!$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false])) {
                $busy = false;
                return $terms;
            }
        }

        // We are re-using the option row of Planner 3.x Custom Statuses module, but storing switch values as 1 / 0 instead of "on" / "off"
        // Re-check on each execution to make sure Planner 3.x wasn't re-activated and updated the array.
        $options = \PublishPress_Statuses::instance()->options;

        if (is_object($options) && !empty($options->post_types)) {
            foreach ($options->post_types as $post_type => $val) {
                if ('on' === $val) {
                    $options->post_types[$post_type] = 1;
                    $do_option_update = true;

                } elseif ('off' === $val) {
                    $options->post_types[$post_type] = 0;
                    $do_option_update = true;
                }
            }

            if (!empty($do_option_update)) {
                update_option('publishpress_custom_status_options', $options);
            }
        }

        // This is a backup of encoded Planner properties previously stored to term->description
        if (!$archived_term_descriptions = get_option('pp_statuses_archived_term_properties')) {
            $archived_term_descriptions = [];
        }

        if (!is_array($archived_term_descriptions)) {
            $archived_term_descriptions = (array) $archived_term_descriptions;
        }

        if ($force_planner_import = get_option('pp_statuses_force_planner_import')) {
            delete_option('pp_statuses_force_planner_import');

            // Back up the encoded Planner properties before potentially overwriting them with Planner-stored values
            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH != $term->taxonomy) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['color', 'icon', 'labels', 'post_type'] as $prop) {
                    if (isset($term_meta[$prop]) && !isset($term_meta["{$prop}_backup"])) {
                        $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                        
                        if (is_string($value)) {
                            update_term_meta($term->term_id, "{$prop}_backup_json", json_decode($value));
                            update_term_meta($term->term_id, "{$prop}_backup_preimport_json", json_decode($value));
                        }
                    }
                }
            }

            if ($original_archived = (array) get_option('pp_statuses_original_archived_term_properties')) {
                foreach ($original_archived as $term_id => $val) {
                    if (!isset($archived_term_descriptions[$term_id]) || defined('PP_STATUSES_USE_ORIGINAL_PLANNER_IMPORT_PROPERTIES')) {
                        $archived_term_descriptions[$term_id] = $val;
                    }
                }

                update_option('pp_statuses_archived_term_properties', $archived_term_descriptions);
            }
        }

        if (\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH == $taxonomy) {
        	$default_terms = get_terms(['taxonomy' => \PublishPress_Statuses::TAXONOMY_CORE_STATUS, 'hide_empty' => false]);

        	self::applyBackupOperations($default_terms, \PublishPress_Statuses::TAXONOMY_CORE_STATUS);
        }

        // Apply backup / restore / default request from settings. @todo: move?
        self::applyBackupOperations($terms, $taxonomy);

        $stored_status_positions = (array) get_option('publishpress_status_positions', []);

        $queued_term_descriptions = (array) get_option('pp_statuses_queued_term_properties', []);

        $new_queued_descriptions = 0;
        $new_archived_descriptions = 0;

        foreach ($terms as $k => $term) {
            // Extra precaution in case terms were passed from the wrong taxonomy
            if (\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH != $term->taxonomy) {
                continue;
            }

            $this_status_new = false;

            // Archive and clear the description field only if it's encoded
            if (!empty($term->description) && preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $term->description) 
            && (strlen($term->description) > 80) && (false === strpos($term->description, ' '))
            ) {
                // If this status was already imported from Planner with the same encoded properties, don't let it trigger a new import
                // (But do support import of additional statuses created by Planner 3.x when re-activated after initial Statuses install)
                if (empty($archived_term_descriptions[$term->term_id])
                || ($archived_term_descriptions[$term->term_id] != $term->description)
                ) {
                    $status_obj = get_post_status_object($term->slug);

                    $archived_term_descriptions[$term->term_id] = $term->description;
                    $new_archived_descriptions++;
                    $this_status_new = true;
                }

                // Now that we've logged the encoded Planner properties, clear the field so it can support actual description storage
                wp_update_term($term->term_id, $term->taxonomy, ['description' => '']);
                $terms[$k]->description = '';
            }

            // Queue the status for import processing if it user-created and encoded properties are new / changed to Statuses, 
            // but also if no status positions have been saved in PP Statuses, or if forcing Planner import (by selection in Statuses > Settings)
            // 
            // Regardless of this, updated Planner-stored properties will still be available for a forced input later.
            if (($this_status_new && empty($status_obj->pp_builtin)) || !$stored_status_positions || $force_planner_import) {
                $queued_term_descriptions[$term->term_id] = true;
                $new_queued_descriptions++;
            }
        }

        if ($new_archived_descriptions) {
            update_option('pp_statuses_archived_term_properties', $archived_term_descriptions);
        }

        if ($new_queued_descriptions) {
            update_option('pp_statuses_queued_term_properties', $queued_term_descriptions);
        }

        // Save a backup of the original Planner term properties archive. 
        // This can be useful in distinguishing Planner-created statuses from Statuses-based entries which Planner re-saved after being re-activated.
        if ($archived_term_descriptions && !get_option('pp_statuses_original_archived_term_properties')) {
            if (is_string($archived_term_descriptions)) {
                update_option('pp_statuses_original_archived_term_properties_json', json_decode($archived_term_descriptions));
            }
        }

        // Auto-import failsafe
        if ($auto_import = \PublishPress_Statuses::instance()->options->auto_import) {
            // Presence of this option indicates last import did not exit normally, so disable auto-import
            if ($last_import_time = get_option('publishpress_statuses_planner_import_gmt')) {
                if (time() - strtotime($last_import_time) > 10) {
                    $options = \PublishPress_Statuses::instance()->options;
                    $options->auto_import = 0;
                    update_option('publishpress_custom_status_options', $options);

                    $auto_import = 0;
                }
            }
        }

        delete_transient('publishpress_statuses_maintenance');

    	$busy = false;

        return $terms;
    }

    public static function applyBackupOperations($terms, $taxonomy, $args = []) {
        if (get_option('pp_statuses_set_backup_props')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_set_backup_props');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                foreach (['color', 'icon', 'labels', 'post_type'] as $prop) {
                    if ($meta_val = get_term_meta($term->term_id, $prop, true)) {
                        if (is_string($meta_val)) {
                            update_term_meta($term->term_id, "{$prop}_backup_json", json_decode($meta_val));
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_backup_colors')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_backup_colors');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['color'] as $prop) {
                    if (isset($term_meta["{$prop}_backup"])) {
                        $value = (is_array($term_meta["{$prop}_backup"])) ? reset($term_meta["{$prop}_backup"]) : $term_meta["{$prop}_backup"];
                        update_term_meta($term->term_id, $prop, $value);
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_backup_icons')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_backup_icons');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['icon'] as $prop) {
                    if (isset($term_meta["{$prop}_backup"])) {
                        $value = (is_array($term_meta["{$prop}_backup"])) ? reset($term_meta["{$prop}_backup"]) : $term_meta["{$prop}_backup"];
                        update_term_meta($term->term_id, $prop, $value);
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_backup_labels')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_backup_labels');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['labels'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_json"])) {
                        $value = (is_array($term_meta["{$prop}_backup_json"])) ? reset($term_meta["{$prop}_backup_json"]) : $term_meta["{$prop}_backup_json"];
                        
                        if (is_string($value)) {
                            update_term_meta($term->term_id, $prop, json_decode($value));
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_backup_post_types')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_backup_post_types');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['post_type'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_json"])) {
                        $value = (is_array($term_meta["{$prop}_backup_json"])) ? reset($term_meta["{$prop}_backup_json"]) : $term_meta["{$prop}_backup_json"];
                        
                        if (is_string($value)) {
                            update_term_meta($term->term_id, $prop, json_decode($value));
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_autobackup_colors')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_autobackup_colors');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['color'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_"])) {
                        $value = (is_array($term_meta["{$prop}_backup_"])) ? reset($term_meta["{$prop}_backup_"]) : $term_meta["{$prop}_backup_"];
                        update_term_meta($term->term_id, $prop, $value);

                        // Swap current value into autobackup slot
                        if (isset($term_meta[$prop])) {
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_autobackup_icons')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_autobackup_icons');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['icon'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_"])) {
                        $value = (is_array($term_meta["{$prop}_backup_"])) ? reset($term_meta["{$prop}_backup_"]) : $term_meta["{$prop}_backup_"];
                        update_term_meta($term->term_id, $prop, $value);

                        // Swap current value into autobackup slot
                        if (isset($term_meta[$prop])) {
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_autobackup_labels')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_autobackup_labels');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['labels'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_json"])) {
                        $value = (is_array($term_meta["{$prop}_backup_json"])) ? reset($term_meta["{$prop}_backup_json"]) : $term_meta["{$prop}_backup_json"];
                        
                        if (is_string($value)) {
                            update_term_meta($term->term_id, $prop, json_decode($value));
                        }

                        // Swap current value into autobackup slot
                        if (isset($term_meta[$prop])) {
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];

                            if (is_string($value)) {
                                update_term_meta($term->term_id, "{$prop}_backup_json", json_decode($value));
                            }
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_restore_autobackup_post_types')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_restore_autobackup_post_types');
                });
            });

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                $term_meta = get_term_meta($term->term_id);

                foreach (['post_type'] as $prop) {
                    if (isset($term_meta["{$prop}_backup_"])) {
                        $value = (is_array($term_meta["{$prop}_backup_json"])) ? reset($term_meta["{$prop}_backup_json"]) : $term_meta["{$prop}_backup_json"];
                        
                        if (is_string($value)) {
                            update_term_meta($term->term_id, $prop, json_decode($value));
                        }

                        // Swap current value into autobackup slot
                        if (isset($term_meta[$prop])) {
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];

                            if (is_string($value)) {
                                update_term_meta($term->term_id, "{$prop}_backup_json", json_decode($value));
                            }
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_colors')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_colors');
                });
            });

            $statuses = \PublishPress_Statuses::getPostStati([], 'object', ['show_disabled' => true]);

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (!empty($statuses[$term->slug]) && (!empty($statuses[$term->slug]->pp_builtin) || !empty($statuses[$term->slug]->_builtin))) {
                    $term_meta = get_term_meta($term->term_id);

                    foreach (['color'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before reverting to default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);

                            delete_term_meta($term->term_id, $prop);
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_icons')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_icons');
                });
            });

            $statuses = \PublishPress_Statuses::getPostStati([], 'object', ['show_disabled' => true]);

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (!empty($statuses[$term->slug]) && (!empty($statuses[$term->slug]->pp_builtin) || !empty($statuses[$term->slug]->_builtin))) {
                    

                    $term_meta = get_term_meta($term->term_id);

                    foreach (['icon'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before reverting to default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);

                            delete_term_meta($term->term_id, $prop);
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_labels')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_labels');
                });
            });

            $statuses = \PublishPress_Statuses::getPostStati([], 'object', ['show_disabled' => true]);

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (!empty($statuses[$term->slug]) && (!empty($statuses[$term->slug]->pp_builtin) || !empty($statuses[$term->slug]->_builtin))) {
                    $term_meta = get_term_meta($term->term_id);

                    foreach (['labels'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before reverting to default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);

                            delete_term_meta($term->term_id, $prop);
                        }
                    }

                    if (!empty($status_obj->default_label)) {
                        wp_update_term(
                            $term->term_id, $term->taxonomy, ['name' => $status_obj->default_label]
                        );
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_post_types')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_post_types');
                });
            });

            $statuses = \PublishPress_Statuses::getPostStati([], 'object', ['show_disabled' => true]);

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (!empty($statuses[$term->slug]) && (!empty($statuses[$term->slug]->pp_builtin) || !empty($statuses[$term->slug]->_builtin))) {
                    $term_meta = get_term_meta($term->term_id);

                    foreach (['post_type'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before reverting to default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);

                            delete_term_meta($term->term_id, $prop);
                        }
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_planner_colors')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_planner_colors');
                });
            });

            if (defined('PUBLISHPRESS_STATUSES_LEGACY_PLANNER_COLORS')) {
                $planner_colors = [
                    'pitch' => '#cc0000',
                    'assigned' => '#00bcc5',
                    'in-progress' => '#ccc500',
                    'draft' => '#f91d84',
                    'pending' => '#d87200',
                    'future' => '#655997',
                    'private' => '#000000',
                    'publish' => '#655997',
                ];
            } else {
                $planner_colors = [
                    'pitch' => '#887618',
                    'assigned' => '#009ba0',
                    'in-progress' => '#8c5400',
                    'draft' => '#767676',
                    'pending' => '#b95c00',
                    'private' => '#b40000',
                    'future' => '#8440f0',
                    'publish' => '#207720'
                ];
            }

            // If a status exists only as a code-defined default, create a term so termmeta can be stored
            foreach (array_keys($planner_colors) as $status) {
                foreach ($terms as $k => $term) {
                    if ($term->slug == $status) {
                        continue 2;
                    }
                }

                if ($status_obj = get_post_status_object($status)) {
                    if ((($taxonomy == \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH) && !empty($status_obj->moderation))
                    || (($taxonomy == \PublishPress_Statuses::TAXONOMY_CORE_STATUS) && !empty($status_obj->_builtin)))
                    {
                        if ($term_id = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $status])) {
                            $term = get_term_by('slug', $status, $taxonomy);
                            $terms []= $term;
                        }
                    }
                }
            }

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (isset($planner_colors[$term->slug])) {
                    $term_meta = get_term_meta($term->term_id);

                    foreach (['color'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before switching to Planner default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);
                        }

                        update_term_meta($term->term_id, $prop, $planner_colors[$term->slug]);
                    }
                }
            }
        }

        if (get_option('pp_statuses_default_planner_icons')) {
            add_action('publishpress_statuses_maintenance_done', function() {
                add_action('shutdown', function() {
                    delete_option('pp_statuses_default_planner_icons');
                });
            });

            $planner_icons = [
                'pitch' => 'dashicons-post-status',
                'assigned' => 'dashicons-admin-users',
                'in-progress' => 'dashicons-format-status',
                'draft' => 'dashicons-media-default',
                'pending' => 'dashicons-clock',
                'future' => 'dashicons-calendar-alt',
                'private' => 'dashicons-lock',
                'publish' => 'dashicons-yes',
            ];

            // If a status exists only as a code-defined default, create a term so termmeta can be stored
            foreach (array_keys($planner_icons) as $status) {
                foreach ($terms as $k => $term) {
                    if ($term->slug == $status) {
                        continue 2;
                    }
                }

                if ($status_obj = get_post_status_object($status)) {
                    if ((($taxonomy == \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH) && !empty($status_obj->moderation))
                    || (($taxonomy == \PublishPress_Statuses::TAXONOMY_CORE_STATUS) && !empty($status_obj->_builtin)))
                    {
                        if ($term_id = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $status])) {
                            $term = get_term_by('slug', $status, $taxonomy);
                            $terms []= $term;
                        }
                    }
                }
            }

            foreach ($terms as $k => $term) {
                // Extra precaution in case terms were passed from the wrong taxonomy
                if (!in_array($term->taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_CORE_STATUS])) {
                    continue;
                }

                if (isset($planner_icons[$term->slug])) {
                    $term_meta = get_term_meta($term->term_id);

                    foreach (['icon'] as $prop) {
                        if (isset($term_meta[$prop])) {
                            // Auto-backup current value before switching to Planner default
                            $value = (is_array($term_meta[$prop])) ? reset($term_meta[$prop]) : $term_meta[$prop];
                            update_term_meta($term->term_id, "{$prop}_backup_", $value);
                        }

                        update_term_meta($term->term_id, $prop, $planner_icons[$term->slug]);
                    }
                }
            }
        }
    }
}
