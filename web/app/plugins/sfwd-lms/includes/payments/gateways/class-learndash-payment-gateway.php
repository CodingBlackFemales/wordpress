<?php
/**
 * Base class for payment gateways.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Payment_Gateway' ) ) {
	/**
	 * Payment gateway class.
	 *
	 * @since 4.5.0
	 */
	abstract class Learndash_Payment_Gateway {
		/**
		 * WP error code for gateway errors.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		protected static $wp_error_code = 'learndash_payment_gateway_error';

		/**
		 * Settings.
		 *
		 * @since 4.5.0
		 *
		 * @var array<string,mixed>
		 */
		protected $settings = array();

		/**
		 * Learndash_Transaction_Logger instance or null.
		 *
		 * @since 4.5.0
		 *
		 * @var Learndash_Transaction_Logger|null
		 */
		protected $logger = null;

		/**
		 * User being processing. Set on init action.
		 *
		 * @since 4.5.0
		 *
		 * @var WP_User
		 */
		protected $user;

		/**
		 * Current currency code.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		protected $currency_code;

		/**
		 * Customer id meta key name.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		protected $customer_id_meta_key = '';

		/**
		 * Parent transaction ID.
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		protected $parent_transaction_id = 0;

		/**
		 * List of all registered gateways.
		 *
		 * @since 4.5.0
		 *
		 * @var Learndash_Payment_Gateway[]
		 */
		private static $gateways = array();

		/**
		 * List of initiated payment gateways.
		 * If a payment gateway is not ready (configured properly), it is not initiated, so it is not added to this list.
		 * Key is the gateway name. Value is the instance of the gateway.
		 *
		 * @since 4.5.0
		 *
		 * @var Learndash_Payment_Gateway[]
		 */
		private static $active_gateways = array();

		/**
		 * Unique log ID.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $log_prefix = '';

		/**
		 * Construction.
		 *
		 * @since 4.5.0
		 */
		public function __construct() {
			$this->user          = wp_get_current_user();
			$this->currency_code = mb_strtoupper( learndash_get_currency_code() );
			$this->log_prefix    = uniqid();

			// add transaction loggers.
			if ( $this->supports_logger() ) {
				$this->logger = new Learndash_Transaction_Logger( $this );

				add_filter(
					'learndash_loggers',
					function( array $loggers ): array {
						$loggers[] = $this->logger;

						return $loggers;
					}
				);
			}
		}

		/**
		 * Initiates actions and filters if enabled and configured.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function init(): void {
			self::$gateways[ static::get_name() ] = $this;

			$this->configure();

			if ( ! $this->is_ready() ) {
				return;
			}

			self::$active_gateways[ static::get_name() ] = $this;

			$this->add_extra_hooks();

			add_action(
				'learndash_payment_button_added',
				function() {
					wp_enqueue_script( 'learndash-payments' );

					$this->enqueue_scripts();
				}
			);

			add_filter( 'learndash_payment_buttons', array( $this, 'add_payment_button' ), 11, 3 );

			add_action( 'wp_ajax_nopriv_' . $this->get_ajax_action_name_setup(), array( $this, 'setup_payment' ) );
			add_action( 'wp_ajax_' . $this->get_ajax_action_name_setup(), array( $this, 'setup_payment' ) );

			add_action( 'wp_loaded', array( $this, 'process_webhook' ), 10, 0 );
		}

		/**
		 * Returns a flag to easily identify if the gateway supports logger. Default is true.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if a gateway supports logger. False otherwise.
		 */
		public function supports_logger(): bool {
			return true;
		}

		/**
		 * Returns a flag to easily identify if the gateway supports transactions management. Default is false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if a gateway supports managing subscriptions/other transactions. False otherwise.
		 */
		public function supports_transactions_management(): bool {
			return false;
		}

		/**
		 * Cancels a subscription.
		 *
		 * @since 4.5.0
		 *
		 * @param string $subscription_id Subscription ID.
		 *
		 * @return bool|WP_Error True if cancelled. Otherwise, WP_Error.
		 */
		public function cancel_subscription( string $subscription_id ) {
			return new WP_Error(
				self::$wp_error_code,
				sprintf(
					/* translators: placeholders: Gateway name */
					esc_html_x( 'Subscription cancellation is not supported by %1$s gateway.', 'placeholder: Gateway name', 'learndash' ),
					static::get_name()
				)
			);
		}

		/**
		 * Returns price in subunits.
		 *
		 * @since 4.5.2
		 *
		 * @param float $price Price.
		 *
		 * @return int
		 */
		protected function get_price_in_subunits( float $price ): int {
			return (int) round( $price * 100 );
		}

		/**
		 * Returns the gateway name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		abstract public static function get_name(): string;

		/**
		 * Returns the gateway label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		abstract public static function get_label(): string;

		/**
		 * Adds hooks from gateway classes.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		abstract public function add_extra_hooks(): void;

		/**
		 * Enqueues scripts.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		abstract public function enqueue_scripts(): void;

		/**
		 * Creates a session/order/subscription or prepares payment options on backend.
		 *
		 * @since 4.5.0
		 *
		 * @return void Json response.
		 */
		abstract public function setup_payment(): void;

		/**
		 * Configures gateway.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		abstract protected function configure(): void;

		/**
		 * Returns true if everything is configured and payment gateway can be used, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		abstract public function is_ready(): bool;

		/**
		 * Returns true it's a test mode, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		abstract protected function is_test_mode(): bool;

		/**
		 * Returns payment button HTML markup.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $params Payment params.
		 * @param WP_Post      $post   Post being processing.
		 *
		 * @return string Payment button HTML markup.
		 */
		abstract protected function map_payment_button_markup( array $params, WP_Post $post ): string;

		/**
		 * Maps transaction meta.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed                   $data    Data.
		 * @param Learndash_Product_Model $product Product.
		 *
		 * @throws Learndash_DTO_Validation_Exception Transaction data validation exception.
		 *
		 * @return Learndash_Transaction_Meta_DTO
		 */
		abstract protected function map_transaction_meta( $data, Learndash_Product_Model $product ): Learndash_Transaction_Meta_DTO;

		/**
		 * Returns AJAX action name for setup.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		protected function get_ajax_action_name_setup(): string {
			return 'learndash_payment_gateway_setup_' . static::get_name();
		}

		/**
		 * Handles the webhook.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		abstract public function process_webhook(): void;

		/**
		 * Gets payment gateways select. Keys are gateway names and values are gateway labels.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string, string>
		 */
		public static function get_select_list(): array {
			$result = array();

			foreach ( self::$gateways as $gateway ) {
				$result[ $gateway::get_name() ] = $gateway::get_label();
			}

			return $result;
		}

		/**
		 * Gets a payment gateway instance by name.
		 *
		 * @since 4.5.0
		 *
		 * @param string $payment_gateway_name Payment gateway name.
		 *
		 * @return Learndash_Payment_Gateway Instance of the payment gateway. Learndash_Unknown_Gateway if not found or not ready.
		 */
		public static function get_active_payment_gateway_by_name( string $payment_gateway_name ): Learndash_Payment_Gateway {
			if ( ! isset( self::$active_gateways[ $payment_gateway_name ] ) ) {
				return self::$active_gateways[ Learndash_Unknown_Gateway::get_name() ];
			}

			return self::$active_gateways[ $payment_gateway_name ];
		}

		/**
		 * Adds button.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,string> $buttons Payment buttons. An associative array where a key is a payment gateway name, and a value is payment button HTML markup.
		 * @param WP_Post              $post    Post being processing.
		 * @param array<mixed>         $params  Payment params.
		 *
		 * @return array<string,string> Payment buttons list.
		 */
		public function add_payment_button( array $buttons, WP_Post $post, array $params ): array {
			$buttons[ static::get_name() ] = $this->map_payment_button_markup( $params, $post );

			return $buttons;
		}

		/**
		 * Returns a successful enrollment url.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products    Products.
		 * @param string                    $gateway_url Gateway url. Default empty string.
		 *
		 * @return string
		 */
		public static function get_url_success( array $products, string $gateway_url = '' ): string {
			$products = array_filter(
				$products,
				function ( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);
			$products = array_values( $products );

			// For buy now and recurring types, use the “enrollment URL” field from post settings.

			if ( 1 === count( $products ) ) {
				$product = $products[0];

				if ( learndash_is_course_post( $product->get_post() ) ) {
					$enrollment_url = learndash_get_course_enrollment_url( $product->get_post() );
				} elseif ( learndash_is_group_post( $product->get_post() ) ) {
					$enrollment_url = learndash_get_group_enrollment_url( $product->get_post() );
				}

				if ( ! empty( $enrollment_url ) ) {
					/**
					 * Filters URL for successful payments.
					 *
					 * @since 4.5.0
					 *
					 * @param string                    $url                  The URL, where user will be redirected after the successful payment.
					 * @param string                    $payment_gateway_name Payment gateway name.
					 * @param Learndash_Product_Model[] $products             Purchased products.
					 *
					 * @return string The URL, where user will be redirected after the successful payment.
					 */
					return apply_filters( 'learndash_payment_option_url_success', $enrollment_url, static::get_name(), $products );
				}
			}

			// Payment gateway setting “return url”.

			if ( ! empty( $gateway_url ) ) {
				/** This filter is documented in includes/payments/gateways/class-learndash-payment-gateway.php */
				return apply_filters( 'learndash_payment_option_url_success', $gateway_url, static::get_name(), $products );
			}

			// "Registration success" page link in settings.

			$registration_success_page_id = (int) LearnDash_Settings_Section::get_section_setting(
				'LearnDash_Settings_Section_Registration_Pages',
				'registration_success'
			);

			if ( ! empty( $registration_success_page_id ) ) {
				$registration_success_page_url = get_permalink( $registration_success_page_id );

				if ( ! empty( $registration_success_page_url ) ) {
					/** This filter is documented in includes/payments/gateways/class-learndash-payment-gateway.php */
					return apply_filters( 'learndash_payment_option_url_success', $registration_success_page_url, static::get_name(), $products );
				}
			}

			// Link to post.

			if ( 1 === count( $products ) ) {
				$post_url = get_permalink( $products[0]->get_post() );

				if ( ! empty( $post_url ) ) {
					/** This filter is documented in includes/payments/gateways/class-learndash-payment-gateway.php */
					return apply_filters( 'learndash_payment_option_url_success', $post_url, static::get_name(), $products );
				}
			}

			// Home url.

			/** This filter is documented in includes/payments/gateways/class-learndash-payment-gateway.php */
			return apply_filters( 'learndash_payment_option_url_success', get_home_url(), static::get_name(), $products );
		}

		/**
		 * Returns a cancellation url.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products    Products.
		 * @param string                    $gateway_url Gateway url. Default empty string.
		 *
		 * @return string
		 */
		public static function get_url_fail( array $products, string $gateway_url = '' ): string {
			$products = array_filter(
				$products,
				function ( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);
			$products = array_values( $products );

			// Payment gateway setting “cancel url”. Currently, PayPal IPN only case.

			if ( ! empty( $gateway_url ) ) {
				/**
				 * Filters URL for failed payments.
				 *
				 * @since 4.5.0
				 *
				 * @param string                    $url                  The URL, where user will be redirected after the failed payment.
				 * @param string                    $payment_gateway_name Payment gateway name.
				 * @param Learndash_Product_Model[] $products             Purchased products.
				 *
				 * @return string The URL, where user will be redirected after the failed payment.
				 */
				return apply_filters( 'learndash_payment_option_url_fail', $gateway_url, static::get_name(), $products );
			}

			$url = get_home_url();

			// Link to post.

			if ( 1 === count( $products ) ) {
				$post_url = get_permalink( $products[0]->get_post() );

				if ( ! empty( $post_url ) ) {
					$url = $post_url;
				}
			}

			/** This filter is documented in includes/payments/gateways/class-learndash-payment-gateway.php */
			return apply_filters( 'learndash_payment_option_url_fail', $url, static::get_name(), $products );
		}

		/**
		 * Returns a description based on the post titles.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products Products.
		 *
		 * @return string
		 */
		protected function map_description( array $products = array() ): string {
			$products = array_filter(
				$products,
				function( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);

			$product_titles = array_map(
				function( Learndash_Product_Model $product ): string {
					return $product->get_post()->post_title;
				},
				$products
			);

			return implode( ', ', $product_titles );
		}

		/**
		 * Returns a post's thumbnail url if only one post and the thumbnail is set, otherwise returns LD logo.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products Products.
		 *
		 * @return string
		 */
		protected function get_image_url( array $products = array() ): string {
			$image_url = '';

			$products = array_filter(
				$products,
				function ( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);
			$products = array_values( $products );

			if ( 1 === count( $products ) ) {
				$image_url = (string) get_the_post_thumbnail_url(
					$products[0]->get_id()
				);
			}

			if ( empty( $image_url ) ) {
				$logo_image_id = LearnDash_Settings_Section::get_section_setting(
					'LearnDash_Settings_Theme_LD30',
					'login_logo'
				);

				$image_url = (string) wp_get_attachment_url( $logo_image_id );
			}

			/**
			 * Filters payment image url.
			 *
			 * @since 4.5.0
			 *
			 * @param string $image_url            Image url.
			 * @param string $payment_gateway_name Payment gateway name.
			 *
			 * @return string Payment image url.
			 */
			$image_url = (string) apply_filters( 'learndash_payment_option_image_url', $image_url, static::get_name() );

			return esc_url( $image_url );
		}

		/**
		 * Records a payment transaction.
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed> $meta Transaction meta.
		 * @param WP_Post                 $post Post.
		 * @param WP_User                 $user User.
		 *
		 * @return int Return the newly created transaction ID.
		 */
		protected function record_transaction( array $meta, WP_Post $post, WP_User $user ): int {
			$transaction_id = learndash_transaction_create( $meta, $post, $user, $this->parent_transaction_id );

			if ( 0 === $this->parent_transaction_id ) {
				$transaction = Learndash_Transaction_Model::find( $transaction_id );

				if ( $transaction ) {
					$parent_transaction = $transaction->get_parent();

					$this->parent_transaction_id = $parent_transaction ? $parent_transaction->get_id() : 0;
				}
			}

			return $transaction_id;
		}

		/**
		 * Returns WP_User object or null.
		 *
		 * @since 4.5.0
		 *
		 * @param int    $user_id     User ID.
		 * @param string $email       Email address.
		 * @param string $customer_id Gateway customer ID.
		 *
		 * @return WP_User|null
		 */
		protected function find_or_create_user( int $user_id, string $email, string $customer_id ): ?WP_User {
			// Try to find by ID.
			$user = $user_id > 0
				? get_user_by( 'ID', $user_id )
				: null;

			// Try to find by an email or create with an email.
			if ( ! $user instanceof WP_User && ! empty( $email ) ) {
				$user = get_user_by( 'email', $email );

				if ( ! $user instanceof WP_User ) {
					$user = $this->create_user( $email );
				}
			}

			if ( ! $user instanceof WP_User ) {
				return null;
			}

			if (
				! empty( $this->customer_id_meta_key ) &&
				! empty( $customer_id ) &&
				empty( get_user_meta( $user->ID, $this->customer_id_meta_key, true ) )
			) {
				update_user_meta( $user->ID, $this->customer_id_meta_key, $customer_id );
			}

			return $user;
		}

		/**
		 * Returns nonce name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		protected function get_nonce_name(): string {
			return 'learndash-payment-gateway-' . static::get_name() . '-nonce';
		}

		/**
		 * Returns payment form class name.
		 *
		 * @since 4.5.0
		 *
		 * @param string $additional_class Additional class. Default empty string.
		 *
		 * @return string
		 */
		protected function get_form_class_name( string $additional_class = '' ): string {
			$form_class = 'learndash-payment-gateway-form-' . static::get_name();

			return $form_class . " $additional_class";
		}

		/**
		 * Creates a user.
		 *
		 * @since 4.5.0
		 *
		 * @param string $email Email.
		 *
		 * @return WP_User|null
		 */
		private function create_user( string $email ): ?WP_User {
			$username = $email;
			$password = wp_generate_password( 18 );

			/**
			 * Filters whether to shorten a username from an email or keep an email.
			 *
			 * @since 4.5.0
			 *
			 * @param bool $shorten Default false.
			 *
			 * @return bool True to shorten, otherwise false.
			 */
			$shorten = (bool) apply_filters( 'learndash_payment_gateway_get_username_from_email', false );

			if ( Learndash_Stripe_Gateway::get_name() === static::get_name() ) {
				/**
				 * Filters whether to shorten a username from an email or keep an email. Default false.
				 *
				 * @since 4.0.0
				 * @deprecated 4.5.0 Use the {@see 'learndash_payment_gateway_get_username_from_email'} filter instead.
				 *
				 * @param bool $shorten True to shorten. Default false.
				 *
				 * @return bool
				 */
				$shorten = apply_filters_deprecated(
					'learndash_stripe_create_short_username',
					array( $shorten ),
					'4.5.0',
					'learndash_get_username_from_email'
				);
			}

			if ( $shorten ) {
				$username = (string) preg_replace( '/(.*)\@(.*)/', '$1', $username );
			}

			if ( username_exists( $username ) ) {
				$username = $username . '-' . uniqid();
			}

			$user_id = wp_create_user( $username, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				return null;
			}

			global $wp_version;

			if ( version_compare( $wp_version, '4.3.0', '<' ) ) {
				// phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found
				wp_new_user_notification( $user_id, $password ); // @phpstan-ignore-line WP 4.3.0 and lower.
			} elseif ( version_compare( $wp_version, '4.3.0', '==' ) ) {
				// phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found
				wp_new_user_notification( $user_id, 'both' ); // @phpstan-ignore-line WP 4.3.0.
			} elseif ( version_compare( $wp_version, '4.3.1', '>=' ) ) {
				wp_new_user_notification( $user_id, null, 'both' );
			}

			if ( Learndash_Razorpay_Gateway::get_name() === static::get_name() ) {
				/**
				 * Fires after a user is created with Razorpay.
				 *
				 * @since 4.2.0
				 * @deprecated 4.5.0 Use the {@see 'learndash_payment_gateway_user_created'} action instead.
				 *
				 * @param int $user_id User ID.
				 */
				do_action_deprecated(
					'learndash_user_created_with_razorpay',
					array( $user_id ),
					'4.5.0',
					'learndash_payment_gateway_user_created'
				);
			}

			if ( Learndash_Stripe_Gateway::get_name() === static::get_name() ) {
				/**
				 * Fires after a user is created with Stripe.
				 *
				 * @deprecated 4.5.0 Use the {@see 'learndash_payment_gateway_user_created'} action instead.
				 *
				 * @param int $user_id User ID.
				 */
				do_action_deprecated(
					'learndash_stripe_after_create_user',
					array( $user_id ),
					'4.5.0',
					'learndash_payment_gateway_user_created'
				);
			}

			$user = get_user_by( 'ID', $user_id );

			if ( ! $user instanceof WP_User ) {
				return null;
			}

			/**
			 * Fires after a user is created by payment gateway.
			 *
			 * @since 4.5.0
			 *
			 * @param WP_User $user                 User.
			 * @param string  $payment_gateway_name Payment gateway name.
			 */
			do_action( 'learndash_payment_gateway_user_created', $user, static::get_name() );

			return $user;
		}

		/**
		 * Adds access to products for a user.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products Products.
		 * @param WP_User                   $user     User.
		 *
		 * @return array<int, mixed> Return the results of enroll() method of each product update.
		 */
		protected function add_access_to_products( array $products, WP_User $user ): array {
			$products = array_filter(
				$products,
				function ( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);

			$updates = array();

			foreach ( $products as $product ) {
				$updates[ $product->get_id() ] = $product->enroll( $user );
			}

			return $updates;
		}

		/**
		 * Removes access to products for a user.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Product_Model[] $products Products.
		 * @param WP_User                   $user     User.
		 *
		 * @return array<int, mixed> Return the results of unenroll() method of each product update.
		 */
		protected function remove_access_to_products( array $products, WP_User $user ): array {
			$products = array_filter(
				$products,
				function ( $product ) {
					return $product instanceof Learndash_Product_Model;
				}
			);

			$updates = array();

			foreach ( $products as $product ) {
				$updates[ $product->get_id() ] = $product->unenroll( $user );
			}

			return $updates;
		}

		/**
		 * Maps the payment button label.
		 *
		 * @since 4.5.0
		 *
		 * @param string  $gateway_button_label Gateway button label.
		 * @param WP_Post $post                 Post.
		 *
		 * @return string
		 */
		protected function map_payment_button_label( string $gateway_button_label, WP_Post $post ): string {
			$button_label = $gateway_button_label;

			$active_gateways = array_filter(
				self::$active_gateways,
				function ( $gateway ) {
					return ! $gateway instanceof Learndash_Unknown_Gateway;
				}
			);

			if ( count( $active_gateways ) > 1 ) {
				$button_label = $gateway_button_label;
			} elseif ( learndash_is_course_post( $post ) ) {
				$button_label = LearnDash_Custom_Label::get_label( LearnDash_Custom_Label::$button_take_course );
			} elseif ( learndash_is_group_post( $post ) ) {
				$button_label = LearnDash_Custom_Label::get_label( LearnDash_Custom_Label::$button_take_group );
			}

			if ( Learndash_Razorpay_Gateway::get_name() === static::get_name() ) {
				/**
				 * Filters Razorpay payment button label.
				 *
				 * @since 4.5.0
				 * @deprecated 4.5.0 Use the {@see 'learndash_payment_button_label'} filter instead.
				 *
				 * @param string $button_label Razorpay button label.
				 *
				 * @return string Razorpay button label.
				 */
				$button_label = apply_filters_deprecated(
					'learndash_button_label_razorpay',
					array( $button_label ),
					'4.5.0',
					'learndash_payment_button_label'
				);
			} elseif ( Learndash_Stripe_Gateway::get_name() === static::get_name() ) {
				/**
				 * Filters Stripe payment button label.
				 *
				 * @since 4.0.0
				 * @deprecated 4.5.0 Use the {@see 'learndash_payment_button_label'} filter instead.
				 *
				 * @param string $button_label Stripe button label.
				 *
				 * @return string Stripe button label.
				 */
				$button_label = apply_filters_deprecated(
					'learndash_stripe_purchase_button_text',
					array( $button_label ),
					'4.5.0',
					'learndash_payment_button_label'
				);
			}

			/**
			 * Filters payment button label.
			 *
			 * @since 4.5.0
			 *
			 * @param string $button_label         Payment button label.
			 * @param string $payment_gateway_name Payment gateway name.
			 *
			 * @return string Payment button label.
			 */
			return apply_filters( 'learndash_payment_button_label', $button_label, static::get_name() );
		}

		/**
		 * Allows to stop event processing.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $event Event.
		 *
		 * @return bool True to ignore, false otherwise.
		 */
		protected function maybe_ignore_event( array $event ): bool {
			if ( Learndash_Razorpay_Gateway::get_name() === static::get_name() ) {
				/**
				 * Filters whether to process the Razorpay webhook or not.
				 *
				 * @since 4.5.0
				 * @deprecated 4.5.0
				 *
				 * @param bool  $process To process or not. True by default.
				 * @param array $event   Decoded Razorpay event.
				 *
				 * @return bool
				 */
				if (
					! apply_filters_deprecated(
						'learndash_process_webhook_razorpay',
						array( true, $event ),
						'4.5.0',
						'learndash_payment_gateway_event_ignore'
					)
				) {
					return true;
				}
			} elseif ( Learndash_Stripe_Gateway::get_name() === static::get_name() ) {
				/**
				 * Filters whether to process the Stripe webhook or not.
				 *
				 * True by default.
				 *
				 * @since 4.0.0
				 * @deprecated 4.5.0
				 *
				 * @param bool   $allow_processing To process or not. Default true.
				 * @param object $event            Decoded Stripe event.
				 *
				 * @return bool
				 */
				if (
					! apply_filters_deprecated(
						'learndash_stripe_process_webhook',
						array( true, json_decode( (string) wp_json_encode( $event ) ) ),
						'4.5.0',
						'learndash_payment_gateway_event_ignore'
					)
				) {
					return true;
				}
			}

			/**
			 * Filters whether to ignore a payment webhook or not.
			 *
			 * False by default.
			 *
			 * @since 4.5.0
			 *
			 * @param bool   $ignore       To ignore or not. Default false.
			 * @param string $gateway_name Gateway name.
			 * @param array  $event        Event data.
			 *
			 * @return bool
			 */
			return apply_filters( 'learndash_payment_gateway_event_ignore', false, static::get_name(), $event );
		}

		/**
		 * Finishes processing the event.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $event     Event data.
		 * @param bool         $processed True if it was processed, false if ignored.
		 *
		 * @return void
		 */
		protected function finish_webhook_processing( array $event, bool $processed ): void {
			$this->log_info( $processed ? 'Webhook processing completed.' : 'Webhook processing disabled.' );

			/**
			 * Fires when the payment gateway event is processed (can be ignored too).
			 *
			 * @since 4.5.0
			 *
			 * @param array  $event        Event data.
			 * @param bool   $processed    True if it was processed, false if ignored.
			 * @param string $gateway_name Gateway name.
			 */
			do_action( 'learndash_payment_gateway_event_processed', $event, $processed, static::get_name() );

			wp_send_json_success(
				array(
					'message' => $processed ? 'Event was processed successfully.' : 'Event was ignored.',
				),
				200
			);
		}

		/**
		 * Writes an information to the log.
		 *
		 * @since 4.5.0
		 *
		 * @param string $message Message.
		 *
		 * @return void
		 */
		protected function log_info( string $message ): void {
			if ( ! $this->supports_logger() || ! $this->logger ) {
				return;
			}

			$this->logger->info( $message, $this->log_prefix );
		}

		/**
		 * Writes an error to the log.
		 *
		 * @since 4.5.0
		 *
		 * @param string $message Message.
		 *
		 * @return void
		 */
		protected function log_error( string $message ): void {
			if ( ! $this->supports_logger() || ! $this->logger ) {
				return;
			}

			$this->logger->error( $message, $this->log_prefix );
		}
	}
}
