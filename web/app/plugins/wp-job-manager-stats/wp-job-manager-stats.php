<?php
/**
 * Plugin Name: Stats for WP Job Manager
 * Plugin URI: https://astoundify.com/products/wp-job-manager-stats/
 * Description: Capture and display stats of the listings.
 * Version: 2.7.3
 * Author: Astoundify
 * Author URI: http://astoundify.com
 * Text Domain: wp-job-manager-stats
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/*
 Constants
------------------------------------------ */

define( 'WPJMS_VERSION', '2.7.3' );
define( 'WPJMS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPJMS_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/*
 Load
------------------------------------------ */

/* Load on init hook */
add_action( 'plugins_loaded', 'wpjms_init', 9 );

/**
 * Load Plugin
 *
 * @since 2.0.0
 */
function wpjms_init() {

	/* Text domain */
	load_plugin_textdomain( 'wp-job-manager-stats', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/* Bail if WP Job Manager not active */
	if ( ! class_exists( 'WP_Job_Manager' ) ) {
		return;
	}

	/* Functions */
	require_once( WPJMS_PATH . 'includes/functions.php' );

	/* Setup */
	require_once( WPJMS_PATH . 'includes/class-wpjms-setup.php' );

	/* Settings */
	if ( is_admin() ) {
		require_once( WPJMS_PATH . 'includes/class-wpjms-settings-setup.php' );
		require_once( WPJMS_PATH . 'includes/class-wpjms-settings.php' );
	}

	/* Stats */
	require_once( WPJMS_PATH . 'includes/abstracts/abstract-wpjms-stat.php' );
	require_once( WPJMS_PATH . 'includes/class-wpjms-stat-visits.php' );
	require_once( WPJMS_PATH . 'includes/class-wpjms-stat-unique-visits.php' );
	require_once( WPJMS_PATH . 'includes/class-wpjms-stat-apply-button-click.php' );
	if ( class_exists( 'Astoundify_Job_Manager_Contact_Listing' ) ) {
		require_once( WPJMS_PATH . 'includes/class-wpjms-stat-apply-form-submit.php' );
	}

	/* Chart */
	require_once( WPJMS_PATH . 'includes/class-wpjms-chart.php' );

	/* Stats Data */
	require_once( WPJMS_PATH . 'includes/class-wpjms-stats-data.php' );

	/* Dashboard */
	require_once( WPJMS_PATH . 'includes/class-wpjms-dashboard.php' );

	/* Admin Stats */
	if ( is_admin() ) {
		require_once( WPJMS_PATH . 'includes/class-wpjms-stats-admin.php' );
	}

	/* WooCommerce Check */
	if ( class_exists( 'WooCommerce' ) ) {

		// WC Paid Listing.
		if ( function_exists( 'wp_job_manager_wcpl_init' ) ) {
			require_once( WPJMS_PATH . 'includes/wc-paid-listings/wc-paid-listings.php' );
		} elseif ( defined( 'ASTOUNDIFY_WPJMLP_VERSION' ) ) { // Listing Payments.
			require_once( WPJMS_PATH . 'includes/listing-payments/listing-payments.php' );
		}
	}

	/* PolyLang Check */
	if ( defined( 'POLYLANG_VERSION' ) ) {
		require_once( WPJMS_PATH . 'includes/polylang/polylang.php' );
	}
}

/**
 * Updater
 */
function wpjms_updater() {
	require_once( WPJMS_PATH . 'vendor/astoundify/plugin-updater/astoundify-pluginupdater.php' );
	$updater = new Astoundify_PluginUpdater( __FILE__ );

	// ensure custom setting can be used
	new Astoundify_PluginUpdater_Integration_WPJobManager( __FILE__ );
}
add_action( 'admin_init', 'wpjms_updater', 9 );

/*
 Activation and Uninstall
------------------------------------------ */

/* Register activation hook. */
register_activation_hook( __FILE__, 'wpjms_activation' );

/**
 * Runs only when the plugin is activated.
 *
 * @since 1.0.0
 */
function wpjms_activation() {

	/* If version do not match, re-install all */
	if ( WPJMS_VERSION != get_option( 'wp_job_manger_stats_version' ) ) {

		/* Create tables */
		wpjms_create_tables();

		/* Update version number */
		update_option( 'wp_job_manger_stats_version', WPJMS_VERSION );
	}
}

/**
 * Create Table
 */
function wpjms_create_tables() {
	global $wpdb;
	$wpdb->hide_errors();

	/* Vars */
	$table_name = $wpdb->prefix . 'job_manager_stats';
	$charset_collate = $wpdb->get_charset_collate();

	/* SQL */
	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) DEFAULT NULL,
		stat_date date DEFAULT NULL,
		stat_id varchar(25) DEFAULT NULL,
		stat_value varchar(255) DEFAULT NULL,
		PRIMARY KEY (id)
	) {$charset_collate};";

	/* Create table */
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // Load dbDelta()
	dbDelta( $sql );
}
