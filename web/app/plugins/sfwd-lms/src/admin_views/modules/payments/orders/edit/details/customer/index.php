<?php
/**
 * View: Order Customer Details.
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

<div class="ld-order-details__table" role="table">
	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/details/customer/table-head',
		[
			'transaction' => $transaction,
		]
	);

	$this->show_admin_template(
		'modules/payments/orders/edit/details/customer/table-body',
		[
			'transaction' => $transaction,
		]
	);
	?>
</div>
