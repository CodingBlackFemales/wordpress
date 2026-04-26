<?php
/**
 * Registration - Checkout button select.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<string, Learndash_Payment_Gateway> $active_gateways        Active gateways.
 * @var array<string, string>                    $buttons                Checkout buttons.
 * @var array<string, string|int|float>          $default_payment_params Default payment params for checkout.
 * @var string                                   $selected_payment       Selected payment.
 * @var Template                                 $this                   The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( count( $buttons ) < 2 ) {
	return;
}
?>
<fieldset class="ld-form ld-form__fieldset ld-form__fieldset--radio learndash_checkout_buttons ld-registration-order__checkout-select ld-registration-order__checkout-select--<?php echo esc_attr( $selected_payment ); ?>">
	<?php foreach ( $buttons as $button_key => $button ) : ?>
		<?php
		$this->template(
			'modules/registration/order/checkout/checkout-select-item',
			[
				'button'           => $button,
				'button_key'       => $button_key,
			]
		);
		?>
	<?php endforeach; ?>
</fieldset>
