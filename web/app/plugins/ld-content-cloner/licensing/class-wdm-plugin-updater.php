<?php

namespace Licensing;

/*
 * Allows plugins to use their own update API.
 */
if ( ! class_exists( 'Licensing\WdmPluginUpdater' ) ) {
	class WdmPluginUpdater {

		private $apiUrl      = '';
		private $apiData     = array();
		private $name        = '';
		private $slug        = '';
		private $version     = '';
		private $wp_override = true;
		private $cache_key   = '';
		private $license     = '';
		private $responseData;

		/**
		 * Class constructor.
		 *
		 * @uses plugin_basename()
		 * @uses hook()
		 *
		 * @param string $apiUrl     The URL pointing to the custom API endpoint.
		 * @param string $pluginFile Path to the plugin file.
		 * @param array  $apiData    Optional data to send with API calls.
		 */
		public function __construct( $pluginFile, $apiData = null ) {
			global $edd_plugin_data;
			$this->apiUrl  = trailingslashit( $apiData['storeUrl'] );
			$this->apiData = urlencode_deep( $apiData );
			if ( ! isset( $apiData['isTheme'] ) || ! $apiData['isTheme'] ) {
				$this->name        = plugin_basename( $pluginFile );
				$this->slug        = $apiData['pluginSlug'];
				$this->productType = 'plugin';
			} else {
				$this->name           = $apiData['pluginSlug'];
				$this->slug           = $apiData['pluginSlug'];
				$this->productType    = 'theme';
				$this->changelog_link = $apiData['themeChangelogUrl'];
			}
			$this->version                  = $apiData['pluginVersion'];
			$this->wp_override              = ! isset( $apiData['wp_override'] ) || (bool) $apiData['wp_override'];
			$this->license                  = trim( get_option( 'edd_' . urldecode_deep( $this->slug ) . LICENSE_KEY ) );
			$this->cache_key                = md5( serialize( $this->slug . $this->license ) );
			$edd_plugin_data[ $this->slug ] = $this->apiData;

			// Set up hooks.
			$this->hook();
		}

		/**
		 * Set up WordPress filters to hook into WP's update process.
		 *
		 * @uses add_filter()
		 */
		private function hook() {
			if ( $this->productType == 'theme' ) {
				add_filter( 'pre_set_site_transient_update_themes', array( $this, 'preSetSiteTransientUpdatePluginsFilter' ) );
				add_filter( 'pre_set_transient_update_themes', array( $this, 'preSetSiteTransientUpdatePluginsFilter' ) );
				add_filter( 'themes_api', array( $this, 'pluginsApiFilter' ), 10, 3 );
			} elseif ( $this->productType == 'plugin' ) {
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'preSetSiteTransientUpdatePluginsFilter' ) );
				add_filter( 'pre_set_transient_update_plugins', array( $this, 'preSetSiteTransientUpdatePluginsFilter' ) );
				add_filter( 'plugins_api', array( $this, 'pluginsApiFilter' ), 10, 3 );
			}
		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * This function dives into the update api just when WordPress creates its update array,
		 * then adds a custom API call and injects the custom plugin data retrieved from the API.
		 * It is reassembled from parts of the native WordPress plugin update code.
		 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
		 *
		 * @uses apiRequest()
		 *
		 * @param array $transientData Update array build by WordPress.
		 *
		 * @return array Modified update array with custom plugin data.
		 */
		public function preSetSiteTransientUpdatePluginsFilter( $transientData ) {
			global $pagenow;
			if ( ! is_object( $transientData ) ) {
				$transientData = new \stdClass();
			}

			if ( 'plugins.php' == $pagenow && is_multisite() ) {
				return $transientData;
			}
			if ( ! empty( $transientData->response ) && ! empty( $transientData->response[ $this->name ] ) && false === $this->wp_override ) {
				return $transientData;
			}

			$version_info = $this->getCachedVersionInfo();
			if ( false === $version_info || empty( $version_info ) ) {
				$version_info = $this->apiRequest( array( 'slug' => $this->slug ) );
				$this->setVersionInfoCache( $version_info );
			}

			return $this->getUpdatedTransientData( $transientData, $version_info );
		}

		public function getUpdatedTransientData( $transientData, $version_info ) {
			if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
				if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {
					if ( $this->productType == 'theme' ) {
						$version_info        = (array) $version_info;
						$version_info['url'] = $this->changelog_link;
					}
					$transientData->response[ $this->name ] = $version_info;
				}
				$transientData->last_checked           = time();
				$transientData->checked[ $this->name ] = $this->version;
			}

			return $transientData;
		}

		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @uses apiRequest()
		 *
		 * @param mixed  $data
		 * @param string $action
		 * @param object $args
		 *
		 * @return object $data
		 */
		public function pluginsApiFilter( $data, $action = '', $args = null ) {
			if ( $this->productType == 'theme' ) {
				$action_type = 'theme_information';
			}

			if ( $this->productType == 'plugin' ) {
				$action_type = 'plugin_information';
			}

			if ( $action_type != $action || ! isset( $args->slug ) || ( $args->slug != $this->slug ) ) {
				return $data;
			}

			$to_send               = array(
				'slug'   => $this->slug,
				'is_ssl' => is_ssl(),
				'fields' => array(
					'banners' => false, // These will be supported soon hopefully
					'reviews' => false,
				),
			);
			$api_request_cache_key = 'edd_api_request_' . md5( serialize( $this->slug . $this->license ) );
			// Get the transient where we store the api request for this plugin for 24 hours
			$edd_api_request_transient = $this->getCachedVersionInfo( $api_request_cache_key );
			// If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
			if ( empty( $edd_api_request_transient ) ) {
				$api_response = $this->apiRequest( $to_send );
				// Expires in 6 hours
				$this->setVersionInfoCache( $api_response, $api_request_cache_key );
				if ( false !== $api_response ) {
					$data = $api_response;
				}
			}

			// Convert sections into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $data->sections ) && ! is_array( $data->sections ) ) {
				$new_sections = array();
				foreach ( $data->sections as $key => $value ) {
					$new_sections[ $key ] = $value;
				}
				$data->sections = $new_sections;
			}

			return $data;
		}

		/**
		 * Calls the API and, if successfull, returns the object delivered by the API.
		 *
		 * @uses get_bloginfo()
		 * @uses wp_remote_get()
		 * @uses is_wp_error()
		 *
		 * @param string $action The requested action.
		 * @param array  $data   Parameters for the API action.
		 *
		 * @return false||object
		 */
		private function apiRequest( $data ) {
			if ( null !== $this->responseData && ! empty( $this->responseData ) ) {
				return $this->responseData;
			}

			$data = array_merge( $this->apiData, $data );

			$licenseKey = trim( get_option( 'edd_' . urldecode_deep( $data['pluginSlug'] ) . LICENSE_KEY ) );

			if ( $data['slug'] != $this->slug || $this->apiUrl == trailingslashit( home_url() ) || empty( $licenseKey ) ) {
				return;
			}

			$apiParams = array(
				'edd_action'      => 'get_version',
				'license'         => $licenseKey,
				'slug'            => $this->slug,
				'author'          => $data['authorName'],
				'current_version' => $this->version,
				'url'             => home_url(),
			);

			if ( $data['itemId'] ) {
				$apiParams['item_id'] = $data['itemId'];
			}

			$apiParams = WdmSendDataToServer::getAnalyticsData( $apiParams );

			$request = wp_remote_post(
				add_query_arg( $apiParams, $this->apiUrl ),
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'blocking'  => true,
				)
			);

			if ( ! is_wp_error( $request ) ) {
				$request = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( $request && isset( $request->sections ) ) {
				$request->sections = maybe_unserialize( $request->sections );
			} else {
				$request = false;
			}

			$this->responseData = $request;

			return $request;
		}

		public function getUpdateCache() {
			$update_cache = get_site_transient( 'update_plugins' );
			return is_object( $update_cache ) ? $update_cache : new \stdClass();
		}

		public function getCachedVersionInfo( $cache_key = '' ) {
			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}
			$cache = get_option( $cache_key );
			if ( empty( $cache['timeout'] ) || current_time( 'timestamp' ) > $cache['timeout'] ) {
				return false; // Cache is expired
			}

			return json_decode( $cache['value'] );
		}

		public function setVersionInfoCache( $value = '', $cache_key = '' ) {
			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}
			$data = array(
				'timeout' => strtotime( '+6 hours', current_time( 'timestamp' ) ),
				'value'   => json_encode( $value ),
			);
			update_option( $this->cache_key, $data );
		}
	}
}
