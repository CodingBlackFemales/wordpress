<?php
/**
 * View: Error icon.
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

$svg_classes = [ 'ld-svgicon__error' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Error icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 10,
		'label'   => $label,
		'width'   => 10,
	],
);

?>

<path d="M6.87291 1.58053C7.27584 1.25181 7.8571 1.2518 8.26003 1.58053L8.34377 1.65545L8.41869 1.73918C8.77075 2.17084 8.74612 2.80767 8.34377 3.21004L6.55339 5.00041L8.34377 6.79078L8.41869 6.87452C8.77074 7.30619 8.74613 7.94301 8.34377 8.34537C7.94138 8.74759 7.30452 8.77233 6.87291 8.42029L6.78917 8.34537L4.9988 6.555L3.20843 8.34537C2.77925 8.77436 2.08298 8.77442 1.65384 8.34537C1.2247 7.91624 1.22482 7.22 1.65384 6.79078L3.44421 5.00041L1.65384 3.21004C1.22471 2.78089 1.22481 2.08465 1.65384 1.65545L1.73757 1.58053C2.1405 1.2518 2.72176 1.25181 3.1247 1.58053L3.20843 1.65545L4.9988 3.44582L6.78917 1.65545L6.87291 1.58053Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
