<?php
/**
 * BuddyBoss Learndash Membership Class.
 *
 * @package BuddyBossPro
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp Learndash access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Learndash_Membership extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Learndash_Membership constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'ld_added_group_access', array( $this, 'bb_access_control_ld_added_group_access' ), PHP_INT_MAX, 2 );
		add_action( 'ld_removed_group_access', array( $this, 'bb_access_control_ld_removed_group_access' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Fires after the user is added to group access meta.
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Group ID.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_ld_added_group_access( $user_id, $group_id ) {
		do_action( 'bb_access_control_ld_added_group_access', $user_id, $group_id );
	}

	/**
	 * Fires after the user is removed from group access meta.
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Group ID.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_ld_removed_group_access( $user_id, $group_id ) {
		do_action( 'bb_access_control_ld_removed_group_access', $user_id, $group_id );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 *
	 * @since 1.1.0
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name           = __CLASS__;
			self::$instance       = new $class_name();
			self::$instance->slug = 'learndash';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available learndash membership.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available learndash membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$results = bb_access_control_get_posts( 'groups' );

		return apply_filters( 'learndash_access_control_get_level_lists', $results );

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
				// If the sender is a member or the leader of the group
				$plan = learndash_is_user_in_group( bp_loggedin_user_id(), $level_id ) || learndash_is_group_leader_of_user( bp_loggedin_user_id(), $user_id );
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
						// If the reciever is a member or the leader of the group
						$plan = learndash_is_user_in_group( $user_id, $level ) || learndash_is_group_leader_of_user( $user_id, bp_loggedin_user_id() );
						if ( $plan ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ], true ) ) {
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							$plan = learndash_is_user_in_group( $user_id, $level );
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
				$plan = learndash_is_user_in_group( $user_id, $level_id );
				if ( $plan ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
