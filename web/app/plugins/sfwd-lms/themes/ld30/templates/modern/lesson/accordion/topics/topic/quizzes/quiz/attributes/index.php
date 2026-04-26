<?php
/**
 * View: Lesson Accordion Topic Quiz - Attributes.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-accordion__item-attributes ld-accordion__item-attributes--topic-quiz">
	<?php $this->template( 'modern/lesson/accordion/topics/topic/quizzes/quiz/attributes/virtual' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/quizzes/quiz/attributes/in-person' ); ?>

	<?php $this->template( 'modern/lesson/accordion/topics/topic/quizzes/quiz/attributes/available-on' ); ?>
</div>
