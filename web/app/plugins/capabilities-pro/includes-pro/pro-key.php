<?php
/*
 * PublishPress Capabilities Pro
 *
 * Special purpose functions related to the license key
 *
 */

function _cme_key_status($refresh = false) {
    $opt_val = get_option('cme_edd_key');

    if (!$refresh && (!is_array($opt_val) || count($opt_val) < 2 || !isset($opt_val['license_key']))) {
        return false;
    } else {
        if ($refresh) {
            require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/library/Factory.php');
            $container      = \PublishPress\Capabilities\Factory::get_container();
            $licenseManager = $container['edd_container']['license_manager'];

            if (empty($opt_val['license_key'])) {
                return false;
            }

            $key = $licenseManager->sanitize_license_key($opt_val['license_key']);
            $status = $licenseManager->validate_license_key($key, PUBLISHPRESS_CAPS_EDD_ITEM_ID);

            if (!is_scalar($status)) {
                return false;
            }

            $opt_val['license_status'] = $status;
            update_option('cme_edd_key', $opt_val);

            if ('valid' == $status) {
                return true;
            } elseif('expired' == $status) {
                return 'expired';
            }
        } else {
            if ('valid' == $opt_val['license_status']) {
                return true;
            } elseif ('expired' == $opt_val['license_status']) {
                return 'expired';
            }
        }
    }

    return false;
}
