<?php
/**
 * View: Transactions Dashboard Widget Pricing Discount.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Transaction $transaction Transaction.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

$pricing = $transaction->get_pricing();

if ( $pricing->discount <= 0 ) {
	return;
}
?>
<span class="ld-dashboard-widget-transactions__label ld-dashboard-widget-transactions__label--small">
	<?php
	printf(
		// Translators: placeholder: Transaction discount.
		esc_html_x( '%s discount', 'placeholder: Transaction discount', 'learndash' ),
		esc_html(
			learndash_get_price_formatted( $pricing->discount * -1, $pricing->currency )
		)
	);
	?>
</span>
