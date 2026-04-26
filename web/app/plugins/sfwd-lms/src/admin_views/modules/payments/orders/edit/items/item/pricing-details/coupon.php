<?php
/**
 * Template: Order item pricing details for Transactions with a Coupon.
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
		// Translators: %1$s is the coupon code, %2$s is the coupon amount.
		__( 'Coupon %1$s (-%2$s)', 'learndash' ),
		$transaction->get_coupon_data()->code,
		$transaction->get_coupon_data()->type === 'percentage'
			? $transaction->get_coupon_data()->amount . '%'
			: learndash_get_price_formatted(
				$transaction->get_coupon_data()->amount,
				$transaction->get_pricing()->currency
			)
	)
);
