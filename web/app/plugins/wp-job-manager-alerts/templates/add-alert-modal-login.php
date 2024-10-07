<?php
/**
 * Job search page modal dialog when login is required.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/alert-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.2.0
 *
 */

use WP_Job_Manager\UI\Notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo Notice::render(
	[
		'title'   => __( 'Add Alert', 'wp-job-manager-alerts' ),
		'message' => __( 'Sign in or create an account to continue.', 'wp-job-manager-alerts' ),
		'buttons' => [
			[
				'url'   => apply_filters( 'job_manager_alerts_login_url', wp_login_url( get_permalink() ) ),
				'label' => __( 'Sign in', 'wp-job-manager-alerts' ),
			],
			[
				'url'   => apply_filters( 'job_manager_alerts_register_url', wp_registration_url() ),
				'label' => __( 'Create Account', 'wp-job-manager-alerts' ),
			],
		],
	]
);

?>
