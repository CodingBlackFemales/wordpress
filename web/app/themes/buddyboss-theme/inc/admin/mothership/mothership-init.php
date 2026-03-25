<?php
/**
 * BuddyBoss Theme Mothership Initialization
 *
 * This file should be included from the main BuddyBoss Theme file to initialize
 * the license activation and add-ons functionality.
 *
 * @package BuddyBossTheme
 * @since 2.14.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize BuddyBoss Theme Mothership functionality.
 */
function buddyboss_theme_init_mothership() {
	if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
		return;
	}

	// Include BuddyBoss Theme-specific files.
	require_once __DIR__ . '/class-bb-theme-connector.php';
	require_once __DIR__ . '/class-bb-theme-license-manager.php';
	require_once __DIR__ . '/class-bb-theme-license-page.php';

	if ( ! class_exists( 'BuddyBossTheme\Admin\Mothership\BB_Theme_Mothership_Loader' ) ) {
		// Include the main loader class.
		require_once __DIR__ . '/class-bb-theme-mothership-loader.php';
	}

	// Initialize the mothership functionality.
	new BuddyBossTheme\Admin\Mothership\BB_Theme_Mothership_Loader();
}

// Initialize immediately since we're already in admin context when this file is included.
buddyboss_theme_init_mothership();
