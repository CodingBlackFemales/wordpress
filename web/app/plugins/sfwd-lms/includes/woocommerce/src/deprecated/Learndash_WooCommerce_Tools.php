<?php
/**
 * Deprecated LearnDash WooCommerce tools class file.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Automattic\WooCommerce\Admin\Overrides\OrderRefund;
use LearnDash\WooCommerce\Settings\Status_Access;

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html(
		LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/App/Modules/Retroactive_Access_Tool/Handler.php'
	)
);

/**
 * Deprecated tools class.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 */
class Learndash_WooCommerce_Tools {
	/**
	 * Hook functions.
	 *
	 * @deprecated 5.2.0
	 */
	public function __construct() {
		_deprecated_constructor( __CLASS__, '2.0.0' );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_ld_wc_retroactive_access', array( $this, 'ajax_retroactive_access' ) );

		add_filter( 'woocommerce_debug_tools', array( $this, 'course_retroactive_access_tool' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @deprecated 5.2.0
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		_deprecated_function( __METHOD__, '2.0.0' );

		$screen = get_current_screen();

		$prefix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if (
			$screen->id === 'woocommerce_page_wc-status'
			&& isset( $_GET['tab'] )
			&& $_GET['tab'] === 'tools'
		) {
			wp_enqueue_style( 'ld-wc-tools', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'assets/css/tools' . $prefix . '.css', array(), LEARNDASH_SCRIPT_VERSION_TOKEN );

			wp_enqueue_script( 'ld-wc-tools', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'assets/js/tools' . $prefix . '.js', array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );

			wp_localize_script(
				'ld-wc-tools',
				'LD_WC_Tools_Params',
				array(
					'text'       => array(
						'status'             => __( 'Status', 'learndash' ),
						'complete'           => __( 'Complete', 'learndash' ),
						'keep_page_open'     => __( 'Please keep this page open until the process is complete.', 'learndash' ),
						'retroactive_button' => __( 'Check LearnDash course access', 'learndash' ),
					),
					'nonce'      => array(
						'retroactive_access' => wp_create_nonce( 'ld_wc_retroactive_access' ),
					),
					'wc_version' => WC_VERSION,
				)
			);
		}
	}

	/**
	 * AJAX hook for processing retroactive tool
	 *
	 * @deprecated 5.2.0
	 *
	 * @return void
	 */
	public function ajax_retroactive_access() {
		_deprecated_function( __METHOD__, '2.0.0' );

		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die();
		}

		if ( ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash(
					$_POST['nonce']
				)
			),
			'ld_wc_retroactive_access'
		) ) {
			wp_die();
		}

		$step = intval( sanitize_text_field( wp_unslash( $_POST['step'] ?? 1 ) ) );

		$per_batch = apply_filters_deprecated( 'learndash_woocommerce_retroactive_tool_per_batch', [ 10 ], '2.0.0', 'learndash_woocommerce_retroactive_access_tool_per_batch' );
		$offset    = ( $step - 1 ) * $per_batch;

		$order_count_args = [
			'limit' => -1,
		];

		$order_total = count( wc_get_orders( $order_count_args ) );
		$total       = $order_total;

		// Process orders.

		$orders = wc_get_orders(
			array(
				'limit'  => $per_batch,
				'offset' => $offset,
				'order'  => 'ASC',
			)
		);

		foreach ( $orders as $order ) {
			$status = $order->get_status();
			$id     = $order->get_id();

			// skip order that is part of subscription.
			if ( function_exists( 'wcs_order_contains_subscription' ) ) {
				// Workaround for WC_Order_Refund because wcs_order_contains_subscription() only accepts WC_Order object or ID.
				if ( ( 'refunded' === $status && is_a( $order, 'WC_Order' ) ) || is_a( $order, 'WC_Order_Refund' ) || is_a( $order, OrderRefund::class ) ) {
					Learndash_WooCommerce::remove_course_access( $id );
					continue;
				}

				if ( wcs_order_contains_subscription( $order, 'any' ) ) {
					continue;
				}
			}

			switch ( $status ) {
				case 'completed':
				case 'processing':
					Learndash_WooCommerce::add_course_access( $id );
					break;

				case 'pending':
				case 'on-hold':
				case 'cancelled':
				case 'refunded':
				case 'failed':
					Learndash_WooCommerce::remove_course_access( $id );
					break;
			}
		}

