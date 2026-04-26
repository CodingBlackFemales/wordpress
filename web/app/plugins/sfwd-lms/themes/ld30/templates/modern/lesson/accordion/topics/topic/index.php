<?php
/**
 * View: Lesson Accordion Topics - Topic.
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
?>
<div class="ld-accordion__item ld-accordion__item--topic">
	<div class="ld-accordion__item-header ld-accordion__item-header--topic">
		<?php $this->template( 'modern/lesson/accordion/topics/topic/title' ); ?>

		<?php $this->template( 'modern/lesson/accordion/topics/topic/attributes' ); ?>
	</div>

	<?php if ( $topic->get_quizzes_number() > 0 ) : ?>
		<?php $this->template( 'modern/lesson/accordion/topics/topic/expand-button' ); ?>

		<div
			class="ld-accordion__item-steps"
			id="ld-expand-<?php echo esc_attr( (string) $topic->get_id() ); ?>"
		>
			<div class="ld-accordion__item-steps-container">
				<?php
				$this->template(
					'modern/lesson/accordion/topics/topic/quizzes',
					[
						'quizzes' => $topic->get_quizzes(),
					]
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
