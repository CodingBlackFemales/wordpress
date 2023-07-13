<?php
/**
 * Deprecated. Use LearnDash_Stripe_Gateway instead.
 * This class handled Stripe Connect integration.
 *
 * @since 4.0.0
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
	esc_html( LEARNDASH_LMS_PLUGIN_DIR . '/includes/payments/gateways/class-learndash-stripe-gateway.php' )
);

if ( ! class_exists( 'LearnDash_Stripe_Connect_Checkout_Integration' ) && class_exists( 'LearnDash_Payment_Gateway_Integration' ) ) {
	/**
	 * Stripe Connect checkout integration class.
	 *
	 * @since 4.0.0
	 * @deprecated 4.5.0
	 */
	class LearnDash_Stripe_Connect_Checkout_Integration extends LearnDash_Payment_Gateway_Integration {
		/**
		 * Deprecated.
		 *
		 * @deprecated 4.5.0
		 */
		const PAYMENT_PROCESSOR = 'stripe';

		/**
		 * Plugin options.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var array
		 */
		protected $options = array();

		/**
		 * Stripe secret key.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $secret_key = '';

		/**
		 * Stripe publishable key.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $publishable_key = '';

		/**
		 * Stripe connected account id.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $account_id = '';

		/**
		 * Stripe API client.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var null
		 */
		protected $stripe = null;

		/**
		 * Variable to hold the Course object we are working with.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var null
		 */
		protected $course = null;

		/**
		 * Stripe checkout session id.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $session_id = '';

		/**
		 * Stripe customer id meta key name.
		 *
		 * @deprecated 4.5.0
		 *
		 * @var string
		 */
		protected $stripe_customer_id_meta_key = '';

		/**
		 * Construction.
		 *
		 * @deprecated 4.5.0
		 */
		public function __construct() {
			_deprecated_constructor( __CLASS__, '4.5.0' );
		}

		/**
		 * Sets Stripe customer id meta key.
		 *
		 * @deprecated 4.5.0
		 */
		protected function set_stripe_customer_id_meta_key(): void {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Configs Stripe API key.
		 *
		 * @deprecated 4.5.0
		 */
		protected function configure() {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Checks if it's a test mode.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return bool
		 */
		public function is_test_mode(): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			return true;
		}

		/**
		 * Get course button args
		 *
		 * @deprecated 4.5.0
		 *
		 * @param int|null $course_id Course ID.
		 *
		 * @return array Course args
		 */
		public function get_course_args( ?int $course_id = null ): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array();
		}

		/**
		 * Enqueues scripts.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Stripe_Gateway::get_name() );
			$gateway->enqueue_scripts();
		}

		/**
		 * Output modified payment button
		 *
		 * @deprecated 4.5.0
		 *
		 * @param string     $default_button Learndash default payment button.
		 * @param array|null $params Button parameters.
		 *
		 * @return string Modified button.
		 */
		public function payment_button( string $default_button, ?array $params = null ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return $default_button;
		}

		/**
		 * Stripe payment button
		 *
		 * @deprecated 4.5.0
		 *
		 * @param string $default_button Default button.
		 *
		 * @return string Payment button.
		 */
		public function stripe_button( $default_button ) {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Integration button scripts
		 *
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function button_scripts() {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Process Stripe new checkout
		 *
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function process_webhook(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Stripe_Gateway::get_name() );
			$gateway->process_webhook();
		}

		/**
		 * Get LearnDash course ID by Stripe plan ID.
		 *
		 * @deprecated 4.5.0
		 *
		 * @param string $plan_id Stripe plan ID.
		 *
		 * @return string|null LearnDash course ID or null.
		 */
		public function get_course_id_by_plan_id( string $plan_id ): ?string {
			_deprecated_function( __METHOD__, '4.5.0' );

			global $wpdb;

			return $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'stripe_plan_id' AND meta_value = %s", $plan_id )
			);
		}

		/**
		 * Outputs transaction message.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function output_transaction_message(): void {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Get user ID of the customer
		 *
		 * @deprecated 4.5.0
		 *
		 * @param null|string $email User email address.
		 * @param string      $customer_id Stripe customer ID.
		 * @param null|int    $user_id WP user ID.
		 *
		 * @return int WP_User ID
		 */
		public function get_user_id( ?string $email, string $customer_id, ?int $user_id = null ): int {
			_deprecated_function( __METHOD__, '4.5.0' );

			$user = ! empty( $user_id ) && is_numeric( $user_id )
				? get_user_by( 'ID', $user_id )
				: ( ! empty( $email ) ? get_user_by( 'email', $email ) : null );

			if ( $user ) {
				return $user->ID;
			}

			return $this->create_user( $email, wp_generate_password( 18 ), $email );
		}

		/**
		 * Creates a user if does not exist.
		 *
		 * @deprecated 4.5.0
		 *
		 * @param string $email Email.
		 * @param string $password Password.
		 * @param string $username Username.
		 *
		 * @return int Newly created user ID
		 */
		public function create_user( string $email, string $password, string $username ): int {
			_deprecated_function( __METHOD__, '4.5.0' );

			if ( username_exists( $username ) ) {
				$random_chars = str_shuffle( substr( md5( (string) time() ), 0, 5 ) );
				$username     = $username . '-' . $random_chars;
			}

			return wp_create_user( $username, $password, $email );
		}

		/**
		 * Records a payment transaction.
		 *
		 * @deprecated 4.5.0
		 *
		 * @param object $session    Transaction data passed through $_POST.
		 * @param int    $post_id    Post ID.
		 * @param int    $user_id    User ID.
		 * @param string $user_email Email of the user.
		 */
		public function record_transaction( $session, int $post_id, int $user_id, string $user_email ) {
			_deprecated_function( __METHOD__, '4.5.0' );
		}

		/**
		 * Maps the secret key.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return string
		 */
		protected function map_secret_key(): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Maps the publishable key.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return string
		 */
		protected function map_publishable_key(): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return '';
		}

		/**
		 * Returns enabled payment methods.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return array
		 */
		protected function get_payment_methods(): array {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array();
		}

		/**
		 * Check if Stripe currency ISO code is zero decimal currency
		 *
		 * @deprecated 4.5.0
		 *
		 * @param string $currency Stripe currency ISO code.
		 *
		 * @return bool
		 */
		protected function is_zero_decimal_currency( string $currency = '' ): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			$zero_decimal_currencies = array(
				'BIF',
				'CLP',
				'DJF',
				'GNF',
				'JPY',
				'KMF',
				'KRW',
				'MGA',
				'PYG',
				'RWF',
				'VND',
				'VUV',
				'XAF',
				'XOF',
				'XPF',
			);

			return in_array( strtoupper( $currency ), $zero_decimal_currencies, true );
		}

		/**
		 * Generates random string.
		 *
		 * @deprecated 4.5.0
		 *
		 * @param integer $length Length.
		 *
		 * @return string
		 */
		protected function generate_random_string( $length = 5 ): string {
			_deprecated_function( __METHOD__, '4.5.0' );

			return substr( md5( microtime() ), 0, $length );
		}

		/**
		 * Checks if learndash Stripe Connect webhook is running.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return bool
		 */
		protected function check_webhook_process(): bool {
			_deprecated_function( __METHOD__, '4.5.0' );

			return false;
		}

		/**
		 * Sets Stripe checkout session on a course page.
		 *
		 * @deprecated 4.5.0
		 *
		 * @param int|null $course_id Course ID.
		 *
		 * @return array
		 */
		protected function set_session( ?int $course_id = null ) {
			_deprecated_function( __METHOD__, '4.5.0' );

			return array(
				'error' => __( 'Deprecated', 'learndash' ),
			);
		}

		/**
		 * AJAX function handler for init checkout.
		 *
		 * @deprecated 4.5.0
		 *
		 * @return void
		 */
		public function ajax_init_checkout(): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Stripe_Gateway::get_name() );
			$gateway->setup_payment();
		}
	}
}
