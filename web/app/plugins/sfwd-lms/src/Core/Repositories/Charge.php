<?php
/**
 * Charge repository class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Repositories;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Commerce\Charge as Charge_Model;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;
use StellarWP\Learndash\StellarWP\Models\ModelQueryBuilder;

/**
 * Repository for the Charge model.
 *
 * @since 4.25.0
 */
class Charge extends Repository {
	/**
	 * Counts charges by subscription ID.
	 *
	 * @since 4.25.0
	 *
	 * @param int         $subscription_id  Subscription ID.
	 * @param string|null $status           The charge status (optional). Default null (all statuses).
	 *
	 * @return int The number of charges for the subscription.
	 */
	public static function count_by_subscription_id( int $subscription_id, ?string $status = null ): int {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$query = DB::table( 'posts', 'charges' )
			->where( 'charges.post_type', $post_type )
			->where( 'charges.post_parent', $subscription_id )
			->where( 'charges.post_status', 'publish' );

		if ( ! is_null( $status ) ) {
			$query->join(
				function ( JoinQueryBuilder $builder ) use ( $status ) {
					$builder->innerJoin( 'postmeta', 'charge_status' )
						->on( 'charge_status.post_id', 'charges.id' )
						->andOn( 'charge_status.meta_key', Charge_Model::$meta_key_status, true )
						->andOn( 'charge_status.meta_value', $status, true );
				}
			);
		}

		return $query->count();
	}

	/**
	 * Finds charges by subscription ID.
	 *
	 * @since 4.25.0
	 *
	 * @param int         $subscription_id Subscription ID.
	 * @param string|null $status          The charge status (optional). Default null (all statuses).
	 * @param int         $limit           Optional. Limit. Default 0 (no limit).
	 * @param int         $offset          Optional. Offset. Default 0 (no offset).
	 *
	 * @return array<Charge_Model> The charges.
	 */
	public static function find_by_subscription_id(
		int $subscription_id,
		?string $status = null,
		int $limit = 0,
		int $offset = 0
	): array {
		$post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		$query = DB::table( 'posts', 'charges' )
			->select( 'charges.id' )
			->where( 'charges.post_type', $post_type )
			->where( 'charges.post_parent', $subscription_id )
			->where( 'charges.post_status', 'publish' );

		if ( ! is_null( $status ) ) {
			$query->join(
				function ( JoinQueryBuilder $builder ) use ( $status ) {
					$builder->innerJoin( 'postmeta', 'charge_status' )
						->on( 'charge_status.post_id', 'charges.id' )
						->andOn( 'charge_status.meta_key', Charge_Model::$meta_key_status, true )
						->andOn( 'charge_status.meta_value', $status, true );
				}
			);
		}

		$query->orderBy( 'charges.post_date', 'DESC' );

		if ( $limit > 0 ) {
			$query->limit( $limit );
		}

		if ( $offset > 0 ) {
			$query->offset( $offset );
		}

		return Charge_Model::find_many( array_map( 'intval', DB::get_col( $query->getSQL() ) ) );
	}

	/**
	 * Prepares the query for the Charge model.
	 *
	 * @since 4.25.0
	 *
	 * @return ModelQueryBuilder<Charge_Model>
	 */
	public function prepareQuery(): ModelQueryBuilder {
		return new ModelQueryBuilder( Charge_Model::class );
	}
}
