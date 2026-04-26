<?php
/**
 * View: Value Comparison Dashboard Widget Difference Icon.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Value_Comparison $widget Widget.
 * @var Template         $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Value_Comparison;
use LearnDash\Core\Template\Template;

$percentage_difference = $widget->get_percentage_difference();

$percentage_difference_icon_class = 'ld-dashboard-widget-value-comparison__difference__icon ';

if ( $percentage_difference > 0 ) {
	$percentage_difference_icon_class .= 'ld-dashboard-widget-value-comparison__difference__icon--positive';
} elseif ( $percentage_difference < 0 ) {
	$percentage_difference_icon_class .= 'ld-dashboard-widget-value-comparison__difference__icon--negative';
}
?>
<svg class="<?php echo esc_attr( $percentage_difference_icon_class ); ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
	<path
		fill-rule="evenodd"
		d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z"
		clip-rule="evenodd"
	/>
</svg>
