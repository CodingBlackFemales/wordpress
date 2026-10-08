<?php
/**
 * Retroactive tool class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Modules\Retroactive_Access_Tool;

use Automattic\WooCommerce\Admin\Overrides\OrderRefund;
use LearnDash\WooCommerce\Settings\Status_Access;
use Learndash_WooCommerce;
use WC_Order;
use WC_Subscription;

/**
 * Retroactive tool class.
 *
 * @since 5.2.0
 */
class Handler {
	/**
	 * The number of orders to process per batch. Default 100.
	 *
	 * @since 5.2.0
	 *
	 * @var int
	 */
	private const PER_BATCH = 100;

	/**
	 * The action key for the retroactive access tool.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const ACTION_KEY = 'learndash_woocommerce_retroactive_access_tool';

	/**
	 * The option key for the retroactive access tool status.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const STATUS_OPTION_KEY = 'learndash_woocommerce_retroactive_access_tool_is_running';

	/**
	 * The option key for the last run timestamp.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const LAST_RUN_OPTION_KEY = 'learndash_woocommerce_retroactive_access_tool_last_run';

	/**
	 * Adds retroactive tool button for LearnDash WooCommerce.
	 *
	 * @since 5.2.0
	 *
	 * @param array<string, array<string, mixed>> $tools Existing tools.
	 *
	 * @return array<string, array<string, mixed>> New tools
	 */
	public function add_tool( $tools ) {
		$tools['learndash_retroactive_access_tool'] = [
			'name'     => __( 'Give LearnDash Students Retroactive Access', 'learndash' ),
			'button'   => __( 'Run', 'learndash' ),
			'desc'     => sprintf(
				// translators: %1$s and %2$s are replaced with the custom labels for courses and groups.
				__( 'Grant or deny LearnDash students %1$s and %2$s access, according to WooCommerce order and subscription status.', 'learndash' ),
				learndash_get_custom_label_lower( 'courses' ),
				learndash_get_custom_label_lower( 'groups' )
			),
			'callback' => function () {
				$status = get_option( self::STATUS_OPTION_KEY );

				if ( $status === false ) {
					$this->init();

					return esc_html__( 'The LearnDash retroactive access tool has been initialized and currently is running in the background.', 'learndash' );
				}

				return esc_html__( 'Another LearnDash retroactive access tool process is already running.', 'learndash' );
			},
		];

		return $tools;
	}

	/**
	 * Handles the tool initialization request.
	 *
	 * The method should register `learndash_woocommerce_retroactive_access_tool` action using action scheduler
	 * functions or methods at some point.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->schedule_batch( 1 );
		update_option( self::STATUS_OPTION_KEY, true );
	}

	/**
	 * Handles the tool end request.
	 *
	 * The method should delete the retroactive access tool status option.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	private function end(): void {
		update_option( self::LAST_RUN_OPTION_KEY, time() );
		delete_option( self::STATUS_OPTION_KEY );
	}

	/**
	 * Runs the retroactive access tool batch.
	 *
	 * The method is hooked to the `learndash_woocommerce_retroactive_access_tool` action which is registered
	 * using action scheduler functions.
	 *
	 * @since 5.2.0
	 *
	 * @param int    $batch The batch number.
	 * @param string $type  The type of the batch. 'order' or 'subscription'. Default 'order'.
	 *
	 * @return void
	 */
	public function run_batch( int $batch, string $type ): void {
		if ( $type === 'order' ) {
			$this->run_order_batch( $batch );
		} elseif ( $type === 'subscription' ) {
			$this->run_subscription_batch( $batch );
		}
	}

	/**
	 * Checks if an order should be skipped during the retroactive access tool batch process.
	 *
	 * @since 5.2.0
	 *
	 * @param object $order WooCommerce order object.
	 *
	 * @return bool
	 */
	private function should_skip_order( object $order ): bool {
		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		}

		// Workaround for WC_Order_Refund because wcs_order_contains_subscription() only accepts WC_Order object or ID.
		if (
			(
				is_a( $order, 'WC_Order' )
				&& $order->get_status() === 'refunded'
			)
			|| is_a( $order, 'WC_Order_Refund' )
			|| is_a( $order, OrderRefund::class )
		) {
			Learndash_WooCommerce::remove_course_access( $order->get_id() );
			return true;
		}

