<?php

/* Load Class */
WPJMS_Stat_Unique_Visits::get_instance();

/**
 * Stat: Unique Visits
 */
class WPJMS_Stat_Unique_Visits extends WPJMS_Stat {

	/**
	 * Returns the instance.
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
		}
		return $instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->post_types   = array( 'job_listing' );
		$this->stat_id      = 'unique_visits';
		$this->stat_label   = __( 'Unique Visits', 'wp-job-manager-stats' );
		$this->cookie_name  = 'listings_visited';
		$this->is_ajax      = true;
		$this->check_cookie = true;
		$this->log_author   = false;

		parent::__construct();
	}
}
