<?php
/**
 * View: Drag icon.
 *
 * @since 4.21.3
 * @version 4.21.3
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__drag' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Drag icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 15,
		'label'   => $label,
		'width'   => 14,
	],
);
?>

<path d="M13.9257 7.07058C14.0248 7.16967 14.0248 7.33033 13.9257 7.42942L11.3873 9.96779C11.2275 10.1276 10.9541 10.0144 10.9541 9.78837V8.05319H7.80319V11.2041H9.53837C9.76443 11.2041 9.87764 11.4775 9.71779 11.6373L7.17942 14.1757C7.08033 14.2748 6.91967 14.2748 6.82058 14.1757L4.28221 11.6373C4.12236 11.4775 4.23557 11.2041 4.46163 11.2041H5.94307C6.08321 11.2041 6.19681 11.0905 6.19681 10.9504V8.05319H3.04586V9.78837C3.04586 10.0144 2.77254 10.1276 2.61269 9.96779L0.0743194 7.42942C-0.024773 7.33033 -0.0247732 7.16967 0.0743192 7.07058L2.61269 4.53221C2.77254 4.37236 3.04586 4.48557 3.04586 4.71163V6.44681H6.19681V3.29586H4.46163C4.23557 3.29586 4.12236 3.02254 4.28221 2.86269L6.82058 0.324319C6.91967 0.225227 7.08033 0.225227 7.17942 0.324319L9.71779 2.86269C9.87764 3.02254 9.76443 3.29586 9.53837 3.29586H7.80319V6.44681H10.9541V4.71163C10.9541 4.48557 11.2275 4.37236 11.3873 4.53221L13.9257 7.07058Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
