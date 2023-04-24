<?php
/**
 * Plugin Uninstall
 *
 * @since 1.0.0
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit();
}

/* Only delete if purge data option is set */
if ( 1 == get_option( 'wp_job_manager_stats_purge_data' ) ) :

	global $wpdb;

	/* Delete tables */
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'job_manager_stats' );

	/* Delete shortcode page */
	if ( get_option( 'wp_job_manager_stats_page_id' ) ) {
		wp_trash_post( get_option( 'wp_job_manager_stats_page_id' ) );
	}

	/* Delete Options */
	delete_option( 'wp_job_manager_stats_purge_data' );
	delete_option( 'wp_job_manger_stats_version' );
	delete_option( 'wp_job_manger_stats_installed_pages' );
	delete_option( 'wp_job_manager_stats_page_id' );

endif;
