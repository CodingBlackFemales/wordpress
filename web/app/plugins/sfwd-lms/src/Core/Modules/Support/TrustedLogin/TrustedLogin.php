<?php
/**
 * TrustedLogin support module.
 *
 * @since 4.14.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Support\TrustedLogin;

use StellarWP\Learndash\TrustedLogin\Client;
use StellarWP\Learndash\TrustedLogin\Config;

/**
 * TrustedLogin module class.
 *
 * @since 4.14.0
 */
class TrustedLogin {
	/**
	 * Slug for the TrustedLogin page.
	 *
	 * @since 4.14.0
	 *
	 * @var string
	 */
	public static $page_slug = 'grant-learndash-access';

	/**
	 * Parent slug for the menu of the TrustedLogin page.
	 *
	 * @since 4.14.0
	 *
	 * @var string
	 */
	private const PARENT_SLUG = 'learndash-lms';

	/**
	 * The TrustedLogin API key for the vendor.
	 *
	 * @since 4.14.0
	 *
	 * @var string
	 */
	private const API_KEY = '7e1b0fbfa9aad4fa';

	/**
	 * Slug for vendor.
	 *
	 * Must be unique and shorter than 96 characters.
	 *
	 * @since 4.14.0
	 *
	 * @var string
	 */
	private const NAMESPACE = 'learndash';

	/**
	 * The role to clone when creating a new support user.
	 *
	 * @since 4.14.0
	 *
	 * @var string
	 */
	private const USER_ROLE = 'administrator';

	/**
	 * Registers the TrustedLogin client.
	 *
	 * @since 4.14.0
	 *
	 * @return void
	 */
	public function register(): void {
		$config = [
			'auth'       => [
				'api_key'     => self::API_KEY,
				'license_key' => get_option( LEARNDASH_LICENSE_KEY, '' ),
			],
			'vendor'     => [
				'namespace'   => self::NAMESPACE,
				'title'       => 'LearnDash',
				'logo_url'    => LEARNDASH_LMS_PLUGIN_URL . 'assets/images/learndash.svg',
				'email'       => 'support@learndash.com',
				'support_url' => 'https://www.learndash.com/support/',
				'website'     => 'https://checkout.learndash.com',
			],
			'decay'      => WEEK_IN_SECONDS,
			'menu'       => [
				'slug' => self::PARENT_SLUG,
			],
			'role'       => self::USER_ROLE,
			'clone_role' => false,
		];

		new Client(
			new Config( $config )
		);
	}

	/**
	 * Removes the submenu item.
	 *
	 * @since 4.14.0
	 *
	 * @return void
	 */
	public function remove_submenu_item(): void {
		remove_submenu_page( self::PARENT_SLUG, self::$page_slug );
	}

	/**
	 * Adds scripts to the TrustedLogin page.
	 *
	 * @since 4.14.0
	 *
	 * @return void
	 */
	public function add_scripts(): void {
		$screen = get_current_screen();

		if (
			! isset( $screen->id )
			|| $screen->id !== self::PARENT_SLUG . '_page_' . self::$page_slug
		) {
			return;
		}

		// Add "current" class to the "Help" submenu item.

		wp_add_inline_script(
			'learndash-admin-menu-script',
			"const submenu_item = document.querySelector( '.submenu-ldlms-help' );

			if ( submenu_item ) {
				submenu_item.classList.add( 'current' );
			}"
		);
	}
}
