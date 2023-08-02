<?php
/**
 * View: Enrollment Button.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string  $payment_button    Payment button.
 * @var bool    $show_login_button Indicates if login button should be shown.
 * @var string  $login_url         Login url.
 * @var Product $product           Product model.
 * @var WP_User $user              User.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Product;

// TODO: Logic in this template looks messy to me. We need to refactor it into multiple templates.
// TODO: We need to decide what to do with the button.
?>
<section class="ld-enrollment">
	<?php
	if ( $product->is_price_type_closed() ) {
		if ( ! empty( $payment_button ) ) {
			echo $payment_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Button HTML.
		}
	} elseif ( $product->is_price_type_open() || $product->is_price_type_free() ) {
		if ( $show_login_button ) {
			echo sprintf(
				'<a class="ld-login-text" href="%s">%s</a>',
				esc_url( $login_url ),
				esc_html__( 'Login to Enroll', 'learndash' )
			);
		} else {
			echo $payment_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Payment button HTML
		}
	} elseif ( $product->is_price_type_subscribe() || $product->is_price_type_paynow() ) {
		echo $payment_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Button HTML.

		if ( $show_login_button ) {
			echo sprintf(
				'<span class="ld-text">%s <a class="ld-login-text" href="%s">%s</a></span>',
				esc_html( ! empty( $payment_button ) ? __( 'or', 'learndash' ) : '' ),
				esc_url( $login_url ),
				esc_html__( 'Login', 'learndash' )
			);
		}
	}
	?>
</section>
