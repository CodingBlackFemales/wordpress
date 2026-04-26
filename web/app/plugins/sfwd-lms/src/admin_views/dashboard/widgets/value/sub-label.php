<?php
/**
 * View: Value Dashboard Widget Sub Label.
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

if ( empty( $widget->get_sub_label() ) ) {
	return;
}
?>
<span class="ld-dashboard-widget-value__sub-label">
	<?php echo esc_html( $widget->get_sub_label() ); ?>
</span>
