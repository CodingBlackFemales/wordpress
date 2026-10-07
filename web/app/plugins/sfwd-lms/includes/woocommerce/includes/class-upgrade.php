<?php
/**
 * Upgrade class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade class.
 *
 * @since 5.2.0
 */
class Learndash_Woocommerce_Upgrade {
	/**
	 * Initialize the class.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function init() {
		// The hook must be plugins_loaded above priority 50 because it's where the plugin is initialized.
		add_action( 'plugins_loaded', [ __CLASS__, 'check_upgrade' ], 100 );
	}

	/**
	 * Check if an upgrade is needed. If so, call the upgrade method.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function check_upgrade() {
		/**
		 * Saved version.
		 *
		 * @var string|false $saved_version The saved version.
		 */
		$saved_version = get_option( 'learndash_woocommerce_version', false );

		/*
		 * The bundled add-on has no version of its own to move forward, so the pass runs once per LearnDash release.
		 * A site coming from a standalone copy has an add-on version stored, which is behind every LearnDash version
		 * and so takes the migrations below on the first load after the upgrade, exactly as an add-on bump did.
		 */
		$current_version = LEARNDASH_VERSION;

		if (
			$saved_version === false
			|| (
				$saved_version !== false
				&& version_compare( $saved_version, $current_version, '<' )
			)
		) {
			self::upgrade( $saved_version, $current_version );
		}
	}

	/**
	 * Upgrade.
	 *
	 * @since 5.2.0
	 *
	 * @param string|false $from_version The version we are upgrading from.
	 * @param string       $to_version   The version we are upgrading to.
	 *
	 * @return void
	 */
	public static function upgrade( $from_version, $to_version ) {
		if (
			(
				$from_version === false
				|| $from_version <= '1.8.0.6'
			)
			&& $to_version >= '1.8.0.7'
		) {
			$queue = get_option( 'learndash_woocommerce_silent_course_enrollment_queue', [] );
			// Delete first so autoload value can be updated in DB.
			delete_option( 'learndash_woocommerce_silent_course_enrollment_queue' );

			update_option( 'learndash_woocommerce_silent_course_enrollment_queue', $queue, false );
		}

		if (
			(
				$from_version === false
				|| version_compare( $from_version, '2.0.0.1-dev', '<' )
			)
			&& version_compare( $to_version, '2.0.0.1-dev', '>=' )
		) {
			$settings_key = 'learndash_woocommerce_enrollment_status_settings';
			$settings     = get_option( $settings_key, [] );

			if ( ! is_array( $settings ) ) {
				return;
			}

			// Set default values if they don't exist.

			if ( ! isset( $settings['order_wc-processing'] ) ) {
				$settings['order_wc-processing'] = 'on';
			}

			if ( ! isset( $settings['order_wc-completed'] ) ) {
				$settings['order_wc-completed'] = 'on';
			}

			if ( ! isset( $settings['subscription_wc-active'] ) ) {
				$settings['subscription_wc-active'] = 'on';
			}

			if ( ! isset( $settings['subscription_wc-pending-cancel'] ) ) {
				$settings['subscription_wc-pending-cancel'] = 'on';
			}

			update_option( $settings_key, $settings );
		}

		update_option( 'learndash_woocommerce_version', $to_version, true );
	}
}

Learndash_Woocommerce_Upgrade::init();
