<?php
/**
 * Handle admin hooks.
 *
 * @class   Admin\Main
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin main class.
 *
 * Entry point for all WP admin-context functionality: menu pages, settings,
 * and admin asset enqueue. All hooks are registered here and delegated to
 * the appropriate sub-class.
 */
final class Main {

	/**
	 * Register all admin hooks.
	 */
	public static function hooks(): void {
		Assets::hooks();
		SettingsPage::hooks();
		ImporterPage::hooks();
		OAuthBridge::hooks();
	}
}
