<?php
/**
 * View: Topic Accordion - Quizzes.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Quiz[]   $quizzes Array of quiz model objects.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Quiz;

if ( count( $quizzes ) <= 0 ) {
	return;
}
?>
<div class="ld-accordion__section ld-accordion__section--quizzes">
	<?php $this->template( 'modern/topic/accordion/quizzes/heading' ); ?>

	<div class="ld-accordion__items ld-accordion__items--quizzes">
		<?php foreach ( $quizzes as $quiz ) : ?>
			<?php $this->template( 'modern/topic/accordion/quizzes/quiz', [ 'quiz' => $quiz ] ); ?>
		<?php endforeach; ?>
	</div>
</div>

