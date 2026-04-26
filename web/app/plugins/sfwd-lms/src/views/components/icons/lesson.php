<?php
/**
 * View: Lesson icon.
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

$svg_classes = [ 'ld-svgicon__lesson' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Lesson label.
		__( '%s icon', 'learndash' ),
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

<path d="M14.625 14.9473C15.1081 14.9473 15.4998 15.3241 15.5 15.7891C15.5 16.2541 15.1082 16.6318 14.625 16.6318H9.375C8.89175 16.6318 8.5 16.2541 8.5 15.7891C8.50019 15.3241 8.89187 14.9473 9.375 14.9473H14.625Z" fill="currentColor"/>
<path d="M14.625 11.5791C15.1082 11.5791 15.5 11.9559 15.5 12.4209C15.5 12.886 15.1082 13.2627 14.625 13.2627H9.375C8.89175 13.2627 8.5 12.886 8.5 12.4209C8.50004 11.9559 8.89178 11.5791 9.375 11.5791H14.625Z" fill="currentColor"/>
<path d="M14.625 8.21094C15.1082 8.21094 15.5 8.58765 15.5 9.05273C15.4999 9.51773 15.1082 9.89453 14.625 9.89453H9.375C8.89181 9.89453 8.5001 9.51773 8.5 9.05273C8.5 8.58765 8.89175 8.21094 9.375 8.21094H14.625Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M17.25 4C17.7141 4 18.1591 4.17739 18.4873 4.49316C18.8155 4.80902 19 5.23789 19 5.68457V18.3154C19 18.7621 18.8155 19.191 18.4873 19.5068C18.1591 19.8226 17.7141 20 17.25 20H6.75C6.28594 20 5.84087 19.8226 5.5127 19.5068C5.18451 19.191 5 18.7621 5 18.3154V5.68457C5 5.23789 5.18451 4.80901 5.5127 4.49316C5.84087 4.17739 6.28593 4 6.75 4H17.25ZM6.75 18.3154H17.25V5.68457H6.75V18.3154Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
