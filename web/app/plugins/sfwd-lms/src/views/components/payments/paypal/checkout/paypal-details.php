<?php
/**
 * View: PayPal Checkout - 'PayPal' payment method details.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Payment_Gateway $gateway The payment gateway object.
 * @var Template        $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Template\Template;

// The 'PayPal' payment method does not have any details in production mode.

if ( ! $gateway->is_sandbox_enabled() ) {
	return;
}

?>
<div class="ld-paypal-checkout__details ld-paypal-checkout__details--paypal">
	<?php $this->template( 'components/payments/paypal/checkout/sandbox-enabled.php' ); ?>
</div>
