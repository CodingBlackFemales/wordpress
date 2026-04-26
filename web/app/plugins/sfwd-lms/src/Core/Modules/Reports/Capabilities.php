<?php
/**
 * Reports Capabilities Assignment and Removal.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports;

use WP_Role;

/**
 * Reports Capabilities Assignment and Removal.
 *
 * @since 4.17.0
 */
class Capabilities {
	/**
	 * Capabilities to be added to or removed from User Roles.
	 *
	 * @since 4.17.0
	 *
	 * @var string[]
	 */
	private const CAPABILITIES = [
		'propanel_widgets',
	];

	/**
	 * Option Name that is used to store a flag in the database to indicate that we have granted capabilities already.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'learndash_modules_reports_capabilities_granted';

	/**
	 * Adds Reports Capabilities to User Roles.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function add(): void {
		if ( get_option( self::OPTION_NAME ) ) {
			return;
		}

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$roles = get_editable_roles();

		foreach ( array_keys( $roles ) as $role_name ) {
			$role = get_role( $role_name );

			if (
				! $role instanceof WP_Role
				|| (
					! $role->has_cap( LEARNDASH_ADMIN_CAPABILITY_CHECK )
					&& $role_name !== 'group_leader'
					&& ! $role->has_cap( LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK )
				)
			) {
				continue;
			}

			foreach ( self::CAPABILITIES as $capability ) {
				if ( $role->has_cap( $capability ) ) {
					continue;
				}

				$role->add_cap( $capability, true );
			}
		}

		update_option( self::OPTION_NAME, LEARNDASH_VERSION );
	}

	/**
	 * Removes Reports Capabilities from all User Roles.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public static function remove(): void {
		if ( ! Provider::should_load() ) {
			return;
		}

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$roles = get_editable_roles();

		foreach ( array_keys( $roles ) as $role_name ) {
			$role = get_role( $role_name );

			if (
				! $role
				|| ! $role instanceof WP_Role
			) {
				continue;
			}

			foreach ( self::CAPABILITIES as $capability ) {
				$role->remove_cap( $capability );
			}
		}

		delete_option( self::OPTION_NAME );
	}
}
