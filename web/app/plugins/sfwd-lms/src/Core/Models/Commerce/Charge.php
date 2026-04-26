<?php
/**
 * Charge model class.
 *
 * Represents a charge (grandchild of Order, child of Subscription) in the LearnDash payment system.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Commerce;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Repositories\Repository;
use LearnDash\Core\Utilities\Cast;

/**
 * Charge model class.
 *
 * @since 4.25.0
 */
class Charge extends Transaction {
	/**
	 * Charge meta key 'Status'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_status = 'status';

	/**
	 * Charge meta key 'Price'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_price = 'price';

	/**
	 * Charge meta key 'Is trial'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_is_trial = 'is_trial';

	/**
	 * Charge status 'Success'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $status_success = 'success';

	/**
	 * Charge status 'Failed'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $status_failed = 'failed';

	/**
	 * Creates a charge.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $subscription_id The subscription ID.
	 * @param int    $user_id         The user ID.
	 * @param float  $price           The price of the charge.
	 * @param string $status          The status of the charge.
	 * @param bool   $is_trial        Whether the charge is a trial. Default false.
	 *
	 * @return void
	 */
	public static function create(
		int $subscription_id,
		int $user_id,
		float $price,
		string $status,
		bool $is_trial = false
	): void {
		Repository::save_post_with_meta(
			[
				'post_author' => $user_id,
				'post_parent' => $subscription_id,
				'post_status' => 'publish',
				'post_type'   => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ),
			],
			self::map_charge_meta( $price, $status, $is_trial )
		);
	}

	/**
	 * Returns the price of the charge.
	 *
	 * @since 4.25.0
	 *
	 * @return float
	 */
	public function get_price(): float {
		return Cast::to_float( $this->getAttribute( self::$meta_key_price ) );
	}

	/**
	 * Returns the timestamp when the charge was created.
	 *
	 * @since 4.25.0
	 *
	 * @return int
	 */
	public function get_date(): int {
		return Cast::to_int( strtotime( $this->get_post()->post_date_gmt ) );
	}

	/**
	 * Returns whether the charge is a trial.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function is_trial(): bool {
		return Cast::to_bool( $this->getAttribute( self::$meta_key_is_trial ) );
	}

	/**
	 * Returns whether the charge is successful.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->getAttribute( self::$meta_key_status ) === self::$status_success;
	}

	/**
	 * Returns whether the charge is failed.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function is_failed(): bool {
		return $this->getAttribute( self::$meta_key_status ) === self::$status_failed;
	}

	/**
	 * Maps the charge meta.
	 *
	 * @since 4.25.0
	 *
	 * @param float  $price    The price of the charge.
	 * @param string $status   The status of the charge.
	 * @param bool   $is_trial Whether the charge is a trial. Default false.
	 *
	 * @return array<string,mixed>
	 */
	private static function map_charge_meta( float $price, string $status, bool $is_trial = false ): array {
		return [
			self::$meta_key_price    => $price,
			self::$meta_key_status   => $status,
			self::$meta_key_is_trial => $is_trial,
		];
	}
}
