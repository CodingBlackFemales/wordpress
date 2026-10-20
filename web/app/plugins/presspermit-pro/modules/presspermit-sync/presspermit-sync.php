<?php
/*
Copyright © 2025 PublishPress.

This file is part of PublishPress Permissions Pro.

PublishPress Permissions Pro is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PublishPress Permissions Pro is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!defined('PRESSPERMIT_SYNC_FILE')) {
    define('PRESSPERMIT_SYNC_FILE', __FILE__);
    define('PRESSPERMIT_SYNC_ABSPATH', __DIR__);
    define('PRESSPERMIT_SYNC_CLASSPATH', __DIR__ . '/classes/Permissions/SyncPosts');

    if (!defined('PRESSPERMIT_VERSION')) {
        return;
    }

    $ext_version = PRESSPERMIT_VERSION;

    $module_title = 'Sync Posts'; // @todo: review removing this, as it is separately set with translation downstream

    if (presspermit()->registerModule(
        'sync', $module_title,  plugin_basename(__FILE__), $ext_version, 
        ['min_pp_version' => '2.7-beta']
    )) {
        define('PRESSPERMIT_SYNC_VERSION', $ext_version);

        class_alias('\PressShack\LibArray', '\PublishPress\Permissions\SyncPosts\Arr');

        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\SyncPosts\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\SyncPosts\UI\PWP');

        require_once(__DIR__ . '/classes/Permissions/SyncPosts.php');
        \PublishPress\Permissions\SyncPosts::instance();
        class_alias('\PublishPress\Permissions\SyncPosts', '\PublishPress\Permissions\SyncPosts\UI\SyncPosts');

        require_once(__DIR__ . '/classes/Permissions/SyncHooks.php');
        new \PublishPress\Permissions\SyncHooks();

        if (is_admin()) {
            require_once(__DIR__ . '/classes/Permissions/SyncHooksAdmin.php');
            new \PublishPress\Permissions\SyncHooksAdmin();
        }
    }
} else {
    add_action(
        'init', 
        function()
        {
            do_action('presspermit_duplicate_module', 'sync', dirname(plugin_basename(__FILE__)));
        }
    );
    return;
}
