<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities Pro
 *
 * Handle ajax requests related to the license key
 *
 */

if (!defined('PUBLISHPRESS_CAPS_PRO_ABSPATH')) {
    define('PUBLISHPRESS_CAPS_PRO_ABSPATH', dirname(__DIR__));
}
require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/pro-maint.php');

if (empty($_GET['publishpress_caps_ajax_settings'])) {
    exit;
}

$key = (isset($_GET['key'])) ? sanitize_key($_GET['key']) : '';
switch ($_GET['publishpress_caps_ajax_settings']) {
    case 'activate_key':
        check_admin_referer('wp_ajax_pp_activate_key');
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', '', ['response' => 403]);
        }
        if (
            is_multisite() && !is_super_admin() && (Maint::isNetworkActivated() || Maint::isMuPlugin())
        ) {
            return;
        }

        $request_vars = [
            'edd_action' => "activate_license",
            'item_id' => PUBLISHPRESS_CAPS_EDD_ITEM_ID,
            'license' => sanitize_key($key),
            'url' => site_url(''),
        ];

        $response = Maint::callHome('activate_license', $request_vars);

        $result = json_decode(wp_unslash($response));
        if (is_object($result) && ('valid' == $result->license)) {
            $setting = ['license_status' => $result->license, 'license_key' => sanitize_key($key), 'expire_date' => $result->expires];
            update_option('cme_edd_key', $setting);
        }

        echo $response;
        exit();

        break;

    case 'deactivate_key':
        check_admin_referer('wp_ajax_pp_deactivate_key');
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', '', ['response' => 403]);
        }
        if (
            is_multisite() && !is_super_admin() && (Maint::isNetworkActivated() || Maint::isMuPlugin())
        ) {
            return;
        }

        $support_key = get_option('cme_edd_key');
        $request_vars = [
            'edd_action' => "deactivate_license",
            'item_id' => PUBLISHPRESS_CAPS_EDD_ITEM_ID,
            'license' => $support_key['license_key'],
            'url' => site_url(''),
        ];

        $response = Maint::callHome('deactivate_license', $request_vars);

        $result = json_decode(wp_unslash($response));
        if (is_object($result) && $result->license != 'valid') {
            delete_option('cme_edd_key');
        }

        echo $response;
        exit();

        break;
}
