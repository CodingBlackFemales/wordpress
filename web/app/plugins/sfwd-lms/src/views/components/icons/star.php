<?php
/**
 * View: Star icon.
 *
 * @since 4.21.0
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

$svg_classes = [ 'ld-svgicon__star' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Star icon', 'learndash' );
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

<path d="M11.9995 4L14.6778 9.32174L19.9995 9.98261L16.3473 14.1217L17.356 20L11.9995 17.3565L6.64299 20L7.65169 14.1217L3.99951 9.98261L9.32125 9.32174L11.9995 4Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
