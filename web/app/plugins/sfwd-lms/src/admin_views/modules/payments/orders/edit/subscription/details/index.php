<?php
/**
 * View: Order Subscription Details.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription Subscription object.
 * @var Template     $this         Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Template\Template;
?>
<div class="ld-order-items__table" role="table">
	<?php $this->show_admin_template( 'modules/payments/orders/edit/subscription/details/table-head' ); ?>

	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/subscription/details/table-body',
		[
			'subscription' => $subscription,
		]
	);
	?>
</div>
