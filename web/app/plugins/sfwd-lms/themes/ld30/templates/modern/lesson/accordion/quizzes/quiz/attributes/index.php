<?php
/**
 * View: Lesson Accordion Quiz - Attributes.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Quiz     $quiz Quiz model object.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;

if (
	! $quiz->is_virtual()
	&& ! $quiz->is_in_person()
	&& $quiz->get_available_on_date() === null
) {
	return;
}

?>
<div class="ld-accordion__item-attributes ld-accordion__item-attributes--quiz">
	<?php $this->template( 'modern/lesson/accordion/quizzes/quiz/attributes/virtual' ); ?>

	<?php $this->template( 'modern/lesson/accordion/quizzes/quiz/attributes/in-person' ); ?>

	<?php $this->template( 'modern/lesson/accordion/quizzes/quiz/attributes/available-on' ); ?>
</div>
