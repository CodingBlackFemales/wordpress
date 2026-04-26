<?php
/**
 * View: Order Subscription Charges - Table Body.
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
<div class="ld-order-items__tbody" role="rowgroup">
	<?php foreach ( $charges as $charge ) : ?>
		<div class="ld-order-items__row" role="row">
			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/subscription/charges/cells/charge-id',
				[
					'charge' => $charge,
				]
			);
			?>

			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/subscription/charges/cells/date',
				[
					'charge' => $charge,
				]
			);
			?>

			<?php
			$this->show_admin_template(
				'modules/payments/orders/edit/subscription/charges/cells/amount',
				[
					'charge' => $charge,
				]
			);
			?>

			<span
				aria-hidden="true"
				class="ld-order-items__item-data ld-order-items__item-data--empty"
				role="cell"
			>
				<?php // Empty cell to align the columns. ?>
			</span>
		</div>
	<?php endforeach; ?>
</div>
