<?php
/**
 * This class handles Stripe Connect integration.
 *
 * @since   4.5.0
 * @package LearnDash
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\App;
use LearnDash\Core\Payments;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Subscription;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Stripe_Gateway' ) && class_exists( 'Learndash_Payment_Gateway' ) ) {
	/**
	 * Stripe Connect gateway class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Stripe_Gateway extends Learndash_Payment_Gateway {
		private const GATEWAY_NAME = 'stripe_connect';

		private const PERIOD_HASH = array(
			'D' => 'day',
			'W' => 'week',
			'M' => 'month',
			'Y' => 'year',
		);

		private const PLANS_META_KEY = 'stripe_plan_id';

		private const EVENT_CHECKOUT_SESSION_COMPLETED    = 'checkout.session.completed';
		private const EVENT_INVOICE_PAYMENT_SUCCEEDED     = 'invoice.payment_succeeded';
		private const EVENT_INVOICE_PAYMENT_FAILED        = 'invoice.payment_failed';
		private const EVENT_CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';
		private const EVENT_COUPON_DELETED = 'coupon.deleted';

		/**
		 * Stripe secret key.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $secret_key;

		/**
		 * Stripe publishable key.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $publishable_key;

		/**
		 * Stripe connected account id.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $account_id;

		/**
		 * Stripe API client.
		 *
		 * @since 4.5.0
		 *
		 * @var StripeClient
		 */
		private $api;

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
				$subscription = $this->api->subscriptions->cancel( $subscription_id );

				return 'canceled' === $subscription->status;
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
			return esc_html__( 'Stripe Connect', 'learndash' );
		}

		/**
		 * Adds hooks.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function add_extra_hooks(): void {
			add_action( 'wp_footer', array( $this, 'show_successful_message' ) );
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
			wp_enqueue_script(
				'stripe-connect',
				'https://js.stripe.com/v3/',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
		}

		/**
		 * Creates a payment session in Stripe.
		 *
		 * @since 4.5.0
		 *
		 * @return void Json response.
		 */
		public function setup_payment(): void {
			if (
				empty( $_POST['post_id'] ) ||
				! isset( $_POST['nonce'] ) ||
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
						'message' => esc_html__( 'Product not found.', 'learndash' ),
					)
				);
			}

			try {
				$session_id = $this->create_payment_session( $product );
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Stripe session was not created.', 'learndash' ) . ' ' . esc_html( $e->getMessage() ),
					)
				);
			}

			wp_send_json_success(
				array(
					'session_id' => $session_id,
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

			return $enabled && ! empty( $this->account_id ) && ! empty( $this->secret_key ) && ! empty( $this->publishable_key );
		}

		/**
		 * Configures gateway.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		protected function configure(): void {
			$this->settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_Stripe_Connect' );

			$this->currency_code = mb_strtolower( $this->currency_code );

			$this->customer_id_meta_key = $this->is_test_mode()
				? LearnDash_Settings_Section_Stripe_Connect::STRIPE_CUSTOMER_ID_META_KEY_TEST
				: LearnDash_Settings_Section_Stripe_Connect::STRIPE_CUSTOMER_ID_META_KEY;
			$this->secret_key           = $this->map_secret_key();
			$this->publishable_key      = $this->map_publishable_key();
			$this->account_id           = $this->settings['account_id'] ?? '';

			if ( ! class_exists( 'Stripe\Stripe' ) ) {
				require_once LEARNDASH_LMS_LIBRARY_DIR . '/stripe-php/init.php';
			}

			if ( ! empty( $this->secret_key ) ) {
				Stripe::setApiKey( $this->secret_key );

				$this->api = new StripeClient( $this->secret_key );
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
				__( 'Use Credit Card', 'learndash' ),
				$post
			);

			$button  = '<form class="' . esc_attr( $this->get_form_class_name() ) . '" name="" action="" method="post">';
			$button .= '<input type="hidden" name="post_id" value="' . esc_attr( (string) $post->ID ) . '" />';
			$button .= '<input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( $this->get_nonce_name() ) ) . '" />';
			$button .= '<input type="hidden" name="action" value="' . esc_attr( $this->get_ajax_action_name_setup() ) . '" />';
			$button .= '<input class="' . esc_attr( Learndash_Payment_Button::map_button_class_name() ) . '" id="' . esc_attr( Learndash_Payment_Button::map_button_id() ) . '" type="submit" value="' . esc_attr( $button_label ) . '">';
			$button .= '</form>';

			ob_start();
			$this->print_button_scripts();
			$button .= ob_get_clean();

			return $button;
		}

		/**
		 * Prints button scripts.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		private function print_button_scripts(): void {
			?>
			<script>
				"use strict";

				function ownKeys(object, enumerableOnly) {
					var keys = Object.keys(object);
					if (Object.getOwnPropertySymbols) {
						var symbols = Object.getOwnPropertySymbols(object);
						if (enumerableOnly) symbols = symbols.filter(function (sym) {
							return Object.getOwnPropertyDescriptor(object, sym).enumerable;
						});
						keys.push.apply(keys, symbols);
					}
					return keys;
				}

				function _objectSpread(target) {
					for (var i = 1; i < arguments.length; i++) {
						var source = arguments[i] != null ? arguments[i] : {};
						if (i % 2) {
							ownKeys(Object(source), true).forEach(function (key) {
								_defineProperty(target, key, source[key]);
							});
						} else if (Object.getOwnPropertyDescriptors) {
							Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
						} else {
							ownKeys(Object(source)).forEach(function (key) {
								Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
							});
						}
					}
					return target;
				}

				function _defineProperty(obj, key, value) {
					if (key in obj) {
						Object.defineProperty(obj, key, {
							value: value,
							enumerable: true,
							configurable: true,
							writable: true
						});
					} else {
						obj[key] = value;
					}
					return obj;
				}

				jQuery(document).ready(function ($) {
					var stripe = Stripe( '<?php echo esc_attr( $this->publishable_key ); ?>', {
						'stripeAccount': '<?php echo esc_attr( $this->account_id ); ?>'
					} );

					$(document).on('submit', '.<?php echo esc_attr( $this->get_form_class_name() ); ?>', function (e) {
						e.preventDefault();
						var inputs = $(this).serializeArray();
						inputs = inputs.reduce(function (new_inputs, value, index, inputs) {
							new_inputs[value.name] = value.value;
							return new_inputs;
						}, {});

						$('.checkout-dropdown-button').hide();
						$(this).closest('.learndash_checkout_buttons').addClass('ld-loading');
						$('head').append('<style class="ld-stripe-css">' + '.ld-loading::after { background: none !important; }' + '.ld-loading::before { width: 30px !important; height: 30px !important; left: 53% !important; top: 62% !important; }' + '</style>');
						$('.learndash_checkout_button').css({
							backgroundColor: 'rgba(182, 182, 182, 0.1)'
						});

						// Set Stripe session
						$.ajax({
							url: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
							type: 'POST',
							dataType: 'json',
							data: _objectSpread({}, inputs)
						}).done(function (response) {
							if ( response.success ) {
								stripe.redirectToCheckout({
									sessionId: response.data.session_id
								}).then(function ( result ) {
									if ( result.error.length > 0 ) {
										alert( result.error );
									}
								});
							} else {
								alert( response.data.message );
							}

							$('.learndash_checkout_buttons').removeClass('ld-loading');
							$('style.ld-stripe-css').remove();
							$('.learndash_checkout_button').css({
								backgroundColor: ''
							});
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * Returns the coupon ID.
		 *
		 * @since 4.6.0
		 *
		 * @return string
		 */
		public function create_fake_coupon_for_webhook_test(): string {
			$coupon_id = uniqid();

			try {
				// Create a coupon to test the webhook and delete it immediately.
				$this->api->coupons
					->create(
						[
							'id'              => $coupon_id, // Create a unique key to avoid guessing by brute forcing during that second.
							'redeem_by'       => time() + 1, // Limit the coupon to one second. In a second, the coupon will be expired.
							'max_redemptions' => 1, // Limit the coupon to one use.
							'percent_off'     => 1, // Limit the coupon to one percent.
						]
					)
					->delete();
			} catch ( ApiErrorException $e ) {
				return $coupon_id;
			}

			return $coupon_id;
		}

		/**
		 * Handles the webhook.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function process_webhook(): void {
			if (
				! isset( $_GET['learndash-integration'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				(
					$this->get_name() !== $_GET['learndash-integration'] && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'stripe-connect' !== $_GET['learndash-integration'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Legacy integration name.
				)
			) {
				return;
			}

			$this->log_info( 'Webhook received.' );

			$event = $this->validate_webhook_event_or_fail();

			$entity = $event->data->object; // @phpstan-ignore-line -- Property access via magic method.

			$customer = $this->get_stripe_customer_or_fail( $entity );
			$user     = $this->setup_user_or_fail( $entity, $customer );
			$products = $this->setup_products_or_fail( $entity );

			$this->process_webhook_event( $event, $products, $user );
		}

		/**
		 * Processes the webhook event.
		 *
		 * @since 4.5.0
		 *
		 * @param Event     $event    Event.
		 * @param Product[] $products Products.
		 * @param WP_User   $user     User.
		 *
		 * @return void
		 */
		private function process_webhook_event( Event $event, array $products, WP_User $user ): void {
			$processed = true;

			$entity = $event->data->object; // @phpstan-ignore-line -- Property access via magic method.

			switch ( $event->type ) {
				case self::EVENT_CHECKOUT_SESSION_COMPLETED:
					$this->add_access_to_products( $products, $user );
					$this->log_info( 'Added access to products.' );

					foreach ( $products as $product ) {
						try {
							$this->record_transaction(
								$this->map_transaction_meta( $entity, $product )->to_array(),
								$product->get_post(),
								$user
							);

							$this->log_info( 'Recorded transaction for product ID: ' . $product->get_id() );
						} catch ( Learndash_DTO_Validation_Exception $e ) {
							$this->log_error( 'Error recording transaction: ' . $e->getMessage() );

							wp_send_json_error(
								array( 'message' => 'Error recording transaction.' ),
								500
							);
						}
					}
					break;

				case self::EVENT_INVOICE_PAYMENT_SUCCEEDED:
					$this->add_access_to_products( $products, $user );
					$this->log_info( 'Added access to products.' );

					if ( empty( $entity->subscription ) ) {
						$this->log_info( 'No subscription found in the Stripe session data. Nothing to do.' );
						break;
					}

					// retrieve the subscription data.
					try {
						$subscription = $this->api->subscriptions->retrieve( $entity->subscription );
					} catch ( Exception $e ) {
						$this->log_error( 'Error retrieving Stripe subscription: ' . $e->getMessage() );

						wp_send_json_error(
							array( 'message' => 'Error retrieving Stripe subscription.' ),
							500
						);
					}
					$this->log_info( 'Subscription ID: ' . $subscription->id . '; Status: ' . $subscription->status );

					$subscription_meta = $subscription->metadata->toArray();
					if ( ! isset( $subscription_meta[ Transaction::$meta_key_pricing_info ] ) ) {
						$this->log_error( 'No pricing info found in the Stripe subscription data.' );

						wp_send_json_error(
							array( 'message' => 'No pricing info found in the Stripe subscription data.' ),
							500
						);
					}

					$subscription_meta[ Transaction::$meta_key_pricing_info ] = json_decode(
						$subscription_meta[ Transaction::$meta_key_pricing_info ],
						true
					);

					try {
						$transaction_meta_dto = Learndash_Transaction_Meta_DTO::create( $subscription_meta );
					} catch ( Exception $e ) {
						$this->log_error( 'Error validating transaction meta: ' . $e->getMessage() );

						wp_send_json_error(
							array( 'message' => 'Error validating transaction meta: ' . $e->getMessage() ),
							500
						);
					}

					// Cancel user subscription if recurring limit is set.
					if ( $transaction_meta_dto->pricing_info->recurring_times > 0 ) {
						try {
							$invoices = $this->api->invoices->all(
								array(
									'status'       => 'paid',
									'customer'     => $entity->customer, // @phpstan-ignore-line -- Property access via magic method.
									'subscription' => $entity->subscription,
								)
							);
						} catch ( Exception $e ) {
							$this->log_error( 'Error retrieving Stripe invoices: ' . $e->getMessage() );

							wp_send_json_error(
								array( 'message' => 'Error retrieving Stripe invoices.' ),
								500
							);
						}

						$payments_count = count( $invoices->data );
						if ( $transaction_meta_dto->has_trial ) {
							$payments_count--;
						}

						if ( $transaction_meta_dto->pricing_info->recurring_times === $payments_count ) {
							try {
								$this->api->subscriptions->update(
									$entity->subscription,
									array( 'cancel_at_period_end' => true )
								);
							} catch ( Exception $e ) {
								$this->log_error( 'Error cancelling Stripe subscription: ' . $e->getMessage() );

								wp_send_json_error(
									array( 'message' => 'Error cancelling Stripe subscription.' ),
									500
								);
							}
							$this->log_info( 'Subscription cancelled.' );
						}
					}
					break;

				case self::EVENT_INVOICE_PAYMENT_FAILED:
					$this->remove_access_to_products( $products, $user );
					$this->log_info( 'Removed access to products.' );
					break;

				case self::EVENT_CUSTOMER_SUBSCRIPTION_DELETED:
					foreach ( $products as $index => $product ) {
						if ( isset( $entity->metadata->has_recurring_limit ) && $entity->metadata->has_recurring_limit ) {
							/**
							 * Filters whether to remove a user's post access if a recurring limit is applied.
							 *
							 * @since 4.0.0
							 *
							 * @param bool $remove  Whether to remove post access or not. Default false.
							 * @param int  $post_id Post ID.
							 * @param int  $user_id User ID.
							 *
							 * @return bool True to remove, otherwise false.
							 */
							$remove_post_access = (bool) apply_filters(
								'learndash_stripe_remove_user_course_access_on_recurring_limit',
								false,
								$product->get_id(),
								$this->user->ID
							);

							if ( ! $remove_post_access ) {
								unset( $products[ $index ] );
							}
						}
					}

					$this->remove_access_to_products( $products, $user );
					$this->log_info( 'Removed access to products.' );

					break;

				default:
					$processed = false;
					break;
			}

			$this->finish_webhook_processing( $event->toArray(), $processed );
		}

		/**
		 * Returns the subscription parameters for a legacy event.
		 *
		 * @since 4.5.0
		 *
		 * @param Product $product Product.
		 *
		 * @throws InvalidArgumentException Should not be thrown because we process all product types.
		 *
		 * @return array{
		 *     duration_value: mixed,
		 *     duration_length: mixed,
		 *     recurring_times: mixed,
		 *     trial_price: string,
		 *     trial_duration_value: mixed,
		 *     trial_duration_length: string,
		 * } Subscription parameters.
		 */
		private function get_legacy_subscription_params( Product $product ): array {
			$product_post = $product->get_post();

			switch ( $product_post->post_type ) {
				case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ):
					$meta_suffix = 'course';
					break;

				case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ):
					$meta_suffix = 'group';
					break;

				default:
					throw new InvalidArgumentException( 'Invalid product type.' );
			}

			$post_settings = learndash_get_setting( $product_post );

			if ( ! is_array( $post_settings ) ) {
				$post_settings = array();
			}

			return array(
				'duration_value'        => get_post_meta( $product_post->ID, "${meta_suffix}_price_billing_p3", true ),
				'duration_length'       => get_post_meta( $product_post->ID, "${meta_suffix}_price_billing_t3", true ),
				'recurring_times'       => $post_settings[ "${meta_suffix}_no_of_cycles" ] ?? '',
				'trial_price'           => $post_settings[ "${meta_suffix}_trial_price" ] ?? '',
				'trial_duration_value'  => $post_settings[ "${meta_suffix}_trial_duration_p1" ] ?? '',
				'trial_duration_length' => $post_settings[ "${meta_suffix}_trial_duration_t1" ] ?? '',
			);
		}

		/**
		 * Processes legacy meta, converts keys to the new format.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity                Entity.
		 * @param array<mixed>         $event_meta            Event meta.
		 * @param bool                 $is_subscription_event True if event contains subscription.
		 * @param string               $learndash_version     LearnDash version that was used to create the event. Can be empty for older versions.
		 * @param Product              $product               Product.
		 *
		 * @return array<mixed>
		 */
		private function process_legacy_meta(
			$entity,
			array $event_meta,
			bool $is_subscription_event,
			string $learndash_version,
			Product $product
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

				if ( ! isset( $event_meta[ Transaction::$meta_key_pricing_info ] ) ) {
					$event_meta[ Transaction::$meta_key_pricing_info ] = array(
						'currency' => $entity['currency'] ?? '',
						'price'    => $entity['amount_total'] ?? '',
					);

					if ( $is_subscription_event ) {
						$legacy_subscription_params = $this->get_legacy_subscription_params( $product );

						$event_meta[ Transaction::$meta_key_pricing_info ] = array_merge(
							$event_meta[ Transaction::$meta_key_pricing_info ],
							$legacy_subscription_params
						);

						// Trial fields.

						$trial_duration_in_days = $this->map_trial_duration_in_days(
							intval( $legacy_subscription_params['trial_duration_value'] ),
							$legacy_subscription_params['trial_duration_length']
						);

						$has_trial      = $trial_duration_in_days > 0;
						$has_free_trial = $has_trial && 0. === learndash_get_price_as_float( $legacy_subscription_params['trial_price'] );

						$event_meta[ Transaction::$meta_key_has_trial ]      = $has_trial;
						$event_meta[ Transaction::$meta_key_has_free_trial ] = $has_free_trial;
					}

					// Encode to decode later, just for compatibility with the new code.
					$event_meta[ Transaction::$meta_key_pricing_info ] = wp_json_encode(
						$event_meta[ Transaction::$meta_key_pricing_info ]
					);
				}
			}

			return $event_meta;
		}

		/**
		 * Checks if the Stripe Session contains legacy data.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity The Stripe object.
		 *
		 * @return bool True if is legacy data, otherwise false.
		 */
		private function is_legacy_data( $entity ): bool {
			return empty( $entity->metadata->is_learndash );
		}

		/**
		 * Creates/finds a user or sends a json error on fail.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity The Stripe object.
		 * @param Customer             $customer The Stripe customer.
		 *
		 * @return WP_User
		 */
		private function setup_user_or_fail( $entity, Customer $customer ): WP_User {
			$user = $this->find_or_create_user(
				(int) ( $entity->metadata->user_id ?? 0 ),
				(string) $customer->email,
				$customer->id
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
		 * Returns the Stripe customer or sends a json error on fail.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity The Stripe object.
		 *
		 * @return Customer
		 */
		private function get_stripe_customer_or_fail( $entity ): Customer {
			if ( empty( $entity->customer ) ) {
				$this->log_error( 'No customer found in the Stripe session data.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event validation failed. Customer is not present in the event.' ),
					422
				);
			}

			try {
				$customer = $this->api->customers->retrieve( $entity->customer );

				$this->log_info( 'Customer ID: ' . $customer->id . '; Email: ' . $customer->email );
			} catch ( Exception $e ) {
				$this->log_error( 'Error retrieving Stripe customer: ' . $e->getMessage() );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event validation failed. Customer could not be retrieved from Stripe.' ),
					422
				);
			}

			return $customer;
		}

		/**
		 * Finds products or sends a json error on fail.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity The Stripe object.
		 *
		 * @return Product[]
		 */
		private function setup_products_or_fail( $entity ): array {
			$products = array();
			$post_ids = array();

			$product_meta_key = $this->is_legacy_data( $entity ) ? 'course_id' : 'post_id';

			if ( ! empty( $entity->lines->data ) ) {
				foreach ( $entity->lines->data as $item ) { // @phpstan-ignore-line -- Property accessed via magic method.
					$post_ids[] = (int) ( $item->metadata[ $product_meta_key ] ?? $this->get_post_id_by_plan_id( $item->plan->id ?? '' ) );
				}

				$products = Product::find_many(
					array_filter( $post_ids )
				);
			} else {
				$post_id    = (int) ( $entity->metadata[ $product_meta_key ] ?? 0 );
				$post_ids[] = $post_id;
				$product    = Product::find( $post_id );

				if ( $product ) {
					$products = array( $product );
				}
			}

			if ( empty( $products ) ) {
				$this->log_error( 'No related products found with ID(s): ' . implode( ', ', $post_ids ) );

				wp_send_json_error(
					new WP_Error(
						'bad_request',
						sprintf( 'Product validation failed. Product was not found.' )
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
		 * Validates the webhook event and returns an event data.
		 *
		 * @since 4.5.0
		 *
		 * @return Event
		 */
		private function validate_webhook_event_or_fail(): Event {
			$payload = file_get_contents( 'php://input' );

			if ( empty( $payload ) ) {
				$this->log_error( 'Empty payload.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Empty JSON body.' ),
					400
				);
			}

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

			if ( ! isset( $event['type'] ) ) {
				$this->log_error( 'Event validation failed. No type field.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event validation failed. No type field.' ),
					422
				);
			}

			$this->log_info( 'Event type: ' . $event['type'] );

			if ( ! isset( $event['id'] ) ) {
				$this->log_error( 'Event validation failed. No ID field.' );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event validation failed. No ID field.' ),
					422
				);
			}

			try {
				$event = $this->api->events->retrieve( $event['id'] );
			} catch ( Exception $e ) {
				$this->log_error( 'Error retrieving Stripe event: ' . $e->getMessage() );

				wp_send_json_error(
					new WP_Error( 'bad_request', 'Event validation failed. The event could not be retrieved from Stripe.' ),
					422
				);
			}

			if ( self::EVENT_COUPON_DELETED === $event->type ) {
				try {
					/**
					 * Webhook setup validator.
					 *
					 * @var Payments\Stripe\Webhook_Setup_Validator $webhook_setup_validator Webhook setup validator.
					 */
					$webhook_setup_validator = App::get( Payments\Stripe\Webhook_Setup_Validator::class );

					// @phpstan-ignore-next-line -- Property access via magic method.
					$marked = $webhook_setup_validator->mark_successful( $event->data->object->id );

					if ( $marked ) {
						$this->log_info( 'Webhook setup validated.' );

						$this->finish_webhook_processing( $event->toArray(), true );
					}
				} catch ( ContainerException $e ) {
					$this->log_info( 'Webhook setup could not be validated because Webhook_Setup_Validator could not be initiated.' );
				}
			}

			return $event;
		}

		/**
		 * Get post ID by Stripe plan ID.
		 *
		 * @since 4.5.0
		 *
		 * @param string $plan_id Stripe plan ID.
		 *
		 * @return int Post ID or 0.
		 */
		private function get_post_id_by_plan_id( string $plan_id ): int {
			if ( empty( $plan_id ) ) {
				return 0;
			}

			global $wpdb;

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = %s AND meta_value = %s",
					self::PLANS_META_KEY,
					$plan_id
				)
			);
		}

		/**
		 * Runs JS alert with a successful transaction message.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function show_successful_message(): void {
			if ( empty( $_GET['ld_stripe_connect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			if ( 'success' !== $_GET['ld_stripe_connect'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$message = is_user_logged_in()
				? __( 'Your transaction was successful.', 'learndash' )
				: __( 'Your transaction was successful. Please log in to access your content.', 'learndash' );
			?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					alert('<?php echo esc_html( $message ); ?>');
				});
			</script>
			<?php
		}

		/**
		 * Maps transaction meta.
		 *
		 * @since 4.5.0
		 *
		 * @param Invoice|Subscription $entity  Transaction data.
		 * @param Product              $product Product.
		 *
		 * @throws Learndash_DTO_Validation_Exception Transaction data validation exception.
		 *
		 * @return Learndash_Transaction_Meta_DTO
		 */
		protected function map_transaction_meta( $entity, Product $product ): Learndash_Transaction_Meta_DTO {
			$is_subscription = 'subscription' === $entity->mode; // @phpstan-ignore-line -- Property access via magic method.

			$meta = array_merge(
				$entity->metadata ? $entity->metadata->toArray() : array(),
				array(
					Transaction::$meta_key_gateway_transaction => Learndash_Transaction_Gateway_Transaction_DTO::create(
						array(
							'id'    => $is_subscription ? $entity->subscription : $entity->payment_intent, // @phpstan-ignore-line -- Property access via magic method.
							'event' => $entity,
						)
					),
				)
			);

			$meta = $this->process_legacy_meta(
				$entity,
				$meta,
				$is_subscription,
				$entity->metadata->learndash_version ?? '',
				$product
			);

			// It was encoded to allow arrays in the metadata.
			if ( is_string( $meta[ Transaction::$meta_key_pricing_info ] ) ) {
				$meta[ Transaction::$meta_key_pricing_info ] = json_decode(
					$meta[ Transaction::$meta_key_pricing_info ],
					true
				);
			}

			return Learndash_Transaction_Meta_DTO::create( $meta );
		}

		/**
		 * Maps the secret key.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function map_secret_key(): string {
			if ( $this->is_test_mode() && ! empty( $this->settings['secret_key_test'] ) ) {
				return strval( $this->settings['secret_key_test'] );
			}

			if ( ! $this->is_test_mode() && ! empty( $this->settings['secret_key_live'] ) ) {
				return strval( $this->settings['secret_key_live'] );
			}

			return '';
		}

		/**
		 * Maps the publishable key.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function map_publishable_key(): string {
			if ( $this->is_test_mode() && ! empty( $this->settings['publishable_key_test'] ) ) {
				return strval( $this->settings['publishable_key_test'] );
			}

			if ( ! $this->is_test_mode() && ! empty( $this->settings['publishable_key_live'] ) ) {
				return strval( $this->settings['publishable_key_live'] );
			}

			return '';
		}

		/**
		 * Returns enabled payment methods.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string>
		 */
		private function get_payment_methods(): array {
			/**
			 * Payment methods.
			 *
			 * @var array<string> $enabled_payment_methods
			 */
			$enabled_payment_methods = ! empty( $this->settings['payment_methods'] )
				? $this->settings['payment_methods']
				: array( 'card' );

			/**
			 * Filters enabled Stripe payment methods.
			 *
			 * @since 4.0.0
			 *
			 * @param array<string> $enabled_payment_methods Enabled Stripe payment methods.
			 *
			 * @return array Stripe payment methods.
			 */
			return apply_filters( 'learndash_stripe_payment_method_types', $enabled_payment_methods );
		}

		/**
		 * Checks if Stripe currency ISO code is zero decimal currency.
		 *
		 * @since 4.5.0
		 *
		 * @param string $currency_code Stripe currency ISO code.
		 *
		 * @return bool
		 */
		private function is_zero_decimal_currency( string $currency_code ): bool {
			return in_array(
				mb_strtoupper( $currency_code ),
				array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' ),
				true
			);
		}

		/**
		 * Get the price in cents, if the currency is not zero decimal.
		 *
		 * @param float $price The Price.
		 *
		 * @return int
		 */
		private function get_price_as_stripe( float $price ): int {
			return $this->is_zero_decimal_currency( $this->currency_code )
				? (int) $price
				: $this->get_price_in_subunits( $price );
		}

		/**
		 * Returns the order data for the session.
		 *
		 * @since 4.5.0
		 *
		 * @param float   $amount  Amount.
		 * @param Product $product Product.
		 *
		 * @throws Learndash_DTO_Validation_Exception If order creation fails.
		 *
		 * @return array{metadata: array<mixed>, items: array<mixed>, payment_data: array<mixed>}
		 */
		private function get_order_data( float $amount, Product $product ): array {
			$transaction_meta_dto = Learndash_Transaction_Meta_DTO::create(
				array(
					Transaction::$meta_key_gateway_name => $this::get_name(),
					Transaction::$meta_key_price_type   => LEARNDASH_PRICE_TYPE_PAYNOW,
					Transaction::$meta_key_pricing_info => Learndash_Pricing_DTO::create(
						array(
							'currency' => mb_strtoupper( $this->currency_code ),
							'price'    => $amount,
						)
					),
				)
			);

			$item_image = $this->get_image_url( array( $product ) );
			$items      = array(
				array(
					'name'     => $this->map_description( array( $product ) ),
					'amount'   => $this->get_price_as_stripe( $amount ),
					'currency' => $this->currency_code,
					'quantity' => 1,
					'images'   => ! empty( $item_image ) ? array( $item_image ) : array(),
				),
			);

			$metadata = array_merge(
				array(
					'is_learndash'      => true,
					'learndash_version' => LEARNDASH_VERSION,
					'post_id'           => $product->get_id(),
					'user_id'           => $this->user->ID,
				),
				array_map(
					function ( $value ) {
						return is_array( $value ) ? wp_json_encode( $value ) : $value;
					},
					$transaction_meta_dto->to_array()
				)
			);

			$payment_intent_data = array_filter(
				array(
					'metadata'      => $metadata,
					'receipt_email' => ! empty( $this->user->user_email ) ? $this->user->user_email : null,
					'description'   => $this->map_description( array( $product ) ),
				)
			);

			return array(
				'metadata'     => $metadata,
				'items'        => $items,
				'payment_data' => $payment_intent_data,
			);
		}

		/**
		 * Returns the subscription data for the session.
		 *
		 * @since 4.5.0
		 *
		 * @param float                 $amount  Amount.
		 * @param Learndash_Pricing_DTO $pricing Pricing DTO.
		 * @param Product               $product Product.
		 *
		 * @throws Exception Exception.
		 *
		 * @return array{metadata: array<mixed>, items: array<mixed>|null, payment_data: array<mixed>}
		 */
		private function get_subscription_data( float $amount, Learndash_Pricing_DTO $pricing, Product $product ): array {
			if ( empty( $pricing->duration_length ) ) {
				throw new Exception( __( 'The Billing Cycle Interval value must be set.', 'learndash' ) );
			} elseif ( 0 === $pricing->duration_value ) {
				throw new Exception( __( 'The minimum Billing Cycle value is 1.', 'learndash' ) );
			}

			$trial_duration_in_days = $this->map_trial_duration_in_days(
				$pricing->trial_duration_value,
				$pricing->trial_duration_length
			);

			$has_trial          = $trial_duration_in_days > 0;
			$course_trial_price = $has_trial ? $pricing->trial_price : 0.;

			$transaction_meta_dto = Learndash_Transaction_Meta_DTO::create(
				array(
					Transaction::$meta_key_gateway_name   => $this::get_name(),
					Transaction::$meta_key_price_type     => LEARNDASH_PRICE_TYPE_SUBSCRIBE,
					Transaction::$meta_key_pricing_info   => $pricing,
					Transaction::$meta_key_has_trial      => $has_trial,
					Transaction::$meta_key_has_free_trial => $has_trial && 0. === $course_trial_price,
				)
			);

			$items = null;
			if ( ! $transaction_meta_dto->has_free_trial ) {
				$items = array(
					array(
						'name'     => sprintf(
							// Translators: number of days.
							_n( '%d Day Trial', '%d Days Trial', $trial_duration_in_days, 'learndash' ),
							$trial_duration_in_days
						),
						'amount'   => $this->get_price_as_stripe( $course_trial_price ),
						'currency' => $this->currency_code,
						'quantity' => 1,
					),
				);
			}

			$metadata = array_merge(
				array(
					'is_learndash'      => true,
					'learndash_version' => LEARNDASH_VERSION,
					'post_id'           => $product->get_id(),
					'user_id'           => $this->user->ID,
				),
				array_map(
					function ( $value ) {
						return is_array( $value ) ? wp_json_encode( $value ) : $value;
					},
					$transaction_meta_dto->to_array()
				)
			);

			$subscription_data = array_filter(
				array(
					'metadata'          => $metadata,
					'items'             => array(
						array(
							'plan' => $this->get_plan_id( $amount, $pricing, $product ),
						),
					),
					'trial_period_days' => $trial_duration_in_days > 0 ? $trial_duration_in_days : null,
					'description'       => $this->map_description( array( $product ) ),
				)
			);

			return array(
				'metadata'     => $metadata,
				'items'        => $items,
				'payment_data' => $subscription_data,
			);
		}

		/**
		 * Sets Stripe checkout session on a course page.
		 *
		 * @since 4.5.0
		 *
		 * @param Product $product Product.
		 *
		 * @throws Learndash_DTO_Validation_Exception|ApiErrorException|InvalidArgumentException Invalid pricing or validation or API error.
		 *
		 * @return string Stripe session ID.
		 */
		private function create_payment_session( Product $product ): string {
			$product_pricing = $product->get_pricing( $this->user );

			/**
			 * Filters course/group price.
			 *
			 * @since 4.1.0
			 *
			 * @param float    $price   Course/Group Price.
			 * @param int      $post_id Course/Group ID.
			 * @param int|null $user_id User ID.
			 */
			$course_price = apply_filters( 'learndash_get_price_by_coupon', $product_pricing->price, $product->get_id(), $this->user->ID );

			$payment_intent_data = null;
			$subscription_data   = null;

			if ( $product->is_price_type_paynow() ) {
				$payment_intent_data = $this->get_order_data( $course_price, $product );
			} elseif ( $product->is_price_type_subscribe() ) {
				$subscription_data = $this->get_subscription_data( $course_price, $product_pricing, $product );
			}

			$success_url = add_query_arg(
				array(
					'ld_stripe_connect' => 'success',
					'session_id'        => '{CHECKOUT_SESSION_ID}',
				),
				$this->get_url_success(
					array( $product ),
					strval( $this->settings['return_url'] ?? '' )
				)
			);

			/**
			 * Filters Stripe session arguments before creation.
			 *
			 * @since 4.0.0
			 *
			 * @param array $session_args Stripe session arguments.
			 *
			 * @return array Stripe session arguments.
			 */
			$session_args = apply_filters(
				'learndash_stripe_session_args',
				array_filter(
					array(
						'allow_promotion_codes' => true,
						'payment_method_types'  => $this->get_payment_methods(),
						'line_items'            => $payment_intent_data ? $payment_intent_data['items'] : $subscription_data['items'] ?? null,
						'metadata'              => $payment_intent_data ? $payment_intent_data['metadata'] : $subscription_data['metadata'] ?? null,
						'payment_intent_data'   => $payment_intent_data ? $payment_intent_data['payment_data'] : null,
						'subscription_data'     => $subscription_data ? $subscription_data['payment_data'] : null,
						'customer'              => $this->get_customer_id(),
						'success_url'           => $success_url,
						'cancel_url'            => $this->get_url_fail( array( $product ) ),
					)
				)
			);

			return $this->api->checkout->sessions->create( $session_args )->id;
		}

		/**
		 * Find or creates a new plan.
		 *
		 * @since 4.5.0
		 *
		 * @param float                 $amount  Amount.
		 * @param Learndash_Pricing_DTO $pricing Pricing DTO.
		 * @param Product               $product Product.
		 *
		 * @throws ApiErrorException If plan could not be created.
		 *
		 * @return string
		 */
		private function get_plan_id( float $amount, Learndash_Pricing_DTO $pricing, Product $product ): string {
			$plan_ids = (array) get_post_meta( $product->get_id(), self::PLANS_META_KEY );
			$plan_id  = strval( end( $plan_ids ) );

			$product_name = $this->map_description( array( $product ) );

			$plan_params = array(
				'amount'         => $this->get_price_as_stripe( $amount ),
				'currency'       => $this->currency_code,
				'id'             => 'learndash-order-' . uniqid(),
				'interval'       => self::PERIOD_HASH[ $pricing->duration_length ],
				'product'        => array(
					'name' => $product_name,
				),
				'interval_count' => $pricing->duration_value,
			);

			if ( empty( $plan_id ) ) {
				// Create a new plan.
				$plan = $this->api->plans->create( $plan_params );
				add_post_meta( $product->get_id(), self::PLANS_META_KEY, $plan->id );

				return $plan->id;
			}

			try {
				$plan = $this->api->plans->retrieve(
					$plan_id,
					array(
						'expand' => array( 'product' ),
					)
				);

				if (
					$plan->amount !== $this->get_price_as_stripe( $amount ) ||
					mb_strtolower( $plan->currency ) !== mb_strtolower( $this->currency_code ) ||
					$plan->id !== $plan_id ||
					$plan->interval !== self::PERIOD_HASH[ $pricing->duration_length ] ||
					! $plan->product instanceof \Stripe\Product ||
					$plan->product->name !== $product_name ||
					$plan->interval_count !== $pricing->duration_value
				) {
					// Don't delete the old plan as old subscription may still be attached to it. Create a new plan.
					$plan = $this->api->plans->create( $plan_params );
					add_post_meta( $product->get_id(), self::PLANS_META_KEY, $plan->id );
				}
			} catch ( Exception $e ) {
				// Create a new plan.
				$plan = $this->api->plans->create( $plan_params );
				add_post_meta( $product->get_id(), self::PLANS_META_KEY, $plan->id );
			}

			return $plan->id;
		}

		/**
		 * Maps trial duration in days.
		 *
		 * @since 4.5.0
		 *
		 * @param int    $duration_value  Duration value.
		 * @param string $duration_length Duration length.
		 *
		 * @return int Number of days.
		 */
		private function map_trial_duration_in_days( int $duration_value, string $duration_length ): int {
			if ( 0 === $duration_value || empty( $duration_length ) ) {
				return 0;
			}

			$duration_number_in_days_by_length = array(
				'D' => 1,
				'W' => 7,
				'M' => 30,
				'Y' => 365,
			);

			return $duration_value * $duration_number_in_days_by_length[ $duration_length ];
		}

		/**
		 * Returns valid customer id.
		 *
		 * @since 4.5.0
		 *
		 * @throws ApiErrorException If customer id is not valid.
		 *
		 * @return string Customer ID or empty string.
		 */
		private function get_customer_id(): string {
			$customer_id = strval( get_user_meta( $this->user->ID, $this->customer_id_meta_key, true ) );

			if ( empty( $customer_id ) ) {
				return '';
			}

			$customer = $this->api->customers->retrieve( $customer_id );

			if ( empty( $customer->id ) || ! empty( $customer->deleted ) ) {
				return '';
			}

			return $customer_id;
		}
	}
}
