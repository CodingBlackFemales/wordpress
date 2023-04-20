<?php
/**
 * Deprecated functions from LD 4.5.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 4.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_footer_payment_buttons' ) ) {
	/**
	 * Prints the dropdown button to the footer.
	 *
	 * @deprecated 4.5.0
	 *
	 * @global string $dropdown_button Dropdown button markup.
	 */
	function learndash_footer_payment_buttons() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		global $dropdown_button;

		if ( ! empty( $dropdown_button ) ) {
			echo $dropdown_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
		}
	}
}

if ( ! function_exists( 'learndash_get_footer' ) ) {
	/**
	 * Dequeues the jquery dropdown js if dropdown button is empty.
	 *
	 * @deprecated 4.5.0
	 *
	 * @global string $dropdown_button Dropdown button markup.
	 */
	function learndash_get_footer() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		if ( is_admin() ) {
			return;
		}

		global $dropdown_button;
		if ( empty( $dropdown_button ) ) {
			wp_dequeue_script( 'jquery-dropdown-js' );
		}
	}
}

if ( ! function_exists( 'learndash_get_payment_button_label' ) ) {
	/**
	 * Maps the payment button label.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param WP_Post|int|null $post Post or Post ID.
	 *
	 * @return string
	 */
	function learndash_get_payment_button_label( $post ): string {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		if ( empty( $post ) ) {
			return '';
		}

		if ( is_int( $post ) ) {
			$post = get_post( $post );

			if ( is_null( $post ) ) {
				return '';
			}
		}

		$button_label = '';

		if ( class_exists( 'LearnDash_Custom_Label' ) ) {
			if ( learndash_is_course_post( $post ) ) {
				$button_label = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
			} elseif ( learndash_is_group_post( $post ) ) {
				$button_label = LearnDash_Custom_Label::get_label( 'button_take_this_group' );
			}
		} else {
			if ( learndash_is_course_post( $post ) ) {
				$button_label = __( 'Take This Course', 'learndash' );
			} elseif ( learndash_is_group_post( $post ) ) {
				$button_label = __( 'Enroll in Group', 'learndash' );
			}
		}

		return $button_label;
	}
}

if ( ! function_exists( 'learndash_paypal_init_user_purchase_hash' ) ) {
	/**
	 * Create a unique hash for the pre-purchase action that will validate the
	 * return transaction logic.
	 *
	 * @deprecated 4.5.0
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return string
	 */
	function learndash_paypal_init_user_purchase_hash( $user_id = 0, $product_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$user_hash = '';

		$user_id    = absint( $user_id );
		$product_id = absint( $product_id );
		if ( ( ! empty( $user_id ) ) && ( ! empty( $product_id ) ) ) {
			$user = get_user_by( 'ID', $user_id );
			if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
				$user_hash = wp_create_nonce( $user->ID . '-' . $user->user_login . '-' . $product_id );

				if ( ! empty( $user_hash ) ) {
					update_user_meta(
						$user_id,
						'ld_purchase_nonce_' . $user_hash,
						array(
							'user_id'    => $user_id,
							'product_id' => $product_id,
							'time'       => time(),
							'nonce'      => $user_hash,
						)
					);
				}
			}
		}

		return $user_hash;
	}
}

