<?php
/**
 * View: Profile Subscriptions - Expired subscription - Payment method warning.
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
		<span><?php esc_html_e( 'This subscription has been canceled via the removal of an associated payment method. You will need to re-enroll in the subscription to regain access.', 'learndash' ); ?></span>

		<a
			class="ld-profile__subscription-action ld-profile__subscription-action--re-enroll"
			href="<?php echo esc_url( $subscription->get_re_enroll_url() ); ?>"
		>
			<?php esc_html_e( 'Re-Enroll Now', 'learndash' ); ?>
		</a>
	</div>
</div>
