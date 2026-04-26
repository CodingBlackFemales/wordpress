<?php
/**
 * View: Value Dashboard Widget Value.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Value    $widget Widget.
 * @var Template $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Value;
use LearnDash\Core\Template\Template;
?>
<span class="ld-dashboard-widget-value__value">
	<?php echo esc_html( (string) $widget->get_value() ); ?>
</span>
