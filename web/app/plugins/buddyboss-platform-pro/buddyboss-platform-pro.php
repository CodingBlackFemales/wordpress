<?php
/**
 * BuddyBoss Platform Pro
 *
 * @package BuddyBossPro
 *
 * Plugin Name: BuddyBoss Platform Pro
 * Plugin URI:  https://buddyboss.com/
 * Description: Adds premium features to BuddyBoss Platform.
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     2.13.1
 * Text Domain: buddyboss-pro
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 * Requires Plugins: buddyboss-platform
 */

/**
 * This file should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define('PRO_EDITION', 'buddyboss-platform-pro');

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * Notice for platform plugin.
 */
function bb_platform_pro_install_bb_platform_notice() {
	echo '<div class="error fade"><p>';
	echo sprintf(
		'<strong>%s</strong> %s <a href="https://buddyboss.com/platform/" target="_blank">%s</a> %s',
		esc_html__( 'BuddyBoss Platform Pro', 'buddyboss-pro' ),
		esc_html__( 'requires the BuddyBoss Platform plugin to work. Please', 'buddyboss-pro' ),
		esc_html__( 'install BuddyBoss Platform', 'buddyboss-pro' ),
		esc_html__( 'first.', 'buddyboss-pro' )
	);
	echo '</p></div>';
}

/**
 * Notice for platform update.
 */
function bb_platform_pro_update_bb_platform_notice() {
	echo '<div class="error fade"><p>';
	echo sprintf(
		'<strong>%s</strong> %s',
		esc_html__( 'BuddyBoss Platform Pro', 'buddyboss-pro' ),
		esc_html__( 'requires BuddyBoss Platform plugin version 1.3.5 or higher to work. Please update BuddyBoss Platform.', 'buddyboss-pro' )
	);
	echo '</p></div>';
}

/**
 * Initialization of the plugin.
 */
function bb_platform_pro_init() {
	if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
		add_action( 'admin_notices', 'bb_platform_pro_install_bb_platform_notice' );
		add_action( 'network_admin_notices', 'bb_platform_pro_install_bb_platform_notice' );

		return;
	} elseif ( version_compare( BP_PLATFORM_VERSION, '1.3.4', '<' ) ) {
		add_action( 'admin_notices', 'bb_platform_pro_update_bb_platform_notice' );
		add_action( 'network_admin_notices', 'bb_platform_pro_update_bb_platform_notice' );

		return;
	} elseif ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {

		// load main class file.
		require_once 'class-bb-platform-pro.php';

		bb_platform_pro();

		// Register with DRM system (only if Platform's DRM is available).
		bb_platform_pro_register_with_drm();
	}
}
add_action( 'plugins_loaded', 'bb_platform_pro_init', 9 );

/**
 * Register Platform Pro with DRM system.
 *
 * @since 2.11.0
 */
function bb_platform_pro_register_with_drm() {
	// Check if Platform's DRM Registry is available.
	if ( ! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry' ) ) {
		return;
	}

	// Register with DRM system.
	\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::register_addon(
		'buddyboss-platform-pro',
		'BuddyBoss Platform Pro',
		array(
			'version' => defined( 'BB_PLATFORM_PRO_PLUGIN_FILE' ) ? bb_platform_pro()->version : '2.13.1',
			'file'    => defined( 'BB_PLATFORM_PRO_PLUGIN_FILE' ) ? BB_PLATFORM_PRO_PLUGIN_FILE : __FILE__,
		)
	);
}

/**
 * Platform Pro activation hook.
 *
 * @since 2.5.20
 *
 * @return void
 */
function bb_platform_pro_activation() {

	update_option( '_bb_schedule_posts_cron_setup', true );

	update_option( 'bb_polls_table_create_on_activation', true );

	/**
	 * Platform Pro activation hook.
	 *
	 * @since 2.5.20
	 */
	do_action( 'bb_platform_pro_activation' );
}

add_action( 'activate_' . plugin_basename( __FILE__ ), 'bb_platform_pro_activation' );
