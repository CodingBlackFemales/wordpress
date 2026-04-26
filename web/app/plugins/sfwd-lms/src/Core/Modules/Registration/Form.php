<?php
/**
 * LearnDash Registration Form class.
 *
 * @since 4.16.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Registration;

use LearnDash\Core\Utilities\Cast;
use LearnDash_Theme_Register_LD30;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Service provider class for the registration form.
 *
 * @since 4.16.2
 */
class Form {
	/**
	 * Ensures that the chosen password is set when using the modern registration form.
	 * This adjustment is necessary as the modern registration form doesn't have a "Confirm Password" field.
	 * If not set, then their set password doesn't get applied on registration.
	 * {@see learndash_register_user_success()}
	 *
	 * @since 4.16.2
	 *
	 * @return void
	 */
	public function set_confirm_password(): void {
		if (
			! wp_verify_nonce(
				Cast::to_string(
					SuperGlobals::get_post_var( 'learndash-registration-form' )
				),
				'learndash-registration-form'
			)
			|| learndash_registration_variation() === LearnDash_Theme_Register_LD30::$variation_classic
			|| empty( $_POST['password'] )
			|| ! empty( $_POST['confirm_password'] )
		) {
			return;
		}

		$_POST['confirm_password'] = $_POST['password']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitizing passwords can cause issues. wp_insert_user() will do it for us regardless.
	}
}
