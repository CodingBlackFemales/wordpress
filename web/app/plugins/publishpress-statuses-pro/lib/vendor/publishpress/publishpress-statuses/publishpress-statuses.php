<?php
/**
 * Plugin Name: PublishPress Statuses Free
 * Plugin URI:  https://publishpress.com/statuses
 * Description: Manage and create post statuses to customize your editorial workflow
 * Version: 1.3.6
 * Author: PublishPress
 * Author URI:  https://publishpress.com/
 * Text Domain: publishpress-statuses
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 * License: GPLv3
 *
 * Copyright (c) 2026 PublishPress
 *
 * GNU General Public License, Free Software Foundation <https://www.gnu.org/licenses/gpl-3.0.html>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     PublishPress Statuses
 * @author      PublishPress
 * @copyright   Copyright (C) 2026 PublishPress. All rights reserved.
 * @license		GNU General Public License version 3
 * @link		https://publishpress.com/
 *
 **/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

 global $wp_version;

 $min_php_version = '7.2.5';
 $min_wp_version  = '5.5';
 
 $invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
 $invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');
 
 // If the PHP version is not compatible, terminate the plugin execution and show an admin notice.
 if (is_admin() && $invalid_php_version) {
     add_action(
         'admin_notices',
         function () use ($min_php_version) {
             if (current_user_can('activate_plugins')) {
                 echo '<div class="notice notice-error"><p>';
                 printf(
                     'PublishPress Statuses requires PHP version %s or higher.',
                     esc_html($min_php_version)
                 );
                 echo '</p></div>';
             }
         }
     );
 }
 
 // If the WP version is not compatible, terminate the plugin execution and show an admin notice.
 if (is_admin() && $invalid_wp_version) {
     add_action(
         'admin_notices',
         function () use ($min_wp_version) {
             if (current_user_can('activate_plugins')) {
                 echo '<div class="notice notice-error"><p>';
                 printf(
                     'PublishPress Statuses requires WordPress version %s or higher.',
                     esc_html($min_wp_version)
                 );
                 echo '</p></div>';
             }
         }
     );
 }
 
 if ($invalid_php_version || $invalid_wp_version) {
     return;
 }

$pro_active = false;

global $publishpress_statuses_loaded_by_pro;

$publishpress_statuses_loaded_by_pro = strpos(str_replace('\\', '/', __FILE__), 'vendor/publishpress/');

// Detect separate Pro plugin activation, but not self-activation (this file loaded in vendor library by Pro)
if (false === $publishpress_statuses_loaded_by_pro) {
    foreach ((array)get_option('active_plugins') as $plugin_file) {
        if (false !== strpos($plugin_file, 'publishpress-statuses-pro.php')) {
            $pro_active = true;
            break;
        }
    }

    if (!$pro_active && is_multisite()) {
        foreach (array_keys((array)get_site_option('active_sitewide_plugins')) as $plugin_file) {
            if (false !== strpos($plugin_file, 'publishpress-statuses-pro.php')) {
                $pro_active = true;
                break;
            }
        }
    }

    if ($pro_active) {
        add_filter(
            'plugin_row_meta',
            function($links, $file)
            {
                if ($file == plugin_basename(__FILE__)) {
                    $links[]= __('<strong>This plugin can be deleted.</strong>', 'publishpress-statuses');
                }

                return $links;
            },
            10, 2
        );
        return;
    }
}

if (! defined('PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH')) {
    define('PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH', __DIR__ . '/lib/vendor');
}

if (!defined('PUBLISHPRESS_STATUSES_FILE') && !$publishpress_statuses_loaded_by_pro) {
    $includeFileRelativePath = '/publishpress/instance-protection/include.php';
    if (file_exists(PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . $includeFileRelativePath)) {
        require_once PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . $includeFileRelativePath;
    }

    if (class_exists('PublishPressInstanceProtection\\Config')) {
        $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
        $pluginCheckerConfig->pluginSlug    = 'publishpress-statuses';
        $pluginCheckerConfig->pluginFolder  = 'publishpress-statuses';
        $pluginCheckerConfig->pluginName    = 'PublishPress Statuses';

        $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
    }

    if (! class_exists('ComposerAutoloaderInitPublishPressStatuses')
        && file_exists(PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . '/autoload.php')
    ) {
        require_once PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . '/autoload.php';
    }

    // Load bundled-translations library
    $bundledTranslationsPath = '/publishpress/bundled-translations/core/include.php';
    if (file_exists(PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . $bundledTranslationsPath)) {
        require_once PUBLISHPRESS_STATUSES_INTERNAL_VENDORPATH . $bundledTranslationsPath;
    }

    // Initialize bundled translations
    add_action('plugins_loaded', function() {
        if (class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
            $bundledTranslations = new PublishPress\BundledTranslations\BundledTranslations(
                'publishpress-statuses',
                __DIR__ . '/languages',
                __FILE__
            );
            $bundledTranslations->init();
        }
    }, 10);
}

