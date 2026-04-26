<?php
/**
 * View: Course Accordion.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var bool     $is_content_visible Whether the content is visible.
 * @var Course   $course             Course model object.
 * @var Lesson[] $lessons            Array of lesson model objects.
 * @var Quiz[]   $final_quizzes      Array of quiz model objects.
 * @var Template $this               Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

if (
	! $is_content_visible
	|| (
		empty( $lessons )
		&& empty( $final_quizzes )
	)
) {
	return;
}
?>
<div
	class="ld-accordion ld-accordion--course"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php $this->template( 'modern/course/accordion/header' ); ?>

	<div class="ld-accordion__content">
		<?php $this->template( 'modern/course/accordion/lessons' ); ?>

		<?php $this->template( 'modern/course/accordion/final-quizzes' ); ?>
	</div>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
