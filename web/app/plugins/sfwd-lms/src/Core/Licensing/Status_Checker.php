<?php
/**
 * LearnDash licensing status checker class file.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Licensing;

use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Str;

/**
 * LearnDash licensing status checker class.
 *
 * @since 4.17.0
 *
 * @phpstan-type SubscriptionStatus 'active'|'pending-cancel'|'on-hold'|'cancelled'|'expired'
 *
 * @phpstan-type LicenseStatus array{
 *  type: 'current'|'legacy',
 *  status: SubscriptionStatus,
 *  expiry: string
 * }
 *
 * @phpstan-type LicenseData array{
 *  variation: string,
 *  status: SubscriptionStatus,
 *  expiry: int
 * }
 */
class Status_Checker {
	/**
	 * Transient key prefix for storing the raw result of the licensing request.
	 * The full key is suffixed with a hash of the license key.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const LEARNDASH_LICENSE_CHECKER_RAW_RESULT_TRANSIENT_KEY = 'learndash_license_checker_raw_result';

	/**
	 * Transient key prefix for storing the legacy license keys map returned by the
	 * licensing server's /subscriptions/keys endpoint. The full key is suffixed
	 * with a hash of the license key.
	 *
	 * @since 5.1.3
	 *
	 * @var string
	 */
	private const LEARNDASH_LEGACY_LICENSE_KEYS_TRANSIENT_KEY = 'learndash_legacy_license_keys';

	/**
	 * Timeout for the licensing cache.
	 *
	 * @since 4.17.0
	 *
	 * @var int
	 */
	private const LEARNDASH_LICENSING_TRANSIENT_TIMEOUT = 12 * HOUR_IN_SECONDS;

	/**
	 * Licensing slug for LearnDash Core.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_learndash_core = 'learndash';

	/**
	 * Licensing slug for LearnDash ProPanel.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_learndash_propanel = 'learndash-propanel';

	/**
	 * Licensing slug for Reports for LearnDash.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_reports_for_learndash = 'report-pro';

	/**
	 * Licensing slug for Instructor Role.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_instructor_role = 'instructor-role';

	/**
	 * Licensing slug for LearnDash Gradebook.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_gradebook = 'gradebook';

	/**
	 * Licensing slug for LearnDash Groups Plus.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_groups_plus = 'groups-management';

	/**
	 * Licensing slug for LearnDash Notes.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_notes = 'learndash-notes';

	/**
	 * Licensing slug for LearnDash Reviews Plus.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_reviews_plus = 'ratings-reviews-feedbacks';

	/**
	 * Licensing slug for LearnDash Group Registration (Seats Plus).
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_group_registration = 'groups-management';

	/**
	 * Licensing slug for MemberDash.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_memberdash = 'memberdash';

	/**
	 * Licensing slug for LearnDash ProPanel Addon.
	 *
	 * @since 5.1.0
	 *
	 * @var string
	 */
	public static string $licensing_slug_learndash_propanel_addon = 'propanel';

	/**
	 * Returns the license status for a specific plugin licensing slug.
	 *
	 * @since 4.17.0
	 *
	 * @param string      $plugin_licensing_slug The plugin licensing slug. Use one of the class properties $licensing_slug_*.
	 * @param string|null $license_key           The license key to use. If not set, the license key will be fetched from the WP options.
	 *
	 * @return LicenseStatus|array{} The license status. If the license key is invalid or not set, an empty array is returned.
	 */
	public static function get_status( string $plugin_licensing_slug, ?string $license_key = null ): array {
		$licensing_data = self::get_licensing_data( $license_key );

		// If the licensing data is not available, return an empty array.

		if ( empty( $licensing_data ) ) {
			return [];
		}

		$current_license_status = [];
		$legacy_license_status  = [];

		// Simple case: the plugin licensing slug is found in the licensing data.

		if ( isset( $licensing_data[ $plugin_licensing_slug ] ) ) {
			$current_license_status = [
				'type'   => 'current',
				'status' => self::get_final_license_status( $licensing_data[ $plugin_licensing_slug ] ),
				'expiry' => self::get_max_expiry_as_iso( $licensing_data[ $plugin_licensing_slug ] ),
			];
		}

		// Special cases: LearnDash Core and ProPanel legacy licenses.

		if (
			in_array(
				$plugin_licensing_slug,
				[ self::$licensing_slug_learndash_core, self::$licensing_slug_learndash_propanel, self::$licensing_slug_reports_for_learndash ],
				true
			)
			&& (
				empty( $current_license_status )
				|| ! self::does_status_allow_access( $current_license_status['status'] ) // If the current license does not allow access, check for legacy licenses.
			)
		) {
			// LearnDash Legacy.

			if ( $plugin_licensing_slug === self::$licensing_slug_learndash_core ) {
				$legacy_license_status = self::get_status_for_learndash_legacy( $licensing_data );
			}

			// LearnDash ProPanel Legacy.

			if ( $plugin_licensing_slug === self::$licensing_slug_learndash_propanel ) {
				$legacy_license_status = self::get_status_for_propanel_legacy( $licensing_data );
			}
		}

		// Return the legacy license status if it allows access or if the current license status is not set.

		if (
			! empty( $legacy_license_status )
			&& (
				self::does_status_allow_access( $legacy_license_status['status'] )
				|| empty( $current_license_status )
			)
		) {
			return $legacy_license_status;
		}

		return $current_license_status;
	}

