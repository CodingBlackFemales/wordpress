<?php
/**
 * View: Profile Subscriptions - Expired subscription - Expired date.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription The subscription.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$expired_date = $subscription->get_expired_date();

if ( ! $expired_date ) {
	return;
}

?>
<div class="ld-profile__subscription-date ld-profile__subscription-date--expired">
	<?php
	printf(
		/* translators: 1: Expired date. */
		esc_html__( 'Subscription expired on %1$s', 'learndash' ),
		esc_html( learndash_adjust_date_time_display( $expired_date, 'F j, Y' ) ),
	);
	?>
</div>
