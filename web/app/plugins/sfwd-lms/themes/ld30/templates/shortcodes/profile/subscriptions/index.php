<?php
/**
 * View: Profile Subscriptions.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var array<Subscription> $subscriptions The subscriptions.
 * @var Template            $this          Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Template\Template;

// Remove subscriptions that are not PayPal for now.
$subscriptions = array_filter(
	$subscriptions,
	function ( $subscription ) {
		return $subscription->get_gateway_name() === Payment_Gateway::get_name();
	}
);

// If there are no subscriptions, don't render anything.
if ( empty( $subscriptions ) ) {
	return;
}
?>
<div
	class="ld-profile__subscriptions"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<h3 class="ld-profile__subscriptions-title">
		<?php esc_html_e( 'Your Subscriptions', 'learndash' ); ?>
	</h3>

	<?php $this->template( 'shortcodes/profile/subscriptions/list' ); ?>

	<?php $this->template( 'shortcodes/profile/subscriptions/cancel-subscription-notice' ); ?>
</div>
<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
