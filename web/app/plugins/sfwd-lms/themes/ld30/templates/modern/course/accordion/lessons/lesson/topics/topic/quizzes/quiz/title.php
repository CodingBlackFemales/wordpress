<?php
/**
 * View: Course Accordion Topic Quiz - Title.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var bool $has_access Whether the user has access to the course or not.
 * @var Quiz $quiz Quiz model object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;

?>
<div
	class="ld-accordion__item-title-wrapper ld-tooltip ld-tooltip--modern"
>
	<a
		<?php if ( ! $has_access && ! $quiz->is_sample() ) : ?>
			aria-describedby="ld-accordion__tooltip--topic-quiz-<?php echo esc_attr( (string) $quiz->get_id() ); ?>"
		<?php endif; ?>
		class="ld-accordion__item-title ld-accordion__item-title--topic-quiz"
		href="<?php echo esc_url( $quiz->get_permalink() ); ?>"
	>
		<?php echo wp_kses_post( $quiz->get_title() ); ?>
	</a>

	<?php if ( ! $has_access && ! $quiz->is_sample() ) : ?>
		<div
			class="ld-tooltip__text"
			id="ld-accordion__tooltip--topic-quiz-<?php echo esc_attr( (string) $quiz->get_id() ); ?>"
			role="tooltip"
		>
			<?php esc_html_e( "You don't currently have access to this content", 'learndash' ); ?>
		</div>
	<?php endif; ?>
</div>
