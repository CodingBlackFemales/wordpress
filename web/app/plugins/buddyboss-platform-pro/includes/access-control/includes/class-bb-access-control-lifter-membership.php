<?php
/**
 * BuddyBoss Lifter Membership Class.
 *
 * @package BuddyBossPro
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp Lifter access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Lifter_Membership extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Lifter_Membership constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'llms_user_added_to_membership_level', array( $this, 'bb_access_control_llms_user_added_to_access_control_level' ), PHP_INT_MAX, 2 );
		add_action( 'llms_user_removed_from_membership_level', array( $this, 'bb_access_control_llms_user_removed_from_access_control_level' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Do something cool upon user enrollment in a membership
	 *
	 * @param    int $student_id      WP User ID.
	 * @param    int $access_control_id   WP Post ID of the Access Control.
	 *
	 * @since 1.1.0
	 * @return   void
	 */
	public function bb_access_control_llms_user_added_to_access_control_level( $student_id, $access_control_id ) {
		do_action( 'bb_access_control_llms_user_added_to_access_control_level', $student_id, $access_control_id );
	}

	/**
	 * Do something cool when user is removed from a membership
	 *
	 * @param    int $student_id      WP User ID.
	 * @param    int $access_control_id   WP Post ID of the Access Control.
	 *
	 * @since 1.1.0
	 * @return   void
	 */
	public function bb_access_control_llms_user_removed_from_access_control_level( $student_id, $access_control_id ) {
		do_action( 'bb_access_control_llms_user_removed_from_access_control_level', $student_id, $access_control_id );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.1.0
	 *
	 * @return Controller|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name           = __CLASS__;
			self::$instance       = new $class_name();
			self::$instance->slug = 'lifter';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available lifter membership.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available lifter membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$results = bb_access_control_get_posts( 'llms_membership' );

		return apply_filters( 'lifter_access_control_get_level_lists', $results );

	}

	/**
	 * Function will check whether user has access or not.
	 *
	 * @param int     $user_id       user id.
	 * @param array   $settings_data DB settings.
	 * @param boolean $threaded      threaded check.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean whether user has access to do a particular given action.
	 */
	public function has_access( $user_id = 0, $settings_data = array(), $threaded = false ) {

		$has_access = parent::has_access( $user_id, $settings_data, $threaded );

		if ( ! is_null( $has_access ) ) {
			return $has_access;
		}

		$has_access = false;

		if ( $threaded ) {
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				$plan = llms_is_user_enrolled( bp_loggedin_user_id(), $level_id );
				if ( $plan ) {
					$arr_key = 'access-control-' . $level_id . '-options';
					if ( empty( $settings_data[ $arr_key ] ) ) {
						$has_access = true;
						break;
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$has_access = true;
						break;
					}
					foreach ( $settings_data[ $arr_key ] as $level ) {
						$plan = llms_is_user_enrolled( $user_id, $level );
						if ( $plan ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ], true ) ) {
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							$plan = llms_is_user_enrolled( $user_id, $level );
							if ( $plan ) {
								$has_access = true;
								break;
							}
						}
					}
				}
			}
		} else {
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				$plan = llms_is_user_enrolled( $user_id, $level_id );
				if ( $plan ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
