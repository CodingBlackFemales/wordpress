<?php
/**
 * View: Play Icon.
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

$svg_classes = [ 'ld-svgicon__play' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Play icon', 'learndash' );
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

<path d="M12.6608 7.788C12.8175 7.88592 12.8175 8.11408 12.6608 8.212L5.3825 12.7609C5.21599 12.865 5 12.7453 5 12.5489L5 3.45106C5 3.2547 5.21599 3.13499 5.3825 3.23906L12.6608 7.788Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
