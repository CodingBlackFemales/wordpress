<?php
/**
 * Transactions based widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Auto_View_Name;
use LearnDash\Core\Template\Dashboards\Widgets\Widget;
use LearnDash_Custom_Label;

/**
 * Transactions based widget.
 *
 * @since 4.9.0
 */
abstract class Transactions extends Widget {
	use Auto_View_Name;

	/**
	 * Transactions.
	 *
	 * @since 4.9.0
	 *
	 * @var Transaction[]
	 */
	protected $transactions = [];

	/**
	 * Returns transactions.
	 *
	 * @since 4.9.0
	 *
	 * @return Transaction[]
	 */
	public function get_transactions(): array {
		return $this->transactions;
	}

	/**
	 * Sets transactions.
	 *
	 * @since 4.9.0
	 *
	 * @param Transaction[] $transactions Transactions.
	 *
	 * @return void
	 */
	public function set_transactions( array $transactions ): void {
		$this->transactions = $transactions;
	}

	/**
	 * Returns a widget empty state text. It is used when there is no data to show.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_empty_state_text(): string {
		return sprintf(
			/* translators: %s: transactions label */
			__( 'No %s found.', 'learndash' ),
			LearnDash_Custom_Label::label_to_lower( 'transactions' )
		);
	}
}
