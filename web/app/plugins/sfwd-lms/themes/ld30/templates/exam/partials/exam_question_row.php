<?php
/**
 * LearnDash LD30 Displays an Exam Question row
 *
 * Available Variables:
 * $learndash_question_content      : (strong/HTML) Question content.
 *
 * $learndash_exam_model            : (object) LDLMS_Model_Exam instance.
 * $learndash_question_model        : (object) LDLMS_Model_Exam_Question instance.
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

if ( ( ! isset( $learndash_question_model ) ) || ( ! is_a( $learndash_question_model, 'LDLMS_Model_Exam_Question' ) ) ) {
	return;
}

$learndash_question_classes = $learndash_question_model->get_question_classes( 'string' );

?>
<li class="<?php echo esc_attr( $learndash_question_classes ); ?>">
	<div class="ld-exam-question-title">
		<?php
		echo wp_kses_post( $learndash_question_model->question_title );
		?>
	</div>
	<?php
	// We don't escape the $question_content because it's already escaped in the template where it was built.
	echo $learndash_question_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</li>
