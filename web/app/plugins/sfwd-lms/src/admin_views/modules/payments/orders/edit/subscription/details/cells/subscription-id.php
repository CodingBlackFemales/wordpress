<?php
/**
 * View: Order Subscription Details - Table Body - Field: Subscription ID.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription Subscription object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
?>
<div class="ld-order-items__item-data ld-order-items__item-data--first-child" role="cell">
	<div class="ld-order-subscription__details-value">
		<?php echo esc_html( $subscription->get_gateway_subscription_id() ); ?>
	</div>
</div>