if ( ! function_exists( 'learndash_paypal_get_purchase_success_redirect_url' ) ) {
	/**
	 * Get the PayPal purchase success redirect URL.
	 *
	 * After the PayPal purchase success, the customer can be redirected
	 * to a specific destination URL.
	 *
	 * @since      3.6.0
	 * @deprecated 4.5.0
	 *
	 * @param int $post_id Course or Group post ID purchased.
	 *
	 * @return string $return_url
	 */
	function learndash_paypal_get_purchase_success_redirect_url( $post_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$return_url = '';

		$post_id = absint( $post_id );
		if ( ! empty( $post_id ) ) {

			$type_slug = '';
			if ( learndash_get_post_type_slug( 'course' ) === get_post_type( $post_id ) ) {
				$type_slug = 'course';
			} elseif ( learndash_get_post_type_slug( 'group' ) === get_post_type( $post_id ) ) {
				$type_slug = 'group';
			}

			if ( ! empty( $type_slug ) ) {
				$price_type = learndash_get_setting( $post_id, $type_slug . '_price_type' );
				if ( ! empty( $price_type ) ) {
					$enrollment_url = learndash_get_setting( $post_id, $type_slug . '_price_type_' . $price_type . '_enrollment_url' );
					if ( ! empty( $enrollment_url ) ) {
						$return_url = $enrollment_url;
					}
				}
			}
		}

		if ( empty( $return_url ) ) {
			$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
			if ( ( isset( $paypal_settings['paypal_returnurl'] ) ) && ( ! empty( $paypal_settings['paypal_returnurl'] ) ) ) {
				$return_url = $paypal_settings['paypal_returnurl'];
			}
		}

		if ( empty( $return_url ) ) {
			$ld_registration_success_page_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', 'registration_success' );
			$ld_registration_success_page_id = absint( $ld_registration_success_page_id );
			if ( ! empty( $ld_registration_success_page_id ) ) {
				$return_url = get_permalink( $ld_registration_success_page_id );
			}
		}

		if ( ( empty( $return_url ) ) && ( ! empty( $post_id ) ) ) {
			/**
			 * If the enrollment URL is empty and the global PayPal return URL is empty,
			 * we return the customer to the course/group.
			 */
			$return_url = get_permalink( $post_id );
		}

		if ( empty( $return_url ) ) {
			$return_url = get_home_url();
		}

		/**
		 * Filters URL for PayPal purchase success.
		 *
		 * @since 3.6.0
		 *
		 * @param string $redirect_url The URL to be redirected on PayPal success.
		 * @param int    $post_id      The Course/Group Post ID.
		 */
		$return_url = apply_filters( 'learndash_paypal_purchase_success_url', $return_url, $post_id );

		return $return_url;
	}
}

if ( ! function_exists( 'learndash_paypal_get_purchase_cancel_redirect_url' ) ) {
	/**
	 * Get the PayPal purchase cancel redirect URL.
	 *
	 * After the PayPal purchase cancellation, the customer can be redirected
	 * to a specific destination URL.
	 *
	 * @since      3.6.0
	 * @deprecated 4.5.0
	 *
	 * @param int $post_id Course or Group post ID purchased.
	 *
	 * @return string $return_url
	 */
	function learndash_paypal_get_purchase_cancel_redirect_url( $post_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$return_url = '';

		$post_id = absint( $post_id );

		$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
		if ( ( isset( $paypal_settings['paypal_cancelurl'] ) ) && ( ! empty( $paypal_settings['paypal_cancelurl'] ) ) ) {
			$return_url = $paypal_settings['paypal_cancelurl'];
		}

		if ( empty( $return_url ) ) {
			if ( ! empty( $post_id ) ) {
				if ( empty( $return_url ) ) {
					/**
					 * If the PayPal cancel URL is empty we return the customer to the course/group.
					 */
					$return_url = get_permalink( $post_id );
				}
			}
		}

		if ( empty( $return_url ) ) {
			$return_url = get_home_url();
		}

		/**
		 * Filters URL for PayPal purchase success.
		 *
		 * @since 3.6.0
		 *
		 * @param string $redirect_url The URL to be redirected on PayPal success.
		 * @param int    $post_id      The Course/Group Post ID.
		 */
		$return_url = apply_filters( 'learndash_paypal_purchase_cancel_url', $return_url, $post_id );

		return $return_url;
	}
}

