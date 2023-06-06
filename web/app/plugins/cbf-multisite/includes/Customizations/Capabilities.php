<?php
/**
 * Capability management
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

use CodingBlackFemales\Multisite\Constants as Constants;
use CodingBlackFemales\Multisite\Utils as Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom capabilities class.
 */
class Capabilities {

	/**
	 * Hook in methods.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'restore_admin_role_capabilities' ), 11 );
	}

	/**
	 * Restores capabilities for multisite admin role
	 */
	public static function restore_admin_role_capabilities() {
		$role = get_role( 'administrator' );

		if ( $role ) {
			$role->add_cap( 'manage_network_users' );
			$role->add_cap( 'unfiltered_html' );
		}
	}
}
