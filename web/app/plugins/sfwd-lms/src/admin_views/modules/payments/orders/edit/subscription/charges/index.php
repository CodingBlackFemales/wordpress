<?php
/**
 * View: Order Subscription Charges.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Template     $this         Current instance of template engine rendering this template.
 * @var Subscription $subscription Subscription object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Commerce\Subscription;

$charges_limit = 25;
$charges       = $subscription->get_charges( null, $charges_limit, 0 );

if ( empty( $charges ) ) {
	return;
}

?>
<div class="ld-order-subscription__charges ld-order-items__table ld-order-items__table--subscription-charges">
	<div class="ld-order-subscription__charges-container">
		<div class="ld-order-subscription__charges-header">
			<h3 class="ld-order-subscription__charges-title">
				<?php esc_html_e( 'Subscription Charges', 'learndash' ); ?>
			</h3>
		</div>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/subscription/charges/table',
			[ 'charges' => $charges ]
		);
		?>

		<?php if ( $subscription->count_charges() > $charges_limit ) : ?>
			<div class="ld-order-subscription__charges-footer">
				<?php
				printf(
					/* translators: %d: Number of charges. */
					esc_html__( 'This list displays only the last %d subscription charges.', 'learndash' ),
					esc_html( (string) $charges_limit )
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
