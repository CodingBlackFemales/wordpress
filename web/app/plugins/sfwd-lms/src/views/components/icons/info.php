<?php
/**
 * View: Info Icon.
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

$svg_classes = [ 'ld-svgicon__info' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Info icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M12 6.08696C8.73432 6.08696 6.08696 8.73432 6.08696 12C6.08696 15.2657 8.73432 17.913 12 17.913C15.2657 17.913 17.913 15.2657 17.913 12C17.913 8.73432 15.2657 6.08696 12 6.08696ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 10.9565C12.5763 10.9565 13.0435 11.4237 13.0435 12V14.7826C13.0435 15.3589 12.5763 15.8261 12 15.8261C11.4237 15.8261 10.9565 15.3589 10.9565 14.7826V12C10.9565 11.4237 11.4237 10.9565 12 10.9565Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M10.9565 9.21739C10.9565 8.64109 11.4237 8.17391 12 8.17391H12.007C12.5833 8.17391 13.0504 8.64109 13.0504 9.21739C13.0504 9.79369 12.5833 10.2609 12.007 10.2609H12C11.4237 10.2609 10.9565 9.79369 10.9565 9.21739Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
