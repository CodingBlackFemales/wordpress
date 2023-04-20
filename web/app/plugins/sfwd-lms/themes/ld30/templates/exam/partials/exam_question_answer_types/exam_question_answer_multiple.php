<?php
/**
 * LearnDash LD30 Displays an Exam Question row
 *
 * Available Variables:
 * $learndash_question_answer : (array) Question answer array.
 *
 * $learndash_exam_model      : (object) LDLMS_Model_Exam instance.
 * $learndash_question_model  : (object) LDLMS_Model_Exam_Question instance.
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

$learndash_block_content  = '';
$learndash_answer_content = '';
$learndash_input_disabled = '';
$learndash_input_checked  = '';

if ( true === $learndash_exam_model->is_graded ) {
	if ( ( isset( $learndash_question_answer['student_answer_value'] ) ) && ( $learndash_question_answer['student_answer_value'] ) ) {
		$learndash_input_checked = ' checked="checked" ';
	}
}
if ( true === $learndash_exam_model->is_graded ) {
	$learndash_input_disabled = ' disabled="disabled" ';
}

$learndash_answer_classes = $learndash_question_model->get_answer_classes( $learndash_question_answer, 'string' );

$learndash_answer_content .= '<input type="checkbox" id="ld-exam-question-answer-' . $learndash_question_model->question_idx . '-' . $learndash_question_answer['answer_idx'] . '" name="ld-exam-question-answer[' . $learndash_question_model->question_idx . '][' . $learndash_question_answer['answer_idx'] . ']" value="1" ' . $learndash_input_checked . ' ' . $learndash_input_disabled . '/>';

if ( ! empty( $learndash_answer_content && ! empty( $learndash_question_answer['answer_label'] ) ) ) {
	$learndash_answer_content .= '<label for="ld-exam-question-answer-' . $learndash_question_model->question_idx . '-' . $learndash_question_answer['answer_idx'] . '">' . wp_kses_post( $learndash_question_answer['answer_label'] ) . '</label>';

	$learndash_block_content .= '<div class="' . $learndash_answer_classes . '">' . $learndash_answer_content . '</div>';
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $learndash_block_content;
