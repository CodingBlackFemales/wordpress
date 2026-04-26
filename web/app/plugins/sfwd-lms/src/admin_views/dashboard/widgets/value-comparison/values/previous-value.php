<?php
/**
 * View: Value Comparison Dashboard Widget Previous Value.
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
?>
<span class="ld-dashboard-widget-value-comparison__prev-value">
	<?php
	printf(
		// translators: %d is the previous number.
		esc_html_x( 'from %d', 'It is a comparison against a current number and a previous one.', 'learndash' ),
		esc_attr( (string) $widget->get_previous_value() )
	);
	?>
</span>
