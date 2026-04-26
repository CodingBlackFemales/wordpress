<?php
/**
 * View: Success icon.
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

$svg_classes = [ 'ld-svgicon__success' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Success icon', 'learndash' );
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

<path d="M7.18196 2.11159C7.59103 1.77256 8.1837 1.77251 8.59274 2.11159L8.67756 2.18972L8.75346 2.27455C9.08219 2.68468 9.08216 3.27405 8.75346 3.68421L8.67756 3.77015L4.70752 7.81051C4.29951 8.2258 3.65026 8.25173 3.21192 7.88864L3.12709 7.81051L1.32232 5.97449C0.892521 5.53701 0.892597 4.83159 1.32232 4.39406L1.40715 4.31593C1.81622 3.97678 2.40884 3.9768 2.81793 4.31593L2.90275 4.39406L3.91619 5.42536L7.09714 2.18972L7.18196 2.11159Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
