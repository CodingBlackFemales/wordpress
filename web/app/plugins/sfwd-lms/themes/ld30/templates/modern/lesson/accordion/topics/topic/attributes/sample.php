<?php
/**
 * View: Lesson Accordion Topic Attribute - Sample.
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

if ( ! $topic->is_sample() ) {
	return;
}

$tooltip = sprintf(
	// translators: %s: Topic custom label.
	esc_html__( 'Sample %s', 'learndash' ),
	esc_html( learndash_get_custom_label( 'topic' ) ),
);

?>
<div
	class="ld-accordion__item-attribute ld-accordion__item-attribute--sample ld-accordion__item-attribute--collapsible ld-tooltip ld-tooltip--modern"
	tabindex="0"
>
	<?php
	$this->template(
		'components/icons/lock',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span
		class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--sample ld-accordion__item-attribute-label--collapsible ld-tooltip__text"
		role="tooltip"
	>
		<?php echo esc_html( $tooltip ); ?>
	</span>
</div>
