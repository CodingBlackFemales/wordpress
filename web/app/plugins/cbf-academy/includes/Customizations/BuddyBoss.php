<?php
/**
 * Ninja Forms Hooks
 *
 * @package     CBFAcademy/Customizations
 * @version     1.0.0
 */

namespace CBFAcademy\Customizations;

use CBFAcademy\Constants as Constants;
use CBFAcademy\Utils as Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom capabilities class.
 */
class BuddyBoss {

	/**
	 * Hook in methods.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'option_buddypages-member-pages', array( __CLASS__, 'enable_member_pages_for_site_admin' ), 10, 2 );
	}

	/**
	 * Return true as 'buddypages-member-pages' option value for site admins
	 */
	public static function enable_member_pages_for_site_admin( $value, $option ) {
		return current_user_can( 'manage_options' ) || $value;
	}
}
