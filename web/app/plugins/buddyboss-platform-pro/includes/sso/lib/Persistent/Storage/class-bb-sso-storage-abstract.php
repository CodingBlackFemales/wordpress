<?php
/**
 * Abstract class for managing persistent storage.
 *
 * @package BuddyBossPro/SSO/Lib/Persistent/Storage
 */

namespace BBSSO\Persistent\Storage;

/**
 * Class BB_SSO_Storage_Abstract.
 *
 * Abstract class for managing persistent storage. This class provides methods
 * to set, get, delete, and clear data, as well as handling session-based storage
 * using transients. Subclasses should define specific storage mechanisms.
 *
 * @since 2.6.30
 */
abstract class BB_SSO_Storage_Abstract {

	/**
	 * Session ID.
	 *
	 * @since 2.6.30
	 *
	 * @var string|null Holds the session ID
	 */
	protected $session_id = null;

	/**
	 * Session data.
	 *
	 * @since 2.6.30
	 *
	 * @var array Stores the data for the session
	 */
	protected $data = array();

	/**
	 * Sets a value in the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key   The key to store the value under.
	 * @param mixed  $value The value to be stored.
	 */
	public function set( $key, $value ) {
		$this->load( true );

		$this->data[ $key ] = $value;

		$this->store();
	}

	/**
	 * Loads the session data from the storage.
	 *
	 * @param bool $create_session Whether to create a new session if it doesn't exist.
	 *
	 * @since 2.6.30
	 */
	protected function load( $create_session = false ) {
		static $is_loaded = false;

		if ( ! $is_loaded ) {
			$data = maybe_unserialize( get_site_transient( $this->session_id ) );
			if ( is_array( $data ) ) {
				$this->data = $data;
			}
			$is_loaded = true;
		}
	}

	/**
	 * Stores the session data in the storage.
	 * Deletes the session if no data is present.
	 *
	 * @since 2.6.30
	 */
	private function store() {
		if ( empty( $this->data ) ) {
			delete_site_transient( $this->session_id );
		} else {
			set_site_transient( $this->session_id, $this->data, apply_filters( 'bb_sso_persistent_expiration', HOUR_IN_SECONDS ) );
		}
	}

	/**
	 * Retrieves a value from the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key associated with the stored value.
	 *
	 * @return mixed|null The stored value, or null if the key doesn't exist.
	 */
	public function get( $key ) {
		$this->load();

		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return null;
	}

	/**
	 * Deletes a value from the persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key associated with the value to be deleted.
	 */
	public function delete( $key ) {
		$this->load();

		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
			$this->store();
		}
	}

	/**
	 * Transfers data from another storage instance to the current storage.
	 *
	 * @since 2.6.30
	 *
	 * @param BB_SSO_Storage_Abstract $storage The storage instance to transfer data from.
	 */
	public function transfer_data( $storage ) {
		$this->data = $storage->data;
		$this->store();

		$storage->clear();
	}

	/**
	 * Clears all data from the persistent storage.
	 *
	 * @since 2.6.30
	 */
	public function clear() {
		$this->data = array();
		$this->store();
	}
}
