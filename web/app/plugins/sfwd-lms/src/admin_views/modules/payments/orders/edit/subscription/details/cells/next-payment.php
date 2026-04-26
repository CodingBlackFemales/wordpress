<?php
/**
 * View: Order Subscription Details - Table Body - Field: Next Payment.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription Subscription object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$has_next_payment_date = $subscription->is_active() || $subscription->is_trial();

if ( $has_next_payment_date ) {
	$next_payment_date   = learndash_adjust_date_time_display( $subscription->get_next_payment_date(), 'F j, Y, g:i A' );
	$next_payment_amount = learndash_get_price_formatted( $subscription->get_price() );
}

?>
<div class="ld-order-items__item-data" role="cell">
	<div class="ld-order-subscription__details-value">
		<?php if ( $has_next_payment_date ) : ?>
			<div class="ld-order-subscription__details-date">
				<?php echo esc_html( $next_payment_date ); ?>
			</div>

			<div class="ld-order-subscription__details-amount">
				<?php echo esc_html( $next_payment_amount ); ?>
			</div>
		<?php else : ?>
			<?php echo esc_html_x( '---', 'Subscription next payment date for non-active subscriptions.', 'learndash' ); ?>
		<?php endif; ?>
	</div>
</div>
