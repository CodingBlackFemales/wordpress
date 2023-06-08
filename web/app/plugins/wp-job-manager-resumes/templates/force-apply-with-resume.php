<?php
/**
 * Message to display when forcing a user to apply with a submitted resume.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/force-apply-with-resume.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
?>

<form class="apply_with_resume" method="post" action="<?php echo esc_url( get_permalink( get_option( 'resume_manager_submit_resume_form_page_id' ) ) ); ?>">
	<p><?php _e( 'Before applying for this position you need to submit your <strong>online resume</strong>. Click the button below to continue.', 'wp-job-manager-resumes' ); ?></p>
	<p>
		<input type="submit" name="wp_job_manager_resumes_apply_with_resume_create" value="<?php esc_attr_e( 'Submit Resume', 'wp-job-manager-resumes' ); ?>" />
		<input type="hidden" name="job_id" value="<?php echo esc_attr( absint( $post->ID ) ); ?>" />
	</p>
</form>
