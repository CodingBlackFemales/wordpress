<?php
/**
 * View: Course Accordion Topic Quiz Attribute - Virtual.
 *
 * @since 4.21.0
 * @version 4.21.3
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
	|| ! $quiz->is_virtual()
) {
	return;
}

$tooltip = __( 'Virtual (Optional)', 'learndash' );

if ( $quiz->is_attendance_required() ) {
	$tooltip = __( 'Virtual (Required)', 'learndash' );
}

?>
<div
	class="ld-accordion__item-attribute ld-accordion__item-attribute--virtual ld-accordion__item-attribute--collapsed ld-tooltip ld-tooltip--modern"
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
		class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--virtual ld-accordion__item-attribute-label--collapsed ld-tooltip__text"
		role="tooltip"
	>
		<?php echo esc_html( $tooltip ); ?>
	</span>
</div>
