<?php
/**
 * View: Amex logo small.
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

$svg_classes = [ 'ld-svgicon__amex-small' ];

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
		'height'  => 17,
		'label'   => $label,
		'width'   => 26,
	],
);

?>

<g clip-path="url(#clip0_16085_26081)">
	<path d="M24.9164 16.4766H1.08355C0.4875 16.4766 0 15.9966 0 15.4099V1.54323C0 0.956563 0.4875 0.476562 1.08355 0.476562H24.9164C25.5125 0.476562 26 0.956563 26 1.54323V15.4099C26 15.9966 25.5125 16.4766 24.9164 16.4766Z" fill="white"/>
	<path d="M4.06738 8.68949H5.57083L4.81813 6.91616M17.7778 7.12749H15.3481V7.94749H17.731V8.87016H15.3423V9.79349H17.8259V10.4635C18.2309 9.95016 18.6904 9.48616 19.1422 8.97349L19.6017 8.46016C18.9946 7.79082 18.3862 7.07349 17.7791 6.41016V7.12816L17.7778 7.12749Z" fill="#1478BE"/>
	<path d="M24.8625 5.14307H21.2192L20.356 6.0764L19.5468 5.14307H11.0396L10.3785 6.75373L9.67005 5.14307H3.44305L0.8125 11.4764H3.9494L4.35435 10.4391H5.26435L5.6693 11.4764H19.4935L20.356 10.4877L21.2192 11.4764H24.8625L22.0285 8.36507L24.8625 5.14307ZM13.3672 10.5431H12.3552V7.06507L10.8375 10.5431H9.97295L8.4565 7.06507L8.4019 10.5431H6.3245L5.91955 9.50573H3.79405L3.3358 10.5431H2.223L4.0976 6.0284H5.6706L7.38985 10.1837V6.02973H9.10975L10.4793 9.0364L11.6935 6.02973H13.3672V10.5431ZM22.9398 10.5431H21.6242L20.3086 9.0364L18.9937 10.5431H14.339V6.0284H19.1945L20.3612 7.47973L21.6768 6.0284H22.9931L20.969 8.30973L22.9398 10.5431Z" fill="#1478BE"/>
</g>
<defs>
	<clipPath id="clip0_16085_26081">
		<rect width="26" height="16" fill="white" transform="translate(0 0.476562)"/>
	</clipPath>
</defs>

<?php
$this->template( 'components/icons/icon/end' );
