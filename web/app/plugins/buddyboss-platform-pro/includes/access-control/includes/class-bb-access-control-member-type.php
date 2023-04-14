<?php
/**
 * BuddyBoss WishList Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp member type access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Member_Type extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Member_Type constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'bp_set_member_type', array( $this, 'bb_access_control_bp_set_member_type' ), PHP_INT_MAX, 3 );
		add_action( 'bp_remove_member_type', array( $this, 'bb_access_control_bp_remove_member_type' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Fires just after a user's profile type has been changed.
	 *
	 * @param int    $user_id     ID of the user whose profile type has been updated.
	 * @param string $member_type profile type.
	 * @param bool   $append      Whether the type is being appended to existing types.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_bp_set_member_type( $user_id, $member_type, $append ) {
		do_action( 'bb_access_control_bp_set_member_type', $user_id, $member_type, $append );
	}

	/**
	 * Fires just after a user's profile type has been removed.
	 *
	 * @param int    $user_id     ID of the user whose profile type has been updated.
	 * @param string $member_type profile type.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_bp_remove_member_type( $user_id, $member_type ) {
		do_action( 'bb_access_control_bp_remove_member_type', $user_id, $member_type );
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
			self::$instance->slug = 'bb_member_type';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available membership.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$member_types = bp_get_member_types( array(), 'objects' );

		// Bail when no profile types are registered.
		if ( ! $member_types ) {
			return array();
		}

		$is_member_type_enabled = bp_member_type_enable_disable();

		if ( false === $is_member_type_enabled ) {
			return array();
		}

		// Get all active member types.
		$bp_active_member_types = bp_get_active_member_types();
		$results                = array();
		foreach ( $bp_active_member_types as $member_type ) {
			$results[] = array(
				'id'      => bp_get_member_type_key( $member_type ),
				'text'    => get_the_title( $member_type ),
				'default' => false,
			);
		}

		return apply_filters( 'bp_member_type_get_level_lists', $results );

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

		$user_member_types = bp_get_member_type( $user_id, false );

		if ( $threaded ) {
			$current_user_member_types = bp_get_member_type( bp_loggedin_user_id(), false );
			if ( isset( $settings_data['access-control-options'] ) && ! empty( $settings_data['access-control-options'] ) && ! empty( $current_user_member_types ) ) {
				foreach ( $current_user_member_types as $current_user_member_type ) {
					if ( in_array( $current_user_member_type, $settings_data['access-control-options'], true ) ) {
						$arr_key = 'access-control-' . $current_user_member_type . '-options';
						if ( empty( $settings_data[ $arr_key ] ) ) {
							$has_access = true;
							break;
						}

						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$has_access = true;
							break;
						}
						if ( ! empty( $user_member_types ) ) {
							foreach ( $user_member_types as $user_member_type ) {
								if ( in_array( $user_member_type, $settings_data[ $arr_key ], true ) ) {
									$has_access = true;
									break;
								}
							}
							if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								foreach ( $user_member_types as $user_member_type ) {
									if ( in_array( $user_member_type, wp_list_pluck( self::get_level_lists(), 'id' ), true ) ) {
										$has_access = true;
										break;
									}
								}
							}
						}
					}
				}
			}
		} else {
			if ( isset( $settings_data['access-control-options'] ) && ! empty( $settings_data['access-control-options'] ) && ! empty( $user_member_types ) ) {
				foreach ( $user_member_types as $user_member_type ) {
					if ( in_array( $user_member_type, $settings_data['access-control-options'], true ) ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
