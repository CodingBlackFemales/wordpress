<?php
/**
 * View: PayPal Checkout Button - 'PayPal' payment method.
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
<div
	aria-label="<?php echo esc_attr( $button_label . ' ' . $gateway->get_checkout_info_text_for_paypal_payment_method( $post->post_type ) ); ?>"
	id="<?php echo esc_attr( Learndash_Payment_Button::map_button_id( 'paypal' ) ); ?>"
></div>
