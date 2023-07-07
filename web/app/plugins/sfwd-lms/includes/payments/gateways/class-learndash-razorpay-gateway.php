<?php
/**
 * This class handles Razorpay integration.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Models\Product;
use LearnDash\Core\Models\Transaction;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

if ( ! class_exists( 'Learndash_Razorpay_Gateway' ) && class_exists( 'Learndash_Payment_Gateway' ) ) {
	/**
	 * Razorpay gateway class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Razorpay_Gateway extends Learndash_Payment_Gateway {
		private const GATEWAY_NAME = 'razorpay';

		private const META_KEY_CUSTOMER_ID_LIVE = 'learndash_razorpay_live_customer_id';
		private const META_KEY_CUSTOMER_ID_TEST = 'learndash_razorpay_test_customer_id';

		private const META_KEY_PLANS_LIVE = 'learndash_razorpay_live_plans';
		private const META_KEY_PLANS_TEST = 'learndash_razorpay_test_plans';

		private const PERIOD_HASH = array(
			'D' => 'daily',
			'W' => 'weekly',
			'M' => 'monthly',
			'Y' => 'yearly',
		);

		/**
		 * Why this constant exists: clients can have many payments, and we have to check the latest ones
		 * to get the user email from a payment for guest subscriptions.
		 *
		 * Max number of payment pages to check in the API for guest subscriptions.
		 * 100 (API maximum) payments are checked per page.
		 * So 3 means that in the worst case)300 latest payments will be checked with 3 API queries.
		 */
		private const API_PAYMENT_PAGES_LIMIT = 3;

		private const API_PER_PAGE = 100;

		private const TRIAL_PERIOD_DURATION_HASH = array(
			'D' => DAY_IN_SECONDS,
			'W' => WEEK_IN_SECONDS,
			'M' => MONTH_IN_SECONDS,
			'Y' => YEAR_IN_SECONDS,
		);

		private const EVENT_ORDER_PAID                 = 'order.paid';
		private const EVENT_SUBSCRIPTION_AUTHENTICATED = 'subscription.authenticated';
		private const EVENT_SUBSCRIPTION_ACTIVATED     = 'subscription.activated';
		private const EVENT_SUBSCRIPTION_COMPLETED     = 'subscription.completed';
		private const EVENT_SUBSCRIPTION_PENDING       = 'subscription.pending';
		private const EVENT_SUBSCRIPTION_HALTED        = 'subscription.halted';
		private const EVENT_SUBSCRIPTION_CANCELLED     = 'subscription.cancelled';
		private const EVENT_SUBSCRIPTION_PAUSED        = 'subscription.paused';
		private const EVENT_SUBSCRIPTION_RESUMED       = 'subscription.resumed';

		/**
		 * Secret key.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $secret_key;

		/**
		 * Publishable key.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $publishable_key;

		/**
		 * Webhook secret.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $webhook_secret;

		/**
		 * API client.
		 *
		 * @since 4.5.0
		 *
		 * @var Api|null
		 */
		private $api = null;

		/**
		 * Razorpay plans meta key name.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $plans_meta_key;

		/**
		 * Returns a flag to easily identify if the gateway supports transactions management.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if a gateway supports managing subscriptions/other transactions. False otherwise.
		 */
		public function supports_transactions_management(): bool {
			return true;
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
			try {
				$subscription = $this->api->subscription->fetch( $subscription_id )->cancel(); // @phpstan-ignore-line -- Property access via magic method.

				return 'cancelled' === $subscription->status;
			} catch ( Exception $e ) {
				return new WP_Error( self::$wp_error_code, $e->getMessage() );
			}
		}

		/**
		 * Returns the gateway name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public static function get_name(): string {
			return self::GATEWAY_NAME;
		}

		/**
		 * Returns the gateway label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public static function get_label(): string {
			return esc_html__( 'Razorpay', 'learndash' );
		}

		/**
		 * Adds hooks.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function add_extra_hooks(): void {
			// No hooks.
		}

		/**
		 * Enqueues scripts.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'razorpay', 'https://checkout.razorpay.com/v1/checkout.js', array(), false, true ); // phpcs:ignore -- WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		}

		/**
		 * Creates an order/subscription in Razorpay.
		 *
		 * @since 4.5.0
		 *
		 * @return void Json response.
		 */
		public function setup_payment(): void {
			if (
				empty( (int) $_POST['post_id'] ) ||
				empty( $_POST['nonce'] ) ||
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
					$this->get_nonce_name()
				)
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Cheating?', 'learndash' ),
					)
				);
			}

			$product = Product::find( (int) $_POST['post_id'] );

			if ( ! $product ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Cheating?', 'learndash' ),
					)
				);
			}

			try {
				$payment_options = $this->map_payment_options( $product );
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'message' => esc_html( $e->getMessage() ),
					)
				);
			}

			wp_send_json_success(
				array(
					'options'      => $payment_options,
					'redirect_url' => $this->get_url_success(
						array( $product ),
						strval( $this->settings['return_url'] ?? '' )
					),
				)
			);
		}

		/**
		 * Returns true if everything is configured and payment gateway can be used, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		public function is_ready(): bool {
			$enabled = 'yes' === ( $this->settings['enabled'] ?? '' );

			return $enabled && ! empty( $this->secret_key ) && ! empty( $this->publishable_key ) && ! empty( $this->webhook_secret );
		}

		/**
		 * Configures settings.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		protected function configure(): void {
			$this->settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_Razorpay' );

			$setting_suffix = $this->is_test_mode() ? 'test' : 'live';

			$this->secret_key           = $this->settings[ "secret_key_$setting_suffix" ];
			$this->publishable_key      = $this->settings[ "publishable_key_$setting_suffix" ];
			$this->webhook_secret       = $this->settings[ "webhook_secret_$setting_suffix" ];
			$this->customer_id_meta_key = $this->is_test_mode() ? self::META_KEY_CUSTOMER_ID_TEST : self::META_KEY_CUSTOMER_ID_LIVE;
			$this->plans_meta_key       = $this->is_test_mode() ? self::META_KEY_PLANS_TEST : self::META_KEY_PLANS_LIVE;

			if ( ! class_exists( 'Razorpay\Api\Api' ) ) {
				require_once LEARNDASH_LMS_LIBRARY_DIR . '/razorpay-php/Razorpay.php';
			}

			if ( ! empty( $this->secret_key ) ) {
				$this->api = new Api( $this->publishable_key, $this->secret_key );
			}
		}

		/**
		 * Returns true it's a test mode, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		protected function is_test_mode(): bool {
			return isset( $this->settings['test_mode'] ) && $this->settings['test_mode'];
		}

		/**
		 * Returns Razorpay options.
		 *
		 * @since 4.5.0
		 *
		 * @param Product $product Product.
		 *
		 * @throws InvalidArgumentException|Exception Throws if not valid arguments passed or something went wrong during API request.
		 *
		 * @return array{
		 *     key: string,
		 *     name: string,
		 *     description: string,
		 *     image?: string,
		 *     notes: array{
		 *          is_learndash: bool,
		 *          learndash_version: string,
		 *          post_id: int,
		 *          user_id: int,
		 *          operation_id: string,
		 *     },
		 *     order_id?: string,
		 *     customer_id?: string,
		 *     subscription_id?: string,
		 * }
		 */
		private function map_payment_options( Product $product ): array {
			// Create a customer entity if needed.

			$customer_id = $this->user->ID > 0 ? $this->find_or_create_customer_id() : null;

			// Map the price.

			$pricing = $product->get_pricing( $this->user );

			/** This filter is documented in includes/payments/gateways/class-learndash-stripe-gateway.php */
			$price = apply_filters(
				'learndash_get_price_by_coupon',
				$pricing->price,
				$product->get_id(),
				$this->user->ID
			);

			$price_in_subunits = $this->get_price_in_subunits( $price );

			if ( $price_in_subunits < 1 ) {
				throw new Exception( __( 'The minimum Price is 1.', 'learndash' ) );
			}

			// Create options.

			$required_notes = array(
				'is_learndash'      => true,
				'learndash_version' => LEARNDASH_VERSION,
				'post_id'           => $product->get_id(),
				'user_id'           => $this->user->ID,
				'operation_id'      => uniqid() . '_' . md5( $this->user->ID . '_' . $product->get_id() ), // Used to connect payments and subscriptions for guest users.
			);

			$options = array_filter(
				array(
					'key'         => $this->publishable_key,
					'name'        => get_bloginfo( 'name' ),
					'description' => $this->map_description( array( $product ) ),
					'image'       => $this->get_image_url( array( $product ) ),
					'notes'       => $required_notes,
				)
			);

			if ( $product->is_price_type_subscribe() ) {
				$options['subscription_id'] = $this->create_subscription_id( $price_in_subunits, $pricing, $product, $required_notes );
			} elseif ( $product->is_price_type_paynow() ) {
				if ( ! is_null( $customer_id ) ) {
					$options['customer_id'] = $customer_id;
				}
				$options['order_id'] = $this->create_order_id( $price_in_subunits, $required_notes );
			}

			/**
			 * Filters Razorpay payment options before creation.
			 *
			 * @since 4.2.0
			 *
			 * @param array $options Razorpay payment options.
			 */
			$options = apply_filters( 'learndash_payment_options_razorpay', $options );

			/**
			 * Options.
			 *
			 * @var array{
			 *     key: string,
			 *     name: string,
			 *     description: string,
			 *     image?: string,
			 *     notes: array{
			 *          is_learndash: bool,
			 *          learndash_version: string,
			 *          post_id: int,
			 *          user_id: int,
			 *          operation_id: string,
			 *     },
			 *     customer_id?: string,
			 *     order_id?: string,
			 *     subscription_id?: string,
			 * } $options
			 */
			return $options;
		}

		/**
		 * Handles the webhook.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function process_webhook(): void {
			// phpcs:ignore -- WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['learndash-integration'] ) || $this->get_name() !== $_GET['learndash-integration'] ) {
				return;
			}

			$this->log_info( 'Webhook received.' );

			$event = $this->validate_webhook_event_or_fail();

			/**
			 * Entity. Never empty because it was validated.
			 *
			 * @var array{
			 *     id: string,
			 *     customer_id?: string,
			 *     notes: array{
			 *         is_learndash?: bool,
			 *         learndash_version?: string,
			 *         post_id?: int,
			 *         user_id?: int,
			 *         operation_id?: string,
			 *     }
			 * } $entity */
			$entity = $this->get_main_entity_from_event( $event );

			$user     = $this->setup_user_or_fail( $entity, $event );
			$products = $this->setup_products_or_fail( (int) ( $entity['notes']['post_id'] ?? 0 ) );

			$this->process_webhook_event( $event, $products, $user );
		}

		/**
		 * Returns subscription/order entity from the event.
		 *
		 * @since 4.5.0
		 *
		 * @param array $event Webhook event.
		 *
		 * @phpstan-param array{
		 *     contains: string[],
		 *     payload: array{
		 *         id: string,
		 *         notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string},
		 *         order?: array{entity: array<mixed>},
		 *         subscription?: array{entity: array<mixed>},
		 *     }
		 * } $event
		 *
		 * @return array{}|array{
		 *     id: string,
		 *     customer_id?: string,
		 *     notes: array{
		 *         is_learndash?: bool,
		 *         learndash_version?: string,
		 *         post_id?: int,
		 *         user_id?: int,
		 *         operation_id?: string,
		 *     }
		 * }
		 */
		private function get_main_entity_from_event( array $event ): array {
			$entity_key = $this->event_contains_subscription( $event ) ? 'subscription' : 'order';

			if ( ! isset( $event['payload'][ $entity_key ] ) ) {
				return array();
			}

			return $event['payload'][ $entity_key ]['entity'];
		}

		/**
		 * Returns an email from the event if possible.
		 *
		 * @since 4.5.0
		 *
		 * @param array $event   Event.
		 * @param int   $user_id User ID.
		 *
		 * @phpstan-param array{
		 *     event: string,
		 *     contains: array<string>,
		 *     payload: array{
		 *         id: string,
		 *         notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string},
		 *         order?: array{entity: array<mixed>},
		 *         subscription?: array{entity: array<mixed>},
		 *         payment?: array{entity: array<mixed>}
		 *     }
		 * } $event
		 *
		 * @return string
		 */
		private function retrieve_user_email( array $event, int $user_id ): string {
			// If user exists, we can get the email from the user object.
			if ( $user_id > 0 ) {
				$user = get_user_by( 'id', $user_id );

				if ( $user instanceof WP_User ) {
					return $user->user_email;
				}
			}

			// If it does not exist, and it's an order.paid event, we have a payment entity.
			if (
				in_array( 'payment', $event['contains'], true )
				&& isset( $event['payload']['payment'] )
			) {
				return strval( $event['payload']['payment']['entity']['email'] );
			}

			// The worst case: "subscription.authenticated" event and a guest user.
			if ( $this->event_contains_subscription( $event ) ) {
				$subscription_entity = $this->get_main_entity_from_event( $event );

				// For events before version 4.5.0 we don't have this unique ID.
				if ( empty( $subscription_entity['notes']['operation_id'] ) ) {
					return '';
				}

				$skip = 0;

				while ( $skip / self::API_PER_PAGE < self::API_PAYMENT_PAGES_LIMIT ) {
					$payments = $this->api->payment->all( // @phpstan-ignore-line -- Property access via magic method.
						array(
							'count' => self::API_PER_PAGE,
							'skip'  => $skip,
						)
					);

					if ( 0 === $payments->count ) {
						break;
					}

					foreach ( $payments->items as $payment_entity ) {
						if (
							isset( $payment_entity->notes['operation_id'] )
							&& $payment_entity->notes['operation_id'] === $subscription_entity['notes']['operation_id']
						) {
							return $payment_entity->email; // @phpstan-ignore-line -- Property access via magic method.
						}
					}

					$skip += self::API_PER_PAGE;
				}
			}

			return '';
		}

		/**
		 * Returns true if it's a subscription event.
		 *
		 * @since 4.5.0
		 *
		 * @param array{contains: string[]} $event Event.
		 *
		 * @return bool
		 */
		private function event_contains_subscription( array $event ): bool {
			return in_array( 'subscription', $event['contains'], true );
		}

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
		public function map_payment_button_markup( array $params, WP_Post $post ): string {
			$button_label = $this->map_payment_button_label(
				__( 'Use Razorpay', 'learndash' ),
				$post
			);

			ob_start();
			?>
			<form
				class="<?php echo esc_attr( $this->get_form_class_name() ); ?>"
				method="post"
				data-action="<?php echo esc_attr( $this->get_ajax_action_name_setup() ); ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( $this->get_nonce_name() ) ); ?>"
				data-post_id="<?php echo esc_attr( (string) $post->ID ); ?>"
			>
				<input class="<?php echo esc_attr( Learndash_Payment_Button::map_button_class_name() ); ?>" id="<?php echo esc_attr( Learndash_Payment_Button::map_button_id() ); ?>" type="submit" value="<?php echo esc_attr( $button_label ); ?>">
			</form>
			<?php
			$buffer = ob_get_clean();
			return $buffer ? $buffer : '';
		}

		/**
		 * Gets or creates a customer. Returns customer id.
		 *
		 * @since 4.5.0
		 *
		 * @throws Exception If customer is not found or created.
		 *
		 * @return string
		 */
		private function find_or_create_customer_id(): string {
			$customer_id = strval( get_user_meta( $this->user->ID, $this->customer_id_meta_key, true ) );

			if ( ! empty( $customer_id ) ) {
				return $customer_id;
			}

			try {
				$customer = $this->api->customer->create( // @phpstan-ignore-line -- Property access via magic method.
					array(
						'name'  => $this->user->display_name,
						'email' => $this->user->user_email,
						'notes' => array(
							'user_id' => $this->user->ID,
						),
					)
				);
			} catch ( Exception $e ) {
				$customer = null;
				$skip     = 0;

				while ( is_null( $customer ) ) {
					$customers = $this->api->customer->all( // @phpstan-ignore-line -- Property access via magic method.
						array(
							'count' => self::API_PER_PAGE,
							'skip'  => $skip,
						)
					);

					if ( 0 === $customers->count ) {
						break;
					}

					foreach ( $customers->items as $customer_entity ) {
						if (
							$customer_entity->email === $this->user->user_email &&
							$customer_entity->name === $this->user->display_name
						) {
							$customer = $customer_entity;
							break;
						}
					}

					$skip += self::API_PER_PAGE;
				}

				if ( is_null( $customer ) ) {
					throw new Exception(
						__( 'Razorpay customer creation failed. And an existing related Razorpay customer was not found.', 'learndash' )
					);
				}
			}

			update_user_meta( $this->user->ID, $this->customer_id_meta_key, $customer->id );

			return $customer->id;
		}

		/**
		 * Creates an order.
		 *
		 * @since 4.5.0
		 *
		 * @param int                 $amount         Amount.
		 * @param array<string,mixed> $required_notes Required notes.
		 *
		 * @throws Learndash_DTO_Validation_Exception If order creation fails.
		 *
		 * @return string
		 */
		private function create_order_id( int $amount, array $required_notes ): string {
			$transaction_meta_dto = Learndash_Transaction_Meta_DTO::create(
				array(
					Transaction::$meta_key_gateway_name => $this::get_name(),
					Transaction::$meta_key_price_type   => LEARNDASH_PRICE_TYPE_PAYNOW,
					Transaction::$meta_key_pricing_info => Learndash_Pricing_DTO::create(
						array(
							'currency' => $this->currency_code,
							'price'    => number_format( $amount / 100, 2, '.', '' ),
						)
					),
				)
			);

			$order = $this->api->order->create( // @phpstan-ignore-line -- Property access via magic method.
				array(
					'amount'          => $amount,
					'currency'        => $this->currency_code,
					'notes'           => array_merge(
						$required_notes,
						array_map(
							function ( $value ) {
								return is_array( $value ) ? wp_json_encode( $value ) : $value;
							},
							$transaction_meta_dto->to_array()
						)
					),
					'partial_payment' => false,
				)
			);

			return $order->id;
		}

		/**
		 * Creates a subscription.
		 *
		 * @since 4.5.0
		 *
		 * @param int                   $price_in_subunits Price.
		 * @param Learndash_Pricing_DTO $pricing           Pricing DTO.
		 * @param Product               $product           Product.
		 * @param array<string,mixed>   $required_notes    Required notes.
		 *
		 * @throws Exception Exception.
		 *
		 * @return string
		 */
		private function create_subscription_id(
			int $price_in_subunits,
			Learndash_Pricing_DTO $pricing,
			Product $product,
			array $required_notes
		): string {
			if ( 0 === $pricing->recurring_times ) {
				throw new Exception( __( 'Razorpay does not support infinite subscriptions.', 'learndash' ) );
			} elseif ( empty( $pricing->duration_length ) ) {
				throw new Exception( __( 'The billing cycle interval value must be set.', 'learndash' ) );
			} elseif ( 0 === $pricing->duration_value ) {
				throw new Exception( __( 'The minimum billing cycle value is 1.', 'learndash' ) );
			} elseif ( 'D' === $pricing->duration_length && $pricing->duration_value < 7 ) {
				throw new Exception( __( 'For daily plans, the minimum billing cycle value is 7.', 'learndash' ) );
			}

			$has_trial = ! empty( $pricing->trial_duration_value ) && ! empty( $pricing->trial_duration_length );

			$transaction_meta_dto = Learndash_Transaction_Meta_DTO::create(
				array(
					Transaction::$meta_key_gateway_name   => $this::get_name(),
					Transaction::$meta_key_price_type     => LEARNDASH_PRICE_TYPE_SUBSCRIBE,
					Transaction::$meta_key_pricing_info   => $pricing,
					Transaction::$meta_key_has_trial      => $has_trial,
					Transaction::$meta_key_has_free_trial => $has_trial && 0.0 === $pricing->trial_price,
				)
			);

			$subscription_options = array(
				'plan_id'     => $this->find_or_create_plan_id( $price_in_subunits, $pricing->duration_value, $pricing->duration_length, $product ),
				'total_count' => $pricing->recurring_times,
				'notes'       => array_merge(
					$required_notes,
					array_map(
						function ( $value ) {
							return is_array( $value ) ? wp_json_encode( $value ) : $value;
						},
						$transaction_meta_dto->to_array()
					)
				),
			);

			// Setup trial period.

			if ( $transaction_meta_dto->has_trial ) {
				// Set subscription start date.
				$subscription_options['start_at'] = time() + $pricing->trial_duration_value * self::TRIAL_PERIOD_DURATION_HASH[ $pricing->trial_duration_length ];

				// Add the trial price as an addon.
				$trial_price_in_subunits = $this->get_price_in_subunits( $pricing->trial_price );

				if ( $trial_price_in_subunits >= 1 ) {
					$subscription_options['addons'] = array(
						array(
							'item' => array(
								'name'     => __( 'Trial', 'learndash' ),
								'amount'   => $trial_price_in_subunits,
								'currency' => $this->currency_code,
							),
						),
					);
				}
			}

			return $this->api->subscription->create( $subscription_options )->id; // @phpstan-ignore-line -- Property access via magic method.
		}

		/**
		 * Creates a plan or returns an existing plan id.
		 *
		 * @since 4.5.0
		 *
		 * @param int     $amount   Amount.
		 * @param int     $interval Interval.
		 * @param string  $period   Period.
		 * @param Product $product  Product.
		 *
		 * @return string Plan ID.
		 */
		private function find_or_create_plan_id( int $amount, int $interval, string $period, Product $product ): string {
			$plan_options = array(
				'period'   => self::PERIOD_HASH[ $period ],
				'interval' => $interval,
				'item'     => array(
					'name'     => $this->map_description( array( $product ) ),
					'amount'   => $amount,
					'currency' => $this->currency_code,
				),
				'notes'    => array(
					'post_id' => $product->get_id(),
				),
			);

			array_multisort( $plan_options );

			$plan_options_hash = md5( (string) wp_json_encode( $plan_options ) );

			$existing_plans = get_post_meta( $product->get_id(), $this->plans_meta_key, true );
			if ( ! is_array( $existing_plans ) ) {
				$existing_plans = array();
			}

			// If we already have an attached plan with the same options hash, we don't need a new plan to be created.
			if ( is_array( $existing_plans ) && isset( $existing_plans[ $plan_options_hash ] ) ) {
				return $existing_plans[ $plan_options_hash ];
			}

			return $this->create_plan( $plan_options, $plan_options_hash, $existing_plans, $product->get_id() );
		}

		/**
		 * Creates a plan.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed>  $plan_options      Plan options.
		 * @param string               $plan_options_hash Plan options hash.
		 * @param array<string,string> $existing_plans    Existing plans.
		 * @param int                  $product_id        Product ID.
		 *
		 * @return string Plan ID.
		 */
		private function create_plan( array $plan_options, string $plan_options_hash, array $existing_plans, int $product_id ): string {
			$plan_id = $this->api->plan->create( $plan_options )->id; // @phpstan-ignore-line -- Property access via magic method.

			$existing_plans[ $plan_options_hash ] = $plan_id;

			update_post_meta( $product_id, $this->plans_meta_key, $existing_plans );

			return $plan_id;
		}

		/**
		 * Maps transaction meta.
		 *
		 * @since 4.5.0
		 *
		 * @param array   $data    Event.
		 * @param Product $product Product.
		 *
		 * @phpstan-param array{
		 *     event: string,
		 *     contains: array<string>,
		 *     payload: array{id: string, notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string}}
		 * } $data
		 *
		 * @throws Learndash_DTO_Validation_Exception Transaction data validation exception.
		 *
		 * @return Learndash_Transaction_Meta_DTO
		 */
		protected function map_transaction_meta( $data, Product $product ): Learndash_Transaction_Meta_DTO {
			$is_subscription_event = $this->event_contains_subscription( $data );
			$entity                = $this->get_main_entity_from_event( $data );

			$meta = array_merge(
				$entity['notes'], // @phpstan-ignore-line -- It was checked before.
				array(
					Transaction::$meta_key_gateway_transaction => Learndash_Transaction_Gateway_Transaction_DTO::create(
						array(
							'id'    => $entity['id'], // @phpstan-ignore-line -- It was checked before.
							'event' => $data,
						)
					),
				)
			);

			$meta = $this->process_legacy_meta(
				$meta,
				$is_subscription_event,
				$entity['notes']['learndash_version'] ?? ''
			);

			if ( is_string( $meta[ Transaction::$meta_key_pricing_info ] ) ) {
				$meta[ Transaction::$meta_key_pricing_info ] = json_decode(
					$meta[ Transaction::$meta_key_pricing_info ],
					true
				);
			}

			return Learndash_Transaction_Meta_DTO::create( $meta );
		}

		/**
		 * Processes legacy meta, converts keys to new format.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $event_meta            Event meta.
		 * @param bool         $is_subscription_event True if event contains subscription.
		 * @param string       $learndash_version     LearnDash version that was used to create the event. Can be empty for older versions.
		 *
		 * @return array<string,mixed>
		 */
		private function process_legacy_meta(
			array $event_meta,
			bool $is_subscription_event,
			string $learndash_version
		): array {
			if ( empty( $learndash_version ) ) {
				if ( ! isset( $event_meta[ Transaction::$meta_key_gateway_name ] ) ) {
					$event_meta[ Transaction::$meta_key_gateway_name ] = self::get_name();
				}

				if ( ! isset( $event_meta[ Transaction::$meta_key_price_type ] ) ) {
					$event_meta[ Transaction::$meta_key_price_type ] = $is_subscription_event
						? LEARNDASH_PRICE_TYPE_SUBSCRIBE
						: LEARNDASH_PRICE_TYPE_PAYNOW;
				}

				if ( isset( $event_meta['pricing'] ) ) {
					/**
					 * Legacy pricing.
					 *
					 * @var array<string,mixed> $legacy_pricing Legacy pricing.
					 */
					$legacy_pricing = json_decode( $event_meta['pricing'], true );

					if ( ! is_array( $legacy_pricing ) ) {
						$legacy_pricing = array();
					}

					if ( $is_subscription_event ) {
						$event_meta[ Transaction::$meta_key_pricing_info ]                          = $legacy_pricing;
						$event_meta[ Transaction::$meta_key_pricing_info ]['recurring_times']       = $legacy_pricing['no_of_cycles'] ?? 0;
						$event_meta[ Transaction::$meta_key_pricing_info ]['duration_value']        = $legacy_pricing['pricing_billing_p3'] ?? 0;
						$event_meta[ Transaction::$meta_key_pricing_info ]['duration_length']       = $legacy_pricing['pricing_billing_t3'] ?? '';
						$event_meta[ Transaction::$meta_key_pricing_info ]['trial_price']           = $legacy_pricing['trial_price'] ?? 0;
						$event_meta[ Transaction::$meta_key_pricing_info ]['trial_duration_value']  = $legacy_pricing['trial_duration_p1'] ?? 0;
						$event_meta[ Transaction::$meta_key_pricing_info ]['trial_duration_length'] = $legacy_pricing['trial_duration_t1'] ?? '';
					} else {
						$event_meta[ Transaction::$meta_key_pricing_info ] = $legacy_pricing;
					}

					// Encode to decode later, just for compatibility with the new code.
					$event_meta[ Transaction::$meta_key_pricing_info ] = wp_json_encode(
						$event_meta[ Transaction::$meta_key_pricing_info ]
					);

					unset( $event_meta['pricing'] );
				}
			}

			return $event_meta;
		}

		/**
		 * Creates/finds a user or sends a json error on fail.
		 *
		 * @since 4.5.0
		 *
		 * @param array $entity Entity.
		 * @param array $event  Event.
		 *
		 * @phpstan-param array{
		 *     id: string,
		 *     customer_id?: string,
		 *     notes: array{
		 *         is_learndash?: bool,
		 *         learndash_version?: string,
		 *         post_id?: int,
		 *         user_id?: int,
		 *         operation_id?: string,
		 *     }
		 * } $entity
		 * @phpstan-param array{
		 *     event: string,
		 *     contains: array<string>,
		 *     payload: array{
		 *         id: string,
		 *         notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string},
		 *         order?: array{entity: array<mixed>},
		 *         subscription?: array{entity: array<mixed>},
		 *         payment?: array{entity: array<mixed>}
		 *     }
		 * } $event
		 *
		 * @return WP_User
		 */
		private function setup_user_or_fail( array $entity, array $event ): WP_User {
			$user_id = (int) ( $entity['notes']['user_id'] ?? 0 );

			$user = $this->find_or_create_user(
				$user_id,
				$this->retrieve_user_email( $event, $user_id ),
				(string) ( $entity['customer_id'] ?? '' )
			);

			if ( ! $user instanceof WP_User ) {
				$this->log_error( 'No WP user found and failed to create a new user.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'User validation failed. User was not found or had not been able to be created successfully.' ),
					422
				);
			}

			$this->log_info( 'WP related User ID: ' . $user->ID . '; Email: ' . $user->user_email );

			return $user;
		}

		/**
		 * Finds products or sends a json error on fail.
		 *
		 * @since 4.5.0
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return Product[]
		 */
		private function setup_products_or_fail( int $post_id ): array {
			if ( $post_id <= 0 ) {
				$this->log_error( 'Event notes validation failed. Missing key "post_id" in notes.' );

				wp_send_json_error(
					new WP_Error(
						'bad_request',
						'Event notes validation failed. Missing key "post_id" in notes.'
					),
					422
				);
			}

			$products = Product::find_many( array( $post_id ) );

			if ( empty( $products ) ) {
				$this->log_error( 'No related products found.' );

				wp_send_json_error(
					new WP_Error(
						'bad_request',
						sprintf( 'Product validation failed. Product with the ID %d was not found.', $post_id )
					),
					422
				);
			}

			$this->log_info( 'Products found: ' . count( $products ) );
			$this->log_info(
				'Products IDs: ' . array_reduce(
					$products,
					function ( string $carry, Product $product ): string {
						return $carry . $product->get_id() . ', ';
					},
					''
				)
			);

			return $products;
		}

		/**
		 * Processes the webhook event.
		 *
		 * @since 4.5.0
		 *
		 * @param array     $event    Event.
		 * @param Product[] $products Products.
		 * @param WP_User   $user     User.
		 *
		 * @phpstan-param array{
		 *     event: string,
		 *     contains: array<string>,
		 *     payload: array{id: string, notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string}}
		 * } $event
		 *
		 * @return void
		 */
		private function process_webhook_event( array $event, array $products, WP_User $user ): void {
			$processed = true;

			switch ( $event['event'] ) {
				case self::EVENT_ORDER_PAID:
				case self::EVENT_SUBSCRIPTION_AUTHENTICATED:
					$this->add_access_to_products( $products, $user );
					$this->log_info( 'Added access to products.' );

					foreach ( $products as $product ) {
						try {
							$this->record_transaction(
								$this->map_transaction_meta( $event, $product )->to_array(),
								$product->get_post(),
								$user
							);

							$this->log_info( 'Recorded transaction for product ID: ' . $product->get_id() );
						} catch ( Learndash_DTO_Validation_Exception $e ) {
							$this->log_error( 'Error recording transaction: ' . $e->getMessage() );
							exit;
						}
					}
					break;

				case self::EVENT_SUBSCRIPTION_ACTIVATED:
				case self::EVENT_SUBSCRIPTION_RESUMED:
					$this->add_access_to_products( $products, $user );
					$this->log_info( 'Added access to products.' );
					break;

				case self::EVENT_SUBSCRIPTION_COMPLETED:
				case self::EVENT_SUBSCRIPTION_PENDING:
				case self::EVENT_SUBSCRIPTION_HALTED:
				case self::EVENT_SUBSCRIPTION_CANCELLED:
				case self::EVENT_SUBSCRIPTION_PAUSED:
					$this->remove_access_to_products( $products, $user );
					$this->log_info( 'Removed access to products.' );
					break;

				default:
					$processed = false;
			}

			$this->finish_webhook_processing( $event, $processed );
		}

		/**
		 * Validates the webhook event and returns an event data.
		 *
		 * @since 4.5.0
		 *
		 * @return array{
		 *     event: string,
		 *     contains: string[],
		 *     payload: array{
		 *         id: string,
		 *         notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, user_id?: int, operation_id?: string},
		 *         order?: array{entity: array<mixed>},
		 *         subscription?: array{entity: array<mixed>},
		 *     }
		 * }
		 */
		private function validate_webhook_event_or_fail(): array {
			if ( ! isset( $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ) ) {
				$this->log_error( 'Razorpay webhook signature was not found.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Missing Razorpay signature header.' ),
					400
				);
			}

			$payload = file_get_contents( 'php://input' );

			if ( empty( $payload ) ) {
				$this->log_error( 'Empty payload.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Empty JSON body.' ),
					400
				);
			}

			/**
			 * Event.
			 *
			 * @var array{event: string, contains: string[], payload: array{id: string, notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string}}} $event
			 */
			$event = json_decode( $payload, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $event ) ) {
				$this->log_error( 'Invalid payload.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Invalid JSON.' ),
					400
				);
			}

			if ( $this->maybe_ignore_event( $event ) ) {
				$this->finish_webhook_processing( $event, false );
			}

			try {
				$this->api->utility->verifyWebhookSignature( // @phpstan-ignore-line -- Property access via magic method.
					$payload,
					sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ) ),
					$this->webhook_secret
				);
			} catch ( SignatureVerificationError $e ) {
				wp_send_json_error(
					new WP_Error( 'bad_request', 'Invalid Razorpay signature.' ),
					400
				);
			}

			$this->log_info( 'Event type: ' . $event['event'] );

			$entity = $this->get_main_entity_from_event( $event );

			if ( empty( $entity ) ) {
				$this->log_error( 'Webhook event entity validation failed. No subscription or order entity found.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event entity validation failed. No subscription or order entity found.' ),
					422
				);
			}

			if ( empty( $entity['notes']['is_learndash'] ) ) {
				$this->log_error( 'Webhook event notes validation failed. Missing key "is_learndash" in notes. This event was not created by LearnDash.' );

				wp_send_json_error(
					new WP_Error(
						'bad_request',
						'Event notes validation failed. Missing key "is_learndash" in notes. This event was not created by LearnDash.'
					),
					422
				);
			}

			/**
			 * Event.
			 *
			 * @var array{
			 *     event: string,
			 *     contains: string[],
			 *     payload: array{id: string, notes: array{is_learndash?: bool, learndash_version?: string, post_id?: int, operation_id?: string}}
			 * } $event
			 */
			return $event;
		}
	}
}
