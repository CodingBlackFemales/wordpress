<?php
/**
 * View: Course Accordion Lessons - Lesson.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Lesson   $lesson     Lesson model object.
 * @var Template $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Lesson;
?>
<div class="ld-accordion__item ld-accordion__item--lesson">
	<div class="ld-accordion__item-header ld-accordion__item-header--lesson">
		<?php $this->template( 'modern/course/accordion/lessons/lesson/title' ); ?>

		<?php $this->template( 'modern/course/accordion/lessons/lesson/attributes' ); ?>
	</div>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/expand-button' ); ?>

	<?php $this->template( 'modern/course/accordion/lessons/lesson/steps' ); ?>
</div>
