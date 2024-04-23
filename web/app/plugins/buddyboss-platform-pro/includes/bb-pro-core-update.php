<?php
/**
 * BuddyBoss Platform Pro Core Update functions.
 *
 * @package BuddyBossPro/Core
 * @since   1.0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is this a fresh installation of BuddyBoss Platform Pro?
 *
 * If there is no raw DB version, we infer that this is the first installation.
 *
 * @since 1.0.4
 *
 * @return bool True if this is a fresh install, otherwise false.
 */
function bbp_pro_is_install() {
	return ! bbp_pro_get_db_version_raw();
}

/**
 * Is this a BuddyBoss Platform pro update?
 *
 * Determined by comparing the registered BB Platform pro version to the version
 * number stored in the database. If the registered version is greater, it's
 * an update.
 *
 * @since 1.0.4
 *
 * @return bool True if update, otherwise false.
 */
function bbp_pro_is_update() {

	// Current DB version of this site (per site in a multisite network).
	$current_db   = (int) bp_get_option( '_bbp_pro_db_version' );
	$current_live = (int) bbp_pro_get_db_version();

	// Pro plugin version history.
	bbp_pro_version_bump();
	$bb_plugin_version_history = (array) bp_get_option( 'bb_pro_plugin_version_history', array() );
	$initial_version_data      = ! empty( $bb_plugin_version_history ) ? end( $bb_plugin_version_history ) : array();
	$bb_version_exists         = ! empty( $initial_version_data ) && ! empty( $initial_version_data['version'] ) && (string) bb_platform_pro()->version === (string) $initial_version_data['version'];
	if ( ! $bb_version_exists || $current_live !== $current_db ) {
		$current_date                = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$bb_latest_plugin_version    = array(
			'db_version' => $current_live,
			'date'       => $current_date->format( 'Y-m-d H:i:s' ),
			'version'    => bb_platform_pro()->version,
		);
		$bb_plugin_version_history[] = $bb_latest_plugin_version;
		bp_update_option( 'bb_pro_plugin_version_history', array_filter( $bb_plugin_version_history ) );
	}

	$is_update = false;
	if ( $current_live !== $current_db ) {
		$is_update = true;
	}

	// Return the product of version comparison.
	return $is_update;
}

/**
 * Update the BB Platform pro version stored in the database to the current version.
 *
 * @since 1.0.4
 */
function bbp_pro_version_bump() {
	bp_update_option( '_bbp_pro_db_version', bbp_pro_get_db_version() );
}

/**
 * Set up the BB PLatform pro updater.
 *
 * @since 1.0.4
 */
function bbp_pro_setup_updater() {

	// Are we running an outdated version of BB Platform pro?
	if ( ! bbp_pro_is_update() ) {
		return;
	}

	bbp_pro_version_updater();
}

/**
 * Initialize an update or installation of BB Platform pro.
 *
 * BB Platform pro's version updater looks at what the current database version is,
 * and runs whatever other code is needed - either the "update" or "install"
 * code.
 *
 * This is most often used when the data schema changes, but should also be used
 * to correct issues with BB Platform pro metadata silently on software update.
 *
 * @since 1.0.4
 */
function bbp_pro_version_updater() {

	// Get current DB version.
	$current_db = (int) bp_get_option( '_bbp_pro_db_version' );
	// Get the raw database version.
	$raw_db_version = (int) bbp_pro_get_db_version_raw();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$switched_to_root_blog = false;

	// Make sure the current blog is set to the root blog.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );

		$switched_to_root_blog = true;
	}

	if ( bbp_pro_is_install() ) {

		// Run the schema install to update tables.
		bbp_pro_core_install();

		// Upgrades.
	} else {

		// Run the schema install to update tables.
		bbp_pro_core_install();

		// Version 1.0.2 .
		if ( $raw_db_version < 111 ) {
			bbp_pro_update_to_1_0_4();
		}

		// Version 1.0.7 .
		if ( $raw_db_version < 217 ) {
			bbp_pro_update_to_1_0_7();
		}

		// Version 1.0.9 .
		if ( $raw_db_version < 231 ) {
			/**
			 * BuddyBoss Pro Update to version 1.0.9.
			 *
			 * @since 1.0.9
			 */
			do_action( 'bbp_pro_update_to_1_0_9' );
		}

		// Version 1.2.0 .
		if ( $raw_db_version < 241 ) {
			do_action( 'bbp_pro_update_to_1_2_0' );
		}

		// Version 2.1.8.
		if ( $raw_db_version < 251 ) {
			do_action( 'bbp_pro_update_to_2_1_5' );
		}

		// Version 2.2.1.2 .
		if ( $raw_db_version < 255 ) {
			bbp_pro_update_to_2_2_1_2();
		}

		// Version 2.3.40.
		if ( $raw_db_version < 275 ) {
			bbp_pro_update_to_2_3_40();
		}

		// Version 2.3.41.
		if ( $raw_db_version < 280 ) {
			bbp_pro_update_to_2_3_41();
		}

		// Version 2.3.91.
		if ( $raw_db_version < 285 ) {
			bbp_pro_update_to_2_3_42();
		}

		if ( $raw_db_version !== $current_db ) {
			if ( function_exists( 'bb_pro_reaction_migration' ) ) {
				bb_pro_reaction_migration();
			}
		}
	}

	/* All done! *************************************************************/

	if ( $switched_to_root_blog ) {
		restore_current_blog();
	}
}

/**
 * Main installer.
 *
 * @since 1.0.4
 */
