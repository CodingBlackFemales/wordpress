<?php

namespace WPMailSMTP\Pro\License;

use WPMailSMTP\Options;

/**
 * Auto-activates a Pro license shipped with the build as a bundled `license.php`
 * file at the plugin root.
 *
 * @since 4.9.0
 */
class BundledLicense {

	/**
	 * Option name for the one-shot pending flag set on activation.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const PENDING_OPTION = 'wp_mail_smtp_bundled_license_pending_activation';

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'maybe_activate' ] );
	}

	/**
	 * Absolute path to the bundled license file at the Pro plugin root.
	 *
	 * Protected so unit tests can override it as a partial-mock seam.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function get_file_path() {

		return plugin_dir_path( WPMS_PLUGIN_FILE ) . 'license.php';
	}

	/**
	 * Set the one-shot pending flag during Pro activation, if a bundled license
	 * is present and no key is already configured.
	 *
	 * @since 4.9.0
	 */
	public function set_pending_on_activation() {

		// No-clobber: never overwrite a key the user already set.
		if ( $this->has_existing_license_key() ) {
			return;
		}

		if ( ! file_exists( $this->get_file_path() ) ) {
			return;
		}

		update_option( self::PENDING_OPTION, time(), false );
	}

	/**
	 * On a real admin page load, claim the pending attempt and try to activate
	 * the bundled key once.
	 *
	 * @since 4.9.0
	 */
	public function maybe_activate() {

		// Real admin page loads only: no AJAX, cron, REST, or WP-CLI.
		if (
			! is_admin() ||
			wp_doing_ajax() ||
			wp_doing_cron() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( defined( 'WP_CLI' ) && WP_CLI )
		) {
			return;
		}

		// Atomic claim: delete_option() returns true only for the request that
		// removed the row, so concurrent loads can't double-fire and a stale
		// object-cache read can't re-claim. Gating on the return (not a prior
		// get_option) is what makes this the lock.
		if ( ! delete_option( self::PENDING_OPTION ) ) {
			return;
		}

		// The bundled file may have been removed since activation; nothing to do.
		// Checked after the claim so the one-shot flag is burned exactly once and
		// a missing file can't leave a stale flag to re-check on every admin load.
		if ( ! file_exists( $this->get_file_path() ) ) {
			return;
		}

		$key = $this->read_bundled_key();

		// Delete the file up front, before any verification. This guarantees a
		// single attempt: on any failure the file is already gone, so a later
		// reactivation finds nothing to retry and the user activates manually.
		$this->delete_bundled_file();

		// Malformed / missing / empty -> no-op. The flag is already claimed.
		if ( ! $this->is_valid_key_format( $key ) ) {
			return;
		}

		$this->get_license()->verify_key( $key );
	}

	/**
	 * Whether a license key is already configured.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function has_existing_license_key() {

		// Options::get() already resolves the WPMS_LICENSE_KEY constant when defined.
		$key = Options::init()->get( 'license', 'key' );

		return ! empty( $key );
	}

	/**
	 * Read the license key from the bundled file.
	 *
	 * @since 4.9.0
	 *
	 * @return string Empty string when missing, unreadable, or malformed.
	 */
	private function read_bundled_key() {

		$path = $this->get_file_path();

		if ( ! file_exists( $path ) ) {
			return '';
		}

		// Suppress errors so a malformed bundled file can't surface a PHP notice/warning on an admin screen.
		$data = @include $path;
		$key  = is_array( $data ) ? ( $data['license_key'] ?? '' ) : '';

		return is_string( $key ) ? $key : '';
	}

	/**
	 * Whether the key is a 32-char MD5 hex string. Guards against sending junk
	 * to the licensing endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	private function is_valid_key_format( $key ) {

		return is_string( $key ) && (bool) preg_match( '/^[a-f0-9]{32}$/i', $key );
	}

	/**
	 * Delete the bundled file if it exists. A missing file or a read-only
	 * filesystem must not raise an error.
	 *
	 * @since 4.9.0
	 */
	private function delete_bundled_file() {

		$path = $this->get_file_path();

		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_delete_file( $path );
	}

	/**
	 * Resolve the License instance used to perform the remote verification.
	 *
	 * @since 4.9.0
	 *
	 * @return License
	 */
	private function get_license() {

		return wp_mail_smtp()->get_pro()->get_license();
	}
}
