<?php
/**
 * View: Value Dashboard Widget.
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
<div class="ld-dashboard-widget ld-dashboard-widget-value">
	<?php $this->template( 'dashboard/widgets/value/label' ); ?>

	<div class="ld-dashboard-widget-value__content">
		<?php $this->template( 'dashboard/widgets/value/value' ); ?>

		<?php $this->template( 'dashboard/widgets/value/sub-label' ); ?>
	</div>
</div>
