<?php
/**
 * View: Order Items.
 *
 * @since 4.19.0
 * @version 4.25.0
 *
 * @var Transaction $transaction  Order object.
 * @var Template    $this         Current instance of template engine rendering this template.
 * @var int         $index        Transaction index.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

?>

<div class="ld-order-items__container">
	<?php if ( $index === 0 ) : ?>
		<div class="ld-order-items__table ld-order-items__table--striped ld-order-items__table--show-for-desktop" role="table">
			<?php $this->show_admin_template( 'modules/payments/orders/edit/items/table-head' ); ?>
		</div>
	<?php endif; ?>

	<div class="ld-order-items__table ld-order-items__table--striped" role="table">
		<?php $this->show_admin_template( 'modules/payments/orders/edit/items/table-head' ); ?>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/items/table-body',
			[
				'transaction' => $transaction,
			]
		);
		?>
	</div>

	<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/subscription',
			[ 'transaction' => $transaction ]
		);
		?>
</div>
