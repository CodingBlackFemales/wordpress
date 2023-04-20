<?php
/**
 * Class to extend LDLMS_Model to LDLMS_Model_User.
 *
 * @since 3.4.0
 * @package LearnDash\User
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_User' ) ) && ( class_exists( 'LDLMS_Model' ) ) ) {

	/**
	 * Class for LearnDash LDLMS_Model_User.
	 *
	 * @since 3.4.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_User extends LDLMS_Model {

		/**
		 * User ID of Model.
		 *
		 * @since 3.2.0
		 *
		 * @var integer $user_id.
		 */
		protected $user_id = null;

		/**
		 * User Object of Model.
		 *
		 * @since 3.2.0
		 *
		 * @var object $user WP_User instance.
		 */
		protected $user = null;

		/**
		 * Public constructor for class.
		 *
		 * @since 3.2.0
		 */
		private function __construct() {
		}
	}
}
