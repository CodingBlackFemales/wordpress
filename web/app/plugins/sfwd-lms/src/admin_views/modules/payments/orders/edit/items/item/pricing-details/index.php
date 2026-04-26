<?php
/**
 * Template: Order item pricing details.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Transaction $transaction Order object.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

?>

<?php if ( $transaction->has_coupon() ) : ?>
	<span class="ld-order-items__icon-coupon">
		<?php $this->show_admin_template( 'modules/payments/orders/edit/items/item/logo-coupon' ); ?>
	</span>
<?php endif; ?>

<span class="ld-order-items__pricing-details">
	<?php if ( $transaction->is_subscription() && $transaction->has_trial() ) : ?>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/items/item/pricing-details/trial',
			[
				'transaction' => $transaction,
			]
		);
		?>

	<?php elseif ( $transaction->is_subscription() ) : ?>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/items/item/pricing-details/subscription',
			[
				'transaction' => $transaction,
			]
		);
		?>

	<?php elseif ( $transaction->has_coupon() ) : ?>

		<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/items/item/pricing-details/coupon',
				[
					'transaction' => $transaction,
				]
			);
		?>

	<?php else : ?>

		<?php $this->show_admin_template( 'modules/payments/orders/edit/items/item/pricing-details/default' ); ?>

	<?php endif; ?>
</span>
