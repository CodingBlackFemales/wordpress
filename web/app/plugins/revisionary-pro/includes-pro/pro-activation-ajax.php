<?php
namespace PublishPress\Revisions;

require_once(REVISIONARY_PRO_ABSPATH . '/includes-pro/pro-maint.php');

if (isset($_GET['rvy_ajax_settings'])) {
switch ($_GET['rvy_ajax_settings']) {
    case 'activate_key':
        check_admin_referer('wp_ajax_pp_activate_key');
        if (
            is_multisite() && !is_super_admin() && (PWP::isNetworkActivated() || PWP::isMuPlugin())
        ) {
            return;
        }

        if (empty($_GET['key'])) {
            return;
        }

        $request_vars = [
            'edd_action' => "activate_license",
            'item_id' => REVISIONARY_EDD_ITEM_ID,
            'license' => sanitize_key($_GET['key']),
            'url' => site_url(''),
        ];

        $response = RevisionaryMaint::callHome('activate_license', $request_vars);

        $result = json_decode($response);
        if (is_object($result) && ('valid' == $result->license)) {
            $setting = ['license_status' => $result->license, 'license_key' => sanitize_key($_GET['key']), 'expire_date' => $result->expires];
            revisionary()->updateOption('edd_key', $setting);
        }

        echo $response;                                     //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit();

        break;

    case 'deactivate_key':
        check_admin_referer('wp_ajax_pp_deactivate_key');
        if (
            is_multisite() && !is_super_admin() && (PWP::isNetworkActivated() || PWP::isMuPlugin())
        ) {
            return;
        }

        $support_key = revisionary()->getOption('edd_key');
        $request_vars = [
            'edd_action' => "deactivate_license",
            'item_id' => REVISIONARY_EDD_ITEM_ID,
            'license' => $support_key['license_key'],
            'url' => site_url(''),
        ];

        $response = RevisionaryMaint::callHome('deactivate_license', $request_vars);

        $result = json_decode($response);
        if (is_object($result) && $result->license != 'valid') {
            revisionary()->deleteOption('edd_key');
        }

        echo $response;                                     //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit();

        break;
}
}
