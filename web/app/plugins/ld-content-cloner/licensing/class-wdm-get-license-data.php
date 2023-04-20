<?php

namespace Licensing;

if (!class_exists('Licensing\WdmGetLicenseData')) {
    class WdmGetLicenseData
    {
        private static $responseData = array();

    /**
     * Retrieves licensing information from database. If valid information is not found, sends request to server to get info.
     *
     * @param array $pluginData Plugin data
     * @param bool  $cache      When cache is true, it returns the value stored in static variable $responseData. When set to false, it forcefully retrieves value from database. Example: If you want to show plugin's settings page after activating license, then pass false, so that it will forcefully get the data from database
     *
     * @return string returns 'available' if license is valid or expired else returns 'unavailable'
     */
        public static function getDataFromDb($pluginData, $cache = true)
        {
            $pluginName = $pluginData[ 'pluginName' ];
            $pluginSlug = $pluginData[ 'pluginSlug' ];
            $storeUrl = $pluginData[ 'storeUrl' ];

            if (isset(self::$responseData[$pluginSlug]) && null !== self::$responseData[$pluginSlug] && $cache === true) {
                return self::$responseData[$pluginSlug];
            }

            $licenseTransient = WdmLicense::getCachedVersionInfo('wdm_'.$pluginSlug.'_license_trans');

            $licenseStatus = get_option('edd_'.$pluginSlug.'_license_status');
            if ($licenseTransient || $licenseStatus == EXPIRED) {
                $licenseStatus = get_option('edd_'.$pluginSlug.'_license_status');
                $activeSite = self::getSiteList($pluginSlug);

                self::setResponseData($licenseStatus, $activeSite, $pluginSlug);

                return self::$responseData[$pluginSlug];
            }

            $licenseKey = trim(get_option('edd_'.$pluginSlug.LICENSE_KEY));

            if ($licenseKey) {
                self::checkLicenseOnServer($licenseKey, $pluginName, $pluginSlug, $storeUrl, $pluginData, $licenseStatus);
            }

            return isset(self::$responseData[$pluginSlug]) ? self::$responseData[$pluginSlug] : '';
        }

    /**
     * set lisense status response
     * Set transient if settransient parameter is true
     * @param string  $licenseStatus current license status
     * @param string  $activeSite    Active sites
     * @param string  $pluginSlug    Plugin slug
     * @param boolean $setTransient  whether to set transient or not
     */
        public static function setResponseData($licenseStatus, $activeSite, $pluginSlug, $setTransient = false)
        {
            self::$responseData[$pluginSlug] = 'unavailable';

            if ($licenseStatus == EXPIRED && (!empty($activeSite) || $activeSite != '')) {
                self::$responseData[$pluginSlug] = 'unavailable';
            } elseif ($licenseStatus == EXPIRED || $licenseStatus == VALID) {
                self::$responseData[$pluginSlug] = 'available';
            }

            if ($setTransient) {
                if ($licenseStatus == VALID) {
                    $time = 7;
                } else {
                    $time = 1;
                }
                WdmLicense::setVersionInfoCache('wdm_'.$pluginSlug.'_license_trans', $time, $licenseStatus);
            }
        }

    /**
     * This function is used to get list of sites where license key is already acvtivated.
     *
     * @param type $pluginSlug current plugin's slug
     *
     * @return string list of site
     *
     * @author Foram Rambhiya
     *
     */
        public static function getSiteList($pluginSlug)
        {
            $sites = get_option('wdm_'.$pluginSlug.'_license_key_sites');
            $max = get_option('wdm_'.$pluginSlug.'_license_max_site');
            $currentSite = get_site_url();
            //EDD treats site with www as a different site. Solving this issue.
            $currentSite = str_ireplace('www.', '', $currentSite);
            $currentSite = preg_replace('#^https?://#', '', $currentSite);

            $siteCount = 0;
            $activeSite = '';

            if (!empty($sites) || $sites != '') {
                foreach ($sites as $key) {
                    foreach ($key as $value) {
                        $value = rtrim($value, '/');

                        if (strcasecmp($value, $currentSite) != 0) {
                            $activeSite .= '<li>'.$value.'</li>';
                            ++$siteCount;
                        }
                    }
                }
            }

            if ($siteCount >= $max) {
                return $activeSite;
            } else {
                return '';
            }
        }

        public static function checkLicenseOnServer($licenseKey, $pluginName, $pluginSlug, $storeUrl, $pluginData, $licenseStatus)
        {
             $apiParams = array(
                'edd_action' => 'check_license',
                'license' => $licenseKey,
                'item_name' => urlencode($pluginName),
                'current_version' => $pluginData[ 'pluginVersion' ],
                'plugin_slug'        => $pluginSlug,
                'item_id'       => $pluginData['itemId'],
            );

            $apiParams = WdmSendDataToServer::getAnalyticsData($apiParams);

            $response = wp_remote_post(add_query_arg($apiParams, $storeUrl), array(
            'timeout' => 15, 'sslverify' => false, 'blocking' => true, ));

            if (is_wp_error($response)) {
                return false;
            }

            $licenseData = json_decode(wp_remote_retrieve_body($response));

            $validResponseCode = array('200', '301');

            $currentResponseCode = wp_remote_retrieve_response_code($response);

            if ($licenseData == null || !in_array($currentResponseCode, $validResponseCode)) {
                //if server does not respond, read current license information
                $licenseStatus = get_option('edd_'.$pluginSlug.'_license_status', '');
                if (empty($licenseData)) {
                    WdmLicense::setVersionInfoCache('wdm_'.$pluginSlug.'_license_trans', 1, 'server_did_not_respond');
                }
            } else {
                include_once plugin_dir_path(__FILE__).'class-wdm-add-license-data.php';
                $licenseStatus = WdmAddLicenseData::updateStatus($licenseData, $pluginSlug);
            }

            $activeSite = self::getSiteList($pluginSlug);

            self::setResponseData($licenseStatus, $activeSite, $pluginSlug, true);
        }
    }
}