	/**
	 * Returns a map of product_slug => license_key for all legacy products
	 * available to the supplied license key, fetched from the licensing server's
	 * /subscriptions/keys endpoint.
	 *
	 * When the server returns more than one key for the same product_slug, only
	 * the first one is retained.
	 *
	 * @since 5.1.3
	 *
	 * @param string|null $license_key The license key to use. If not set, the license key will be fetched from the WP options.
	 *
	 * @return array<string, string> Map of product_slug => license_key.
	 */
	public static function get_legacy_license_keys( ?string $license_key = null ): array {
		if ( empty( $license_key ) ) {
			$license_key = sanitize_text_field(
				Cast::to_string( get_option( LEARNDASH_LICENSE_KEY ) )
			);
		}

		if ( empty( $license_key ) ) {
			return [];
		}

		$transient_key = self::LEARNDASH_LEGACY_LICENSE_KEYS_TRANSIENT_KEY . '_' . md5( $license_key );

		$cached = get_transient( $transient_key );

		if ( $cached === 'invalid' ) {
			return [];
		}

		if ( is_array( $cached ) ) {
			return self::sanitize_legacy_license_keys( $cached );
		}

		$request = wp_remote_get(
			self::get_licensing_server_url() . 'wp-json/learndash/v2/subscriptions/keys?license_key=' . $license_key
		);

		if ( is_wp_error( $request ) ) {
			return [];
		}

		$response = wp_remote_retrieve_body( $request );

		if ( empty( $response ) ) {
			return [];
		}

		$data = json_decode( $response, true );

		if ( ! is_array( $data ) ) {
			return [];
		}

		if (
			isset( $data['code'] )
			&& $data['code'] === 'rest_invalid_license_key'
		) {
			set_transient( $transient_key, 'invalid', self::LEARNDASH_LICENSING_TRANSIENT_TIMEOUT );

			return [];
		}

		$keys = [];

		foreach ( $data as $item ) {
			if (
				! is_array( $item )
				|| ! isset( $item['license_key'], $item['product_slug'] )
				|| ! is_string( $item['license_key'] )
				|| ! is_string( $item['product_slug'] )
				|| $item['license_key'] === ''
				|| $item['product_slug'] === ''
			) {
				continue;
			}

			// First-wins: only keep the first license key seen per product_slug.
			if ( ! isset( $keys[ $item['product_slug'] ] ) ) {
				$keys[ $item['product_slug'] ] = $item['license_key'];
			}
		}

		set_transient(
			$transient_key,
			$keys,
			self::LEARNDASH_LICENSING_TRANSIENT_TIMEOUT
		);

		return $keys;
	}

	/**
	 * Sanitizes a cached product_slug => license_key map.
	 *
	 * @since 5.1.3
	 *
	 * @param array<mixed> $keys The cached map.
	 *
	 * @return array<string, string>
	 */
	private static function sanitize_legacy_license_keys( array $keys ): array {
		$sanitized = [];

		foreach ( $keys as $product_slug => $license_key ) {
			if (
				is_string( $product_slug )
				&& is_string( $license_key )
				&& $product_slug !== ''
				&& $license_key !== ''
			) {
				$sanitized[ $product_slug ] = $license_key;
			}
		}

		return $sanitized;
	}

