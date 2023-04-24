<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Setup::get_instance();

/**
 * Setup
 *
 * @since 2.0.0
 */
class WPJMS_Setup {

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

		/*
		 Job Manager Columns
		------------------------------------------ */

		/* Add Action */
		add_filter( 'job_manager_my_job_actions', array( $this, 'my_job_actions' ), 10, 2 );
		add_action( 'job_manager_job_dashboard_do_action_stats', array( $this, 'my_job_actions_stats' ) );

		/* Admin Post Column */
		add_action( 'job_manager_job_dashboard_columns', array( $this, 'job_manager_job_dashboard_columns' ) );
		add_action( 'job_manager_job_dashboard_column_total_views', array( $this, 'job_manager_job_dashboard_column_total_views' ) );

		/*
		 Scripts
		------------------------------------------ */

		/* Register Scripts */
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/*
	 Job Manager Columns
	------------------------------------------ */

	/**
	 * Add link to stats page on job listings.
	 */
	public function my_job_actions( $actions, $job ) {
		$actions['stats'] = array(
			'label' => __( 'View stats', 'wp-job-manager-stats' ),
			'nonce' => true,
		);
		return $actions;
	}

	/**
	 * Redirect when click
	 */
	public function my_job_actions_stats() {
		wp_redirect( esc_url_raw( wpjms_job_stat_url( $_GET['job_id'] ) ) );
		exit;
	}

	/**
	 * Add Column
	 */
	public function job_manager_job_dashboard_columns( $columns ) {
		$columns['total_views'] = __( 'Visits', 'wp-job-manager-stats' );
		return $columns;
	}

	/**
	 * Column Content
	 */
	public function job_manager_job_dashboard_column_total_views( $job ) {
		$post_id = $job->ID;

		/*  Get Stats Data */
		$stats_data = new WPJMS_Stats_Data( array(
			'post_ids' => array( $post_id ),
			'stat_ids' => 'visits',
		) );
		$stat_total = $stats_data->get_all_stats();

		$visits = isset( $stat_total['visits'] ) ? $stat_total['visits'] : 0;

		echo apply_filters( 'wpjms_stats_job_dashboard_column', $visits, $job );
	}
	/*
	 Scripts
	------------------------------------------ */

	/**
	 * Scripts
	 */
	public function scripts() {

		/* jQuery UI CSS */
		wp_register_style( 'jquery-ui', WPJMS_URL . 'assets/jquery-ui/jquery-ui.css', array(), '1.11.4' );

		/* Chart JS */
		wp_register_script( 'chart-js', WPJMS_URL . 'assets/chart-js/chart.min.js', array(), '2.2.1', true );

		/* Moment JS */
		wp_register_script( 'moment-js', WPJMS_URL . 'assets/moment-js/moment.min.js', array(), '2.14.1', true );

		/* JQuery Date Range Picker */
		wp_register_style( 'date-range-picker', WPJMS_URL . 'assets/date-range-picker/daterangepicker.min.css' , array(), '0.6.0' );
		wp_register_script( 'date-range-picker', WPJMS_URL . 'assets/date-range-picker/jquery.daterangepicker.min.js', array( 'jquery', 'moment-js' ), '0.6.0', true );
		$date_range_picker_lang = array(
			'selected'         => __( 'Selected:', 'wp-job-manager-stats' ),
			'day'              => __( 'Day', 'wp-job-manager-stats' ),
			'days'             => __( 'Days', 'wp-job-manager-stats' ),
			'apply'            => __( 'Close', 'wp-job-manager-stats' ),
			'week-1'           => __( 'mo', 'wp-job-manager-stats' ),
			'week-2'           => __( 'tu', 'wp-job-manager-stats' ),
			'week-3'           => __( 'we', 'wp-job-manager-stats' ),
			'week-4'           => __( 'th', 'wp-job-manager-stats' ),
			'week-5'           => __( 'fr', 'wp-job-manager-stats' ),
			'week-6'           => __( 'sa', 'wp-job-manager-stats' ),
			'week-7'           => __( 'su', 'wp-job-manager-stats' ),
			'week-number'      => __( 'W', 'wp-job-manager-stats' ),
			'month-name'       => array(
				__( 'january', 'wp-job-manager-stats' ),
				__( 'february', 'wp-job-manager-stats' ),
				__( 'march', 'wp-job-manager-stats' ),
				__( 'april', 'wp-job-manager-stats' ),
				__( 'may', 'wp-job-manager-stats' ),
				__( 'june', 'wp-job-manager-stats' ),
				__( 'july', 'wp-job-manager-stats' ),
				__( 'august', 'wp-job-manager-stats' ),
				__( 'september', 'wp-job-manager-stats' ),
				__( 'october', 'wp-job-manager-stats' ),
				__( 'november', 'wp-job-manager-stats' ),
				__( 'december', 'wp-job-manager-stats' ),
			),
			'shortcuts'        => __( 'Shortcuts', 'wp-job-manager-stats' ),
			'custom-values'    => __( 'Custom Values', 'wp-job-manager-stats' ),
			'past'             => __( 'Past', 'wp-job-manager-stats' ),
			'following'        => __( 'Following', 'wp-job-manager-stats' ),
			'previous'         => __( 'Previous', 'wp-job-manager-stats' ),
			'prev-week'        => __( 'Week', 'wp-job-manager-stats' ),
			'prev-month'       => __( 'Month', 'wp-job-manager-stats' ),
			'prev-year'        => __( 'Year', 'wp-job-manager-stats' ),
			'next'             => __( 'Next', 'wp-job-manager-stats' ),
			'next-week'        => __( 'Week', 'wp-job-manager-stats' ),
			'next-month'       => __( 'Month', 'wp-job-manager-stats' ),
			'next-year'        => __( 'Year', 'wp-job-manager-stats' ),
			'less-than'        => __( 'Date range should not be more than %d days', 'wp-job-manager-stats' ),
			'more-than'        => __( 'Date range should not be less than %d days', 'wp-job-manager-stats' ),
			'default-more'     => __( 'Please select a date range longer than %d days', 'wp-job-manager-stats' ),
			'default-single'   => __( 'Please select a date', 'wp-job-manager-stats' ),
			'default-less'     => __( 'Please select a date range less than %d days', 'wp-job-manager-stats' ),
			'default-range'    => __( 'Please select a date range between %1$d and %2$d days', 'wp-job-manager-stats' ),
			'default-default'  => __( 'Please select a date range', 'wp-job-manager-stats' ),
			'time'             => __( 'Time', 'wp-job-manager-stats' ),
			'hour'             => __( 'Hour', 'wp-job-manager-stats' ),
			'minute'           => __( 'Minute', 'wp-job-manager-stats' ),
		);
		wp_localize_script( 'date-range-picker', 'drp_lang', $date_range_picker_lang );

		/* Jquery Block UI */
		wp_register_script( 'blockUI', WPJMS_URL . 'assets/blockUI/jquery.blockUI.min.js', array( 'jquery' ), '2.70.0-2014.11.23', true );
	}

}
