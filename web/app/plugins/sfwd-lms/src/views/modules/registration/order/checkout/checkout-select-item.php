<?php
/**
 * Registration - Checkout button select item.
 *
 * @since 4.16.0
 * @version 4.25.0
 *
 * @var array<string, Learndash_Payment_Gateway> $active_gateways        Active gateways.
 * @var string                                   $button_key             Button key.
 * @var array<string, string>                    $buttons                Checkout buttons.
 * @var array<string, string|int|float>          $default_payment_params Default payment params for checkout.
 * @var string                                   $product_type           Product type.
 * @var string                                   $selected_payment       Selected payment.
 * @var Template                                 $this                   The Template object.
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

$is_selected  = $button_key === $selected_payment;
$item_classes = [
	'ld-form__field-radio-container',
	'ld-form__field-svgradio-container',
	'ld-registration-order__checkout-select-item',
	'ld-registration-order__checkout-select-item-' . $button_key,
];

if ( $is_selected ) {
	$item_classes[] = 'ld--selected';
}

$checkout_data = $gateway->get_checkout_data_for_button_key(
	$button_key,
	[
		'product_type' => $product_type,
	]
);

$item_info            = Cast::to_string( $checkout_data['info_text'] ?? '' );
$meta_html            = Cast::to_string( $checkout_data['meta_html'] ?? '' );
$gateway_details_html = Cast::to_string( $checkout_data['gateway_details_html'] ?? '' );
?>
<div
	class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
>
	<div class="ld-registration-order__checkout-select-item-main">
		<?php
		$this->template(
			'components/forms/fields/radio',
			[
				'field_id'    => 'ld-payment_type__' . $button_key,
				'field_label' => $checkout_data['label'] ?? '',
				'field_name'  => 'payment_type',
				'field_value' => $button_key,
				'is_selected' => $is_selected,
				'extra_attrs' => [
					'data-id' => $button_key,
				],
				'is_required' => true,
			]
		);
		?>

		<?php if ( $meta_html ) : ?>
			<div class="ld-registration-order__checkout-select-item-meta">
				<?php echo wp_kses( $meta_html, Sanitize::extended_kses() ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $gateway_details_html || $item_info ) : ?>
		<div class="ld-registration-order__checkout-select-item-gateway-details">
			<?php echo $gateway_details_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML content. ?>

			<?php if ( $item_info ) : ?>
				<div class="ld-registration-order__checkout-select-item-info" aria-live="polite" role="note">
					<?php echo esc_html( $item_info ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
