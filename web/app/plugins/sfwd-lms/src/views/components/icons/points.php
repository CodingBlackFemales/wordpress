<?php
/**
 * View: Points icon.
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

$svg_classes = [ 'ld-svgicon__points' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}

if ( empty( $label ) ) {
	$label = __( 'Points icon', 'learndash' );
}

$this->template(
	'components/icons/icon/start',
	[
		'classes' => $svg_classes,
		'height'  => 17,
		'label'   => $label,
		'width'   => 16,
	],
);
?>

<path fill-rule="evenodd" clip-rule="evenodd" d="M7.99935 4.16797C5.60612 4.16797 3.66602 6.10807 3.66602 8.5013C3.66602 10.8945 5.60612 12.8346 7.99935 12.8346C10.3926 12.8346 12.3327 10.8945 12.3327 8.5013C12.3327 6.10807 10.3926 4.16797 7.99935 4.16797ZM7.99935 3.16797C5.05383 3.16797 2.66602 5.55578 2.66602 8.5013C2.66602 11.4468 5.05383 13.8346 7.99935 13.8346C10.9449 13.8346 13.3327 11.4468 13.3327 8.5013C13.3327 5.55578 10.9449 3.16797 7.99935 3.16797Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M8.55041 5.53085C8.32263 5.07826 7.6765 5.07826 7.44873 5.53085L6.76148 6.89641L5.43204 7.06151C4.94009 7.1226 4.71766 7.70976 5.04564 8.08147L5.98629 9.14755L5.72196 10.688C5.63674 11.1846 6.1508 11.5682 6.60265 11.3452L7.99957 10.6558L9.39649 11.3452C9.84834 11.5682 10.3624 11.1846 10.2772 10.688L10.0128 9.14755L10.9535 8.08147C11.2815 7.70976 11.059 7.1226 10.5671 7.06151L9.23765 6.89641L8.55041 5.53085ZM8.0565 6.82235C8.03188 6.77342 7.96203 6.77342 7.9374 6.82235L7.45061 7.78961C7.44061 7.80948 7.42136 7.82305 7.39928 7.8258L6.44255 7.9446C6.38937 7.95121 6.36532 8.01469 6.40078 8.05487L7.06294 8.80532C7.07629 8.82045 7.08207 8.84081 7.07866 8.8607L6.89439 9.93452C6.88518 9.98821 6.94075 10.0297 6.9896 10.0056L7.96745 9.52301C7.98605 9.51383 8.00786 9.51383 8.02646 9.52301L9.00431 10.0056C9.05315 10.0297 9.10873 9.98821 9.09951 9.93452L8.91525 8.8607C8.91184 8.84081 8.91762 8.82045 8.93097 8.80532L9.59313 8.05487C9.62858 8.01469 9.60454 7.95121 9.55135 7.9446L8.59463 7.8258C8.57255 7.82305 8.5533 7.80948 8.54329 7.78961L8.0565 6.82235Z" fill="currentColor"/>

<?php
$this->template( 'components/icons/icon/end' );
