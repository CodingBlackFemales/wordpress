<?php
/**
 * View: Up caret icon.
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

$svg_classes = [ 'ld-svgicon__up-caret' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Up caret icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M19.6648 17.6338C19.2528 18.0844 18.6045 18.119 18.1562 17.7377L18.0485 17.6338L11.9995 11.0186L5.95049 17.6338C5.53851 18.0844 4.89024 18.119 4.44191 17.7377L4.33425 17.6338C3.92227 17.1832 3.89057 16.4741 4.23917 15.9838L4.33425 15.866L11.1914 8.36599C11.6034 7.91539 12.2516 7.88073 12.7 8.26201L12.8076 8.36599L19.6648 15.866C20.1111 16.3542 20.1111 17.1456 19.6648 17.6338Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
