<?php
/**
 * Plugin Name: PressPermit Membership
 * Plugin URI:  https://publishpress.com/press-permit
 * Description: Allows Permit Group membership to be date-limited (delayed and/or scheduled for expiration).
 * Author:      PublishPress
 * Author URI:  https://publishpress.com/
 * Version:     2.7
 * Text Domain: ppm
 * Domain Path: /languages/
 * Min WP Version: 4.7
 */

/*
Copyright 2024 PublishPress

This file is part of PressPermit Membership.

PressPermit Membership is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PressPermit Membership is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!defined('PRESSPERMIT_MEMBERSHIP_FILE')) {
    define('PRESSPERMIT_MEMBERSHIP_FILE', __FILE__);
    define('PRESSPERMIT_MEMBERSHIP_ABSPATH', __DIR__);
    define('PRESSPERMIT_MEMBERSHIP_CLASSPATH', __DIR__ . '/classes/Permissions/Membership');

    if (!defined('PRESSPERMIT_VERSION')) {
        return;
    }

    $ext_version = PRESSPERMIT_VERSION;

    $module_title = 'Membership'; // @todo: review removing this, as it is separately set with translation downstream

    if (presspermit()->registerModule(
        'membership', $module_title, dirname(plugin_basename(__FILE__)), $ext_version, ['min_pp_version' => '2.7-beta']
    )) {
        define('PRESSPERMIT_MEMBERSHIP_VERSION', $ext_version);

        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Membership\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Membership\DB\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Membership\UI\PWP');

        require_once(__DIR__ . '/classes/Permissions/MembershipHooks.php');
        new \PublishPress\Permissions\MembershipHooks();

        if (is_admin()) {
            require_once(__DIR__ . '/classes/Permissions/MembershipHooksAdmin.php');
            new \PublishPress\Permissions\MembershipHooksAdmin();
        }
    }
} else {
    add_action(
        'init',
        function()
        {
            do_action('presspermit_duplicate_module', 'membership', dirname(plugin_basename(__FILE__)));
        }
    );
    return;
}
