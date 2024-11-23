<?php

namespace WPForms\Pro\Integrations\AI;

/**
 * AI features related helper methods for Pro.
 *
 * @since 1.9.2
 */
class Helpers {

	/**
	 * Determine whether a license key is active.
	 *
	 * @since 1.9.2
	 *
	 * @return bool
	 */
	public static function is_license_active(): bool {

		$license = (array) get_option( 'wpforms_license', [] );

		return ! empty( wpforms_get_license_key() ) &&
			empty( $license['is_expired'] ) &&
			empty( $license['is_disabled'] ) &&
			empty( $license['is_invalid'] );
	}
}
