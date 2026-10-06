<?php

function pp_capabilities_statuses_info() {
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
    global $pagenow, $current_user;

    if (!defined('PUBLISHPRESS_STATUSES_VERSION')) {
        add_action('upgrader_process_complete', function($package, $extra) {
            if (!empty($package->skin) && !empty($package->skin->result) && is_array($package->skin->result) && !empty($package->skin->result['destination_name']) 
            && ('publishpress-statuses' == $package->skin->result['destination_name'])
            ) {
                if (!empty($_REQUEST['action']) && ('install-plugin' == $_REQUEST['action'])  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && !empty($_SERVER['HTTP_REFERER'])                                           // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && false === strpos($_SERVER['HTTP_REFERER'], 'plugins.php')                  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && false === strpos($_SERVER['HTTP_REFERER'], 'plugin-install.php')           // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                ) {
                    activate_plugin('publishpress-statuses/publishpress-statuses.php');
                }
            }
        }, 10, 2);
        
        if ('admin.php' == $pagenow) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            if (!empty($_REQUEST['page']) && ('pp-capabilities-settings' == $_REQUEST['page'])) {
                // Display an explanatory caption and install / activation link on Capabilities > Settings > Capabilities

                $statuses_info = pp_capabilities_statuses_info();
                
                if (empty($statuses_info['statuses_installed'])) {
                    add_action(
                        'admin_enqueue_scripts', 
                        function() {
                            add_thickbox();
                            wp_enqueue_script('updates');
                        }
                    );
                }
            
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            } elseif (!empty($_REQUEST['page']) && ('pp-capabilities' == $_REQUEST['page'])) {
                // Also ensure any user gets one-time display of PublishPress Statuses notice on Capabilities > Capabilities screen

                if ($status_options = get_option('publishpress_custom_status_options')) {
                    $status_options = maybe_unserialize($status_options);

                    if (is_object($status_options) && !empty($status_options->enabled) && ('off' != $status_options->enabled)) {

                        if (!get_user_option('publishpress_capabilities_statuses_notice_done')) {

                            $statuses_info = pp_capabilities_statuses_info();
                            
                            if (empty($statuses_info['statuses_installed'])) {
                                add_action(
                                    'admin_enqueue_scripts', 
                                    function() {
                                        add_thickbox();
                                        wp_enqueue_script('updates');
                                    }
                                );
                            }

                            add_action(
                                'all_admin_notices',
                                function() {
                                    global $current_user;

                                    if (!defined('PUBLISHPRESS_STATUSES_VERSION')) {
                                        $statuses_info = pp_capabilities_statuses_info();

                                        $settings_url = (current_user_can('manage_options')) ? admin_url('admin.php?page=pp-capabilities-settings&pp_tab=capabilities') : '#';

                                        echo "<div id='message' class='error pp-admin-notice'>";

                                        if (!empty($statuses_info['statuses_installed'])) {
                                            printf(
                                                esc_html__('Custom status control is disabled until you activate the %1$sPublishPress Statuses%2$s plugin. See %3$sCapabilities > Settings%4$s for details.', 'capabilities-pro'),
                                                '<a href="' . esc_url($statuses_info['info_url']) . '">',
                                                '</a>',
                                                '<a href="' . esc_url($settings_url) . '">',
                                                '</a>'
                                            );
                                        } else {
                                            printf(
                                                esc_html__('Custom status control is disabled until you install the %1$sPublishPress Statuses%2$s plugin. See %3$sCapabilities > Settings%4$s for details.', 'capabilities-pro'),
                                                '<a href="' . esc_url($statuses_info['info_url']) . '" class="thickbox">',
                                                '</a>',
                                                '<a href="' . esc_url($settings_url) . '">',
                                                '</a>'
                                            );
                                        }

                                        echo '</div>';
                                    }

                                    update_user_option($current_user->ID, 'publishpress_capabilities_statuses_notice_done', true);
                                }
                            );
                        }
                    }
                }
            }
        }
    }
}
