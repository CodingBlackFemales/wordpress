<?php
/**
 * Plugin Name: PublishPress Planner Pro
 * Plugin URI: https://publishpress.com/
 * Description: PublishPress Planner helps you plan and publish content inside WordPress. Features include a content calendar, kanban board, and notifications.
 * Version: 4.8.1
 * Author: PublishPress
 * Author URI: https://publishpress.com
 * Text Domain: publishpress-pro
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 *
 * Copyright (c) 2024 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
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
 * @package     PublishPress
 * @author      PublishPress
 * @copyright   Copyright (c) 2024 PublishPress. All rights reserved.
 * @link        https://publishpress.com/
 * 
 */

namespace PublishPressPro;

use WPPF2\Plugin\ServicesAbstract;
use PublishPressInstanceProtection;

global $wp_version;

$min_php_version = '7.2.5';
$min_wp_version  = '5.5';

$invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
$invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');

if ($invalid_php_version || $invalid_wp_version) {
    return;
}

if (! defined('PPPRO_LIB_VENDOR_PATH')) {
    define('PPPRO_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
}

$instanceProtectionIncPath = PPPRO_LIB_VENDOR_PATH . '/publishpress/instance-protection/include.php';
if (is_file($instanceProtectionIncPath) && is_readable($instanceProtectionIncPath)) {
    require_once $instanceProtectionIncPath;
}

if (class_exists('PublishPressInstanceProtection\\Config')) {
    $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
    $pluginCheckerConfig->pluginSlug     = 'publishpress-pro';
    $pluginCheckerConfig->pluginName     = 'PublishPress Planner Pro';
    $pluginCheckerConfig->freePluginName = 'PublishPress Planner';
    $pluginCheckerConfig->isProPlugin    = true;

    $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

$autoloadFilePath = PPPRO_LIB_VENDOR_PATH . '/autoload.php';
if (! class_exists('ComposerAutoloaderInitPublishPressPlannerPro')
    && is_file($autoloadFilePath)
    && is_readable($autoloadFilePath)
) {
    require_once $autoloadFilePath;
}

if (! defined('PUBLISHPRESS_PRO_ACTION_LOAD_BASE_PLUGIN')) {
    define('PUBLISHPRESS_PRO_ACTION_LOAD_BASE_PLUGIN', 'publishpress_pro_load_base_plugin');
}

if (! defined('PUBLISHPRESS_PRO_ACTION_HALT')) {
    define('PUBLISHPRESS_PRO_ACTION_HALT', 'publishpress_pro_halt');
}


if (! defined('PUBLISHPRESS_PRO_INTERNAL_VENDORPATH')) {
    /**
     * @deprecated 3.12.0 Use PPPRO_LIB_VENDOR_PATH instead.
     */
    define('PUBLISHPRESS_PRO_INTERNAL_VENDORPATH', PPPRO_LIB_VENDOR_PATH);
}

if (!function_exists('checkupRanSuccessfully')) {
    function checkupRanSuccessfully()
    {
        return require 'checkup.php';
    }
}

if (checkupRanSuccessfully() && ! defined('PUBLISHPRESS_PRO_LOADED')) {

    define('PUBLISHPRESS_PRO_VERSION', '4.8.1');

    define('PUBLISHPRESS_PRO_FILE', __FILE__);

    define('PUBLISHPRESS_PRO_DIR_PATH', plugin_dir_path(__FILE__));

    define('PUBLISHPRESS_PRO_PLUGIN_URL', plugins_url('/', __FILE__));

    define('PUBLISHPRESS_PRO_ITEM_ID', 49742);

    define('PUBLISHPRESS_SKIP_VERSION_NOTICES', true);

    // Initialize the free plugin.
    if (defined('PUBLISHPRESS_FREE_PLUGIN_PATH')) {
        require_once PUBLISHPRESS_FREE_PLUGIN_PATH . '/publishpress.php';
        do_action(PUBLISHPRESS_PRO_ACTION_LOAD_BASE_PLUGIN);
    } else {
        require_once PPPRO_LIB_VENDOR_PATH . '/publishpress/publishpress-planner/publishpress.php';
    }

    add_action('plugins_loaded', function () {
        
        // Initialize the framework
        require_once PUBLISHPRESS_PRO_DIR_PATH . '/lib/wppf2/includes.php';

        // Initialize the Slack module - migrated from the Slack plugin.
        require_once PUBLISHPRESS_PRO_DIR_PATH . '/modules/slack/includes.php';

        // Initialize the Reminders module - migrated from the Reminders plugin.
        require_once PUBLISHPRESS_PRO_DIR_PATH . '/modules/reminders/includes.php';

        $container = Factory::getContainer();

        $pluginInitializer = $container->get(ServicesAbstract::PLUGIN_INITIALIZER);
        $pluginInitializer->init();

        do_action('publishpress_planner_pro_loaded');
    }, -9);

    define('PUBLISHPRESS_PRO_LOADED', 1);
}
