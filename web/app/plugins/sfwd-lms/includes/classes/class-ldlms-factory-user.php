<?php
/**
 * LearnDash Factory User Class.
 * This is a factory class used to instantiate user related data.
 *
 * @since 3.4.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Factory_User' ) ) && ( class_exists( 'LDLMS_Factory' ) ) ) {
	/**
	 * Class for LearnDash Factory User.
	 *
	 * @since 2.5.0
	 * @uses LDLMS_Factory
	 */
	class LDLMS_Factory_User extends LDLMS_Factory {

		/**
		 * Get a User Instance.
		 *
		 * @param int|object $user   Either user_id integer or WP_User instance.
		 * @param bool       $reload True to force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_User` or null
		 */
		public static function user( $user = null, $reload = false ) {
			$model = 'LDLMS_Model_User';

			if ( ! empty( $user ) ) {
				$user_id = 0;

				if ( is_a( $user, 'WP_User' ) ) {
					$user_id = absint( $user->ID );
				} else {
					$user_id = absint( $user );
				}

				if ( ( ! empty( $user_id ) ) ) {
					if ( true === $reload ) {
						self::remove_instance( $model, $user_id );
					}
					return self::add_instance( $model, $user_id, $user_id );
				}
			}

			return null;
		}


		/**
		 * Get a User Course Progress Instance.
		 *
		 * @param int|object $user Either user_id integer or WP_User instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_User_Course_Progress` or null
		 */
		public static function course_progress( $user = null, $reload = false ) {
			$model = 'LDLMS_Model_User_Course_Progress';

			$user_id = 0;

			if ( is_a( $user, 'WP_User' ) ) {
				$user_id = absint( $user->ID );
			} else {
				$user_id = absint( $user );
			}

			if ( ! empty( $user_id ) ) {
				if ( true === $reload ) {
					self::remove_instance( $model, $user_id );
				}
				return self::add_instance( $model, $user_id, $user_id );
			}

			return null;
		}

		/**
		 * Get a User Course Progress Instance.
		 *
		 * @param int|object $user Either user_id integer or WP_User instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_User_Quiz_Progress` or null
		 */
		public static function quiz_progress( $user = null, $reload = false ) {
			$model = 'LDLMS_Model_User_Quiz_Progress';

			$user_id = 0;

			if ( is_a( $user, 'WP_User' ) ) {
				$user_id = absint( $user->ID );
			} else {
				$user_id = absint( $user );
			}

			if ( ! empty( $user_id ) ) {
				if ( true === $reload ) {
					self::remove_instance( $model, $user_id );
				}
				return self::add_instance( $model, $user_id, $user_id );
			}

			return null;
		}
	}
}
