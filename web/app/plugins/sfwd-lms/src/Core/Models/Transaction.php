<?php
/**
 * This class provides the easy way to operate a transaction.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash_Custom_Label;
use Learndash_DTO_Validation_Exception;
use Learndash_Payment_Gateway;
use Learndash_Paypal_IPN_Gateway;
use Learndash_Pricing_DTO;
use Learndash_Razorpay_Gateway;
use Learndash_Transaction_Coupon_DTO;
use Learndash_Transaction_Gateway_Transaction_DTO;
use Learndash_Unknown_Gateway;
use WP_User;

/**
 * Transaction model class.
 *
 * @since 4.6.0
 */
class Transaction extends Post {
	/**
	 * Product ID meta keys.
	 *
	 * @since 4.5.0
	 *
	 * @var string[]
	 */
	public static $product_id_meta_keys = array(
		'post_id', // Current.
		'course_id', // Legacy.
		'group_id', // Legacy.
	);

	/**
	 * Meta key to identify the parent transaction.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_is_parent = 'is_parent';

	/**
	 * Meta key to identify if the transaction is free (100% coupon for example).
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_is_free = 'is_zero_price';

	/**
	 * Meta key to for the pricing info.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_gateway_name = 'ld_payment_processor';

	/**
	 * Meta key to for the pricing info.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_pricing_info = 'pricing_info';

	/**
	 * Meta key to for the gateway transaction info (contains ID and an event).
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_gateway_transaction = 'gateway_transaction';

	/**
	 * Meta key to for the price type (payment/subscription).
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_price_type = 'price_type';

	/**
	 * Meta key to identify if the transaction has a trial.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_has_trial = 'has_trial';

	/**
	 * Meta key to identify if the transaction has a free trial.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public static $meta_key_has_free_trial = 'has_free_trial';

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.5.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ),
		);
	}

	/**
	 * Returns an attached user.
	 * If a user is not attached to the transaction, returns a WP_User object with the display name "Deleted".
	 *
	 * @since 4.5.0
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		if ( $this->post->post_author > 0 ) { // Actual.
			$user_id = (int) $this->post->post_author;
		} elseif ( intval( $this->getAttribute( 'user_id', 0 ) ) > 0 ) { // Legacy.
			$user_id = intval( $this->getAttribute( 'user_id' ) );
		} else {
			$user_id = 0;
		}

		if ( $user_id > 0 ) {
			$user = get_user_by( 'ID', $user_id );

			if ( $user instanceof WP_User ) {
				/**
				 * Filters a transaction user.
				 *
				 * @since 4.5.0
				 *
				 * @param WP_User     $user        User.
				 * @param Transaction $transaction Transaction model.
				 *
				 * @return WP_User User.
				 */
				return apply_filters( 'learndash_model_transaction_user', $user, $this );
			}
		}

		// Legacy PayPal.
		if ( ! empty( $this->getAttribute( 'payer_email' ) ) ) {
			$user = get_user_by(
				'email',
				strval( $this->getAttribute( 'payer_email' ) )
			);

			if ( $user instanceof WP_User ) {
				/** This filter is documented in includes/models/class-learndash-transaction.php */
				return apply_filters( 'learndash_model_transaction_user', $user, $this );
			}
		}

		$user = new WP_User();

		if ( ! empty( $this->getAttribute( 'user' ) ) ) {
			/**
			 * User data.
			 *
			 * @var array{
			 *     display_name: string,
			 *     user_email: string
			 * } $user_info You can find the current shape in learndash_transaction_create().
			 */
			$user_info = $this->getAttribute( 'user' );

			$user->display_name = $user_info['display_name'];
			$user->user_email   = $user_info['user_email'];
		} else {
			$user->display_name = __( 'Deleted', 'learndash' );
		}

		/** This filter is documented in includes/models/class-learndash-transaction.php */
		return apply_filters( 'learndash_model_transaction_user', $user, $this );
	}

	/**
	 * Returns a transaction gateway name.
	 *
	 * @since 4.5.0
	 *
	 * @return string Transaction gateway name.
	 */
	public function get_gateway_name(): string {
		$gateway_name = '';

		if ( $this->is_parent() ) {
			$children = $this->get_children();

			if ( ! empty( $children ) ) {
				return $children[0]->get_gateway_name();
			}
		} else {
			if (
				$this->hasAttribute( self::$meta_key_gateway_name ) &&
				in_array(
					$this->getAttribute( self::$meta_key_gateway_name ),
					array_keys( Learndash_Payment_Gateway::get_select_list() ),
					true
				)
			) {
				$gateway_name = $this->getAttribute( self::$meta_key_gateway_name ); // If the gateway name is up-to-date, use it.
			} elseif (
				'paypal' === $this->getAttribute( self::$meta_key_gateway_name ) || // If it's an old PayPal transaction. Legacy support.
				$this->hasAttribute( 'ipn_track_id' ) // If it's PayPal according to the IPN track ID field. Legacy support.
			) {
				$gateway_name = Learndash_Paypal_IPN_Gateway::get_name();
			} elseif ( $this->hasAttribute( 'stripe_price' ) ) { // If it's Stripe according to the price field. Legacy support.
				$gateway_name = 'stripe';
			}
		}

		/**
		 * Filters a transaction gateway name.
		 *
		 * @since 4.5.0
		 *
		 * @param string      $gateway_name Transaction gateway name.
		 * @param Transaction $transaction  Transaction model.
		 *
		 * @return string Transaction gateway name.
		 */
		return apply_filters( 'learndash_model_transaction_gateway_name', $gateway_name, $this );
	}

	/**
	 * Returns a transaction gateway label.
	 *
	 * @since 4.5.0
	 *
	 * @return string Payment gateway label.
	 */
	public function get_gateway_label(): string {
		$gateway_label = '';

		if ( $this->is_parent() ) {
			$children = $this->get_children();

			if ( ! empty( $children ) ) {
				return $children[0]->get_gateway_label();
			}
		} else {
			if ( $this->is_free() ) {
				$gateway_label = esc_html__( 'No Gateway', 'learndash' );
			} else {
				$gateway_name  = $this->get_gateway_name();
				$gateway_label = Learndash_Payment_Gateway::get_select_list()[ $gateway_name ] ?? '';

				if ( empty( $gateway_label ) ) {
					$gateway_label = ! empty( $gateway_name )
						? ucfirst( $gateway_name )
						: Learndash_Unknown_Gateway::get_label();
				}
			}
		}

		/**
		 * Filters a transaction gateway label.
		 *
		 * @since 4.5.0
		 *
		 * @param string      $gateway_label Transaction gateway label.
		 * @param Transaction $transaction   Transaction model.
		 *
		 * @return string Transaction gateway label.
		 */
		return apply_filters( 'learndash_model_transaction_gateway_label', $gateway_label, $this );
	}

	/**
	 * Returns a transaction gateway instance.
	 * If the gateway is not found (not active, not configured), returns an instance of the `Learndash_Unknown_Gateway` class.
	 *
	 * @since 4.5.0
	 *
	 * @return Learndash_Payment_Gateway Payment gateway instance.
	 */
	public function get_gateway(): Learndash_Payment_Gateway {
		$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name(
			$this->get_gateway_name()
		);

		/**
		 * Filters a transaction gateway instance.
		 * If the gateway is not found (not active, not configured), returns an instance of the `Learndash_Unknown_Gateway` class.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Payment_Gateway $gateway     Transaction gateway instance.
		 * @param Transaction               $transaction Transaction model.
		 *
		 * @return Learndash_Payment_Gateway Transaction gateway instance.
		 */
		return apply_filters( 'learndash_model_transaction_gateway', $gateway, $this );
	}

	/**
	 * Returns true if it's a subscription, false otherwise.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_subscription(): bool {
		$is_subscription = false;

		if ( $this->hasAttribute( self::$meta_key_price_type ) ) {
			$is_subscription = LEARNDASH_PRICE_TYPE_SUBSCRIBE === $this->getAttribute( self::$meta_key_price_type );
		} elseif ( $this->hasAttribute( 'stripe_price_type' ) ) { // Legacy Stripe.
			$is_subscription = LEARNDASH_PRICE_TYPE_SUBSCRIBE === $this->getAttribute( 'stripe_price_type' );
		} elseif ( // Legacy PayPal.
			$this->hasAttribute( 'subscr_id' ) &&
			'subscr_signup' === $this->getAttribute( 'txn_type' )
		) {
			$is_subscription = true;
		}

		/**
		 * Filters whether a transaction is a subscription.
		 *
		 * @since 4.5.0
		 *
		 * @param bool        $is_subscription True if it's a subscription, false otherwise.
		 * @param Transaction $transaction     Transaction model.
		 *
		 * @return bool True if it's a subscription, false otherwise.
		 */
		return apply_filters( 'learndash_model_transaction_is_subscription', $is_subscription, $this );
	}

	/**
	 * Returns true if it's a free transaction, false otherwise.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		/**
		 * Filters whether it's a free transaction made via a coupon.
		 *
		 * @since 4.5.0
		 *
		 * @param bool        $is_free     True if it's a free transaction.
		 * @param Transaction $transaction Transaction model.
		 *
		 * @return bool True if it's a free transaction made via a coupon.
		 */
		return apply_filters(
			'learndash_model_transaction_is_free',
			(bool) $this->getAttribute( self::$meta_key_is_free, false ),
			$this
		);
	}

	/**
	 * Returns true if it's a parent transaction, false otherwise.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_parent(): bool {
		/**
		 * Filters whether a transaction is a parent.
		 *
		 * @since 4.5.0
		 *
		 * @param bool        $is_parent   True if it's a parent transaction, false otherwise.
		 * @param Transaction $transaction Transaction model.
		 *
		 * @return bool True if it's a parent transaction, false otherwise.
		 */
		return apply_filters(
			'learndash_model_transaction_is_parent',
			(bool) ( $this->getAttribute( self::$meta_key_is_parent, false ) ),
			$this
		);
	}

	/**
	 * Returns true if it's a transaction has a trial.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function has_trial(): bool {
		/**
		 * Filters whether a transaction has a trial.
		 *
		 * @since 4.5.0
		 *
		 * @param bool        $has_trial   True if it has a trial, false otherwise.
		 * @param Transaction $transaction Transaction model.
		 *
		 * @return bool True if it has a trial, false otherwise.
		 */
		return apply_filters(
			'learndash_model_transaction_has_trial',
			(bool) $this->getAttribute( self::$meta_key_has_trial, false ),
			$this
		);
	}

	/**
	 * Returns a subscription/payment ID, empty string otherwise.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_gateway_transaction_id(): string {
		$transaction_id = '';

		$gateway_name = $this->get_gateway_name();

		if ( $this->hasAttribute( self::$meta_key_gateway_transaction ) ) {
			try {
				$gateway_transaction_dto = Learndash_Transaction_Gateway_Transaction_DTO::create(
					(array) $this->getAttribute( self::$meta_key_gateway_transaction )
				);

				$transaction_id = $gateway_transaction_dto->id;
			} catch ( Learndash_DTO_Validation_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Do nothing.
			}
		} elseif ( 'stripe' === $gateway_name ) {
			if ( $this->hasAttribute( 'subscription' ) ) { // Stripe Legacy subscription ID field.
				$transaction_id = $this->getAttribute( 'subscription' );
			} elseif ( $this->hasAttribute( 'stripe_payment_intent' ) ) { // Stripe Legacy payment intent ID field.
				$transaction_id = $this->getAttribute( 'stripe_payment_intent' );
			}
		} elseif (
			Learndash_Razorpay_Gateway::get_name() === $gateway_name &&
			$this->hasAttribute( 'razorpay_event' ) && // Razorpay Legacy field.
			is_array( $this->getAttribute( 'razorpay_event' ) ) &&
			isset( $this->getAttribute( 'razorpay_event' )['payload'] )
		) {
			$razorpay_payload = $this->getAttribute( 'razorpay_event' )['payload'];

			if ( isset( $razorpay_payload['payment'] ) ) {
				$transaction_id = $razorpay_payload['payment']['entity']['id'];
			} elseif ( isset( $razorpay_payload['subscription'] ) ) {
				$transaction_id = $razorpay_payload['subscription']['entity']['id'];
			}
		}

		/**
		 * Filters LD transaction gateway transaction ID.
		 *
		 * @since 4.5.0
		 *
		 * @param string      $transaction_id Gateway transaction ID or an empty string.
		 * @param Transaction $transaction    Transaction model.
		 *
		 * @return string Gateway transaction ID.
		 */
		return apply_filters( 'learndash_model_transaction_gateway_transaction_id', (string) $transaction_id, $this );
	}

	/**
	 * Returns an attached product model or null if not found.
	 *
	 * @since 4.5.0
	 *
	 * @return Product|null
	 */
	public function get_product(): ?Product {
		$product_id = 0;
		foreach ( self::$product_id_meta_keys as $product_id_meta_key ) {
			$product_id = intval( $this->getAttribute( $product_id_meta_key, 0 ) );

			if ( $product_id > 0 ) {
				break;
			}
		}

		/**
		 * Filters a transaction product.
		 *
		 * @since 4.5.0
		 *
		 * @param Product|null $product     Product model or null if not found.
		 * @param Transaction  $transaction Transaction model.
		 *
		 * @return Product|null Transaction product model or null.
		 */
		return apply_filters(
			'learndash_model_transaction_product',
			Product::find( $product_id ),
			$this
		);
	}

	/**
	 * Returns a product name or "Not found".
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_product_name(): string {
		$product = $this->get_product();

		if ( $product ) {
			$product_title = $product->get_post()->post_title;
		} elseif (
			$this->hasAttribute( 'post' ) &&
			is_array( $this->getAttribute( 'post' ) ) &&
			isset( $this->getAttribute( 'post' )['post_title'] )
		) {
			$product_title = $this->getAttribute( 'post' )['post_title'];
		} elseif ( $this->hasAttribute( 'stripe_name' ) ) { // Legacy Stripe.
			$product_title = $this->getAttribute( 'stripe_name' );
		} elseif ( $this->hasAttribute( 'item_name' ) ) { // Legacy PayPal.
			$product_title = $this->getAttribute( 'item_name' );
		} else {
			$product_title = __( 'Not found', 'learndash' );
		}

		/**
		 * Filters a transaction product title.
		 *
		 * @since 4.5.0
		 *
		 * @param string      $product_title Product name.
		 * @param Transaction $transaction   Transaction model.
		 *
		 * @return string Transaction product name.
		 */
		return apply_filters( 'learndash_model_transaction_product_name', strval( $product_title ), $this );
	}

	/**
	 * Returns a product type label. Usually "Course" or "Group".
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_product_type_label(): string {
		$product = $this->get_product();

		if ( $product ) {
			$label = $product->get_type_label();
		} elseif (
			$this->hasAttribute( 'post' ) &&
			is_array( $this->getAttribute( 'post' ) ) &&
			isset( $this->getAttribute( 'post' )['post_type'] )
		) {
			$label = LearnDash_Custom_Label::get_label(
				LDLMS_Post_Types::get_post_type_key(
					strval( $this->getAttribute( 'post' )['post_type'] )
				)
			);
		} else {
			$label = '';
		}

		/**
		 * Filters transaction product type label.
		 *
		 * @since 4.5.0
		 *
		 * @param string      $type_label  Product type label. Course/Group.
		 * @param Transaction $transaction Transaction model.
		 *
		 * @return string Product type label.
		 */
		return apply_filters( 'learndash_model_transaction_product_type_label', $label, $this );
	}

	/**
	 * Returns a pricing DTO.
	 *
	 * @since 4.5.0
	 *
	 * @throws Learndash_DTO_Validation_Exception DTO validation exception.
	 *
	 * @return Learndash_Pricing_DTO
	 */
	public function get_pricing(): Learndash_Pricing_DTO {
		$pricing = array();

		if ( $this->hasAttribute( self::$meta_key_pricing_info ) ) {
			$pricing = (array) $this->getAttribute( self::$meta_key_pricing_info );
		} elseif (
			is_array( $this->getAttribute( LEARNDASH_TRANSACTION_COUPON_META_KEY ) ) &&
			isset( $this->getAttribute( LEARNDASH_TRANSACTION_COUPON_META_KEY )['price'] )
		) { // Legacy coupon.
			/**
			 * Legacy coupon meta.
			 *
			 * @var array{
			 *     currency?: string,
			 *     price?: float,
			 *     discount?: float,
			 *     discounted_price?: float,
			 * } $coupon_meta
			 */
			$coupon_meta = $this->getAttribute( LEARNDASH_TRANSACTION_COUPON_META_KEY );

			$pricing['currency']         = $coupon_meta['currency'] ?? '';
			$pricing['price']            = $coupon_meta['price'] ?? 0;
			$pricing['discount']         = $coupon_meta['discount'] ?? 0;
			$pricing['discounted_price'] = $coupon_meta['discounted_price'] ?? 0;
		} else {
			$gateway_name = $this->get_gateway_name();

			if ( 'stripe' === $gateway_name ) { // Legacy Stripe.
				$pricing['currency'] = mb_strtoupper(
					strval( $this->getAttribute( 'stripe_currency', '' ) )
				);
				$pricing['price']    = $this->getAttribute( 'stripe_price' );

				if ( $this->is_subscription() ) {
					$duration_hash = array(
						'day'   => 'D',
						'week'  => 'W',
						'month' => 'M',
						'year'  => 'Y',
					);

					$pricing['price']                 = $this->getAttribute( 'subscribe_price', 0 );
					$pricing['recurring_times']       = $this->getAttribute( 'no_of_cycles', 0 );
					$pricing['duration_value']        = $this->getAttribute( 'pricing_billing_p3', 0 );
					$pricing['duration_length']       = $duration_hash[ $this->getAttribute( 'pricing_billing_t3', '' ) ] ?? '';
					$pricing['trial_price']           = $this->getAttribute( 'trial_price', 0 );
					$pricing['trial_duration_value']  = $this->getAttribute( 'trial_duration_p1', 0 );
					$pricing['trial_duration_length'] = $duration_hash[ $this->getAttribute( 'trial_duration_t1', '' ) ] ?? '';
				}
			} elseif ( Learndash_Paypal_IPN_Gateway::get_name() === $gateway_name ) { // Legacy PayPal.
				$pricing['currency'] = $this->getAttribute( 'mc_currency' );
				$pricing['price']    = $this->getAttribute( 'mc_gross', 0 );

				if ( $this->is_subscription() ) {
					$duration       = explode(
						' ',
						strval( $this->getAttribute( 'period3', '' ) )
					);
					$trial_duration = explode(
						' ',
						strval( $this->getAttribute( 'period1', '' ) )
					);

					if ( empty( $pricing['price'] ) ) {
						$pricing['price'] = $this->getAttribute( 'mc_amount3', 0 );
					}

					$pricing['recurring_times']       = $this->getAttribute( 'recur_times', 0 );
					$pricing['duration_value']        = $duration[0] ?? 0;
					$pricing['duration_length']       = $duration[1] ?? '';
					$pricing['trial_price']           = $this->getAttribute( 'amount1' );
					$pricing['trial_duration_value']  = $trial_duration[0] ?? 0;
					$pricing['trial_duration_length'] = $trial_duration[1] ?? '';
				}
			} elseif ( Learndash_Razorpay_Gateway::get_name() === $gateway_name ) { // Legacy Razorpay.
				$pricing_legacy = $this->getAttribute( 'pricing', array() );

				if ( ! empty( $pricing_legacy ) ) {
					/**
					 * Legacy pricing.
					 *
					 * @var array{
					 *     currency: string,
					 *     price: float,
					 *     no_of_cycles?: int,
					 *     pricing_billing_p3?: int,
					 *     pricing_billing_t3?: string,
					 *     trial_price?: float,
					 *     trial_duration_p1?: int,
					 *     trial_duration_t1?: string,
					 * } $pricing_legacy
					 */
					$pricing['currency'] = $pricing_legacy['currency'];
					$pricing['price']    = $pricing_legacy['price'];

					if ( $this->is_subscription() ) {
						$pricing['recurring_times']       = $pricing_legacy['no_of_cycles'] ?? 0;
						$pricing['duration_value']        = $pricing_legacy['pricing_billing_p3'] ?? 0;
						$pricing['duration_length']       = $pricing_legacy['pricing_billing_t3'] ?? '';
						$pricing['trial_price']           = $pricing_legacy['trial_price'] ?? 0;
						$pricing['trial_duration_value']  = $pricing_legacy['trial_duration_p1'] ?? 0;
						$pricing['trial_duration_length'] = $pricing_legacy['trial_duration_t1'] ?? '';
					}
				}
			}
		}

		/**
		 * Filters transaction product pricing.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Pricing_DTO $pricing_dto Transaction pricing DTO.
		 * @param Transaction           $transaction Transaction model.
		 *
		 * @return Learndash_Pricing_DTO Transaction pricing DTO.
		 */
		return apply_filters(
			'learndash_model_transaction_pricing',
			Learndash_Pricing_DTO::create( $pricing ),
			$this
		);
	}

	/**
	 * Gets transaction coupon data.
	 *
	 * @since 4.5.0
	 *
	 * @throws Learndash_DTO_Validation_Exception Coupon DTO validation exception.
	 *
	 * @return Learndash_Transaction_Coupon_DTO Transaction coupon data.
	 */
	public function get_coupon_data(): Learndash_Transaction_Coupon_DTO {
		/**
		 * Filters transaction coupon data.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Transaction_Coupon_DTO $coupon_data Transaction coupon data.
		 * @param Transaction                      $transaction Transaction model.
		 *
		 * @return array Transaction coupon data.
		 */
		return apply_filters(
			'learndash_model_transaction_coupon_data',
			Learndash_Transaction_Coupon_DTO::create(
				(array) $this->getAttribute( LEARNDASH_TRANSACTION_COUPON_META_KEY, array() )
			),
			$this
		);
	}

	/**
	 * Checks if a transaction has an attached coupon.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function has_coupon(): bool {
		/**
		 * Filters whether a transaction has an attached coupon.
		 *
		 * @since 4.5.0
		 *
		 * @param bool        $has_coupon  Flag whether a transaction has an attached coupon.
		 * @param Transaction $transaction Transaction model.
		 *
		 * @return bool Flag whether a transaction has an attached coupon.
		 */
		return apply_filters(
			'learndash_model_transaction_has_coupon',
			is_array( $this->getAttribute( LEARNDASH_TRANSACTION_COUPON_META_KEY ) ),
			$this
		);
	}
}
