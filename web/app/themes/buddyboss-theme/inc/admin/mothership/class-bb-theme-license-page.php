<?php

declare(strict_types=1);

namespace BuddyBossTheme\Admin\Mothership;

use BuddyBossTheme\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossTheme\GroundLevel\Container\Contracts\StaticContainerAwareness;

/**
 * This class registers and renders an admin page that displays a form for activating/deactivating the license.
 */
class BB_Theme_License_Page implements StaticContainerAwareness {

	use HasStaticContainer;

	/**
	 * The capability required to view the page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * The page slug.
	 */
	public const SLUG = 'buddyboss-theme-license';

	/**
	 * Retrieves the page title.
	 *
	 * @return string
	 */
	public static function pageTitle(): string {
		return esc_html__( 'BuddyBoss Theme Activation', 'buddyboss-theme' );
	}

	/**
	 * Registers the page.
	 *
	 * @return mixed The resulting page's hook suffix or false if the user does not have the capability set in the constant self::CAPABILITY.
	 */
	public static function register() {

		$parent_slug = function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ? 'buddyboss-platform' : 'buddyboss-settings';

		return add_submenu_page(
			$parent_slug,
			self::pageTitle(),
			esc_html__( 'License Activation', 'buddyboss-theme' ),
			self::CAPABILITY,
			self::SLUG,
			array(
				self::class,
				'render',
			),
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render(): void {
		wp_enqueue_style( 'bb-theme-mothership-admin', get_template_directory_uri() . '/inc/admin/assets/css/mothership.css', array(), '2.14.0' );

		include_once __DIR__ . '/views/admin.php';
	}
}
