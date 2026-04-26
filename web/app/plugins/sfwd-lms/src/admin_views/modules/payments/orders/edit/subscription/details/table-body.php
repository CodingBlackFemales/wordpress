<?php
/**
 * View: Order Subscription Details - Table Body.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Subscription $subscription Subscription object.
 * @var Template     $this         Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Template\Template;

?>
<div class="ld-order-items__tbody" role="rowgroup">
	<div class="ld-order-items__row" role="row">
		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/subscription/details/cells/subscription-id',
			[
				'subscription' => $subscription,
			]
		);
		?>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/subscription/details/cells/next-payment',
			[
				'subscription' => $subscription,
			]
		);
		?>

		<?php
		$this->show_admin_template(
			'modules/payments/orders/edit/subscription/details/cells/status',
			[
				'subscription' => $subscription,
			]
		);
		?>

		<div
			aria-hidden="true"
			class="ld-order-items__item-data ld-order-items__item-data--empty"
			role="cell"
		>
			<?php // Empty cell to to align the columns. ?>
		</div>
	</div>
</div>
