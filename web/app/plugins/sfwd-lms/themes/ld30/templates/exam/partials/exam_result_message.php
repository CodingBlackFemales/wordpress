<?php
/**
 * LearnDash LD30 Displays an Exam Result Message.
 *
 * Available Variables:
 * $learndash_exam_model : (object) LDLMS_Model_Exam instance.
 *
 * @since 4.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! isset( $learndash_exam_model ) ) || ( ! is_a( $learndash_exam_model, 'LDLMS_Model_Exam' ) ) ) {
	return;
}

?><div class="ld-exam-result-message">
<?php
if ( true === $learndash_exam_model->is_graded ) {
	$learndash_exam_result_message = $learndash_exam_model->get_result_message();
	$learndash_exam_result_message = trim( $learndash_exam_result_message );
	if ( ! empty( $learndash_exam_result_message ) ) {
		echo wp_kses_post( $learndash_exam_result_message );
	}

	$learndash_exam_result_button_params = $learndash_exam_model->get_result_button_params();
	if ( ( isset( $learndash_exam_result_button_params['redirect_url'] ) ) && ( isset( $learndash_exam_result_button_params['button_label'] ) ) ) {
		?>
		<p class="result-button"><a href="<?php echo esc_url( $learndash_exam_result_button_params['redirect_url'] ); ?>" class="ld-exam-result-button"><?php echo esc_html( $learndash_exam_result_button_params['button_label'] ); ?></a></p>
		<?php
	}
}
?>
</div>
