<?php
/**
 * View: Check Icon.
 *
 * @since 4.20.1
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

$svg_classes = [ 'ld-svgicon__check' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Check icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 10,
		'label'   => $label,
		'width'   => 12,
	],
);
?>

<path d="M10.0996 0.149902L11.5522 1.0874L5.18628 9.48584H3.73364L0.1875 4.95459L1.64014 3.70459L4.45996 6.0874L10.0996 0.149902Z"/>

<?php
$this->template( 'components/icons/icon/end' );
