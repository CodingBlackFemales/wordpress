<?php

class PublishPress_Statuses_Pro {
    public $version;
    private static $instance = null;

    public static function instance($reload = false) {
        if ( is_null(self::$instance) || $reload) {
            self::$instance = new \PublishPress_Statuses_Pro(false);
            self::$instance->load();
        }

        return self::$instance;
    }

    public function __construct($do_load = true)
    {
        $this->version = PUBLISHPRESS_STATUSES_PRO_VERSION;

        if ($do_load) {
            $this->load();
        }
    }

    private function load() {
        global $pagenow;
        
        add_action('publishpress_statuses_version_check', [$this, 'versionUpdate']);

        add_action('init', [$this, 'ensureDefaults'], 12);
        add_action('init', [$this, 'handleActivation'], 20);

        if (!defined('PRESSPERMIT_PRO_VERSION') || version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '>=')) {
            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/modules/status-control/status-control.php');
        }

        $this->loadUpdater();

        if (is_admin()) {
            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/LicenseAdmin.php');
            new \PublishPress\StatusesPro\LicenseAdmin();
        }
    }

    public function versionUpdate() {
        $last_statuses_version = get_option('publishpress_statuses_version');

        if (!$last_statuses_version || version_compare($last_statuses_version, '1.1-beta', '<')) {
            delete_option('publishpress_statuses_processed_roles');
        
        } elseif (version_compare($last_statuses_version, '1.1.3-beta', '<')) {
            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/NotificationDefaults.php');
            $notification_defaults = new \PublishPress_Statuses\NotificationDefaults();

            $notification_defaults->fixDefaultNotificationsMetaKey();

            update_option('statuses_pro_restore_notifications', true);
        }
    }

    public function ensureDefaults() {
        global $script_name;

        if (defined('PUBLISHPRESS_VERSION')) {
            $flush_notifications = defined('PUBLISHPRESS_STATUSES_FLUSH_NOTIFICATIONS');

            $activated = get_option('publishpress_statuses_pro_activate');

            // In spite of the option name, these are actually default notifications for unpublished posts, not revisions
            if (!get_option('_pp_statuses_default_revision_notifications') || $activated || !empty($flush_notifications)) {
                require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/NotificationDefaults.php');
                $notification_defaults = new \PublishPress_Statuses\NotificationDefaults();
                $notification_defaults->applyPostDefaults($flush_notifications);
            }

            if (get_option('statuses_pro_restore_notifications') && (empty($script_name) || ('plugin-install.php' != $script_name))) {
                require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/NotificationDefaults.php');

                if (empty($notification_defaults)) {
                    $notification_defaults = new \PublishPress_Statuses\NotificationDefaults();
                }

                $notification_defaults->restoreDefaultNotificationWorkflows();
            }
        }
    }

    // plugin activation script
    public function handleActivation() {
        global $wpdb;
        
        if (get_option('publishpress_statuses_pro_activate')) {
            delete_option('publishpress_statuses_pro_activate');

            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && !get_option('rvy_permissions_compat_mode', false)) {
                $revision_statuses = \PublishPress_Statuses::instance()->getPostStatuses(['for_revision' => true], 'names', 'and', ['context' => 'load']);
                $revision_status_csv = implode("','", array_map('sanitize_key', $revision_statuses));
        
                $wpdb->query("UPDATE $wpdb->posts SET post_mime_type = post_status WHERE post_status IN ('$revision_status_csv')");                             // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                
                update_option('publishpress_statuses_pro_activate_step2', true);
            }

        } elseif (get_option('publishpress_statuses_pro_activate_step2')) {
            delete_option('publishpress_statuses_pro_activate_step2');
            
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && !get_option('rvy_permissions_compat_mode', false)) {
                $revision_statuses = \PublishPress_Statuses::instance()->getPostStatuses(['for_revision' => true], 'names', 'and', ['context' => 'load']);
                $private_statuses = get_post_stati(['private' => true], 'names');
                $revision_status_csv = implode("','", array_map('sanitize_key', array_diff($revision_statuses, ['publish', 'private', 'draft'], $private_statuses)));
                
                $wpdb->query("UPDATE $wpdb->posts SET post_status = 'pending' WHERE post_status IN ('$revision_status_csv')");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                update_option('publishpress_statuses_pro_activate_step3', true);        
            }
        } elseif (get_option('publishpress_statuses_pro_activate_step3')) {
            delete_option('publishpress_statuses_pro_activate_step3');

            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && !get_option('rvy_permissions_compat_mode', false)) {
                $wpdb->query("UPDATE $wpdb->posts SET post_status = 'draft' WHERE post_status IN ('draft-revision')");       // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            }
        }
    }

    public function loadUpdater() {
		require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/library/Factory.php');
        $container = \PublishPress\Statuses\Factory::get_container(self::instance());

        return $container['edd_container']['update_manager'];
	}

	public function keyStatus($refresh = false)
    {
        $opt_val = get_option('publishpress_statuses_edd_key');

        if (!is_array($opt_val)) {
            $opt_val = [];
        }
    
        if (!$refresh && (!is_array($opt_val) || count($opt_val) < 2 || !isset($opt_val['license_key']))) {
            return false;
        } else {
            if ($refresh) {
                $key_string = (isset($opt_val['license_key'])) ? $opt_val['license_key'] : '';
    
                require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/library/Factory.php');
                $container      = \PublishPress\Statuses\Factory::get_container();
                $licenseManager = $container['edd_container']['license_manager'];
    
                if ($key = $licenseManager->sanitize_license_key($key_string)) {
                    $status = $licenseManager->validate_license_key($key, PUBLISHPRESS_STATUSES_EDD_ITEM_ID);
                } else {
                    $status = false;
                }
    
                if (!is_scalar($status)) {
                    return false;
                }
    
                $opt_val['license_status'] = $status;
                update_option('publishpress_statuses_edd_key', $opt_val);
    
                if ('valid' == $status) {
                    return true;
                } elseif('expired' == $status) {
                    return 'expired';
                }
            } else {
                if ('valid' == $opt_val['license_status']) {
                    return true;
                } elseif ('expired' == $opt_val['license_status']) {
                    return 'expired';
                }
            }
        }
    
        return false;
    }

    public function keyActive($refresh = false)
    {
        return in_array($this->keyStatus($refresh), [true, 'valid', 'expired'], true);                
    }
}
