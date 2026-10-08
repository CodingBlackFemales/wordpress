<?php
/**
 * LearnDash LD30 Displays an Exam Questions
 *
 * Available Variables:
 * $learndash_question_description : (strong/HTML) Block content for description.
 *
 * $learndash_exam_model           : (object) LDLMS_Model_Exam instance.
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

if ( isset( $learndash_question_description ) ) {
	$learndash_question_description = trim( $learndash_question_description );
	if ( ( ! empty( $learndash_question_description ) ) && ( '<p></p>' !== $learndash_question_description ) ) {
		?>
		<div class="ld-exam-question-description">
			<?php echo wp_kses_post( $learndash_question_description ); ?>
		</div>
		<?php
	}
}
