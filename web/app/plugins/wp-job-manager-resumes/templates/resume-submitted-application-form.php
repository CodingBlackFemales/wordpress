<?php
/**
 * When users intend to apply for a job with a resume, this shows the application form
 * at the end of the resume submission process.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/resume-submitted-application-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 *
 * @var int     $job_id When initiating resume submission, this is the job that the user intends to apply for.
 * @var WP_Post $resume Resume post object that was just submitted.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Hide this application form when the resume needs moderation. There is no resume to apply with.
if ( 'publish' !== $resume->post_status ) {
	return;
}
?>
<h3 class="applying_for">
	<?php
	// translators: Placeholder %s is a link to the job listing with the title as the text.
	echo wp_kses_post( sprintf( __( 'Submit your application to the job "%s".', 'wp-job-manager-resumes' ), '<a href="' . get_permalink( $job_id ) . '">' . get_the_title( $job_id ) . '</a>' ) );
	?>
</h3>
<?php
echo do_shortcode( '[job_apply id="' . absint( $job_id ) . '"]' );
