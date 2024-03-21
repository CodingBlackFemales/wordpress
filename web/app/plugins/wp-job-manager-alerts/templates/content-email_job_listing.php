<?php
/**
 * Generates a single job item as part of the {jobs} list in job alert e-mails.
 *
 * WARNING: This template should only be used as plaintext e-mail content.
 * User content is not escaped to securely display on the site as HTML content.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if not being rendered in the context of an e-mail.

global $job_manager_doing_email;

if ( empty( $job_manager_doing_email ) ) {
	wp_die( __( 'Invalid template usage: This template can only be used as part of an e-mail.', 'wp-job-manager-alerts' ) );
}

global $post;

$types    = wpjm_get_the_job_types();
$location = get_the_job_location();
$company  = get_the_company_name();

echo "\n";

// Job title
echo wp_specialchars_decode( $post->post_title );

// Job types
if ( $types && count( $types ) > 0 ) {
	$names = wp_list_pluck( $types, 'name' );

	$types_str = implode( ', ', $names );

	echo ' (' . wp_specialchars_decode( $types_str ) . ')';
}

echo "\n";
echo esc_url( get_the_job_permalink() ) . "\n";

// Location and company
if ( $location ) {
	printf( __( 'Location: %s', 'wp-job-manager-alerts' ) . "\n", wp_specialchars_decode( $location ) );
}
if ( $company ) {
	printf( __( 'Company: %s', 'wp-job-manager-alerts' ) . "\n", wp_specialchars_decode( $company ) );
}
