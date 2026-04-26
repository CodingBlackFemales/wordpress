<?php
/**
 * LearnDash License utility functions.
 *
 * @since 4.3.1
 *
 * @package LearnDash\License
 *
 * @cspell:ignore shere .
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Utilities\Cast;

const LEARNDASH_HUB_LICENSE_CACHE_OPTION  = 'learndash_hub_license_result';
const LEARNDASH_HUB_LICENSE_CACHE_TIMEOUT = 6 * HOUR_IN_SECONDS;
const LEARNDASH_LICENSE_KEY               = 'nss_plugin_license_sfwd_lms';
const LEARNDASH_LICENSE_EMAIL_KEY         = 'nss_plugin_license_email_sfwd_lms';
const LEARNDASH_HUB_PLUGIN_SLUG           = 'learndash-hub/learndash-hub.php';
/**
 * Updates the LearnDash Hub license cache when the license is verified.
 *
 * @since 4.5.0
 *
 * @param WP_Error|bool $license_response The license response.
 *
 * @return void
 */
add_action(
	'learndash_licensing_management_license_verified',
	function ( $license_response ) {
		update_option(
			LEARNDASH_HUB_LICENSE_CACHE_OPTION,
			array(
				time(),
				! is_wp_error( $license_response ),
			)
		);
	}
);

/**
 * Removes the license cache after the license logout.
 *
 * @since 4.5.0
 *
 * @return void
 */
add_action(
	'learndash_licensing_management_license_logout',
	function () {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
	}
);

/**
 * Validate a license key.
 *
 * @since 4.3.1
 * @since 4.20.2 Added $force_check param.
 *
 * @param string $email The email address of the license key.
 * @param string $license_key The license key.
 * @param bool   $force_check Whether to force a check. Default false.
 *
 * @return bool True if the license key is valid. False otherwise.
 */
function learndash_validate_hub_license( string $email, string $license_key, bool $force_check = false ) {
	if ( ! class_exists( 'LearnDash\Hub\Component\API' ) ) {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
		return false; // legacy license system is not supported.
	}

	if ( empty( $email ) || empty( $license_key ) ) {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
		return false;
	}

	$hub_api           = new LearnDash\Hub\Component\API();
	$validation_result = $hub_api->verify_license( $email, $license_key, $force_check );

	$license_valid = ! is_wp_error( $validation_result ) && $validation_result === true;
	update_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION, array( time(), $license_valid ) );

	return $license_valid;
}

/**
 * Check if the license is valid.
 *
 * @since 4.3.1
 *
 * @return bool True if the license is valid. False otherwise.
 */
function learndash_is_license_hub_valid() {
	$license_valid = get_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );

	if (
		! is_array( $license_valid ) ||
		count( $license_valid ) !== 2 ||
		$license_valid[0] < time() - LEARNDASH_HUB_LICENSE_CACHE_TIMEOUT
	) {
		// recheck the license.
		return learndash_validate_hub_license(
			get_option( LEARNDASH_LICENSE_EMAIL_KEY, '' ),
			get_option( LEARNDASH_LICENSE_KEY, '' )
		);
	}

	return $license_valid[1];
}

/**
 * Get the last check time of the LearnDash Hub license status.
 *
 * @since 4.3.1
 *
 * @return int The last check time or 0 if never checked.
 */
function learndash_get_last_license_hub_check_time() {
	$license_valid = get_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );

	if (
		! is_array( $license_valid ) ||
		count( $license_valid ) !== 2
	) {
		return 0;
	}

	return intval( $license_valid[0] );
}

/**
 * Checks Whether the learndash license is valid or not.
 *
 * @since 3.4.0
 * @since 4.18.0 -- This is now an alias for learndash_is_license_hub_valid().
 *
 * @return bool
 */
function learndash_is_learndash_license_valid() {
	return learndash_is_license_hub_valid();
}

/**
 * Get the last license check time.
 *
 * @since 4.3.1
 * @since 4.18.0 -- This is now an alias for learndash_get_last_license_hub_check_time().
 *
 * @return int The last license check time.
 */
function learndash_get_last_license_check_time() {
	return learndash_get_last_license_hub_check_time();
}

/**
 * Utility function to check if we should check for updates.
 *
 * Updates includes by not limited to:
 * License checks, LD core and ProPanel Updates,
 * Add-on updates, Translations.
 *
 * @since 3.1.8
 *
 * @return bool
 */
function learndash_updates_enabled() {
	$updates_enabled = true;

	if (
		// @phpstan-ignore-next-line -- It is possible for this to not evaluate to true.
		defined( 'LEARNDASH_UPDATES_ENABLED' )
		// @phpstan-ignore-next-line -- It is possible for this to not evaluate to true.
		&& ( true !== LEARNDASH_UPDATES_ENABLED )
	) {
		$updates_enabled = false;
	}

	/**
	 * Filter for controlling update processing cycle.
	 *
	 * @since 3.1.8
	 *
	 * @param boolean $updates_enabled true.
	 * @return boolean True to process updates call. Anything else to abort.
	 */
	return (bool) apply_filters( 'learndash_updates_enabled', $updates_enabled );
}

/**
 * Check if we are showing the license notice.
 *
 * @since 3.1.8
 *
 * @return bool
 */
