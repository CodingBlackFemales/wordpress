<?php
/**
 * View: Person icon.
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

$svg_classes = [ 'ld-svgicon__person' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Person icon', 'learndash' );
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

<path fill-rule="evenodd" clip-rule="evenodd" d="M10.0951 8.38188C10.0951 7.32991 10.9478 6.47712 11.9998 6.47712C13.0518 6.47712 13.9046 7.32991 13.9046 8.38188C13.9046 9.43385 13.0518 10.2866 11.9998 10.2866C10.9478 10.2866 10.0951 9.43385 10.0951 8.38188ZM11.9998 4.19141C9.68548 4.19141 7.80934 6.06755 7.80934 8.38188C7.80934 10.6962 9.68548 12.5724 11.9998 12.5724C14.3142 12.5724 16.1903 10.6962 16.1903 8.38188C16.1903 6.06755 14.3142 4.19141 11.9998 4.19141ZM8.95219 13.3343C7.84081 13.3343 6.77495 13.7758 5.98908 14.5616C5.20321 15.3475 4.76172 16.4134 4.76172 17.5247V19.0485C4.76172 19.6797 5.27339 20.1914 5.90458 20.1914C6.53576 20.1914 7.04743 19.6797 7.04743 19.0485V17.5247C7.04743 17.0196 7.24811 16.5351 7.60532 16.1779C7.96254 15.8207 8.44702 15.62 8.95219 15.62H15.0474C15.5526 15.62 16.0371 15.8207 16.3943 16.1779C16.7515 16.5351 16.9522 17.0196 16.9522 17.5247V19.0485C16.9522 19.6797 17.4639 20.1914 18.0951 20.1914C18.7262 20.1914 19.2379 19.6797 19.2379 19.0485V17.5247C19.2379 16.4134 18.7964 15.3475 18.0105 14.5616C17.2247 13.7758 16.1588 13.3343 15.0474 13.3343H8.95219Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
