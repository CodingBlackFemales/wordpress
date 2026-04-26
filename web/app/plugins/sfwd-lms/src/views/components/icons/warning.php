<?php
/**
 * View: Warning Icon.
 *
 * @since 4.21.4
 * @version 4.21.4
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__warning' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Warning icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M15.5467 11.6097L10.2923 2.50399C9.81884 1.68407 8.94406 1.17883 7.99708 1.17883C7.05013 1.17883 6.17536 1.68407 5.70188 2.50399L0.355106 11.7656C-0.118369 12.5855 -0.118369 13.596 0.355106 14.4159C0.828582 15.2359 1.70336 15.7411 2.65031 15.7411H13.3439C13.3468 15.7411 13.3497 15.7411 13.3497 15.7411C14.8134 15.7411 16 14.5545 16 13.0908C16 12.5422 15.8326 12.0312 15.5467 11.6097ZM9.13172 12.496C9.13172 13.1225 8.64958 13.6018 8 13.6018C7.35041 13.6018 6.86828 13.1225 6.86828 12.496V12.4701C6.86828 11.8464 7.35041 11.3643 8 11.3643C8.64958 11.3643 9.13172 11.8436 9.13172 12.4701V12.496ZM9.15771 5.0417L8.59762 9.93235C8.56009 10.2846 8.32624 10.504 8 10.504C7.67376 10.504 7.43991 10.2817 7.40238 9.93235L6.8423 5.03881C6.80476 4.66061 7.00977 4.37479 7.36196 4.37479H8.63515C8.98737 4.37768 9.19524 4.6635 9.15771 5.0417Z"/>

<?php
$this->template( 'components/icons/icon/end' );
