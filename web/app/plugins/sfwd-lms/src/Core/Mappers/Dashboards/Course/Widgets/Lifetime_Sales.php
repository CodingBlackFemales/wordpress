<?php
/**
 * Lifetime sales widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Money;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\QueryBuilder;

/**
 * Lifetime sales widget.
 *
 * @since 4.9.0
 */
class Lifetime_Sales extends Money implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Returns the number of transactions to process per time.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	private static function transactions_chunk_size(): int {
		/**
		 * Filters the number of transactions to process per time.
		 *
		 * @since 4.9.0
		 *
		 * @param int $transactions_chunk_size The number of transactions to process per time. Default 100.
		 *
		 * @return int
		 */
		return apply_filters(
			'learndash_dashboard_widget_course_lifetime_sales_transactions_chunk_size',
			100
		);
	}

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$this->set_label(
			__( 'Lifetime Sales', 'learndash' )
		);

		$this->set_value(
			$this->get_lifetime_sales_amount()
		);
	}

	/**
	 * Returns the amount of lifetime sales.
	 *
	 * @since 4.9.0
	 *
	 * @return float
	 */
	private function get_lifetime_sales_amount(): float {
		$total_lifetime_sales = 0.;

		// get current currency.
		$ld_currency = learndash_get_currency_code();

		$offset = 0;
		while ( $transaction_ids = $this->get_transaction_ids( $offset ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- It's intentional.
			$transactions = Transaction::find_many( $transaction_ids );

			foreach ( $transactions as $transaction ) {
				$pricing = $transaction->get_pricing();

				// Skip if the currency is not the current one.

				if ( $pricing->currency !== $ld_currency ) {
					continue;
				}

				// Sum the trial price.

				$total_lifetime_sales += $pricing->trial_price;

				// Use the discounted price if the transaction has a discount.

				$total_lifetime_sales += $pricing->discount > 0
					? $pricing->discounted_price
					: $pricing->price;
			}

			$offset += count( $transaction_ids );
		}

		return $total_lifetime_sales;
	}

	/**
	 * Returns the transaction IDs related to the post.
	 *
	 * @since 4.9.0
	 *
	 * @param int $offset The offset. Default 0.
	 *
	 * @return array<int>
	 */
	private function get_transaction_ids( int $offset = 0 ): array {
		$transaction_sql = DB::table( 'posts' )
			->select( 'ID' )
			->joinRaw(
				sprintf(
					"JOIN %s AS post_id_ref
						ON post_id_ref.post_id = id
						AND ( post_id_ref.meta_key = 'post_id'
							OR post_id_ref.meta_key = 'course_id' )
						AND post_id_ref.meta_value = %d",
					DB::prefix( 'postmeta' ),
					$this->get_post()->ID
				)
			)
			->where( 'post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'post_status', 'publish' )
			->where( 'post_parent', '0', '!=' )
			->whereNotExists(
				function ( QueryBuilder $builder ) {
					$builder
						->select( 'meta_value' )
						->from( 'postmeta', 'free' )
						->whereRaw( 'WHERE free.post_id = ID' )
						->where( 'free.meta_key', Transaction::$meta_key_is_free );
				}
			)
			->limit( self::transactions_chunk_size() )
			->offset( $offset )
			->getSQL();

		return DB::get_col( $transaction_sql );
	}
}
