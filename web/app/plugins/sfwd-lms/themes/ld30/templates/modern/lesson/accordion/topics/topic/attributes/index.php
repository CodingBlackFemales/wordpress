<?php
/**
 * View: Lesson Accordion Topic - Attributes.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic Topic model object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if (
	! $topic->is_complete()
	&& $topic->get_quizzes_number() === 0
	&& ! $topic->is_sample()
	&& ! $topic->is_virtual()
	&& ! $topic->is_in_person()
	&& $topic->get_available_on_date() === null
) {
	return;
}

?>
<div class="ld-accordion__item-attributes ld-accordion__item-attributes--topic">
	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/progress' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/quizzes' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/sample' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/virtual' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/in-person' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes/available-on' ); ?>
</div>
