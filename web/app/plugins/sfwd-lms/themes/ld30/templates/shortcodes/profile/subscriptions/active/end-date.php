<?php
/**
 * View: Profile Subscriptions - Active subscription - End date.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription The subscription.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$product = $subscription->get_product();

$end_date = $product ? $product->get_end_date() : 0;

$payment_method_description = $subscription->get_payment_method_description();

?>
<div class="ld-profile__subscription-date ld-profile__subscription-date--end">
	<?php if ( empty( $payment_method_description ) && empty( $end_date ) ) : ?>
		<?php esc_html_e( 'Subscription access will continue until expiration', 'learndash' ); ?>
	<?php elseif ( empty( $end_date ) ) : ?>
		<?php esc_html_e( 'Subscription renews automatically until canceled', 'learndash' ); ?>
	<?php else : ?>
		<?php
		printf(
			/* translators: 1: End date. */
			esc_html__( 'Subscription ends on %1$s', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( $end_date, 'F j, Y' ) ),
		);
		?>
	<?php endif; ?>
</div>
