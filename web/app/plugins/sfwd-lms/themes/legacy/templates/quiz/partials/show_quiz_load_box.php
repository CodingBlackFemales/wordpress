<?php
/**
 * Displays Quiz Load Box
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
<div style="display: none;" class="wpProQuiz_loadQuiz">
	<p>
		<?php
		printf(
			// translators: placeholder: Quiz label.
			esc_html_x( '%s is loading...', 'placeholder: Quiz label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'quiz' )
		);
		?>
	</p>
</div>
