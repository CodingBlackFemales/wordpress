<?php
/**
 * WooCommerce cron class
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Cron class
 */
class Learndash_WooCommerce_Cron {
	/**
	 * Hooks cron schedule and registration.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'cron_schedules', [ $this, 'add_cron_schedule' ] );
		add_action( 'admin_init', [ $this, 'register_cron' ] );
	}

	/**
	 * Add cron schedule
	 *
	 * @param array $schedules Cron schedules.
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['per_minute'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => __( 'Once per Minute', 'learndash' ),
		);

		return $schedules;
	}

	/**
	 * Register cron hook
	 */
	public function register_cron() {
		if ( ! wp_next_scheduled( 'learndash_woocommerce_cron' ) ) {
			wp_schedule_event( time(), 'per_minute', 'learndash_woocommerce_cron' );
		}
	}

	/**
	 * Deregister cron hook.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function deregister_hook() {
		wp_clear_scheduled_hook( 'learndash_woocommerce_cron' );
	}
}

new Learndash_WooCommerce_Cron();
