<?php
/**
 * LearnDash LD30 Displays an Exam Footer
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

?>
<div class="ld-exam-footer">
	<button type="submit" class="ld-exam-button-next"><?php echo esc_html__( 'Next', 'learndash' ); ?></button>
	<button type="submit" class="ld-exam-button-submit"><?php echo esc_html__( 'Submit', 'learndash' ); ?></button>
</div>
