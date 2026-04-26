<?php
/**
 * View: Transactions Dashboard Widget Pricing Trial.
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

if ( $pricing->trial_duration_value <= 0 ) {
	return;
}
?>
<span class="ld-dashboard-widget-transactions__label ld-dashboard-widget-transactions__label--small">
	<?php
	printf(
		// Translators: placeholder: Transaction trial price, Transaction trial duration value, Transaction trial duration length.
		esc_html_x(
			'%1$s %2$d %3$s trial',
			'placeholder: Transaction trial price, Transaction trial duration value, Transaction trial duration length',
			'learndash'
		),
		esc_html(
			learndash_get_price_formatted( $pricing->trial_price, $pricing->currency )
		),
		esc_attr( (string) $pricing->trial_duration_value ),
		esc_html(
			learndash_get_grammatical_number_label_for_interval(
				$pricing->trial_duration_value,
				$pricing->trial_duration_length
			)
		)
	);
	?>
</span>
