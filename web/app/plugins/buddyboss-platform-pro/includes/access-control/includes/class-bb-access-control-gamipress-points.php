<?php
/**
 * BuddyBoss Woo Access Control Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp woo access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Gamipress_Points extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Gamipress_Points constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {

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
			self::$instance->slug = 'gamipress_points';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available access control.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$results = bb_access_control_get_posts( 'points-type' );

		return apply_filters( 'gamipress_rank_get_level_lists', $results );

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
				$plan = gamipress_has_user_earned_rank( $level_id, bp_loggedin_user_id() );
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
						$plan = gamipress_has_user_earned_rank( $level, $user_id );
						if ( $plan ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							$plan = gamipress_has_user_earned_rank( $level, $user_id );
							if ( $plan ) {
								$has_access = true;
								break;
							}
						}
					}
				}
			}
			if ( is_null( $has_access ) ) {
				foreach ( $settings_data['access-control-options'] as $level_id ) {
					$earners     = gamipress_get_rank_earners( $level_id );
					$earners_arr = wp_list_pluck( $earners, 'ID' );
					if ( $earners_arr && in_array( bp_loggedin_user_id(), $earners_arr, true ) ) {
						$arr_key = 'access-control-' . $level_id . '-options';
						if ( empty( $settings_data[ $arr_key ] ) ) {
							$has_access = true;
							break;
						}
						foreach ( $settings_data[ $arr_key ] as $level ) {
							$earners     = gamipress_get_rank_earners( (int) $level );
							$earners_arr = wp_list_pluck( $earners, 'ID' );
							if ( $earners_arr && in_array( $user_id, $earners_arr, true ) ) {
								$has_access = true;
								break;
							}
						}
						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
								$earners     = gamipress_get_rank_earners( (int) $level );
								$earners_arr = wp_list_pluck( $earners, 'ID' );
								if ( $earners_arr && in_array( $user_id, $earners_arr, true ) ) {
									$has_access = true;
									break;
								}
							}
						}
					}
				}
			}
		} else {
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				$plan = gamipress_has_user_earned_rank( $level_id, $user_id );
				if ( $plan ) {
					$has_access = true;
					break;
				}
			}
			if ( is_null( $has_access ) ) {
				foreach ( $settings_data['access-control-options'] as $level_id ) {
					$earners     = gamipress_get_rank_earners( (int) $level_id );
					$earners_arr = wp_list_pluck( $earners, 'ID' );
					if ( $earners_arr && in_array( $user_id, $earners_arr, true ) ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