function bbp_pro_core_install() {
	/**
	 * BuddyBoss Pro core install.
	 *
	 * @since 1.0.4
	 */
	do_action( 'bbp_pro_core_install' );
}

/**
 * Update migration for version 1.0.4
 *
 * @since 1.0.4
 */
function bbp_pro_update_to_1_0_4() {
	/**
	 * BuddyBoss Pro Update to version 1.0.4.
	 *
	 * @since 1.0.4
	 */
	do_action( 'bbp_pro_update_to_1_0_4' );
}

/**
 * Update migration for version 1.0.7
 *
 * @since 1.0.7
 */
function bbp_pro_update_to_1_0_7() {
	/**
	 * BuddyBoss Pro Update to version 1.0.7.
	 *
	 * @since 1.0.7
	 */
	do_action( 'bbp_pro_update_to_1_0_7' );
}

/**
 * Update migration for version 2.2.1.2
 *
 * @since 2.2.1.3
 */
function bbp_pro_update_to_2_2_1_2() {
	delete_transient( 'update_themes' );
	delete_transient( 'update_plugins' );
	delete_transient( 'bb_updates_bp-loader' );
	delete_transient( 'bb_updates_buddyboss-theme' );
	delete_transient( 'bb_updates_buddyboss-platform-pro' );
	// For Multi site.
	delete_site_transient( 'update_themes' );
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'bb_updates_bp-loader' );
	delete_site_transient( 'bb_updates_buddyboss-theme' );
	delete_site_transient( 'bb_updates_buddyboss-platform-pro' );
}

/**
 * Update migration for version 2.3.40.
 *
 * @since 2.3.40
 */
function bbp_pro_update_to_2_3_40() {
	$settings = array(
		'warnings'        => array(),
		'errors'          => array(),
		'sidewide_errors' => array(),
	);

	$connected_app_id      = bp_get_option( 'bb-onesignal-connected-app' );
	$connected_app_details = bp_get_option( 'bb-onesignal-connected-app-details' );

	if ( ! empty( $connected_app_id ) ) {
		$settings['app_id'] = $connected_app_id;
	}

	if ( ! empty( $connected_app_details ) ) {
		$settings['app_details'] = $connected_app_details;
		$settings['app_name']    = $connected_app_details['name'];
	}

	if (
		! empty( $connected_app_id ) &&
		! empty( $connected_app_details ) &&
		! empty( $connected_app_details['basic_auth_key'] )
	) {
		$settings['rest_api_key'] = $connected_app_details['basic_auth_key'];
		$settings['is_connected'] = true;
		bb_onesignal_update_settings( $settings );
		bb_onesignal_update_app_details();
	} else {
		if ( ! empty( $settings['app_id'] ) ) {
			$settings['sidewide_errors'] = array( 'upgrade_to_rest_api_key' );
		}
		bb_onesignal_update_settings( $settings );
	}

	// Delete all other options.
	bp_delete_option( 'bb-onesignal-connected-app' );
	bp_delete_option( 'bb-onesignal-connected-app-details' );
	bp_delete_option( 'bb-onesignal-connected-app-name' );
	bp_delete_option( 'bb-onesignal-authenticated' );
	bp_delete_option( 'bb-onesignal-account-apps' );
}

/**
 * Update migration for version 2.3.41.
 *
 * @since 2.3.41
 */
function bbp_pro_update_to_2_3_41() {
	$bb_onesignal = bb_onesignal_get_settings();

	if (
		! empty( $bb_onesignal ) &&
		(
			empty( $bb_onesignal['app_id'] ) ||
			(
				! empty( $bb_onesignal['app_id'] ) &&
				! empty( $bb_onesignal['rest_api_key'] )
			)
		)
	) {
		$bb_onesignal['sidewide_errors'] = array();
		bb_onesignal_update_settings( $bb_onesignal );
	}
}

/**
 * Update migration for version 2.3.91.
 *
 * @since 2.3.91
 */
function bbp_pro_update_to_2_3_42() {
	global $wpdb, $bp;

	$settings = bp_get_option( 'bb-zoom', array() );

	if ( empty( $settings ) ) {
		$settings = array();
	}

	// Migrate zoom account email.
	if ( empty( $settings['account-email'] ) ) {
		$settings['account-email'] = bp_get_option( 'bp-zoom-api-email' );
	}

	// Migrate zoom/webinar hide url options.
	if ( empty( $settings['meeting-hide-zoom-urls'] ) ) {
		$hide_zoom_urls_enabled    = bp_get_option( 'bp-zoom-hide-zoom-urls' );
		$hide_webinar_urls_enabled = bp_get_option( 'bp-zoom-hide-zoom-webinar-urls' );

		$enabled_for = array();

		if ( $hide_zoom_urls_enabled ) {
			$enabled_for[] = 'meetings';
		}

		if ( $hide_webinar_urls_enabled ) {
			$enabled_for[] = 'webinar';
		}

		$enabled_for = ! empty( $enabled_for ) ? implode( '-', $enabled_for ) : 'none';

		$settings['meeting-hide-zoom-urls'] = $enabled_for;
	}

	bp_update_option( 'bb-zoom', $settings );

	if (
		function_exists( 'bp_is_active' ) &&
		bp_is_active( 'groups' )
	) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT DISTINCT group_id FROM {$bp->groups->table_name_groupmeta} WHERE ( meta_key = %s AND meta_value != '' ) ORDER BY group_id DESC",
				'bp-group-zoom-api-key'
			)
		);

		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				$api_email           = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email' );
				$s2s_group_api_email = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
				if ( empty( $s2s_group_api_email ) ) {
					groups_update_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email', $api_email );
				}
			}
		}
	}
}
