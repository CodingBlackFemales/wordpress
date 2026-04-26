<?php
/**
 * View: Course Accordion Topic Attribute - In-Person.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var Topic    $topic Topic model.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if (
	! $topic->is_external()
	|| ! $topic->is_in_person()
) {
	return;
}

$tooltip = __( 'In-Person (Optional)', 'learndash' );

if ( $topic->is_attendance_required() ) {
	$tooltip = __( 'In-Person (Required)', 'learndash' );
}

?>
<div
	class="ld-accordion__item-attribute ld-accordion__item-attribute--in-person ld-accordion__item-attribute--collapsed ld-tooltip ld-tooltip--modern"
	tabindex="0"
>

	<?php
	$this->template(
		'components/icons/person',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span
		class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--in-person ld-accordion__item-attribute-label--collapsed ld-tooltip__text"
		role="tooltip"
	>
		<?php echo esc_html( $tooltip ); ?>
	</span>
</div>
