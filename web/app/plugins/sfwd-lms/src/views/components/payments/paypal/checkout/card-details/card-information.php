<?php
/**
 * View: PayPal Checkout - Card Information.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-paypal-checkout__card-info">
	<h3 class="ld-paypal-checkout__block-title">
		<?php esc_html_e( 'Card information', 'learndash' ); ?>
	</h3>

	<div id="ld-paypal-checkout__card-name-field">
	</div>

	<div class="ld-paypal-checkout__card-fields">
		<div
			class="ld-paypal-checkout__card-field ld-paypal-checkout__card-field--large"
			id="ld-paypal-checkout__card-number-field"
		>
		</div>
		<div class="ld-paypal-checkout__card-fields-row">
			<div
				class="ld-paypal-checkout__card-field ld-paypal-checkout__card-field--small"
				id="ld-paypal-checkout__card-expiry-field"
			>
			</div>
			<div
				class="ld-paypal-checkout__card-field ld-paypal-checkout__card-field--small"
				id="ld-paypal-checkout__card-cvv-field"
			>
			</div>
		</div>
	</div>
</div>
