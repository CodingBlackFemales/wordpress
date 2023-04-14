<?php
/**
 * BuddyBoss Membership Abstract Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp access control Abstract class.
 *
 * @since 1.1.0
 */
abstract class BB_Access_Control_Abstract {

	/**
	 * Membership plugin slug.
	 *
	 * @var string $slug access control plugin slug.
	 *
	 * @since 1.1.0
	 */
	public $slug;

	/**
	 * Function will return the level lists.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of levels.
	 */
	abstract public function get_level_lists();

	/**
	 * Function will check whether user has access or not.
	 *
	 * @param int     $user_id       user id.
	 * @param array   $settings_data DB settings.
	 * @param boolean $threaded      threaded check.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean|null whether user has access to do a particular given action.
	 */
	public function has_access( $user_id = 0, $settings_data = array(), $threaded = false ) {

		$has_access = null;
		if (
			(
				empty( $settings_data ) ||
				empty( $settings_data['access-control-options'] ) ||
				(
					0 !== $user_id &&
					bp_user_can( $user_id, 'bp_moderate' ) &&
					get_current_user_id() === $user_id
				) ||
				bp_current_user_can( 'bp_moderate' )
			)
		) {
			$has_access = true;
		} elseif (
			(
				! empty( $settings_data['access-control-type'] ) &&
				(
					! empty( $settings_data['plugin-access-control-type'] ) ||
					! empty( $settings_data['gamipress-access-control-type'] )
				) &&
				! empty( $settings_data['access-control-options'] )
			) &&
			! bbp_pro_is_license_valid()
		) {
			$has_access = false;
		} elseif (
			(
				! empty( $settings_data['access-control-type'] ) &&
				(
					! empty( $settings_data['plugin-access-control-type'] ) ||
					! empty( $settings_data['gamipress-access-control-type'] )
				) &&
				empty( $settings_data['access-control-options'] )
			) &&
			bbp_pro_is_license_valid()
		) {
			$has_access = true;
		} elseif (
			(
				! empty( $settings_data['access-control-type'] ) &&
				(
					! empty( $settings_data['plugin-access-control-type'] ) ||
					! empty( $settings_data['gamipress-access-control-type'] )
				) &&
				empty( $settings_data['access-control-options'] )
			) &&
			! bbp_pro_is_license_valid()
		) {
			$has_access = true;
		} elseif (
			! $user_id ||
			! bbp_pro_is_license_valid()
		) {
			$has_access = false;
		}

		return apply_filters( 'bb_access_control_has_access', $has_access, $this->slug );

	}
}
