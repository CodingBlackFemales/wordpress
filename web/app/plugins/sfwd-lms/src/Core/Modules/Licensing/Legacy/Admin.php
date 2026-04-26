<?php
/**
 * Admin class for LearnDash licensing module.
 *
 * @since 4.21.5
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Legacy;

use LearnDash\Hub\Component\API;

/**
 * Admin class for LearnDash licensing module.
 *
 * @since 4.21.5
 */
class Admin {
	/**
	 * Hides the LearnDash license tab menu item if the conditions to hide it are met.
	 *
	 * @since 4.21.5
	 *
	 * @param array<int, array<string, string>> $tab_sets The tab sets to check.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function maybe_hide_license_tab( $tab_sets ): array {
		if ( ! $this->should_hide_license_page() ) {
			return $tab_sets;
		}

		foreach ( $tab_sets as $key => $set ) {
			if (
				isset( $set['id'] )
				&& $set['id'] === 'admin_page_learndash_hub_licensing'
			) {
				unset( $tab_sets[ $key ] );
				return $tab_sets;
			}
		}

		return $tab_sets;
	}

	/**
	 * Hides the LearnDash license page when StellarSites MU plugin is active and the license is valid.
	 *
	 * @since 4.21.5
	 *
	 * @return bool Whether the license page should be hidden.
	 */
	private function should_hide_license_page(): bool {
		// Check if the StellarSites MU plugin is active.
		if ( ! class_exists( '\StellarWP\StellarSites\Plugin' ) ) {
			return false;
		}

		$license_api = new API();

		return $license_api->is_signed_on();
	}
}
