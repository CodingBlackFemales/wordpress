<?php
/**
 * Template to show when submitting a resume.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/resume-submit.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'wp-resume-manager-resume-submission' );

$captcha_version = ( class_exists('WP_Job_Manager\WP_Job_Manager_Recaptcha') && get_option( 'resume_manager_enable_recaptcha_resume_submission' ) )
    ? WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->get_recaptcha_version()
    : null;

?>
<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-resume-form" class="job-manager-form" enctype="multipart/form-data" <?php if ( 'v3' === $captcha_version ) echo "onsubmit='return jm_job_submit_click(event)'" ?> >

	<?php do_action( 'submit_resume_form_start' ); ?>

	<?php if ( apply_filters( 'submit_resume_form_show_signin', true ) ) : ?>

		<?php get_job_manager_template( 'account-signin.php', [ 'class' => $class ], 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

	<?php endif; ?>

	<?php if ( resume_manager_user_can_post_resume() ) : ?>

		<!-- Resume Fields -->
		<?php do_action( 'submit_resume_form_resume_fields_start' ); ?>

		<?php foreach ( $resume_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ) . apply_filters( 'submit_resume_form_required_label', $field['required'] ? '' : ' <small>' . esc_html__( '(optional)', 'wp-job-manager-resumes' ) . '</small>', $field ); ?></label>
				<div class="field">
					<?php $class->get_field_template( $key, $field ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( 'submit_resume_form_resume_fields_end' ); ?>

		<p>
			<input type="hidden" name="resume_manager_form" value="<?php echo esc_attr( $form ); ?>" />
			<input type="hidden" name="resume_id" value="<?php echo esc_attr( $resume_id ); ?>" />
			<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
			<input type="submit" name="submit_resume" class="button"
			value="<?php esc_attr_e( $submit_button_text ); ?>" />

		</p>

	<?php else : ?>

		<?php do_action( 'submit_resume_form_disabled' ); ?>

	<?php endif; ?>

	<?php do_action( 'submit_resume_form_end' ); ?>
</form>
