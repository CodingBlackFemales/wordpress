<?php
namespace PublishPress\Permissions;

require_once(PRESSPERMIT_PRO_ABSPATH . '/includes-pro/pro-maint.php');

$pp_ajax_settings = PWP::GET_key('pp_ajax_settings');
$key = PWP::GET_key('key');

switch ($pp_ajax_settings) {
    case 'activate_key':
        if (!current_user_can('pp_manage_settings')) {
            wp_die(esc_html(PWP::__wp('Cheatin&#8217; uh?')));
        }

        check_admin_referer('wp_ajax_pp_activate_key');
        if (
            is_multisite() && !is_super_admin() && (PWP::isNetworkActivated() || PWP::isMuPlugin())
        ) {
            return;
        }

        $request_vars = [
            'edd_action' => "activate_license",
            'item_id' => PRESSPERMIT_EDD_ITEM_ID,
            'license' => $key,
            'url' => site_url(''),
        ];

        $response = PressPermitMaint::callHome('activate_license', $request_vars);
        $result = (is_string($response) && is_object(json_decode($response))) ? json_decode($response) : $response;

        if (is_object($result) && isset($result->license) && ('valid' == $result->license)) {
            $setting = ['license_status' => $result->license, 'license_key' => $key, 'expire_date' => $result->expires];
            presspermit()->updateOption('edd_key', $setting);
        } else {
            usleep(500000);

            // If invalid as a Press Permit Pro upgrade key, try activating as a new Permissions Pro key
            $edd_item_id = ($request_vars['item_id'] == 21050) ? 34506 : 21050;

            delete_option('presspermit_edd_id');

            $request_vars['item_id'] = $edd_item_id;
            $response = PressPermitMaint::callHome('activate_license', $request_vars);
            $result = (is_string($response) && is_object(json_decode($response))) ? json_decode($response) : $response;

            if (is_object($result) && isset($result->license)) {
                if ('valid' == $result->license) {
                    $setting = ['license_status' => $result->license, 'license_key' => $key, 'expire_date' => $result->expires];
                    presspermit()->updateOption('edd_key', $setting);
                    update_option('presspermit_edd_id', $edd_item_id);
                } else {
                    $result->license = 'retry';
                    $response = wp_json_encode($result);
                }
            } else {
                if (is_string($result)) {
                    $response = (!empty($result)) 
                    ? $result 
                    : 'An unidentified error occurred';  // This should never happen unless logic in callHome() goes wrong

                } elseif (is_scalar($result)) {
                    $response = sprintf('Error: %s', strval($result));  // This will probably never happen

                } elseif (is_array($result)) {
                    $response = sprintf('Error: %s', wp_json_encode($result));  // This will probably never happen
                } else {
                    $response = 'An unidentified error occurred';  // This should never happen unless logic in callHome() goes wrong
                }
            }
        }

        // Echoing response from our trusted server

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $response;
        exit();

        break;

    case 'deactivate_key':
        if (!current_user_can('pp_manage_settings')) {
            wp_die(esc_html(PWP::__wp('Cheatin&#8217; uh?')));
        }

        check_admin_referer('wp_ajax_pp_deactivate_key');
        if (
            is_multisite() && !is_super_admin() && (PWP::isNetworkActivated() || PWP::isMuPlugin())
        ) {
            return;
        }

        $support_key = presspermit()->getOption('edd_key');
        $request_vars = [
            'edd_action' => "deactivate_license",
            'item_id' => PRESSPERMIT_EDD_ITEM_ID,
            'license' => $support_key['license_key'],
            'url' => site_url(''),
        ];

        $response = PressPermitMaint::callHome('deactivate_license', $request_vars);

        $result = json_decode($response);
        if (is_object($result) && $result->license != 'valid') {
            presspermit()->deleteOption('edd_key');
        }

        // Echoing response from our trusted server

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $response;
        exit();

        break;
}