function learndash_get_license_show_notice() {
	if (
		// @phpstan-ignore-next-line -- It is possible for this to not evaluate to true.
		defined( 'LEARNDASH_LICENSE_PANEL_SHOW' )
		// @phpstan-ignore-next-line -- It is possible for this to not evaluate to true.
		&& false === LEARNDASH_LICENSE_PANEL_SHOW
	) {
		return false;
	}

	if ( ! learndash_updates_enabled() ) {
		$current_screen = get_current_screen();
		if (
			! $current_screen instanceof WP_Screen
			|| ! in_array(
				$current_screen->id,
				[
					'admin_page_learndash-setup',
				],
				true
			)
		) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! empty( $user_id ) ) {
			$notice_dismissed_timestamp = get_user_meta( $user_id, 'learndash_license_notice_dismissed', true );
			$notice_dismissed_timestamp = absint( $notice_dismissed_timestamp );
			if ( ( time() - $notice_dismissed_timestamp ) < ( DAY_IN_SECONDS ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Get the license notice message.
 *
 * @since 3.1.8
 * @since 4.22.1 Deprecated $mode parameter.
 *
 * @param integer $mode Deprecated. Which message to get.
 *
 * @return string
 */
function learndash_get_license_message( $mode = null ) {
	if ( $mode !== null ) {
		_deprecated_argument( __FUNCTION__, '4.22.1', 'The $mode parameter is deprecated since 4.22.1.' );
	}

	ob_start();
	?>

	<p>
		<strong>
			<?php esc_html_e( 'License Activation Failed', 'learndash' ); ?>
		</strong>
	</p>

	<p>
		<?php
		printf(
			// Translators: %1$s: Opening anchor tag, %2$s: Closing anchor tag, %3$s: Opening anchor tag, %4$s: Closing anchor tag.
			esc_html__(
				// cSpell:ignore syour -- Sprintf specifier placeholder.
				'We couldn\'t validate your LearnDash license. If you already have a valid license, please make sure you\'re using the latest version of LearnDash. %1$sCheck for updates%2$s or download the latest version from %3$syour account%4$s.',
				'learndash'
			),
			'<a href="/wp-admin/update-core.php" target="_blank" rel="noopener noreferrer">',
			'</a>',
			'<a href="https://go.learndash.com/downloads" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>

	<p>
		<?php
		printf(
			// Translators: %1$s: Opening anchor tag, %2$s: Closing anchor tag, %3$s: Opening anchor tag, %4$s: Closing anchor tag.
			esc_html__(
				"If you're new to LearnDash, get %1\$sa license here%2\$s. Still need assistance? %3\$sContact Support%4\$s",
				'learndash'
			),
			'<a href="https://go.learndash.com/lepricing" target="_blank" rel="noopener noreferrer">',
			'</a>',
			'<a href="https://go.learndash.com/support" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>

	<?php
	return Cast::to_string( ob_get_clean() );
}

/**
 * Get license notice class.
 *
 * @since 3.1.8
 *
 * @param string $css_classes Current class.
 *
 * @return string
 */
function learndash_get_license_class( $css_classes = '' ) {
	if ( ! learndash_updates_enabled() ) {
		$css_classes = 'notice notice-info is-dismissible learndash-updates-disabled-dismissible';
	}

	return $css_classes;
}

/**
 * Get license notice attributes.
 *
 * @since 3.1.8
 * @since 4.18.0 Added $should_echo parameter to allow returning a string.
 * Defaults to echo-ing the output, which was the previous behavior.
 *
 * @param bool $should_echo Whether to echo the output. Defaults to true.
 *
 * @return string Echos the content or returns it as a string.
 */
function learndash_get_license_data_attrs( bool $should_echo = true ) {
	ob_start();

	if ( ! learndash_updates_enabled() ) {
		echo ' data-notice-dismiss-nonce="' . esc_attr( wp_create_nonce( 'notice-dismiss-nonce-' . get_current_user_id() ) ) . '" ';
	} else {
		echo '';
	}

	$output = Cast::to_string( ob_get_clean() );

	if ( $should_echo ) {
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
	}

	return $output;
}

/**
 * AJAX function to handle license notice dismiss action from browser.
 *
 * @since 3.1.8
 *
 * @return void
 */
function learndash_license_notice_dismissed_ajax() {
	$user_id = get_current_user_id();

	if (
		empty( $_POST['learndash_license_notice_dismissed_nonce'] )
		|| $user_id <= 0
	) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['learndash_license_notice_dismissed_nonce'] ) );

	if (
		! wp_verify_nonce(
			$nonce,
			"notice-dismiss-nonce-{$user_id}"
		)
	) {
		return;
	}

	update_user_meta( $user_id, 'learndash_license_notice_dismissed', time() );
}
add_action( 'wp_ajax_learndash_license_notice_dismissed', 'learndash_license_notice_dismissed_ajax' );

/**
 * AJAX function to handle hub upgrade notice dismiss action from browser.
 *
 * @since 4.3.1
 *
 * @return void
 */
function learndash_hub_upgrade_dismissed_ajax() {
	$user_id = get_current_user_id();

	if (
		empty( $_POST['learndash_hub_upgrade_dismissed_nonce'] )
		|| $user_id <= 0
	) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['learndash_hub_upgrade_dismissed_nonce'] ) );

	if (
		! wp_verify_nonce(
			$nonce,
			"notice-dismiss-nonce-{$user_id}"
		)
	) {
		return;
	}

	delete_option( 'learndash_show_hub_upgrade_admin_notice' );
}
add_action( 'wp_ajax_learndash_hub_upgrade_dismissed', 'learndash_hub_upgrade_dismissed_ajax' );


/**
 * Hide the ProPanel license notice when we have disabled the LD updates.
 *
 * @since 3.1.8
 *
 * @return void
 */
function learndash_license_hide_propanel_notice() {
	if ( ! learndash_updates_enabled() ) {
		?>
		<style>
		p#nss_plugin_updater_admin_notice { display:none !important; }
		</style>
		<?php
	}
}
add_action( 'admin_footer', 'learndash_license_hide_propanel_notice', 99 );