if ((!defined('PUBLISHPRESS_STATUSES_FILE') && !$pro_active) || $publishpress_statuses_loaded_by_pro) {
    define('PUBLISHPRESS_STATUSES_FILE', __FILE__);
    define('PUBLISHPRESS_STATUSES_ABSPATH', __DIR__);

    if (!defined('PUBLISHPRESS_STATUSES_CLASSPATH_COMMON')) {
        define('PUBLISHPRESS_STATUSES_CLASSPATH_COMMON', __DIR__ . '/classes/PressShack');
    }

    function publishpress_statuses_load() {
        global $publishpress_statuses_loaded_by_pro;

        $publishpress_statuses_loaded_by_pro = strpos(str_replace('\\', '/', __FILE__), 'vendor/publishpress/');

        if (defined('PUBLISHPRESS_STATUSES_VERSION')) {
            return;
        }

        if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '4.0-beta4', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice error">
                    <p><?php printf(
                        // translators: %s is a version number
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Planner to version %s or higher.', 'publishpress-statuses'),
                        '4.0-beta4'
                    ); 
                    ?></p>
                </div>
                <?php
            });

            $interrupt_load = true;
        }

        if (defined('PRESSPERMIT_PRO_VERSION') && version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice error">
                    <p><?php printf(
                        // translators: %s is a version number
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Permissions Pro to version %s or higher.', 'publishpress-statuses'),
                        '4.6.4'
                    ); 
                    ?></p>
                </div>
                <?php
            });
        
            $interrupt_load = true;
        }
        
        if (defined('PUBLISHPRESS_CAPS_PRO_VERSION') && version_compare(PUBLISHPRESS_CAPS_PRO_VERSION, '2.11-beta2', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice error">
                    <p><?php printf(
                        // translators: %s is a version number
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Capabilities Pro to version %s or higher.', 'publishpress-statuses'),
                        '2.11-beta2'
                    ); 
                    ?></p>
                </div>
                <?php
            });

            $interrupt_load = true;
        }

        global $pagenow;

        if (is_admin() && isset($pagenow) && ('customize.php' == $pagenow)) {
            $interrupt_load = true;
        }
        
        if (empty($interrupt_load)) {
            define('PUBLISHPRESS_STATUSES_VERSION', '1.3.6');

            define('PUBLISHPRESS_STATUSES_URL', trailingslashit(plugins_url('', __FILE__)));    // @todo: vendor lib

            define('PUBLISHPRESS_STATUSES_DIR', __DIR__);

            require_once(__DIR__ . '/lib/PP_Statuses_Functions.php');

            if (!$publishpress_statuses_loaded_by_pro) {
                require_once(__DIR__ . '/includes-core/Core.php');
                new \PublishPress\Statuses\Core();
    
                if (is_admin()) {
                    require_once(__DIR__ . '/includes-core/CoreAdmin.php');
                    new \PublishPress\Statuses\CoreAdmin();
                }

                // Include a back compat class if an older version of status-capabilities library will be used
                add_filter('publishpress_status_capabilities_library',
                    function($status_caps_package_to_use) {
                        if (!empty($status_caps_package_to_use) && !empty($status_caps_package_to_use->version)) {
                            if (version_compare($status_caps_package_to_use->version, '1.3.0', '<')) {
                                require_once(__DIR__ . '/includes-core/PublishPress_Functions.php');
                            }
                        }

                        return $status_caps_package_to_use;
                    }, 99
                );
            }

            require_once(__DIR__ . '/publishpress-module/Module_Base.php');
            new \PublishPress\PPP_Module_Base();

            require_once(__DIR__ . '/PublishPress_Statuses.php');
            PublishPress_Statuses::instance();
        
        	do_action('publishpress_statuses_init');

            add_action(
                'init', 
                function() {
                    @load_plugin_textdomain('publishpress-statuses', false, dirname(plugin_basename(__FILE__)) . '/languages');     // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
                },
                5
            );

            if (!class_exists('PP_Custom_Status') 
            && (defined('PRESSPERMIT_VERSION') || (!defined('PUBLISHPRESS_VERSION') || version_compare(PUBLISHPRESS_VERSION, '4.0', '<')))
            ) {
                class_alias('\PublishPress_Statuses', '\PP_Custom_Status');
            }
        }
    }

    // negative priority to precede any default WP action handlers
    if ($publishpress_statuses_loaded_by_pro) {
        publishpress_statuses_load();	// Pro support
    } else {
        add_action('plugins_loaded', 'publishpress_statuses_load', -5);

        // Disable early loading of capabilities-pro textdomain with Capabilities Pro 2.44.0
        add_action(
            'plugins_loaded',
            function() {
                if (defined('PUBLISHPRESS_CAPS_PRO_VERSION') && version_compare(PUBLISHPRESS_CAPS_PRO_VERSION, '2.45.0', '<')) {
                    global $l10n;

                    if ( !isset( $l10n[ 'capabilities-pro' ] ) ) {
                        $l10n[ 'capabilities-pro' ] = new NOOP_Translations();  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    }
                }
            }, 9
        );

        add_action(
            'plugins_loaded',
            function() {
                if (defined('PUBLISHPRESS_CAPS_PRO_VERSION') && version_compare(PUBLISHPRESS_CAPS_PRO_VERSION, '2.45.0', '<')) {
                    global $l10n;
                    unset( $l10n[ 'capabilities-pro' ] );   // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                }
            }, 11
        );
    }

    register_activation_hook(
        __FILE__, 
        function() {
            if (!get_option('publishpress_statuses_activation_done')) {
                update_option('publishpress_statuses_activation_done', true);
                update_option('publishpress_statuses_activate', true);
            }
        }
    );
    
    register_deactivation_hook(
        __FILE__,
        function()
        {
            delete_transient('publishpress_statuses_maintenance');
            delete_option('publishpress_statuses_planner_import_gmt');
    
            if (!get_option('publishpress_version') || defined('PUBLISHPRESS_STATUSES_NO_PLANNER_BACK_COMPAT')) {
                return;
            }
    
            // Restore archived PublishPress Planner 3.x term descriptions (with encoded status properties), in case it will be re-activated
            if ($archived_term_descriptions = get_option('pp_statuses_archived_term_properties')) {
    
                // Use hardcoded taxonomy string here because class PublishPress_Statuses is not loaded
                $terms = get_terms(['taxonomy' => 'post_status', 'hide_empty' => false]);
    
                if (is_array($terms)) {
                    foreach ($terms as $term) {
                        if (!empty($archived_term_descriptions[$term->term_id])) {
                            $description = $archived_term_descriptions[$term->term_id];
    
                            if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $description) 
                            && (strlen($description) > 80) && (false === strpos($description, ' '))
                            ) {
                                // Use hardcoded taxonomy string here because class PublishPress_Statuses is not loaded
                                wp_update_term($term->term_id, 'post_status', ['description' => $description]);
                            }
                        }
                    }
                }
            }
    
            // Set post_types option storage value back to "on" / "off"
            $options = get_option('publishpress_custom_status_options');
    
            if (is_object($options) && isset($options->enabled)) {
                if ($options->enabled) {
                    $options->enabled = 'on';
                    $do_option_update = true;
                } else {
                    $options->enabled = 'off';
                    $do_option_update = true;
                }
            }
    
            if (is_object($options) && !empty($options->post_types)) {
                foreach ($options->post_types as $post_type => $val) {
                    if ($val) {
                        $options->post_types[$post_type] = 'on';
                        $do_option_update = true;
                    } else {
                        $options->post_types[$post_type] = 'off';
                        $do_option_update = true;
                    }
                }
            }
    
            if (!empty($options->loaded_once)) {
                unset($options->loaded_once);
                $do_option_update = true;
            }
    
            if (!empty($do_option_update)) {
                update_option('publishpress_custom_status_options', $options);
            }
        }
    );
}
