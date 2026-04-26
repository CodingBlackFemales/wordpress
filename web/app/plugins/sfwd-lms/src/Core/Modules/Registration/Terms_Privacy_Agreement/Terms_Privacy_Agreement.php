<?php
/**
 * LearnDash Terms and Privacy Agreement Provider class.
 *
 * @since 4.20.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Registration\Terms_Privacy_Agreement;

use LearnDash_Custom_Label;
use LearnDash_Settings_Section_Terms_Pages;
use WP_Error;

/**
 * Service provider class for terms and privacy agreement.
 *
 * @since 4.20.2
 */
class Terms_Privacy_Agreement {
	/**
	 * Fetches the admin settings for the Privacy and Terms checkboxes.
	 *
	 * @since 4.20.2
	 *
	 * @return array{
	 *     terms_enabled: string,
	 *     terms_page: string,
	 *     privacy_enabled: string,
	 *     privacy_page: string
	 * }
	 */
	public static function get_settings(): array {
		return LearnDash_Settings_Section_Terms_Pages::get_section_settings_all(); // @phpstan-ignore-line -- Type definitions are not aligned.
	}

	/**
	 * Add terms and privacy form error messages.
	 *
	 * @since 4.20.2
	 *
	 * @return array{ requires_terms: string, requires_privacy: string }
	 */
	public static function get_error_messages(): array {
		return [
			'requires_terms'   => sprintf(
				/* translators: placeholder: %1$s = Terms of Service label */
				__( 'Registration requires accepting the %1$s.', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'terms_of_service' ),
			),
			'requires_privacy' => sprintf(
				/* translators: placeholder: %1$s = Privacy Policy label */
				__( 'Registration requires accepting the %1$s.', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'privacy_policy' ),
			),
		];
	}

	/**
	 * Check our admin settings if the Terms checkbox fields are setup.
	 *
	 * @since 4.20.2
	 *
	 * @return bool
	 */
	public static function is_terms_enabled(): bool {
		/**
		 * Terms settings var.
		 *
		 * @var array{ terms_enabled: string, terms_page: string } $terms_settings The options for terms checkbox.
		 */
		$terms_settings = get_option( 'learndash_settings_terms_pages' );

		if ( empty( $terms_settings['terms_enabled'] ) ) {
			return false;
		}

		return $terms_settings['terms_enabled'] === 'yes'
			&& ! empty( $terms_settings['terms_page'] );
	}

	/**
	 * Check our admin settings if the Terms checkbox fields are setup.
	 *
	 * @since 4.20.2
	 *
	 * @return bool
	 */
	public static function is_privacy_enabled(): bool {
		/**
		 * Privacy settings var.
		 *
		 * @var array{ privacy_enabled: string, privacy_page: string } $terms_settings The options for terms checkbox.
		 */
		$terms_settings = get_option( 'learndash_settings_terms_pages' );

		if ( empty( $terms_settings['privacy_enabled'] ) ) {
			return false;
		}

		return $terms_settings['privacy_enabled'] === 'yes'
			&& ! empty( $terms_settings['privacy_page'] );
	}

	/**
	 * Handles the registration_errors hook, applying our validation if relevant.
	 *
	 * @since 4.20.2
	 *
	 * @param WP_Error            $errors    The error object, to apply any custom errors to.
	 * @param array<string,mixed> $post_data The post data to validate against.
	 *
	 * @return WP_Error
	 */
	public static function validate_post( $errors, array $post_data ) {
		$error_messages = self::get_error_messages();

		if (
			self::is_terms_enabled()
			&& empty( $post_data['terms_checkbox'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Registration form handles nonce validation.
		) {
			$errors->add(
				'requires_terms',
				$error_messages['requires_terms'],
			);
		}

		if (
			self::is_privacy_enabled()
			&& empty( $post_data['privacy_checkbox'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Registration form handles nonce validation.
		) {
			$errors->add(
				'requires_privacy',
				$error_messages['requires_privacy'],
			);
		}

		return $errors;
	}
}
