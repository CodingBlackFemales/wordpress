<?php
/**
 * View: Topic Accordion Quizzes - Quiz.
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
<div class="ld-accordion__item ld-accordion__item--quiz">
	<?php $this->template( 'modern/topic/accordion/quizzes/quiz/icon' ); ?>

	<div class="ld-accordion__item-header ld-accordion__item-header--quiz">
		<?php $this->template( 'modern/topic/accordion/quizzes/quiz/title' ); ?>

		<?php $this->template( 'modern/topic/accordion/quizzes/quiz/attributes' ); ?>
	</div>
</div>
