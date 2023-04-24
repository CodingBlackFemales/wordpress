<?php
/**
 * Stats: Apply Form Submit
 *
 * @since 2.0.0
 */

/* Load Class */
WPJMS_Stat_Apply_Form_Submit::get_instance();

/**
 * Stat: Apply Form Submit
 *
 * @since 2.0.0
 */
class WPJMS_Stat_Apply_Form_Submit extends WPJMS_Stat {

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
		$this->stat_id      = 'apply_form_submit';
		$this->stat_label   = __( 'Contact Submissions', 'wp-job-manager-stats' );
		$this->is_ajax      = true;
		$this->check_cookie = false;
		$this->log_author   = false;

		parent::__construct();
	}
}
