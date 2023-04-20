<?php
/**
 * Displays Quiz Toplist Box
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
<div class="wpProQuiz_toplistShowInButton" style="display: none;">
	<?php echo do_shortcode( '[LDAdvQuiz_toplist ' . $quiz->getId() . ' q="true"]' ); ?>
</div>