	/**
	 * Returns whether the license status allows access to the plugin.
	 *
	 * The license status allows access if it is one of the following:
	 * - 'active'
	 * - 'pending-cancel'
	 * - 'on-hold'
	 *
	 * @since 4.17.0
	 *
	 * @param SubscriptionStatus $status The license status.
	 *
	 * @return bool
	 */
	public static function does_status_allow_access( string $status ): bool { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- phpcs does not support PHPStan types.
		return in_array( $status, [ 'active', 'pending-cancel', 'on-hold' ], true );
	}

	/**
	 * Returns the license status for ProPanel legacy licenses.
	 *
	 * @since 4.17.0
	 *
	 * @param array<string, array<LicenseData>> $licensing_data The licensing data. The key is the plugin licensing slug returned by the licensing server.
	 *
	 * @return LicenseStatus|array{} The license status. If the license key is invalid or not set, an empty array is returned.
	 */
	private static function get_status_for_propanel_legacy( array $licensing_data ): array {
		// Map all licenses which variation starts with 'pro' or 'plus'.

		$legacy_licenses = array_filter(
			$licensing_data['learndash-legacy'] ?? [],
			function ( array $license ): bool {
				return Str::starts_with( strtolower( $license['variation'] ), [ 'pro', 'plus' ] );
			}
		);

		if ( ! empty( $legacy_licenses ) ) {
			return [
				'type'   => 'legacy',
				'status' => self::get_final_license_status( $legacy_licenses ),
				'expiry' => self::get_max_expiry_as_iso( $legacy_licenses ),
			];
		}

		// License key not found.

		return [];
	}

	/**
	 * Returns the license status for LearnDash legacy licenses.
	 *
	 * @since 4.17.0
	 *
	 * @param array<string, array<LicenseData>> $licensing_data The licensing data. The key is the plugin licensing slug returned by the licensing server.
	 *
	 * @return LicenseStatus|array{} The license status. If the license key is invalid or not set, an empty array is returned.
	 */
	private static function get_status_for_learndash_legacy( array $licensing_data ): array {
		$legacy_licenses = $licensing_data['learndash-legacy'] ?? [];

		if ( ! empty( $legacy_licenses ) ) {
			return [
				'type'   => 'legacy',
				'status' => self::get_final_license_status( $legacy_licenses ),
				'expiry' => self::get_max_expiry_as_iso( $legacy_licenses ),
			];
		}

		// License key not found.

		return [];
	}

	/**
	 * Returns the final license status (best status in order of allow access) for a list of licenses.
	 *
	 * The final license status is determined by the following rules:
	 * - If at least one license is active, the final status is 'active'.
	 * - If at least one license is pending-cancel, the final status is 'pending-cancel'.
	 * - If at least one license is on-hold, the final status is 'on-hold'.
	 * - If at least one license is cancelled, the final status is 'cancelled'.
	 * - If no other status is found, the final status is 'expired'.
	 *
	 * @since 4.17.0
	 *
	 * @param array<LicenseData> $licenses The licenses to check.
	 *
	 * @return SubscriptionStatus The final license status.
	 */
	private static function get_final_license_status( array $licenses ): string {
		$statuses = array_unique(
			array_map(
				function ( array $license ): string {
					return $license['status'];
				},
				$licenses
			)
		);

		// Order is important here.
		foreach ( [ 'active', 'pending-cancel', 'on-hold', 'cancelled' ] as $status ) {
			if ( in_array( $status, $statuses, true ) ) {
				return $status;
			}
		}

		// Default to 'expired' if no other status is found.

		return 'expired';
	}

	/**
	 * Returns the latest expiry date from a list of licenses as an ISO 8601 date string (Y-m-d).
	 *
	 * @since 5.1.0
	 *
	 * @param array<LicenseData> $licenses The licenses to check.
	 *
	 * @return string The latest expiry date as "Y-m-d", or an empty string if no expiry is found.
	 */
	private static function get_max_expiry_as_iso( array $licenses ): string {
		$max = 0;

		foreach ( $licenses as $license ) {
			if ( $license['expiry'] > $max ) {
				$max = $license['expiry'];
			}
		}

		return $max > 0 ? gmdate( 'Y-m-d', $max ) : '';
	}

