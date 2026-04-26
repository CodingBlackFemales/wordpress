<?php
/**
 * View: Value Comparison Dashboard Widget.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget ld-dashboard-widget-value-comparison">
	<?php $this->template( 'dashboard/widgets/value-comparison/label' ); ?>

	<div class="ld-dashboard-widget-value-comparison__content">
		<?php $this->template( 'dashboard/widgets/value-comparison/values' ); ?>

		<?php $this->template( 'dashboard/widgets/value-comparison/difference' ); ?>
	</div>
</div>
