<?php
/**
 * View: Value Comparison Dashboard Widget Difference Number.
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
<span class="ld-dashboard-widget-value-comparison__difference__number">
	<?php
	printf(
		// Translators: placeholder: Percentage difference.
		esc_html_x( '%1$s%%', 'placeholder: Percentage difference', 'learndash' ),
		esc_html( (string) absint( $widget->get_percentage_difference() ) )
	);
	?>
</span>