	/**
	 * Returns the licensing data fetched from the licensing server or cache, if available.
	 *
	 * @since 4.17.0
	 *
	 * @param string|null $license_key The license key to use. If not set, the license key will be fetched from the WP options.
	 *
	 * @return array<string, array<LicenseData>>|array{} The licensing data or an empty array if the data is not available. The key is the plugin licensing slug returned by the licensing server.
	 */
	private static function get_licensing_data( ?string $license_key = null ): array {
		if ( empty( $license_key ) ) {
			$license_key = sanitize_text_field(
				Cast::to_string( get_option( LEARNDASH_LICENSE_KEY ) )
			);
		}

		// If the license key is not set, return an empty array.

		if ( empty( $license_key ) ) {
			return [];
		}

		$transient_key = self::LEARNDASH_LICENSE_CHECKER_RAW_RESULT_TRANSIENT_KEY . '_' . md5( $license_key );

		$cached = get_transient( $transient_key );

		// If the cached value indicates an invalid license key, return early without hitting the server.

		if ( $cached === 'invalid' ) {
			return [];
		}

		$licensing_data = self::sanitize_license_data( $cached );

		// If the licensing data is not in the cache, fetch it from the licensing server.

		if ( empty( $licensing_data ) ) {
			$request = wp_remote_get(
				self::get_licensing_server_url() . 'wp-json/learndash/v2/subscriptions/status?license_key=' . $license_key,
			);

			if ( is_wp_error( $request ) ) {
				return [];
			}

			$response = wp_remote_retrieve_body( $request );

			if ( empty( $response ) ) {
				return [];
			}

			$licensing_data = json_decode( $response, true );

			if ( ! is_array( $licensing_data ) ) {
				return [];
			}

			// Cache invalid license key responses to avoid repeated server calls.

			if (
				isset( $licensing_data['code'] )
				&& $licensing_data['code'] === 'rest_invalid_license_key'
			) {
				set_transient( $transient_key, 'invalid', self::LEARNDASH_LICENSING_TRANSIENT_TIMEOUT );

				return [];
			}

			$licensing_data = self::sanitize_license_data( $licensing_data );

			// Cache the licensing data.

			if ( ! empty( $licensing_data ) ) {
				set_transient(
					$transient_key,
					$licensing_data,
					self::LEARNDASH_LICENSING_TRANSIENT_TIMEOUT
				);
			}
		}

		return $licensing_data;
	}

	/**
	 * Sanitizes the licensing data.
	 *
	 * @since 4.17.0
	 *
	 * @param mixed $licensing_data The licensing data to sanitize.
	 *
	 * @return array<string, array<LicenseData>>|array{} The sanitized licensing data or an empty array if the data is not valid.
	 */
	private static function sanitize_license_data( $licensing_data ): array {
		if ( ! is_array( $licensing_data ) ) {
			return [];
		}

		foreach ( $licensing_data as $licensing_plugin_slug => $licenses ) {
			if (
				! is_string( $licensing_plugin_slug )
				|| ! is_array( $licenses )
			) {
				unset( $licensing_data[ $licensing_plugin_slug ] );
				continue;
			}

			foreach ( $licenses as $array_index => $license ) {
				if (
					! is_array( $license )
					|| ! isset( $license['variation'], $license['status'], $license['expiry'] )
				) {
					unset( $licenses[ $array_index ] );
					continue;
				}

				if (
					! is_string( $license['variation'] )
					|| ! in_array( $license['status'], [ 'active', 'cancelled', 'expired', 'on-hold', 'pending-cancel' ], true )
					|| ! is_int( $license['expiry'] )
				) {
					unset( $licenses[ $array_index ] );
					continue;
				}
			}

			// Remove empty licenses and update the licenses array.

			if ( empty( $licenses ) ) {
				unset( $licensing_data[ $licensing_plugin_slug ] );
			} else {
				$licensing_data[ $licensing_plugin_slug ] = $licenses;
			}
		}

		return $licensing_data;
	}

	/**
	 * Returns the licensing server URL.
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	private static function get_licensing_server_url(): string {
		return trailingslashit(
			defined( 'LEARNDASH_LICENSING_SERVER_URL' )
				? LEARNDASH_LICENSING_SERVER_URL
				: 'https://checkout.learndash.com'
		);
	}
}
