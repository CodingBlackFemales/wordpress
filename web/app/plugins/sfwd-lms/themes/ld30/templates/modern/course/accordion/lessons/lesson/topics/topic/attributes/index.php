<?php
/**
 * View: Course Accordion Topic - Attributes.
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

if (
	! $topic->is_virtual()
	&& ! $topic->is_in_person()
	&& $topic->get_available_on_date() === null
) {
	return;
}

?>
<div class="ld-accordion__item-attributes ld-accordion__item-attributes--lesson-topic">
	<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/attributes/virtual' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/attributes/in-person' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/attributes/available-on' ); ?>
</div>
