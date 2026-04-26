<?php
/**
 * LearnDash Terms and Privacy Agreement Provider class.
 *
 * @since 4.20.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Registration\Terms_Privacy_Agreement;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use WP_Error;

/**
 * Service provider class for terms and privacy agreement.
 *
 * @since 4.20.2
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.20.2
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_filter(
			'registration_errors',
			$this->container->callback( self::class, 'registration_validation' ),
			10,
			1
		);
		add_filter(
			'learndash_registration_errors',
			$this->container->callback( self::class, 'add_error_messages' ),
			10,
			1
		);
	}

	/**
	 * Flags whether our settings our enabled and we should have any of the provider operations setup.
	 *
	 * @since 4.20.2
	 *
	 * @return bool
	 */
	protected function is_enabled(): bool {
		return Terms_Privacy_Agreement::is_terms_enabled()
			|| Terms_Privacy_Agreement::is_privacy_enabled();
	}

	/**
	 * Add terms and privacy form error messages.
	 *
	 * @since 4.20.2
	 *
	 * @param array<string,string> $error_messages Error messages for the registration form.
	 *
	 * @return mixed
	 */
	public function add_error_messages( $error_messages ) {
		return array_merge(
			$error_messages,
			Terms_Privacy_Agreement::get_error_messages()
		);
	}

	/**
	 * Handles the registration_errors hook, applying our validation if relevant.
	 *
	 * @since 4.20.2
	 *
	 * @param WP_Error $errors The error object, to apply any custom errors to.
	 *
	 * @return WP_Error
	 */
	public function registration_validation( $errors ) {
		return Terms_Privacy_Agreement::validate_post( $errors, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Not processing data here.
	}
}
