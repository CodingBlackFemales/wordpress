<?php
/**
 * View: Reports Widget.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @var ProPanel2_Widget $widget Widget.
 * @var Template         $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\Reports\Dashboard\Widgets\Types\ProPanel2_Widget;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget__reports-propanel2-widget ld-dashboard-widget__reports-propanel2-widget--<?php echo esc_attr( $widget->get_name() ); ?>">
	<div class="metabox-holder">
		<div class="postbox">
			<div class="postbox-header">
				<h2><?php echo esc_html( $widget->get_label() ); ?></h2>
			</div>
			<div class="inside">
				<?php echo $widget->get_initial_template(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within the method. ?>
			</div>
		</div>
	</div>
</div>
