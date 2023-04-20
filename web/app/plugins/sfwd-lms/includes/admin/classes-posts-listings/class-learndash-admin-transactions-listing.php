<?php
/**
 * LearnDash Transactions (sfwd-transactions) Posts Listing.
 *
 * @since 3.2.0
 * @package LearnDash\Transactions\Listing
 */

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
		private const ACTION_REMOVE_ACCESS = 'remove_access';
		private const ACTION_ADD_ACCESS    = 'add_access';

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			$this->post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

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

			$this->selectors = array(
				'payment_processors' => array(
					'type'                   => 'early',
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'Show All Payment Processors', 'learndash' ),
					'options'                => Learndash_Payment_Gateway::get_select_list(),
					'listing_query_function' => array( $this, 'filter_by_payment_gateway' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'transaction_type'   => array(
					'type'                   => 'early',
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'Show All Transactions Types', 'learndash' ),
					'options'                => array(
						'return-success'                => esc_html__( 'PayPal Purchase Pending', 'learndash' ),
						'web_accept'                    => esc_html__( 'PayPal Purchase Complete', 'learndash' ),
						'subscr_cancel'                 => esc_html__( 'PayPal Subscription Canceled', 'learndash' ),
						'subscr_eot'                    => esc_html__( 'PayPal Subscription Expired', 'learndash' ),
						'subscr_failed'                 => esc_html__( 'PayPal Subscription Payment Failed', 'learndash' ),
						'subscr_payment'                => esc_html__( 'PayPal Subscription Payment Success', 'learndash' ),
						'subscr_signup'                 => esc_html__( 'PayPal Subscription Signup', 'learndash' ),
						'stripe_paynow'                 => esc_html__( 'Stripe Purchase', 'learndash' ),
						'stripe_subscribe'              => esc_html__( 'Stripe Subscription', 'learndash' ),
						'razorpay_paynow'               => esc_html__( 'Razorpay Purchase', 'learndash' ),
						'razorpay_subscribe'            => esc_html__( 'Razorpay Subscription (no trial)', 'learndash' ),
						'razorpay_subscribe_paid_trial' => esc_html__( 'Razorpay Subscription (paid trial)', 'learndash' ),
						'razorpay_subscribe_free_trial' => esc_html__( 'Razorpay Subscription (free trial)', 'learndash' ),
					),
					'listing_query_function' => array( $this, 'filter_by_transaction_type' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'course_id'          => array(
					'type'                    => 'post_type',
					'post_type'               => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
					'show_all_value'          => '',
					'show_all_label'          => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'listing_query_function'  => array( $this, 'filter_by_product' ),
					'selector_value_function' => array( $this, 'selector_value_for_course' ),
				),
				'group_id'           => array(
					'type'                    => 'post_type',
					'post_type'               => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
					'show_all_value'          => '',
					'show_all_label'          => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'All %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'listing_query_function'  => array( $this, 'filter_by_product' ),
					'selector_value_function' => array( $this, 'selector_value_for_group' ),
				),
			);

			$this->columns = array(
				'product' => array(
					'label'   => esc_html__( 'Product', 'learndash' ),
					'display' => array( $this, 'show_column_product' ),
				),
				'user'    => array(
					'label'   => esc_html__( 'User', 'learndash' ),
					'display' => array( $this, 'show_column_user' ),
				),
				'access'  => array(
					'label'   => esc_html__( 'Access', 'learndash' ),
					'display' => array( $this, 'show_column_access' ),
				),
				'gateway' => array(
					'label'   => esc_html__( 'Gateway', 'learndash' ),
					'display' => array( $this, 'show_column_gateway' ),
				),
				'info'    => array(
					'label'   => esc_html__( 'Info', 'learndash' ),
					'display' => array( $this, 'show_column_info' ),
				),
				'coupon'  => array(
					'label'   => esc_html__( 'Coupon', 'learndash' ),
					'display' => array( $this, 'show_column_coupon' ),
				),
			);

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

			add_action( 'admin_footer', array( $this, 'transactions_bulk_actions' ), 30 );

			$this->transactions_bulk_actions_update_access();
		}

		/**
		 * Filters by a payment gateway.
		 *
		 * @since 3.6.0
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_payment_gateway( array $q_vars, array $selector = array() ): array {
			if ( empty( $selector['selected'] ) ) {
				return $q_vars;
			}

			if ( ! isset( $q_vars['meta_query'] ) || ! is_array( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			if ( Learndash_Paypal_IPN_Gateway::get_name() === $selector['selected'] ) {
				$q_vars['meta_query']['relation'] = 'OR';
				$q_vars['meta_query'][]           = array(
					'key'     => Learndash_Transaction_Model::$meta_key_gateway_name,
					'compare' => '=',
					'value'   => $selector['selected'],
				);
				$q_vars['meta_query'][]           = array(
					'key'     => Learndash_Transaction_Model::$meta_key_gateway_name,
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
					'key'     => Learndash_Transaction_Model::$meta_key_gateway_name,
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
					'key'     => Learndash_Transaction_Model::$meta_key_gateway_name,
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
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_transaction_type( array $q_vars, array $selector = array() ): array {
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
						'key'   => Learndash_Transaction_Model::$meta_key_gateway_name,
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
						'key'   => Learndash_Transaction_Model::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_price_type,
						'value' => LEARNDASH_PRICE_TYPE_SUBSCRIBE,
					),
					array(
						'key'     => Learndash_Transaction_Model::$meta_key_has_trial,
						'compare' => '!=',
						'value'   => 1,
					),
				);
			} elseif ( 'razorpay_subscribe_paid_trial' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_has_trial,
						'value' => 1,
					),
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_has_free_trial,
						'value' => 0,
					),
				);
			} elseif ( 'razorpay_subscribe_free_trial' === $selector['selected'] ) {
				$q_vars['meta_query'][] = array(
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_gateway_name,
						'value' => Learndash_Razorpay_Gateway::get_name(),
					),
					array(
						'key'   => Learndash_Transaction_Model::$meta_key_has_free_trial,
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
		 *
		 * @param array<string,mixed> $q_vars   Query vars used for the table listing.
		 * @param array<string,mixed> $selector Selector array.
		 *
		 * @return array<string,mixed> Query vars.
		 */
		protected function filter_by_product( array $q_vars, array $selector = array() ): array {
			if ( empty( $selector['selected'] ) ) {
				return $q_vars;
			}

			if ( ! isset( $q_vars['meta_query'] ) || ! is_array( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query']['relation'] = 'OR';
			foreach ( Learndash_Transaction_Model::$product_id_meta_keys as $meta_key ) {
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
			$transaction = Learndash_Transaction_Model::find( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$gateway_select = Learndash_Payment_Gateway::get_select_list();
			$gateway_name   = $transaction->get_gateway_name();
			$gateway_label  = $transaction->get_gateway_label();

			echo esc_html( $gateway_label );

			if ( empty( $gateway_name ) || ! array_key_exists( $gateway_name, $gateway_select ) ) {
				return;
			}

			echo $this->list_table_row_actions( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
				array(
					'ld-payment-processor-filter' => sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							add_query_arg( 'payment_processors', $gateway_name, $this->get_clean_filter_url() )
						),
						esc_html__( 'filter', 'learndash' )
					),
				)
			);
		}

		/**
		 * Outputs the Transaction Type column.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_info( int $post_id ): void {
			$transaction = Learndash_Transaction_Model::find( $post_id );

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

			try {
				$pricing = $transaction->get_pricing();
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				return;
			}

			echo sprintf(
				// Translators: placeholder: Transaction price.
				esc_html_x( 'Price: %s', 'placeholder: Transaction price', 'learndash' ),
				esc_html(
					learndash_get_price_formatted( $pricing->price, $pricing->currency )
				)
			);

			if ( $pricing->discount > 0 ) {
				echo '<br/>';

				echo sprintf(
					// Translators: placeholder: Transaction discount.
					esc_html_x( 'Discount: %s', 'placeholder: Transaction discount', 'learndash' ),
					esc_html(
						learndash_get_price_formatted( $pricing->discount * -1, $pricing->currency )
					)
				);
				echo '<br/>';

				echo sprintf(
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

				echo sprintf(
					// Translators: placeholder: Transaction recurring times.
					esc_html_x( 'Recurring times: %s', 'placeholder: Transaction recurring times', 'learndash' ),
					esc_html(
						$pricing->recurring_times > 0
							? (string) $pricing->recurring_times
							: __( 'Unlimited', 'learndash' )
					)
				);
				echo '<br/>';

				echo sprintf(
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

					echo sprintf(
						// Translators: placeholder: Transaction trial price.
						esc_html_x( 'Trial price: %s', 'placeholder: Transaction trial price', 'learndash' ),
						esc_html(
							learndash_get_price_formatted( $pricing->trial_price, $pricing->currency )
						)
					);
					echo '<br/>';

					echo sprintf(
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
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_coupon( int $post_id ): void {
			$transaction = Learndash_Transaction_Model::find( $post_id );

			if ( ! $transaction || $transaction->is_parent() ) {
				return;
			}

			try {
				$coupon_data = $transaction->get_coupon_data();
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				return;
			}

			if ( ! empty( $coupon_data->code ) ) {
				try {
					$pricing = $transaction->get_pricing();
				} catch ( Learndash_DTO_Validation_Exception $e ) {
					return;
				}

				$formatted_amount = $coupon_data->type === LEARNDASH_COUPON_TYPE_FLAT
					? learndash_get_price_formatted( $coupon_data->amount, $pricing->currency )
					: $coupon_data->amount . '%';

				echo sprintf(
					// Translators: placeholder: Coupon code.
					esc_html_x( 'Code: %s', 'placeholder: Coupon code', 'learndash' ),
					esc_html( $coupon_data->code )
				);
				echo '<br/>';

				echo sprintf(
					// Translators: placeholder: Coupon type.
					esc_html_x( 'Type: %s', 'placeholder: Coupon type', 'learndash' ),
					esc_html( $coupon_data->type )
				);
				echo '<br/>';

				echo sprintf(
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
		 * Output a course/group.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_product( int $post_id ): void {
			$transaction = Learndash_Transaction_Model::find( $post_id );

			if ( ! $transaction ) {
				return;
			}

			if ( $transaction->is_parent() ) {
				$children_count = count( $transaction->get_children() );

				echo esc_html(
					$children_count . ' ' . _n( 'product', 'products', $children_count, 'learndash' )
				);

				return;
			}

			$product = $transaction->get_product();

			if ( ! $product ) {
				$label = $transaction->get_product_type_label();
				if ( ! empty( $label ) ) {
					echo esc_html( $label . ': ' );
				}

				echo esc_html( $transaction->get_product_name() );

				return;
			}

			$product_post = $product->get_post();

			// Map filter url.
			$filter_url = esc_url(
				add_query_arg(
					LDLMS_Post_Types::get_post_type_key( $product_post->post_type ) . '_id',
					$product->get_id(),
					$this->get_clean_filter_url()
				)
			);

			// Map row actions.

			$row_actions = array(
				'ld-post-filter' => sprintf(
					'<a href="%s">%s</a>',
					$filter_url,
					esc_html__( 'filter', 'learndash' )
				),
			);

			if ( current_user_can( 'edit_post', $product->get_id() ) ) {
				$row_actions['ld-post-edit'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_edit_post_link( $product_post ) ),
					esc_html__( 'edit', 'learndash' )
				);
			}

			if ( is_post_type_viewable( $product_post->post_type ) ) {
				$row_actions['ld-post-view'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_permalink( $product_post ) ),
					esc_html__( 'view', 'learndash' )
				);
			}

			// Show HTML.
			echo sprintf(
				// translators: placeholder: Post type label (Course/Group), Link to Course/Group.
				esc_html_x( '%1$s: %2$s', 'placeholder: Post type label (Course/Group), Link to Course/Group', 'learndash' ),
				esc_html( $transaction->get_product_type_label() ),
				wp_kses_post(
					sprintf( '<a href="%s">%s</a>', $filter_url, esc_html( $transaction->get_product_name() ) )
				)
			);

			// Show actions.
			echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
		}

		/**
		 * Outputs product access status.
		 *
		 * @since 3.6.0
		 *
		 * @param int $post_id Transaction Post ID.
		 *
		 * @return void
		 */
		protected function show_column_access( int $post_id ): void {
			$transaction = Learndash_Transaction_Model::find( $post_id );

			if ( ! $transaction ) {
				return;
			}

			if ( $transaction->is_parent() ) {
				return;
			}

			$user    = $transaction->get_user();
			$product = $transaction->get_product();

			$user_has_access = $product && $product->user_has_access( $user );

			$user_has_access
				? esc_html_e( 'Yes', 'learndash' )
				: esc_html_e( 'No', 'learndash' );

			if ( ! $product || 0 === $user->ID ) {
				return;
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
			$transaction = Learndash_Transaction_Model::find( $post_id );

			if ( ! $transaction ) {
				return;
			}

			$user         = $transaction->get_user();
			$display_name = $user->display_name;

			if ( current_user_can( 'edit_users' ) && $user->ID > 0 ) {
				if ( ! empty( $user->user_email ) && $user->user_email !== $user->display_name ) {
					$display_name .= ' (' . $user->user_email . ')';
				}

				$edit_url = get_edit_user_link( $user->ID );

				echo sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html( $display_name ) );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
				echo $this->list_table_row_actions(
					array(
						'edit' => sprintf(
							'<a href="%s">%s</a>',
							esc_url( $edit_url ),
							esc_html__( 'edit', 'learndash' )
						),
					)
				);
			} else {
				echo esc_html( $display_name );
			}
		}

		/**
		 * Adds a 'Remove Access' option next to certain selects on transaction edit screen in admin.
		 *
		 * Fires on `admin_footer` hook.
		 *
		 * @since 3.6.0
		 *
		 * @global WP_Post $post Post object.
		 *
		 * @return void
		 */
		public function transactions_bulk_actions(): void {
			global $post;

			if ( empty( $post ) ) {
				return;
			}

			if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $post->post_type ) {
				return;
			}

			$remove_access_action_label = esc_html__( 'Remove access', 'learndash' );
			$add_access_action_label    = esc_html__( 'Add access', 'learndash' );
			?>
			<script type="text/javascript">
				jQuery( function() {
					jQuery('<option>').val('<?php echo esc_attr( self::ACTION_REMOVE_ACCESS ); ?>').text('<?php echo esc_attr( $remove_access_action_label ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('<?php echo esc_attr( self::ACTION_REMOVE_ACCESS ); ?>').text('<?php echo esc_attr( $remove_access_action_label ); ?>').appendTo("select[name='action2']");
					jQuery('<option>').val('<?php echo esc_attr( self::ACTION_ADD_ACCESS ); ?>').text('<?php echo esc_attr( $add_access_action_label ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('<?php echo esc_attr( self::ACTION_ADD_ACCESS ); ?>').text('<?php echo esc_attr( $add_access_action_label ); ?>').appendTo("select[name='action2']");
				});
			</script>
			<?php
		}

		/**
		 * Handles the access removal/adding in bulk.
		 *
		 * Fires on `load-edit.php` hook.
		 *
		 * @since 3.6.0
		 *
		 * @return void
		 */
		protected function transactions_bulk_actions_update_access(): void {
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

			$transactions = Learndash_Transaction_Model::find_many(
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
	}
}

new Learndash_Admin_Transactions_Listing();
