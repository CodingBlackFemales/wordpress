<?php
/**
 * View: Visa logo.
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

$svg_classes = [ 'ld-svgicon__visa' ];

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
		'height'  => 24,
		'label'   => $label,
		'width'   => 40,
	],
);

?>

<g clip-path="url(#clip0_11282_39265)">
	<path d="M15.7756 5.72674L10.5028 18.1813H7.1392L4.59375 8.27219C4.41193 7.63583 4.32102 7.45401 3.77557 7.18129C3.0483 6.72674 1.77557 6.3631 0.59375 6.09038L0.684659 5.72674H6.1392C6.86648 5.72674 7.50284 6.18129 7.59375 6.99947L8.95739 14.1813L12.321 5.72674H15.7756ZM29.0483 14.0904C29.0483 10.8176 24.5028 10.6358 24.5028 9.18129C24.5028 8.72674 24.9574 8.27219 25.8665 8.18129C26.321 8.09038 27.5937 8.09038 29.0483 8.72674L29.5937 6.09038C28.8665 5.81765 27.8665 5.54492 26.5938 5.54492C23.4119 5.54492 21.1392 7.27219 21.1392 9.63583C21.1392 11.454 22.7756 12.454 23.9574 12.9995C25.2301 13.6358 25.5938 13.9995 25.5938 14.5449C25.5938 15.3631 24.5937 15.7267 23.6847 15.7267C22.0483 15.7267 21.1392 15.2722 20.4119 14.9086L19.8665 17.6358C20.5937 17.9995 21.9574 18.2722 23.4119 18.2722C26.7756 18.3631 29.0483 16.7267 29.0483 14.0904ZM37.4119 18.1813H40.4119L37.7756 5.72674H35.0483C34.4119 5.72674 33.8665 6.09038 33.6847 6.63583L28.8665 18.1813H32.2301L32.8665 16.3631H36.9574L37.4119 18.1813ZM33.8665 13.8176L35.5938 9.18129L36.5938 13.8176H33.8665ZM20.321 5.72674L17.6847 18.1813H14.5028L17.1392 5.72674H20.321Z" fill="#1434CB"/>
</g>
<defs>
	<clipPath id="clip0_11282_39265">
		<rect width="40" height="24" fill="white" transform="translate(0.5)"/>
	</clipPath>
</defs>

<?php
$this->template( 'components/icons/icon/end' );
