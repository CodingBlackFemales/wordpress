<?php
/**
 * Template: Order item pricing details for Trial Transactions.
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

echo esc_html(
	sprintf(
		// Translators: %1$d is the trial duration value, %2$s is the trial duration label, %3$s is the trial price, %4$s is the price, %5$d is the duration value, %6$s is the duration label, %7$s is the recurring times label.
		__( 'First %1$d %2$s %3$s, then %4$s every %5$d %6$s %7$s', 'learndash' ),
		$transaction->get_pricing()->trial_duration_value,
		learndash_get_grammatical_number_label_for_interval(
			$transaction->get_pricing()->trial_duration_value,
			$transaction->get_pricing()->trial_duration_length
		),
		learndash_get_price_formatted(
			$transaction->get_pricing()->trial_price
		),
		learndash_get_price_formatted(
			$transaction->get_pricing()->price
		),
		$transaction->get_pricing()->duration_value,
		learndash_get_grammatical_number_label_for_interval(
			$transaction->get_pricing()->duration_value,
			$transaction->get_pricing()->duration_length
		),
		$transaction->get_pricing()->recurring_times > 0
			? sprintf(
		// Translators: %1$d is the recurring times value, %2$s is the recurring times label.
				__( 'for %1$d %2$s', 'learndash' ),
				$transaction->get_pricing()->recurring_times,
				learndash_get_grammatical_number_label_for_interval(
					$transaction->get_pricing()->recurring_times,
					$transaction->get_pricing()->duration_length
				)
			)
			: __( 'until cancelled', 'learndash' )
	)
);
