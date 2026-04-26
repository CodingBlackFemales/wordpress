<?php
/**
 * Deprecated functions from LD 4.18.0.
 * The functions will be removed in a later version.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Deprecated
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_hub_install' ) ) {
	/**
	 * LearnDash Hub Activate/Install function.
	 *
	 * @since 4.18.0
	 * @deprecated 4.18.0 -- This function was never used.
	 *
	 * @param bool|null $network_wide Whether this is a network-wide installation. Defaults to null.
	 *
	 * @return void
	 */
	function learndash_hub_install( $network_wide = null ) {
		_deprecated_function( __METHOD__, '4.18.0' );
	}
}

if ( ! function_exists( 'learndash_hub_deactivated_notice' ) ) {
	/**
	 * Shows admin notice warning if Licensing & Management plugin is not activated.
	 *
	 * @since 4.6.0
	 * @deprecated 4.18.0 -- This is now included in LearnDash - LMS.
	 *
	 * @return void
	 */
	function learndash_hub_deactivated_notice() {
		_deprecated_function( __FUNCTION__, '4.18.0' );

		if (
			learndash_is_learndash_hub_active()
			|| ! current_user_can( 'administrator' )
		) {
			return;
		}

		if ( learndash_is_learndash_hub_installed() ) {
			$activation_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . LEARNDASH_HUB_PLUGIN_SLUG ), 'activate-plugin_' . LEARNDASH_HUB_PLUGIN_SLUG );

			$message = sprintf(
				// translators: %1$s: opening anchor tag, %2$s: closing anchor tag.
				esc_html__( 'Important! The LearnDash Licensing & Management plugin is deactivated. Please %1$sclick here%2$s to activate the plugin to ensure your LearnDash license works correctly. ', 'learndash' ), // cspell: disable-line -- HTML link.
				'<a href="' . $activation_url . '">',
				'</a>'
			);
		} else {
			$message = esc_html__( 'Important! The LearnDash Licensing & Management plugin is missing. Please install the plugin to ensure your LearnDash license works correctly. ', 'learndash' );
		}

		$class = 'notice notice-warning is-dismissible';
		$title = __( 'LearnDash Licensing & Management', 'learndash' );
		$links = __( '<a href="https://www.learndash.com/support/docs/core/learndash-licensing-and-management/">LearnDash Licensing Guide</a>', 'learndash' );

		printf(
			'<div class="%1$s">
				<p><strong>%2$s</strong></p>
				<p>%3$s</p>
				<p>%4$s</p>
			</div>',
			esc_attr( $class ),
			esc_html( $title ),
			$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			wp_kses_post( $links )
		);
	}
}

if ( ! function_exists( 'learndash_get_updater_instance' ) ) {
	/**
	 * Gets the `nss_plugin_updater_sfwd_lms` instance.
	 *
	 * If the instance already exists it returns the existing instance otherwise creates a new instance.
	 *
	 * @since 4.0.0
	 * @deprecated 4.18.0 -- nss_plugin_updater_sfwd_lms is deprecated.
	 *
	 * @param bool $force_new Whether to force a new instance.
	 *
	 * @return nss_plugin_updater_sfwd_lms The `nss_plugin_updater_sfwd_lms` instance.
	 */
	function learndash_get_updater_instance( $force_new = false ) {
		_deprecated_function( __FUNCTION__, '4.18.0' );

		static $updater_sfwd_lms = null;

		if ( true === $force_new ) {
			if ( ! is_null( $updater_sfwd_lms ) ) {
				$updater_sfwd_lms = null;
			}
		}

		if ( ! $updater_sfwd_lms instanceof nss_plugin_updater_sfwd_lms ) {
			$nss_plugin_updater_plugin_remote_path = 'https://support.learndash.com/';
			$nss_plugin_updater_plugin_slug        = basename( LEARNDASH_LMS_PLUGIN_DIR ) . '/sfwd_lms.php';
			$updater_sfwd_lms                      = new nss_plugin_updater_sfwd_lms( $nss_plugin_updater_plugin_remote_path, $nss_plugin_updater_plugin_slug );
		}

		if ( $updater_sfwd_lms instanceof nss_plugin_updater_sfwd_lms ) {
			return $updater_sfwd_lms;
		}
	}
}


if ( ! function_exists( 'learndash_activate_learndash_hub' ) ) {
	/**
	 * Activates the LearnDash Hub plugin (Licensing & Management).
	 *
	 * @since 4.8.0
	 * @deprecated 4.18.0 -- This is now included in LearnDash - LMS.
	 *
	 * @return bool True if the plugin is activated. False otherwise.
	 */
	function learndash_activate_learndash_hub(): bool {
		_deprecated_function( __FUNCTION__, '4.18.0' );

		if ( learndash_is_learndash_hub_active() ) {
			return true;
		}

		$activation_result = activate_plugin(
			LEARNDASH_HUB_PLUGIN_SLUG,
			'',
			is_plugin_active_for_network( LEARNDASH_LMS_PLUGIN_KEY ),
			true
		);

		if ( is_wp_error( $activation_result ) ) {
			WP_DEBUG && error_log( 'Failed to activate the learndash licensing & management plugin: ' . $activation_result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'learndash_is_learndash_hub_installed' ) ) {
	/**
	 * Check if LearnDash Hub is installed.
	 *
	 * @since 4.8.0
	 * @deprecated 4.18.0 -- This is now included in LearnDash - LMS.
	 *
	 * @return bool True if the LearnDash Hub is installed. False otherwise.
	 */
	function learndash_is_learndash_hub_installed() {
		_deprecated_function( __FUNCTION__, '4.18.0' );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return array_key_exists( LEARNDASH_HUB_PLUGIN_SLUG, get_plugins() );
	}
}

if ( ! function_exists( 'learndash_is_learndash_hub_active' ) ) {
	/**
	 * Check if LearnDash Hub is installed and active.
	 *
	 * @since 4.3.1
	 * @deprecated 4.18.0 -- This is now included in LearnDash - LMS.
	 *
	 * @return bool True if the LearnDash Hub is installed and active. False otherwise.
	 */
	function learndash_is_learndash_hub_active() {
		_deprecated_function( __FUNCTION__, '4.18.0' );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return function_exists( 'is_plugin_active' ) && is_plugin_active( LEARNDASH_HUB_PLUGIN_SLUG );
	}
}
