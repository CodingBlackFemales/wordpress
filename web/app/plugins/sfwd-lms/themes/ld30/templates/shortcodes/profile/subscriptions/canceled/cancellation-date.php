<?php
/**
 * View: Profile Subscriptions - Canceled subscription - Cancellation date (including the cancellation reason).
 *
 * @since 4.25.0
 * @version 4.25.3
 *
 * @var Subscription $subscription The subscription.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$cancellation_date = $subscription->get_cancellation_date();

if ( ! $cancellation_date ) {
	return;
}

?>
<div class="ld-profile__subscription-date ld-profile__subscription-date--canceled">
	<?php
	printf(
		/* translators: 1: Cancellation reason. 2: Cancellation date. */
		esc_html__( '%1$s on %2$s', 'learndash' ),
		esc_html( $subscription->get_cancellation_reason_description() ),
		esc_html( learndash_adjust_date_time_display( $cancellation_date, 'F j, Y' ) )
	);
	?>
</div>
