<?php
/**
 * LearnDash Transactions (sfwd-transactions) Posts Listing.
 *
 * @since 3.2.0
 * @package LearnDash\Transactions\Listing
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Learndash_Admin_Posts_Listing' ) && ! class_exists( 'Learndash_Admin_Transactions_Listing' ) ) {
	/**
	 * Class LearnDash Transactions (sfwd-transactions) Posts Listing.
	 *
	 * @since 3.2.0
	 */
	class Learndash_Admin_Transactions_Listing extends Learndash_Admin_Posts_Listing {
		/**
		 * Action to remove access.
		 *
		 * @deprecated 4.19.0 This constant is no longer used.
		 *
		 * @var string
		 */
		private const ACTION_REMOVE_ACCESS = 'remove_access';

		/**
		 * Action to add access.
		 *
		 * @deprecated 4.19.0 This constant is no longer used.
		 *
		 * @var string
		 */
		private const ACTION_ADD_ACCESS = 'add_access';

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			$this->post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

			add_filter(
				'list_table_primary_column',
				[ $this, 'set_primary_column' ],
				50,
				2
			);

			add_filter(
				'page_row_actions',
				[ $this, 'remove_row_actions' ],
				50,
				2
			);

			add_filter(
				"views_edit-{$this->post_type}",
				[ $this, 'remove_list_views' ],
				50
			);

			add_filter(
				"views_edit-{$this->post_type}",
				[ $this, 'fix_current_indicator_on_test_tab' ],
				50
			);

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}

			$this->selectors = [
				'date_range'         => [
					'type'                   => 'early',
					'display'                => static function ( $args ) {
						Template::show_admin_template( 'modules/payments/orders/list/filters/date', [ 'args' => $args ] );
					},
					'listing_query_function' => [ $this, 'filter_by_date_range' ],
				],
				'payment_processors' => [
					'type'           => 'early',
					'show_all_value' => '',
					'show_all_label' => esc_html__( 'All Gateways', 'learndash' ),
					'options'        => Learndash_Payment_Gateway::get_select_list(),
					'select2'        => true,
					'select2_fetch'  => false,
				],
				'group_id'           => [
					'type'                    => 'post_type',
					'post_type'               => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
					'show_all_value'          => '',
					'show_all_label'          => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'All %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'selector_value_function' => [ $this, 'selector_value_for_group' ],
				],
				'course_id'          => [
					'type'                    => 'post_type',
					'post_type'               => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
					'show_all_value'          => '',
					'show_all_label'          => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'selector_value_function' => [ $this, 'selector_value_for_course' ],
				],
			];

			$this->columns = [
				'id'       => [
					'label'   => esc_html__( 'ID', 'learndash' ),
					'display' => [ $this, 'show_column_id' ],
				],
				'item'     => [
					'label'   => esc_html__( 'Item', 'learndash' ),
					'display' => [ $this, 'show_column_product' ],
				],
				'date'     => [
					'label'   => esc_html__( 'Date', 'learndash' ),
					'display' => [ $this, 'show_column_date' ],
				],
				'customer' => [
					'label'   => esc_html__( 'Customer', 'learndash' ),
					'display' => [ $this, 'show_column_user' ],
				],
				'gateway'  => [
					'label'   => esc_html__( 'Gateway', 'learndash' ),
					'display' => [ $this, 'show_column_gateway' ],
				],
				'price'    => [
					'label'   => esc_html__( 'Price', 'learndash' ),
					'display' => [ $this, 'show_column_price' ],
				],
			];

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.6.0
		 */
		public function on_load_listing() {
			if ( ! $this->post_type_check() ) {
				return;
			}

			parent::on_load_listing();

			add_filter( 'bulk_actions-edit-sfwd-transactions', [ $this, 'remove_bulk_edit_action' ] );
		}

		/**
		 * Removes the default Edit bulk action.
		 *
		 * @since 4.19.0
		 *
		 * @param array<mixed> $actions Array of bulk actions.
		 *
		 * @return array<mixed> Modified bulk actions.
		 */
		public function remove_bulk_edit_action( $actions ) {
			// Remove the 'Edit' bulk action.
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return $actions;
		}

		/**
		 * Sets the primary/default column for the list table.
		 * This is important to ensure that mobile styles are rendered properly.
		 *
		 * @since 4.19.0
		 *
		 * @param string $default_column Column name default for the list table.
		 * @param string $context        Screen ID for the list table.
		 *
		 * @return string
		 */
		public function set_primary_column( $default_column, $context ) {
			if (
				empty( $this->columns )
				|| $context !== "edit-{$this->post_type}"
			) {
				return $default_column;
			}

			return array_key_first( $this->columns );
		}

		/**
		 * Removes the inline edit action for the transaction post type.
		 *
		 * @since 4.19.0
		 *
		 * @param array<string, string> $actions Array of actions to display for the row.
		 * @param WP_Post               $post    Row's Post object.
		 *
		 * @return array<string, string>
		 */
		public function remove_row_actions( $actions, $post ) {
			if ( $this->post_type !== $post->post_type ) {
				return $actions;
			}

			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			return $actions;
		}

		/**
		 * Remove unwanted "Views" from the list table.
		 * These are the links that show along the top for "All", "Mine", "Published", etc.
		 *
		 * @since 4.19.0
		 *
		 * @param string[] $views View HTML links to display.
		 *
		 * @return string[]
		 */
		public function remove_list_views( $views ) {
			$remove = [
				'publish',
				'mine',
				'draft',
			];

			foreach ( $remove as $key ) {
				if ( isset( $views[ $key ] ) ) {
					unset( $views[ $key ] );
				}
			}

			// If "All" is the only view that would be shown, remove it.
			if (
				count( $views ) === 1
				&& isset( $views['all'] )
			) {
				unset( $views['all'] );
			}

			return $views;
		}

		/**
		 * Fixes the current indicator on the test tab for the "All" case.
		 * It works for WP 6.2.0 and later because it uses the `WP_HTML_Tag_Processor` class.
		 *
		 * @since 4.19.0
		 *
		 * @param string[] $views View HTML links to display.
		 *
		 * @return string[]
		 */
		public function fix_current_indicator_on_test_tab( $views ) {
			$is_test_mode = Cast::to_bool(
				SuperGlobals::get_var( 'is_test_mode', false )
			);

			if (
				! $is_test_mode
				|| ! $this->is_base_request() // If we are not on the "All" view, do nothing.
				|| empty( $views['all'] ) // If "All" is not present, do nothing, we need to fix the "All" view only in this case.
				|| ! class_exists( 'WP_HTML_Tag_Processor' ) // No nice way to process HTML tags without this class, it was added in WP 6.2.0.
			) {
				return $views;
			}

			$processor = new WP_HTML_Tag_Processor( $views['all'] );
			$processor->next_tag( [ 'tag_name' => 'a' ] );
			$processor->add_class( 'current' );
			$processor->set_attribute( 'aria-current', 'page' );

			$views['all'] = $processor->get_updated_html();

			return $views;
		}

		/**
		 * Determines if the current view is the "All" view.
		 *
		 * Copied from the `WP_Posts_List_Table::is_base_request`.
		 * Only the additional `is_test_mode` GET param was added to be removed to allow detection of the "All" view.
		 *
		 * @since 4.19.0
		 *
		 * @return bool Whether the current view is the "All" view.
		 */
		private function is_base_request(): bool {
			$vars = SuperGlobals::get_sanitized_superglobal( 'GET' );

			if ( ! is_array( $vars ) ) {
				return false;
			}

			unset( $vars['paged'], $vars['is_test_mode'] );

			if ( empty( $vars ) ) {
				return true;
			} elseif (
				1 === count( $vars )
				&& ! empty( $vars['post_type'] )
			) {
				return $this->post_type === $vars['post_type'];
			}

			return 1 === count( $vars )
				&& ! empty( $vars['mode'] );
		}

		/**
		 * Filters by a date range.
		 *
		 * @since 4.19.0
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_date_range( array $q_vars, array $selector = [] ): array {
			$nonce = Cast::to_string(
				SuperGlobals::get_var( 'ld-listing-nonce' )
			);

			if (
				empty( $nonce )
				|| ! wp_verify_nonce( $nonce, get_called_class() )
			) {
				return $q_vars;
			}

			$q_vars['date_query'] = [
				[
					'after'     => SuperGlobals::get_var( 'from_date' ),
					'before'    => SuperGlobals::get_var( 'to_date' ),
					'inclusive' => true,
				],
			];

			return $q_vars;
		}

		/**
		 * Filters by a payment gateway.
		 *
		 * @since 3.6.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_payment_gateway( array $q_vars, array $selector = array() ): array {
			_deprecated_function( __METHOD__, '4.19.0' );

			if ( empty( $selector['selected'] ) ) {
				return $q_vars;
			}

			if ( ! isset( $q_vars['meta_query'] ) || ! is_array( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			if ( Learndash_Paypal_IPN_Gateway::get_name() === $selector['selected'] ) {
				$q_vars['meta_query']['relation'] = 'OR';
				$q_vars['meta_query'][]           = array(
					'key'     => Transaction::$meta_key_gateway_name,
					'compare' => '=',
					'value'   => $selector['selected'],
				);
				$q_vars['meta_query'][]           = array(
					'key'     => Transaction::$meta_key_gateway_name,
					'compare' => '=',
					'value'   => 'paypal',
				);
				$q_vars['meta_query'][]           = array(
					'key'     => 'ipn_track_id',
					'compare' => 'EXISTS',
				);
			} elseif ( Learndash_Stripe_Gateway::get_name() === $selector['selected'] ) {
				$q_vars['meta_query']['relation'] = 'OR';
				$q_vars['meta_query'][]           = array(
					'key'     => Transaction::$meta_key_gateway_name,
					'compare' => '=',
					'value'   => $selector['selected'],
				);
				// Legacy.
				$q_vars['meta_query'][] = array(
					'key'     => 'stripe_session_id',
					'compare' => 'EXISTS',
				);
			} elseif ( Learndash_Razorpay_Gateway::get_name() === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'     => Transaction::$meta_key_gateway_name,
					'compare' => '=',
					'value'   => $selector['selected'],
				);
			}

			return $q_vars;
		}

		/**
		 * Filters by a transaction type.
		 *
		 * @since 3.6.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_transaction_type( array $q_vars, array $selector = array() ): array {
			_deprecated_function( __METHOD__, '4.19.0' );

			if ( empty( $selector['selected'] ) ) {
				return $q_vars;
			}

			if ( ! isset( $q_vars['meta_query'] ) || ! is_array( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			if ( 'web_accept' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'web_accept',
				);
			} elseif ( 'subscr_cancel' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'subscr_cancel',
				);
			} elseif ( 'subscr_eot' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'subscr_eot',
				);
			} elseif ( 'subscr_failed' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'subscr_failed',
				);
			} elseif ( 'subscr_payment' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'subscr_payment',
				);
			} elseif ( 'subscr_signup' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'txn_type',
					'value' => 'subscr_signup',
				);
			} elseif ( 'stripe_paynow' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'stripe_price_type',
					'value' => LEARNDASH_PRICE_TYPE_PAYNOW,
				);
			} elseif ( 'stripe_subscribe' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					'key'   => 'stripe_price_type',
					'value' => LEARNDASH_PRICE_TYPE_SUBSCRIBE,
				);
			} elseif ( 'razorpay_paynow' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Transaction::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => 'price_type',
						'value' => LEARNDASH_PRICE_TYPE_PAYNOW,
					),
				);
			} elseif ( 'razorpay_subscribe' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Transaction::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Transaction::$meta_key_price_type,
						'value' => LEARNDASH_PRICE_TYPE_SUBSCRIBE,
					),
					array(
						'key'     => Transaction::$meta_key_has_trial,
						'compare' => '!=',
						'value'   => 1,
					),
				);
			} elseif ( 'razorpay_subscribe_paid_trial' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Transaction::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Transaction::$meta_key_has_trial,
						'value' => 1,
					),
					array(
						'key'   => Transaction::$meta_key_has_free_trial,
						'value' => 0,
					),
				);
			} elseif ( 'razorpay_subscribe_free_trial' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Transaction::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Transaction::$meta_key_has_free_trial,
						'value' => 1,
					),
				);
			}

			return $q_vars;
		}

		/**
		 * Filters by a product.
		 *
		 * @since 4.5.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_product( array $q_vars, array $selector = array() ): array {
			_deprecated_function( __METHOD__, '4.19.0' );

			if ( empty( $selector['selected'] ) ) {
				return $q_vars;
			}

			if ( ! isset( $q_vars['meta_query'] ) || ! is_array( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query']['relation'] = 'OR';
			foreach ( Transaction::$product_id_meta_keys as $meta_key ) {
				$q_vars['meta_query'][] = array(
					'key'   => $meta_key,
					'value' => (int) $selector['selected'],
				);
			}

			return $q_vars;
		}

		/**
		 * Outputs a payment gateway label.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_gateway( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			// Show.

			$gateway_label = sprintf(
				'<span>%s</span>',
				$transaction->get_gateway_label()
			);

			$gateway_transaction_id = '';

			if (
				$transaction->get_gateway_transaction_id()
				&& ! $transaction->is_subscription()
			) {
				$gateway_transaction_id = sprintf(
					'<span>%s%s</span>',
					$transaction->get_gateway_transaction_id(),
					wp_kses(
						Template::get_admin_template(
							'common/copy-text',
							[
								'text'            => $transaction->get_gateway_transaction_id(),
								'tooltip_default' => esc_html__( 'Copy Session ID', 'learndash' ),
							]
						),
						[
							'button' => [
								'class'                => true,
								'data-tooltip'         => true,
								'data-tooltip-default' => true,
								'data-tooltip-success' => true,
								'data-text'            => true,
							],
							'span'   => [
								'class'       => true,
								'aria-hidden' => true,
							],
						]
					)
				);
			}

			$row_actions = $this->list_table_row_actions(
				[
					'ld-payment-processor-filter' => sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url(
							add_query_arg( 'payment_processors', $transaction->get_gateway_name(), $this->get_clean_filter_url() )
						),
						esc_html__( 'Filter', 'learndash' )
					),
				]
			);

			echo wp_kses_post(
				sprintf(
					'<div class="ld-order-list__cell-container ld-order-list__cell-container--gateway">%1$s%2$s%3$s</div>',
					$gateway_label,
					$gateway_transaction_id,
					$row_actions
				)
			);
		}

		/**
		 * Outputs the Transaction Type column.
		 *
		 * @since 3.2.3
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_info( int $post_id ): void {
			_deprecated_function( __METHOD__, '4.19.0' );

			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			if ( $transaction->is_parent() ) {
				return;
			}

			if ( $transaction->is_free() ) {
				esc_html_e( 'Enrolled by a coupon with no payment', 'learndash' );
				return;
			}

			$pricing = $transaction->get_pricing();

			printf(
				// Translators: placeholder: Transaction price.
				esc_html_x( 'Price: %s', 'placeholder: Transaction price', 'learndash' ),
				esc_html(
					learndash_get_price_formatted( $pricing->price, $pricing->currency )
				)
			);

			if ( $pricing->discount > 0 ) {
				echo '<br/>';

				printf(
					// Translators: placeholder: Transaction discount.
					esc_html_x( 'Discount: %s', 'placeholder: Transaction discount', 'learndash' ),
					esc_html(
						learndash_get_price_formatted( $pricing->discount * -1, $pricing->currency )
					)
				);
				echo '<br/>';

				printf(
					// Translators: placeholder: Transaction discounted price.
					esc_html_x( 'Final price: %s', 'placeholder: Transaction final price', 'learndash' ),
					esc_html(
						learndash_get_price_formatted( $pricing->discounted_price, $pricing->currency )
					)
				);
			}

			if ( $transaction->is_subscription() ) {
				echo '<br/>';
				echo '<br/>';

				printf(
					// Translators: placeholder: Transaction recurring times.
					esc_html_x( 'Recurring times: %s', 'placeholder: Transaction recurring times', 'learndash' ),
					esc_html(
						$pricing->recurring_times > 0
							? (string) $pricing->recurring_times
							: __( 'Unlimited', 'learndash' )
					)
				);
				echo '<br/>';

				printf(
					// Translators: placeholder: Transaction billing cycle value, Transaction billing cycle length.
					esc_html_x( 'Billing cycle: every %1$d %2$s', 'placeholder: Transaction billing cycle value, Transaction billing cycle length', 'learndash' ),
					esc_attr( (string) $pricing->duration_value ),
					esc_html(
						learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length )
					)
				);

				if ( $pricing->trial_duration_value > 0 ) {
					echo '<br/>';
					echo '<br/>';

					printf(
						// Translators: placeholder: Transaction trial price.
						esc_html_x( 'Trial price: %s', 'placeholder: Transaction trial price', 'learndash' ),
						esc_html(
							learndash_get_price_formatted( $pricing->trial_price, $pricing->currency )
						)
					);
					echo '<br/>';

					printf(
						// Translators: placeholder: Transaction trial duration value, Transaction trial duration length.
						esc_html_x( 'Trial duration: %1$d %2$s', 'placeholder: Transaction trial duration value, Transaction trial duration length', 'learndash' ),
						esc_attr( (string) $pricing->trial_duration_value ),
						esc_html(
							learndash_get_grammatical_number_label_for_interval( $pricing->trial_duration_value, $pricing->trial_duration_length )
						)
					);
				}
			}
		}

		/**
		 * Outputs the Coupon column.
		 *
		 * @since 4.1.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_coupon( int $post_id ): void {
			_deprecated_function( __METHOD__, '4.19.0' );

			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction || $transaction->is_parent() ) {
				return;
			}

			try {
				$coupon_data = $transaction->get_coupon_data();
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				return;
			}

			if ( ! empty( $coupon_data->code ) ) {
				$pricing = $transaction->get_pricing();

				$formatted_amount = $coupon_data->type === LEARNDASH_COUPON_TYPE_FLAT
					? learndash_get_price_formatted( $coupon_data->amount, $pricing->currency )
					: $coupon_data->amount . '%';

				printf(
					// Translators: placeholder: Coupon code.
					esc_html_x( 'Code: %s', 'placeholder: Coupon code', 'learndash' ),
					esc_html( $coupon_data->code )
				);
				echo '<br/>';

				printf(
					// Translators: placeholder: Coupon type.
					esc_html_x( 'Type: %s', 'placeholder: Coupon type', 'learndash' ),
					esc_html( $coupon_data->type )
				);
				echo '<br/>';

				printf(
					// Translators: placeholder: Coupon amount.
					esc_html_x( 'Amount: %s', 'placeholder: Coupon amount', 'learndash' ),
					esc_html( $formatted_amount )
				);
			} elseif ( $transaction->is_free() ) { // Legacy free transactions will go here as they don't have coupon data attached.
				echo esc_html__( 'Unknown', 'learndash' );
			} else {
				echo esc_html__( 'No', 'learndash' );
			}
		}

		/**
		 * Outputs the customized column 'Date'.
		 *
		 * @since 4.19.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_date( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$transaction_timestamp = Cast::to_int(
				strtotime( $transaction->get_post()->post_date_gmt )
			);

			printf(
				'<div class="ld-order-list__cell-container ld-order-list__cell-container--date"><span>%1$s</span><span>%2$s</span></div>',
				esc_html(
					learndash_adjust_date_time_display(
						$transaction_timestamp,
						Cast::to_string( get_option( 'date_format', 'Y/m/d' ) )
					)
				),
				esc_html(
					learndash_adjust_date_time_display(
						$transaction_timestamp,
						Cast::to_string( get_option( 'time_format', 'g:i A' ) )
					)
				)
			);
		}

		/**
		 * Outputs the column 'Status'.
		 *
		 * @since 4.19.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_status( int $post_id ): void {
			echo 'TODO'; // TODO: Implement this method.
		}

		/**
		 * Outputs the column 'Price'.
		 *
		 * @since 4.19.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_price( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$fist_child_transaction = $transaction->get_first_child();

			if ( ! $fist_child_transaction ) {
				return;
			}

			echo esc_html(
				$fist_child_transaction->get_formatted_price()
			);
		}

		/**
		 * Outputs the column 'ID'.
		 *
		 * @since 4.19.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_id( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$test_mode_indicator = $transaction->is_test_mode()
				? Template::get_admin_template( 'modules/payments/orders/components/test-mode-label' )
				: '';

			$id_label = esc_html( Cast::to_string( $transaction->get_id() ) );

			if ( current_user_can( 'edit_post', $transaction->get_id() ) ) {
				$id_label = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $transaction->get_edit_post_link() ),
					$id_label
				);
			}

			echo wp_kses_post(
				sprintf(
					'<div class="ld-order-list__cell-container ld-order-list__cell-container--id">%1$s%2$s</div>',
					$test_mode_indicator,
					$id_label
				)
			);
		}

		/**
		 * Output the column 'Item'.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_product( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$product      = $transaction->get_product();
			$product_name = $transaction->get_product_name();

			// If we can't find a product in the database, show the product name from the transaction or "Not found" if it's empty.
			if ( ! $product ) {
				echo esc_html( $product_name );

				return;
			}

			// Map a status.

			$transaction_user = $transaction->get_user();

			if ( ! $transaction_user->exists() ) {
				$access_status = '';
			} elseif ( $product->is_pre_ordered( $transaction_user ) ) {
				$access_status = __( 'Pre-enrolled', 'learndash' );
			} elseif ( $product->user_has_access( $transaction_user ) ) {
				$access_status = __( 'Enrolled', 'learndash' );
			} else {
				$access_status = __( 'Not Enrolled', 'learndash' );
			}

			// Show.

			echo wp_kses_post(
				sprintf(
					'<div class="ld-order-list__cell-container ld-order-list__cell-container--product"><a href="%1$s">%2$s</a> %3$s</div>',
					esc_url( $product->get_permalink() ),
					esc_html( $product_name ),
					esc_html( $access_status )
				)
			);

			// Row actions.

			$filter_arg_name = '';

			if ( $product->is_post_type( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) ) ) {
				$filter_arg_name = 'course_id';
			} elseif ( $product->is_post_type( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) ) ) {
				$filter_arg_name = 'group_id';
			}

			$row_actions = [
				'ld-post-filter' => sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						add_query_arg(
							$filter_arg_name,
							$product->get_id(),
							$this->get_clean_filter_url()
						)
					),
					esc_html__( 'Filter', 'learndash' )
				),
			];

			if ( current_user_can( 'edit_post', $product->get_id() ) ) {
				$row_actions['edit'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $product->get_edit_post_link() ),
					esc_html__( 'Edit', 'learndash' )
				);
			}

			if ( is_post_type_viewable( $product->get_post_type() ) ) {
				$row_actions['view'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $product->get_permalink() ),
					esc_html__( 'View', 'learndash' )
				);
			}

			echo wp_kses_post(
				$this->list_table_row_actions( $row_actions )
			);
		}

		/**
		 * Outputs product access status.
		 *
		 * @since 3.6.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_access( int $post_id ): void {
			_deprecated_function( __METHOD__, '4.19.0' );

			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			if ( $transaction->is_parent() ) {
				return;
			}

			$user    = $transaction->get_user();
			$product = $transaction->get_product();

			if ( ! $product || ! $user->exists() ) {
				return;
			}

			$user_has_access = $product->user_has_access( $user );

			if ( $product->is_pre_ordered( $user ) ) {
				esc_html_e( 'Pre-order', 'learndash' );
			} else {
				$user_has_access
				? esc_html_e( 'Yes', 'learndash' )
				: esc_html_e( 'No', 'learndash' );
			}

			if ( $user_has_access ) {
				$can_be_removed = true;

				if ( Learndash_Paypal_IPN_Gateway::get_name() === $transaction->get_gateway_name() ) {
					/**
					 * Filters the PayPal Subscription removal statuses.
					 *
					 * @param string[] $removal_statuses Array of PayPal IPN subscription statuses.
					 * @param WP_Post  $post             Course or Group.
					 * @param WP_User  $user             User.
					 *
					 * @return string[] Array of PayPal IPN subscription statuses.
					 */
					$removal_statuses = apply_filters(
						'learndash_paypal_subscription_removal_statuses',
						array( 'return-success', 'subscr_failed', 'subscr_cancel', 'subscr_eot' ),
						$product->get_post(),
						$user
					);

					$can_be_removed = in_array(
						get_post_meta( $transaction->get_id(), 'txn_type', true ),
						$removal_statuses,
						true
					);
				}

				if ( $can_be_removed ) {
					echo $this->list_table_row_actions( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
						array(
							'ld-access-remove' => sprintf(
								'<a href="#" class="small ld_remove_access_single" data-transaction-id="%d">%s</a>',
								esc_attr( (string) $transaction->get_id() ),
								esc_html__( 'remove', 'learndash' )
							),
						)
					);
				}
			} else {
				echo $this->list_table_row_actions( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					array(
						'ld-access-add' => sprintf(
							'<a href="#" class="small ld_add_access_single" data-transaction-id="%d">%s</a>',
							esc_attr( (string) $transaction->get_id() ),
							esc_html__( 'add', 'learndash' )
						),
					)
				);
			}
		}

		/**
		 * Shows a user.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_user( int $post_id ): void {
			$transaction = $this->get_transaction( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$user = $transaction->get_user();

			// If the user exists and editable, show a link to edit the user. Otherwise, just show the user's email.

			$user_label = esc_html( $user->user_email );
			if (
				current_user_can( 'edit_users' )
				&& $user->exists()
			) {
				$user_label = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( get_edit_user_link( $user->ID ) ),
					$user_label
				);
			}

			if ( $user->display_name !== $user->user_email ) {
				$user_label_additional = sprintf(
					'<span>%s</span>',
					esc_html( $user->display_name )
				);
			} else {
				$user_label_additional = '';
			}

			echo wp_kses_post(
				sprintf(
					'<div class="ld-order-list__cell-container ld-order-list__cell-container--user">%1$s%2$s</div>',
					$user_label,
					$user_label_additional
				)
			);
		}

		/**
		 * Outputs bulk action validations for transactions (orders).
		 *
		 * Fires on `admin_footer` hook.
		 *
		 * @since 3.6.0
		 * @deprecated 4.19.0
		 *
		 * @global WP_Post $post Post object.
		 *
		 * @return void
		 */
		public function transactions_bulk_actions(): void {
			_deprecated_function( __METHOD__, '4.19.0' );

			global $post;

			if ( empty( $post ) ) {
				return;
			}

			if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $post->post_type ) {
				return;
			}
		}

		/**
		 * Handles the access removal/adding in bulk.
		 *
		 * Fires on `load-edit.php` hook.
		 *
		 * @since 3.6.0
		 * @deprecated 4.19.0 This method is no longer used.
		 *
		 * @return void
		 */
		protected function transactions_bulk_actions_update_access(): void {
			_deprecated_function( __METHOD__, '4.19.0' );

			if (
				empty( $_REQUEST['ld-listing-nonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ld-listing-nonce'] ) ), get_called_class() ) ||
				empty( $_REQUEST['post'] ) ||
				! is_array( $_REQUEST['post'] ) ||
				empty( $_REQUEST['post_type'] ) ||
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $_REQUEST['post_type']
			) {
				return;
			}

			$action = '';
			if ( isset( $_REQUEST['action'] ) && -1 !== intval( $_REQUEST['action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			} elseif ( isset( $_REQUEST['action2'] ) && -1 !== intval( $_REQUEST['action2'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );
			} elseif ( isset( $_REQUEST['ld_action'] ) && self::ACTION_REMOVE_ACCESS === $_REQUEST['ld_action'] ) {
				$action = self::ACTION_REMOVE_ACCESS;
			} elseif ( isset( $_REQUEST['ld_action'] ) && self::ACTION_ADD_ACCESS === $_REQUEST['ld_action'] ) {
				$action = self::ACTION_ADD_ACCESS;
			}

			if ( ! in_array( $action, array( self::ACTION_REMOVE_ACCESS, self::ACTION_ADD_ACCESS ), true ) ) {
				return;
			}

			$transactions = Transaction::find_many(
				wp_parse_id_list( wp_unslash( $_REQUEST['post'] ) )
			);

			foreach ( $transactions as $transaction ) {
				$product = $transaction->get_product();
				$user    = $transaction->get_user();

				if ( ! $product || 0 === $user->ID ) {
					continue;
				}

				$user_has_access = $product->user_has_access( $user );

				if ( self::ACTION_REMOVE_ACCESS === $action && $user_has_access ) {
					$can_be_removed = true;

					if ( Learndash_Paypal_IPN_Gateway::get_name() === $transaction->get_gateway_name() ) {
						/**
						 * Filter the PayPal Subscription removal statuses
						 *
						 * @param string[] $removal_statuses Array of PayPal IPN subscription statuses.
						 * @param WP_Post  $product          Course or Group.
						 * @param WP_User  $user             User.
						 *
						 * @return string[] Array of PayPal IPN subscription statuses.
						 */
						$removal_statuses = apply_filters(
							'learndash_paypal_subscription_removal_statuses',
							array( 'subscr_failed', 'subscr_cancel', 'subscr_eot' ),
							$product->get_post(),
							$user
						);

						$can_be_removed = in_array(
							get_post_meta( $transaction->get_id(), 'txn_type', true ),
							$removal_statuses,
							true
						);
					}

					if ( $can_be_removed ) {
						$product->unenroll( $user );
					}
				} elseif ( self::ACTION_ADD_ACCESS === $action && ! $user_has_access ) {
					$product->enroll( $user );
				}
			}
		}

		/**
		 * Retrieves a transaction by its post ID.
		 *
		 * @since 4.19.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return Transaction|null
		 */
		private function get_transaction( int $post_id ): ?Transaction {
			return Transaction::find( $post_id );
		}
	}
}

new Learndash_Admin_Transactions_Listing();
