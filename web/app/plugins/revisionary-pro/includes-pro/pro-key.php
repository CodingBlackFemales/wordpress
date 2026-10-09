<?php
function _revisionary_key_status($refresh = false) {
    $opt_val = revisionary()->getOption('edd_key');

    if (!is_array($opt_val)) {
        $opt_val = [];
    }

    if (!$refresh && (!is_array($opt_val) || count($opt_val) < 2 || !isset($opt_val['license_key']))) {
        return false;
    } else {
        if ($refresh) {
            $key_string = (isset($opt_val['license_key'])) ? $opt_val['license_key'] : '';

            require_once(REVISIONARY_PRO_ABSPATH . '/includes-pro/library/Factory.php');
            $container      = \PublishPress\Revisions\Factory::get_container();
            $licenseManager = $container['edd_container']['license_manager'];

            if ($key = $licenseManager->sanitize_license_key($key_string)) {
                $status = $licenseManager->validate_license_key($key, REVISIONARY_EDD_ITEM_ID);
            } else {
                $status = false;
            }

            if (!is_scalar($status)) {
                return false;
            }

            $opt_val['license_status'] = $status;
            revisionary()->updateOption('edd_key', $opt_val);

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
