<?php
/**
 * View: Transactions Dashboard Widget Item.
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
<div class="ld-dashboard-widget-transactions__item">
	<?php $this->template( 'dashboard/widgets/transactions/pricing' ); ?>

	<?php $this->template( 'dashboard/widgets/transactions/coupon' ); ?>

	<?php $this->template( 'dashboard/widgets/transactions/user' ); ?>

	<?php $this->template( 'dashboard/widgets/transactions/date' ); ?>
</div>
