<?php
/**
 * View: Coupon Icon
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

$svg_classes = [ 'ld-svgicon__coupon' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Coupon icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 14,
		'label'   => $label,
		'width'   => 15,
	],
);
?>

<g clip-path="url(#clip0_7392_1933)">
	<path d="M9.83203 4.66406H9.8262M13.332 3.03073L13.332 5.64086C13.332 5.92622 13.332 6.0689 13.2998 6.20317C13.2712 6.32221 13.2241 6.43601 13.1601 6.54039C13.088 6.65813 12.9871 6.75902 12.7853 6.9608L8.31193 11.4342C7.6189 12.1272 7.27239 12.4737 6.87281 12.6035C6.52134 12.7177 6.14273 12.7177 5.79125 12.6035C5.39168 12.4737 5.04516 12.1272 4.35213 11.4342L3.06193 10.144C2.3689 9.45093 2.02239 9.10442 1.89256 8.70484C1.77836 8.35337 1.77836 7.97476 1.89256 7.62328C2.02239 7.22371 2.3689 6.87719 3.06193 6.18416L7.5353 1.7108C7.73708 1.50902 7.83796 1.40813 7.9557 1.33598C8.06008 1.27202 8.17389 1.22488 8.29293 1.1963C8.4272 1.16406 8.56987 1.16406 8.85523 1.16406L11.4654 1.16406C12.1188 1.16406 12.4455 1.16406 12.695 1.29122C12.9145 1.40307 13.093 1.58155 13.2049 1.80107C13.332 2.05064 13.332 2.37733 13.332 3.03073ZM9.54036 4.66406C9.54036 4.82515 9.67095 4.95573 9.83203 4.95573C9.99311 4.95573 10.1237 4.82515 10.1237 4.66406C10.1237 4.50298 9.99311 4.3724 9.83203 4.3724C9.67095 4.3724 9.54036 4.50298 9.54036 4.66406Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</g>
<defs>
	<clipPath id="clip0_7392_1933">
		<rect width="14" height="14" fill="white" transform="matrix(-1 0 0 1 14.5 0)"/>
	</clipPath>
</defs>

<?php
$this->template( 'components/icons/icon/end' );
