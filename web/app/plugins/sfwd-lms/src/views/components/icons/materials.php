<?php
/**
 * View: Materials icon.
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

$svg_classes = [ 'ld-svgicon__materials' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Materials icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 4C6.28587 4 5.84075 4.17744 5.51256 4.49329C5.18438 4.80914 5 5.23753 5 5.68421V18.3158C5 18.7625 5.18437 19.1909 5.51256 19.5067C5.84075 19.8226 6.28587 20 6.75 20H17.25C17.7141 20 18.1592 19.8226 18.4874 19.5067C18.8156 19.1909 19 18.7625 19 18.3158V5.68421C19 5.23753 18.8156 4.80915 18.4874 4.49329C18.1592 4.17744 17.7141 4 17.25 4H6.75ZM8.49984 5.68421H6.75L6.75 18.3158H17.25L17.25 5.68421H13.7498V11.5789C13.7498 11.9195 13.5367 12.2266 13.2097 12.357C12.8827 12.4873 12.5064 12.4152 12.2561 12.1744L11.1248 11.0857L9.99356 12.1744C9.74331 12.4152 9.36696 12.4873 9.03999 12.357C8.71303 12.2266 8.49984 11.9195 8.49984 11.5789V5.68421ZM11.9998 5.68421H10.2498V9.54593L10.5061 9.29928C10.8478 8.97042 11.4018 8.97042 11.7436 9.29928L11.9998 9.54593V5.68421Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
