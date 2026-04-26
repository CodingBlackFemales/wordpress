<?php
/**
 * View: Profile Subscriptions - Active subscription - Actions.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription The subscription.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;

$payment_method_description = $subscription->get_payment_method_description();

// Don't allow canceling the subscription if it doesn't have a payment method.
if ( empty( $payment_method_description ) ) {
	return;
}
?>
<div class="ld-profile__subscription-actions">
	<a
		class="ld-profile__subscription-action ld-profile__subscription-action--cancel"
		href="<?php echo esc_url( $subscription->get_cancel_url() ); ?>"
	>
		<?php esc_html_e( 'Cancel', 'learndash' ); ?>
	</a>
</div>
