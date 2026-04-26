<?php
/**
 * View: Lock icon.
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

$svg_classes = [ 'ld-svgicon__lock' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Lock icon', 'learndash' );
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

<path d="M12.0801 4.28577C14.1987 4.28587 15.916 6.01301 15.916 8.14319V8.64319C15.9159 9.11645 15.5342 9.49963 15.0635 9.49963C14.5928 9.49955 14.2111 9.1164 14.2109 8.64319V8.14319C14.2109 6.95978 13.257 5.99974 12.0801 5.99963C10.903 5.99963 9.94824 6.95972 9.94824 8.14319V11.1432H15.916C17.0725 11.1432 17.9999 12.0904 18 13.2467V17.61C18 18.7663 17.0725 19.7145 15.916 19.7145H8.08398C6.9275 19.7145 6 18.7663 6 17.61V13.2467C6.00005 12.0904 6.92753 11.1432 8.08398 11.1432H8.24414V8.14319C8.24414 6.01295 9.96139 4.28577 12.0801 4.28577Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
