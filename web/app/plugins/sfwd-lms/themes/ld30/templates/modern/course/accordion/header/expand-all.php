<?php
/**
 * View: Course Accordion Header - Expand All Button.
 *
 * @since 4.21.0
 * @version 4.21.4
 *
 * @var Course   $course              Course model object.
 * @var Lesson[] $lessons             Lessons.
 * @var bool     $content_is_expanded Whether the content is expanded or not.
 * @var Template $this                Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Course;

if ( empty( $lessons ) ) {
	return;
}

$container_ids = implode(
	' ',
	array_filter(
		array_map(
			function ( Lesson $lesson ): string {
				return $lesson->has_steps() ? "ld-expand-{$lesson->get_id()}" : '';
			},
			$lessons
		)
	)
);

if ( empty( $container_ids ) ) {
	return;
}

?>
<button
	aria-controls="<?php echo esc_attr( $container_ids ); ?>"
	aria-expanded="<?php echo $content_is_expanded ? 'true' : 'false'; ?>"
	class="ld-accordion__expand-button ld-accordion__expand-button--all"
	data-ld-collapse-text="<?php esc_attr_e( 'Collapse All', 'learndash' ); ?>"
	data-ld-expand-button="true"
	data-ld-expand-text="<?php esc_attr_e( 'Expand All', 'learndash' ); ?>"
	id="<?php echo esc_attr( 'ld-expand-button-' . $course->get_id() ); ?>"
>
	<span
		class="ld-accordion__expand-button-text"
		data-ld-expand-button-text-element="true"
	>
		<?php esc_html_e( 'Expand All', 'learndash' ); ?>
	</span>

	<span class="screen-reader-text">
		<?php echo esc_html( learndash_get_custom_label( 'lessons' ) ); ?>
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
