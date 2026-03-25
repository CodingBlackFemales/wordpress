<?php
/**
 * BB_SSO_Storage_Transient storage for persistent data.
 *
 * @package BuddyBossPro/SSO/Lib/Persistent/Storage
 */

namespace BBSSO\Persistent\Storage;

/**
 * Class BB_SSO_Storage_Transient.
 *
 * BB_SSO_Storage_Transient storage for persistent data.
 *
 * @since 2.6.30
 */
class BB_SSO_Storage_Transient extends BB_SSO_Storage_Abstract {

	/**
	 * Constructor.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $user_id The user ID to use for the session.
	 */
	public function __construct( $user_id = false ) {
		if ( false === $user_id ) {
			$user_id = get_current_user_id();
		}
		$this->session_id = 'bb_sso_persistent_' . $user_id;
	}
}
