<?php
/**
 * View: PayPal Checkout Button - 'Credit Card' payment method.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var string          $button_label The button label.
 * @var Payment_Gateway $gateway      The payment gateway object.
 * @var WP_Post         $post         The post object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;

?>
<button
	class="<?php echo esc_attr( Learndash_Payment_Button::map_button_class_name() ); ?>"
	id="<?php echo esc_attr( Learndash_Payment_Button::map_button_id( 'card' ) ); ?>"
	type="button"
>
	<?php echo esc_html( $button_label ); ?>
</button>
