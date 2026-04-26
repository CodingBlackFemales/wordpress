<?php
/**
 * View: PayPal Checkout - Payment Options.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore paypalprivacy
 */

?>
<div class="ld-paypal-checkout__payment-options">
	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__save-payment-method">
			<input
				id="ld-paypal-checkout__save-payment-method"
				name="ld-paypal-checkout-save-payment-method"
				type="checkbox"
			/>
			<span class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'Save this payment method', 'learndash' ); ?>
			</span>
		</label>
	</div>

	<div class="ld-paypal-checkout__privacy-link">
		<a href="https://go.learndash.com/paypalprivacy" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'By paying with my card, I agree to the PayPal Privacy Statement.', 'learndash' ); ?>
		</a>
	</div>
</div>
