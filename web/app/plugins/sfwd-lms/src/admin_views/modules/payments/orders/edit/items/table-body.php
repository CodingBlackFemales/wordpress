<?php
/**
 * Template: Order items body.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Transaction $transaction     Order object.
 * @var Template    $this            Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

?>
<div class="ld-order-items__tbody" role="rowgroup">
	<div class="ld-order-items__row" role="row">
		<div class="ld-order-items__item-data ld-order-items__item-data--first-child ld-order-items__item-name" role="cell">
			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/items/item/name',
				[
					'transaction' => $transaction,
				]
			);
			?>
		</div>

		<div class="ld-order-items__item-data ld-order-items__item-status" role="cell">
			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/items/item/status',
				[
					'transaction' => $transaction,
				]
			);
			?>
		</div>

		<div class="ld-order-items__item-data ld-order-items__item-pricing-details" role="cell">
			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/items/item/pricing-details',
				[
					'transaction' => $transaction,
				]
			);
			?>
		</div>

		<div class="ld-order-items__item-data ld-order-items__item-data--last-child ld-order-items__item-price" role="cell">
			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/items/item/price',
				[
					'transaction' => $transaction,
				]
			);
			?>
		</div>
	</div>
</div>
