<?php
/**
 * LearnDash Harbor Addon Legacy Licenses class.
 *
 * @since 5.1.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries\Harbor;

use LearnDash\Core\Licensing\Status_Checker;
use LearnDash\Core\Utilities\Cast;

/**
 * Registers legacy license keys for premium addons that store their auth token
 * in an auth-token.php file at the plugin root.
 *
 * @since 5.1.0
 *
 * @phpstan-type AddonEntry array{
 *   harbor_slug: string,
 *   status_slug: string,
 *   subscription_slug: string,
 * }
 */
class Addon_Legacy_Licenses {
	/**
	 * Map of plugin root path constants to their slug configuration.
	 *
	 * Each entry should be:
	 *   'CONSTANT_NAME' => [
	 *     'harbor_slug'       => 'harbor-slug',       // slug used in the lw-harbor/legacy_licenses filter
	 *     'status_slug'       => 'status-slug',       // slug used for Status_Checker::get_status()
	 *     'subscription_slug' => 'subscription-slug', // product_slug used by Status_Checker::get_legacy_license_keys()
	 *   ]
	 *
	 * @since 5.1.0
	 *
	 * @var array<string,AddonEntry>
	 */
	private array $addon_map;

	/**
	 * Constructor.
	 *
	 * @since 5.1.0
	 *
	 * @param array<string,AddonEntry> $addon_map Map of ABSPATH constant names to slug configuration.
	 */
	public function __construct( array $addon_map ) {
		$this->addon_map = $addon_map;
	}

	/**
	 * Registers legacy license keys for each addon.
	 *
	 * For each addon, the license key is sourced from the licensing server's
	 * /subscriptions/keys endpoint. If no remote key is available and the
	 * plugin is installed locally, the addon's own auth-token.php file is
	 * used as a fallback.
	 *
	 * When the plugin is not installed locally, the addon is registered only
	 * when the user has access AND a remote key is available.
	 *
	 * Hooked into `lw-harbor/legacy_licenses`.
	 *
	 * @since 5.1.0
	 *
	 * @param array<int,array<string,mixed>> $licenses The existing legacy licenses.
	 *
	 * @return array<int,array<string,mixed>> The legacy licenses with addon entries appended.
	 */
	public function register( array $licenses ): array {
		$remote_keys = Status_Checker::get_legacy_license_keys();

		foreach ( $this->addon_map as $constant => $entry ) {
			$auth_token_file = defined( $constant )
				? trailingslashit( Cast::to_string( constant( $constant ) ) ) . 'auth-token.php'
				: '';

			$is_installed = (
				$auth_token_file !== ''
				&& file_exists( $auth_token_file )
			);

			$remote_key  = $remote_keys[ $entry['subscription_slug'] ] ?? '';
			$status_data = Status_Checker::get_status( $entry['status_slug'] );
			$is_active   = (
				isset( $status_data['status'] )
				&& Status_Checker::does_status_allow_access( $status_data['status'] )
			);

			// When the plugin is not installed, only register if the user has access and a remote key exists.
			if (
				! $is_installed
				&& (
					! $is_active
					|| $remote_key === ''
				)
			) {
				continue;
			}

			// Prefer the remote key; fall back to the auth-token.php when the plugin is installed.
			$license_key = $remote_key;

			if (
				$license_key === ''
				&& $is_installed
			) {
				$auth_token_key = require $auth_token_file;

				if (
					is_string( $auth_token_key )
					&& $auth_token_key !== ''
				) {
					$license_key = $auth_token_key;
				}
			}

			if ( $license_key === '' ) {
				continue;
			}

			// The 'name' is intentionally omitted; Harbor resolves the human-readable name from the slug.
			$licenses[] = [
				'key'             => $license_key,
				'slug'            => $entry['harbor_slug'],
				'is_active'       => $is_active,
				'product'         => 'learndash',
				'use_for_updates' => true,
				'expires_at'      => $status_data['expiry'] ?? '',
			];
		}

		return $licenses;
	}
}
