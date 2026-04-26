<?php
/**
 * View: Widget Empty.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Widget   $widget Widget.
 * @var Template $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Widget;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget__empty">
	<?php echo esc_html( $widget->get_empty_state_text() ); ?>
</div>
