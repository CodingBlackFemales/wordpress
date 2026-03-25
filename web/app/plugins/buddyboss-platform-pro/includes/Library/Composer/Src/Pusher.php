<?php

namespace BuddyBoss\PlatformPro\Library\Composer;

/**
 * Pusher custom class.
 *
 * @since 2.5.40
 */
class Pusher {
	private static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @since 2.5.40
	 *
	 * @return Pusher
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class          = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * This Function Is Used To Get Instance From Scoped Vendor
	 *
	 * @since 2.5.40
	 *
	 * @param string               $auth_key
	 * @param string               $secret
	 * @param string               $app_id
	 * @param array                $options [optional]
	 *                                      Options to configure the Pusher instance.
	 *                                      scheme - e.g. http or https
	 *                                      host - the host e.g. api-mt1.pusher.com. No trailing forward slash.
	 *                                      port - the http port
	 *                                      timeout - the http timeout
	 *                                      useTLS - quick option to use scheme of https and port 443 (default is
	 *                                      true).
	 *                                      cluster - cluster name to connect to.
	 *                                      encryption_master_key_base64 - a 32 byte key, encoded as base64. This key,
	 *                                      along with the channel name, are used to derive per-channel encryption
	 *                                      keys. Per-channel keys are used to encrypt event data on encrypted
	 *                                      channels.
	 * @param ClientInterface|null $client  [optional] - a Guzzle client to use for all HTTP requests
	 *
	 * @return \Pusher\Pusher
	 */

	function pusher( $auth_key, $secret, $app_id, $options = [], $client = null ) {
		return new \Pusher\Pusher( $auth_key, $secret, $app_id, $options, $client );
	}

}
