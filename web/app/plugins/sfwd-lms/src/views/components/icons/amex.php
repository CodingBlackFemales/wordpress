<?php
/**
 * View: Amex logo.
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

$svg_classes = [ 'ld-svgicon__amex' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Amex logo', 'learndash' );
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

<g clip-path="url(#clip0_11282_39286)">
	<path d="M38.833 24H2.167C1.25 24 0.5 23.28 0.5 22.4V1.6C0.5 0.72 1.25 0 2.167 0H38.833C39.75 0 40.5 0.72 40.5 1.6V22.4C40.5 23.28 39.75 24 38.833 24Z" fill="white"/>
	<path d="M6.76562 12.3194H9.07862L7.92062 9.65939M27.8586 9.97639H24.1206V11.2064H27.7866V12.5904H24.1116V13.9754H27.9326V14.9804C28.5556 14.2104 29.2626 13.5144 29.9576 12.7454L30.6646 11.9754C29.7306 10.9714 28.7946 9.89539 27.8606 8.90039V9.97739L27.8586 9.97639Z" fill="#1478BE"/>
	<path d="M38.75 7H33.145L31.817 8.4L30.572 7H17.484L16.467 9.416L15.377 7H5.797L1.75 16.5H6.576L7.199 14.944H8.599L9.222 16.5H30.49L31.817 15.017L33.145 16.5H38.75L34.39 11.833L38.75 7ZM21.065 15.1H19.508V9.883L17.173 15.1H15.843L13.51 9.883L13.426 15.1H10.23L9.607 13.544H6.337L5.632 15.1H3.92L6.804 8.328H9.224L11.869 14.561V8.33H14.515L16.622 12.84L18.49 8.33H21.065V15.1ZM35.792 15.1H33.768L31.744 12.84L29.721 15.1H22.56V8.328H30.03L31.825 10.505L33.849 8.328H35.874L32.76 11.75L35.792 15.1Z" fill="#1478BE"/>
</g>
<defs>
	<clipPath id="clip0_11282_39286">
		<rect width="40" height="24" fill="white" transform="translate(0.5)"/>
	</clipPath>
</defs>
</svg>

<?php
$this->template( 'components/icons/icon/end' );
