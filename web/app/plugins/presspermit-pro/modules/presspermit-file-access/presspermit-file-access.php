<?php
/**
 * Plugin Name: PressPermit File Acces
 * Plugin URI:  https://publishpress.com/press-permit
 * Description: Filters direct file access, based on user's access to post(s) which the file is attached to.
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 * Version:     2.7
 * Text Domain: ppff
 * Domain Path: /languages/
 * Min WP Version: 4.7
 */

/*
Copyright © 2024 PublishPress.

This file is part of PressPermit File Access.

PressPermit File Access is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PressPermit File Access is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (defined('PRESSPERMIT_FILEACCESS_FILE')) {
    add_action(
        'init', 
        function()
        {
            do_action('presspermit_duplicate_module', 'file-access', PRESSPERMIT_FILEACCESS_FILE);
        }
    );
    return;
} else {
    define('PRESSPERMIT_FILEACCESS_FILE', dirname(plugin_basename(__FILE__)));
    define('PRESSPERMIT_FILEACCESS_ABSPATH', __DIR__);
    define('PRESSPERMIT_FILEACCESS_CLASSPATH', __DIR__ . '/classes/Permissions/FileAccess');

    if (!defined('PRESSPERMIT_VERSION')) {
        return;
    }

    $ext_version = PRESSPERMIT_VERSION;

    $module_title = 'File Access'; // @todo: review removing this, as it is separately set with translation downstream

    if (presspermit()->registerModule(
        'file-access', $module_title, dirname(plugin_basename(__FILE__)), $ext_version, ['min_pp_version' => '2.7-beta']
    )) {
        define('PRESSPERMIT_FILE_ACCESS_VERSION', $ext_version);

        class_alias('\PressShack\LibArray', '\PublishPress\Permissions\FileAccess\Arr');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\FileAccess\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\FileAccess\UI\PWP');

        require_once(__DIR__ . '/classes/Permissions/FileAccess.php');

        class_alias('\PublishPress\Permissions\FileAccess', '\PublishPress\Permissions\FileAccess\FileAccess');

        if ( is_admin() ) {
            class_alias('\PublishPress\Permissions\FileAccess', '\PublishPress\Permissions\FileAccess\UI\FileAccess');
        }

        require_once(__DIR__ . '/classes/Permissions/FileAccessHooks.php');
        new \PublishPress\Permissions\FileAccessHooks();

        require_once(__DIR__ . '/classes/Permissions/FileAccess/RuleFlushBatch.php');
        \PublishPress\Permissions\FileAccess\RuleFlushBatch::init();

        require_once(__DIR__ . '/classes/Permissions/FileAccess/AttachmentScanBatch.php');
        \PublishPress\Permissions\FileAccess\AttachmentScanBatch::init();

        if (is_admin()) {
            require_once(__DIR__ . '/classes/Permissions/FileAccessHooksAdmin.php');
            new \PublishPress\Permissions\FileAccessHooksAdmin();
        }
    }

    if (did_action('presspermit_activate') || get_option('presspermit_activation') || get_option('presspermit_file_access_deactivate')) {
        delete_option('presspermit_activation');
        delete_option('presspermit_file_access_deactivate');

        require_once(__DIR__ . '/classes/Permissions/FileAccess.php');
        \PublishPress\Permissions\FileAccess::flushAllFileRules();
    }
    
    add_action('presspermit_deactivate', function() {
        require_once(__DIR__ . '/classes/Permissions/FileAccess.php');
        \PublishPress\Permissions\FileAccess::clearAllFileRules();
        }
    );

    add_action('presspermit-file-access_deactivate', function() {
        require_once(__DIR__ . '/classes/Permissions/FileAccess.php');
        \PublishPress\Permissions\FileAccess::clearAllFileRules();
        }
    );
}
