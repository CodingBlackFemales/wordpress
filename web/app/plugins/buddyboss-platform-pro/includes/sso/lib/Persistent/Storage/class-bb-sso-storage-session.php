<?php
/**
 * BB_SSO_Storage_Session storage for persistent data.
 *
 * @package BuddyBossPro/SSO/Lib/Persistent/Storage
 */

namespace BBSSO\Persistent\Storage;

/**
 * Class BB_SSO_Storage_Session.
 *
 * Manages session-based storage using cookies and site transients. This class
 * handles session initialization, data persistence, and session destruction
 * for environments like WP Engine, where custom cookie names are required.
 *
 * @since 2.6.30
 */
class BB_SSO_Storage_Session extends BB_SSO_Storage_Abstract {

	/**
	 * The name of the session cookie.
	 *
	 * @since 2.6.30
	 *
	 * @see   https://pantheon.io/docs/caching-advanced-topics/
	 *
	 * @var string Name of the session cookie. Can be changed via the 'bb_sso_session_name' filter and
	 * BB_SSO_SESSION_NAME constant.
	 */
	private $session_name = 'SESSbbsso';

	/**
	 * Constructor for the session class. Sets the session cookie name based on the hosting environment and constants.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {

		/**
		 * WP Engine hosting needs custom cookie name to prevent caching.
		 *
		 * @see https://wpengine.com/support/wpengine-ecommerce/
		 */
		if ( class_exists( 'WpePlugin_common', false ) ) {
			$this->session_name = 'wordpress_bbsso';
		}
		if ( defined( 'BB_SSO_SESSION_NAME' ) ) {
			$this->session_name = BB_SSO_SESSION_NAME; // phpcs:ignore
		}
		$this->session_name = apply_filters( 'bb_sso_session_name', $this->session_name );
	}

	/**
	 * Clears session data and destroys the session cookie.
	 *
	 * @since 2.6.30
	 */
	public function clear() {
		parent::clear();

		$this->destroy();
	}

	/**
	 * Destroys the session by removing the session cookie and setting up
	 * transient deletion on the 'shutdown' action.
	 *
	 * @since 2.6.30
	 */
	private function destroy() {
		$session_id = $this->session_id;
		if ( $session_id ) {
			$this->setCookie( $session_id, time() - YEAR_IN_SECONDS, apply_filters( 'bb_sso_session_use_secure_cookie', false ) );

			add_action(
				'shutdown',
				array(
					$this,
					'destroySiteTransient',
				)
			);
		}
	}

	/**
	 * Sets a cookie with the given value, expiration time, and secure flag.
	 *
	 * @since 2.6.30
	 *
	 * @param string $value  The value to store in the cookie.
	 * @param int    $expire The expiration time for the cookie.
	 * @param bool   $secure Whether the cookie should be marked as secure.
	 */
	private function setCookie( $value, $expire, $secure = false ) {

		setcookie( $this->session_name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure );
	}

	/**
	 * Deletes the session data stored in the site transient.
	 *
	 * @since 2.6.30
	 */
	public function destroySiteTransient() {
		$session_id = $this->session_id;
		if ( $session_id ) {
			delete_site_transient( 'bb_sso_' . $session_id );
		}
	}

	/**
	 * Loads the session data from the site transient, or creates a new session if requested.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $create_session Whether to create a new session if none exists.
	 */
	protected function load( $create_session = false ) {
		static $is_loaded = false;
		if ( null === $this->session_id ) {
			if ( isset( $_COOKIE[ $this->session_name ] ) ) {
				// phpcs:ignore
				$this->session_id = 'bb_sso_persistent_' . md5( SECURE_AUTH_KEY . $_COOKIE[ $this->session_name ] );
			} elseif ( $create_session ) {
				$unique = uniqid( 'bb-sso', true );

				$this->setCookie( $unique, apply_filters( 'bb_sso_session_cookie_expiration', 0 ), apply_filters( 'bb_sso_session_use_secure_cookie', false ) );

				$this->session_id = 'bb_sso_persistent_' . md5( SECURE_AUTH_KEY . $unique );

				$is_loaded = true;
			}
		}

		if ( ! $is_loaded ) {
			if ( null !== $this->session_id ) {
				$data = maybe_unserialize( get_site_transient( $this->session_id ) );
				if ( is_array( $data ) ) {
					$this->data = $data;
				}
				$is_loaded = true;
			}
		}
	}

	/**
	 * Gets the session name.
	 *
	 * @since 2.6.90
	 *
	 * @return string The session name.
	 */
	public function get_session_name() {
		return $this->session_name;
	}
}
