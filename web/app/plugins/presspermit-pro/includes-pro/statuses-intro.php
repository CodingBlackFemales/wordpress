<?php

function pp_permissions_statuses_info() {
    static $return_val;

    if (!empty($return_val)) {
        return $return_val;
    }

    if (!function_exists('get_plugins')) {
        if (@file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    $statuses_installed = false;

    if (function_exists('get_plugins')) {
        $plugins = get_plugins();
        $statuses_installed = !empty($plugins['publishpress-statuses/publishpress-statuses.php']);
    }

    if ($statuses_installed) {
        if (current_user_can('activate_plugins')) {
            if ($statuses_installed) {
                $_url = "plugins.php";
                $info_url = self_admin_url($_url);
            } else {
                $_url = "plugin-install.php?tab=plugin-information&plugin=publishpress-statuses&TB_iframe=true&width=600&height=800";
                $info_url = self_admin_url($_url);
            }
        } else {
            $info_url = 'https://wordpress.org/plugins/publishpress-statuses';
        }
    } else {
        if (current_user_can('install_plugins')) {
            if ($statuses_installed) {
                $_url = "plugins.php";
                $info_url = self_admin_url($_url);
            } else {
                $_url = "plugin-install.php?tab=plugin-information&plugin=publishpress-statuses&TB_iframe=true&width=600&height=800";
                $info_url = self_admin_url($_url);
            }
        } else {
            $info_url = 'https://wordpress.org/plugins/publishpress-statuses';
        }
    }

    $return_val = compact('info_url', 'statuses_installed');

    return $return_val;
}

if (is_admin()) {
    global $pagenow;

    if (!defined('PUBLISHPRESS_STATUSES_VERSION')) {
        add_action('upgrader_process_complete', function($package, $extra) {
            if (!empty($package->skin) && !empty($package->skin->result) && is_array($package->skin->result) && !empty($package->skin->result['destination_name']) 
            && ('publishpress-statuses' == $package->skin->result['destination_name'])
            ) {
                if (!empty($_REQUEST['action']) && ('install-plugin' == $_REQUEST['action'])  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && !empty($_SERVER['HTTP_REFERER'])                                           // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && false === strpos($_SERVER['HTTP_REFERER'], 'plugins.php')                  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && false === strpos($_SERVER['HTTP_REFERER'], 'plugin-install.php')           // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                ) {
                    activate_plugin('publishpress-statuses/publishpress-statuses.php');
                }
            }
        }, 10, 2);

        if (('plugins.php' == $pagenow) ||
        (('admin.php' == $pagenow) && !empty($_REQUEST['page']) && ('presspermit-settings' == $_REQUEST['page']))  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        ) {
            $statuses_info = pp_permissions_statuses_info();
            
            if (empty($statuses_info['statuses_installed'])) {
                add_action(
                    'admin_enqueue_scripts', 
                    function() {
                        add_thickbox();
                        wp_enqueue_script('updates');
                    }
                );
            }
        }
    }
}
