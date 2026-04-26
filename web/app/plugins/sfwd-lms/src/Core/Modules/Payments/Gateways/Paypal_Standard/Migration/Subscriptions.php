<?php
/**
 * PayPal Standard Migration Subscriptions helper.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use LDLMS_Post_Types;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash_Settings_Section;
use WP_User;

/**
 * PayPal Standard Migration Subscriptions helper.
 *
 * Helper class to fetch PayPal Standard (IPN) subscriptions and users, to migrate to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Subscriptions {
	/**
	 * Gets the total number of migrated users.
	 *
	 * @since 4.25.3
	 *
	 * @return int The total number of migrated users.
	 */
	public function get_total_migrated_users(): int {
		$meta_key = User_Data::get_user_meta_key(
			$this->is_test_mode()
		);

		$result = DB::table( 'usermeta' )
			->where( 'meta_key', $meta_key )
			->whereLike( 'meta_value', 'migrated' )
			->count();

		return (int) $result;
	}

	/**
	 * Gets the total number of subscriptions.
	 *
	 * @since 4.25.3
	 *
	 * @param bool $include_migrated Whether to include migrated subscriptions.
	 *
	 * @return int The total number of subscriptions.
	 */
	public function get_total_subscriptions( bool $include_migrated = false ): int {
		$is_sandbox = $this->is_test_mode();

		// Get the migration meta key for filtering.
		$migration_meta_key = User_Data::get_user_meta_key( $is_sandbox );

		$query = DB::table( 'posts', 'transactions' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_gateway' )
						->on( 'postmeta_gateway.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway.meta_key', 'ld_payment_processor', true )
						->andOn( 'postmeta_gateway.meta_value', 'paypal_ipn', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_price_type' )
						->on( 'postmeta_price_type.post_id', 'transactions.id' )
						->andOn( 'postmeta_price_type.meta_key', 'price_type', true )
						->andOn( 'postmeta_price_type.meta_value', 'subscribe', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) use ( $is_sandbox ) {
					$builder->innerJoin( 'postmeta', 'postmeta_test_mode' )
						->on( 'postmeta_test_mode.post_id', 'transactions.id' )
						->andOn( 'postmeta_test_mode.meta_key', 'is_test_mode', true )
						->andOn( 'postmeta_test_mode.meta_value', $is_sandbox ? '1' : '0', true );
				}
			);

		// Add migration status filtering based on include_migrated parameter.
		if ( ! $include_migrated ) {
			// Exclude migrated users by using a LEFT JOIN to filter out users with migration data containing 'migrated'.
			$query->join(
				function ( JoinQueryBuilder $builder ) use ( $migration_meta_key ) {
					$builder->leftJoin( 'usermeta', 'migration_status' )
						->on( 'migration_status.user_id', 'transactions.post_author' )
						->andOn( 'migration_status.meta_key', $migration_meta_key, true );
				}
			)
			->where(
				function ( $query ) {
					$query->where( 'migration_status.user_id', null, 'IS NULL' )
						->orWhere( 'migration_status.meta_value', '%migrated%', 'NOT LIKE' );
				}
			);
		}

		$result = $query
			->where( 'transactions.post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'transactions.post_status', 'publish' )
			->count();

		return (int) $result;
	}

	/**
	 * Gets the current subscriptions.
	 *
	 * @since 4.25.3
	 *
	 * @param int  $page             The page number (1-based). 1 by default.
	 * @param int  $per_page         The number of items per page. 10 by default.
	 * @param bool $include_migrated Whether to include migrated subscriptions. False by default.
	 *
	 * @return array<int,array{
	 *     'name': string,
	 *     'email': string,
	 *     'subscriptions': string[],
	 *     'paypal_subscription_ids': string[],
	 *     'migration_status': string
	 * }>
	 */
	public function get_current_subscriptions( int $page = 1, int $per_page = 10, bool $include_migrated = false ): array {
		$is_sandbox = $this->is_test_mode();

		// Calculate offset for pagination.
		$offset = ( $page - 1 ) * $per_page;

		// Get the migration meta key for filtering.
		$migration_meta_key = User_Data::get_user_meta_key( $is_sandbox );

		// Get all users with PayPal IPN subscriptions.
		$query = DB::table( 'posts', 'transactions' )
			->select( 'transactions.post_author as user_id', 'postmeta_gateway_transaction.meta_value as gateway_transaction' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_gateway' )
						->on( 'postmeta_gateway.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway.meta_key', 'ld_payment_processor', true )
						->andOn( 'postmeta_gateway.meta_value', 'paypal_ipn', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_price_type' )
						->on( 'postmeta_price_type.post_id', 'transactions.id' )
						->andOn( 'postmeta_price_type.meta_key', 'price_type', true )
						->andOn( 'postmeta_price_type.meta_value', 'subscribe', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) use ( $is_sandbox ) {
					$builder->innerJoin( 'postmeta', 'postmeta_test_mode' )
						->on( 'postmeta_test_mode.post_id', 'transactions.id' )
						->andOn( 'postmeta_test_mode.meta_key', 'is_test_mode', true )
						->andOn( 'postmeta_test_mode.meta_value', $is_sandbox ? '1' : '0', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_gateway_transaction' )
						->on( 'postmeta_gateway_transaction.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway_transaction.meta_key', 'gateway_transaction', true );
				}
			);

		// Add migration status filtering based on include_migrated parameter.
		if ( ! $include_migrated ) {
			// Exclude migrated users by using a LEFT JOIN to filter out users with migration data containing 'migrated'.
			$query->join(
				function ( JoinQueryBuilder $builder ) use ( $migration_meta_key ) {
					$builder->leftJoin( 'usermeta', 'migration_status' )
						->on( 'migration_status.user_id', 'transactions.post_author' )
						->andOn( 'migration_status.meta_key', $migration_meta_key, true );
				}
			)
			->where(
				function ( $query ) {
					$query->where( 'migration_status.user_id', null, 'IS NULL' )
						->orWhere( 'migration_status.meta_value', '%migrated%', 'NOT LIKE' );
				}
			);
		}

		$results = $query
			->where( 'transactions.post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'transactions.post_status', 'publish' )
			->groupBy( 'transactions.post_author' )
			->orderBy( 'transactions.post_author', 'ASC' )
			->limit( $per_page )
			->offset( $offset )
			->getAll( ARRAY_A );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		// Group subscriptions by user and collect PayPal subscription IDs.
		$user_subscriptions = [];
		foreach ( $results as $result ) {
			$user_id = Cast::to_int( $result['user_id'] );

			if ( ! isset( $user_subscriptions[ $user_id ] ) ) {
				$user_subscriptions[ $user_id ] = [
					'paypal_subscription_ids' => [],
				];
			}

			// Extract PayPal subscription ID from gateway transaction data.
			if ( ! empty( $result['gateway_transaction'] ) ) {
				$gateway_transaction = maybe_unserialize( $result['gateway_transaction'] );
				if ( is_array( $gateway_transaction ) && isset( $gateway_transaction['id'] ) ) {
					$user_subscriptions[ $user_id ]['paypal_subscription_ids'][] = $gateway_transaction['id'];
				}
			}
		}

		// Build the final result array.
		$subscriptions = [];
		foreach ( $user_subscriptions as $user_id => $data ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			// Get unique PayPal subscription IDs for this user.
			$paypal_subscription_ids = array_unique( $data['paypal_subscription_ids'] );

			// Get user's subscribed course/group names.
			$product_ids = $this->get_user_subscribed_product_ids( $user_id );

			if ( empty( $product_ids ) ) {
				continue;
			}

			$subscription_names = [];

			foreach ( $product_ids as $product_id ) {
				$post = get_post( $product_id );
				if ( $post ) {
					$subscription_names[] = $post->post_title;
				}
			}

			// Get migration status.
			$migration_data   = User_Data::get_migration_data( $user_id, $is_sandbox );
			$migration_status = 'not-started';

			if ( Arr::has( $migration_data, 'status' ) ) {
				$migration_status = Cast::to_string( Arr::get( $migration_data, 'status', 'not-started' ) );

				if ( $migration_status === 'pending' ) {
					$migration_status = 'not-started';
				}
			}

			$subscriptions[] = [
				'name'                    => $user->display_name,
				'email'                   => $user->user_email,
				'subscriptions'           => $subscription_names,
				'paypal_subscription_ids' => $paypal_subscription_ids,
				'migration_status'        => $migration_status,
			];
		}

		return $subscriptions;
	}

	/**
	 * Returns a list of email addresses of users with subscriptions that require migration.
	 *
	 * @since 4.25.3
	 *
	 * @return string[] Array of email addresses.
	 */
	public function get_user_emails(): array {
		$is_sandbox = $this->is_test_mode();

		// Get all user emails with PayPal IPN subscriptions in one query.
		$results = DB::table( 'posts', 'transactions' )
			->select( 'users.user_email' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_gateway' )
						->on( 'postmeta_gateway.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway.meta_key', 'ld_payment_processor', true )
						->andOn( 'postmeta_gateway.meta_value', 'paypal_ipn', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_price_type' )
						->on( 'postmeta_price_type.post_id', 'transactions.id' )
						->andOn( 'postmeta_price_type.meta_key', 'price_type', true )
						->andOn( 'postmeta_price_type.meta_value', 'subscribe', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) use ( $is_sandbox ) {
					$builder->innerJoin( 'postmeta', 'postmeta_test_mode' )
						->on( 'postmeta_test_mode.post_id', 'transactions.id' )
						->andOn( 'postmeta_test_mode.meta_key', 'is_test_mode', true )
						->andOn( 'postmeta_test_mode.meta_value', $is_sandbox ? '1' : '0', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'users', 'users' )
						->on( 'users.ID', 'transactions.post_author' );
				}
			)
			->where( 'transactions.post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'transactions.post_status', 'publish' )
			->groupBy( 'users.user_email' )
			->orderBy( 'users.user_email', 'ASC' )
			->getAll( ARRAY_A );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		return array_column( $results, 'user_email' );
	}

	/**
	 * Gets the user subscribed product IDs.
	 *
	 * @since 4.25.3
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return int[] The user subscribed product IDs.
	 */
	public function get_user_subscribed_product_ids( int $user_id ): array {
		// Fetches product IDs from PayPal Standard transactions.
		$results = DB::table( 'posts' )
			->select( 'COALESCE(postmeta_course.meta_value, postmeta_product.meta_value, postmeta_group.meta_value) as product_id' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_gateway' )
						->on( 'postmeta_gateway.post_id', 'id' )
						->andOn( 'postmeta_gateway.meta_key', 'ld_payment_processor', true )
						->andOn( 'postmeta_gateway.meta_value', 'paypal_ipn', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_product' )
						->on( 'postmeta_product.post_id', 'id' )
						->andOn( 'postmeta_product.meta_key', 'post_id', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_course' )
						->on( 'postmeta_course.post_id', 'id' )
						->andOn( 'postmeta_course.meta_key', 'course_id', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_group' )
						->on( 'postmeta_group.post_id', 'id' )
						->andOn( 'postmeta_group.meta_key', 'group_id', true );
				}
			)
			->where( 'post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'post_author', $user_id )
			->where( 'post_status', 'publish' )
			->groupBy( 'COALESCE(postmeta_course.meta_value, postmeta_product.meta_value)' )
			->getAll( ARRAY_A );

		if (
			empty( $results )
			|| ! is_array( $results )
		) {
			return [];
		}

		// Extracts IDs from results.
		$product_ids = array_map(
			'intval',
			wp_list_pluck(
				$results,
				'product_id'
			)
		);

		// Returns empty array if no IDs are found.
		if ( empty( $product_ids ) ) {
			return [];
		}

		// Get user's enrolled courses and groups.
		$enrolled_course_ids = learndash_user_get_enrolled_courses( $user_id );
		$enrolled_group_ids  = learndash_get_users_group_ids( $user_id );

		// Removes product IDs that are not enrolled in courses or groups.
		$product_ids = array_values(
			array_filter(
				$product_ids,
				function ( $product_id ) use ( $enrolled_course_ids, $enrolled_group_ids ) {
					return in_array( $product_id, $enrolled_course_ids, true )
						|| in_array( $product_id, $enrolled_group_ids, true );
				}
			)
		);

		return $product_ids;
	}

	/**
	 * Gets the PayPal subscription ID for paypal_ipn based on user_id and course_id.
	 *
	 * @since 4.25.3
	 *
	 * @param int $user_id    The user ID.
	 * @param int $product_id The product ID.
	 *
	 * @return string The PayPal subscription ID, empty string if not found.
	 */
	public function get_paypal_subscription_id( int $user_id, int $product_id ): string {
		$is_sandbox = $this->is_test_mode();

		$result = DB::table( 'posts', 'transactions' )
			->select( 'postmeta_gateway_transaction.meta_value as gateway_transaction' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->innerJoin( 'postmeta', 'postmeta_gateway' )
						->on( 'postmeta_gateway.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway.meta_key', 'ld_payment_processor', true )
						->andOn( 'postmeta_gateway.meta_value', 'paypal_ipn', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) use ( $is_sandbox ) {
					$builder->innerJoin( 'postmeta', 'postmeta_test_mode' )
						->on( 'postmeta_test_mode.post_id', 'transactions.id' )
						->andOn( 'postmeta_test_mode.meta_key', 'is_test_mode', true )
						->andOn( 'postmeta_test_mode.meta_value', $is_sandbox ? '1' : '0', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_gateway_transaction' )
						->on( 'postmeta_gateway_transaction.post_id', 'transactions.id' )
						->andOn( 'postmeta_gateway_transaction.meta_key', 'gateway_transaction', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_course' )
						->on( 'postmeta_course.post_id', 'transactions.id' )
						->andOn( 'postmeta_course.meta_key', 'course_id', true );
				}
			)
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder->leftJoin( 'postmeta', 'postmeta_product' )
						->on( 'postmeta_product.post_id', 'transactions.id' )
						->andOn( 'postmeta_product.meta_key', 'post_id', true );
				}
			)
			->where( 'transactions.post_type', LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) )
			->where( 'transactions.post_status', 'publish' )
			->where( 'transactions.post_author', $user_id )
			->where(
				function ( $query ) use ( $product_id ) {
					$query->where( 'postmeta_course.meta_value', $product_id )
						->orWhere( 'postmeta_product.meta_value', $product_id );
				}
			)
			->orderBy( 'transactions.post_date', 'DESC' )
			->limit( 1 )
			->getAll( ARRAY_A );

		if ( empty( $result ) || ! is_array( $result ) || ! isset( $result[0]['gateway_transaction'] ) ) {
			return '';
		}

		$gateway_transaction = maybe_unserialize( $result[0]['gateway_transaction'] );
		if ( ! is_array( $gateway_transaction ) || ! isset( $gateway_transaction['id'] ) ) {
			return '';
		}

		return Cast::to_string( $gateway_transaction['id'] );
	}

	/**
	 * Checks if the test mode is enabled.
	 *
	 * @since 4.25.3
	 *
	 * @return bool True if the test mode is enabled, false otherwise.
	 */
	private function is_test_mode(): bool {
		$settings = array_filter(
			Arr::wrap(
				LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' )
			)
		);

		return 'yes' === Cast::to_string( Arr::get( $settings, 'paypal_sandbox', 'no' ) );
	}
}
