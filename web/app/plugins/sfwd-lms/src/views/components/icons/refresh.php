<?php
/**
 * View: Refresh Icon.
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

$svg_classes = [ 'ld-svgicon__refresh' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Refresh icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 18,
		'label'   => $label,
		'width'   => 18,
	],
);
?>

<path d="M9.16674 3C10.2133 3 11.1784 3.23256 12.0621 3.69767C12.9458 4.16279 13.6784 4.81395 14.2598 5.65116C14.8412 6.46512 15.2016 7.37209 15.3412 8.37209H17.2249L14.0853 11.9302L10.9458 8.37209H13.0388C12.8295 7.46512 12.3644 6.72093 11.6435 6.13953C10.9226 5.55814 10.097 5.26744 9.16674 5.26744C8.53884 5.26744 7.94581 5.4186 7.38767 5.72093C6.82953 6 6.36442 6.38372 5.99232 6.87209L4.49232 5.12791C5.07372 4.45349 5.77139 3.93023 6.58535 3.55814C7.3993 3.18605 8.25977 3 9.16674 3ZM8.81791 15C7.79465 15 6.82953 14.7674 5.92256 14.3023C5.03884 13.8372 4.30628 13.1977 3.72488 12.3837C3.14349 11.5465 2.78302 10.6279 2.64349 9.62791H0.759766L3.8993 6.06977L7.03884 9.62791H4.94581C5.15511 10.5349 5.62023 11.2791 6.34116 11.8605C7.06209 12.4419 7.88767 12.7326 8.81791 12.7326C9.44581 12.7326 10.0388 12.593 10.597 12.314C11.1551 12.0116 11.6202 11.6163 11.9923 11.1279L13.4923 12.8721C12.9109 13.5465 12.2133 14.0698 11.3993 14.4419C10.5853 14.814 9.72488 15 8.81791 15Z"/>

<?php
$this->template( 'components/icons/icon/end' );
