<?php
/**
 * LearnDash Deprecated Settings Pages Class.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Pages_Deprecated' ) ) {
	/**
	 * Class for LearnDash Settings Pages Deprecated.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Pages_Deprecated {

		/**
		 * Collection of deprecated settings pages.
		 *
		 * @since 3.6.0
		 *
		 * @var array $tables_indexes.
		 */
		private static $settings_page_slugs = array();

		/**
		 * Private constructor for class
		 *
		 * @since 3.6.0
		 */
		private function __construct() {
		}

		/**
		 * Public Initialize function for class
		 *
		 * @since 3.6.0
		 */
		public static function init() {
			add_action( 'admin_page_access_denied', array( get_called_class(), 'settings_page_access_denied' ) );
		}

		/**
		 * Handles the `admin_page_access_denied` action from WordPress.
		 *
		 * @since 3.6.0
		 */
		public static function settings_page_access_denied() {
			global $pagenow;

			if ( 'admin.php' === $pagenow ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ( isset( $_GET['page'] ) ) && ( ! empty( $_GET['page'] ) ) ) {
					$requested_page_slug = strtolower( esc_attr( wp_unslash( $_GET['page'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
					if ( isset( self::$settings_page_slugs[ $requested_page_slug ] ) ) {
						$settings_page_set = self::$settings_page_slugs[ $requested_page_slug ];

						self::deprecated_settings_page( $settings_page_set['page_slug'], $settings_page_set['version'], $settings_page_set['replacement_args'] );
					}
				}
			}
		}

		/**
		 * Register deprecated settings page.
		 *
		 * @since 3.6.0
		 *
		 * @param string $page_slug        The original page slug as in 'page=<page_slug>'.
		 * @param string $version          The version of LearnDash that deprecated the function.
		 * @param array  $replacement_args Optional. An array of replacement page args. Will be passed to
		 * `add_query_arg()` to generate redirect URL.
		 */
		public static function register_deprecated_settings_page( $page_slug = '', $version = '', $replacement_args = array() ) {
			$page_slug = strtolower( esc_attr( $page_slug ) );
			$version   = esc_attr( $version );

			if ( ( is_array( $replacement_args ) ) && ( ! empty( $replacement_args ) ) ) {
				$replacement_args = array_map( 'trim', $replacement_args );
				$replacement_args = array_map( 'esc_attr', $replacement_args );
			} else {
				$replacement_args = array();
			}

			if ( ( ! empty( $page_slug ) ) && ( ! empty( $version ) ) ) {
				if ( ! isset( self::$settings_page_slugs[ $page_slug ] ) ) {
					self::$settings_page_slugs[ $page_slug ] = array(
						'page_slug'        => $page_slug,
						'version'          => $version,
						'replacement_args' => $replacement_args,
					);
				}
			}
		}

		/**
		 * Mark a settings page url as deprecated and inform when it has been used.
		 *
		 * This function works similar to standard WordPress functions like
		 * `_deprecated_function`. The current behavior is to trigger a user
		 * error if `WP_DEBUG` is true.
		 *
		 * @since 3.6.0
		 *
		 * @param string $page_slug        The original page slug as in 'page=<page_slug>'.
		 * @param string $version          The version of LearnDash that deprecated the function.
		 * @param array  $replacement_args Optional. An array of replacement page args. Will be passed to
		 * `add_query_arg()` to generate redirect URL.
		 */
		public static function deprecated_settings_page( $page_slug = '', $version = '', $replacement_args = array() ) {
			$page_slug = strtolower( esc_attr( $page_slug ) );
			$version   = esc_attr( $version );

			if ( ( is_array( $replacement_args ) ) && ( ! empty( $replacement_args ) ) ) {
				$replacement_args = array_map( 'trim', $replacement_args );
				$replacement_args = array_map( 'esc_attr', $replacement_args );
			} else {
				$replacement_args = array();
			}

			if ( ( ! empty( $page_slug ) ) && ( ! empty( $version ) ) ) {
				/**
				 * Fires when a deprecated settings page is called.
				 *
				 * @since 3.6.0
				 *
				 * @param string $page_slug        The original page slug as in 'page=<$page_slug>'.
				 * @param array  $replacement_args Optional. An array of replacement page args. Will be passed to
				 * `add_query_arg()` to generate redirect.
				 * @param string $version          The version of LearnDash that deprecated the function.
				 */
				do_action( 'learndash_deprecated_settings_page_run', $page_slug, $replacement_args, $version );

				/**
				 * Filters whether to trigger an error for deprecated settings page.
				 *
				 * @since 3.6.0
				 *
				 * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
				 */
				if ( WP_DEBUG && apply_filters( 'learndash_deprecated_settings_page_url_error', true ) ) {
					if ( function_exists( '__' ) ) {
						if ( ! empty( $replacement_args ) ) {
							trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
								wp_kses_post(
									sprintf(
										/* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
										__( 'LearnDash Settings Page %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'learndash' ),
										esc_url( add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) ) ),
										$version,
										esc_url( add_query_arg( $replacement_args, admin_url( 'admin.php' ) ) )
									)
								),
								E_USER_DEPRECATED
							);
						} else {
							trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
								wp_kses_post(
									sprintf(
										/* translators: 1: PHP function name, 2: Version number. */
										__( 'LearnDash Settings Page %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', 'learndash' ),
										esc_url( add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) ) ),
										$version
									)
								),
								E_USER_DEPRECATED
							);
						}
					} else {
						if ( $replacement_args ) {
							trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
								wp_kses_post(
									sprintf(
										'LearnDash Settings Page %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
										esc_url( add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) ) ),
										$version,
										esc_url( add_query_arg( $replacement_args, admin_url( 'admin.php' ) ) )
									)
								),
								E_USER_DEPRECATED
							);
						} else {
							trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
								wp_kses_post(
									sprintf(
										'LearnDash Settings Page %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
										esc_url( add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) ) ),
										$version
									)
								),
								E_USER_DEPRECATED
							);
						}
					}
				}

				$redirect_url = admin_url();
				if ( ! empty( $replacement_args ) ) {
					$redirect_url = add_query_arg( $replacement_args, admin_url( 'admin.php' ) );
				}

				/**
				 * Filters the redirect URL.
				 *
				 * @since 3.6.0
				 *
				 * @param string $redirect_url     Redirect URL.
				 * @param string $page_slug        The original page slug as in 'page=<page_slug>'.
				 * @param string $version          The version of LearnDash that deprecated the function.
				 * @param array  $replacement_args Optional. An array of replacement page args used to
				 * generate the `$redirect_url`.
				 */
				$redirect_url = apply_filters( 'learndash_deprecated_settings_page_url_redirect', $redirect_url, $page_slug, $version, $replacement_args );
				if ( ! empty( $redirect_url ) ) {
					learndash_safe_redirect( $redirect_url );
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_admin_init',
	function() {
		LearnDash_Settings_Pages_Deprecated::init();
	}
);

LearnDash_Settings_Pages_Deprecated::register_deprecated_settings_page(
	'learndash_lms_settings_custom_labels',
	'3.6.0',
	array(
		'page'             => 'learndash_lms_advanced',
		'section-advanced' => 'settings_custom_labels',
	)
);

LearnDash_Settings_Pages_Deprecated::register_deprecated_settings_page(
	'learndash_lms_settings_paypal',
	'3.6.0',
	array(
		'page'             => 'learndash_lms_payments',
		'section-advanced' => 'settings_paypal',
	)
);

LearnDash_Settings_Pages_Deprecated::register_deprecated_settings_page(
	'learndash_data_upgrades',
	'3.6.0',
	array(
		'page'             => 'learndash_lms_advanced',
		'section-advanced' => 'settings_data_upgrades',
	)
);
