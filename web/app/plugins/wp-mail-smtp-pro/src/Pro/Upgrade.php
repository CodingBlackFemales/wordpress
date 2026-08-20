<?php

namespace WPMailSMTP\Pro;

use WPMailSMTP\Pro\Tasks\LicenseCheckTask;
use WPMailSMTP\Tasks\NotificationsUpdateTask;
use WPMailSMTP\UsageTracking\SendUsageTask;
use WPMailSMTP\WP;

/**
 * Pro specific upgrades.
 *
 * @since 4.4.0
 */
class Upgrade {

	/**
	 * Register hooks.
	 *
	 * @since 4.4.0
	 */
	public function hooks() {

		add_filter( 'wp_mail_smtp_upgrade_upgrades', [ $this, 'upgrades' ], 10, 2 );
	}

	/**
	 * List of upgrade callbacks to run.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $upgrades List of upgrade callbacks to run.
	 * @param string $version  Latest installed version of the plugin.
	 *
	 * @return array
	 */
	public function upgrades( $upgrades, $version ) {

		if ( version_compare( $version, '4.4.0', '<' ) ) {
			$upgrades[] = [ $this, 'v440_upgrade' ];
		}

		return $upgrades;
	}

	/**
	 * Upgrade to version 4.4.0.
	 *
	 * @since 4.4.0
	 */
	public function v440_upgrade() {

		if ( ! WP::use_global_plugin_settings() || is_main_site() ) {
			return;
		}

		( new NotificationsUpdateTask() )->cancel();
		( new LicenseCheckTask() )->cancel();
		( new SendUsageTask() )->cancel();
	}
}