		// Process subscriptions only after orders processing is completed.

		$subscriptions = array();

		if ( empty( $orders ) ) {
			// Process subscriptions.
			if ( function_exists( 'wcs_get_subscriptions' ) ) {
				if ( ! empty( $_POST['subscription_step'] ) && ! empty( $_POST['last_order_step'] ) ) {
					$last_order_step   = intval( sanitize_text_field( wp_unslash( $_POST['last_order_step'] ) ) );
					$subscription_step = intval( sanitize_text_field( wp_unslash( $_POST['subscription_step'] ) ) );
				} else {
					$last_order_step   = $step - 1;
					$subscription_step = 1;
				}

				$subscription_count_args = [
					'subscriptions_per_page' => -1,
				];

				$subscription_total  = count( wcs_get_subscriptions( $subscription_count_args ) );
				$total              += $subscription_total;
				$subscription_offset = ( $subscription_step - 1 ) * $per_batch;

				// Get subscriptions.
				$subscriptions = wcs_get_subscriptions(
					array(
						'subscriptions_per_page' => $per_batch,
						'offset'                 => $subscription_offset,
						'order'                  => 'ASC',
					)
				);

				foreach ( $subscriptions as $subscription ) {
					$status = $subscription->get_status();
					$id     = $subscription->get_id();

					switch ( $status ) {
						case 'active':
						case 'pending-cancel':
							Learndash_WooCommerce::add_subscription_course_access( $subscription );
							break;

						case 'cancelled':
						case 'on-hold':
							Learndash_WooCommerce::remove_subscription_course_access( $subscription );
							break;

						case 'expired':
							if ( ! Status_Access::is_access_removal_disabled_on_expiration() ) {
								Learndash_WooCommerce::remove_subscription_course_access( $subscription );
							} else {
								Learndash_WooCommerce::add_subscription_course_access( $subscription );
							}
							break;
					}
				}
			}
		}

		if ( ! empty( $orders ) || ! empty( $subscriptions ) ) {
			$last_order_step   = isset( $last_order_step ) && ! empty( $last_order_step ) ? $last_order_step : 0;
			$subscription_step = isset( $subscription_step ) && ! empty( $subscription_step ) ? $subscription_step : 0;

			$order_offset        = empty( $last_order_step ) ? ( $step - 1 ) * $per_batch : ( $last_order_step - 1 ) * $per_batch;
			$subscription_offset = ! empty( $subscription_step ) ? ( $subscription_step - 1 ) * $per_batch : 0;
			$offset              = $order_offset + $subscription_offset;

			$percentage = number_format( ( ( $offset + $per_batch ) / $total ) * 100, 0 );
			$percentage = $percentage > 100 ? 100 : $percentage;

			$returned_subscription_step = $subscription_step > 0 ? $subscription_step + 1 : 0;

			$return = array(
				'step'              => intval( $step + 1 ),
				'last_order_step'   => intval( $last_order_step ),
				'subscription_step' => intval( $returned_subscription_step ),
				'percentage'        => intval( $percentage ),
			);
		} else {
			// done.
			$return = array(
				'step' => 'complete',
			);
		}

		echo wp_json_encode( $return );

		wp_die();
	}

	/**
	 * Get the latest option
	 *
	 * @deprecated 5.2.0
	 *
	 * @return array Addon settings
	 */
	public function get_options() {
		_deprecated_function( __METHOD__, '2.0.0' );

		wp_cache_delete( 'learndash_woocommerce_settings', 'options' );
		$options = get_option( 'learndash_woocommerce_settings', array() );

		return $options;
	}

	/**
	 * Add tools button for LD WooCommerce
	 *
	 * @deprecated 5.2.0
	 *
	 * @param  array $tools Existing tools.
	 * @return array         New tools
	 */
	public function course_retroactive_access_tool( $tools ) {
		_deprecated_function( __METHOD__, '2.0.0' );

		$tools['learndash_retroactive_access'] = array(
			'name'     => __( 'LearnDash retroactive course access', 'learndash' ),
			'button'   => __( 'Check LearnDash course access', 'learndash' ),
			'desc'     => __( 'Check LearnDash course access of WooCommerce integration. Enroll and unenroll users according to WooCommerce purchase/subscription data.', 'learndash' ),
			'callback' => function() {},
		);

		return $tools;
	}
}
