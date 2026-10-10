<?php
/**
 * Plugin Name: PublishPress Revisions Pro
 * Plugin URI: https://publishpress.com/revisionary/
 * Description: Maintain published content with teamwork and precision using the Revisions model to submit, approve and schedule changes.
 * Author: PublishPress
 * Author URI: https://publishpress.com
 * Version: 4.1.0
 * Text Domain: revisionary
 * Domain Path: /languages/
 * Min WP Version: 5.5
 * Requires PHP: 7.2.5
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
 * @package     PublishPress\Revisions
 * @author      PublishPress
 * @copyright   Copyright (C) 2026 PublishPress. All rights reserved.
 *
 **/

// Temporary usage within this module only; avoids multiple instances of version string
global $pp_revisions_version;

$pp_revisions_version = '4.1.0';

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wp_version;

$min_wp_version = '5.5';
$min_php_version = '7.2.5';

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
                    'PublishPress Revisions Pro requires PHP version %s or higher.',
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
                    'PublishPress Revisions Pro requires WordPress version %s or higher.',
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

if (isset($_SERVER['SCRIPT_NAME']) && (strpos(esc_url_raw(wp_unslash($_SERVER['SCRIPT_NAME'])), 'p-admin/index-extra.php') || strpos(esc_url_raw(wp_unslash($_SERVER['SCRIPT_NAME'])), 'p-admin/update.php'))) {
	return;
}

if (! defined('REVISIONS_PRO_INTERNAL_VENDORPATH')) {
    define('REVISIONS_PRO_INTERNAL_VENDORPATH', __DIR__ . '/lib/vendor');
}

$includeFileRelativePath = REVISIONS_PRO_INTERNAL_VENDORPATH . '/publishpress/publishpress-instance-protection/include.php';
if (file_exists($includeFileRelativePath)) {
    require_once $includeFileRelativePath;
}

// Load bundled-translations library
$bundledTranslationsPath = '/publishpress/bundled-translations/core/include.php';
if (file_exists(REVISIONS_PRO_INTERNAL_VENDORPATH . $bundledTranslationsPath)) {
	require_once REVISIONS_PRO_INTERNAL_VENDORPATH . $bundledTranslationsPath;
}

// Initialize bundled translations
add_action('plugins_loaded', function() {
	if (class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
		$bundledTranslations = new PublishPress\BundledTranslations\BundledTranslations(
			'revisionary',
			REVISIONS_PRO_INTERNAL_VENDORPATH . '/publishpress/publishpress-revisions/languages',
			__FILE__
		);
		$bundledTranslations->init();
	}
}, 10);

