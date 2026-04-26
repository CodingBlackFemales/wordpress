<?php
/**
 * Template: Order item pricing details for Subscription Transactions.
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
		// Translators: %1$d is the duration value, %2$s is the duration label, %3$s is the recurring times label.
		__( 'Every %1$d %2$s, %3$s', 'learndash' ),
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
