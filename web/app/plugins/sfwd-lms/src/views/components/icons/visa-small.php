<?php
/**
 * View: Visa logo small.
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

$svg_classes = [ 'ld-svgicon__visa-small' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Visa logo', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 17,
		'label'   => $label,
		'width'   => 26,
	],
);

?>

<path d="M9.92873 4.31018L6.50146 12.6132H4.31509L2.66055 6.00715C2.54237 5.5829 2.48327 5.46169 2.12873 5.27987C1.656 4.97684 0.828729 4.73442 0.0605469 4.5526L0.119638 4.31018H3.66509C4.13782 4.31018 4.55146 4.61321 4.61055 5.15866L5.49691 9.94654L7.68327 4.31018H9.92873ZM18.556 9.88593C18.556 7.70412 15.6015 7.5829 15.6015 6.61321C15.6015 6.31018 15.8969 6.00715 16.4878 5.94654C16.7833 5.88593 17.6105 5.88593 18.556 6.31018L18.9105 4.5526C18.4378 4.37078 17.7878 4.18896 16.9605 4.18896C14.8924 4.18896 13.4151 5.34048 13.4151 6.91624C13.4151 8.12836 14.4787 8.79503 15.2469 9.15866C16.0742 9.5829 16.3105 9.82533 16.3105 10.189C16.3105 10.7344 15.6605 10.9768 15.0696 10.9768C14.006 10.9768 13.4151 10.6738 12.9424 10.4314L12.5878 12.2496C13.0605 12.492 13.9469 12.6738 14.8924 12.6738C17.0787 12.7344 18.556 11.6435 18.556 9.88593ZM23.9924 12.6132H25.9424L24.2287 4.31018H22.456C22.0424 4.31018 21.6878 4.5526 21.5696 4.91624L18.4378 12.6132H20.6242L21.0378 11.4011H23.6969L23.9924 12.6132ZM21.6878 9.70412L22.8105 6.61321L23.4605 9.70412H21.6878ZM12.8833 4.31018L11.1696 12.6132H9.10145L10.8151 4.31018H12.8833Z" fill="#1434CB"/>

<?php
$this->template( 'components/icons/icon/end' );
