<?php
/**
 * View: Down caret icon.
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

$svg_classes = [ 'ld-svgicon__down-caret' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Down caret icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M4.33474 8.36612C4.74672 7.91551 5.39498 7.88085 5.84331 8.26213L5.95098 8.36612L12 14.9813L18.049 8.36612C18.461 7.91551 19.1093 7.88085 19.5576 8.26213L19.6653 8.36612C20.0772 8.81672 20.1089 9.52576 19.7603 10.0161L19.6653 10.1339L12.8081 17.6339C12.3961 18.0845 11.7479 18.1192 11.2995 17.7379L11.1919 17.6339L4.33474 10.1339C3.88842 9.64573 3.88842 8.85427 4.33474 8.36612Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
