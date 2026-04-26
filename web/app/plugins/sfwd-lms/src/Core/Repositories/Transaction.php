<?php
/**
 * Transaction repository class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Repositories;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Transaction as Transaction_Model;
use LearnDash\Core\Models\Commerce\Product as Commerce_Product;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;
use StellarWP\Learndash\StellarWP\Models\ModelQueryBuilder;

/**
 * Repository for the Transaction model.
 *
 * @since 4.25.0
 */
class Transaction extends Repository {
	/**
	 * Finds the latest transaction ID (in a context of a Commerce Product) for a user and product.
	 *
	 * @since 4.25.0
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return int|null The latest transaction ID or null if not found.
	 */
	public static function find_latest_transaction_id( int $user_id, int $product_id ): ?int {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$sql = DB::table( 'posts' )
			->select( 'id' )
			->join(
				function ( JoinQueryBuilder $builder ) use ( $product_id ) {
					$builder->innerJoin( 'postmeta', 'product_id_meta' )
						->on( 'product_id_meta.post_id', 'id' )
						->andOn( 'product_id_meta.meta_key', 'post_id', true )
						->andOn( 'product_id_meta.meta_value', (string) $product_id, true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'product_status_meta' )
						->on( 'product_status_meta.post_id', 'id' )
						->andOn( 'product_status_meta.meta_key', Commerce_Product::$meta_key_product_status, true );
				}
			)
			->where( 'post_type', $post_type )
			->where( 'post_author', $user_id )
			->where( 'post_parent', 0, '!=' ) // Exclude the top-level transactions (orders).
			->where( 'post_status', 'publish' )
			->orderBy( 'post_date', 'DESC' )
			->limit( 1 )
			->getSQL();

		$transaction_id = DB::get_var( $sql );

		return $transaction_id
			? Cast::to_int( $transaction_id )
			: null; // No transaction found.
	}


	/**
	 * Prepares the query for the Transaction model.
	 *
	 * @since 4.25.0
	 *
	 * @return ModelQueryBuilder<Transaction_Model>
	 */
	public function prepareQuery(): ModelQueryBuilder {
		return new ModelQueryBuilder( Transaction_Model::class );
	}
}
