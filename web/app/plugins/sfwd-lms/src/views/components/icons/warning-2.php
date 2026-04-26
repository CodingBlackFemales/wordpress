<?php
/**
 * View: Warning 2 icon.
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

$svg_classes = [ 'ld-svgicon__warning-2' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Warning icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 12,
		'label'   => $label,
		'width'   => 10,
	],
);

?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M4.92092 0.982056C5.61398 0.982056 6.17582 1.54389 6.17582 2.23696V6.00166C6.17582 6.69473 5.61398 7.25657 4.92092 7.25657C4.22785 7.25657 3.66602 6.69473 3.66602 6.00166V2.23696C3.66602 1.54389 4.22785 0.982056 4.92092 0.982056Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M3.66602 9.76637C3.66602 9.07331 4.22785 8.51147 4.92092 8.51147H4.9319C5.62496 8.51147 6.1868 9.07331 6.1868 9.76637C6.1868 10.4594 5.62496 11.0213 4.9319 11.0213H4.92092C4.22785 11.0213 3.66602 10.4594 3.66602 9.76637Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
