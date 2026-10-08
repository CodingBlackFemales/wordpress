<?php
namespace PublishPress\Statuses;

class StatusControl {
    private static $instance = null;
    private static $attributes = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new StatusControl();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public static function defineClassAliases() {
        class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\PPS');
        class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\PPS');
        class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\DB\PPS');

        if (is_admin()) {
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\UI\PPS');
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\UI\Handlers\PPS');
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\UI\Dashboard\PPS');
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\UI\Gutenberg\PPS');
        }

        if (defined('PUBLISHPRESS_REVISIONS_VERSION')) {
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\Revisions\PPS');

        } elseif (defined('REVISIONARY_VERSION')) {
            class_alias('\PublishPress\Statuses\StatusControl', '\PublishPress\Statuses\StatusControl\Revisionary\PPS');
        }
    }

    public function getUser($user_id = false, $name = '', $args = []) {
        global $current_user;
        
        if (function_exists('presspermit')) {
            return presspermit()->getUser($user_id, $name, $args);
        
        } elseif ($user_id === false) {
            return $current_user;
        } else {
            return new WP_User($user_id);
        }
    }

    public static function getCustomStatuses($args = [])
    {
        global $wp_post_statuses;

        $defaults = ['post_type' => '', 'ignore_moderation_statuses' => false, 'ignore_private_stati' => false];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        $custom_stati = [];

        foreach ($wp_post_statuses as $status => $st) {
            if (
            ((!$ignore_moderation_statuses || empty($st->moderation)) && empty($st->_builtin) && !in_array($status, ['pending', 'draft', 'future'])) 
            || ((!$ignore_private_stati || empty($st->private)) && ('private' != $status))
            ) {
                $custom_stati [] = $status;
            }
        }

        return $custom_stati;
    }

    public static function customStatiUsed($args = [])
    {
        global $wpdb, $wp_post_statuses;

        $defaults = ['post_type' => '', 'ignore_moderation_statuses' => false, 'ignore_private_stati' => false, 'ignore_status' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $status_args = [];

        if (!empty($ignore_moderation_statuses)) {
            $status_args['moderation'] = false;
            $status_args['for_revision'] = false;
        }

        if (!empty($ignore_private_stati)) {
            $status_args['private'] = false;
        }

        $custom_stati = \PublishPress_Statuses::getCustomStatuses($status_args, 'names');

        if (!empty($args['ignore_status'])) {
            $custom_stati = array_diff($custom_stati, (array)$args['ignore_status']);
        }

        $status_csv = implode("','", array_map('sanitize_key', $custom_stati));

        if ($post_type) {
            // Direct query of posts table for plugin admin query

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $post_exists = (int)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE post_status IN ('$status_csv') AND post_type = %s LIMIT 1",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $post_type
                )
            );
        } else {
            // Direct query of posts table for plugin admin query
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $post_exists = (int)$wpdb->get_var(
                "SELECT ID FROM $wpdb->posts WHERE post_status IN ('$status_csv') LIMIT 1"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        }

        return $post_exists;
    }

    public function orderTypes($types, $args = [])
    {
        $defaults = ['order_property' => '', 'item_type' => '', 'labels_property' => ''];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if ('post' == $item_type) {
            $post_types = get_post_types([], 'object');
        } elseif ('taxonomy' == $item_type) {
            $taxonomies = get_taxonomies([], 'object');
        }

        $ordered_types = [];
        foreach (array_keys($types) as $name) {
            if ('post' == $item_type) {
                $ordered_types[$name] = (isset($post_types[$name]->labels->singular_name))
                    ? $post_types[$name]->labels->singular_name
                    : '';
            } elseif ('taxonomy' == $item_type) {
                $ordered_types[$name] = (isset($taxonomies[$name]->labels->singular_name))
                    ? $taxonomies[$name]->labels->singular_name
                    : '';
            } else {
                if (!is_object($types[$name])) {
                    return $types;
                }

                if ($order_property) {
                    $ordered_types[$name] = (isset($types[$name]->$order_property))
                        ? $types[$name]->$order_property
                        : '';
                } else {
                    $ordered_types[$name] = (isset($types[$name]->labels->$labels_property))
                        ? $types[$name]->labels->$labels_property
                        : '';
                }
            }
        }

        asort($ordered_types);

        foreach (array_keys($ordered_types) as $name) {
            $ordered_types[$name] = $types[$name];
        }

        return $ordered_types;
    }

