<?php
/**
 * View: Value Comparison Dashboard Widget Values.
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
<div class="ld-dashboard-widget-value-comparison__values">
	<?php $this->template( 'dashboard/widgets/value-comparison/values/value' ); ?>

	<?php $this->template( 'dashboard/widgets/value-comparison/values/previous-value' ); ?>
</div>
