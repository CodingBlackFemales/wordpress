<?php

namespace PublishPress;

/**
 * Attributes
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2026 PublishPress
 *
 * Custom status capabilities are implemented by defining 'post_status' as a PublishPress Permissions Attribute.
 *
 * For any attribute, the generic equivalent of "status" is "condition"
 *
 */
class StatusCapabilities
{
    const VERSION = '1.3.2';

    // Custom status capabilities are implemented by defining 'post_status' as an Attribute.
    var $attributes = [];               // attributes[attribute] = object with the following properties: conditions, label, taxonomies

                                        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    var $condition_cap_map = [];        // condition_cap_map[basic_cap_name][attribute][condition] = condition_cap_name

                                        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    var $condition_metacap_map = [];    // condition_metacap_map[object_type][basic_metacap_name][attribute][condition] = condition_cap_name
    var $all_custom_condition_caps = [];
    var $processed_statuses = [];
    var $all_privacy_caps;
    var $all_moderation_caps;

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            $class = apply_filters('publishpress_status_capabilities_class', 'PublishPress\StatusCapabilities');

            self::$instance = new $class();

            self::registerAttribute('post_status', 'post');

            require_once(__DIR__ . '/CapabilityFilters.php');
            StatusCapabilities\CapabilityFilters::instance();
        }

        return self::$instance;
    }

    protected function __construct($args = [])
    {
        $defaults = [];
        foreach(array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        add_filter('publishpress_statuses_required_caps', [$this, 'fltStatusRequiredCaps'], 10, 5);

        add_action('init', [$this, 'act_post_stati_prep'], 48);

        add_action('init', [$this, 'actForceDistinctPostCaps'], 50);

        // Register custom capabilities for statuses right after they are processed by PublishPress Statuses
        $init_priority = (defined('PUBLISHPRESS_ACTION_PRIORITY_INIT')) ? intval(PUBLISHPRESS_ACTION_PRIORITY_INIT) + 1 : 11;
        add_action('init', [$this, 'act_normal_registrations'], $init_priority);

        // Possibly also register custom capabilities for any late-registered statuses @todo: remove?
        add_action('wp_loaded', [$this, 'act_late_registrations']);

        add_filter('presspermit_get_post_statuses', [$this, 'fltGetPostStatuses'], 10, 5);  // deprecated
        add_filter('_presspermit_get_post_statuses', [$this, 'fltStatuses'], 20, 4);

        add_filter('publishpress_status_properties', [$this, 'fltStatusProperties']);

        add_action('admin_enqueue_scripts', [$this, 'actEnqueueScripts']);

        add_filter('publishpress_statuses_block_editor_args', [$this, 'flt_publishpress_statuses_block_editor_args'], 10, 2);

        add_filter('cme_list_visibility_status', [$this, 'fltListVisibilityStatus'], 10, 2);
        add_filter('cme_visibility_cap_property_prefixes', [$this, 'fltVisibilityCapPropertyPrefixes']);
        add_filter('cme_visibility_cap_property_tips', [$this, 'fltVisibilityCapPropertyTips']);

        if (is_admin() && class_exists('PublishPress_Statuses')) {
            require_once(__DIR__ . '/AdminPro.php');
            new \PublishPress\StatusCapabilities\AdminPro(); 
        }
    }

    // @todo: review
    function act_post_stati_prep()
    {
        global $wp_post_statuses;

        // set default properties
        foreach (array_keys($wp_post_statuses) as $status) {
            if (!isset($wp_post_statuses[$status]->moderation))
                $wp_post_statuses[$status]->moderation = false;
        }
    }

    function actForceDistinctPostCaps()
    {
        global $wp_post_types;

        $generic_caps = ['post' => ['set_posts_status' => 'set_posts_status'], 'page' => ['set_posts_status' => 'set_posts_status']];

        // post types which are enabled for PP filtering must have distinct type-related cap definitions
        foreach (
            array_intersect(
                get_post_types(
                    ['public' => true, 'show_ui' => true],
                    'names',
                    'or'
                ),
                array_keys(self::getEnabledPostTypes())
            ) as $post_type
        ) {
            $type_caps = [];

            if ('post' == $post_type) {
                $type_caps['set_posts_status'] = 'set_posts_status';
            } else {
                $type_caps['set_posts_status'] = str_replace('_post', "_$post_type", 'set_posts_status');
            }

            $wp_post_types[$post_type]->cap = (object)array_merge((array)$wp_post_types[$post_type]->cap, $type_caps);

            $plural_type = self::getPlural($post_type);

            self::addTypeCaps($type_caps);

            foreach (self::getPostStatuses(['moderation' => true, 'post_type' => $post_type, 'disabled' => false]) as $status_name) {
                $cap_property = "set_{$status_name}_posts";
                $wp_post_types[$post_type]->cap->$cap_property = str_replace("_posts", "_{$plural_type}", $cap_property);
            }
        }
    }

    private static function getPlural($post_type) {
        global $wp_post_types;

        if (class_exists('PublishPress\Permissions\Capabilities')) {
            $plural_type = \PublishPress\Permissions\Capabilities::getPlural($post_type, $wp_post_types[$post_type]);

        } elseif (function_exists('_cme_get_plural')) {
            $plural_type = _cme_get_plural($post_type, $wp_post_types[$post_type]);

        } else {
            $plural_type = $post_type . 's';
        }

        return $plural_type;
    }

    private static function addTypeCaps($type_caps) {
        global $cme_cap_helper;

        if (function_exists('presspermit')) {
            $cap_helper = \presspermit()->capDefs();

        } elseif (!empty($cme_cap_helper)) {
            $cap_helper = $cme_cap_helper;
        }

        if (!empty($cap_helper)) {
            $cap_helper->all_type_caps = array_merge($cap_helper->all_type_caps, array_fill_keys($type_caps, true));
        }
    }

    // Process any statuses that were registered by PublishPress Statuses, or on/before the init action priority it uses
    function act_normal_registrations() {
        $this->registerConditions();
    }

    // Process any statuses that were registered between PublishPress Statuses init and wp_loaded
    // Statuses that were already processed will be skipped.
    function act_late_registrations() {
        $this->registerConditions(['late' => true]);
    }

    private function registerConditions($args = []) {
        global $wp_post_statuses, $current_user;

        $is_administrator = is_super_admin() || current_user_can('administrator');

        $skip_metacaps = !self::customStatusPostMetaPermissions()
        && (
            // Still register meta caps for administration on our plugin screens, but only if the user is allowed to administer them
            (!$is_administrator && empty($current_user->allcaps['pp_moderate_any']))
            || (!is_admin() || !isset($_SERVER['SCRIPT_NAME']) || false == strpos(sanitize_text_field($_SERVER['SCRIPT_NAME']), 'admin.php')        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || !isset($_REQUEST['page']) || !in_array($_REQUEST['page'], ['pp-capabilities', 'pp-capabilities-settings', 'publishpress-statuses'])  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            )
        );

        if (!$skip_metacaps) {
            $statuses = apply_filters(
                'presspermit_register_access_statuses',
                self::getPostStatuses(['moderation' => true], 'object', 'and', ['context' => 'load'])
            );

            // register each custom post status as an attribute condition with mapped caps
            foreach ($statuses as $status => $status_obj) {
                if (isset($status_obj->capability_status) && ('' === $status_obj->capability_status)) {
                    continue;
                }

                $_status = (isset($status_obj->capability_status) && ($status != $status_obj->capability_status) 
                && !empty($wp_post_statuses[$status_obj->capability_status])) 
                ? $status_obj->capability_status 
                : $status;

                $metacap_map = ['edit_post' => "edit_{$_status}_posts", 'delete_post' => "delete_{$_status}_posts"];

                self::registerCondition('post_status', $status, [
                    'label' => $status_obj->label,
                    'metacap_map' => $metacap_map,
                    'cap_map' => [
                        'set_posts_status' => "set_posts_{$_status}",
                        'edit_others_posts' => "edit_others_{$status}_posts",
                        'delete_others_posts' => "delete_others_{$status}_posts"
                    ]
                ]);
            }
        }

        if (!empty($args['late'])) {
            do_action('publishpress_status_capabilities_late_load');
        } else {
        	// Permissions Pro < 4.0.11 executed this function on the wp_loaded action, firing this action
            do_action('publishpress_status_capabilities_loaded');
        }

        if (empty($statuses)) {
            $statuses = $wp_post_statuses;
        }

        $this->process_status_caps($statuses);

        if (!empty($args['late'])) {
            // Support use of PublishPress-defined status change capability where type-specific status capabilities are not enabled
            foreach($this->condition_cap_map as $cap_name => $conditions) {
                if (0 === strpos($cap_name, 'set_') && !empty($conditions['post_status'])) {
                    foreach(array_keys($wp_post_statuses) as $status) {
                        if (!isset($this->condition_cap_map[$cap_name]['post_status'][$status]) && !in_array($status, ['draft', 'future', 'draft-revision'])) {
                            $_status = str_replace('-', '_', $status);
                            $this->condition_cap_map[$cap_name]['post_status'][$status] = "status_change_{$_status}";
                        }
                    }
                }
            }
        }
    }

    /*
    * args:
    *  label = translated string
    */
    public static function registerAttribute($attribute, $source_name = 'post', $args = [])
    {
        $defaults = ['label' => $attribute, 'taxonomies' => []];
        $args = array_merge($defaults, $args);
        $args['conditions'] = [];
        $args['source_name'] = $source_name;

        if (empty(self::instance()->attributes[$attribute])) {
            self::instance()->attributes[$attribute] = (object)$args;
        }
    }

    // args:
    //   label = translated string
    //   cap_map = ['base_cap_property' => restriction_cap_pattern] where restriction_cap_pattern may contain "_posts"
    //      (will be converted to plural name of obj type)
    //
    //   metacap_map = ['meta_cap' => restriction_cap_pattern]
    //
    //   exemption_cap = base cap property corresponding to a capability whose presence in a role indicates the role
    //      should be credited with all caps for this status
    //      (i.e. if a role has $cap->publish_posts, it also has all 'restricted_submission' caps)
    public static function registerCondition($attribute, $condition, $args = [])
    {
        $defaults = ['label' => $condition, 'cap_map' => [], 'metacap_map' => []];
        $args = array_merge($defaults, $args);

        if (!isset(self::instance()->attributes[$attribute])) {
            return;
        }

        $args['name'] = $condition;
        self::instance()->attributes[$attribute]->conditions[$condition] = (object)$args;
    }

    // @todo: still needed?
    function fltStatusProperties($status) {
        $statuses = $this->fltGetPostStatuses([$status->name => $status]);

        return array_pop($statuses);
    }

    function fltStatuses($statuses, $status_args, $return_args, $function_args) {
        return $this->fltGetPostStatuses($statuses, $status_args, $return_args, '', $function_args);
    }

    function fltGetPostStatuses($statuses, $status_args = [], $return_args = [], $operator = '', $function_args = []) {
        $status_cap_status = array_intersect_key((array) get_option('presspermit_status_capability_status'), $statuses);

        foreach ($statuses as $k => $status) {
            if (!is_object($status)) {
                continue;
            }

            if (in_array($status->name, ['draft', 'publish', 'private', 'future', 'draft-revision'])) {
                continue;

            // Workflow statuses, including Pending, default to standard capabilities
            } elseif (empty($status->public) && empty($status->private)) {
                $statuses[$k]->capability_status = (isset($status_cap_status[$status->name])) ? $status_cap_status[$status->name] : '';

            // Visibility statuses default to custom capabilities
            } elseif (!empty($status->private) && empty($status->_builtin)) {
                $statuses[$k]->capability_status = (isset($status_cap_status[$status->name])) ? $status_cap_status[$status->name] : $status->name;
            }
        }

        return $statuses;
    }

    function fltStatusRequiredCaps($status_caps, $cap_type, $status_name, $post_type, $args = []) {
        switch ($cap_type) {
            case 'set_status':
                $type_obj = get_post_type_object($post_type);

                if (!empty($type_obj->cap->set_posts_status)) {
                    if ($check_caps = $this->getConditionCaps(
                        $type_obj->cap->set_posts_status,
                        $post_type,
                        'post_status',
                        $status_name
                    )) {
                        $status_caps = $check_caps;
                    }
                }

                break;
        }

        return $status_caps;
    }

    function is_metacap($caps)
    {
        return (bool)array_intersect((array)$caps, ['read_post', 'read_page', 'edit_post', 'edit_page', 'delete_post', 'delete_page']);
    }

    public static function postStatusHasCustomCaps($status)
    {
        return !empty(self::instance()->attributes['post_status']->conditions[$status]);
    }

    public static function customStatusPostMetaPermissions($post_type = '', $post_status = '', $args = []) {
        if (!$attributes = self::instance()) {
            return false;
        }

        if (is_object($post_status)) {
            $status_obj = $post_status;
            $post_status = strval($post_status->name);
        } else {
            $status_obj = get_post_status_object($post_status);
        }

        if ($post_status && !self::postStatusHasCustomCaps($post_status)) {
            return false;
        }

        if ($post_type) {
            if ($status_obj) {
                if (!empty($status_obj->post_type) && !in_array($post_type, $status_obj->post_type)) {
                    return false;
                }
            }
        }

        return self::presspermitStatusControlActive() || !defined('PUBLISHPRESS_CAPS_PRO_VERSION')
        || get_option('cme_custom_status_postmeta_caps') || !empty($args['ignore_capabilities_option']);
    }

    public static function presspermitStatusControlActive() {
        return (function_exists('presspermit') && \presspermit()->moduleActive('status-control') && \presspermit()->moduleActive('collaboration')) 
        || class_exists('PublishPress\Statuses\StatusControl');
    }

    public static function updateCapabilityStatus($status, $set_capability_status) {
        if (!$capability_status = get_option('presspermit_status_capability_status')) {
            $capability_status = [];
        }

        $status = sanitize_key($status);
        $set_capability_status = sanitize_key($set_capability_status);

        if (!$set_capability_status) {
            delete_option("presspermit_custom_{$status}_caps");
        }

        $capability_status[$status] = $set_capability_status;

        update_option("presspermit_status_capability_status", $capability_status);
        update_option("presspermit_custom_{$status}_caps", true);  // @todo: eliminate
    }

    public static function getEnabledPostTypes() {
        if (function_exists('presspermit')) {
            return \presspermit()->getEnabledPostTypes([], 'object');

        } elseif (function_exists('cme_get_assisted_post_types')) {
            $post_types = cme_get_assisted_post_types();

        } elseif (class_exists('PublishPress_Statuses')) {
            $post_types = \PublishPress_Statuses::instance()->get_enabled_post_types();

        } else {
            $option_name = (defined('PPC_VERSION') && !defined('PRESSPERMIT_VERSION')) ? 'pp_enabled_post_types' : 'presspermit_enabled_post_types';
            $enabled = (array) get_option( $option_name, array( 'post' => true, 'page' => true ) );

            $post_types = array_intersect( $post_types, array_keys( array_filter( $enabled ) ) );
        }

        $type_objects = [];

        foreach($post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $type_objects[$post_type] = get_post_type_object($post_type);
            }
        }

        return $type_objects;
    }

    function process_status_caps($statuses = [], $status_args = [])
    {
        global $wp_post_statuses;

        if (empty($statuses)) {
            $statuses = $wp_post_statuses;
        }

        if (!isset($this->all_custom_condition_caps)) {
            $this->all_custom_condition_caps = ['post' => []];
            $this->all_privacy_caps = ['post' => []];
            $this->all_moderation_caps = ['post' => []];
        }

        if (isset($this->attributes['post_status'])) {
            foreach (array_keys($this->attributes['post_status']->conditions) as $cond) {
                if (empty($statuses[$cond])) {
                	continue;
				}

                $status_obj = (!empty($statuses[$cond])) ? $statuses[$cond] : false;

                if (!empty($status_args)) {
                    foreach ($status_args as $prop => $val) {
                        if (is_scalar($val)) {
                            if (!empty($val) && (empty($status_obj->{$prop}) || ($val !== $status_obj->{$prop}))) {
                                continue 2;
                            }

                            if (empty($val) && !empty($status_obj->{$prop})) {
                                continue 2;
                            }
                        }
                    }
                }

                foreach ($this->getEnabledPostTypes([], 'object') as $object_type => $type_obj) {
                    if (!empty($statuses[$cond]->post_type) && !in_array($object_type, $statuses[$cond]->post_type)) {
                        continue;
                    }

                    // convert 'edit_restricted_posts' to 'edit_restricted_pages', etc.
                    $plural_name = (isset($type_obj->plural_name)) ? $type_obj->plural_name : $object_type . 's';

                    // Map condition caps to post meta caps( 'edit_post', 'delete_post', etc. ) because:
                    //  (1) mapping to expanded caps is problematic b/c for private posts, 'edit_private_posts' is required but 'edit_posts' is not
                    //  (2) WP converts type-specific meta caps back to basic metacap equivalent before calling 'map_meta_cap'
                    foreach (
                        $this->attributes['post_status']->conditions[$cond]->metacap_map
                        as $base_cap_property => $condition_cap_pattern
                    ) {
                        // If the type object has "edit_restricted_posts" defined, use it.
                        $replacement_cap = (isset($type_obj->cap->$condition_cap_pattern))
                        ? $type_obj->cap->$condition_cap_pattern
                        : str_replace('_posts', "_{$plural_name}", $condition_cap_pattern);

                        $this->condition_metacap_map[$object_type][$base_cap_property]['post_status'][$cond] = $replacement_cap;

                        switch ($base_cap_property) {
                            case 'read_post':
                                $type_cap = defined('PRESSPERMIT_READ_PUBLIC_CAP') ? PRESSPERMIT_READ_PUBLIC_CAP : 'read';
                                break;
                            case 'edit_post':
                                $type_cap = $type_obj->cap->edit_posts;
                                break;
                            case 'delete_post':
                                if (isset($type_obj->cap->delete_posts))
                                    $type_cap = $type_obj->cap->delete_posts;
                                else
                                    $type_cap = str_replace('edit_', 'delete_', $type_obj->cap->edit_posts);
                                break;
                            default:
                                $type_cap = $base_cap_property;
                        }

                        $this->all_custom_condition_caps[$object_type][$replacement_cap] = $type_cap;

                        if (!empty($status_obj->private))
                            $this->all_privacy_caps[$object_type][$replacement_cap] = $type_cap;

                        if (!empty($status_obj->moderation))
                            $this->all_moderation_caps[$object_type][$replacement_cap] = $type_cap;
                    }

                    foreach (
                        $this->attributes['post_status']->conditions[$cond]->cap_map
                        as $base_cap_property => $condition_cap_pattern
                    ) {
                        // If the type object has "edit_restricted_posts" defined, use it.
                        $replacement_cap = (isset($type_obj->cap->$condition_cap_pattern))
                        ? $type_obj->cap->$condition_cap_pattern
                        : str_replace('_posts', "_{$plural_name}", $condition_cap_pattern);

                        $cap_name = (isset($type_obj->cap->$base_cap_property))
                        ? $type_obj->cap->$base_cap_property
                        : $base_cap_property;

						// Previous versions defined edit_others capability as "edit_others_{$status_name}_attachments" for all post types
                        if (!isset($this->condition_cap_map[$cap_name]['post_status'][$cond]) || defined('PRESSPERMIT_LEGACY_STATUS_CAP_DEFINITIONS')) {
                            $this->condition_cap_map[$cap_name]['post_status'][$cond] = $replacement_cap;
                        }

                        $this->all_custom_condition_caps[$object_type][$replacement_cap] = $cap_name;

                        if (!empty($status_obj->private)) {
                            $this->all_privacy_caps[$object_type][$replacement_cap] = $cap_name;
                        }

                        if (!empty($status_obj->moderation)) {
                            $this->all_moderation_caps[$object_type][$replacement_cap] = $cap_name;
                        }
                    }
                } // end foreach object type
            } // end foreach condition
        }
    }

    function getConditionCaps($reqd_caps, $object_type, $attribute, $conditions, $args = [])
    {
        $defaults = ['merge_caps' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if ($merge_caps) {
            $merge_caps = array_map('sanitize_key', (array) $merge_caps);
        }

        $cond_caps = [];

        $reqd_caps = (array)$reqd_caps;

        foreach ($reqd_caps as $base_cap) {
            foreach ((array)$conditions as $cond) {
                if (!empty($this->condition_cap_map[$base_cap][$attribute][$cond])) {
                    $cond_caps[] = $this->condition_cap_map[$base_cap][$attribute][$cond];
                }

                if (!empty($this->condition_metacap_map[$object_type][$base_cap][$attribute][$cond])) {
                    $cond_caps[] = $this->condition_metacap_map[$object_type][$base_cap][$attribute][$cond];
                }
            }
        }

        if ($merge_caps) {
            $cond_caps = array_merge($cond_caps, $merge_caps);

            // If a status-specific edit_others_{$status}_posts capability is defined, don't also assign / require edit_others_posts
            foreach(['edit_others_posts', 'delete_others_posts'] as $base_cap) {
                if (!empty($this->condition_cap_map[$base_cap][$attribute][$cond])) {
                    if ($type_obj = get_post_type_object($object_type)) {
                        if (!empty($type_obj->cap->$base_cap)) {
                            $cond_caps = array_diff($cond_caps, [$type_obj->cap->$base_cap]);
                        }
                    }
                }
            }
        }

        return array_unique($cond_caps);
    }

    public static function getPostStatuses($args, $return = 'names', $operator = 'and', $function_args = [])
    {
        if (isset($args['post_type'])) {
            $post_type = $args['post_type'];
            unset($args['post_type']);
            $stati = get_post_stati($args, 'object', $operator);

            foreach ($stati as $status => $obj) {
                if (!empty($obj->post_type) && !array_intersect((array)$post_type, (array)$obj->post_type))
                    unset($stati[$status]);
            }

            $statuses = ('names' == $return) ? array_keys($stati) : $stati;
        } else {
            $statuses = get_post_stati($args, $return, $operator);
        }

        return apply_filters('presspermit_get_post_statuses', $statuses, $args, $return, $operator, $function_args);
    }

    function actEnqueueScripts() {
        if (is_admin() && !empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page'])) {    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            $url = trailingslashit(plugins_url('', __FILE__));

            wp_enqueue_script(
                'pp-permissions-statuses',
                $url . "common/js/permissions-statuses{$suffix}.js",
                ['jquery', 'jquery-ui-sortable'],
                self::VERSION,
                true
            );

            wp_localize_script(
                'pp-permissions-statuses',
                'PPPermissionsStatuses',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'ppNonce' => wp_create_nonce('pp-permissions-statuses-nonce')
                ]
            );
        }
    }

    function flt_publishpress_statuses_block_editor_args($args, $filter_args) {
        if (function_exists('presspermit')) {
            $user = \presspermit()->getUser();
        } else {
            global $current_user;
            $user = $current_user;
        }

        $filter_args = (array) $filter_args;

        $defaults = ['post_id' => 0, 'post_type' => '', 'status' => ''];

        foreach ($defaults as $var => $default_val) {
            $$var = (isset($filter_args[$var])) ? $filter_args[$var] : $default_val;
        }

        $attributes = self::instance();

        if ($check_caps = $attributes->getConditionCaps('edit_post', $post_type, 'post_status', $status)) {
            if (array_diff($check_caps, array_keys(array_filter($user->allcaps)))) {
                $args["redirectURL{$status}"] = admin_url("edit.php?post_type={$post_type}&pp_submission_done={$status}");
            }
        }

        return $args;
    }

    public function fltListVisibilityStatus($display_status, $status) {
        if (('private' == $status) && defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS) {
            $display_status = true;
        }

        return $display_status;
    }

    public function fltVisibilityCapPropertyPrefixes($cap_property_prefixes) {
        if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS) {
            if (defined('PRESSPERMIT_VERSION')) {
                $cap_property_prefixes['list'] = __('List', 'publishpress-statuses-pro');
            }

            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && function_exists('rvy_get_option') && rvy_get_option('copy_posts_capability')) {
                $cap_property_prefixes['copy'] = __('Revise', 'publishpress-statuses-pro');
            }
        }

        return $cap_property_prefixes;
    }

    public function fltVisibilityCapPropertyTips($cap_property_tips) {
        if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS) {
            if (defined('PRESSPERMIT_VERSION')) {
                $cap_property_tips['list'] = __( 'Edit Posts listing includes posts in this status (even if not editable)', 'publishpress-statuses-pro');
            }

            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && function_exists('rvy_get_option') && rvy_get_option('copy_posts_capability')) {
                $cap_property_tips['copy'] = __( 'Can create revisions of posts in this status', 'publishpress-statuses-pro');
            }
        }

        return $cap_property_tips;
    }

    // Deprecated, using __() instead
    // Wrapper to prevent poEdit from adding core WordPress strings to the plugin .po
    public static function __wp($string, $unused = '')
    {
        return __($string);
    }

    // Deprecated, using _e() instead
    // Wrapper to prevent poEdit from adding core WordPress strings to the plugin .po
    public static function _e_wp($string, $unused = '')
    {
        return _e($string);
    }

    // Deprecated, using _x() instead
    public static function _x_wp($string, $context) {
        return _x($string, $context);
    }
}
