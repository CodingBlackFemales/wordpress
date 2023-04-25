<?php
/**
 * BuddyBoss Hooks
 *
 * @package     CBFAcademy/Customizations
 * @version     1.0.0
 */

namespace CBFAcademy\Customizations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss Class.
 */
class BuddyBoss {

	/**
	 * Hook in methods.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'bp_core_get_table_prefix', array( __CLASS__, 'bp_core_get_table_prefix' ) );
	}

	/**
	 * Sets the correct database table prefix based on the current subsite
	 *
	 * @param  string $base_prefix The base table prefix (e.g. `wp_`).
	 * @return string
	 */
	public static function bp_core_get_table_prefix( $base_prefix ) {
		if ( is_multisite() ) {
			$base_prefix .= get_current_blog_id() . '_';
		}

		return $base_prefix;
	}
}
