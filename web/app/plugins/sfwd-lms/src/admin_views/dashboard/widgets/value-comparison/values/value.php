<?php
/**
 * View: Value Comparison Dashboard Widget Value.
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
?>
<span class="ld-dashboard-widget-value-comparison__value">
	<?php echo esc_html( (string) $widget->get_value() ); ?>
</span>
