<?php
/**
 * BB_SSO_Persistent class file.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Lib/Persistent
 */

namespace BBSSO\Persistent;

use BBSSO\Persistent\Storage\BB_SSO_Storage_Session;
use BBSSO\Persistent\Storage\BB_SSO_Storage_Abstract;
use BBSSO\Persistent\Storage\BB_SSO_Storage_Transient;
use WP_User;

require_once __DIR__ . '/Storage/class-bb-sso-storage-abstract.php';
require_once __DIR__ . '/Storage/class-bb-sso-storage-session.php';
require_once __DIR__ . '/Storage/class-bb-sso-storage-transient.php';

/**
 * Class BB_SSO_Persistent.
 *
 * Manages the persistence of data between user sessions and logged-in states
 * using different storage methods. It automatically switches between transient
 * storage for logged-in users and session storage for non-logged-in users.
 *
 * @since 2.6.30
 */
class BB_SSO_Persistent {

	/**
	 * The single instance of the class.
	 *
	 * @var BB_SSO_Persistent
	 */
	private static $instance;

	/**
	 * The storage object used to persist data.
	 *
	 * @var BB_SSO_Storage_Abstract
	 */
	private $storage;

	/**
	 * Persistent constructor.
	 * Initializes the Persistent class and hooks the necessary WordPress actions.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		self::$instance = $this;
		add_action(
			'init',
			array(
				$this,
				'init',
			),
			0
		);

		add_action(
			'bb_sso_before_wp_login',
			function () {
				add_action(
					'wp_login',
					array(
						$this,
						'transfer_session_to_user',
					),
					10,
					2
				);
			}
		);
	}

	/**
	 * Stores a value in the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param mixed  $value The value to be stored.
	 *
	 * @param string $key   The key to store the value under.
	 */
	public static function set( $key, $value ) {
		if ( self::$instance->storage ) {
			self::$instance->storage->set( $key, $value );
		}
	}

	/**
	 * Retrieves a value from the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key associated with the stored value.
	 *
	 * @return mixed|false The stored value or false if the key doesn't exist.
	 *
	 */
	public static function get( $key ) {
		if ( self::$instance->storage ) {
			return self::$instance->storage->get( $key );
		}

		return false;
	}

	/**
	 * Deletes a value from the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key associated with the value to be deleted.
	 *
	 */
	public static function delete( $key ) {
		if ( self::$instance->storage ) {
			self::$instance->storage->delete( $key );
		}
	}

	/**
	 * Clears all data from the persistent storage.
	 *
	 * @since 2.6.30
	 */
	public static function clear() {
		if ( self::$instance->storage ) {
			self::$instance->storage->clear();
		}
	}

	/**
	 * Initializes the storage system based on user login status.
	 * Uses transient storage for logged-in users and session storage otherwise.
	 *
	 * @since 2.6.30
	 */
	public function init() {
		if ( null === $this->storage ) {
			if ( is_user_logged_in() ) {
				$this->storage = new BB_SSO_Storage_Transient();
			} else {
				$this->storage = new BB_SSO_Storage_Session();
			}
		}
	}

	/**
	 * Transfers the current session data to transient storage when a user logs in.
	 * This ensures session data is persisted across login states.
	 *
	 * @param string  $user_login The user's login name.
	 * @param WP_User $user       The user object (optional).
	 *
	 * @since 2.6.30
	 */
	public function transfer_session_to_user( $user_login, $user = null ) {

		if ( ! $user ) { // For do_action( 'wp_login' ) calls that lacked passing the 2nd arg.
			$user = get_user_by( 'login', $user_login );
		}

		$new_storage = new BB_SSO_Storage_Transient( $user->ID );
		/**
		 * $this->storage might be NULL if init action not called yet
		 */
		if ( null !== $this->storage ) {
			$new_storage->transfer_data( $this->storage );
		}

		$this->storage = $new_storage;
	}
}

new BB_SSO_Persistent();
