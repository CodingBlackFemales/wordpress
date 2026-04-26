<?php
/**
 * View: Lesson Accordion.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Quiz[]   $quizzes Array of quiz model objects.
 * @var Topic[]  $topics  Array of topic model objects.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if (
	empty( $topics )
	&& empty( $quizzes )
) {
	return;
}
?>
<div
	class="ld-accordion ld-accordion--lesson"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php $this->template( 'modern/lesson/accordion/header' ); ?>

	<div class="ld-accordion__content">
		<?php $this->template( 'modern/lesson/accordion/topics' ); ?>

		<?php $this->template( 'modern/lesson/accordion/quizzes' ); ?>
	</div>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
