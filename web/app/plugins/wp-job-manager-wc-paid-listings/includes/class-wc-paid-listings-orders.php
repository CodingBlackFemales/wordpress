<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders
 */
class WC_Paid_Listings_Orders {

	/** @var object Class Instance */
	private static $instance;

	/**
	 * Get the class instance
	 *
	 * @return static
	 */
	public static function get_instance() {
		return null === self::$instance ? ( self::$instance = new self() ) : self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_thankyou', array( $this, 'woocommerce_thankyou' ), 5 );

		// Displaying user packages on the frontend
		add_action( 'woocommerce_before_my_account', array( $this, 'my_packages' ) );

		// Statuses
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'package_cancelled' ) );

		add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );

		// User deletion
		add_action( 'delete_user', array( $this, 'delete_user_packages' ) );

		// Disable repeat package purchase
		add_filter( 'wcpl_package_is_purchasable', array( $this, 'disable_repeat_purchase' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'disable_repeat_purchase' ), 10, 2 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'purchase_disabled_message' ), 31 );
	}

	/**
	 * Thanks page
	 *
	 * @param mixed $order_id
	 */
	public function woocommerce_thankyou( $order_id ) {
		global $wp_post_types;

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			if ( isset( $item['job_id'] ) && 'publish' === get_post_status( $item['job_id'] ) ) {

				foreach ( get_post_meta( $item['job_id'], '_renewal_completed_product_id' ) as $renewal_product_id ) {
					if ( (int) $renewal_product_id === $item['product_id'] ) {
						return;
					}
				}

				// translators: %s is the job title.
				$message  = wpautop( sprintf( __( '%s has been submitted successfully.', 'wp-job-manager-wc-paid-listings' ), get_the_title( $item['job_id'] ) ) );
				$message .= '<p class="job-manager-submitted-paid-listing-actions">
					<a class="button" href="' . get_permalink( $item['job_id'] ) . '">' . __( 'View Listing', 'wp-job-manager-wc-paid-listings' ) . '</a>
				</p>';

				echo wp_kses_post( $message );
			} elseif ( isset( $item['resume_id'] ) ) {
				$resume = get_post( $item['resume_id'] );

				switch ( get_post_status( $item['resume_id'] ) ) {
					case 'pending':
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once approved.', 'wp-job-manager-wc-paid-listings' ), get_the_title( $item['resume_id'] ) ) );
						break;
					case 'pending_payment':
					case 'expired':
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once payment has been confirmed.', 'wp-job-manager-wc-paid-listings' ), get_the_title( $item['resume_id'] ) ) );
						break;
					default:
						echo wpautop( sprintf( __( '%s has been submitted successfully.', 'wp-job-manager-wc-paid-listings' ), get_the_title( $item['resume_id'] ) ) );
						break;
				}

				echo '<p class="job-manager-submitted-paid-listing-actions">';

				if ( 'publish' === get_post_status( $item['resume_id'] ) ) {
					echo '<a class="button" href="' . get_permalink( $item['resume_id'] ) . '">' . __( 'View Listing', 'wp-job-manager-wc-paid-listings' ) . '</a> ';
				} elseif ( get_option( 'resume_manager_candidate_dashboard_page_id' ) ) {
					echo '<a class="button" href="' . get_permalink( get_option( 'resume_manager_candidate_dashboard_page_id' ) ) . '">' . __( 'View Dashboard', 'wp-job-manager-wc-paid-listings' ) . '</a> ';
				}

				if ( ! empty( $resume->_applying_for_job_id ) ) {
					echo '<a class="button" href="' . get_permalink( absint( $resume->_applying_for_job_id ) ) . '">' . sprintf( __( 'Apply for "%s"', 'wp-job-manager-wc-paid-listings' ), get_the_title( absint( $resume->_applying_for_job_id ) ) ) . '</a> ';
				}

				echo '</p>';
			}
		}
	}

	/**
	 * Show my packages
	 */
	public function my_packages() {
		if ( ( $packages = wc_paid_listings_get_user_packages( get_current_user_id(), 'job_listing' ) ) && is_array( $packages ) && sizeof( $packages ) > 0 ) {
			wc_get_template(
				'my-packages.php',
				array(
					'packages' => $packages,
					'type'     => 'job_listing',
				),
				'wc-paid-listings/',
				JOB_MANAGER_WCPL_TEMPLATE_PATH
			);
		}
		if ( ( $packages = wc_paid_listings_get_user_packages( get_current_user_id(), 'resume' ) ) && is_array( $packages ) && sizeof( $packages ) > 0 ) {
			wc_get_template(
				'my-packages.php',
				array(
					'packages' => $packages,
					'type'     => 'resume',
				),
				'wc-paid-listings/',
				JOB_MANAGER_WCPL_TEMPLATE_PATH
			);
		}
	}

	/**
	 * Triggered when an order is paid
	 *
	 * @param  int $order_id
	 */
	public function order_paid( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		/**
		 * Filters whether to approve/publish orders with the 'processing' status.
		 * This is useful if you want only completed orders to be published or for other payment methods.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $approve Whether to approve orders with the processing status.
		 * @param WC_Order $order The order object that was processed.
		 */
		if ( $order->get_status() === 'processing' && ! apply_filters( 'wcpl_approve_processing_orders', true, $order ) ) {
			return;
		}

		if ( get_post_meta( $order_id, 'wc_paid_listings_packages_processed', true ) ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );

			if ( $product->is_type( array( 'job_package', 'resume_package' ) ) && wc_paid_listings_get_order_customer_id( $order ) ) {

				// Give packages to user
				$user_package_id = false;
				for ( $i = 0; $i < $item['qty']; $i ++ ) {
					$user_package_id = wc_paid_listings_give_user_package( wc_paid_listings_get_order_customer_id( $order ), $product->get_id(), $order_id );
				}

				$this->attach_package_listings( $item, $order, $user_package_id );
			}
		}

		update_post_meta( $order_id, 'wc_paid_listings_packages_processed', true );
	}

	/**
	 * Delete packages on user deletion
	 *
	 * @param mixed $user_id
	 */
	public function delete_user_packages( $user_id ) {
		global $wpdb;

		if ( $user_id ) {
			$wpdb->delete(
				"{$wpdb->prefix}wcpl_user_packages",
				array(
					'user_id' => $user_id,
				)
			);
		}
	}

	/**
	 * Handles the tasks after the restoration of orders and job listing or resume posts.
	 *
	 * @param int $post_id
	 */
	public function untrash_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		switch ( $post_type ) {
			case 'shop_order':
				$this->untrash_shop_order( $post_id );
				break;
			case 'job_listing':
			case 'resume':
				$this->untrash_wpjm_post( $post_id );
				break;
		}
	}

	/**
	 * Handles the tasks after a job listing or resume post is restored.
	 *
	 * @param int $post_id
	 */
	public function untrash_wpjm_post( $post_id ) {
		$product_id      = get_post_meta( $post_id, '_package_id', true );
		$user_package_id = get_post_meta( $post_id, '_user_package_id', true );
		$product         = wc_get_product( $product_id );
		$user_package    = wc_paid_listings_get_user_package( $user_package_id );
		$order           = false;
		if ( $user_package && $user_package->has_package() ) {
			$order = wc_get_order( $user_package->get_order_id() );
		}
		if ( $order && $product && $product->is_type( array( 'job_package', 'resume_package' ) ) ) {
			if ( $this->should_increment_package_count_on_untrash( $post_id ) ) {
				wc_paid_listings_increase_package_count( $order->get_user_id(), $user_package_id );
				delete_post_meta( $post_id, 'job_manager_decremented_package_count_on_trash' );
			}
		}
	}

	/**
	 * Handles tasks after a WC order is restored.
	 *
	 * @param int $order_id
	 */
	public function untrash_shop_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			/**
			 * @var WC_Order_Item_Product $item
			 */
			$product = $item->get_product();
			if ( $product->is_type( array( 'job_package', 'resume_package' ) ) && $order->get_user_id() ) {
				$user_package_id = null;
				// Give packages to user
				for ( $i = 0; $i < $item['qty']; $i ++ ) {
					$user_package_id = wc_paid_listings_give_user_package( $order->get_user_id(), $product->get_id(), wc_paid_listings_get_order_id( $order ) );
				}
				$this->attach_package_listings( $item, $order, $user_package_id );
			}
		}
	}

	/**
	 * Attached listings to the user package.
	 *
	 * @param WC_Order_Item $item
	 * @param WC_Order      $order
	 * @param int           $user_package_id
	 */
	private function attach_package_listings( $item, $order, $user_package_id ) {
		global $wpdb;

		$listing_ids   = (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_cancelled_package_order_id', wc_paid_listings_get_order_id( $order ) ) );
		$listing_ids[] = $item['job_id'] ?? '';
		$listing_ids[] = $item['resume_id'] ?? '';
		$listing_ids   = array_unique( array_filter( array_map( 'absint', $listing_ids ) ) );
		foreach ( $listing_ids as $listing_id ) {
			if ( in_array( get_post_status( $listing_id ), array( 'pending_payment', 'expired' ), true ) ) {
				wc_paid_listings_approve_listing_with_package( $listing_id, $order->get_user_id(), $user_package_id );
				delete_post_meta( $listing_id, '_cancelled_package_order_id' );
			}

			foreach ( get_post_meta( $listing_id, '_renewal_pending_product_id' ) as $renewal_product_id ) {
				if ( (int) $renewal_product_id === $item['product_id'] ) {
					wc_paid_listings_handle_listing_renewal( $item['product_id'], $user_package_id, $listing_id );
					break;
				}
			}
		}
	}

	/**
	 * Handles the tasks after WC orders and WPJM related posts are trashed.
	 *
	 * @param int $post_id
	 */
	public function wp_trash_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		switch ( $post_type ) {
			case 'shop_order':
				$this->trash_shop_order( $post_id );
				break;
			case 'job_listing':
			case 'resume':
				$this->trash_wpjm_post( $post_id );
				break;
		}
	}

	/**
	 * Handles tasks after a job listing or resume post is trashed.
	 *
	 * @param int $post_id
	 */
	public function trash_wpjm_post( $post_id ) {
		$product_id      = get_post_meta( $post_id, '_package_id', true );
		$user_package_id = get_post_meta( $post_id, '_user_package_id', true );
		$product         = wc_get_product( $product_id );
		$user_package    = wc_paid_listings_get_user_package( $user_package_id );
		$order           = false;
		if ( $user_package && $user_package->has_package() ) {
			$order = wc_get_order( $user_package->get_order_id() );
		}
		if ( $order && $product && $product->is_type( array( 'job_package', 'resume_package' ) ) ) {
			if ( $this->should_decrement_package_count_on_trash( $post_id ) ) {
				add_post_meta( $post_id, 'job_manager_decremented_package_count_on_trash', true );
				wc_paid_listings_decrease_package_count( $order->get_user_id(), $user_package_id );
			}
		}
	}

	/**
	 * If a listing gets trashed/deleted, the pack may need it's listing count changing
	 *
	 * @param int $order_id
	 */
	public function trash_shop_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product || ( ! $product->is_type( array( 'job_package', 'resume_package' ) ) && $order->get_user_id() ) ) {
				continue;
			}
			$this->delete_package( $order_id, $product->get_id() );
		}
	}

	/**
	 * Determine whether trashing a post should decrement the package count.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	private function should_decrement_package_count_on_trash( $post_id ) {
		/** This filter is documented in includes/package-functions.php */
		$job_listing_affects_package_count = apply_filters( 'job_manager_job_listing_affects_package_count', true, $post_id );

		/**
		 * Sets the allowed deletion period, in days, for a paid job
		 * listing. If the listing's age is within this period, deleting the
		 * listing will free up its slot in the paid package.
		 *
		 * @since 2.8.2
		 *
		 * @param bool $job_listing_allowed_deletion_period The allowed deletion period in days. Default is 2 days.
		 * @param int  $listing_id                          Post ID.
		 */
		$allowed_deletion_period = apply_filters( 'job_manager_job_listing_allowed_deletion_period', 2 );
		$end_of_period           = get_post_time( 'U', false, $post_id ) + $allowed_deletion_period * DAY_IN_SECONDS;

		return $job_listing_affects_package_count && current_time( 'timestamp' ) < $end_of_period;
	}

	/**
	 * Determine whether untrashing a post should increment the package count.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	private function should_increment_package_count_on_untrash( $post_id ) {
		/** This filter is documented in includes/package-functions.php */
		$job_listing_affects_package_count = apply_filters( 'job_manager_job_listing_affects_package_count', true, $post_id );

		$decremented = get_post_meta( $post_id, 'job_manager_decremented_package_count_on_trash', true );

		return $job_listing_affects_package_count && $decremented;
	}

	/**
	 * Fires when a order was canceled. Looks for Job Packages in order and deletes the package if found.
	 *
	 * @param $order_id
	 */
	public function package_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product->is_type( array( 'job_package', 'resume_package' ) ) ) {
				continue;
			}
			$this->delete_package( $order_id, $product->get_id() );
		}
	}

	/**
	 * Deletes a package.
	 *
	 * @param int $order_id
	 * @param int $product_id
	 */
	private function delete_package( $order_id, $product_id ) {
		global $wpdb;

		$user_package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcpl_user_packages WHERE order_id = %d AND product_id = %d;", $order_id, $product_id ) );
		if ( $user_package ) {
			// Delete the package
			$wpdb->delete(
				"{$wpdb->prefix}wcpl_user_packages",
				array(
					'id' => $user_package->id,
				)
			);

			$listing_ids = wc_paid_listings_get_listings_for_package( $user_package->id );
			foreach ( $listing_ids as $listing_id ) {
				$original_status = get_post_status( $listing_id );
				$listing         = array(
					'ID'          => $listing_id,
					'post_status' => 'expired',
				);
				wp_update_post( $listing );

				// Make a record of the order ID and original status in case of re-activation
				update_post_meta( $listing_id, '_cancelled_package_order_id', $order_id );
				update_post_meta( $listing_id, '_post_status_before_package_pause', $original_status );
			}
		}
	}

	/**
	 * Disables repeat purchase for packages.
	 *
	 * @param  mixed $purchasable
	 * @param  mixed $product
	 * @return void
	 */
	public function disable_repeat_purchase( $purchasable, $product ) {
		if ( ! $product->is_type( array( 'job_package', 'resume_package' ) ) ) {
			return $purchasable;
		}

		if ( $product->get_meta( '_wcpl_disable_repurchase' ) !== 'yes' ) {
			return $purchasable;
		}

		if ( wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $product->get_id() ) ) {
			$purchasable = false;
		}

		return $purchasable;
	}

	/**
	 * "Purchase disabled" message if package has been purchased.
	 *
	 * @return void
	 */
	public function purchase_disabled_message() {
		global $product;

		if ( ! $product->is_type( array( 'job_package', 'resume_package' ) ) ) {
			return false;
		}

		if ( $product->get_meta( '_wcpl_disable_repurchase' ) !== 'yes' ) {
			return false;
		}

		if ( wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $product->get_id() ) ) {
			printf(
				'<div class="woocommerce"><div class="woocommerce-info wc-nonpurchasable-message">%s</div></div>',
				esc_html__( 'You\'ve already purchased this product! It can only be purchased once.', 'wp-job-manager-wc-paid-listings' )
			);
		}
	}
}
WC_Paid_Listings_Orders::get_instance();
