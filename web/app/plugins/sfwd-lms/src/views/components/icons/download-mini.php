<?php
/**
 * View: Download mini icon.
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

$svg_classes = [ 'ld-svgicon__download-mini' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Download icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 17,
		'label'   => $label,
		'width'   => 16,
	],
);
?>

<path d="M13.4004 9.79004C13.7317 9.79004 14.0009 10.0583 14.001 10.3896V12.79C14.0009 13.2673 13.811 13.725 13.4736 14.0625C13.1361 14.4001 12.6776 14.5898 12.2002 14.5898H3.80078C3.32338 14.5898 2.86492 14.4001 2.52734 14.0625C2.18992 13.725 2.00006 13.2673 2 12.79V10.3896C2.00006 10.0583 2.26925 9.79004 2.60059 9.79004C2.9318 9.79018 3.20013 10.0584 3.2002 10.3896V12.79C3.20025 12.949 3.2636 13.1014 3.37598 13.2139C3.4885 13.3264 3.64165 13.3896 3.80078 13.3896H12.2002C12.3593 13.3896 12.5125 13.3264 12.625 13.2139C12.7373 13.1014 12.8007 12.949 12.8008 12.79V10.3896C12.8008 10.0584 13.0691 9.79012 13.4004 9.79004Z" fill="currentColor"/>
<path d="M8 2.58984C8.33124 2.58984 8.60037 2.85826 8.60059 3.18945V8.94043L10.5762 6.96582C10.8104 6.73155 11.1905 6.73164 11.4248 6.96582C11.6587 7.20005 11.6587 7.57923 11.4248 7.81348L8.4248 10.8145C8.39598 10.8433 8.36327 10.8681 8.3291 10.8906C8.27503 10.9262 8.21588 10.9496 8.15527 10.9658C8.14227 10.9693 8.12956 10.9749 8.11621 10.9775C8.03949 10.9926 7.96049 10.9927 7.88379 10.9775C7.86746 10.9743 7.85176 10.9684 7.83594 10.9639C7.77756 10.9472 7.72008 10.9244 7.66797 10.8896C7.63499 10.8677 7.60411 10.8424 7.57617 10.8145L4.57617 7.81348C4.34217 7.57921 4.34215 7.20007 4.57617 6.96582C4.81045 6.73155 5.19047 6.73164 5.4248 6.96582L7.40039 8.94141V3.18945C7.40061 2.85844 7.669 2.59013 8 2.58984Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
