<?php
/**
 * View: Check 2 Icon.
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

$svg_classes = [ 'ld-svgicon__check-2' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Check icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M19.63 6.37658C20.1233 6.87868 20.1233 7.69275 19.63 8.19485L10.3669 17.6234C9.87358 18.1255 9.07379 18.1255 8.5805 17.6234L4.36997 13.3377C3.87668 12.8356 3.87668 12.0215 4.36997 11.5194C4.86326 11.0173 5.66305 11.0173 6.15635 11.5194L9.47368 14.896L17.8437 6.37658C18.3369 5.87447 19.1367 5.87447 19.63 6.37658Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
