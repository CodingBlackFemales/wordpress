<?php
function _presspermit_key_status($refresh = false) {
    $opt_val = presspermit()->getOption('edd_key');

    if (!is_array($opt_val)) {
        $opt_val = [];
    }

    $license_key = (!empty($opt_val['license_key'])) ? $opt_val['license_key'] : '';

    if (!$refresh && (!is_array($opt_val) || count($opt_val) < 2 || !isset($license_key))) {
        return false;
    } else {
        if ($refresh) {
            require_once(PRESSPERMIT_PRO_ABSPATH . '/includes-pro/library/Factory.php');
            $container      = \PublishPress\Permissions\Factory::get_container();
            $licenseManager = $container['edd_container']['license_manager'];

            $key = $licenseManager->sanitize_license_key($license_key);
            $status = $licenseManager->validate_license_key($key, PRESSPERMIT_EDD_ITEM_ID);

            if (!is_scalar($status)) {
                return false;
            }

            $opt_val['license_status'] = $status;
            presspermit()->updateOption('edd_key', $opt_val);

            if ('valid' == $status) {
                return true;
            } elseif('expired' == $status) {
                return 'expired';
            } else {
                // If invalid as a Press Permit Pro upgrade key, try activating as a new Permissions Pro key
                $edd_item_id = (34506 == PRESSPERMIT_EDD_ITEM_ID) ? 21050 : 34506;
                $status = $licenseManager->validate_license_key($key, $edd_item_id);

                if (!is_scalar($status)) {
                    return false;
                }

                $opt_val['license_status'] = $status;
                presspermit()->updateOption('edd_key', $opt_val);

                if ('valid' == $status) {
                    update_option('presspermit_edd_id', $edd_item_id);
                    return true;
                
                } elseif('expired' == $status) {
                    return 'expired';
                }
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