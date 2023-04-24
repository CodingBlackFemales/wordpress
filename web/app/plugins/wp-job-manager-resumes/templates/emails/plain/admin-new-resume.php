<?php
/**
 * Email content when notifying admin of a new resume.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/emails/plain/admin-new-resume.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 *
 * @var WP_Job_Manager_Email $email          Email object for the notification.
 * @var bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @var bool                 $plain_text     True if the email is being sent as plain text.
 * @var array                $args           Arguments used to generate the email notification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resume post object.
 *
 * @var WP_Post $resume
 */
$resume = $args['resume'];

// translators: %1$s is the URL for the site; %2$s is the name of the site.
echo esc_html( sprintf( __( 'A new resume has been submitted to %1$s (%2$s).', 'wp-job-manager-resumes' ), get_bloginfo( 'name' ), home_url() ) );

switch ( $resume->post_status ) {
	case 'publish':
		echo ' ' . esc_html__( 'It has been published and is now available to the public.', 'wp-job-manager-resumes' );
		break;
	case 'pending':
		// translators: Placeholder is URL for WP admin.
		echo ' ' . esc_html( sprintf( __( 'It is awaiting approval by an administrator in WordPress admin (%s).', 'wp-job-manager-resumes' ), esc_url( admin_url( 'edit.php?post_type=resume' ) ) ) );
		break;
}

/**
 * Show details about the resume listing.
 *
 * @param WP_Post              $resume            The resume listing to show details for.
 * @param WP_Job_Manager_Email $email          Email object for the notification.
 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @param bool                 $plain_text     True if the email is being sent as plain text.
 */
do_action( 'resume_manager_email_resume_details', $resume, $email, true, $plain_text );
