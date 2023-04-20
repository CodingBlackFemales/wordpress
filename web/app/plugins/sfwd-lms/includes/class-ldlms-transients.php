<?php
/**
 * Utility class to contain all transient and cache related functions used within LearnDash.
 *
 * @since 3.1.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Transients' ) ) {
	/**
	 * Class to create the instance.
	 *
	 * @since 3.1.0
	 */
	class LDLMS_Transients {

		/**
		 * Cache Group.
		 *
		 * @since 3.4.1
		 *
		 * @var string $cache_group;
		 */
		private static $cache_group = 'learndash';

		/**
		 * Private constructor for class
		 *
		 * @since 3.1.0
		 */
		private function __construct() {
		}

		/**
		 * Get a Transient
		 *
		 * @since 3.1.0
		 *
		 * @param string $transient_key The transient key to retrieve.
		 *
		 * @return mixed $transient_data the retrieved transient data or false if expired.
		 */
		public static function get( $transient_key = '' ) {
			$transient_data = false;

			if ( ! empty( $transient_key ) ) {

				/**
				 * Filters whether the to bypass cache.
				 *
				 * @since 3.4.1
				 *
				 * @param bool    $bypass_cache  Whether to bypass the existing cache.
				 * @param string  $transient_key Transient Key.
				 *
				 * @return bool true to skip cache. False to use cache.
				 */
				if ( ! apply_filters( 'learndash_bypass_cache', false, $transient_key ) ) {

					/**
					 * Filters whether the object cache is enabled.
					 *
					 * @since 3.4.1
					 *
					 * @param boolean $cache_enabled Whether the object cache is enabled.
					 * @param string  $transient_key  Transient Key.
					 */
					if ( apply_filters( 'learndash_object_cache_enabled', LEARNDASH_OBJECT_CACHE_ENABLED, $transient_key ) ) {
						$found          = false;
						$transient_data = wp_cache_get( $transient_key, self::$cache_group, false, $found );
						if ( false === $found ) {
							$transient_data = false;
						}
					} elseif (
						/**
						 * Filters whether the transients are disabled or not.
						 *
						 * @since 2.3.3
						 *
						 * @param boolean $transients_disabled Whether the transients are disabled or not.
						 * @param string  $transient_key       Transient Key.
						 */
						! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, $transient_key )
					) {
						$transient_data = get_transient( $transient_key );
					}
				}
			}

			return $transient_data;
		}

		/**
		 * Utility function to interface with WP set_transient function. This function allow for
		 * filtering if to actually write the transient.
		 *
		 * @since 3.1.0
		 *
		 * @param string  $transient_key The transient key.
		 * @param mixed   $transient_data Data to store in transient.
		 * @param integer $transient_expire Expiration time for transient.
		 */
		public static function set( $transient_key = '', $transient_data = '', $transient_expire = MINUTE_IN_SECONDS ) {

			if ( ! empty( $transient_key ) ) {
				$transient_expire = apply_filters( 'learndash_cache_expire', $transient_expire, $transient_key );
				$transient_expire = absint( $transient_expire );
				if ( ! empty( $transient_expire ) ) {
					/** This filter is documented in includes/class-ld-transients.php */
					if ( apply_filters( 'learndash_object_cache_enabled', LEARNDASH_OBJECT_CACHE_ENABLED, $transient_key ) ) {
						return wp_cache_set( $transient_key, $transient_data, self::$cache_group, $transient_expire );
					} elseif (
						/** This filter is documented in includes/class-ld-transients.php */
						! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, $transient_key )
					) {
						return set_transient( $transient_key, $transient_data, $transient_expire );
					}
				}
			}
		}

		/**
		 * Delete object cache by key.
		 *
		 * @since 3.4.1
		 *
		 * @param string $transient_key The transient key.
		 */
		public static function delete( $transient_key = '' ) {
			if ( ! empty( $transient_key ) ) {
				/** This filter is documented in includes/class-ld-transients.php */
				if ( apply_filters( 'learndash_object_cache_enabled', LEARNDASH_OBJECT_CACHE_ENABLED, $transient_key ) ) {
					return wp_cache_delete( $transient_key, self::$cache_group );
				} elseif (
					/** This filter is documented in includes/class-ld-transients.php */
					! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, $transient_key )
				) {
					return delete_transient( $transient_key );
				}
			}
		}

		/**
		 * Purge all transients.
		 *
		 * @since 3.1.0
		 */
		public static function purge_all() {
			/** This filter is documented in includes/class-ld-transients.php */
			if ( apply_filters( 'learndash_object_cache_enabled', LEARNDASH_OBJECT_CACHE_ENABLED, 'learndash_all_purge' ) ) {
				wp_cache_flush();
			} elseif (
				/** This filter is documented in includes/class-ld-transients.php */
				! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, 'learndash_all_purge' )
			) {
				global $wpdb;

				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
						'_transient_learndash_%',
						'_transient_timeout_learndash_%'
					)
				);
			}
		}

		// End of functions.
	}
}
