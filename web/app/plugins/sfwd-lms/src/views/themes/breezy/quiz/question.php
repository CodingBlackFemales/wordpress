<?php
/**
 * View: Question.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Template $this          Template instance.
 * @var string   $question_body Body content.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;

?>
<div class="<?php learndash_the_wrapper_class(); ?>--no-sidebar ld-quiz__question">
	<?php $this->template( 'quiz/header' ); ?>

	<div class="ld-quiz__question-body">
		<?php echo $question_body; // phpcs:ignore ?>
	</div>

	<?php $this->template( 'quiz/questions/free-choice' ); ?>

	<?php $this->template( 'quiz/footer' ); ?>
</div>
