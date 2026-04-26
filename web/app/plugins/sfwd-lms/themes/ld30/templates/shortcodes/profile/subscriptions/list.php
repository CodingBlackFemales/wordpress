<?php
/**
 * View: Profile Subscriptions - List.
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
use LearnDash\Core\Template\Template;
?>
<div class="ld-profile__subscriptions-list">
	<?php foreach ( $subscriptions as $subscription ) : ?>
		<?php
		switch ( $subscription->get_status() ) :
			case Subscription::$status_active:
			case Subscription::$status_trial: // Trial subscriptions have the same UI as active subscriptions.
				$this->template( 'shortcodes/profile/subscriptions/active', [ 'subscription' => $subscription ] );
				break;
			case Subscription::$status_canceled:
				$this->template( 'shortcodes/profile/subscriptions/canceled', [ 'subscription' => $subscription ] );
				break;
			case Subscription::$status_expired:
				$this->template( 'shortcodes/profile/subscriptions/expired', [ 'subscription' => $subscription ] );
				break;
			default:
				break;
		endswitch;
		?>
	<?php endforeach; ?>
</div>