if ( ! function_exists( 'learndash_send_purchase_invoice_email' ) ) {
	/**
	 * Sends the course/group purchase invoice email
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id Transaction ID.
	 *
	 * @return void
	 */
	function learndash_send_purchase_invoice_email( int $transaction_id ): void {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		if ( empty( $transaction_id ) ) {
			return;
		}

		$user_id = get_post_meta( $transaction_id, 'user_id', true );

		if ( empty( $user_id ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! is_a( $user, 'WP_User' ) ) {
			return;
		}

		$email_setting = LearnDash_Settings_Section_Emails_Purchase_Invoice::get_section_settings_all();

		if ( 'on' !== $email_setting['enabled'] ) {
			return;
		}

		$post_id = get_post_meta( $transaction_id, 'post_id', true );

		$purchased_post = get_post( $post_id );
		if ( ( ! $purchased_post ) || ( ! is_a( $purchased_post, 'WP_Post' ) ) ) {
			return;
		}

		$transaction_post = get_post( $transaction_id );
		if ( ( ! $transaction_post ) || ( ! is_a( $transaction_post, 'WP_Post' ) ) ) {
			return;
		}

		if ( ! in_array( $purchased_post->post_type, learndash_get_post_type_slug( array( LDLMS_Post_Types::COURSE, LDLMS_Post_Types::GROUP ) ), true ) ) {
			return;
		}

		if ( learndash_get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction_post->post_type ) {
			return;
		}

		$placeholders = array(
			'{user_login}'   => $user->user_login,
			'{first_name}'   => $user->user_firstname,
			'{last_name}'    => $user->user_lastname,
			'{display_name}' => $user->display_name,
			'{user_email}'   => $user->user_email,
			'{post_title}'   => $purchased_post->post_title,
		);

		/**
		 * Filters purchase email placeholders.
		 *
		 * @since 4.2.0
		 *
		 * @param array $placeholders Array of email placeholders and values.
		 * @param int   $user_id      User ID.
		 * @param int   $post_id      Post ID.
		 */
		$placeholders = apply_filters( 'learndash_purchase_invoice_email_placeholders', $placeholders, $user_id, $post_id );

		/**
		 * Filters purchase invoice email subject.
		 *
		 * @since 4.2.0
		 *
		 * @param string $email_subject Email subject text.
		 * @param int    $user_id       User ID.
		 * @param int    $post_id       Post ID.
		 */
		$email_setting['subject'] = apply_filters( 'learndash_purchase_invoice_email_subject', $email_setting['subject'], $user_id, $post_id );
		if ( ! empty( $email_setting['subject'] ) ) {
			$email_setting['subject'] = learndash_emails_parse_placeholders( $email_setting['subject'], $placeholders );
		}

		/**
		 * Filters purchase invoice email message.
		 *
		 * @since 4.2.0
		 *
		 * @param string $email_message Email message text.
		 * @param int    $user_id       User ID.
		 * @param int    $post_id       Post ID.
		 */
		$email_setting['message'] = apply_filters( 'learndash_purchase_invoice_email_message', $email_setting['message'], $user_id, $post_id );
		if ( ! empty( $email_setting['message'] ) ) {
			$email_setting['message'] = learndash_emails_parse_placeholders( $email_setting['message'], $placeholders );
		}

		$transaction_meta = get_post_meta( $transaction_id, '', true );
		// remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_meta ) ) {
			unset( $transaction_meta['stripe_metadata'] );
		}

		$purchase_date = date_i18n( get_option( 'date_format' ), strtotime( $transaction_post->post_date ) ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $transaction_post->post_date ) );

		$pdf_data = array(
			'purchaser_name'   => learndash_emails_parse_placeholders( $email_setting['purchaser_name'], $placeholders ),
			'user_id'          => $user_id,
			'purchase_date'    => $purchase_date,
			'transaction_id'   => $transaction_id,
			'transaction_meta' => learndash_transaction_get_payment_meta( $transaction_id ),
			'vat_number'       => $email_setting['vat_number'],
			'company_name'     => $email_setting['company_name'],
			'company_address'  => $email_setting['company_address'],
			'company_logo'     => esc_url( wp_get_attachment_url( $email_setting['company_logo'] ) ),
			'logo_location'    => $email_setting['logo_location'],
			'filename'         => learndash_purchase_invoice_filename( $user_id, $post_id ),
			'filepath'         => learndash_purchase_invoice_filepath( $post_id ),
		);

		require_once __DIR__ . '/../ld-convert-post-pdf.php';

		$pdf = learndash_purchase_invoice_pdf(
			array(
				'pdf_data' => $pdf_data,
			)
		);

		if ( ! empty( $email_setting['subject'] ) && ( true === $pdf ) ) {
			learndash_emails_send(
				$user->user_email,
				$email_setting,
				'',
				array( $pdf_data['filepath'] . $pdf_data['filename'] )
			);
			update_post_meta( $transaction_id, 'purchase_invoice_filename', $pdf_data['filename'] );
		}
	}
}