    public static function privacyStatusesDisabled() {
        // This replaces constant PPS_NATIVE_CUSTOM_STATI_DISABLED (previously defined dynamically in StatusesHooks::actRegistrations())
        return !get_option('presspermit_privacy_statuses_enabled', 1);  // option shared with Capabilities Pro
    }

    public static function customPrivacyEditCapsEnabled() {
        if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS')) {
            return PPS_CUSTOM_PRIVACY_EDIT_CAPS;
        } else {
            $options = \PublishPress_Statuses::instance()->options;
            return !empty($options) && !empty($options->custom_privacy_edit_caps);
        }
    }

    public static function customStatusesEnabled($post_type = '', $ignore_status = [])
    {
        global $wp_post_statuses;

        $ignore_status = (array)$ignore_status;

        foreach ($wp_post_statuses as $status => $st) {
            if (!in_array($status, $ignore_status, true) && ((!empty($st->moderation) && !in_array($status, ['private', 'future'])) 
            || (!empty($st->private) && ('private' != $status))) && empty($st->_builtin)) {

                if (!$post_type || !isset($st->post_type) 
                || (is_array($st->post_type) && (!$st->post_type || in_array($post_type, $st->post_type)))
                ) {    
                    return true;
                }
            }
        }

        return false;
    }

    public static function defaultStatusOrder()
    {
        // Prior implementation's status order values
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        return [
            'draft' => 0,
            'pitch' => 2,
            'assigned' => 5,
            'in-progress' => 7,
            'pending' => 10,
            'pending-review' => 10,  // @todo
            'approved' => 18,
        ];*/

        return [
            'draft' => 0,
            'pitch' => 100,
            'assigned' => 200,
            'in-progress' => 300,
            'pending' => 500,
            'pending-review' => 500,  // @todo
            'approved' => 600,
        ];
    }

    public static function attributes()
    {
        if ( is_null(self::$attributes) ) {
            // \PublishPress\StatusCapabilities filters instance class based on our hook
            self::$attributes = \PublishPress\StatusCapabilities::instance();
        }

        return self::$attributes;
    }

    public static function registerPrivacyConditions($statuses)
    {
        global $wp_post_statuses;

        if (empty($statuses)) {
            $statuses = $wp_post_statuses;
        }

        $suppress_edit_caps = !PPS::customPrivacyEditCapsEnabled();

        // register each custom post status as an attribute condition with mapped caps
        foreach ($statuses as $status => $status_obj) {
            if (!empty($status_obj->private)) {
                if ('private' == $status) {
                    if (!$suppress_edit_caps && defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                        $metacap_map = ['copy_post' => "copy_private_posts"];
                        $cap_map = [];
                    } else {
                        continue;
                    }
                } else {
                    \PublishPress\StatusCapabilities::registerCondition('force_visibility', $status, ['label' => $status_obj->label]);

                    if (!empty($wp_post_statuses[$status])) {
                        $wp_post_statuses[$status]->capability_status = $status;
                    }

                    $metacap_map = ($suppress_edit_caps) 
                    ? ['read_post' => "read_{$status}_posts", 'edit_post' => "edit_private_posts", 'delete_post' => "delete_private_posts"] 
                    : ['read_post' => "read_{$status}_posts", 'edit_post' => "edit_{$status}_posts", 'delete_post' => "delete_{$status}_posts"];

                    if (!$suppress_edit_caps && defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                        $metacap_map['copy_post'] = "copy_{$status}_posts";
                    }

                    // Custom Visibility statuses have never mapped edit_others or delete_others capabilities
                    $cap_map = ($suppress_edit_caps) ? ['set_posts_status' => "status_change_{$status}"] : ['set_posts_status' => "set_posts_{$status}"];
                }

                \PublishPress\StatusCapabilities::registerCondition('post_status', $status, [
                    'label' => $status_obj->label,
                    'metacap_map' => $metacap_map,
                    'cap_map' => $cap_map,
                    'pattern_role_availability_requirement' => [
                        'edit_posts' => 'edit_published_posts', 
                        'delete_posts' => 'delete_published_posts'
                    ],
                ]);
            }
        }

        \PublishPress\StatusCapabilities::instance()->process_status_caps($statuses, ['private' => true]);
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
   /*
    * set_conditions[attribute][condition] = true
    * args : ['force_flush' => false]
    */ 
    public static function setItemCondition(
        $attribute, $scope, $item_source, $item_id, $set_conditions, $assign_for = 'item', $args = [])
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/AttributesUpdate.php');

        return StatusControl\DB\AttributesUpdate::set_item_condition(
            $attribute, $scope, $item_source, $item_id, $set_conditions, $assign_for, $args
        );
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
     * args : ['inherited_only' => false]
     */ 
    public static function clearItemCondition($attribute, $scope, $item_source, $item_id, $assign_for, $args = [])
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/AttributesUpdate.php');

        return StatusControl\DB\AttributesUpdate::clear_item_condition(
            $attribute, $scope, $item_source, $item_id, $assign_for, $args
        );
    }

    public static function orderStatuses($statuses = false, $args = [])
    {
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // defaults: 'min_order' => 0, 'status_parent' => false, 'ignore_status' => [], 'include_status' => []

        return \PublishPress_Statuses::orderStatuses($statuses, $args);
    }

    public static function getDescendantIds($item_source, $item_id, $args = [])
    {
        require_once(PUBLISHPRESS_STATUS_CONTROL_ABSPATH . '/classes/PressShack/Ancestry.php');
        
        switch ($item_source) {
            case 'post':
                // Back compat for existing getDescendantIds() calls
                if (isset($args['post_types'])) {
                    $args['post_type'] = (array) $args['post_types'];
                    unset($args['post_types']);
                }
                
                if (!isset($args['post_type']) && empty($args['any_type_or_taxonomy'])) {
                    $args['post_type'] = false;
                }

                return \PressShack\Ancestry::getPageDescendants($item_id, $args);
                break;

            case 'term':
                // Back compat for existing getDescendantIds() calls
                if (!isset($args['taxonomy']) && !empty($args['any_type_or_taxonomy'])) {
                    $args['taxonomy'] = false;
                }

                return \PressShack\Ancestry::getTermDescendants($item_id, $args);
                break;

            default:
                return [];
        }
    }

    public static function havePermission($perm_name, $args = [])
    {
        $defaults = ['force_refresh' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $user = publishpress_status_control()->getUser();

        if (!isset($user->cfg) || !is_array($user->cfg)) {
            $user->cfg = [];
        }

        if (!isset($user->cfg[$perm_name])) {
            $user->cfg[$perm_name] = [];
        }

        switch ($perm_name) {
            case 'moderate_any':
                $return = !empty($user->allcaps['pp_moderate_any']);
                break;
            default:
        }

        $user->cfg[$perm_name] = $return;
        return $return;
    }

    public static function haveStatusPermission($perm_name, $post_type, $post_status, $args = [])
    {
        $perms = self::getUserStatusPermissions($perm_name, $post_type, $post_status, $args);
        return !empty($perms[$post_status]);
    }

    public static function getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args = [])
    {
        return \PublishPress_Statuses::getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args);
    }

    public static function publishpressStatusesActive($post_type = '', $args = [])
    {
        return true;
    }
}
