<?php
/**
 * Subscription repository class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Repositories;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Commerce\Subscription as Subscription_Model;
use LearnDash\Core\Models\Commerce\Product as Commerce_Product;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;
use StellarWP\Learndash\StellarWP\Models\ModelQueryBuilder;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\WhereQueryBuilder;

/**
 * Repository for the Subscription model.
 *
 * @since 4.25.0
 */
class Subscription extends Repository {
	/**
	 * Finds subscriptions by user.
	 *
	 * @since 4.25.0
	 *
	 * @param int $user_id    User ID.
	 *
	 * @return array<Subscription_Model> The subscriptions.
	 */
	public static function find_by_user( int $user_id ): array {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$sql = DB::table( 'posts', 'transactions' )
			->select( 'transactions.id' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'product_price_type' )
						->on( 'product_price_type.post_id', 'transactions.id' )
						->andOn( 'product_price_type.meta_key', 'price_type', true )
						->andOn( 'product_price_type.meta_value', LEARNDASH_PRICE_TYPE_SUBSCRIBE, true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'product_status_meta' )
						->on( 'product_status_meta.post_id', 'transactions.id' )
						->andOn( 'product_status_meta.meta_key', Commerce_Product::$meta_key_product_status, true );
				}
			)
			->where( 'transactions.post_type', $post_type )
			->where( 'transactions.post_author', $user_id )
			->where( 'transactions.post_parent', 0, '!=' ) // Exclude the top-level transactions (orders).
			->where( 'transactions.post_status', 'publish' )
			->orderBy( 'transactions.post_date', 'DESC' )
			->getSQL();

		$subscription_ids = DB::get_col( $sql );

		$models = Subscription_Model::find_many( array_map( 'intval', $subscription_ids ) );

		// Sort subscriptions by status.

		usort(
			$models,
			[ self::class, 'compare_subscription_statuses' ]
		);

		return $models;
	}

	/**
	 * Finds subscriptions due for payment up to a specific timestamp in batches.
	 *
	 * @since 4.25.0
	 *
	 * @param int $start_timestamp Start timestamp for the date range. Use 0 to check from beginning of time.
	 * @param int $end_timestamp   End timestamp for the date range.
	 * @param int $batch_size      Number of subscriptions to process per batch. Default 50.
	 * @param int $offset          Offset for pagination. Default 0.
	 *
	 * @return array<array{
	 *     subscription_id: int,
	 *     user_id: int
	 * }>|null The subscription IDs and user IDs for this batch.
	 */
	public static function find_due_for_payment(
		int $start_timestamp,
		int $end_timestamp,
		int $batch_size = 50,
		int $offset = 0
	): ?array {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$query = DB::table( 'posts', 'transactions' )
			->select( 'transactions.id', 'transactions.post_author' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'product_price_type' )
						->on( 'product_price_type.post_id', 'transactions.id' )
						->andOn( 'product_price_type.meta_key', 'price_type', true )
						->andOn( 'product_price_type.meta_value', LEARNDASH_PRICE_TYPE_SUBSCRIBE, true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'product_status_meta' )
						->on( 'product_status_meta.post_id', 'transactions.id' )
						->andOn( 'product_status_meta.meta_key', Commerce_Product::$meta_key_product_status, true );
				}
			)
			->whereIn( 'product_status_meta.meta_value', [ Subscription_Model::$status_active, Subscription_Model::$status_trial ] )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'next_payment_date_meta' )
						->on( 'next_payment_date_meta.post_id', 'transactions.id' )
						->andOn( 'next_payment_date_meta.meta_key', Subscription_Model::$meta_key_next_payment_date, true );
				}
			)
			->where( 'transactions.post_type', $post_type )
			->where( 'transactions.post_parent', 0, '!=' ) // Exclude the top-level transactions (orders).
			->where( 'transactions.post_status', 'publish' )
			->where( 'next_payment_date_meta.meta_value', Cast::to_string( $end_timestamp ), '<=' );

		// If start_timestamp is greater than 0, add the start timestamp condition.
		if ( $start_timestamp > 0 ) {
			$query->where( 'next_payment_date_meta.meta_value', Cast::to_string( $start_timestamp ), '>=' );
		}

		$sql = $query
			->orderBy( 'transactions.post_date', 'DESC' )
			->limit( $batch_size )
			->offset( $offset )
			->getSQL();

		$results = DB::get_results( $sql );

		if ( empty( $results ) ) {
			return null;
		}

		return array_map(
			function ( $row ) {
				return [
					'subscription_id' => (int) $row->id,
					'user_id'         => (int) $row->post_author,
				];
			},
			(array) $results
		);
	}

	/**
	 * Removes a payment token from all subscriptions for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $user_id    User ID.
	 * @param string $token_id   Payment token ID.
	 *
	 * @return void
	 */
	public static function remove_payment_token_by_user( int $user_id, string $token_id ): void {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$sql = DB::table( 'posts', 'transactions' )
			->select( 'transactions.id', 'payment_token_meta.meta_value as payment_token' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'payment_token_meta' )
						->on( 'payment_token_meta.post_id', 'transactions.id' )
						->andOn( 'payment_token_meta.meta_key', Subscription_Model::$meta_key_payment_token, true );
				}
			)
			->where( 'transactions.post_type', $post_type )
			->where( 'transactions.post_parent', 0, '!=' ) // Exclude the top-level transactions (orders).
			->where( 'transactions.post_status', 'publish' )
			->where( 'transactions.post_author', $user_id )
			->getSQL();

		$results = DB::get_results( $sql );

		if (
			empty( $results )
			|| ! is_array( $results )
		) {
			return;
		}

		$subscriptions_to_update = [];

		foreach ( $results as $result ) {
			$payment_token = maybe_unserialize( $result->payment_token );

			if (
				is_array( $payment_token )
				&& isset( $payment_token['token'] )
				&& $payment_token['token'] === $token_id
			) {
				$subscriptions_to_update[] = Cast::to_int( $result->id );
			}
		}

		if ( empty( $subscriptions_to_update ) ) {
			return;
		}

		// DB library does not support complex updates, so we need to use the global $wpdb object.

		global $wpdb;

		$subscription_ids = implode( ',', array_map( 'absint', $subscriptions_to_update ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = '' WHERE post_id IN ($subscription_ids) AND (meta_key = %s OR meta_key = %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This is a valid use of interpolation.
				Subscription_Model::$meta_key_payment_token,
				Subscription_Model::$meta_key_payment_method_information
			)
		);
	}

	/**
	 * Prepares the query for the Subscription model.
	 *
	 * @since 4.25.0
	 *
	 * @return ModelQueryBuilder<Subscription_Model>
	 */
	public function prepareQuery(): ModelQueryBuilder {
		return new ModelQueryBuilder( Subscription_Model::class );
	}

	/**
	 * Compares subscription statuses.
	 *
	 * @since 4.25.0
	 *
	 * @param Subscription_Model $a The first subscription.
	 * @param Subscription_Model $b The second subscription.
	 *
	 * @return int The comparison result, like strcmp(): -1 if $a is less than $b, 0 if they are equal, and 1 if $a is greater than $b.
	 */
	private static function compare_subscription_statuses( Subscription_Model $a, Subscription_Model $b ): int {
		$status_order = [
			Subscription_Model::$status_active   => 0,
			Subscription_Model::$status_trial    => 1,
			Subscription_Model::$status_expired  => 2,
			Subscription_Model::$status_canceled => 3,
		];

		$a_order = $status_order[ $a->get_status() ] ?? 4;
		$b_order = $status_order[ $b->get_status() ] ?? 4;

		return $a_order <=> $b_order;
	}
}
