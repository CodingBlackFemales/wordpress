<?php
/**
 * View: Quiz complete icon.
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

$svg_classes = [ 'ld-svgicon__quiz-complete' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Quiz label.
		__( '%s complete icon', 'learndash' ),
		learndash_get_custom_label( 'quiz' )
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

<path d="M11.1689 4.7998C11.0583 5.29406 11 5.8083 11 6.33594V6.40039H6.75V18.4004H17.25V13.2959C17.4964 13.3221 17.7466 13.3359 18 13.3359C18.3395 13.3359 18.6734 13.3114 19 13.2646V18.4004C19 18.4533 18.9969 18.5064 18.9912 18.5586C18.9513 18.9247 18.7744 19.2688 18.4873 19.5312C18.1591 19.8313 17.7141 20 17.25 20H6.75C6.28587 20 5.84089 19.8313 5.5127 19.5312C5.1846 19.2313 5.0001 18.8246 5 18.4004V6.40039C5 5.97605 5.1845 5.56861 5.5127 5.26855C5.84087 4.96857 6.28594 4.79981 6.75 4.7998H11.1689Z" fill="currentColor"/>
<path d="M13.6426 12.9561C13.8958 12.6814 14.3152 12.6813 14.5684 12.9561C14.8107 13.2191 14.8107 13.6383 14.5684 13.9014L11.6738 17.0439C11.4207 17.3188 11.0002 17.3188 10.7471 17.0439L9.43164 15.6152C9.18948 15.3523 9.18962 14.934 9.43164 14.6709C9.68482 14.396 10.1042 14.396 10.3574 14.6709L11.2109 15.5967L13.6426 12.9561Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M18.001 0.333984C21.3147 0.333984 24.001 3.02028 24.001 6.33398C24.001 9.64769 21.3147 12.334 18.001 12.334C14.6873 12.334 12.001 9.64769 12.001 6.33398C12.001 3.02028 14.6873 0.333984 18.001 0.333984ZM21.127 4.24414C20.8359 3.9479 20.3614 3.9479 20.0703 4.24414L17.0254 7.34277L15.9297 6.22754C15.6387 5.93133 15.1651 5.93138 14.874 6.22754C14.5855 6.52125 14.5855 6.99535 14.874 7.28906L16.498 8.94238C16.7891 9.23832 17.2628 9.23848 17.5537 8.94238L21.127 5.30566C21.4153 5.0121 21.415 4.53785 21.127 4.24414Z" fill="currentColor"/>
<path d="M9.43164 9.6709C9.68482 9.39602 10.1042 9.39602 10.3574 9.6709L11.2109 10.5967L11.9268 9.81934C12.1522 10.2116 12.4145 10.5802 12.709 10.9199L11.6738 12.0439C11.4207 12.3188 11.0002 12.3188 10.7471 12.0439L9.43164 10.6152C9.18951 10.3522 9.1896 9.93396 9.43164 9.6709Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
