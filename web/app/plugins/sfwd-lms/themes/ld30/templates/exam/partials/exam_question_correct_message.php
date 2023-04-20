<?php
/**
 * LearnDash LD30 Displays an Exam Question Correct Message.
 *
 * Available Variables:
 * $learndash_question_correct_message : (string/HTML) Question correct message.
 *
 * $learndash_exam_model               : (object) LDLMS_Model_Exam instance.
 * $learndash_question_model           : (object) LDLMS_Model_Exam_Question instance.
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

if ( ! isset( $learndash_question_correct_message ) ) {
	$learndash_question_correct_message = '';
}

if ( ( true !== $learndash_exam_model->is_graded ) || ( true !== $learndash_question_model->get_grade ) ) {
	$learndash_question_correct_message = '';
}

$learndash_question_correct_message = trim( $learndash_question_correct_message );
if ( '<p></p>' === $learndash_question_correct_message ) {
	$learndash_question_correct_message = '';
}

if ( ! empty( $learndash_question_correct_message ) ) {
	?><div class="ld-exam-question-correct-message"><?php echo wp_kses_post( $learndash_question_correct_message ); ?></div>
	<?php
}
