<?php
/**
 * BuddyBoss s2 Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp s2 access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_S2_Member extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_S2_Member constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'set_user_role', array( $this, 'bb_access_control_s2_member_add_update' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Fires after the user's role has changed.
	 *
	 * @param int      $user_id   The user ID.
	 * @param string   $role      The new role.
	 * @param string[] $old_roles An array of the user's previous roles.
	 *
	 * @since 1.1.5
	 */
	public function bb_access_control_s2_member_add_update( $user_id, $role, $old_roles ) {
		do_action( 'bb_access_control_s2_member_add_update', $user_id, $role, $old_roles );
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
			self::$instance->slug = 's2_member';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available s2 membership.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available s2 membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		if ( ! defined( 'WS_PLUGIN__S2MEMBER_VERSION' ) ) {
			return array();
		}

		$results = array();
		$levels  = $this->get_membership();
		if ( $levels ) {
			foreach ( $levels as $level ) {
				if ( empty( $search ) || strpos( strtolower( $level['name'] ), strtolower( $search ) ) !== false || empty( $search ) ) {
					$results[] = array(
						'id'      => $level['id'],
						'text'    => $level['name'],
						'default' => false,
					);
				}
			}
		}

		return apply_filters( 's2_member_get_level_lists', $results );

	}

	/**
	 * Function will return all the s2 membership lists.
	 *
	 * @since 1.1.0
	 *
	 * @return array available levels.
	 */
	private function get_membership() {
		$levels = array();
		if ( ! empty( $GLOBALS['WS_PLUGIN__']['s2member']['c']['levels'] ) ) {
			for ( $n = 0; $n <= $GLOBALS['WS_PLUGIN__']['s2member']['c']['levels']; $n ++ ) {
				$levels[] = array(
					'id'   => $n,
					'name' => $GLOBALS['WS_PLUGIN__']['s2member']['o'][ 'level' . $n . '_label' ],
				);
			}
		}

		return $levels;
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
				if ( user_is( bp_loggedin_user_id(), 's2member_level' . $level_id ) ) {
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
						if ( user_is( $user_id, 's2member_level' . $level ) ) {
							$has_access = true;
							break;
						}
					}
					if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
							if ( user_is( $user_id, 's2member_level' . $level ) ) {
								$has_access = true;
								break;
							}
						}
					}
				}
			}
		} else {
			foreach ( $settings_data['access-control-options'] as $level_id ) {
				if ( user_is( $user_id, 's2member_level' . $level_id ) ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
