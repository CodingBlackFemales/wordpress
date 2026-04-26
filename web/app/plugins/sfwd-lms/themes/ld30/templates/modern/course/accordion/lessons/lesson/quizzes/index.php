<?php
/**
 * View: Course Accordion Lesson - Quizzes.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Quiz[]   $quizzes Array of quiz model objects.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;

if ( empty( $quizzes ) ) {
	return;
}
?>
<div class="ld-accordion__items ld-accordion__items--lesson-quizzes">
	<?php foreach ( $quizzes as $quiz ) : ?>
		<?php $this->template( 'modern/course/accordion/lessons/lesson/quizzes/quiz', [ 'quiz' => $quiz ] ); ?>
	<?php endforeach; ?>
</div>