if ( ! function_exists( 'learndash_transaction_add_learndash_version' ) ) {
	/**
	 * Saves current LD version as a transaction meta.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id Transaction ID.
	 *
	 * @return void
	 */
	function learndash_transaction_add_learndash_version( int $transaction_id ): void {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		update_post_meta( $transaction_id, 'learndash_version', LEARNDASH_VERSION );
	}
}

if ( ! function_exists( 'learndash_transaction_get_payment_meta' ) ) {
	/**
	 * Gets payment meta for transaction based upon payment processor.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id The ID of the transaction.
	 *
	 * @return array Array of transaction meta.
	 */
	function learndash_transaction_get_payment_meta( int $transaction_id = 0 ): array {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return array();
		}

		$transaction = get_post( $transaction_id );
		if ( ! $transaction || ! is_a( $transaction, 'WP_Post' ) ) {
			return array();
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return array();
		}

		$transaction_meta = get_post_meta( $transaction_id );

		$transaction_processor = '';
		// remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_meta ) ) {
			unset( $transaction_meta['stripe_metadata'] );
			// Stripe addon does not add this, so we force it here.
			$transaction_processor = 'stripe';
		} elseif ( ! empty( $transaction_meta['ld_payment_processor'] ) ) {
			$transaction_processor = $transaction_meta['ld_payment_processor'][0];
		}

		if ( true === array_key_exists( 'coupon', $transaction_meta ) ) {
			$meta = learndash_transaction_get_coupon_meta( $transaction_id );
		} else {
			if ( 'stripe' === $transaction_processor ) {
				$meta = learndash_transaction_get_stripe_meta( $transaction_id );
			} elseif ( 'paypal' === $transaction_processor ) {
				$meta = learndash_transaction_get_paypal_meta( $transaction_id );
			} elseif ( 'razorpay' === $transaction_processor ) {
				$meta = learndash_transaction_get_razorpay_meta( $transaction_id );
			} else {
				$meta = array();
			}
		}

		return $meta;
	}
}

if ( ! function_exists( 'learndash_transaction_get_razorpay_meta' ) ) {
	/**
	 * Gets meta for transaction related to RazorPay.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id The ID of the transaction.
	 *
	 * @return array Array of transaction meta.
	 */
	function learndash_transaction_get_razorpay_meta( int $transaction_id = 0 ): array {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return array();
		}

		$transaction = get_post( $transaction_id );
		if ( ( ! $transaction ) || ( ! is_a( $transaction, 'WP_Post' ) ) ) {
			return array();
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return array();
		}

		$transaction_meta = get_post_meta( $transaction_id );
		$pricing_meta     = get_post_meta( $transaction_id, 'pricing', true );

		$meta = array(
			'item_name'  => get_the_title( $transaction_meta['post_id'][0] ),
			'item_price' => learndash_get_price_formatted( $pricing_meta['price'], $pricing_meta['currency'] ),
		);

		if ( ! empty( $pricing_meta['pricing_billing_t3'] ) ) {
			switch ( $pricing_meta['pricing_billing_t3'] ) {
				case 'D':
					$pricing_meta['pricing_billing_t3'] = 'day';
					break;

				case 'W':
					$pricing_meta['pricing_billing_t3'] = 'week';
					break;

				case 'M':
					$pricing_meta['pricing_billing_t3'] = 'month';
					break;

				case 'Y':
					$pricing_meta['pricing_billing_t3'] = 'year';
					break;
			}
		}

		if ( ! empty( $pricing_meta['trial_duration_t1'] ) ) {
			switch ( $pricing_meta['trial_duration_t1'] ) {
				case 'D':
					$pricing_meta['trial_duration_t1'] = 'day';
					break;

				case 'W':
					$pricing_meta['trial_duration_t1'] = 'week';
					break;

				case 'M':
					$pricing_meta['trial_duration_t1'] = 'month';
					break;

				case 'Y':
					$pricing_meta['trial_duration_t1'] = 'year';
					break;
			}

			$subscribe_meta = array(
				'recurring_times' => $pricing_meta['no_of_cycles'],
				'duration_value'  => $pricing_meta['pricing_billing_p3'],
				'duration_length' => $pricing_meta['pricing_billing_t3'],
			);

			$meta = array_merge( $meta, $subscribe_meta );
		}

		if ( ! empty( $pricing_meta['trial_price'] ) ) {
			$trial_meta = array(
				'trial_price'           => learndash_get_price_formatted( $pricing_meta['trial_price'], $pricing_meta['currency'] ),
				'trial_duration_value'  => $pricing_meta['trial_duration_p1'],
				'trial_duration_length' => $pricing_meta['trial_duration_t1'],
			);

			$meta = array_merge( $meta, $trial_meta );
		}

		return $meta;
	}
}

