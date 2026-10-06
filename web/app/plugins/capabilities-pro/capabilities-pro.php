<?php
/**
 * Plugin Name: PublishPress Capabilities Pro
 * Plugin URI: https://publishpress.com/
 * Description: PublishPress Capabilities is the access control plugin for WordPress. You can manage all your WordPress user roles, from Administrators to Subscribers.
 * Version: 2.52.0
 * Author: PublishPress
 * Author URI: https://publishpress.com/
 * Text Domain: capabilities-pro
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 * License: GPLv3
 *
 * Copyright (c) 2024 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Capability Manager
 * Author: Jordi Canals
 * Copyright (c) 2009, 2010 Jordi Canals
 * ------------------------------------------------------------------------------
 *
 * @package 	capabilities-pro
 * @author		PublishPress
 * @copyright   Copyright (C) 2024 PublishPress. All rights reserved.
 * @license		GNU General Public License version 3
 * @link		https://publishpress.com/
 */
global $wp_version;

$min_php_version = '7.2.5';
$min_wp_version  = '5.5';

$invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
$invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');

if ($invalid_php_version || $invalid_wp_version) {
	return;
}

if (!function_exists('pp_capabilities_pro_is_free_plugin_active')) {
	function pp_capabilities_pro_is_free_plugin_active()
	{
		$free_plugin_file = 'capability-manager-enhanced/capsman-enhanced.php';

		if (!function_exists('is_plugin_active') || !function_exists('is_plugin_active_for_network')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active($free_plugin_file)
			|| (is_multisite() && is_plugin_active_for_network($free_plugin_file));
	}
}

if (!defined('PP_CAPABILITIES_PRO_LOADED')) {
	define('PP_CAPABILITIES_PRO_LOADED', true);

	if (!defined('PP_CAPABILITIES_PRO_LIB_VENDOR_PATH')) {
		define('PP_CAPABILITIES_PRO_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
	}

	$instanceProtectionIncPath = PP_CAPABILITIES_PRO_LIB_VENDOR_PATH . '/publishpress/instance-protection/include.php';
	if (is_file($instanceProtectionIncPath) && is_readable($instanceProtectionIncPath)) {
		require_once $instanceProtectionIncPath;
	}

	if (class_exists('PublishPressInstanceProtection\\Config')) {
		$pluginCheckerConfig = new PublishPressInstanceProtection\Config();
		$pluginCheckerConfig->pluginSlug = 'capabilities-pro';
		$pluginCheckerConfig->pluginName = 'PublishPress Capabilities Pro';
		$pluginCheckerConfig->isProPlugin = true;
		$pluginCheckerConfig->freePluginName = 'PublishPress Capabilities';

		$pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
	}

	$autoloadFilePath = PP_CAPABILITIES_PRO_LIB_VENDOR_PATH . '/autoload.php';
	if (
		!class_exists('ComposerAutoloaderInitPublishPressCapabilitiesPro')
		&& is_file($autoloadFilePath)
		&& is_readable($autoloadFilePath)
	) {
		require_once $autoloadFilePath;
	}


	$includeFilebRelativePath = '/publishpress/publishpress-instance-protection/include.php';
	if (file_exists(__DIR__ . '/vendor' . $includeFilebRelativePath)) {
		require_once __DIR__ . '/vendor' . $includeFilebRelativePath;
	}

	// Define constants for Pro only
	if (!defined('PUBLISHPRESS_CAPS_PRO_FILE')) {
		define('PUBLISHPRESS_CAPS_PRO_FILE', __FILE__);
	}

	if (!defined('PUBLISHPRESS_CAPS_PRO_ABSPATH')) {
		define('PUBLISHPRESS_CAPS_PRO_ABSPATH', __DIR__);
	}

	if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
		define('PUBLISHPRESS_CAPS_PRO_VERSION', '2.52.0');
	}

	if (!defined('PUBLISHPRESS_CAPS_EDD_ITEM_ID')) {
		define('PUBLISHPRESS_CAPS_EDD_ITEM_ID', 44811);
	}

	$free_plugin_is_active = pp_capabilities_pro_is_free_plugin_active();

	// Initialize vendored free plugin only if standalone free plugin is not active.
	if (!$free_plugin_is_active) {
		require_once PP_CAPABILITIES_PRO_LIB_VENDOR_PATH . '/publishpress/publishpress-capabilities/capsman-enhanced.php';
	}

	add_action('plugins_loaded', function () {

		if (!defined('CAPSMAN_VERSION')) {
			define('CAPSMAN_VERSION', '2.52.0');
			define('PUBLISHPRESS_CAPS_VERSION', CAPSMAN_VERSION);
		}

		require_once(dirname(__FILE__) . '/includes-pro/load.php');

		if (is_admin()) {
			require_once(dirname(__FILE__) . '/includes-pro/functions-admin.php');
			require_once(dirname(__FILE__) . '/includes-pro/admin-load.php');
			new \PublishPress\Capabilities\AdminFiltersPro();
		}

		do_action('publishpress_capabilities_loaded');
	}, -9);

}


register_activation_hook(
    __FILE__,
    function () {
        update_option('pp_capabilities_activated', true);
    }
);