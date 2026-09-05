<?php
/**
 * Handle plugin activation, deactivation and uninstallation.
 *
 * @class   Install
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter;

use CodingBlackFemales\SlidesImporter\Import\Janitor;
use CodingBlackFemales\SlidesImporter\Import\JobRunner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install class.
 *
 * Responsible for:
 * - Creating the two custom DB tables on activation.
 * - Granting the `cbf_slides_import` capability to administrators.
 * - Clearing scheduled hooks on deactivation.
 * - Dropping tables and removing all plugin data on uninstall.
 */
final class Install {

	/**
	 * DB version option key — used to detect when a schema migration is needed.
	 */
	const DB_VERSION_OPTION = 'cbf_si_db_version';

	/**
	 * Current DB schema version.
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * wp_options key for the encrypted Google OAuth client secret.
	 */
	const CLIENT_SECRET_OPTION = 'cbf_si_google_client_secret_enc';

	/**
	 * wp_options key for the configured Drive folder ID.
	 */
	const FOLDER_ID_OPTION = 'cbf_si_drive_folder_id';

	/**
	 * wp_usermeta key for per-user encrypted OAuth tokens.
	 */
	const USER_TOKEN_META = 'cbf_si_google_token_enc';

	/**
	 * Activation hook callback.
	 *
	 * Creates custom tables, adds capabilities, stores DB version.
	 */
	public static function install(): void {
		self::log_schema_upgrade();
		self::create_tables();
		self::add_capabilities();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		// Schedule hourly cleanup cron if not already registered.
		if ( ! wp_next_scheduled( Janitor::CLEANUP_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', Janitor::CLEANUP_HOOK );
		}

		do_action( 'cbf_si_installed' );
	}


	/**
	 * Record that a schema upgrade is about to happen.
	 *
	 * The upgrade itself is dbDelta()'s doing — it adds the batch columns to an
	 * existing jobs table in place, so a site coming from 1.0.0 keeps its job
	 * history. This only leaves a trace in the log, which is the one thing
	 * dbDelta will not do for you when a migration misbehaves.
	 */
	private static function log_schema_upgrade(): void {
		$installed = (string) get_option( self::DB_VERSION_OPTION, '' );

		if ( $installed !== '' && version_compare( $installed, self::DB_VERSION, '<' ) ) {
			Utils::log(
				'Upgrading plugin schema.',
				array(
					'from' => $installed,
					'to'   => self::DB_VERSION,
				)
			);
		}
	}


	/**
	 * Deactivation hook callback.
	 *
	 * Clears both scheduled WP-Cron hooks (job processor and cleanup) so no
	 * orphaned events fire after deactivation. Does NOT remove data — that is
	 * reserved for uninstall.php.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( JobRunner::CRON_HOOK );
		wp_clear_scheduled_hook( Janitor::CLEANUP_HOOK );
	}


	/**
	 * Uninstall callback — called from uninstall.php only.
	 *
	 * Drops both plugin tables, removes all wp_options keys, and removes
	 * all per-user OAuth tokens from wp_usermeta.
	 */
	public static function uninstall(): void {
		global $wpdb;

		// Drop tables.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cbf_slide_import_configs' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cbf_slide_import_jobs' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cbf_slide_import_batches' );
		// phpcs:enable

		// Remove all plugin options.
		delete_option( self::DB_VERSION_OPTION );
		delete_option( self::CLIENT_SECRET_OPTION );
		delete_option( self::FOLDER_ID_OPTION );

		// Remove all per-user tokens.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => self::USER_TOKEN_META ), array( '%s' ) );

		do_action( 'cbf_si_uninstalled' );
	}


	/**
	 * Create or update the two custom DB tables using dbDelta().
	 *
	 * Tables use the site's table prefix and utf8mb4 charset.
	 *
	 * cbf_slide_import_configs:  one row per (user, deck) configuration.
	 * cbf_slide_import_jobs:     one row per import job, tracks lifecycle.
	 * cbf_slide_import_batches:  one row per bulk CSV migration.
	 *
	 * A job belonging to a batch carries a non-null `batch_id`; single-file jobs
	 * leave it null, which is what keeps every pre-existing query correct.
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		$configs_table = "
CREATE TABLE {$prefix}cbf_slide_import_configs (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id       BIGINT UNSIGNED NOT NULL,
  user_id       BIGINT UNSIGNED NOT NULL,
  drive_file_id VARCHAR(200)    NOT NULL,
  deck_name     VARCHAR(500)    NOT NULL DEFAULT '',
  mode          VARCHAR(50)     NOT NULL DEFAULT 'lesson-only',
  heading_layout_regex VARCHAR(500) NOT NULL DEFAULT '',
  course_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  lesson_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  slide_overrides LONGTEXT         NULL DEFAULT NULL,
  created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY   uq_blog_file_user (blog_id, drive_file_id, user_id),
  KEY          idx_user_id (user_id),
  KEY          idx_blog_id (blog_id)
) $charset;
";

		$jobs_table = "
CREATE TABLE {$prefix}cbf_slide_import_jobs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id          BIGINT UNSIGNED NOT NULL,
  user_id          BIGINT UNSIGNED NOT NULL,
  config_id        BIGINT UNSIGNED     NULL DEFAULT NULL,
  drive_file_id    VARCHAR(200)    NOT NULL,
  deck_name        VARCHAR(500)    NOT NULL DEFAULT '',
  status           ENUM('pending','downloading','parsing','parsed','importing','done','failed') NOT NULL DEFAULT 'pending',
  error_message    TEXT                NULL DEFAULT NULL,
  created_post_ids LONGTEXT            NULL DEFAULT NULL,
  result_summary   LONGTEXT            NULL DEFAULT NULL,
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  batch_id         BIGINT UNSIGNED     NULL DEFAULT NULL,
  batch_row        INT UNSIGNED        NULL DEFAULT NULL,
  KEY idx_blog_status (blog_id, status),
  KEY idx_user_id (user_id),
  KEY idx_config_id (config_id),
  KEY idx_batch (batch_id, batch_row)
) $charset;
";

		$batches_table = "
CREATE TABLE {$prefix}cbf_slide_import_batches (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id       BIGINT UNSIGNED NOT NULL,
  user_id       BIGINT UNSIGNED NOT NULL,
  course_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  overwrite     TINYINT(1)      NOT NULL DEFAULT 0,
  csv_name      VARCHAR(500)    NOT NULL DEFAULT '',
  row_count     INT UNSIGNED    NOT NULL DEFAULT 0,
  status        ENUM('validating','awaiting_confirmation','running','done','completed_with_errors','failed','cancelled') NOT NULL DEFAULT 'validating',
  plan          LONGTEXT            NULL DEFAULT NULL,
  report        LONGTEXT            NULL DEFAULT NULL,
  error_message TEXT                NULL DEFAULT NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_blog_user (blog_id, user_id),
  KEY idx_status (status)
) $charset;
";

		dbDelta( $configs_table );
		dbDelta( $jobs_table );
		dbDelta( $batches_table );
	}


	/**
	 * Grant the cbf_slides_import capability to administrator role.
	 *
	 * Idempotent — safe to call on every activation.
	 */
	private static function add_capabilities(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'cbf_slides_import' );
		}
	}
}
