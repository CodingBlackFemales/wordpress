<?php
/**
 * View: Comment Outlined Icon.
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

$svg_classes = [ 'ld-svgicon__comment-outlined' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Comment icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M4.26669 4.07375C4.12524 4.07375 3.98958 4.12994 3.88956 4.22995C3.78954 4.32997 3.73335 4.46563 3.73335 4.60708V11.8528L4.95623 10.6299C5.05625 10.5299 5.1919 10.4737 5.33335 10.4737H11.7334C11.8748 10.4737 12.0105 10.4175 12.1105 10.3175C12.2105 10.2175 12.2667 10.0819 12.2667 9.9404V4.60708C12.2667 4.46563 12.2105 4.32997 12.1105 4.22995C12.0105 4.12994 11.8748 4.07375 11.7334 4.07375H4.26669ZM3.13532 3.47571C3.43537 3.17565 3.84234 3.00708 4.26669 3.00708H11.7334C12.1577 3.00708 12.5647 3.17565 12.8647 3.47571C13.1648 3.77577 13.3334 4.18273 13.3334 4.60708V9.9404C13.3334 10.3647 13.1648 10.7717 12.8647 11.0718C12.5647 11.3718 12.1577 11.5404 11.7334 11.5404H5.55427L3.57714 13.5175C3.42461 13.6701 3.19522 13.7157 2.99592 13.6331C2.79663 13.5506 2.66669 13.3561 2.66669 13.1404V4.60708C2.66669 4.18273 2.83526 3.77577 3.13532 3.47571Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
