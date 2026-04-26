<?php
/**
 * View: Course Accordion Lesson - Steps.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Lesson;

if ( ! $lesson->has_steps() ) {
	return;
}
?>
<div id="ld-expand-<?php echo esc_attr( (string) $lesson->get_id() ); ?>" class="ld-accordion__item-steps">
	<div class="ld-accordion__item-steps-container">
		<?php
		$this->template(
			'modern/course/accordion/lessons/lesson/topics',
			[
				'topics' => $lesson->get_topics(),
			]
		);
		?>

		<?php
		$this->template(
			'modern/course/accordion/lessons/lesson/quizzes',
			[
				'quizzes' => $lesson->get_quizzes(),
			]
		);
		?>
	</div>
</div>
