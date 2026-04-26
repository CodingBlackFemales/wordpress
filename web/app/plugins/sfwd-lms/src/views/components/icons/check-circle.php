<?php
/**
 * View: Check circle icon.
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

$svg_classes = [ 'ld-svgicon__check-circle' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Check circle icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 17,
		'label'   => $label,
		'width'   => 16,
	],
);

?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M7.9987 1.33594C4.3168 1.33594 1.33203 4.32071 1.33203 8.0026C1.33203 11.6845 4.3168 14.6693 7.9987 14.6693C11.6806 14.6693 14.6654 11.6845 14.6654 8.0026C14.6654 4.32071 11.6806 1.33594 7.9987 1.33594ZM10.1466 5.52192C10.554 5.10728 11.2182 5.10728 11.6256 5.52192C12.0281 5.93163 12.0281 6.59229 11.6256 7.002L7.65564 11.0428C7.24828 11.4575 6.58408 11.4575 6.17671 11.0428L4.3722 9.20608C3.96968 8.79637 3.96968 8.13571 4.3722 7.726C4.77957 7.31136 5.44376 7.31136 5.85113 7.726L6.91618 8.81007L10.1466 5.52192Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
