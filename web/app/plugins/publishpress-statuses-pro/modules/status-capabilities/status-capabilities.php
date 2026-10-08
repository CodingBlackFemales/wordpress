<?php
// Status capabilities library: loader stub to select latest version of library
add_filter('publishpress_status_capabilities_library',
    function($status_caps_package_to_use) {
        $status_caps_version = '1.3.3';

        if (empty($status_caps_package_to_use) 
        || !is_object($status_caps_package_to_use)
        || version_compare($status_caps_version, $status_caps_package_to_use->version, '>' )
        ) {
            $status_caps_package_to_use = (object) [
                'version' => $status_caps_version, 
                'path' => __DIR__ . '/StatusCapabilities.php'
            ];
        }

        return $status_caps_package_to_use;
    }
);
