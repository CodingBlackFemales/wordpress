<?php
namespace PublishPress\Capabilities;

use PublishPress\Capabilities\Factory;

/*
 * PublishPress Capabilities Pro
 *
 * Pro functions and filter handlers with broad scope
 *
 */

class Pro
{
    // object references
    private static $instance = null;

    public static function instance($args = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new Pro($args);
        }

        return self::$instance;
    }

    private function __construct()
    {

        if (function_exists('bbp_get_version')) {
            require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/classes/bbPress.php');
            new bbPress();
        }
    }

    static function customStatusPermissionsAvailable() {
        return class_exists('PublishPress_Statuses') || (defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status'));
    }

    static function customStatusCapabilities() {
        if (class_exists('\PublishPress\StatusCapabilities')) {
            return \PublishPress\StatusCapabilities::instance();

        } elseif (class_exists('PublishPress\Permissions\Statuses') && method_exists('PublishPress\Permissions\Statuses', 'attributes')) {
            return \PublishPress\Permissions\Statuses::attributes();
        }
    }

    static function customStatusPostMetaPermissions($post_type = '', $post_status = '', $args = []) {
        if (class_exists('PublishPress\StatusCapabilities')) {
            $enabled = \PublishPress\StatusCapabilities::customStatusPostMetaPermissions($post_type, $post_status, $args);

        } else {
        	// legacy status control without Statuses plugin
            $enabled = self::presspermitStatusControlActive() && defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status');

            if ($post_status && $enabled) {
                if (!$attributes = \PublishPress\Permissions\Statuses::attributes()) {
                    return false;
                }

                if (is_object($post_status)) {
                    $status_obj = $post_status;
                    $post_status = strval($post_status->name);
                } else {
                    $status_obj = get_post_status_object($post_status);
                }

                if (empty($attributes->attributes['post_status']->conditions[$post_status])) {
                    return false;
                }

                if ($post_type) {
                    if ($status_obj) {
                        if (!empty($status_obj->post_type) && !in_array($post_type, $status_obj->post_type)) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($enabled && !self::presspermitStatusControlActive()) { // Permissions Pro Status Control module locks this Capabilities option on
            $enabled = get_option('cme_custom_status_postmeta_caps') || !empty($args['ignore_capabilities_option']);
        }

        return $enabled;
    }

    public static function presspermitStatusControlActive() {
        return class_exists('PublishPress\Statuses\StatusControl') || (function_exists('presspermit') && \presspermit()->moduleActive('status-control'));
    }

    public static function customPrivacyStatusesAvailable() {
        return self::presspermitStatusControlActive() && get_option('presspermit_privacy_statuses_enabled');
    }

    static function getStatusCaps($cap, $post_type, $post_status) {
        if (!self::customStatusPostMetaPermissions()) {
            return [$cap];
        }

        if (class_exists('PublishPress\StatusCapabilities')) {
            if (!$attributes = \PublishPress\StatusCapabilities::instance()) {
                return [$cap];
            }

        } elseif (!class_exists('PublishPress\Permissions\Statuses')) {
            return [$cap];

        } elseif (!$attributes = \PublishPress\Permissions\Statuses::attributes()) {
            return [$cap];
        }

        if (!isset($attributes->attributes['post_status']->conditions[$post_status])) {
            return [$cap];
        }

        $caps = [];

        if (isset($attributes->condition_metacap_map[$post_type][$cap]['post_status'][$post_status])) {
            $caps = array_merge($caps, (array) $attributes->condition_metacap_map[$post_type][$cap]['post_status'][$post_status]);
        }

        if (!empty($attributes->condition_cap_map[$cap]['post_status'][$post_status])) {
            global $wp_post_statuses;

            if ($post_type && !empty($wp_post_statuses[$post_status]->post_type) && !in_array($post_type, $wp_post_statuses[$post_status]->post_type)) {
                // This status does not have custom capabilities enabled for this post type
                return [$cap];
            }

            $caps = array_merge($caps, (array) $attributes->condition_cap_map[$cap]['post_status'][$post_status]);
        }

        return $caps;
    }

    /**
     * @return EDD_SL_Plugin_Updater
     */
    public function load_updater()
    {
        require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/library/Factory.php');
    	$container = \PublishPress\Capabilities\Factory::get_container();
		return $container['edd_container']['update_manager'];
    }

    public function keyStatus($refresh = false)
    {
        require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/pro-key.php');
        return _cme_key_status($refresh);
    }

    public function keyActive($refresh = false)
    {
        return in_array($this->keyStatus($refresh), [true, 'valid', 'expired'], true);
    }

} // end class
