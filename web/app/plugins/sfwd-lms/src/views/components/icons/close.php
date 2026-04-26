<?php
/**
 * View: Close Icon
 *
 * @since 4.16.0
 * @version 4.21.0
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__close' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Close icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 16,
		'label'   => $label,
		'width'   => 17,
	],
);
?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M13.5653 2.93534C13.9244 3.29445 13.9244 3.87667 13.5653 4.23577L4.73772 13.0634C4.37862 13.4225 3.7964 13.4225 3.4373 13.0634C3.07819 12.7043 3.07819 12.122 3.4373 11.7629L12.2649 2.93534C12.624 2.57624 13.2062 2.57624 13.5653 2.93534Z"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M3.4373 2.93534C3.7964 2.57624 4.37862 2.57624 4.73772 2.93534L13.5653 11.7629C13.9244 12.122 13.9244 12.7043 13.5653 13.0634C13.2062 13.4225 12.624 13.4225 12.2649 13.0634L3.4373 4.23577C3.07819 3.87667 3.07819 3.29445 3.4373 2.93534Z"/>

<?php
$this->template( 'components/icons/icon/end' );
