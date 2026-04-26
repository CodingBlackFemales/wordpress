<?php
/**
 * View: Profile Subscriptions - Active subscription - Payment method warning.
 *
 * Displays a warning if the subscription has been canceled via the removal of an
 * associated payment method and access will end after the current billing cycle.
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

if ( ! empty( $payment_method_description ) ) {
	return;
}
?>
<div class="ld-profile__subscription-payment-method-warning">
	<span
		aria-hidden="true"
		class="ld-profile__subscription-payment-method-warning-icon dashicons dashicons-warning"
	>
	</span>

	<div class="ld-profile__subscription-payment-method-warning-content">
		<span><?php esc_html_e( 'This subscription does not have a payment method associated. Access to this subscription will end after the current billing cycle. You will need to re-enroll in the subscription after expiration to regain access.', 'learndash' ); ?></span>
	</div>
</div>
