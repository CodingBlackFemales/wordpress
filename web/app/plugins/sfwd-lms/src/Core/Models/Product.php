<?php
/**
 * This class provides the easy way to operate a product (a course or a group).
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use Exception;
use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Custom_Label;
use Learndash_Pricing_DTO;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use StellarWP\Learndash\StellarWP\DB\DB;
use WP_User;

/**
 * Product model class.
 *
 * @since 4.6.0
 */
class Product extends Post {
	/**
	 * Has trial.
	 *
	 * @since 4.16.0
	 *
	 * @var bool|null Whether the product has a trial or not.
	 */
	protected $has_trial = null;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.5.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
		);
	}

	/**
	 *
	 * Returns a product type (buy now, subscription, free, open, closed, etc).
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_pricing_type(): string {
		/**
		 * Filters product pricing type.
		 *
		 * @since 4.5.0
		 *
		 * @param string  $pricing_type Product pricing type.
		 * @param Product $product      Product model.
		 *
		 * @return string Product pricing type.
		 */
		return apply_filters(
			'learndash_model_product_pricing_type',
			$this->get_pricing_settings()['type'] ?? '',
			$this
		);
	}

	/**
	 * Returns if the product price type is open.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_price_type_open(): bool {
		return LEARNDASH_PRICE_TYPE_OPEN === $this->get_pricing_type();
	}

	/**
	 * Returns if the product price type is free.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_price_type_free(): bool {
		return LEARNDASH_PRICE_TYPE_FREE === $this->get_pricing_type();
	}

	/**
	 * Returns if the product price type is paynow.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_price_type_paynow(): bool {
		return LEARNDASH_PRICE_TYPE_PAYNOW === $this->get_pricing_type();
	}

	/**
	 * Returns if the product price type is subscribe.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_price_type_subscribe(): bool {
		return LEARNDASH_PRICE_TYPE_SUBSCRIBE === $this->get_pricing_type();
	}

	/**
	 * Returns if the product price type is closed.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_price_type_closed(): bool {
		return LEARNDASH_PRICE_TYPE_CLOSED === $this->get_pricing_type();
	}

	/**
	 * Returns whether the product supports coupons.
	 *
	 * @since 4.20.2.1
	 *
	 * @return bool
	 */
	public function supports_coupon(): bool {
		$supports_coupon = $this->is_price_type_paynow();

		/**
		 * Filters whether the product supports coupons.
		 *
		 * @since 4.20.2.1
		 *
		 * @param bool    $supports_coupon Whether the product supports coupons.
		 * @param Product $product         Product model.
		 *
		 * @return bool Whether the product supports coupons.
		 */
		return apply_filters( 'learndash_model_product_supports_coupon', $supports_coupon, $this );
	}

	/**
	 * Returns true when the product has a trial.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function has_trial(): bool {
		if ( $this->has_trial === null ) {
			$pricing = $this->get_pricing();

			$this->has_trial = $pricing->trial_duration_value > 0 && ! empty( $pricing->trial_duration_length );
		}

		return $this->has_trial;
	}

	/**
	 * Returns whether a product can be purchased.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function can_be_purchased( $user = null ): bool {
		$user = $this->map_user( $user );

		$can_be_purchased = true;

		if (
			$this->has_ended( $user )
			|| $this->is_pre_ordered( $user )
			|| $this->user_has_access( $user )
			|| 0 === $this->get_seats_available( $user )
		) {
			$can_be_purchased = false;
		}

		/**
		 * Filters whether a product has ended.
		 *
		 * @since 4.7.0
		 * @since Added the $user parameter.
		 *
		 * @param bool        $can_be_purchased True if a product can be purchased, false otherwise.
		 * @param Product     $product          Product model.
		 * @param WP_User|int $user             The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if a product can be purchased, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_can_be_purchased', $can_be_purchased, $this, $user );
	}

	/**
	 * Returns whether a product has started. If the product has not a start date or is open, it returns true.
	 *
	 * @since 4.7.0
	 *
	 * @return bool
	 */
	public function has_started(): bool {
		// open products are not affected by the start date.
		$has_started = $this->is_price_type_open();

		$start_date = $this->get_start_date();

		if ( ! $has_started ) {
			$has_started = null === $start_date || $start_date <= time();
		}

		/**
		 * Filters whether a product has started.
		 *
		 * @since 4.7.0
		 *
		 * @param bool     $has_started True if a product has started, false otherwise
		 * @param ?int     $start_date  The start date.
		 * @param Product  $product     Product model.
		 *
		 * @return bool True if a product has started, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_has_started', $has_started, $start_date, $this );
	}

	/**
	 * Returns whether a product has ended. If the product has no end date or is open, it returns false.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Added $user parameter.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function has_ended( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$end_date                  = $this->get_end_date();
		$has_ended                 = ! $this->is_price_type_open(); // open products are not affected by the end date.
		$extended_access_timestamp = 0;

		// Check if the user has extended access.

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$extended_access_timestamp = learndash_course_get_extended_access_timestamp( $this->post->ID, $user_id );
		}

		if (
			! empty( $extended_access_timestamp )
			&& $extended_access_timestamp > $end_date
		) {
			$end_date = $extended_access_timestamp;
		}

		// Check if the product has ended.

		if ( $has_ended ) {
			$has_ended = $end_date !== null && $end_date <= time();
		}

		/**
		 * Filters whether a product has ended.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added $user parameter.
		 *
		 * @param bool        $has_ended True if a product has ended, false otherwise.
		 * @param ?int        $end_date  The end date.
		 * @param Product     $product   Product model.
		 * @param WP_User|int $user      The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if a product has ended, false otherwise.
		 */
		return apply_filters(
			'learndash_model_product_has_ended',
			$has_ended,
			$end_date,
			$this,
			$user
		);
	}

	/**
	 * Returns the start date. Null if the product has not a start date.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return ?int
	 */
	public function get_start_date( $user = null ): ?int {
		$user = $this->map_user( $user );

		$start_date = null;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$associated_group = $this->get_first_course_group_for_user( $user );

			if ( $associated_group ) {
				$start_date = $associated_group->get_start_date( $user );
			} else {
				$start_date = Cast::to_int(
					$this->get_setting( 'course_start_date' )
				);
			}
		} elseif ( $this->is_post_type_by_key( LDLMS_Post_Types::GROUP ) ) {
			$start_date = Cast::to_int(
				$this->get_setting( 'group_start_date' )
			);
		}

		$start_date = ! empty( $start_date ) ? $start_date : null;

		/**
		 * Filters the product start date.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param ?int        $start_date Product start date. Null if the product has not a start date.
		 * @param Product     $product    Product model.
		 * @param WP_User|int $user       The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return ?int Product start date. Null if the product has not a start date.
		 */
		return apply_filters( 'learndash_model_product_start_date', $start_date, $this, $user );
	}

	/**
	 * Returns the end date. Null if the product has not an end date.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return ?int The timestamp of the end date. Null if the product has no end date.
	 */
	public function get_end_date( $user = null ): ?int {
		$user = $this->map_user( $user );

		$end_date = null;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$associated_group = $this->get_first_course_group_for_user( $user );

			if ( $associated_group ) {
				$end_date = $associated_group->get_end_date( $user );
			} else {
				$end_date = Cast::to_int(
					$this->get_setting( 'course_end_date' )
				);
			}
		} elseif ( $this->is_post_type_by_key( LDLMS_Post_Types::GROUP ) ) {
			$end_date = Cast::to_int(
				$this->get_setting( 'group_end_date' )
			);
		}

		$end_date = ! empty( $end_date ) ? $end_date : null;

		/**
		 * Filters the product end date.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param ?int        $end_date Product end date. Null if the product has not an end date.
		 * @param Product     $product  Product model.
		 * @param WP_User|int $user     The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return ?int Product end date. Null if the product has not an end date.
		 */
		return apply_filters( 'learndash_model_product_end_date', $end_date, $this, $user );
	}

	/**
	 * Returns the seats limit. Null if the product has not a seats limit.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return ?int
	 */
	public function get_seats_limit( $user = null ): ?int {
		$user = $this->map_user( $user );

		$seats_limit = null;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$associated_group = $this->get_first_course_group_for_user( $user );

			if ( $associated_group ) {
				$seats_limit = $associated_group->get_seats_limit( $user );
			} else {
				$seats_limit = Cast::to_int(
					$this->get_setting( 'course_seats_limit' )
				);
			}
		} elseif ( $this->is_post_type_by_key( LDLMS_Post_Types::GROUP ) ) {
			$seats_limit = Cast::to_int(
				$this->get_setting( 'group_seats_limit' )
			);
		}

		$seats_limit = ! empty( $seats_limit ) && $seats_limit > 0 ? $seats_limit : null;

		/**
		 * Filters the product seats limit.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param ?int        $seats_limit Product seats limit. Null if the product has not a seats limit.
		 * @param Product     $product     Product model.
		 * @param WP_User|int $user        The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return ?int Product seats limit. Null if the product has not a seats limit.
		 */
		return apply_filters( 'learndash_model_product_seats_limit', $seats_limit, $this, $user );
	}

	/**
	 * Returns the number of seats used in the product.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Added $user parameter.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int|null The number of seats used in the product. Null if it is not possible to count.
	 */
	public function get_seats_used( $user = null ): ?int {
		$user       = $this->map_user( $user );
		$seats_used = null;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$associated_group = $this->get_first_course_group_for_user( $user );

			if ( $associated_group ) {
				$seats_used = $associated_group->get_seats_used( $user );
			} else {
				$seats_used = DB::table( 'usermeta' )
						->where( 'meta_key', "course_{$this->post->ID}_access_from" )
						->count();
			}
		} elseif ( $this->is_post_type_by_key( LDLMS_Post_Types::GROUP ) ) {
			$seats_used = DB::table( 'usermeta' )
						->where( 'meta_key', "learndash_group_users_{$this->post->ID}" )
						->count();
		}

		/**
		 * Filters the product seats used.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param int|null    $seats_used Product seats used.
		 * @param Product     $product    Product model.
		 * @param WP_User|int $user       The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return int|null Product seats used.
		 */
		return apply_filters( 'learndash_model_product_seats_used', $seats_used, $this, $user );
	}

	/**
	 * Returns the number of seats available.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Added $user parameter.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int|null The number of seats available. If there is no limit, it returns null.
	 */
	public function get_seats_available( $user = null ): ?int {
		$user            = $this->map_user( $user );
		$seats_limit     = $this->get_seats_limit( $user );
		$seats_available = null;

		if ( ! is_null( $seats_limit ) ) {
			$seats_available = max( $seats_limit - $this->get_seats_used( $user ), 0 );
		}

		/**
		 * Filters the product seats available.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param int|null    $seats_available Product seats available.
		 * @param Product     $product         Product model.
		 * @param WP_User|int $user            The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return int|null Product seats available.
		 */
		return apply_filters( 'learndash_model_product_seats_available', $seats_available, $this, $user );
	}

	/**
	 * Returns the product final price, after applying the coupon.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return float
	 */
	public function get_final_price( $user = null ): float {
		$user = $this->map_user( $user, false );

		/**
		 * Filters course/group price.
		 *
		 * @since 4.1.0
		 *
		 * @param float    $price   Course/Group Price.
		 * @param int      $post_id Course/Group ID.
		 * @param int|null $user_id User ID.
		 *
		 * @return float Course/Group Price.
		 */
		$price = apply_filters(
			'learndash_get_price_by_coupon',
			$this->get_pricing( $user )->price,
			$this->get_id(),
			$user instanceof WP_User ? $user->ID : $user
		);

		/**
		 * Filters the product final price, after applying the coupon.
		 *
		 * @since 4.25.0
		 *
		 * @param float       $price    Product final price, after applying the coupon.
		 * @param WP_User|int $user     The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 * @param Product     $product  Product model.
		 *
		 * @return float Product final price, after applying the coupon.
		 */
		return apply_filters(
			'learndash_model_product_final_price',
			$price,
			$user,
			$this
		);
	}

	/**
	 * Returns the numeric price.
	 *
	 * If the price cannot be converted to a float, it returns 0.
	 *
	 * @since 4.25.0
	 *
	 * @return float
	 */
	public function get_price(): float {
		return learndash_get_price_as_float( (string) ( $this->get_pricing_settings()['price'] ?? 0.0 ) );
	}

	/**
	 * Returns the display price.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_display_price(): string {
		$price = strval( $this->get_pricing_settings()['price'] ?? '' );

		/**
		 * Filters product display price.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $display_price Product display price.
		 * @param string  $price         Product price.
		 * @param Product $product       Product model.
		 *
		 * @return string Product display price.
		 */
		return apply_filters(
			'learndash_model_product_display_price',
			$this->get_formatted_display_price( $price ),
			$price,
			$this
		);
	}

	/**
	 * Returns the numeric trial price.
	 *
	 * If the trial price cannot be converted to a float, it returns 0.
	 *
	 * @since 4.25.0
	 *
	 * @return float
	 */
	public function get_trial_price(): float {
		return learndash_get_price_as_float( (string) ( $this->get_pricing_settings()['trial_price'] ?? 0.0 ) );
	}

	/**
	 * Returns the display trial price.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_display_trial_price(): string {
		$trial_price = strval( $this->get_pricing_settings()['trial_price'] ?? '' );

		/**
		 * Filters product display trial price.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $display_trial_price Product display trial price.
		 * @param string  $trial_price         Product trial price.
		 * @param Product $product             Product model.
		 *
		 * @return string Product display trial price.
		 */
		return apply_filters(
			'learndash_model_product_display_trial_price',
			$this->get_formatted_display_price( $trial_price ),
			$trial_price,
			$this
		);
	}

	/**
	 * Returns the formatted display price (with the currency).
	 *
	 * @since 4.6.0
	 *
	 * @param string $price Price.
	 *
	 * @return string
	 */
	private function get_formatted_display_price( string $price ): string {
		$float_price = learndash_get_price_as_float( $price );

		if (
			empty( $float_price )
			&& empty( $price )
		) {
			return __( 'Free', 'learndash' );
		}

		if ( empty( $float_price ) ) {
			return learndash_get_price_formatted( $price );
		}

		/** This filter is documented in src/Core/Models/Product.php */
		$float_price = apply_filters(
			'learndash_get_price_by_coupon',
			$float_price,
			$this->get_id(),
			get_current_user_id()
		);

		return learndash_get_price_formatted( $float_price );
	}

	/**
	 * Returns the trial interval message.
	 *
	 * @since 4.16.0
	 *
	 * @param array<string, mixed> $pricing Pricing settings.
	 *
	 * @return string
	 */
	private function get_trial_interval_message( $pricing ): string {
		$repeats          = absint( Cast::to_int( Arr::get( $pricing, 'repeats', 0 ) ) );
		$interval         = absint( Cast::to_int( Arr::get( $pricing, 'interval', 0 ) ) );
		$frequency        = esc_html( Cast::to_string( Arr::get( $pricing, 'frequency', '' ) ) );
		$repeat_frequency = esc_html( Cast::to_string( Arr::get( $pricing, 'repeat_frequency', '' ) ) );
		$price            = esc_html( learndash_get_price_formatted( Arr::get( $pricing, 'price', 0 ) ) );
		$trial_price      = esc_html( learndash_get_price_formatted( Arr::get( $pricing, 'trial_price', 0 ) ) );
		$trial_interval   = absint( Cast::to_int( Arr::get( $pricing, 'trial_interval', 0 ) ) );
		$trial_frequency  = esc_html( Cast::to_string( Arr::get( $pricing, 'trial_frequency', '' ) ) );

		$data   = [];
		$data[] = $trial_interval;  // 1.
		$data[] = $trial_frequency; // 2.
		$data[] = $trial_price;     // 3.
		$data[] = $price;           // 4.

		if ( ! $repeats ) {
			$data[] = $interval;  // 5.
			$data[] = $frequency; // 6.

			if (
				$trial_interval === 1
				&& $interval === 1
			) {
				return vsprintf(
					// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency.
					_x(
						'First %2$s %3$s, then %4$s every %6$s',
						'If the trial interval and subscription interval are both 1. Example: First week $10, then $20 every week',
						'learndash'
					),
					$data
				);
			} elseif (
				$trial_interval === 1
				&& $interval > 1
			) {
				return vsprintf(
					// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency.
					_x(
						'First %2$s %3$s, then %4$s every %5$s %6$s',
						'If the trial interval is 1 and subscription interval > 1. Example: First week $10, then $20 every 2 weeks',
						'learndash'
					),
					$data
				);
			} elseif (
				$trial_interval > 1
				&& $interval === 1
			) {
				return vsprintf(
					// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency.
					_x(
						'First %1$s %2$s %3$s, then %4$s every %6$s',
						'If the trial interval > 1 and subscription interval is 1. Example: First 2 weeks $10, then $20 every week',
						'learndash'
					),
					$data
				);
			}

			return vsprintf(
				// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency.
				_x(
					'First %1$s %2$s %3$s, then %4$s every %5$s %6$s',
					'If the trial interval > 1 and subscription interval > 1. Example: First 2 weeks $10, then $20 every 2 weeks',
					'learndash'
				),
				$data
			);
		}

		$data[] = $interval;            // 5.
		$data[] = $frequency;           // 6.
		$data[] = $interval * $repeats; // 7.
		$data[] = $repeat_frequency;    // 8.

		if (
			$trial_interval === 1
			&& $interval === 1
		) {
			return vsprintf(
				// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency, %s$7 = entire duration, %8$s = repeat frequency.
				_x(
					'First %2$s %3$s, then %4$s every %6$s for %7$s %8$s',
					'If the trial interval and subscription interval are both 1. Example: First week $10, then $20 every week for 20 weeks',
					'learndash'
				),
				$data
			);
		} elseif (
			$trial_interval === 1
			&& $interval > 1
		) {
			return vsprintf(
				// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency, %s$7 = entire duration, %8$s = repeat frequency.
				_x(
					'First %2$s %3$s, then %4$s every %5$s %6$s for %7$s %8$s',
					'If the trial interval is 1 and subscription interval > 1. Example: First week $10, then $20 every 2 weeks for 20 weeks',
					'learndash'
				),
				$data
			);
		} elseif (
			$trial_interval > 1
			&& $interval === 1
		) {
			return vsprintf(
				// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency, %s$7 = entire duration, %8$s = repeat frequency.
				_x(
					'First %1$s %2$s %3$s, then %4$s every %6$s for %7$s %8$s',
					'If the trial interval > 1 and subscription interval is 1. Example: First 2 weeks $10, then $20 every week for 20 weeks',
					'learndash'
				),
				$data
			);
		}

		return vsprintf(
			// translators: placeholder: %1$s = trial interval, %2$s = trial frequency, %3$s = trial price, %4$s = price, %5$s = interval, %6$s = frequency, %s$7 = entire duration, %8$s = repeat frequency.
			_x(
				'First %1$s %2$s %3$s, then %4$s every %5$s %6$s for %7$s %8$s',
				'Example: First 2 weeks $20, then $20 every 2 weeks for 20 weeks',
				'learndash'
			),
			$data
		);
	}

	/**
	 * Returns the interval message.
	 *
	 * @since 4.16.0
	 *
	 * @return string
	 */
	public function get_interval_message(): string {
		$pricing  = $this->get_pricing_settings();
		$interval = absint( Cast::to_int( Arr::get( $pricing, 'interval', 0 ) ) );
		$message  = '';

		if ( $this->has_trial() ) {
			$message = $this->get_trial_interval_message( $pricing );
		} elseif ( $interval ) {
			$repeats          = absint( Cast::to_int( Arr::get( $pricing, 'repeats', 0 ) ) );
			$frequency        = esc_html( Cast::to_string( Arr::get( $pricing, 'frequency', '' ) ) );
			$repeat_frequency = esc_html( Cast::to_string( Arr::get( $pricing, 'repeat_frequency', '' ) ) );
			$price            = esc_html( learndash_get_price_formatted( Arr::get( $pricing, 'price', 0 ) ) );

			$data   = [];
			$data[] = $price;     // 1.
			$data[] = $interval;  // 2.
			$data[] = $frequency; // 3.

			if ( ! $repeats ) {
				if ( $interval === 1 ) {
					$message = vsprintf(
						// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency.
						_x( '%1$s every %3$s', 'Subscribe with an interval of 1. Example: $20 every month', 'learndash' ),
						$data
					);
				} else {
					$message = vsprintf(
						// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency.
						_x( '%1$s every %2$s %3$s', 'Subscribe with an interval > 1. Example: $20 every 2 weeks', 'learndash' ),
						$data
					);
				}
			} else {
				$data[] = $interval * $repeats; // 4.
				$data[] = $repeat_frequency;    // 5.

				if ( $interval === 1 ) {
					$message = vsprintf(
						// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency, %4$s = entire duration, %5$s = repeat frequency.
						_x( '%1$s every %3$s for %4$s %5$s', 'Repeating subscription with an interval of 1. Example: $20 every week for 20 weeks', 'learndash' ),
						$data
					);
				} else {
					$message = vsprintf(
						// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency, %4$s = entire duration, %5$s = repeat frequency.
						_x( '%1$s every %2$s %3$s for %4$s %5$s', 'Repeating subscription with an interval > 1. Example: $20 every 2 weeks for 20 weeks', 'learndash' ),
						$data
					);
				}
			}
		}

		/**
		 * Filters the product interval message.
		 *
		 * @since 4.16.0
		 *
		 * @param string  $message Product interval message.
		 * @param Product $product Product model.
		 *
		 * @return string Product interval message.
		 */
		$message = apply_filters( 'learndash_model_product_interval_message', $message, $this );

		return Cast::to_string( $message );
	}

	/**
	 * Returns the product type.
	 *
	 * @since 4.16.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return LDLMS_Post_Types::get_post_type_key( $this->post->post_type );
	}

	/**
	 * Returns a product type label. Usually "Course" or "Group".
	 *
	 * @since 4.5.0
	 * @since 4.21.0 Added optional $force_lowercase parameter. Default is false.
	 *
	 * @param bool $force_lowercase Whether to return the label in lowercase.
	 *
	 * @return string
	 */
	public function get_type_label( bool $force_lowercase = false ): string {
		$post_type_key = LDLMS_Post_Types::get_post_type_key( $this->post->post_type );

		if ( $force_lowercase ) {
			$label = LearnDash_Custom_Label::label_to_lower( $post_type_key );
		} else {
			$label = LearnDash_Custom_Label::get_label( $post_type_key );
		}

		/**
		 * Filters product type label.
		 *
		 * @since 4.5.0
		 * @since 4.21.0 Added the $force_lowercase argument.
		 *
		 * @param string  $label           Product type label. Course/Group.
		 * @param Product $product         Product model.
		 * @param bool    $force_lowercase Whether to return the label in lowercase.
		 *
		 * @return string Product type label.
		 */
		return apply_filters( 'learndash_model_product_type_label', $label, $this, $force_lowercase );
	}

	/**
	 * Returns a pricing DTO.
	 *
	 * @since 4.5.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or object. If null or empty, the current user is used.
	 *
	 * @return Learndash_Pricing_DTO
	 */
	public function get_pricing( $user = null ): Learndash_Pricing_DTO {
		$user             = $this->map_user( $user );
		$pricing_settings = $this->get_pricing_settings( $user );

		$pricing = array(
			'currency'              => learndash_get_currency_code(),
			'price'                 => isset( $pricing_settings['price'] )
				? learndash_get_price_as_float( strval( $pricing_settings['price'] ) )
				: 0,
			'recurring_times'       => $pricing_settings['repeats'] ?? 0,
			'duration_value'        => $pricing_settings['interval'] ?? 0,
			'duration_length'       => $pricing_settings['frequency_raw'] ?? '',
			'trial_price'           => isset( $pricing_settings['trial_price'] )
				? learndash_get_price_as_float( strval( $pricing_settings['trial_price'] ) )
				: 0,
			'trial_duration_value'  => $pricing_settings['trial_interval'] ?? 0,
			'trial_duration_length' => $pricing_settings['trial_frequency_raw'] ?? '',
		);

		try {
			$pricing_dto = new Learndash_Pricing_DTO( $pricing );
		} catch ( Exception $e ) {
			$pricing_dto = new Learndash_Pricing_DTO();
		}

		/**
		 * Filters product pricing.
		 *
		 * @since 4.5.0
		 * @since 4.8.0 Added the $user parameter.
		 *
		 * @param Learndash_Pricing_DTO $pricing_dto Product Pricing DTO.
		 * @param Product               $product     Product model.
		 * @param WP_User|int           $user        The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return Learndash_Pricing_DTO Product pricing DTO.
		 */
		return apply_filters(
			'learndash_model_product_pricing',
			$pricing_dto,
			$this,
			$user
		);
	}

	/**
	 * Returns true if a user has access to this product, false otherwise.
	 *
	 * @since 4.5.0
	 * @since 4.7.0 $user parameter is optional.
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function user_has_access( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$has_access = false;

		if (
			$this->has_started()
			&& ! $this->has_ended( $user )
		) {
			if ( learndash_is_course_post( $this->post ) ) {
				$has_access = sfwd_lms_has_access( $this->post->ID, $user_id );
			} elseif ( learndash_is_group_post( $this->post ) ) {
				$has_access = learndash_is_user_in_group( $user_id, $this->post->ID );
			}
		}

		/**
		 * Filters whether a user has access to a product.
		 *
		 * @since 4.5.0
		 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
		 *
		 * @param bool        $has_access True if a user has access, false otherwise.
		 * @param Product     $product    Product model.
		 * @param WP_User|int $user       The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if a user has access, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_user_has_access', $has_access, $this, $user );
	}

	/**
	 * Returns true if a user has pre-ordered this product, false otherwise.
	 *
	 * @since 4.7.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_pre_ordered( $user = null ): bool {
		$user        = $this->map_user( $user );
		$user_id     = $user instanceof WP_User ? $user->ID : $user;
		$access_from = null;
		$pre_ordered = false;

		if ( $user_id > 0 ) {
			if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
				$associated_group = $this->get_first_course_group_for_user( $user );

				if ( $associated_group ) {
					$pre_ordered = $associated_group->is_pre_ordered( $user );
				} else {
					$access_from = ld_course_access_from( $this->post->ID, $user_id );
				}
			} elseif ( $this->is_post_type_by_key( LDLMS_Post_Types::GROUP ) ) {
				$access_from = learndash_group_access_from( $this->post->ID, $user_id );
			} else {
				$access_from = 0;
			}

			if ( ! is_null( $access_from ) ) {
				$pre_ordered = $access_from > 0 && time() < $access_from;
			}
		}

		/**
		 * Filters whether a user has pre-ordered a product.
		 *
		 * @since 4.7.0
		 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
		 *
		 * @param bool        $pre_ordered True if a user has pre-ordered, false otherwise.
		 * @param Product     $product     Product model.
		 * @param WP_User|int $user        The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if a user has pre-ordered, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_pre_ordered', $pre_ordered, $this, $user );
	}

	/**
	 * Adds access for a user.
	 *
	 * @since 4.5.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int $user The user ID or object.
	 *
	 * @return bool Returns true if it successfully enrolled a user, false otherwise.
	 */
	public function enroll( $user ): bool {
		$user = $this->map_user( $user, true );

		if ( empty( $user ) ) {
			return false; // unexpected parameter: no user ID.
		}

		$user_id  = $user instanceof WP_User ? $user->ID : $user;
		$enrolled = false;

		if ( learndash_is_course_post( $this->post ) ) {
			$enrolled = ld_update_course_access( $user_id, $this->post->ID );
		} elseif ( learndash_is_group_post( $this->post ) ) {
			$enrolled = ld_update_group_access( $user_id, $this->post->ID );
		}

		/**
		 * Filters whether a user was enrolled to a product.
		 *
		 * @since 4.5.0
		 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
		 *
		 * @param bool        $enrolled True if a user was enrolled, false otherwise.
		 * @param Product     $product  Product model.
		 * @param WP_User|int $user     The WP_User or the user ID, according to the filter's caller.
		 *
		 * @return bool True if a user was enrolled, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_user_enrolled', $enrolled, $this, $user );
	}

	/**
	 * Removes access for a user.
	 *
	 * @since 4.5.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int $user The user ID or object.
	 *
	 * @return bool Returns true if it successfully unenrolled a user, false otherwise.
	 */
	public function unenroll( $user ): bool {
		$user = $this->map_user( $user, true );

		if ( empty( $user ) ) {
			return false; // unexpected parameter: no user ID.
		}

		$user_id    = $user instanceof WP_User ? $user->ID : $user;
		$unenrolled = false;

		if ( learndash_is_course_post( $this->post ) ) {
			$unenrolled = ld_update_course_access( $user_id, $this->post->ID, true );
		} elseif ( learndash_is_group_post( $this->post ) ) {
			$unenrolled = ld_update_group_access( $user_id, $this->post->ID, true );
		}

		/**
		 * Filters whether a user was unenrolled from a product.
		 *
		 * @since 4.5.0
		 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
		 *
		 * @param bool        $unenrolled True if a user was unenrolled, false otherwise.
		 * @param Product     $product    Product model.
		 * @param WP_User|int $user       The WP_User or the user ID, according to the filter's caller.
		 *
		 * @return bool True if a user was unenrolled, false otherwise.
		 */
		return apply_filters( 'learndash_model_product_user_unenrolled', $unenrolled, $this, $user );
	}

	/**
	 * Returns the Course enrollment date for a user.
	 *
	 * @since 4.8.0
	 *
	 * @param WP_User|int $user The user ID or object.
	 *
	 * @return ?int The enrollment date timestamp or null if we can't find it.
	 */
	public function get_enrollment_date( $user ): ?int {
		$user = $this->map_user( $user, true );

		if ( empty( $user ) ) {
			return null; // unexpected parameter: no user ID.
		}

		$user_id         = $user instanceof WP_User ? $user->ID : $user;
		$enrollment_date = null;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$enrollment_date = get_user_meta( $user_id, 'learndash_course_' . $this->get_id() . '_enrolled_at', true );

			// Try to get the enrollment date from the course activity.

			if ( empty( $enrollment_date ) ) {
				$course_activity = learndash_get_user_activity(
					[
						'activity_type' => 'course',
						'user_id'       => $user_id,
						'post_id'       => $this->get_id(),
						'course_id'     => $this->get_id(),
					]
				);

				$enrollment_date = $course_activity->activity_started ?? null;
			}
		}

		// Normalize the enrollment date.
		$enrollment_date = Cast::to_int( $enrollment_date );
		$enrollment_date = ! empty( $enrollment_date ) ? $enrollment_date : null;

		/**
		 * Filters the enrollment date for a user.
		 *
		 * @since 4.8.0
		 *
		 * @param ?int        $enrollment_date The enrollment date.
		 * @param Product     $product         Product model.
		 * @param WP_User|int $user            The WP_User or the user ID, according to the filter's caller.
		 *
		 * @return ?int The enrollment date timestamp or null if we can't find it.
		 */
		return apply_filters( 'learndash_model_product_user_enrollment_date', $enrollment_date, $this, $user_id );
	}

	/**
	 * Sets the Course enrollment date for a user.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_User|int $user  The user ID or object.
	 * @param int         $timestamp The enrollment date timestamp.
	 *
	 * @return bool True if the enrollment date was set successfully, false otherwise.
	 */
	public function set_enrollment_date( $user, int $timestamp ): bool {
		$user = $this->map_user( $user, true );

		if ( empty( $user ) ) {
			return false; // unexpected parameter: no user ID.
		}

		$user_id = $user instanceof WP_User ? $user->ID : $user;

		if ( $this->is_post_type_by_key( LDLMS_Post_Types::COURSE ) ) {
			$meta_key = 'learndash_course_' . $this->get_id() . '_enrolled_at';
			return false !== update_user_meta( $user_id, $meta_key, $timestamp );
		}

		return false;
	}

	/**
	 * Returns whether the product content should be visible.
	 *
	 * @since 4.6.0
	 * @since 4.7.0 $user parameter is optional.
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_content_visible( $user = null ): bool {
		$user = $this->map_user( $user );

		$is_content_visible = true;
		$setting_value      = '';

		if ( learndash_is_course_post( $this->post ) ) {
			$setting_value = $this->get_setting( 'course_disable_content_table' );
		} elseif ( learndash_is_group_post( $this->post ) ) {
			$setting_value = $this->get_setting( 'group_disable_content_table' );
		}

		// Only visible to enrolled users.
		if ( 'on' === $setting_value ) {
			$is_content_visible = $this->user_has_access( $user );
		}

		/**
		 * Filters whether a product content should be visible.
		 *
		 * @since 4.6.0
		 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
		 *
		 * @param bool        $is_content_visible True if the content should be visible, false otherwise.
		 * @param Product     $product            Product model.
		 * @param WP_User|int $user               The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if the content should be visible, false otherwise.
		 *
		 * @ignore
		 */
		return apply_filters( 'learndash_model_product_is_content_visible', $is_content_visible, $this, $user );
	}

	/**
	 * Returns the materials content.
	 *
	 * @since 4.6.0
	 * @deprecated 4.21.0 Use the `Course::get_materials` or `Group::get_materials` methods instead.
	 *
	 * @return string
	 */
	public function get_materials(): string {
		_deprecated_function( __METHOD__, '4.21.0', 'Course::get_materials or Group::get_materials' );

		return '';
	}

	/**
	 * Returns formatted post pricing data.
	 *
	 * @since 4.5.0
	 * @since 4.8.0 Changed the $user parameter to accept an int or a WP_User object.
	 * @since 4.21.0 Changed visibility from private to public.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User.
	 *
	 * @return array{
	 *     type?: string,
	 *     price?: float|string,
	 *     interval?: int,
	 *     frequency?: string,
	 *     frequency_raw?: string,
	 *     repeats?: int,
	 *     repeat_frequency?: string,
	 *     trial_price?: float,
	 *     trial_interval?: int,
	 *     trial_frequency?: string,
	 *     trial_frequency_raw?: string
	 * }
	 */
	public function get_pricing_settings( $user = null ): array {
		$pricing_settings = array();

		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		if ( learndash_is_course_post( $this->post ) ) {
			$pricing_settings = learndash_get_course_price( $this->post, $user_id );
		} elseif ( learndash_is_group_post( $this->post ) ) {
			$pricing_settings = learndash_get_group_price( $this->post, $user_id );
		}

		return $pricing_settings;
	}

	/**
	 * Returns the first group product for the course respecting the user group enrollment status.
	 *
	 * @since 4.8.0
	 *
	 * @param WP_User|int $user The user ID or WP_User.
	 *
	 * @return Product|null
	 */
	private function get_first_course_group_for_user( $user ): ?Product {
		$user    = $this->map_user( $user, true );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		if ( empty( $user_id ) ) {
			return null;
		}

		$course_group_ids = array_intersect(
			learndash_get_course_groups( $this->get_id() ),
			learndash_get_users_group_ids( $user_id )
		);

		if ( empty( $course_group_ids ) ) {
			return null;
		}

		$course_group_ids = array_values( $course_group_ids );

		return self::find( $course_group_ids[0] );
	}
}
