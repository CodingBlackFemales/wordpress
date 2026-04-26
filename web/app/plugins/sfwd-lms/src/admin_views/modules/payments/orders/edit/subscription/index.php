<?php
/**
 * View: Order Subscription.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Transaction $transaction Transaction object.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Template\Template;

$subscription = Subscription::create_from_transaction( $transaction );

if ( ! $subscription ) {
	return;
}

if ( $subscription->get_gateway_name() !== Payment_Gateway::get_name() ) {
	return; // Only PayPal subscriptions are supported for now.
}

?>
<div class="ld-order-subscription">
	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/subscription/details',
		[
			'subscription' => $subscription,
		]
	);
	?>

	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/subscription/charges',
		[
			'subscription' => $subscription,
		]
	);
	?>
</div>
