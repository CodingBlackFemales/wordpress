<?php
/**
 * Registration - Checkout button gateway details.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var string                          $button_html            Button HTML.
 * @var string                          $button_key             Button key.
 * @var array<string, string>           $buttons                Checkout buttons.
 * @var array<string, string|int|float> $default_payment_params Default payment params for checkout.
 * @var string                          $selected_payment       Selected payment.
 * @var string                          $product_type           Product type.
 * @var Template                        $this                   The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Sanitize;

$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_button_key( $button_key );

if ( $gateway instanceof Learndash_Unknown_Gateway ) {
	return;
}

$is_selected = $button_key === $selected_payment;

if ( ! $is_selected ) {
	return;
}

$checkout_data = $gateway->get_checkout_data_for_button_key( $button_key );

$gateway_details_html = Cast::to_string( $checkout_data['gateway_details_html'] ?? '' );

if ( ! $gateway_details_html ) {
	return;
}

$label     = Cast::to_string( $checkout_data['label'] ?? '' );
$meta_html = Cast::to_string( $checkout_data['meta_html'] ?? '' );
?>
<div class="ld-registration-order__checkout-button-gateway-details-container">
	<div class="ld-registration-order__checkout-button-gateway-details-header">
		<span class="ld-registration-order__checkout-button-gateway-details-header-label">
			<?php echo esc_html( $label ); ?>
		</span>

		<?php if ( $meta_html ) : ?>
			<div class="ld-registration-order__checkout-button-gateway-details-header-meta">
				<?php echo wp_kses( $meta_html, Sanitize::extended_kses() ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="ld-registration-order__checkout-button-gateway-details-content">
		<?php echo $gateway_details_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML content. ?>
	</div>
</div>
