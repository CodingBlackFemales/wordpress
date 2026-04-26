<?php
/**
 * View: Order Subscription Charges - Table.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Charge[] $charges Charges.
 * @var Template $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Commerce\Charge;

?>
<div class="ld-order-items__table" role="table">
	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/subscription/charges/table-head',
		[
			'charges' => $charges,
		]
	);
	?>

	<?php
	$this->show_admin_template(
		'modules/payments/orders/edit/subscription/charges/table-body',
		[
			'charges' => $charges,
		]
	);
	?>
</div>
