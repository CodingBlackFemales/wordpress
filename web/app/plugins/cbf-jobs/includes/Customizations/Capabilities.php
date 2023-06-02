<?php
/**
 * Ninja Forms Hooks
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
		add_action( 'init', array( __CLASS__, 'custom_grant_manage_network_users_capability' ), 11 );
	}

	/**
	 * Hooks onto ninja_forms_post_run_action_type_redirect to correctly decode redirection URL argument
	 * Fixes URL provided when logging in from Discourse
	 */
	public static function custom_grant_manage_network_users_capability() {
		$role = get_role( 'administrator' );

		if ( $role ) {
			$role->add_cap( 'manage_network_users', true );
		}
	}
}
