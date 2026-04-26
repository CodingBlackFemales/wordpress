<?php
/**
 * View: PayPal Checkout - 'Credit Card' payment method details.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<div class="ld-paypal-checkout__details ld-paypal-checkout__details--card">
	<?php $this->template( 'components/payments/paypal/checkout/sandbox-enabled.php' ); ?>

	<div class="ld-paypal-checkout__card-details-main">
		<?php
		$this->template( 'components/payments/paypal/checkout/card-details/card-logos.php' );

		$this->template( 'components/payments/paypal/checkout/card-details/saved-cards.php' );

		$this->template( 'components/payments/paypal/checkout/card-details/card-information.php' );

		$this->template( 'components/payments/paypal/checkout/card-details/billing-address.php' );

		$this->template( 'components/payments/paypal/checkout/card-details/payment-options.php' );
		?>
	</div>
</div>
