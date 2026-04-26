<?php
/**
 * View: PayPal Checkout Disconnect and Reconnect buttons.
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
<div class="ld-paypal-checkout__buttons">
	<button
		type="button"
		class="button button-primary ld-paypal-checkout__button ld-paypal-checkout__button--disconnect"
	>
		<span class="ld-paypal-checkout__logo">
			<?php $this::show_admin_template( 'modules/payments/gateways/paypal/white-logo' ); ?>
		</span>
		<span>
			<?php esc_html_e( 'Disconnect PayPal Checkout', 'learndash' ); ?>
		</span>
	</button>

	<button
		type="button"
		class="button button-secondary ld-paypal-checkout__button ld-paypal-checkout__button--reconnect"
	>
		<?php esc_html_e( 'Resync Payment Connection/Webhooks', 'learndash' ); ?>
	</button>
</div>
