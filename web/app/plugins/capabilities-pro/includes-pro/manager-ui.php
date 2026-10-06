<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities Pro
 *
 * Pro adjustments to the Capabilities screen UI
 *
 */

class ManagerUI {
    function __construct() {
        $this->loadScripts();

        add_filter('cme_plugin_capabilities', [$this, 'fltPluginCapabilities']);
    }

    public function loadScripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        wp_enqueue_script('publishpress-caps-pro-settings', plugins_url('', PUBLISHPRESS_CAPS_PRO_FILE) . "/includes-pro/settings-pro{$suffix}.js", ['jquery', 'jquery-form'], PUBLISHPRESS_CAPS_VERSION, true);
    }

    public function fltPluginCapabilities($plugin_caps) {
        if (class_exists('BuddyPress')) {
            $plugin_caps['BuddyPress'] = apply_filters('cme_buddypress_capabilities',
                ['bp_moderate', 'bp_create_groups']
            );
        }

        ksort($plugin_caps);

        return $plugin_caps;
    }
}
