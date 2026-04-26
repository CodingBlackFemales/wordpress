<?php
/**
 * View: Credit Card icon.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__credit-card' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Credit/Debit Card icon', 'learndash' );
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

<path d="M5 3.75C3.34315 3.75 2 5.09315 2 6.75V7.5H23V6.75C23 5.09315 21.6569 3.75 20 3.75H5Z" fill="#235AF3"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M23 9.75H2V17.25C2 18.9069 3.34315 20.25 5 20.25H20C21.6569 20.25 23 18.9069 23 17.25V9.75ZM5 13.5C5 13.0858 5.33579 12.75 5.75 12.75H11.75C12.1642 12.75 12.5 13.0858 12.5 13.5C12.5 13.9142 12.1642 14.25 11.75 14.25H5.75C5.33579 14.25 5 13.9142 5 13.5ZM5.75 15.75C5.33579 15.75 5 16.0858 5 16.5C5 16.9142 5.33579 17.25 5.75 17.25H8.75C9.16421 17.25 9.5 16.9142 9.5 16.5C9.5 16.0858 9.16421 15.75 8.75 15.75H5.75Z" fill="#235AF3"/>

<?php
$this->template( 'components/icons/icon/end' );
