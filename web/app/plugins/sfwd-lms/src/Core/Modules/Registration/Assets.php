<?php
/**
 * LearnDash Registration settings class.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Registration;

use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * Service provider class for registration.
 *
 * @since 4.16.0
 */
class Assets {
	/**
	 * Registers scripts that can be enqueued.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		$breakpoint_asset = Base_Assets::instance()->get( 'learndash-breakpoints' );

		if ( $breakpoint_asset instanceof Asset ) {
			$breakpoint_asset->add_to_group( 'learndash-registration' );
		}

		Asset::add( 'learndash-ld30-modern', 'css/modern.css' )
			->add_to_group( 'learndash-registration' )
			->set_path( 'themes/ld30/assets' )
			->set_dependencies( 'dashicons' )
			->register();

		Asset::add( 'learndash-validation', 'js/modules/forms/validation.js' )
			->add_to_group( 'learndash-registration' )
			->set_dependencies( 'learndash-main' )
			->add_localize_script(
				'learndash.forms.validation',
				[
					'i18n' => [
						'requiredErrorMessage'         => esc_html__( 'This field is required.', 'learndash' ),
						'passwordMismatchErrorMessage' => esc_html__( 'Make sure this matches your password.', 'learndash' ),
					],
				]
			)
			->register();

		Asset::add( 'learndash-password', 'js/modules/forms/password.js' )
			->add_to_group( 'learndash-registration' )
			->set_dependencies( 'learndash-main' )
			->add_localize_script(
				'learndash.forms.password',
				[
					'i18n' => [
						'hiddenButtonLabel' => esc_html__( 'The password is not visible.', 'learndash' ),
						'hiddenButtonText'  => esc_html__( 'Show', 'learndash' ),
						'shownButtonLabel'  => esc_html__( 'The password is visible.', 'learndash' ),
						'shownButtonText'   => esc_html__( 'Hide', 'learndash' ),
					],
				]
			)
			->register();

		Asset::add( 'learndash-svgradio', 'js/modules/forms/radio.js' )
			->add_to_group( 'learndash-registration' )
			->set_dependencies( 'learndash-main' )
			->register();

		Asset::add( 'learndash-registration-checkout', 'js/modules/registration/checkout.js' )
			->add_to_group( 'learndash-registration' )
			->set_dependencies( 'learndash-main', 'learndash-svgradio' )
			->register();
	}
}
