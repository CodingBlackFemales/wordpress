<?php
/**
 * View: PayPal Checkout Connect button.
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
<a
	href=""
	class="ld-paypal-checkout__button ld-paypal-checkout__button--connect"
	target="PPFrame"
	data-paypal-onboard-complete="ldPaypalCheckoutOnboardComplete"
	data-paypal-button="true"
>
	<span class="ld-paypal-checkout__logo">
		<?php $this::show_admin_template( 'modules/payments/gateways/paypal/white-logo' ); ?>
	</span>
	<span>
		<?php esc_html_e( 'Connect PayPal Checkout', 'learndash' ); ?>
	</span>
</a>
