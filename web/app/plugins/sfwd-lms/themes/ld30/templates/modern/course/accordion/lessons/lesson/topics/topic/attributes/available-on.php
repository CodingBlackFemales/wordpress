<?php
/**
 * View: Course Accordion Topic Attribute - Available On.
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

$available_on = $topic->get_available_on_date();

if ( is_null( $available_on ) ) {
	return;
}

$tooltip = sprintf(
	// translators: %s: Date when a lesson will be available.
	esc_html_x( 'Available %s', '%s: Date when a lesson will be available', 'learndash' ),
	esc_html( learndash_adjust_date_time_display( $available_on ) )
);

?>
<div
	class="ld-accordion__item-attribute ld-accordion__item-attribute--available-on ld-accordion__item-attribute--collapsed ld-tooltip ld-tooltip--modern"
	tabindex="0"
>
	<?php
	$this->template(
		'components/icons/clock',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon ld-accordion__item-attribute-icon--available-on' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span
		class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--available-on ld-accordion__item-attribute-label--collapsed ld-tooltip__text"
		role="tooltip"
	>
		<?php echo esc_html( $tooltip ); ?>
	</span>
</div>
