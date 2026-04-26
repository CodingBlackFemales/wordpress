<?php
/**
 * View: Course Accordion Topic Quizzes - Quiz.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Quiz     $quiz Quiz model object.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;

?>
<div class="ld-accordion__item ld-accordion__item--topic-quiz">
	<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/quizzes/quiz/icon' ); ?>

	<div class="ld-accordion__item-header ld-accordion__item-header--topic-quiz">
		<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/quizzes/quiz/title' ); ?>

		<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic/quizzes/quiz/attributes' ); ?>
	</div>
</div>
