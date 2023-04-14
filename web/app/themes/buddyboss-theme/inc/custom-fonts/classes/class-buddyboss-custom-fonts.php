<?php
/**
 * BuddyBoss Theme Custom fonts - Init
 *
 * @package BuddyBoss_Custom_Fonts
 */

namespace BuddyBossTheme;

if ( ! class_exists( '\BuddyBossTheme\BuddyBoss_Custom_Fonts' ) ) {

	/**
	 * BuddyBoss Custom fonts
	 *
	 * @since 1.2.10
	 */
	class BuddyBoss_Custom_Fonts {

		/**
		 * Member Varible
		 *
		 * @var object instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Constructor function that initializes required actions and hooks
		 */
		public function __construct() {
			require_once get_template_directory() . '/inc/custom-fonts/classes/class-buddyboss-custom-fonts-cpt.php';
			require_once get_template_directory() . '/inc/custom-fonts/classes/class-buddyboss-custom-fonts-admin.php';
			require_once get_template_directory() . '/inc/custom-fonts/classes/class-buddyboss-custom-fonts-render.php';
		}
	}
}
