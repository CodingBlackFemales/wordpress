<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_WCPL_Setup::get_instance();

/**
 * Stuff
 *
 * @since 2.0.0
 */
class WPJMS_WCPL_Setup {

	/**
	 * Returns the instance.
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) { $instance = new self;
		}
		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$active = get_option( 'wp_job_manager_stats_require_paid_listing' );
		if ( $active ) {

			/* Filter loop */
			add_filter( 'wpjms_job_listing_loop_args', array( $this, 'job_listing_loop_args' ) );

			/* Disable Viewing Single Stats */
			add_action( 'wp', array( $this, 'disable_job_stats' ) );

			/* Disable Link to View Single Stats */
			add_filter( 'job_manager_my_job_actions', array( $this, 'my_job_actions' ), 10, 2 );

			/* Do not shoe total stats in job column */
			add_filter( 'wpjms_stats_job_dashboard_column', array( $this, 'wpjms_stats_job_dashboard_column' ), 10, 2 );
		}
	}

	/**
	 * Modify All WPJMS Loop
	 */
	public function job_listing_loop_args( $args ) {
		$args['meta_query']  = array(
			'relation'    => 'AND',
			array(
				'key'     => '_package_id',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_package_id',
				'compare' => 'IN',
				'value'   => $this->products(),
			),
		);
		return $args;
	}

	/**
	 * Disable Job Stats
	 */
	public function disable_job_stats() {
		$stat_page_id = wpjms_stat_page_id();
		$job_id = isset( $_GET['job_id'] ) ? intval( $_GET['job_id'] ) : '';
		if ( $stat_page_id && is_page( $stat_page_id ) && $job_id && ! current_user_can( 'administrator' ) ) {
			$package_id = get_post_meta( $job_id, '_package_id', true );
			if ( ! in_array( $package_id, $this->products() ) ) {
				wp_die( __( 'Stats are disable for this listing', 'wp-job-manager-stats' ) );
			}
		}
	}

	/**
	 * Disabled Link to View Single Stat in Job Dashboard
	 */
	public function my_job_actions( $actions, $job ) {

		/* Set global, so it will only query wc products once in dashboard page. */
		global $wpjms_products;
		if ( ! isset( $wpjms_products ) ) {
			$wpjms_products = $this->products();
		}

		$package_id = get_post_meta( $job->ID, '_package_id', true );
		if ( ! in_array( $package_id, $wpjms_products ) ) {
			unset( $actions['stats'] );
		}
		return $actions;
	}

	/**
	 * Disable total stat in job dashboard column
	 */
	public function wpjms_stats_job_dashboard_column( $stats, $job ) {
		/* Set global, so it will only query wc products once in dashboard page. */
		global $wpjms_products;
		if ( ! isset( $wpjms_products ) ) {
			$wpjms_products = $this->products();
		}

		$package_id = get_post_meta( $job->ID, '_package_id', true );
		if ( ! in_array( $package_id, $wpjms_products ) ) {
			return '&mdash;';
		}
		return $stats;
	}
	/*
	 Utility Functions
	------------------------------------------ */

	/**
	 * Get Product With Show Listing Enable
	 */
	public function products() {
		$posts = get_posts( array(
			'post_type'     => 'product',
			'post_status'   => 'publish',
			'fields'        => 'ids',
			'meta_query'    => array(
				array(
					'key'        => '_job_listing_stats',
					'compare'    => '=',
					'value'      => 'yes',
				),
			),
		) );
		return array_values( $posts );
	}

}

