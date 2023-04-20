<?php
/**
 * Displays Quiz Review Box
 *
 * Available Variables:
 *
 * @var object $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz      WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int    $question_count Number of Question to display.
 * @since 3.2.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpProQuiz_reviewQuestion">
	<ol>
		<?php for ( $xy = 1; $xy <= $question_count; $xy ++ ) { ?>
			<li><?php echo $xy; ?></li>
		<?php } ?>
	</ol>
	<div style="display: none;"></div>
</div>
