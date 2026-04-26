<?php
/**
 * View: Values Dashboard Widget Item.
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
<div class="ld-dashboard-widget-values__item">
	<?php $this->template( 'dashboard/widgets/values/label' ); ?>

	<div class="ld-dashboard-widget-values__content">
		<?php $this->template( 'dashboard/widgets/values/value' ); ?>

		<?php $this->template( 'dashboard/widgets/values/sub-label' ); ?>
	</div>
</div>
