<?php

namespace Licensing;

if ( ! class_exists( 'Licensing\WdmLicense' ) ) {
	class WdmLicense {

		/**
		 * @var plugin data
		 */
		private static $pluginData = array();

		public function __construct( $plugin_data ) {
			$slug                      = $plugin_data['pluginSlug'];
			self::$pluginData[ $slug ] = $plugin_data;

			// Constants
			if ( ! defined( 'LICENSE_KEY' ) ) {
				define( 'LICENSE_KEY', '_license_key' );
			}

			if ( ! defined( 'VALID' ) ) {
				define( 'VALID', 'valid' );
			}

			if ( ! defined( 'EXPIRED' ) ) {
				define( 'EXPIRED', 'expired' );
			}

			if ( ! defined( 'INVALID' ) ) {
				define( 'INVALID', 'invalid' );
			}

			require_once 'class-wdm-add-license-data.php';
			$addLicenseData = new \Licensing\WdmAddLicenseData( $plugin_data );

			require_once 'class-wdm-send-customer-data.php';
			$sendDataToServer = new \Licensing\WdmSendDataToServer( $plugin_data );

			$getDataFromDb = self::checkLicenseAvailiblity( $slug, false );
			if ( $getDataFromDb == 'available' ) {
				require_once 'class-wdm-plugin-updater.php';
				$pluginUpdater = new \Licensing\wdmPluginUpdater( $plugin_data['baseFolderDir'] . '/' . $plugin_data['mainFileName'], $plugin_data );
			}

			$oldTransient = get_transient( 'wdm_' . $slug . '_license_trans' );
			if ( $oldTransient ) {
				delete_transient( 'wdm_' . $slug . '_license_trans' );
				self::setVersionInfoCache( 'wdm_' . $slug . '_license_trans', 7, $oldTransient );
			}

			unset( $addLicenseData );
			unset( $sendDataToServer );
			unset( $pluginUpdater );
		}

		public static function checkLicenseAvailiblity( $slug, $cache = true ) {
			require_once 'class-wdm-get-license-data.php';

			return \Licensing\WdmGetLicenseData::getDataFromDb( self::$pluginData[ $slug ], $cache );
		}

		public static function getCachedVersionInfo( $cacheKey ) {
			$cache = get_option( $cacheKey );
			if ( ! $cache ) {
				return false;
			}
			if ( $cache['timeout'] != 0 && ( empty( $cache['timeout'] ) || current_time( 'timestamp' ) > $cache['timeout'] ) ) {
				return false; // Cache is expired
			}

			return json_decode( $cache['value'] );
		}

		public static function setVersionInfoCache( $cacheKey, $time, $value = '' ) {
			if ( $time == 0 ) {
				$timeOut = 0;
			} else {
				$timeOut = strtotime( '+' . $time . ' day', current_time( 'timestamp' ) );
			}
			$data = array(
				'timeout' => $timeOut,
				'value'   => json_encode( $value ),
			);
			update_option( $cacheKey, $data );
		}
	}
}
