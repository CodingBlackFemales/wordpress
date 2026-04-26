<?php
/**
 * View: Lesson Accordion Header - Expand All Button.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Topic[]  $topics Topics.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if ( empty( $topics ) ) {
	return;
}

$container_ids = implode(
	' ',
	array_filter(
		array_map(
			function ( Topic $topic ): string {
				return $topic->get_quizzes_number() > 0 ? "ld-expand-{$topic->get_id()}" : '';
			},
			$topics
		)
	)
);

if ( empty( $container_ids ) ) {
	return;
}
?>
<button
	aria-controls="<?php echo esc_attr( $container_ids ); ?>"
	aria-expanded="false"
	class="ld-accordion__expand-button ld-accordion__expand-button--all"
	data-ld-collapse-text="<?php esc_attr_e( 'Collapse All', 'learndash' ); ?>"
	data-ld-expand-button="true"
	data-ld-expand-text="<?php esc_attr_e( 'Expand All', 'learndash' ); ?>"
	id="<?php echo esc_attr( 'ld-expand-button-' . $lesson->get_id() ); ?>"
>
	<span
		class="ld-accordion__expand-button-text"
		data-ld-expand-button-text-element="true"
	>
		<?php esc_html_e( 'Expand All', 'learndash' ); ?>
	</span>

	<span class="screen-reader-text">
		<?php echo esc_html( learndash_get_custom_label( 'topics' ) ); ?>
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
