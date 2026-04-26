<?php
/**
 * View: Profile Subscriptions - Active subscription - Next payment.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription The subscription.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$next_payment               = $subscription->get_next_payment_date();
$payment_method_description = $subscription->get_payment_method_description();

if (
	empty( $next_payment )
	|| empty( $payment_method_description )
) {
	return;
}

?>
<div class="ld-profile__subscription-next-payment">
	<span class="ld-profile__subscription-next-payment-label">
		<?php esc_html_e( 'Next payment', 'learndash' ); ?>
	</span>

	<span class="ld-profile__subscription-next-payment-value">
		<?php
		printf(
			/* translators: 1: Subscription price 2: Next payment date. */
			esc_html__( '%1$s on %2$s', 'learndash' ),
			esc_html( learndash_get_price_formatted( $subscription->get_price() ) ),
			esc_html( learndash_adjust_date_time_display( $next_payment, 'F j, Y' ) ),
		);
		?>
	</span>
</div>
