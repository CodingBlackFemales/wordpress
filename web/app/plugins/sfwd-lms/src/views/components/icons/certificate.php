<?php
/**
 * View: Certificate icon.
 *
 * @since 4.21.0
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

$svg_classes = [ 'ld-svgicon__certificate' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Certificate label.
		__( '%s icon', 'learndash' ),
		learndash_get_custom_label( 'certificate' )
	);
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 18,
		'label'   => $label,
		'width'   => 19,
	],
);
?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M5.86364 7.31935C5.86364 5.56951 7.26783 4.15099 9 4.15099C10.7322 4.15099 12.1364 5.56951 12.1364 7.31935C12.1364 8.416 11.5848 9.38252 10.7467 9.95128C10.713 9.96942 10.6811 9.99028 10.6513 10.0135C10.1717 10.3141 9.60591 10.4877 9 10.4877C7.26783 10.4877 5.86364 9.06919 5.86364 7.31935ZM10.5163 11.6007C10.0425 11.772 9.53205 11.8653 9 11.8653C8.4682 11.8653 7.95794 11.7721 7.48436 11.601L7.1349 14.2589L8.64926 13.341C8.86518 13.2101 9.13492 13.2101 9.35084 13.341L10.8654 14.259L10.5163 11.6007ZM6.2034 10.881C5.16552 10.0483 4.5 8.76232 4.5 7.31935C4.5 4.80871 6.51472 2.77344 9 2.77344C11.4853 2.77344 13.5 4.80871 13.5 7.31935C13.5 8.762 12.8348 10.0477 11.7973 10.8805L12.4032 15.4941C12.4376 15.7562 12.3204 16.0152 12.1016 16.1604C11.8828 16.3056 11.601 16.3114 11.3765 16.1753L9.00005 14.7348L6.62357 16.1753C6.39907 16.3114 6.11726 16.3056 5.89843 16.1603C5.67961 16.0151 5.56242 15.7562 5.59689 15.494L6.2034 10.881Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
