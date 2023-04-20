<?php
/**
 * LearnDash LD30 Displays an Exam Wrapper
 *
 * Available Variables:
 *
 * $exam_content        : (string/HTML) Content for Exam.
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

$learndash_question_classes = 'ld-exam-content';
if ( true === $learndash_exam_model->is_graded ) {
	$learndash_question_classes .= ' ld-exam-graded';
	if ( true === $learndash_exam_model->get_grade ) {
		$learndash_question_classes .= ' ld-exam-graded-passed';
	} else {
		$learndash_question_classes .= ' ld-exam-graded-failed';
	}
} else {
	$learndash_question_classes .= ' ld-exam-not-graded';
}
?>
<div id="ld-exam-content-<?php echo absint( $learndash_exam_model->exam_id ); ?>" class="<?php echo esc_attr( $learndash_question_classes ); ?>">

	<?php if ( true !== $learndash_exam_model->is_graded ) { ?>
		<form method="POST" action="<?php echo esc_url( get_permalink( $learndash_exam_model->exam_id ) ); ?>">
			<input type="hidden" name="exam-nonce" value="<?php echo esc_attr( $learndash_exam_model->form_nonce ); ?>" />
			<input type="hidden" id="ld-form-exam-id" name="exam_id" value="<?php echo absint( $learndash_exam_model->exam_id ); ?>" />
			<input type="hidden" id="ld-form-exam-started" name="exam_started" value="0" />
			<input type="hidden" id="ld-form-exam-course-id" name="course_id" value="<?php echo absint( $learndash_exam_model->course_id ); ?>" />
			<input type="hidden" id="ld-form-exam-user-id" name="user_id" value="<?php echo absint( $learndash_exam_model->user_id ); ?>" />
		<?php
	}

	// We don't escape the $exam_content because it's already escaped in the template where it was built.
	echo $exam_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>

	<?php if ( true !== $learndash_exam_model->is_graded ) { ?>
		</form>
	<?php } ?>
</div>
