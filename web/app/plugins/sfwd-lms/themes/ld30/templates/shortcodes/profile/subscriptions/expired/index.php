<?php
/**
 * View: Profile Subscriptions - Expired subscription.
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
	$subscription->get_status() !== Subscription::$status_expired
) {
	return;
}

$classes = [
	'ld-profile__subscription',
	'ld-profile__subscription--' . $subscription->get_status(),
];

$subscription_status_classes = [
	'ld-profile__subscription-status',
	'ld-profile__subscription-status--' . $subscription->get_status(),
];

?>

<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<h4 class="ld-profile__subscription-title">
		<span class="screen-reader-text"><?php esc_html_e( 'Subscription for', 'learndash' ); ?></span>
		<?php echo esc_html( $subscription->get_title() ); ?>
	</h4>

	<div class="ld-profile__subscription-status-container">
		<div class="<?php echo esc_attr( implode( ' ', $subscription_status_classes ) ); ?>">
			<?php echo esc_html( $subscription->get_status_label() ); ?>
		</div>

		<?php $this->template( 'shortcodes/profile/subscriptions/expired/expired-date' ); ?>
	</div>

	<?php $this->template( 'shortcodes/profile/subscriptions/expired/payment-method-warning' ); ?>
</div>
