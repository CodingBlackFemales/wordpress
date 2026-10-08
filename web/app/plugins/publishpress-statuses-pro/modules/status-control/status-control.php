<?php
/*
Copyright 2026 PublishPress

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

if (!defined('PRESSPERMIT_STATUSES_FILE')) {
    define('PRESSPERMIT_STATUSES_FILE', __FILE__);
    define('PUBLISHPRESS_STATUS_CONTROL_ABSPATH', __DIR__);

    if (!defined('REVISIONARY_VERSION') && defined('RVY_VERSION')) {
        define('REVISIONARY_VERSION', RVY_VERSION);
    }

    $module_title = 'Status Control'; // @todo: review removing this, as it is separately set with translation downstream

    add_action(
        'publishpress_statuses_init',
        function() {
            $ext_version = PUBLISHPRESS_STATUSES_VERSION;
            define('PRESSPERMIT_STATUSES_VERSION', $ext_version);   // back compat
        }
    );

    define('PRESSPERMIT_STATUSES_DB_VERSION', '1.0');

    // Status capabilities library: loader stub to select latest version of library (@todo: vendor library?)
    require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/modules/status-capabilities/status-capabilities.php');

    add_filter('publishpress_status_capabilities_class', 
        function($class_name) { 
            require_once(__DIR__ . '/classes/StatusControl/Attributes.php');
            return '\PublishPress\Statuses\StatusControl\Attributes';
        }
    );

    add_action('plugins_loaded', function() {
        define('PP_STATUS_CONTROL_CLASSPATH', __DIR__ . '/classes/StatusControl');

        if (!class_exists('PublishPress\StatusCapabilities')) {
            $status_caps_package = apply_filters('publishpress_status_capabilities_library', false);

            if (is_object($status_caps_package) && isset($status_caps_package->path) && file_exists($status_caps_package->path)) {
                require_once($status_caps_package->path);

                if (class_exists('PublishPress\StatusCapabilities')) {
                    \PublishPress\StatusCapabilities::instance();
                }
            }
        }

        require_once(__DIR__ . '/classes/StatusControl.php');
        \PublishPress\Statuses\StatusControl::defineClassAliases();

        require_once(__DIR__ . '/classes/StatusControlHooks.php');
        new \PublishPress\Statuses\StatusControlHooks();

        if (is_admin()) {
            require_once(__DIR__ . '/classes/StatusControlHooksAdmin.php');
            new \PublishPress\Statuses\StatusControlHooksAdmin();
        }
    });

} else {
    add_action(
        'init', 
        function()
        {
            do_action('presspermit_duplicate_module', 'pp-custom-post-status', dirname(plugin_basename(__FILE__)));
        }
    );
    return;
}

function publishpress_status_control() {
    require_once(__DIR__ . '/classes/StatusControl.php');
    return \PublishPress\Statuses\StatusControl::instance();
}
