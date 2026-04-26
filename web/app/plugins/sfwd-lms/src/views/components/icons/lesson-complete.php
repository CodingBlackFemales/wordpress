<?php
/**
 * View: Lesson complete icon.
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

$svg_classes = [ 'ld-svgicon__lesson-complete' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Lesson label.
		__( '%s complete icon', 'learndash' ),
		learndash_get_custom_label( 'lesson' )
	);
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

<path d="M11.3994 4C11.2102 4.53456 11.0843 5.09931 11.0303 5.68457H6.75V18.3154H17.25V13.2959C17.4964 13.3221 17.7466 13.3359 18 13.3359C18.3395 13.3359 18.6734 13.3114 19 13.2646V18.3154C19 18.7621 18.8155 19.191 18.4873 19.5068C18.1591 19.8226 17.7141 20 17.25 20H6.75C6.28594 20 5.84087 19.8226 5.5127 19.5068C5.18451 19.191 5 18.7621 5 18.3154V5.68457C5 5.23789 5.18451 4.80901 5.5127 4.49316C5.84087 4.17739 6.28593 4 6.75 4H11.3994Z" fill="currentColor"/>
<path d="M14.625 14.9473C15.1081 14.9473 15.4998 15.3242 15.5 15.7891C15.5 16.2541 15.1082 16.6318 14.625 16.6318H9.375C8.89186 16.6317 8.5 16.2541 8.5 15.7891C8.50022 15.3242 8.892 14.9474 9.375 14.9473H14.625Z" fill="currentColor"/>
<path d="M13.3623 11.5791C13.9565 12.105 14.6405 12.532 15.3887 12.833C15.2388 13.0897 14.9528 13.2627 14.625 13.2627H9.375C8.89186 13.2626 8.5 12.8859 8.5 12.4209C8.50008 11.956 8.89191 11.5792 9.375 11.5791H13.3623Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M18.001 0.333984C21.3147 0.333984 24.001 3.02028 24.001 6.33398C24.001 9.64769 21.3147 12.334 18.001 12.334C14.6873 12.334 12.001 9.64769 12.001 6.33398C12.001 3.02028 14.6873 0.333984 18.001 0.333984ZM21.127 4.24414C20.8359 3.9479 20.3614 3.9479 20.0703 4.24414L17.0254 7.34277L15.9297 6.22754C15.6387 5.93133 15.1651 5.93138 14.874 6.22754C14.5855 6.52125 14.5855 6.99535 14.874 7.28906L16.498 8.94238C16.7891 9.23832 17.2628 9.23848 17.5537 8.94238L21.127 5.30566C21.4153 5.0121 21.415 4.53785 21.127 4.24414Z" fill="currentColor"/>
<path d="M11.2539 8.21094C11.4195 8.80801 11.6622 9.37302 11.9707 9.89453H9.375C8.8919 9.89441 8.50007 9.51768 8.5 9.05273C8.5 8.58773 8.89186 8.21106 9.375 8.21094H11.2539Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
