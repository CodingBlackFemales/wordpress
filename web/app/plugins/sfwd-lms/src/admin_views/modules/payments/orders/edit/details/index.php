<?php
/**
 * View: Order Details.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template    $this        Current instance of template engine rendering this template.
 * @var Transaction $transaction Transaction object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Transaction;
?>
<div class="ld-order-details__container">
	<?php
	if ( $transaction->get_post()->post_status === 'draft' ) {
		$this->show_admin_template(
			'modules/payments/orders/edit/details/status-history',
			[
				'transaction' => $transaction,
			]
		);
	} else {
		$this->show_admin_template(
			'modules/payments/orders/edit/details/customer',
			[
				'transaction' => $transaction,
			]
		);

		$this->show_admin_template(
			'modules/payments/orders/edit/details/payment',
			[
				'transaction' => $transaction,
			]
		);
	}
	?>
</div>
