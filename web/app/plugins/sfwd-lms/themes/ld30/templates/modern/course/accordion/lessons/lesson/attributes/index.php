<?php
/**
 * View: Course Accordion Lesson - Attributes.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if (
	! $lesson->is_complete()
	&& $lesson->get_topics_number() === 0
	&& $lesson->get_quizzes_number() === 0
	&& ! $lesson->is_sample()
	&& ! $lesson->is_virtual()
	&& ! $lesson->is_in_person()
	&& $lesson->get_available_on_date() === null
) {
	return;
}

?>
<div class="ld-accordion__item-attributes ld-accordion__item-attributes--lesson">
	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/progress' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/topics' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/quizzes' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/sample' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/virtual' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/in-person' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes/available-on' ); ?>
</div>
