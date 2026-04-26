<?php
/**
 * Registration - Checkout button.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var string                          $button_html            Button HTML.
 * @var string                          $button_key             Button key.
 * @var array<string, string>           $buttons                Checkout buttons.
 * @var array<string, string|int|float> $default_payment_params Default payment params for checkout.
 * @var string                          $selected_payment       Selected payment.
 * @var Template                        $this                   The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;

$is_selected = $button_key === $selected_payment;

$item_classes = [
	'ld-registration-order__checkout-button',
	'ld-registration-order__checkout-button-' . $button_key,
];

if ( $is_selected ) {
	$item_classes[] = 'ld--selected';
}
?>
<div
	class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
>
	<?php
	/**
	 * This filter is documented in includes/payments/class-learndash-payment-button.php
	 */
	$button_html = apply_filters(
		'learndash_payment_button',
		$button_html,
		$default_payment_params
	);

	echo Cast::to_string( $button_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
