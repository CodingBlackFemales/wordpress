<?php
/**
 * Plugin Name: PublishPress Statuses Pro
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
 * @package     PublishPress Statuses Pro
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

 if (! defined('PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH')) {
    define('PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH', __DIR__ . '/lib/vendor');
}

$includeFileRelativePath = PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . '/publishpress/instance-protection/include.php';
if (file_exists($includeFileRelativePath)) {
    require_once $includeFileRelativePath;
}

if (class_exists('PublishPressInstanceProtection\\Config')) {
	$pluginCheckerConfig = new PublishPressInstanceProtection\Config();
	$pluginCheckerConfig->pluginSlug = 'publishpress-statuses-pro';
	$pluginCheckerConfig->pluginName = 'PublishPress Statuses Pro';
	$pluginCheckerConfig->isProPlugin = true;
	$pluginCheckerConfig->freePluginName = 'PublishPress Statuses';

	$pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

// Load bundled-translations library
$bundledTranslationsPath = '/publishpress/bundled-translations/core/include.php';
if (file_exists(PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . $bundledTranslationsPath)) {
    require_once PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . $bundledTranslationsPath;
}

// Initialize bundled translations
add_action('plugins_loaded', function() {
    if (class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
        $bundledTranslations = new PublishPress\BundledTranslations\BundledTranslations(
            'publishpress-statuses',
            PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . '/publishpress/publishpress-statuses/languages',
            __FILE__
        );
        $bundledTranslations->init();
    }
}, 10);

if (!defined('PUBLISHPRESS_STATUSES_PRO_FILE')) {
    define('PUBLISHPRESS_STATUSES_PRO_FILE', __FILE__);
    define('PUBLISHPRESS_STATUSES_PRO_ABSPATH', __DIR__);

    require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-statuses/lib/PP_Statuses_Functions.php');

    if (! class_exists('ComposerAutoloaderInitPublishPressStatusesPro')
        && file_exists(PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . '/autoload.php')
    ) {
        require_once PUBLISHPRESS_STATUSES_PRO_INTERNAL_VENDORPATH . '/autoload.php';
    }

    // negative priority to precede any default WP action handlers
    add_action('plugins_loaded', function() {
        if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
            return;
        }

        if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '4.0-beta4', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice error">
                    <p><?php printf(
                        // translators: %s is a version number
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Planner to version %s or higher.', 'publishpress-statuses-pro'),
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
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Permissions Pro to version %s or higher.', 'publishpress-statuses-pro'),
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
                        esc_html__('To use PublishPress Statuses, please upgrade PublishPress Capabilities Pro to version %s or higher.', 'publishpress-statuses-pro'),
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
            define('PUBLISHPRESS_STATUSES_PRO_VERSION', '1.3.6');
            define('PUBLISHPRESS_STATUSES_EDD_ITEM_ID', 336700);

            define('PUBLISHPRESS_STATUSES_PRO_URL', trailingslashit(plugins_url('', __FILE__)));
            define('PUBLISHPRESS_STATUSES_PRO_DIR', __DIR__);

            require_once(__DIR__ . '/includes-pro/PublishPress_Statuses_Pro.php');
            \PublishPress_Statuses_Pro::instance();

            add_action(
                'init',
                function() {
                    @load_plugin_textdomain('publishpress-statuses-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');     // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
                },
                5
            );

            add_action('publishpress_statuses_init', function() {
                global $wp_version;

                if (defined('PUBLISHPRESS_REVISIONS_VERSION') 
                && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.6-beta', '>=')
                && function_exists('rvy_in_revision_workflow')
                && (
                    (version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION'))        // Require WP 6.6 due to splitting of prior custom status block JS to legacy file
                    || (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>='))
                    )
                ) {
                    require_once(__DIR__ . '/includes-pro/Revisions.php');
                    new \PublishPress_Statuses\Revisions();
                } {
                    add_filter('pp_dashboard_post_status_widget_statuses', 
                        function($statuses) {
                            foreach ($statuses as $k => $status) {
                                if (in_array($status->name,  ['draft-revision', 'pending-revision', 'future-revision', 'revision-deferred', 'revision-needs-work', 'revision-rejected'])) {
                                    unset($statuses[$k]);
                                }
                            }

                            return $statuses;
                        }
                    );
                }
            });

            require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-statuses/publishpress-statuses.php');
        }
    }, -5);
    
    register_activation_hook(
        __FILE__, 
        function() {
            if (!get_option('publishpress_statuses_activation_done')) {
                update_option('publishpress_statuses_activation_done', true);
                update_option('publishpress_statuses_activate', true);
                update_option('publishpress_statuses_pro_activate', true);
            }
        }
    );

    register_deactivation_hook(
        __FILE__,
        function()
        {
            global $wpdb;
            
            if (!get_option('rvy_permissions_compat_mode', false)) {
                // Prevents pending / scheduled revisions from being listed as regular drafts / pending posts after plugin is deactivated
                $revision_statuses = ['draft-revision', 'pending-revision', 'future-revision', 'revision-deferred', 'revision-needs-work', 'revision-rejected'];
                
                if (!taxonomy_exists('pp_revision_status')) {
                    register_taxonomy(
                        'pp_revision_status',
                        'post',
                        [
                            'hierarchical'          => false,
                            'query_var'             => false,
                            'rewrite'               => false,
                            'show_ui'               => false,
                        ]
                    );
                }
            
                $stored_statuses = get_terms(['taxonomy' => 'pp_revision_status', 'hide_empty' => false, 'return' => 'name']);
            
                foreach ($stored_statuses as $status) {
                    if (is_object($status) && property_exists($status, 'slug') && !in_array($status->slug, $revision_statuses)) {
                        $revision_statuses[] = $status->slug;
                    }
                }

                // Revisions with native statuses will remain accessible
                $revision_statuses = array_diff($revision_statuses, ['draft-revision', 'pending-revision', 'future-revision']);
            
                $revision_status_csv = implode("','", array_map('sanitize_key', $revision_statuses));
            
                $wpdb->query("UPDATE $wpdb->posts SET post_status = post_mime_type WHERE post_mime_type IN ('$revision_status_csv')");                          // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }

            // @todo: Avoid redundant code by calling free plugin deactivation handler

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
