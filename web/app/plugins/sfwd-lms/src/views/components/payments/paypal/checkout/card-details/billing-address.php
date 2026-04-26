<?php
/**
 * View: PayPal Checkout - Billing Address.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var array<string, string> $countries List of countries with their codes.
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-paypal-checkout__billing-address">
	<h3 class="ld-paypal-checkout__block-title">
		<?php esc_html_e( 'Billing address', 'learndash' ); ?>
	</h3>

	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__billing-address-1">
			<div class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'Address line 1*', 'learndash' ); ?>
			</div>

			<input
				id="ld-paypal-checkout__billing-address-1"
				name="ld-paypal-checkout__billing-address-1"
				required
				type="text"
			/>
		</label>
	</div>

	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__billing-address-2">
			<div class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'Address line 2', 'learndash' ); ?>
			</div>
		</label>

		<input
			id="ld-paypal-checkout__billing-address-2"
			name="ld-paypal-checkout__billing-address-2"
			type="text"
		/>
	</div>

	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__billing-city">
			<div class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'City', 'learndash' ); ?>
			</div>
		</label>

		<input
			id="ld-paypal-checkout__billing-city"
			name="ld-paypal-checkout__billing-city"
			required
			type="text"
		/>
	</div>

	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__billing-postal">
			<div class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'Postal/zip code*', 'learndash' ); ?>
			</div>

			<input
				id="ld-paypal-checkout__billing-postal"
				name="ld-paypal-checkout__billing-postal"
				required
				type="text"
			/>
		</label>
	</div>

	<div class="ld-paypal-checkout__billing-field-wrapper">
		<label for="ld-paypal-checkout__billing-country">
			<div class="ld-paypal-checkout__billing-field-label">
				<?php esc_html_e( 'Country*', 'learndash' ); ?>
			</div>

			<select
				id="ld-paypal-checkout__billing-country"
				name="ld-paypal-checkout__billing-country"
				required
			>
				<?php foreach ( $countries as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>
</div>
