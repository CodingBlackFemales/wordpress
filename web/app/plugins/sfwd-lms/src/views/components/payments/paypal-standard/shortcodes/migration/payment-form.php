<?php
/**
 * View: PayPal Standard - Migration Payment Form.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-paypal-standard__migration-payment-form">
	<?php $this->template( 'components/payments/paypal/checkout/card-details/saved-cards.php' ); ?>

	<?php $this->template( 'components/payments/paypal/checkout/card-details/card-information.php' ); ?>

	<?php $this->template( 'components/payments/paypal/checkout/card-details/billing-address.php' ); ?>
</div>
