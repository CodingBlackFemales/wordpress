<?php
/**
 * View: Value Comparison Dashboard Widget Difference.
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

if ( $widget->get_previous_value() <= 0 ) {
	return;
}

$percentage_difference = $widget->get_percentage_difference();

$percentage_difference_class = 'ld-dashboard-widget-value-comparison__difference ';

if ( $percentage_difference > 0 ) {
	$percentage_difference_class .= 'ld-dashboard-widget-value-comparison__difference--positive';
} elseif ( $percentage_difference < 0 ) {
	$percentage_difference_class .= 'ld-dashboard-widget-value-comparison__difference--negative';
}
?>
<div class="<?php echo esc_attr( $percentage_difference_class ); ?>">
	<?php $this->template( 'dashboard/widgets/value-comparison/difference/icon' ); ?>

	<?php $this->template( 'dashboard/widgets/value-comparison/difference/number' ); ?>
</div>
