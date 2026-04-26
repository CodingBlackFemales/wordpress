<?php
/**
 * View: Computer icon.
 *
 * @since 4.21.0
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

$svg_classes = [ 'ld-svgicon__computer' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Computer icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 25,
		'label'   => $label,
		'width'   => 24,
	],
);

?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M6.43478 6.97368C6.24268 6.97368 6.08696 7.1294 6.08696 7.3215V14.278C6.08696 14.4701 6.24268 14.6258 6.43478 14.6258H11.9858C11.9905 14.6258 11.9953 14.6258 12 14.6258C12.0048 14.6258 12.0095 14.6258 12.0143 14.6258H17.5652C17.7573 14.6258 17.913 14.4701 17.913 14.278V7.3215C17.913 7.1294 17.7573 6.97368 17.5652 6.97368H6.43478ZM13.0435 16.7128H17.5652C18.9099 16.7128 20 15.6227 20 14.278V7.3215C20 5.97681 18.9099 4.88672 17.5652 4.88672H6.43478C5.09009 4.88672 4 5.97681 4 7.3215V14.278C4 15.6227 5.09009 16.7128 6.43478 16.7128H10.9565V17.4085H9.21751C8.64122 17.4085 8.17404 17.8757 8.17404 18.452C8.17404 19.0283 8.64122 19.4955 9.21751 19.4955H14.7827C15.359 19.4955 15.8262 19.0283 15.8262 18.452C15.8262 17.8757 15.359 17.4085 14.7827 17.4085H13.0435V16.7128Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