		return wcs_order_contains_subscription( $order, 'any' );
	}

	/**
	 * Processes the orders during the retroactive access tool batch.
	 *
	 * @since 5.2.0
	 *
	 * @param WC_Order[] $orders Orders to process.
	 *
	 * @return void
	 */
	private function process_orders( array $orders ): void {
		$granted_order_statuses = Status_Access::get_access_granted_order_statuses();
		$denied_order_statuses  = Status_Access::get_access_denied_order_statuses();

		foreach ( $orders as $order ) {
			$status   = $order->get_status();
			$order_id = $order->get_id();

			if ( $this->should_skip_order( $order ) ) {
				continue;
			}

			if ( in_array( $status, array_keys( $granted_order_statuses ), true ) ) {
				Learndash_WooCommerce::add_course_access( $order_id );
			}

			if ( in_array( $status, array_keys( $denied_order_statuses ), true ) ) {
				Learndash_WooCommerce::remove_course_access( $order_id );
			}

			/**
			 * Partial refund order still can have a status of `completed` or other status, so the order status
			 * won't match with the status of the denied access settings. We check all orders if it has a partial
			 * refund.
			 */
			Learndash_WooCommerce::remove_course_access_on_refund( $order_id, 0 );
		}
	}

	/**
	 * Processes the subscriptions during the retroactive access tool batch.
	 *
	 * @since 5.2.0
	 *
	 * @param WC_Subscription[] $subscriptions Subscriptions to process.
	 *
	 * @return void
	 */
	private function process_subscriptions( array $subscriptions ): void {
		$granted_subscription_statuses = Status_Access::get_access_granted_subscription_statuses();
		$denied_subscription_statuses  = Status_Access::get_access_denied_subscription_statuses();

		foreach ( $subscriptions as $subscription ) {
			$subscription_status = $subscription->get_status();

			if ( in_array( $subscription_status, array_keys( $granted_subscription_statuses ), true ) ) {
				Learndash_WooCommerce::add_subscription_course_access( $subscription );
			}

			foreach ( $denied_subscription_statuses as $denied_subscription_status => $label ) {
				if (
					$subscription_status === $denied_subscription_status
					&& $subscription_status === 'expired'
					&& Status_Access::is_access_removal_disabled_on_expiration()
				) {
					continue;
				}

				if ( $subscription_status !== $denied_subscription_status ) {
					continue;
				}

				Learndash_WooCommerce::remove_subscription_course_access( $subscription );
			}
		}
	}

	/**
	 * Schedules the process batch number.
	 *
	 * @since 5.2.0
	 *
	 * @param int    $batch The batch number.
	 * @param string $type  The type of the batch. 'order' or 'subscription'. Default 'order'.
	 *
	 * @return void
	 */
	private function schedule_batch( int $batch, string $type = 'order' ): void {
		as_schedule_single_action(
			time(),
			self::ACTION_KEY,
			[
				$batch,
				$type,
			]
		);
	}

	/**
	 * Run order batch.
	 *
	 * @since 5.2.0
	 *
	 * @param int $batch The current batch to process.
	 *
	 * @return void
	 */
	private function run_order_batch( int $batch ): void {
		$per_batch = $this->get_per_batch();
		$offset    = ( $batch - 1 ) * $per_batch;

		/**
		 * Get orders.
		 *
		 * @var WC_Order[] $orders Orders to process.
		 */
		$orders = wc_get_orders(
			[
				'limit'  => $per_batch,
				'offset' => $offset,
				'order'  => 'ASC',
			]
		);

		if ( empty( $orders ) ) {
			// If there are no orders left, we process subscriptions batches next.
			$this->schedule_batch( 1, 'subscription' );
			return;
		}

		$this->process_orders( $orders );
		$this->schedule_batch( $batch + 1, 'order' );
	}

	/**
	 * Run subscription batch.
	 *
	 * @since 5.2.0
	 *
	 * @param int $batch The current batch to process.
	 *
	 * @return void
	 */
	private function run_subscription_batch( int $batch ): void {
		$per_batch = $this->get_per_batch();
		$offset    = ( $batch - 1 ) * $per_batch;

		$subscriptions = [];

		if ( function_exists( 'wcs_get_subscriptions' ) ) {
			$subscriptions = wcs_get_subscriptions(
				[
					'subscriptions_per_page' => $per_batch,
					'offset'                 => $offset,
					'order'                  => 'ASC',
				]
			);
		}

		if ( empty( $subscriptions ) ) {
			// Finish and update the retroactive access tool status.
			$this->end();
			return;
		}

		$this->process_subscriptions( $subscriptions );
		$this->schedule_batch( $batch + 1, 'subscription' );
	}

	/**
	 * Get number of orders or subscriptions to process per batch.
	 *
	 * @since 5.2.0
	 *
	 * @return int
	 */
	private function get_per_batch(): int {
		/**
		 * Filters the number of orders to process per batch.
		 *
		 * Kept for backward compatibility.
		 *
		 * @since 5.2.0
		 * @deprecated 5.2.0 Use `learndash_woocommerce_retroactive_access_tool_per_batch` instead.
		 *
		 * @param int $per_batch The number of orders to process per batch. Default 10.
		 *
		 * @return int The number of orders to process per batch.
		 */
		$per_batch = apply_filters_deprecated( 'learndash_woocommerce_retroactive_tool_per_batch', [ self::PER_BATCH ], '2.0.0', 'learndash_woocommerce_retroactive_access_tool_per_batch' );

		/**
		 * Filters the number of orders to process per batch.
		 *
		 * @since 5.2.0
		 *
		 * @param int $per_batch The number of orders to process per batch. Default 10.
		 *
		 * @return int The number of orders to process per batch.
		 */
		return apply_filters( 'learndash_woocommerce_retroactive_access_tool_per_batch', $per_batch );
	}
}
