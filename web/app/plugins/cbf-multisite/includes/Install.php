<?php
/**
 * Handle plugin's install actions.
 *
 * @class       Install
 * @version     1.0.0
 * @package     CodingBlackFemales/Multisite/Classes/
 */

namespace CodingBlackFemales\Multisite;

use CodingBlackFemales\Multisite\Customizations\WP_Cron as WP_Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install class
 */
final class Install {

	/**
	 * Deactivation action.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( WP_Cron::EXPORT_EVENT_NAME );
	}

	/**
	 * Install action.
	 */
	public static function install() {

		// Perform install actions here.
		// Trigger action.
		do_action( '_installed' );
	}
}
