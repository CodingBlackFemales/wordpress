<?php
/**
 * Capability management
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */
// phpcs:disable PHPCompatibility.Classes.NewConstVisibility.Found
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
	private const MANAGE_USERS_CAP = 'manage_network_users';
	private const UNFILTERED_HTML_CAP = 'unfiltered_html';

	/**
	 * Hook in methods.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'restore_admin_role_capabilities' ), 11 );
		add_filter( 'map_meta_cap', array( __CLASS__, 'add_unfiltered_html_capability_to_admins' ), 1, 3 );
	}

	/**
	 * Restores capabilities for multisite admin role
	 */
	public static function restore_admin_role_capabilities() {
		$role = get_role( 'administrator' );

		if ( $role ) {
			$role->add_cap( self::MANAGE_USERS_CAP );
		}
	}

	/**
	 * Enable unfiltered_html capability for Administrators.
	 *
	 * @param  array  $caps    The user's capabilities.
	 * @param  string $cap     Capability name.
	 * @param  int    $user_id The user ID.
	 * @return array  $caps    The user's capabilities, with 'unfiltered_html' potentially added.
	 */
	public static function add_unfiltered_html_capability_to_admins( $caps, $cap, $user_id ) {
		if ( $cap === self::UNFILTERED_HTML_CAP && user_can( $user_id, 'manage_options' ) ) {
			$caps = array( self::UNFILTERED_HTML_CAP );
		}

		return $caps;
	}
}
