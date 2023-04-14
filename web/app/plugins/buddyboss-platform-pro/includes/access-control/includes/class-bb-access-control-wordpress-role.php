<?php
/**
 * BuddyBoss WordPress role Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp WordPress role access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_WordPress_Role extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_WordPress_Role constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'set_user_role', array( $this, 'bb_access_control_wp_role_add_update' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Fires after the user's role has changed.
	 *
	 * @param int      $user_id   The user ID.
	 * @param string   $role      The new role.
	 * @param string[] $old_roles An array of the user's previous roles.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_wp_role_add_update( $user_id, $role, $old_roles ) {
		do_action( 'bb_access_control_wp_role_add_update', $user_id, $role, $old_roles );
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
			self::$instance->slug = 'wp_role';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available WordPress roles.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available WordPress roles.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$editable_roles = get_editable_roles();
		$results        = array();

		if ( isset( $editable_roles ) && ! empty( $editable_roles ) ) {
			foreach ( $editable_roles as $role => $details ) {
				$name      = translate_user_role( $details['name'] );
				$results[] = array(
					'id'      => $role,
					'text'    => $name,
					'default' => ( 'administrator' === $role ) ? true : false,
				);
			}
		}

		return apply_filters( 'bp_wp_role_get_level_lists', $results );

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

		$user_data  = get_userdata( $user_id );
		$user_roles = isset( $user_data->roles ) ? $user_data->roles : array();

		if ( $threaded ) {
			$current_user       = get_userdata( bp_loggedin_user_id() );
			$current_user_roles = isset( $current_user->roles ) ? $current_user->roles : array();
			if ( isset( $settings_data['access-control-options'] ) && ! empty( $settings_data['access-control-options'] ) && ! empty( $current_user_roles ) ) {
				foreach ( $current_user_roles as $current_user_role ) {
					if ( in_array( $current_user_role, $settings_data['access-control-options'], true ) ) {
						$arr_key = 'access-control-' . $current_user_role . '-options';
						if ( empty( $settings_data[ $arr_key ] ) ) {
							$has_access = true;
							break;
						}

						if ( in_array( 'all', $settings_data[ $arr_key ], true ) ) {
							$has_access = true;
							break;
						} else {
							foreach ( $user_roles as $user_role ) {
								if ( in_array( $user_role, $settings_data[ $arr_key ], true ) ) {
									$has_access = true;
									break;
								} elseif ( in_array( 'all', $settings_data[ $arr_key ], true ) && in_array( $user_role, wp_list_pluck( self::get_level_lists(), 'id' ), true ) ) {
									$has_access = true;
									break;
								}
							}
						}
					}
				}
			}
		} else {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $settings_data['access-control-options'], true ) ) {
					$has_access = true;
					break;
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
