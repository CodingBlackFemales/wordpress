<?php
/**
 * View: Lesson Accordion Topic Quiz Attribute - In-Person.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Quiz     $quiz The quiz object.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;

if (
	! $quiz->is_external()
	|| ! $quiz->is_in_person()
) {
	return;
}

$tooltip = __( 'In-Person (Optional)', 'learndash' );

if ( $quiz->is_attendance_required() ) {
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