if ( ! function_exists( 'learndash_transaction_get_paypal_meta' ) ) {
	/**
	 * Gets meta for transaction related to PayPal.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id The ID of the transaction.
	 *
	 * @return array Array of transaction meta.
	 */
	function learndash_transaction_get_paypal_meta( int $transaction_id = 0 ): array {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return array();
		}

		$transaction = get_post( $transaction_id );
		if ( ! $transaction || ! is_a( $transaction, 'WP_Post' ) ) {
			return array();
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return array();
		}

		$transaction_meta = get_post_meta( $transaction_id );
		$currency         = $transaction_meta['mc_currency'][0];

		$meta = array(
			'item_name'  => get_the_title( $transaction_meta['post_id'][0] ),
			'item_price' => learndash_get_price_formatted( $transaction_meta['mc_gross'][0], $currency ),
		);

		if ( 'subscr_payment' === $transaction_meta['txn_type'][0] ) {
			$duration_value  = $transaction_meta['period3'][0][0];
			$duration_length = $transaction_meta['period3'][0][2];
			$subscribe_meta  = array(
				'item_price'      => learndash_get_price_formatted( $transaction_meta['amount3'][0], $currency ),
				'recurring_times' => $transaction_meta['recur_times'][0],
				'duration_value'  => $duration_value,
				'duration_length' => $duration_length,
			);

			$meta = array_merge( $meta, $subscribe_meta );
		}

		if ( ! empty( $transaction_meta['trial_price'][0] ) ) {
			$trial_duration_value  = $transaction_meta['period1'][0][0];
			$trial_duration_length = $transaction_meta['period1'][0][2];
			$trial_meta            = array(
				'trial_price'           => learndash_get_price_formatted( $transaction_meta['trial_price'][0], $currency ),
				'trial_duration_value'  => $trial_duration_value,
				'trial_duration_length' => $trial_duration_length,
			);

			$meta = array_merge( $meta, $trial_meta );
		}

		return $meta;
	}
}

if ( ! function_exists( 'learndash_transaction_get_final_price' ) ) {
	/**
	 * Grabs the final price of the transaction.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id Post ID.
	 *
	 * @return int Actual price paid for the transaction.
	 */
	function learndash_transaction_get_final_price( int $transaction_id ): int {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return 0;
		}

		$transaction = get_post( $transaction_id );
		if ( ! $transaction || ! is_a( $transaction, 'WP_Post' ) ) {
			return 0;
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return 0;
		}

		$transaction_meta      = get_post_meta( $transaction_id );
		$transaction_processor = $transaction_meta[ Learndash_Transaction_Model::$meta_key_gateway_name ][0];
		// remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_meta ) ) {
			unset( $transaction_meta['stripe_metadata'] );
		}

		if ( 'stripe' === $transaction_processor ) {
			$final_price = $transaction_meta['stripe_price'][0];
		} elseif ( 'paypal' === $transaction_processor ) {
			$final_price = $transaction_meta['mc_gross'][0];
		} elseif ( 'razorpay' === $transaction_processor ) {
			$price       = json_decode( $transaction_meta['pricing'][0] );
			$final_price = $price->price;
		} else {
			$final_price = 0;
		}

		return $final_price;
	}
}

