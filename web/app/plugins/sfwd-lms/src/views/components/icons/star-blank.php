<?php
/**
 * View: Star with blank fill icon.
 *
 * @since 4.25.1
 * @version 4.25.1
 *
 * @var string[] $classes        Additional classes to add to the svg icon.
 * @var string   $label          The label for the icon.
 * @var bool     $is_aria_hidden Whether the icon is hidden from screen readers. Default false to show the icon.
 * @var Template $this           The template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$svg_classes = [ 'ld-svgicon__star-blank' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Star with blank fill icon', 'learndash' );
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

<path d="M12.4467 5.10335C12.6314 4.73638 13.1556 4.73638 13.3403 5.10335L15.6391 9.67073C15.7141 9.81961 15.8584 9.92165 16.0239 9.94222L20.5122 10.4989C20.911 10.5484 21.0915 11.0246 20.8256 11.326L17.7036 14.8651C17.6036 14.9785 17.5598 15.131 17.5854 15.2801L18.4584 20.366C18.5273 20.7686 18.1109 21.079 17.7446 20.8983L13.1147 18.6141C12.9753 18.5454 12.8117 18.5453 12.6723 18.6141L8.04341 20.8983C7.67714 21.079 7.25974 20.7686 7.32857 20.366L8.20161 15.2801C8.22721 15.1309 8.18358 14.9786 8.08345 14.8651L4.96138 11.326C4.69582 11.0246 4.87614 10.5484 5.27485 10.4989L9.76314 9.94222C9.92871 9.92166 10.0739 9.81976 10.1489 9.67073L12.4467 5.10335ZM11.1743 11.4188L7.75337 11.8436L10.101 14.5047L9.45259 18.285L12.8969 16.5848L16.3413 18.285L15.6928 14.5047L18.0405 11.8436L14.6186 11.4188L12.8969 7.99691L11.1743 11.4188Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
