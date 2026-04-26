<?php
/**
 * PayPal Standard Migration User Data helper.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Models\Product;

/**
 * PayPal Standard Migration User Data helper.
 *
 * Helper class to get user data for migration from PayPal Standard to PayPal Checkout.
 *
 * @since 4.25.3
 */
class User_Data {
	/**
	 * User meta key for storing user data for migration.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	private const USER_META_KEY = 'learndash_paypal_standard_migration';

	/**
	 * Returns the migration data for a user.
	 *
	 * @since 4.25.3
	 *
	 * @param int  $user_id    User ID.
	 * @param bool $is_sandbox Whether to use the sandbox environment.
	 *
	 * @return array{
	 *     products?: int[],
	 *     status?: string,
	 *     processed?: int[],
	 * } The migration data for the user.
	 */
	public static function get_migration_data( int $user_id, bool $is_sandbox = false ): array {
		$user_data = get_user_meta( $user_id, self::get_user_meta_key( $is_sandbox ), true );

		if ( empty( $user_data ) ) {
			return [];
		}

		/**
		 * User data is a JSON string, so we need to decode it.
		 *
		 * @var array{
		 *     products?: int[],
		 *     status?: string,
		 *     processed?: int[],
		 * } $user_data The migration data for the user.
		 */
		$user_data = json_decode( Cast::to_string( $user_data ), true );

		return $user_data;
	}

	/**
	 * Updates the migration data for a user.
	 *
	 * @since 4.25.3
	 *
	 * @phpstan-param array{
	 *     products?: int[],
	 *     status?: string,
	 *     processed?: int[],
	 * } $data The migration data for the user.
	 *
	 * @param int                 $user_id    User ID.
	 * @param array<string,mixed> $data       The migration data for the user.
	 * @param bool                $is_sandbox Whether to use the sandbox environment.
	 *
	 * @return void
	 */
	public static function update_migration_data( int $user_id, array $data, bool $is_sandbox = false ): void {
		update_user_meta( $user_id, self::get_user_meta_key( $is_sandbox ), wp_json_encode( $data ) );
	}

	/**
	 * Updates the migrated product data for a user.
	 *
	 * If the number of migrated products is the same as the number of products,
	 * the migration status is updated to 'migrated'.
	 *
	 * @since 4.25.3
	 *
	 * @param int  $user_id    User ID.
	 * @param int  $product_id Product ID.
	 * @param bool $is_sandbox Whether to use the sandbox environment.
	 *
	 * @return void
	 */
	public static function update_migrated_product_data( int $user_id, int $product_id, bool $is_sandbox = false ): void {
		$data = self::get_migration_data( $user_id, $is_sandbox );

		// Initialize the migrated products array if it doesn't exist.
		if ( ! isset( $data['processed'] ) ) {
			$data['processed'] = [];
		}

		// Add the product ID to the migrated products array.
		$data['processed'][] = $product_id;

		$products = array_filter(
			Arr::wrap(
				Arr::get( $data, 'products', [] )
			)
		);

		// Update the migration status to 'migrated' if 'products' as the same length as 'processed'.
		if ( count( $products ) === count( $data['processed'] ) ) {
			$data['status'] = 'migrated';
		}

		self::update_migration_data( $user_id, $data, $is_sandbox );
	}

	/**
	 * Returns whether the user has migrated products.
	 *
	 * Helper method to check if the user has migrated products from PayPal Standard to PayPal Checkout.
	 * Used to skip the revoke access process for migrated subscriptions in the PayPal Standard (IPN) gateway.
	 *
	 * @since 4.25.3
	 *
	 * @param int       $user_id    User ID.
	 * @param Product[] $products   Products.
	 * @param bool      $is_sandbox Whether to use the sandbox environment.
	 *
	 * @return bool Whether the user has migrated products.
	 */
	public static function has_migrated_products( int $user_id, array $products, bool $is_sandbox = false ): bool {
		$data = self::get_migration_data( $user_id, $is_sandbox );

		$ids = array_map(
			function ( $id ) {
				return Cast::to_int( $id );
			},
			array_filter(
				Arr::wrap(
					Arr::get( $data, 'products', [] )
				)
			)
		);

		foreach ( $products as $product ) {
			if ( in_array( $product->get_id(), $ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the user meta key for the given environment.
	 *
	 * @since 4.25.3
	 *
	 * @param bool $is_sandbox Whether to use the sandbox environment.
	 *
	 * @return string The user meta key for the given environment.
	 */
	public static function get_user_meta_key( bool $is_sandbox ): string {
		return self::USER_META_KEY . '_' . ( $is_sandbox ? 'sandbox' : 'live' );
	}
}
