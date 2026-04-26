<?php
/**
 * View: Quiz icon.
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

$svg_classes = [ 'ld-svgicon__quiz' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Quiz label.
		__( '%s icon', 'learndash' ),
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M18.9914 18.5581C18.9517 18.9244 18.7746 19.2689 18.4874 19.5314C18.1592 19.8315 17.7141 20 17.25 20H6.75C6.28587 20 5.84075 19.8315 5.51256 19.5314C5.18437 19.2314 5 18.8244 5 18.4001V6.40009C5 5.97575 5.18437 5.56873 5.51256 5.26868C5.84075 4.96862 6.28587 4.80005 6.75 4.80005L17.25 4.80005C17.7141 4.80005 18.1592 4.96862 18.4874 5.26868C18.7655 5.52288 18.9403 5.8538 18.9872 6.20702C18.9957 6.27074 19 6.33519 19 6.40004L19 12.4V18.4M6.75 6.40009C6.75332 6.40009 6.75663 6.40008 6.75994 6.40004L17.2402 6.40004C17.2434 6.40008 17.2467 6.40009 17.25 6.40009C17.25 6.40009 17.25 10.0569 17.25 12.4C17.25 14.7432 17.25 18.4 17.25 18.4C17.2467 18.4 17.2434 18.4 17.2402 18.4001H6.75994C6.75663 18.4 6.75332 18.4 6.75 18.4L6.75 12.8L6.75 6.40009ZM19 18.4C19 18.453 18.9971 18.5058 18.9914 18.5581Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M14.5683 7.95616C14.3151 7.68128 13.8954 7.68128 13.6423 7.95616L11.2105 10.5963L10.3577 9.67044C10.1046 9.39557 9.6849 9.39557 9.43173 9.67044C9.18942 9.93352 9.18942 10.3522 9.43173 10.6153L10.7475 12.0438C11.0007 12.3187 11.4204 12.3187 11.6735 12.0438L14.5683 8.90099C14.8106 8.63791 14.8106 8.21923 14.5683 7.95616Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M14.5683 12.9562C14.3151 12.6813 13.8954 12.6813 13.6423 12.9562L11.2105 15.5963L10.3577 14.6704C10.1046 14.3956 9.6849 14.3956 9.43173 14.6704C9.18942 14.9335 9.18942 15.3522 9.43173 15.6153L10.7475 17.0438C11.0007 17.3187 11.4204 17.3187 11.6735 17.0438L14.5683 13.901C14.8106 13.6379 14.8106 13.2192 14.5683 12.9562Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
