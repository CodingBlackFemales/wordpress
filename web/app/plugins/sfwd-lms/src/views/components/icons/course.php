<?php
/**
 * View: Course icon.
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

$svg_classes = [ 'ld-svgicon__course' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = sprintf(
		// translators: %s: Course label.
		__( '%s icon', 'learndash' ),
		learndash_get_custom_label( 'course' )
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M3.81815 4C3.3663 4 3 4.35258 3 4.7875V16.6C3 17.0349 3.3663 17.3875 3.81815 17.3875H9.54516C9.97914 17.3875 10.3953 17.5534 10.7022 17.8488C11.0091 18.1442 11.1815 18.5448 11.1815 18.9625C11.1815 19.3974 11.5478 19.75 11.9996 19.75C12.4514 19.75 12.8185 19.3974 12.8185 18.9625C12.8185 18.5448 12.9909 18.1442 13.2978 17.8488C13.6047 17.5534 14.0209 17.3875 14.4548 17.3875H20.1819C20.6337 17.3875 21 17.0349 21 16.6V4.7875C21 4.35258 20.6337 4 20.1819 4H15.273C14.1881 4 13.1476 4.41484 12.3804 5.15327C12.2426 5.28594 12.1156 5.4271 12 5.57549C11.8844 5.4271 11.7574 5.28594 11.6196 5.15327C10.8524 4.41484 9.81195 4 8.72702 4H3.81815ZM11.1815 7.9375V16.2345C10.6882 15.9604 10.1246 15.8125 9.54516 15.8125H4.63629V5.575H8.72702C9.37798 5.575 10.0023 5.82391 10.4626 6.26696C10.9229 6.71001 11.1815 7.31093 11.1815 7.9375ZM14.4548 15.8125C13.8754 15.8125 13.3118 15.9604 12.8185 16.2345V7.9375C12.8185 7.31093 13.0771 6.71001 13.5374 6.26696C13.9977 5.82391 14.622 5.575 15.273 5.575H19.3637V15.8125H14.4548Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