if (class_exists('PublishPressInstanceProtection\\Config')) {
	$pluginCheckerConfig = new PublishPressInstanceProtection\Config();
	$pluginCheckerConfig->pluginSlug = 'revisionary-pro';
	$pluginCheckerConfig->pluginName = 'PublishPress Revisions Pro';
	$pluginCheckerConfig->isProPlugin = true;
	$pluginCheckerConfig->freePluginName = 'PublishPress Revisions';

	$pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

if (!defined('REVISIONARY_PRO_FILE')) {
	define('REVISIONARY_PRO_FILE', __FILE__);
	define('REVISIONARY_PRO_ABSPATH', __DIR__);

	if (! class_exists('ComposerAutoloaderInitRevisionsPro')
		&& file_exists(REVISIONS_PRO_INTERNAL_VENDORPATH . '/autoload.php')
	) {
		require_once REVISIONS_PRO_INTERNAL_VENDORPATH . '/autoload.php';
	}

	// negative priority to precede any default WP action handlers
    add_action(
        'plugins_loaded',
        function()
        {
            if (defined('PUBLISHPRESS_REVISIONS_PRO_VERSION')) {
                return;
            }

			global $pp_revisions_version;

			define('PUBLISHPRESS_REVISIONS_PRO_VERSION', '4.1.0');
			define('REVISIONARY_EDD_ITEM_ID', 40280);

			if (!function_exists('rvy_init')) {
				require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-revisions/rvy_init-functions.php');
			}

			require_once(__DIR__ . '/includes-pro/compat/beaver-builder-access.php');
			require_once( dirname(__FILE__).'/includes-pro/pro-load.php' );
			RevisionaryPro::instance();

			global $revisionary_compat;
			require_once( dirname(__FILE__).'/includes-pro/compat.php' );
			$revisionary_compat = new RevisionaryCompat();

			if (is_admin()) {
            	require_once(__DIR__ . '/includes-pro/admin-load.php');
            	new \RevisionaryProAdmin();
			}

			require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-revisions/revisionary.php');
        }
        , -10
    );

	add_action(
		'pp_revisions_admin_init',
		function() {
			load_plugin_textdomain('revisionary-pro', false, dirname(plugin_basename(REVISIONARY_PRO_FILE)) . '/languages');	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
		}
	);

	if (!function_exists('rvy_archive_post_type_rest_controller')) {
		global $rvy_rest_buffer_controller;
		$rest_buffer_controller = [];
	
		// WP Rest Cache plugin compat
		add_filter('register_post_type_args', 'rvy_archive_post_type_rest_controller', 9, 2);
		add_filter('register_post_type_args', 'rvy_restore_post_type_rest_controller_args', 11, 2);
	
		function rvy_archive_post_type_rest_controller($args, $post_type) {
			global $rvy_rest_buffer_controller;
	
			$rvy_rest_buffer_controller[$post_type] = isset( $args['rest_controller_class'] ) ? $args['rest_controller_class'] : false;
			return $args;
		}
	
		function rvy_restore_post_type_rest_controller_args($args, $post_type) {
			global $rvy_rest_buffer_controller;
			
			if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD']) 
			&& strpos(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), 'wp-json/wp/') && ($_SERVER['REQUEST_METHOD'] === 'POST')
			) {
				if (!empty($args['rest_controller_class']) && isset($rvy_rest_buffer_controller[$post_type])) {
					if (false !== strpos($args['rest_controller_class'], 'WP_Rest_Cache_Plugin')) {
						$args['rest_controller_class'] = $rvy_rest_buffer_controller[$post_type];
					}
				}
			}
	
			return $args;
		}
	}

	// register these functions before any early exits so normal activation/deactivation can still run with RS_DEBUG
	register_activation_hook(__FILE__, function() 
		{
			global $pp_revisions_version;

			if (!function_exists('revisionary')) {
				require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-revisions/functions.php');

				if (function_exists('pp_revisions_plugin_updated')) {
					pp_revisions_plugin_updated($pp_revisions_version, ['is_pro' => true]);
				}

				if (function_exists('pp_revisions_plugin_activation')) {
					pp_revisions_plugin_activation();
					do_action('revisionary_pro_activate');
					update_option('revisionary_pro_activate', true);
					if (class_exists('RevisionaryBeaverBuilderAccess')) {
						RevisionaryBeaverBuilderAccess::on_activation();
					}

					if (!get_option('revisionary_activation_done')) {
						update_option('revisionary_activation_done', true);
						update_option('revisionary_activation', true);
					}
				}
			}
		}
	);

	register_deactivation_hook(__FILE__, function()
		{
			if (class_exists('RevisionaryBeaverBuilderAccess')) {
				RevisionaryBeaverBuilderAccess::on_deactivation();
			}

			if (!function_exists('rvy_init')) {
				require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-revisions/rvy_init.php');
			}

			// If Revisions free was needlessly active already, don't hide pending / sheduled revisions
			if (!rvy_is_plugin_active('revisionary/revisionary.php')) {
				if (!function_exists('revisionary')) {
					require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-revisions/functions.php');
				}

				if (function_exists('pp_revisions_plugin_deactivation')) {
					pp_revisions_plugin_deactivation();
				}
			}
		}
	);
}
