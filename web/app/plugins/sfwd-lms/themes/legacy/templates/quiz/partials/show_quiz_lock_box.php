<?php
/**
 * Displays Quiz Lock Box
 *
 * Available Variables:
 *
 * @var object $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz      WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div style="display: none;" class="wpProQuiz_lock">		
	<?php
	echo wp_kses_post(
		SFWD_LMS::get_template(
			'learndash_quiz_messages',
			array(
				'quiz_post_id' => $quiz->getID(),
				'context'      => 'quiz_locked_message',
				'message'      => '<p>' . sprintf(
					// translators: placeholder: Quiz label.
					esc_html_x( 'You have already completed the %s before. Hence you can not start it again.', 'placeholder: Quiz label.', 'learndash' ),
					learndash_get_custom_label_lower( 'quiz' )
				) . '</p>',
			)
		)
	);
	?>
</div>
