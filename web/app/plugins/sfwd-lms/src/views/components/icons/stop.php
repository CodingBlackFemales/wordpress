<?php
/**
 * View: Stop Icon.
 *
 * @since 4.23.0
 * @version 4.23.0
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__stop' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Stop icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 16,
		'label'   => $label,
		'width'   => 16,
	],
);
?>

<rect x="4" y="4" width="8" height="8" rx="1" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
