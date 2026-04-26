<?php
/**
 * View: Forward slash icon.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__forward-slash' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Forward slash icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M10.8389 1.89787L4.13485 13.5096C4.05765 13.6465 4.0366 13.808 4.07613 13.9601C4.16047 14.2768 4.48553 14.4651 4.80218 14.3808C4.95476 14.3401 5.08484 14.2403 5.16364 14.1035L11.867 2.49293C12.0312 2.20932 11.9343 1.84634 11.6507 1.68219C11.366 1.51741 11.003 1.61426 10.8389 1.89787Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
