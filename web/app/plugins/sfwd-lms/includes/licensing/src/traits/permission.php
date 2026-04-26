<?php

namespace LearnDash\Hub\Traits;

defined( 'ABSPATH' ) || exit;

trait Permission {
	/**
	 * Checks if the current user have permission for execute an action.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';

		return current_user_can( $cap );
	}

	/**
	 * Checks if the current user has permission to access the hub.
	 *
	 * @param int $user_id The user id. If not set, it will use the current user id.
	 *
	 * @return bool
	 */
	public function is_user_allowed( $user_id = 0 ) {
		// we should pass the permission here in the future.

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		if ( ! $this->is_signed_on() ) {
			return true; // if the site is not signed on, this functionality is disabled.
		}

		$access_list = get_site_option( 'learndash_hub_access_list' );
		if (
		! is_array( $access_list ) ||
		empty( $access_list ) ||
		( count( $access_list ) === 1 && isset( $access_list[''] ) )
		) {
			$access_list = $this->populate_access_list();
		}

		if ( isset( $access_list[ $user_id ] ) ) {
			return ! empty( $access_list[ $user_id ] );
		}

		return false;
	}

	/**
	 * Populates the access list with all admin users.
	 *
	 * @return array
	 */
	public function populate_access_list() {
		$access_list = array();
		$users_ids   = get_users(
			array(
				'role__in' => array( 'administrator', 'super_admin' ),
				'fields'   => 'ID',
			)
		);

		foreach ( $users_ids as $user_id ) {
			$access_list[ $user_id ] = array(
				'allow'     => $this->get_default_user_permissions(),
				'is_master' => false,
			);
		}

		update_site_option( 'learndash_hub_access_list', $access_list );

		return $access_list;
	}

	/**
	 * Cleanups the access list:
	 * - remove the master flag from all users
	 * - remove user that are removed from the WP database
	 * - remove users that are not admin anymore
	 *
	 * @return void
	 */
	public function cleanup_access_list() {
		$access_list = get_site_option( 'learndash_hub_access_list' );
		if ( ! is_array( $access_list ) || empty( $access_list ) ) {
			return;
		}

		$current_admins = get_users(
			array(
				'role__in' => array( 'administrator', 'super_admin' ),
				'fields'   => 'ID',
			)
		);

		foreach ( $access_list as $user_id => $user_data ) {
			if ( ! in_array( $user_id, $current_admins, true ) ) {
				unset( $access_list[ $user_id ] );
			}
			$access_list[ $user_id ]['is_master'] = false;
		}

		update_site_option( 'learndash_hub_access_list', $access_list );
	}

	/**
	 * Gets the list of allowed users.
	 *
	 * @return array
	 */
	public function get_allowed_users() {
		$access_list = get_site_option( 'learndash_hub_access_list' );

		if ( ! is_array( $access_list ) || empty( $access_list ) ) {
			$access_list = $this->populate_access_list();
		}

		return $access_list;
	}

	/**
	 * Adds a user to the access list.
	 *
	 * @param int   $user_id The user id.
	 * @param array $allow The allowed permissions. Default is all permissions.
	 * @param bool  $is_master If the user is master. Default is false.
	 *
	 * @return void
	 */
	public function allow_user( $user_id, $allow = array(), $is_master = false ) {
		$access_list = get_site_option( 'learndash_hub_access_list' );

		if ( ! user_can( $user_id, 'administrator' ) ) {
			return;
		}

		if ( ! is_array( $access_list ) ) {
			$access_list = array();
		}

		if ( empty( $allow ) ) {
			$allow = $this->get_default_user_permissions();
		}

		$access_list[ $user_id ] = array(
			'allow'     => $allow,
			'is_master' => $is_master,
		);

		update_site_option( 'learndash_hub_access_list', $access_list );
	}

	/**
	 * Removes a user from the access list.
	 *
	 * @param int $user_id The user id.
	 */
	public function disallow_user( $user_id ) {
		$access_list = get_site_option( 'learndash_hub_access_list' );

		if ( ! is_array( $access_list ) ) {
			$access_list = array();
		}

		if ( isset( $access_list[ $user_id ] ) ) {
			unset( $access_list[ $user_id ] );
		}

		update_site_option( 'learndash_hub_access_list', $access_list );
	}

	/**
	 * Updates the access list when a user role is changed.
	 *
	 * @param int    $user_id The user id.
	 * @param string $new_role The new role.
	 *
	 * @return void
	 */
	public function update_access_list_after_role_update( $user_id, $new_role ) {
		$access_list = get_site_option( 'learndash_hub_access_list' );

		if ( ! is_array( $access_list ) || empty( $access_list ) ) {
			return;
		}

		if ( isset( $access_list[ $user_id ] ) ) {
			if ( ! user_can( $user_id, 'administrator' ) ) {
				unset( $access_list[ $user_id ] );
			}
		} elseif ( user_can( $user_id, 'administrator' ) ) {
				$access_list[ $user_id ] = array(
					'allow'     => $this->get_default_user_permissions(),
					'is_master' => false,
				);
		}

		update_site_option( 'learndash_hub_access_list', $access_list );
	}

	/**
	 * Gets a list of default user roles.
	 *
	 * @return array
	 */
	public function get_default_user_permissions() {
		return array( 'dashboard', 'projects', 'billing', 'settings' );
	}

	/**
	 * A quick hand for verify the nonce.
	 *
	 * @param string $action The nonce action.
	 *
	 * @return bool
	 */
	public function verify_nonce( string $action ): bool {
		if ( ! isset( $_REQUEST['hubnonce'] ) ) {
			return false;
		}

		return wp_verify_nonce( $_REQUEST['hubnonce'], $action );
	}
}
