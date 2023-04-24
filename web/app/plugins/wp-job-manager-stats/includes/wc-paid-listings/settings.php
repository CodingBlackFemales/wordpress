<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_WCPL_Settings::get_instance();

/**
 * Stuff
 *
 * @since 2.0.0
 */
class WPJMS_WCPL_Settings {

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

		/* Add Settings */
		add_filter( 'wpjms_settings', array( $this, 'add_require_paid_listing_setting' ) );

		/* Sanitize */
		add_filter( 'sanitize_option_wp_job_manager_stats_require_paid_listing', array( $this, 'sanitize_checkbox' ) );
	}

	/**
	 * Add setting.
	 * Add a setting to require a paid listing.
	 */
	public function add_require_paid_listing_setting( $settings ) {

		$settings[] = array(
			'name'          => 'wp_job_manager_stats_require_paid_listing',
			'type'          => 'checkbox',
			'label'         => __( 'Require Purchase', 'wp-job-manager-stats' ),
			'cb_label'      => __( 'Requires a Listing Package', 'wp-job-manager-stats' ),
			'desc'          => __( 'Listing owners must have purchased a listing package to track statistics. Toggle statistics visibility in individual package settings.', 'wp-job-manager-stats' ),
			'std'          => 0,
		);

		return $settings;
	}

	/**
	 * Utility: Sanitize Checkbox
	 */
	public function sanitize_checkbox( $input ) {
		return $input ? 1 : 0;
	}

}
