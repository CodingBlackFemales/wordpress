<?php
/**
 * View: Right caret icon.
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

$svg_classes = [ 'ld-svgicon__right-caret' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Right caret icon', 'learndash' );
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

<path d="M6.18302 3.64458C6.52628 3.27127 7.09257 3.22493 7.48999 3.52251L7.56649 3.58598L13.7579 9.27934C13.9595 9.46468 14.0744 9.72574 14.0745 9.99956C14.0745 10.2735 13.9595 10.5352 13.7579 10.7206L7.56649 16.4139C7.1684 16.7797 6.54896 16.7533 6.18302 16.3554C5.81728 15.9573 5.84365 15.3378 6.24162 14.9719L11.6485 9.99956L6.24162 5.02804L6.17082 4.95642C5.84142 4.58541 5.84016 4.01776 6.18302 3.64458Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
