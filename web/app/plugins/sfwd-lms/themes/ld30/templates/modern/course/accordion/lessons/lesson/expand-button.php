<?php
/**
 * View: Course Accordion Lesson - Expand Button.
 *
 * @since 4.21.0
 * @version 4.21.4
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if ( ! $lesson->has_steps() ) {
	return;
}
?>

<button
	aria-controls="<?php echo esc_attr( 'ld-expand-' . $lesson->get_id() ); ?>"
	aria-expanded="false"
	class="ld-accordion__expand-button ld-accordion__expand-button--lesson"
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
		<?php echo esc_html( $lesson->get_title() ); ?>
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
