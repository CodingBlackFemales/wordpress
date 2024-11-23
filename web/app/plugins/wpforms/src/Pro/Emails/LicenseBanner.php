<?php

namespace WPForms\Pro\Emails;

use WPForms\Emails\Templates\Summary;

/**
 * Class LicenseBanner.
 *
 * @since 1.8.8
 */
class LicenseBanner {

	/**
	 * Number of days before the license expires to show the banner.
	 *
	 * Note that the banner will be shown if the license expires within the next X days,
	 * and the user has canceled the subscription, meaning the license will not be renewed automatically.
	 *
	 * @since 1.8.8
	 */
	const EXPIRE_SOON_DAYS = 7;

	/**
	 * Initialize class.
	 *
	 * @since 1.8.8
	 */
	public function init() {

		// Leave early if the license key is not set.
		if ( ! $this->has_license() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.8
	 */
	private function hooks() {

		add_filter( 'wpforms_emails_summaries_template', [ $this, 'add_license_banner_to_header_args' ] );
	}

	/**
	 * Adds license banner to header arguments based on license status.
	 *
	 * @since 1.8.8
	 *
	 * @param Summary $template The template being used.
	 *
	 * @return Summary
	 */
	public function add_license_banner_to_header_args( $template ) {

		// Get the current license status.
		$license_status = $this->get_license_status();

		// Leave early if the license status is empty.
		if ( empty( $license_status ) ) {
			return $template;
		}

		// Retrieve license banner arguments based on license status.
		$license_banner_args = $this->generate_license_banner_args( $license_status );

		// Add license banner to header arguments.
		$template->set_args(
			[
				'header' => [
					'license_banner' => $license_banner_args,
				],
			]
		);

		// Return modified header arguments.
		return $template;
	}

	/**
	 * Checks if the license exists.
	 *
	 * @since 1.8.8
	 *
	 * @return bool
	 */
	private function has_license(): bool {

		return ! empty( wpforms_get_license_key() );
	}

	/**
	 * Retrieves the license status.
	 * The status can be one of the following: invalid, expired, expire_soon, or an empty string.
	 *
	 * @since 1.8.8
	 *
	 * @return string
	 */
	private function get_license_status(): string {

		// Retrieve the license data from options.
		$license = (array) get_option( 'wpforms_license', [] );

		// Check for expired license.
		if ( ! empty( $license['is_expired'] ) ) {
			return 'expired';
		}

		// Check for disabled or invalid license.
		if ( ! empty( $license['is_disabled'] ) || ! empty( $license['is_invalid'] ) ) {
			return 'disabled';
		}

		// Check if the subscription status is active or empty.
		// There could be instances where a user can have an active license but the subscription status is empty.
		if ( empty( $license['sub_status'] ) || $license['sub_status'] === 'active' ) {
			return '';
		}

		// Check for soon-to-expire license within X days.
		// Note the `expires` value normally returns timestamp in string format;
		// There could be a case when it returns "lifetime" string for licenses with no expiration date.
		if ( isset( $license['expires'] ) && is_numeric( $license['expires'] ) ) {
			$expires_timestamp = (int) $license['expires'];

			// Valid timestamp, proceed with comparison.
			if ( $expires_timestamp < strtotime( '+' . self::EXPIRE_SOON_DAYS . ' days' ) ) {
				return 'expire_soon';
			}
		}

		return '';
	}

	/**
	 * Retrieves the license banner arguments based on the provided status.
	 *
	 * @since 1.8.8
	 *
	 * @param string $status The status of the license.
	 *
	 * @return array
	 */
	private function generate_license_banner_args( string $status ): array {

		$license_help_url  = wpforms_utm_link( 'https://wpforms.com/docs/why-you-should-always-use-the-latest-version-of-wpforms/', 'Latest Version Doc', 'Use Latest WPForms Version' );
		$license_help_html = sprintf(
			wp_kses( /* translators: %1$s - WPForms.com license help URL. */
				__( 'Have questions? <a href="%1$s" target="_blank" rel="noopener noreferrer">Learn why you should always use the latest version of WPForms</a>.', 'wpforms' ),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			$license_help_url
		);

		// Switch statement to handle different license statuses.
		switch ( $status ) {
			case 'invalid':
			case 'disabled':
				$message = [
					'status'   => 'disabled',
					'title'    => __( 'Heads Up! Your License is Invalid', 'wpforms' ),
					'content'  => [
						__( 'Your key either no longer exists or the user associated with the key has been deleted. Please use a different key to continue receiving automatic updates.', 'wpforms' ),
						$license_help_html,
					],
					'help_url' => $license_help_url,
					'cta'      => [
						'class' => 'button button-red',
						'text'  => __( 'Purchase a New License', 'wpforms' ),
						'url'   => wpforms_utm_link( 'https://wpforms.com/pricing/', 'Invalid Banner', 'Renew Your License Now' ),
					],
				];
				break;

			case 'expired':
				$message = [
					'status'   => 'expired',
					'title'    => __( 'Heads Up! Your License Has Expired', 'wpforms' ),
					'content'  => [
						__( 'Without an active license, you lose access to plugin updates and new features. Renew your license today so you don’t miss out!', 'wpforms' ),
						$license_help_html,
					],
					'help_url' => $license_help_url,
					'cta'      => [
						'class' => 'button button-red',
						'text'  => __( 'Renew Your License Now', 'wpforms' ),
						'url'   => wpforms_utm_link( 'https://wpforms.com/account/licenses/', 'Expired Banner', 'Renew Your License Now' ),
					],
				];
				break;

			case 'expire_soon':
				$message = [
					'status'   => 'expire_soon',
					'title'    => __( 'Heads Up! Your License Will Expire Soon', 'wpforms' ),
					'content'  => [
						__( 'Without an active license, you will lose access to plugin updates and new features. Enable automatic renewal today so you don’t miss out!', 'wpforms' ),
						$license_help_html,
					],
					'help_url' => $license_help_url,
					'cta'      => [
						'class' => 'button button-orange',
						'text'  => __( 'Turn on Automatic Renewal', 'wpforms' ),
						'url'   => wpforms_utm_link( 'https://wpforms.com/account/billing/', 'Expiring Soon Banner', 'Turn On Automatic Renewal' ),
					],
				];
				break;

			default:
				// Set default message for other cases.
				$message = [];
				break;
		}

		// Return the banner arguments.
		return $message;
	}
}
