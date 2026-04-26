<?php
/**
 * View: PayPal Checkout - Sandbox enabled alert.
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

if ( ! $gateway->is_sandbox_enabled() ) {
	return;
}

?>
<div class="ld-paypal-checkout__sandbox-enabled">
	<span class="ld-paypal-checkout__sandbox-enabled-icon dashicons dashicons-warning"></span>
	<div class="ld-paypal-checkout__sandbox-enabled-content">
		<div class="ld-paypal-checkout__sandbox-enabled-title">
			<?php esc_html_e( 'Sandbox Enabled', 'learndash' ); ?>
		</div>

		<div class="ld-paypal-checkout__sandbox-enabled-description">
			<?php
			printf(
				/* translators: %s: PayPal Testing Guide link */
				esc_html__( 'You can use PayPal sandbox accounts only. See the %s for details.', 'learndash' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://go.learndash.com/paypal/',
					esc_html__( 'PayPal Testing Guide', 'learndash' )
				)
			);
			?>
		</div>
	</div>
</div>
