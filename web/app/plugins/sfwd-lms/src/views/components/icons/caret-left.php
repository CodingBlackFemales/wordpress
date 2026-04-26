<?php
/**
 * View: Left caret icon.
 *
 * @since 4.21.0
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

$svg_classes = [ 'ld-svgicon__left-caret' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Left caret icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 20,
		'label'   => $label,
		'width'   => 20,
	],
);

?>

<path d="M13.817 16.3554C13.4737 16.7287 12.9074 16.7751 12.51 16.4775L12.4335 16.414L6.24211 10.7207C6.04053 10.5353 5.92563 10.2743 5.92554 10.0004C5.92554 9.7265 6.04045 9.46484 6.24211 9.27941L12.4335 3.58605C12.8316 3.22031 13.451 3.24668 13.817 3.64465C14.1827 4.04273 14.1564 4.66217 13.7584 5.02811L8.35148 10.0004L13.7584 14.972L13.8292 15.0436C14.1586 15.4146 14.1598 15.9822 13.817 16.3554Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
