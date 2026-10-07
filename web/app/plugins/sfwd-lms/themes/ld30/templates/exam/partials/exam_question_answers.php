<?php
/**
 * LearnDash LD30 Displays an Exam Question row
 *
 * Available Variables:
 * $learndash_question_answers : (array) Question answers array.
 *
 * $learndash_exam_model       : (object) LDLMS_Model_Exam instance.
 * $learndash_question_model   : (object) LDLMS_Model_Exam_Question instance.
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

$learndash_block_content = '';

if ( ( isset( $learndash_question_answers ) ) && ( is_array( $learndash_question_answers ) ) ) {
	foreach ( $learndash_question_answers as $learndash_question_answer ) {
		$learndash_answer_content = '';

		$learndash_answer_content = SFWD_LMS::get_template(
			'exam/partials/exam_question_answer_types/exam_question_answer_' . $learndash_question_model->question_type . '.php',
			array(
				'learndash_question_answer' => $learndash_question_answer,
				'learndash_exam_model'      => $learndash_exam_model,
				'learndash_question_model'  => $learndash_question_model,
			)
		);

		if ( ( is_string( $learndash_answer_content ) ) && ( ! empty( $learndash_answer_content ) ) ) {
			$learndash_block_content .= $learndash_answer_content;
		}
	}
}
?>
<div class="ld-exam-question-answers">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $learndash_block_content;
	?>
</div>

