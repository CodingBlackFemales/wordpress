<?php

/**
 * Plugin Name: PublishPress Permissions Pro
 * Plugin URI:  https://publishpress.com/permissions
 * Description: PublishPress Permissions allows you to enable or deny access to posts, pages, categories, tags and more.
 * Version: 4.9.1
 * Author: PublishPress
 * Author URI:  https://publishpress.com/
 * Text Domain: presspermit-pro
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 *
 * Copyright (c) 2025 PublishPress
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
 * @package     PublishPress Permissions Pro
 * @author      PublishPress
 * @copyright   Copyright (C) 2025 PublishPress. All rights reserved.
 * @license     GNU General Public License version 3
 * @link        https://publishpress.com/
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
                    'PublishPress Permissions Pro requires PHP version %s or higher.',
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
                    'PublishPress Permissions Pro requires WordPress version %s or higher.',
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

if (! defined('PRESSPERMIT_PRO_INTERNAL_VENDORPATH')) {
    define('PRESSPERMIT_PRO_INTERNAL_VENDORPATH', __DIR__ . '/lib/vendor');
}

$includeFileRelativePath = PRESSPERMIT_PRO_INTERNAL_VENDORPATH . '/publishpress/instance-protection/include.php';
if (file_exists($includeFileRelativePath)) {
    require_once $includeFileRelativePath;
}

if (class_exists('PublishPressInstanceProtection\\Config')) {
    $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
    $pluginCheckerConfig->pluginSlug = 'presspermit-pro';
    $pluginCheckerConfig->pluginName = 'PublishPress Permissions Pro';
    $pluginCheckerConfig->isProPlugin = true;
    $pluginCheckerConfig->freePluginName = 'PublishPress Permissions';

    $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

if (!defined('PRESSPERMIT_PRO_FILE')) {
    define('PRESSPERMIT_PRO_FILE', __FILE__);
    define('PRESSPERMIT_PRO_ABSPATH', __DIR__);

    if (
        ! class_exists('ComposerAutoloaderInitPressPermitPro')
        && file_exists(PRESSPERMIT_PRO_INTERNAL_VENDORPATH . '/autoload.php')
    ) {
        require_once PRESSPERMIT_PRO_INTERNAL_VENDORPATH . '/autoload.php';
    }

    // negative priority to precede any default WP action handlers
    add_action(
        'plugins_loaded',
        function () {
            if (defined('PRESSPERMIT_PRO_VERSION')) {
                return;
            }

            // The Events Calendar Pro: Avoid conflict with recurring event insert / update
            if (
                defined('DOING_AJAX') && DOING_AJAX && !empty($_POST['action'])  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                && (defined('EVENTS_CALENDAR_PRO_FILE') || class_exists('Tribe__Events__Pro__Main'))
                && (!empty($_POST['action'] && 'gutenberg_events_pro_recurrence_queue' == $_POST['action']))  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                return;
            }

            if (
                defined('PMXE_VERSION') && !empty($_SERVER['SCRIPT_NAME'])
                && (false !== strpos(esc_url_raw($_SERVER['SCRIPT_NAME']), 'wp-load.php'))
                && !empty($_REQUEST['export_key'])  // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            ) {
                return;
            }

            global $pagenow;

            if (is_admin() && isset($pagenow) && ('customize.php' == $pagenow)) {
                return;
            }

            define('PRESSPERMIT_PRO_VERSION', '4.9.1');

            $edd_id = get_option('presspermit_edd_id', 21050);
            define('PRESSPERMIT_EDD_ITEM_ID', $edd_id);

            add_action(
                'init',
                function () {
                    @load_plugin_textdomain('presspermit-pro', false, dirname(plugin_basename(PRESSPERMIT_PRO_FILE)) . '/languages');
                },
                5
            );

            require_once(__DIR__ . '/includes-pro/admin-load.php');
            new \PublishPress\Permissions\AdminLoadPro();

            require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-permissions/press-permit-core.php');
        },
        -10
    );

    register_activation_hook(
        __FILE__,
        function () {
            require_once(__DIR__ . '/lib/vendor/publishpress/publishpress-permissions/activation.php');
        }
    );

    register_deactivation_hook(
        __FILE__,
        function () {
            do_action('presspermit_deactivate');
        }
    );
}
