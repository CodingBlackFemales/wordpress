<?php
/**
 * Template to show when previewing a resume being submitted.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/resume-preview.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 *
 * @var WP_Resume_Manager_Form_Submit_Resume $form Form object performing the action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<form method="post" id="resume_preview" action="<?php echo esc_url( $form->get_action() ); ?>">
	<?php
	/**
	 * Fires at the top of the preview resume form.
	 *
	 * @since 1.18.0
	 */
	do_action( 'preview_resume_form_start' );
	?>
	<div class="job_listing_preview_title">
		<input type="submit" name="continue" id="resume_preview_submit_button" class="button job-manager-button-submit-listing" value="<?php echo esc_attr( apply_filters( 'submit_resume_step_preview_submit_text', __( 'Submit Resume &rarr;', 'wp-job-manager-resumes' ) ) ); ?>" />
		<input type="submit" name="edit_resume" class="button" value="<?php esc_attr_e( '&larr; Edit resume', 'wp-job-manager-resumes' ); ?>" />
		<input type="hidden" name="resume_id" value="<?php echo esc_attr( $form->get_resume_id() ); ?>" />
		<input type="hidden" name="job_id" value="<?php echo esc_attr( $form->get_job_id() ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
		<input type="hidden" name="resume_manager_form" value="<?php echo esc_attr( $form->form_name ); ?>" />

		<h2><?php esc_html_e( 'Preview', 'wp-job-manager-resumes' ); ?></h2>
	</div>
	<div class="resume_preview single-resume">
		<h1><?php the_title(); ?></h1>
		<?php get_job_manager_template_part( 'content-single', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>
	</div>
	<?php
	/**
	 * Fires at the bottom of the preview resume form.
	 *
	 * @since 1.18.0
	 */
	do_action( 'preview_resume_form_end' );
	?>
</form>
