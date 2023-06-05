<?php
/**
 * Capability management
 *
 * @package     CBFJobs/Customizations
 * @version     1.0.0
 */

namespace CBFJobs\Customizations;

use CBFJobs\Constants as Constants;
use CBFJobs\Utils as Utils;

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
			$role->add_cap( 'manage_network_users', true );
			$role->add_cap( 'unfiltered_html' );
		}
	}
}
