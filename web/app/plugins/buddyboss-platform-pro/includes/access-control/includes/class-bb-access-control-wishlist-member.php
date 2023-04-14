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
 * Setup the bp wishList access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Wishlist_Member extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Wishlist_Member constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'wishlistmember_remove_user_levels', array( $this, 'bb_access_control_wishlistmember_remove_user_levels' ), PHP_INT_MAX, 2 );
		add_action( 'wishlistmember_add_user_levels', array( $this, 'bb_access_control_wishlistmember_add_user_levels' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Fires after the user's level changed.
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $levels     The user levels.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_wishlistmember_remove_user_levels( $user_id, $levels ) {
		do_action( 'bb_access_control_wishlistmember_remove_user_levels', $user_id, $levels );
	}

	/**
	 * Fires after the user's level changed.
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $levels     The user levels.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_wishlistmember_add_user_levels( $user_id, $levels ) {
		do_action( 'bb_access_control_wishlistmember_add_user_levels', $user_id, $levels );
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
			self::$instance->slug = 'wishlist_member';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available access control list.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available access control.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		if ( ! function_exists( 'wlmapi_get_levels' ) ) {
			return array();
		}

		$levels  = wlmapi_get_levels();
		$results = array();

		if ( $levels && $levels['success'] && $levels['levels']['level'] ) {
			foreach ( $levels['levels']['level'] as $level ) {
				$results[] = array(
					'id'      => $level['id'],
					'text'    => $level['name'],
					'default' => false,
				);
			}
		}

		return apply_filters( 'wishlist_access_control_get_level_lists', $results );

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
				$level_data = wlmapi_get_level_members( $level_id );
				if ( ! empty( $level_data['success'] ) && ! empty( $level_data['members']['member'] ) ) {
					$members = $level_data['members']['member'];
					$in_list = in_array( bp_loggedin_user_id(), wp_list_pluck( $members, 'id' ) ); //phpcs:ignore
					if ( $in_list ) {
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
							$level_data = wlmapi_get_level_members( $level );
							if ( ! empty( $level_data['success'] ) && ! empty( $level_data['members']['member'] ) ) {
								$members = $level_data['members']['member'];
								$in_list = in_array( $user_id, wp_list_pluck( $members, 'id' ) ); //phpcs:ignore
								if ( $in_list ) {
									$has_access = true;
									break;
								}
							}
						}
						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
								$level_data = wlmapi_get_level_members( $level );
								if ( ! empty( $level_data['success'] ) && ! empty( $level_data['members']['member'] ) ) {
									$members = $level_data['members']['member'];
									$in_list = in_array( $user_id, wp_list_pluck( $members, 'id' ) ); //phpcs:ignore
									if ( $in_list ) {
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
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				$level_data = wlmapi_get_level_members( $level_id );
				if ( ! empty( $level_data['success'] ) && ! empty( $level_data['members']['member'] ) ) {
					$members = $level_data['members']['member'];
					$in_list = in_array( $user_id, wp_list_pluck( $members, 'id' ) ); //phpcs:ignore
					if ( $in_list ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
