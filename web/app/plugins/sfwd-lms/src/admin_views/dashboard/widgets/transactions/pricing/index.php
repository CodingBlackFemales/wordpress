<?php
/**
 * View: Transactions Dashboard Widget Pricing.
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
<div class="ld-dashboard-widget-transactions__column">
	<?php $this->template( 'dashboard/widgets/transactions/pricing/price' ); ?>

	<?php $this->template( 'dashboard/widgets/transactions/pricing/discount' ); ?>

	<?php $this->template( 'dashboard/widgets/transactions/pricing/trial' ); ?>
</div>
