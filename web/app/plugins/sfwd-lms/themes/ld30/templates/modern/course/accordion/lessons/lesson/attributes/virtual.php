<?php
/**
 * View: Course Accordion Lesson Attribute - Virtual.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if (
	! $lesson->is_external()
	|| ! $lesson->is_virtual()
) {
	return;
}

$tooltip = __( 'Virtual (Optional)', 'learndash' );

if ( $lesson->is_attendance_required() ) {
	$tooltip = __( 'Virtual (Required)', 'learndash' );
}

?>
<div
	class="ld-accordion__item-attribute ld-accordion__item-attribute--virtual ld-accordion__item-attribute--collapsible ld-tooltip ld-tooltip--modern"
	tabindex="0"
>
	<?php
	$this->template(
		'components/icons/computer',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span
		class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--virtual ld-accordion__item-attribute-label--collapsible ld-tooltip__text"
		role="tooltip"
	>
		<?php echo esc_html( $tooltip ); ?>
	</span>
</div>
