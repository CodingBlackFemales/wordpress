<?php
/**
 * Email content for showing resume details.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/emails/plain/email-resume-details.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 *
 * @var array                $fields         Array of the resume details.
 * @var WP_Post              $resume         The resume listing to show details for.
 * @var WP_Job_Manager_Email $email          Email object for the notification.
 * @var bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @var bool                 $plain_text     True if the email is being sent as plain text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo PHP_EOL . PHP_EOL;

if ( empty( $fields ) ) {
	return;
}

foreach ( $fields as $field ) {
	$multiple_lines = false !== strpos( $field['value'], PHP_EOL );
	if ( $multiple_lines ) {
		$section_heading = '---- ' . $field['label'] . ' ----';
		echo PHP_EOL . esc_html( $section_heading ) . PHP_EOL;
	} else {
		echo esc_html( $field['label'] ) . ': ';
	}

	echo esc_html( $field['value'] );
	if ( ! empty( $field['url'] ) ) {
		echo ' (' . esc_url( $field['url'] ) . ')';
	}
	echo PHP_EOL;

	if ( $multiple_lines ) {
		echo PHP_EOL;
	}
}
