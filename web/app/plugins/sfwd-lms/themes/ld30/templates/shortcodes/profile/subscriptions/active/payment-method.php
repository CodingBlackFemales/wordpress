<?php
/**
 * View: Profile Subscriptions - Active subscription - Payment method.
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

$payment_method_description = $subscription->get_payment_method_description();

if ( empty( $payment_method_description ) ) {
	return;
}

$payment_method_information = $subscription->get_payment_method_information();

?>
<div class="ld-profile__subscription-payment-method">
	<span class="ld-profile__subscription-payment-method-description">
		<?php
		printf(
			/* translators: 1: Payment method description. */
			esc_html__( 'Via %1$s', 'learndash' ),
			esc_html( $payment_method_description ),
		);
		?>
	</span>

	<span class="ld-profile__subscription-payment-method-icon">
		<?php
		if ( ! empty( $payment_method_information['icon'] ) ) {
			$this->template(
				sprintf(
					'components/icons/%s-small.php',
					$payment_method_information['icon']
				),
				[
					'label' => sprintf(
						/* translators: %s: Payment method icon name. */
						__( 'Payment method icon: %s', 'learndash' ),
						esc_html( $payment_method_information['icon'] )
					),
				]
			);
		}
		?>
	</span>
</div>
