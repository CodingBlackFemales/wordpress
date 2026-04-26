<?php
/**
 * View: Order Subscription Charges - Table Body - Field: Amount.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Charge $charge Charge object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Charge;

$price     = learndash_get_price_formatted( $charge->get_price() );
$is_failed = $charge->is_failed();

$charge_amount_classes = [
	'ld-order-subscription__charge-amount',
	$is_failed ? 'ld-order-subscription__charge-amount--failed' : '',
];
?>
<span class="ld-order-items__item-data ld-order-items__item-data--last-child" role="cell">
	<div class="ld-order-subscription__charge-amount-container">
		<span class="<?php echo esc_attr( implode( ' ', array_filter( $charge_amount_classes ) ) ); ?>">
			<?php echo esc_html( $price ); ?>
		</span>

		<?php if ( $charge->is_trial() ) : ?>
			<span class="ld-order-items__price-status ld-order-items__price-status--trial">
			<?php esc_html_e( 'Trial Price', 'learndash' ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $is_failed ) : ?>
			<span class="ld-order-items__price-status ld-order-items__price-status--failed">
				<?php esc_html_e( 'Failed Charge', 'learndash' ); ?>
			</span>
		<?php endif; ?>
	</div>
</span>
