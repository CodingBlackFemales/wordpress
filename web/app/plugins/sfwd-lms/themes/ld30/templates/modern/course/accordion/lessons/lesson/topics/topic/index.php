<?php
/**
 * View: Course Accordion Lesson Topics - Topic.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Topic    $topic Topic model object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;
?>
<div class="ld-accordion__item ld-accordion__item--lesson-topic">
	<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/icon' ); ?>

	<div class="ld-accordion__item-header ld-accordion__item-header--lesson-topic">
		<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/title' ); ?>

		<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/attributes' ); ?>

		<?php
		$this->template(
			'modern/course/accordion/lessons/lesson/topics/topic/quizzes',
			[
				'quizzes' => $topic->get_quizzes(),
			]
		);
		?>

	</div>
</div>
