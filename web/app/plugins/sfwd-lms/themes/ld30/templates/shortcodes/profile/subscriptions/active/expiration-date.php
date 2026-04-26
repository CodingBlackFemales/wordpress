<?php
/**
 * View: Profile Subscriptions - Active subscription - Expiration date.
 *
 * Displays the expiration date of the subscription if it has no payment method.
 * If the subscription has a payment method, the next payment date is displayed,
 * since the subscription will get canceled during the next renewal.
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
	|| ! empty( $payment_method_description )
) {
	return;
}

?>
<div class="ld-profile__subscription-next-payment">
	<span class="ld-profile__subscription-next-payment-label">
		<?php esc_html_e( 'Subscription expires', 'learndash' ); ?>
	</span>

	<span class="ld-profile__subscription-next-payment-value">
		<?php
		printf(
			/* translators: 1: Expiration date. */
			esc_html__( 'on %1$s', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( $next_payment, 'F j, Y' ) ),
		);
		?>
	</span>
</div>
