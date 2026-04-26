<?php
/**
 * Latest transactions widget.
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
use LearnDash\Core\Template\Dashboards\Widgets\Types\Transactions;
use StellarWP\Learndash\StellarWP\DB\DB;

/**
 * Latest transactions widget.
 *
 * @since 4.9.0
 */
class Latest_Transactions extends Transactions implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Returns the limit of transactions to display.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	protected static function get_transactions_limit(): int {
		/**
		 * Filters the limit of transactions to display.
		 *
		 * @since 4.9.0
		 *
		 * @param int $transactions_limit The limit of transactions to display.
		 *
		 * @return int
		 */
		return apply_filters( 'learndash_dashboard_widget_course_latest_transactions_limit', 6 );
	}

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$transaction_ids = DB::table( 'posts' )
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
			->orderBy( 'post_date', 'DESC' )
			->limit( self::get_transactions_limit() )
			->getSQL();

		$this->set_transactions(
			Transaction::find_many(
				DB::get_col( $transaction_ids )
			)
		);
	}
}
