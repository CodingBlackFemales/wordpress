<?php
/**
 * View: Lesson Accordion Topic - Expand Button.
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

if ( $topic->get_quizzes_number() <= 0 ) {
	return;
}
?>
<button
	aria-controls="<?php echo esc_attr( 'ld-expand-' . $topic->get_id() ); ?>"
	aria-expanded="false"
	class="ld-accordion__expand-button ld-accordion__expand-button--topic"
	data-ld-collapse-text="<?php esc_html_e( 'Collapse', 'learndash' ); ?>"
	data-ld-expand-button="true"
	data-ld-expand-text="<?php esc_html_e( 'Expand', 'learndash' ); ?>"
>
	<span
		class="ld-accordion__expand-button-text"
		data-ld-expand-button-text-element="true"
	>
		<?php esc_html_e( 'Expand', 'learndash' ); ?>
	</span>

	<span class="screen-reader-text">
		<?php echo esc_html( $topic->get_title() ); ?>
	</span>

	<?php
	$this->template(
		'components/icons/caret-down',
		[
			'classes'        => [
				'ld-accordion__expand-button-icon',
				'ld-accordion__expand-button-icon--expand',
			],
			'is_aria_hidden' => true,
		]
	);
	?>
	<?php
	$this->template(
		'components/icons/caret-up',
		[
			'classes'        => [
				'ld-accordion__expand-button-icon',
				'ld-accordion__expand-button-icon--collapse',
			],
			'is_aria_hidden' => true,
		]
	);
	?>
</button>
