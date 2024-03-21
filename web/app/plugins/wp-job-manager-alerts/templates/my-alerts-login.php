<?php
/**
 * Lists job listing alerts content if user is not logged in.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/my-alerts-login.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 */

use WP_Job_Manager\Guest_Session;
use WP_Job_Manager\UI\Notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( Guest_Session::current_guest_has_account() ) {
	echo Notice::hint( [
		'message' => __( 'Sign in to manage your alerts.', 'wp-job-manager-alerts' ),
		'buttons' => [
			[
				'url'   => apply_filters( 'job_manager_alerts_login_url', wp_login_url( get_permalink() ) ),
				'label' => __( 'Sign in', 'wp-job-manager-alerts' ),
				'class' => [],
			],
		],
	] );
} else {
	echo Notice::dialog( [
		'message' => __( 'Sign in or create an account to manage your alerts.', 'wp-job-manager-alerts' ),
		'buttons' => [
			[
				'url'   => apply_filters( 'job_manager_alerts_login_url', wp_login_url( get_permalink() ) ),
				'label' => __( 'Sign in', 'wp-job-manager-alerts' ),
				'class' => [],
			],
			[
				'url'   => apply_filters( 'job_manager_alerts_register_url', wp_registration_url() ),
				'label' => __( 'Create Account', 'wp-job-manager-alerts' ),
				'class' => [],
			],
		],
	] );
}

?>
