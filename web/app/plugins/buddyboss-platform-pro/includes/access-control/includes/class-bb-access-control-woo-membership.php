<?php
/**
 * BuddyBoss Woo Membership Class.
 *
 * @package BuddyBossPro
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp woo access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Woo_Membership extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Woo_Membership constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'wc_memberships_user_membership_created', array( $this, 'bb_access_control_woo_access_control_add_update' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Fires after a user has been granted membership access.
	 *
	 * @param \WC_Memberships_Membership_Plan $access_control_plan the plan that user was granted access to.
	 * @param array                           $data Array of User Membership arguments.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_woo_access_control_add_update( $access_control_plan, $data ) {
		do_action( 'bb_access_control_woo_access_control_add_update', $access_control_plan, $data );
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
			self::$instance->slug = 'woocommerce';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available woo membership plans.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available woo membership plans.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$results = bb_access_control_get_posts( 'wc_membership_plan' );

		return apply_filters( 'woo_access_control_get_level_lists', $results );

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

		if ( ! function_exists( 'wc_memberships_is_user_active_or_delayed_member' ) ) {
			return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );
		}

		if ( $threaded ) {
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				$plan = wc_memberships_is_user_active_or_delayed_member( bp_loggedin_user_id(), $level_id );
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
						$plan = wc_memberships_is_user_active_or_delayed_member( $user_id, $level );
						if ( $plan ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							$plan = wc_memberships_is_user_active_or_delayed_member( $user_id, $level );
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
				$plan = wc_memberships_is_user_active_or_delayed_member( $user_id, $level_id );
				if ( $plan ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
