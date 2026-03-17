<?php

namespace BuddyBoss\PlatformPro\Library;

/**
 * Composer class for scoped library logic.
 * 
 * @since 2.5.40
 */
class Composer {

	/**
	 * @var $instance
	 *
	 * @since 2.5.40
	 */
	private static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @since 2.5.40
	 *
	 * @return Composer
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class          = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * This function is used to get Pusher instance from scoped vendor.
	 *
	 * @since 2.5.40
	 *
	 * @return \BuddyBoss\PlatformPro\Library\Composer\Pusher/BuddyBossPlatformPro\BuddyBoss\PlatformPro\Library\Composer\Pusher
	 */
	function pusher_instance() {
		if ( class_exists( '\BuddyBossPlatformPro\BuddyBoss\PlatformPro\Library\Composer\Pusher' ) ) {
			return \BuddyBossPlatformPro\BuddyBoss\PlatformPro\Library\Composer\Pusher::instance();
		}

		return \BuddyBoss\PlatformPro\Library\Composer\Pusher::instance();
	}
}
