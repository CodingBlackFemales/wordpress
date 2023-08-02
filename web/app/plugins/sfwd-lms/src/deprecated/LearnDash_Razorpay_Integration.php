<?php
/**
 * Deprecated. Use LearnDash_Razorpay_Gateway instead.
 * This class handled Razorpay integration.
 *
 * @since 4.2.0
 * @deprecated 4.5.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'4.5.0',
	esc_html( LEARNDASH_LMS_PLUGIN_DIR . '/includes/payments/gateways/class-learndash-razorpay-gateway.php' )
);

if ( ! class_exists( 'LearnDash_Razorpay_Integration' ) && class_exists( 'LearnDash_Payment_Gateway_Integration' ) ) {
	/**
	 * Deprecated Razorpay's integration class.
	 *
	 * @since 4.2.0
	 * @deprecated 4.5.0
	 */
	class LearnDash_Razorpay_Integration extends LearnDash_Payment_Gateway_Integration {
		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const AJAX_ACTION_SETUP_OPTIONS = 'learndash_razorpay_setup_options';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const META_KEY_CUSTOMER_ID_LIVE = 'learndash_razorpay_live_customer_id';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const META_KEY_CUSTOMER_ID_TEST = 'learndash_razorpay_test_customer_id';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const META_KEY_PLANS_LIVE = 'learndash_razorpay_live_plans';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const META_KEY_PLANS_TEST = 'learndash_razorpay_test_plans';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const PERIOD_HASH = array(
			'D' => 'daily',
			'W' => 'weekly',
			'M' => 'monthly',
			'Y' => 'yearly',
		);

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const TRIAL_PERIOD_DURATION_HASH = array(
			'D' => DAY_IN_SECONDS,
			'W' => WEEK_IN_SECONDS,
			'M' => MONTH_IN_SECONDS,
			'Y' => YEAR_IN_SECONDS,
		);

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_ORDER_PAID = 'order.paid';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_AUTHENTICATED = 'subscription.authenticated';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_ACTIVATED = 'subscription.activated';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_COMPLETED = 'subscription.completed';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_PENDING = 'subscription.pending';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_HALTED = 'subscription.halted';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_CANCELLED = 'subscription.cancelled';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_PAUSED = 'subscription.paused';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const EVENT_SUBSCRIPTION_RESUMED = 'subscription.resumed';

		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const PAYMENT_PROCESSOR = 'razorpay';

		/**
		 * Settings.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var array
		 */
		protected $settings = array();

		/**
		 * Secret key.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $secret_key = '';

		/**
		 * Publishable key.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $publishable_key = '';

		/**
		 * Webhook secret.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $webhook_secret = '';

		/**
		 * API client.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var null
		 */
		protected $api = null;

		/**
		 * Course/Group we are working with.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var null
		 */
		protected $post = null;

		/**
		 * Current user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var null
		 */
		protected $user = null;

		/**
		 * Razorpay customer id meta key name.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $customer_id_meta_key = '';

		/**
		 * Razorpay plans meta key name.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $plans_meta_key = '';

		/**
		 * Current currency code.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $currency = '';

		/**
		 * Construction.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 */
		public function __construct() {
			_deprecated_constructor( __CLASS__, '4.5.0' );
		}

		/**
		 * Enqueues scripts.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Razorpay_Gateway::get_name() );
			$gateway->enqueue_scripts();
		}

		/**
		 * Creates an order/subscription in Razorpay.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function setup_options(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Razorpay_Gateway::get_name() );
			$gateway->setup_payment();
		}

		/**
		 * Checks if enabled and all keys are filled in.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return bool
		 */
		public function is_ready(): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			return false;
		}

		/**
		 * Configures settings.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		protected function configure(): void {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Checks if it's a test mode.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return bool
		 */
		protected function is_test_mode(): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			return true;
		}

		/**
		 * Returns Razorpay options.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @throws Exception Throws if not valid arguments passed.
		 *
		 * @return array
		 */
		protected function map_payment_options(): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array();
		}

		/**
		 * Returns modified payment button.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param string $default_button Learndash default payment button.
		 * @param array  $params         Parameters.
		 *
		 * @return string
		 */
		public function add_payment_button( string $default_button, array $params ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return $default_button;
		}

		/**
		 * Processes a webhook.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function process_webhook(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Razorpay_Gateway::get_name() );
			$gateway->process_webhook();
		}

		/**
		 * Finds or creates a user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event.
		 *
		 * @return WP_User|null
		 */
		public function find_or_create_user( array $event ): ?WP_User {
			_deprecated_function( __METHOD__, '4.5.0' );

			$entity  = $this->get_main_entity_from_event( $event );
			$user_id = (int) $entity['notes']['user_id'];
			$payment = $this->get_payment_entity_from_event( $event );

			if ( $user_id > 0 ) {
				$user = get_user_by( 'ID', $user_id );
			} elseif ( ! empty( $payment ) ) {
				$user = get_user_by( 'email', $event['payload']['payment']['entity']['email'] );
			} else {
				return null;
			}

			if ( ! is_a( $user, WP_User::class ) ) {
				if ( empty( $payment ) ) {
					return null;
				}

				$user = $this->create_user( $payment['email'] );
			}

			return $user;
		}

		/**
		 * Creates a user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param string $email Email.
		 *
		 * @return WP_User
		 */
		public function create_user( string $email ): WP_User {
			_deprecated_function( __METHOD__, '4.5.0' );

			$username = $email;
			$password = wp_generate_password( 18 );

			if ( username_exists( $username ) ) {
				$username = $username . '-' . uniqid();
			}

			$user_id = wp_create_user( $username, $password, $email );

			return get_user_by( 'ID', $user_id );
		}

		/**
		 * Returns subscription/order entity from the event.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event.
		 *
		 * @return array
		 */
		protected function get_main_entity_from_event( array $event ): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			$entity_key = $this->event_contains_subscription( $event ) ? 'subscription' : 'order';

			if ( ! isset( $event['payload'][ $entity_key ] ) ) {
				return array();
			}

			return $event['payload'][ $entity_key ]['entity'];
		}

		/**
		 * Returns payment from the event.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event.
		 *
		 * @return array
		 */
		protected function get_payment_entity_from_event( array $event ): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array();
		}

		/**
		 * Returns true if it's a subscription event.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event.
		 *
		 * @return bool
		 */
		protected function event_contains_subscription( array $event ): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			return in_array( 'subscription', $event['contains'], true );
		}

		/**
		 * Records a payment transaction.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event data.
		 */
		public function record_transaction( array $event ): void {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Maps payment button markup.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param string $default_button Default button.
		 *
		 * @return string
		 */
		protected function map_payment_button( string $default_button ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Returns nonce name.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @return string
		 */
		protected function get_nonce_name(): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Gets or creates a customer. Returns customer id.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @throws Exception If customer is not found or created.
		 */
		protected function find_or_create_customer_id(): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			throw new Exception(
				__( 'Deprecated', 'learndash' )
			);
		}

		/**
		 * Creates an order.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param int $amount Amount.
		 *
		 * @return string
		 */
		protected function create_order_id( int $amount ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Creates a subscription.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @throws Exception Exception.
		 *
		 * @param int $price Price.
		 *
		 * @return string
		 */
		protected function create_subscription_id( int $price ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Creates a plan or returns an existing plan id.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param int    $amount   Amount.
		 * @param int    $interval Interval.
		 * @param string $period   Period.
		 *
		 * @return string Plan ID.
		 */
		protected function find_or_create_plan_id( int $amount, int $interval, string $period ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Creates a plan.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array  $plan_options      Plan options.
		 * @param string $plan_options_hash Plan options hash.
		 * @param array  $existing_plans    Existing plans.
		 *
		 * @return string Plan ID.
		 */
		protected function create_plan( array $plan_options, string $plan_options_hash, array $existing_plans ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Maps transaction meta fields.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param array $event Event.
		 *
		 * @return array
		 */
		protected function map_transaction_meta( array $event ): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array();
		}
	}
}
