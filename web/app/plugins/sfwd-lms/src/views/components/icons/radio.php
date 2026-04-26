<?php
/**
 * View: Radio button Icon
 *
 * The .ld-svgicon--radio-selected class is added to the part of the svg that gets displayed when the radio button is selected.
 *
 * @since 4.16.0
 * @version 4.21.0
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__radio' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Radio button icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 24,
		'label'   => $label,
		'width'   => 24,
	],
);
?>

<rect class="ld-svgicon__radio-bg" x="0.5" y="0.5" width="22" height="22" rx="11.5"/>
<rect class="ld-svgicon__radio-border" x="0.5" y="0.5" width="22" height="22" rx="11.5"/>
<circle class="ld-svgicon__radio-select" cx="11.5" cy="11.5" r="7"/>

<?php
$this->template( 'components/icons/icon/end' );