if ( ! function_exists( 'learndash_transaction_get_stripe_meta' ) ) {
	/**
	 * Gets meta for transaction related to Stripe.
	 *
	 * @since      4.2.0
	 * @deprecated 4.5.0
	 *
	 * @param int $transaction_id The ID of the transaction.
	 *
	 * @return array Array of transaction meta.
	 */
	function learndash_transaction_get_stripe_meta( int $transaction_id = 0 ): array {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return array();
		}

		$transaction = get_post( $transaction_id );
		if ( ! $transaction || ! is_a( $transaction, 'WP_Post' ) ) {
			return array();
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return array();
		}

		$transaction_meta = get_post_meta( $transaction_id );

		// remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_meta ) ) {
			unset( $transaction_meta['stripe_metadata'] );
		}

		$currency = $transaction_meta['stripe_currency'][0];

		$meta = array(
			'item_name'  => get_the_title( $transaction_meta['post_id'][0] ),
			'item_price' => learndash_get_price_formatted( $transaction_meta['stripe_price'][0], $currency ),
		);
		if ( ! empty( $transaction_meta['pricing_billing_p3'][0] ) ) {
			$duration_value  = $transaction_meta['pricing_billing_p3'][0];
			$duration_length = $transaction_meta['pricing_billing_t3'][0];
			$subscribe_meta  = array(
				'item_price'      => learndash_get_price_formatted( $transaction_meta['subscribe_price'][0], $currency ),
				'recurring_times' => $transaction_meta['no_of_cycles'][0],
				'duration_value'  => $duration_value,
				'duration_length' => $duration_length,
			);

			$meta = array_merge( $meta, $subscribe_meta );
		}
		if ( ! empty( $transaction_meta['trial_price'][0] ) ) {
			$trial_duration_value  = $transaction_meta['trial_duration_p1'][0];
			$trial_duration_length = $transaction_meta['trial_duration_t1'][0];
			$trial_meta            = array(
				'trial_price'           => learndash_get_price_formatted( $transaction_meta['trial_price'][0], $currency ),
				'trial_duration_value'  => $trial_duration_value,
				'trial_duration_length' => $trial_duration_length,
			);

			$meta = array_merge( $meta, $trial_meta );
		}

		return $meta;
	}
}

if ( ! function_exists( 'learndash_transaction_get_coupon_meta' ) ) {
	/**
	 * Gets meta for transaction if coupon was used.
	 *
	 * @since       4.2.0
	 * @deprecated  4.5.0
	 *
	 * @param int $transaction_id The ID of the transaction.
	 *
	 * @return array Array of transaction meta.
	 */
	function learndash_transaction_get_coupon_meta( int $transaction_id = 0 ): array {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.5.0' );
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return array();
		}

		$transaction = get_post( $transaction_id );
		if ( ! $transaction || ! is_a( $transaction, 'WP_Post' ) ) {
			return array();
		}

		if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction->post_type ) {
			return array();
		}

		$transaction_data = get_post_meta( $transaction_id );
		// remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_data ) ) {
			unset( $transaction_data['stripe_metadata'] );
		}

		$coupon_meta = $transaction_data['coupon'][0];
		$coupon_meta = unserialize( $coupon_meta ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$currency    = $coupon_meta['currency'];

		if ( empty( $coupon_meta ) ) {
			return array();
		}

		$discount = LEARNDASH_COUPON_TYPE_FLAT === $coupon_meta['type']
			? learndash_get_price_formatted( $coupon_meta['discount'], $currency )
			: $coupon_meta['amount'] . '%';

		return array(
			'item_name'             => get_the_title( $transaction_data['post_id'][0] ),
			'item_price'            => learndash_get_price_formatted( $coupon_meta['price'], $currency ),
			'item_coupon'           => $coupon_meta['code'],
			'item_discount'         => $discount,
			'item_discounted_price' => learndash_get_price_formatted( $coupon_meta['discounted_price'], $currency ),
		);
	}
}
