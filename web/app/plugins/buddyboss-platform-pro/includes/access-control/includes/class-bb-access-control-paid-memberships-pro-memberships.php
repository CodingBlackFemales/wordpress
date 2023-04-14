<?php
/**
 * BuddyBoss Paid Membership Pro Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp paid membership pro membership class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Paid_Memberships_Pro_Memberships extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Paid_Memberships_Pro_Memberships constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'pmpro_after_change_membership_level', array( $this, 'bb_access_control_pmpro_after_change_access_control_level' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Fires after a user has membership level changes.
	 *
	 * @param int $level_id New level id.
	 * @param int $user_id User id.
	 * @param int $cancel_level old level id.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_pmpro_after_change_access_control_level( $level_id, $user_id, $cancel_level ) {
		do_action( 'bb_access_control_pmpro_after_change_access_control_level', $level_id, $user_id, $cancel_level );
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
			self::$instance->slug = 'paid_memberships_pro';
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

		if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
			return array();
		}

		$pm_pro_levels = pmpro_getAllLevels();
		$results       = array();
		foreach ( $pm_pro_levels as $pm_pro_level ) {
			if ( empty( $search ) || strpos( strtolower( $pm_pro_level->name ), strtolower( $search ) ) !== false ) {
				$results[] = array(
					'id'      => $pm_pro_level->id,
					'text'    => $pm_pro_level->name,
					'default' => false,
				);
			}
		}

		return apply_filters( 'pm_pro_get_level_lists', $results );

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
				$plan = pmpro_hasMembershipLevel( (int) $level_id, bp_loggedin_user_id() );
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
						$plan = pmpro_hasMembershipLevel( (int) $level, (int) $user_id );
						if ( $plan ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							$plan = pmpro_hasMembershipLevel( (int) $level, (int) $user_id );
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
				$plan = pmpro_hasMembershipLevel( (int) $level_id, (int) $user_id );
				if ( $plan ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
