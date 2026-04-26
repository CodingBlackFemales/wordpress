<?php
/**
 * View: Profile Subscriptions - Active subscription.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription The subscription.
 * @var Template     $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Template\Template;

if (
	$subscription->get_status() !== Subscription::$status_active
	&& $subscription->get_status() !== Subscription::$status_trial
) {
	return;
}

$subscription_status       = $subscription->get_status();
$payment_token             = $subscription->get_payment_token();
$subscription_status_label = $subscription->get_status_label();

if ( $subscription->can_be_cancelled() && empty( $payment_token ) ) {
	$subscription_status       = Subscription::$status_canceled;
	$subscription_status_label = __( 'Canceled', 'learndash' );
}

$classes = [
	'ld-profile__subscription',
	'ld-profile__subscription--' . $subscription_status,
];

$subscription_status_classes = [
	'ld-profile__subscription-status',
	'ld-profile__subscription-status--' . $subscription_status,
];

?>

<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<h4 class="ld-profile__subscription-title">
		<span class="screen-reader-text"><?php esc_html_e( 'Subscription for', 'learndash' ); ?></span>
		<?php echo esc_html( $subscription->get_title() ); ?>
	</h4>

	<div class="ld-profile__subscription-status-container">
		<div class="<?php echo esc_attr( implode( ' ', $subscription_status_classes ) ); ?>">
			<?php echo esc_html( $subscription_status_label ); ?>
		</div>

		<?php $this->template( 'shortcodes/profile/subscriptions/active/next-payment' ); ?>

		<?php $this->template( 'shortcodes/profile/subscriptions/active/expiration-date' ); ?>
	</div>

	<?php $this->template( 'shortcodes/profile/subscriptions/active/payment-method' ); ?>

	<?php $this->template( 'shortcodes/profile/subscriptions/active/end-date' ); ?>

	<?php $this->template( 'shortcodes/profile/subscriptions/active/actions' ); ?>

	<?php $this->template( 'shortcodes/profile/subscriptions/active/payment-method-warning' ); ?>
</div>
