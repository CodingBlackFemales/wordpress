<?php
/**
 * Plugin Name: PressPermit Compatibility Pack
 * Plugin URI:  https://publishpress.com/press-permit
 * Description: Supports bbPress, BuddyPress, WPML, Co-Authors+, Relevanssi, SearchWP, Custom Post Type UI, various others. For multisite, provides network-wide permission groups.
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 * Version:     2.7
 * Text Domain: ppp
 * Domain Path: /languages/
 * Min WP Version: 4.7
 */

/*
Copyright © 2024 PublishPress.

This file is part of PressPermit Compatibility Pack.

PressPermit Compatibility Pack is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PressPermit Compatibility Pack is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!defined('PRESSPERMIT_COMPAT_FILE')) {
    define('PRESSPERMIT_COMPAT_FILE', __FILE__);
    define('PRESSPERMIT_COMPAT_ABSPATH', __DIR__);
    define('PRESSPERMIT_COMPAT_CLASSPATH', __DIR__ . '/classes/Permissions/Compat');

    if (!defined('PRESSPERMIT_VERSION')) {
        return;
    }

    $ext_version = PRESSPERMIT_VERSION;

    $module_title = 'Compatibility Pack'; // @todo: review removing this, as it is separately set with translation downstream

    if (presspermit()->registerModule(
        'compatibility', $module_title,  plugin_basename(__FILE__), $ext_version, ['min_pp_version' => '2.7-beta']
    )) {
        define('PRESSPERMIT_COMPAT_VERSION', $ext_version);

        class_alias('\PressShack\LibArray', '\PublishPress\Permissions\Compat\Arr');

        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\PWP\UI');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\BBPress\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\BuddyPress\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\BuddyPress\PermissionGroups\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\Relevanssi\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Compat\WPML\PWP');

        require_once(__DIR__ . '/classes/Permissions/Compat.php');

        require_once(__DIR__ . '/classes/Permissions/NetworkGroupCleanupBatch.php');
        \PublishPress\Permissions\NetworkGroupCleanupBatch::init();

        require_once(__DIR__ . '/classes/Permissions/CompatHooks.php');
        new \PublishPress\Permissions\CompatHooks();

        if (is_admin()) {
            require_once(__DIR__ . '/classes/Permissions/CompatHooksAdmin.php');
            new \PublishPress\Permissions\CompatHooksAdmin();
        } else {
            require_once(__DIR__ . '/classes/Permissions/CompatHooksFront.php');
            new \PublishPress\Permissions\CompatHooksFront();
        }
    }

    add_action(
        'plugins_loaded', 
        function()
        {
            if (!defined('REVISIONARY_VERSION') && defined('RVY_VERSION')) {
                define('REVISIONARY_VERSION', RVY_VERSION);
            }
        }, 
        20
    );
} else {
    add_action(
        'init', 
        function()
        {
            do_action('presspermit_duplicate_module', 'pp-compat', dirname(plugin_basename(__FILE__)));
        }
    );
    return;
}
