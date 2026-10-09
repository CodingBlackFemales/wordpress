<?php
/**
 * Handle plugin's install actions.
 *
 * @class   Install
 * @version 1.0.0
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary;

use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\PostFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install class.
 *
 * Owns the reference index table. Entries themselves are ordinary posts and
 * need no schema.
 */
final class Install {

	/**
	 * Option recording the installed schema version, per site.
	 */
	const DB_VERSION_OPTION = 'cbf_glossary_db_version';

	/**
	 * Current schema version.
	 */
	const DB_VERSION = '1.0.0';


	/**
	 * Activation hook callback.
	 */
	public static function install(): void {
		self::create_tables();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		/**
		 * Fires after the plugin has been installed.
		 */
		do_action( 'cbf_glossary_installed' );
	}


	/**
	 * Install or upgrade the schema when this site is behind.
	 *
	 * Activation only runs on the site it was triggered from, so a network
	 * activation or a site created later would otherwise have no index table.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		self::install();
	}


	/**
	 * Uninstall callback, called from uninstall.php only.
	 *
	 * Drops the index table and removes options and per-post data. Glossary
	 * entries are left in place: they are content, and deleting a plugin should
	 * not silently destroy an editorial team's work.
	 */
	public static function uninstall(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- a table name cannot be a placeholder.
		$wpdb->query( 'DROP TABLE IF EXISTS ' . Index::table() );

		delete_option( self::DB_VERSION_OPTION );
		delete_option( Settings::OPTION );

		foreach ( array( PostFields::EXTRA_META, PostFields::IGNORED_META ) as $key ) {
			delete_post_meta_by_key( $key );
		}

		/**
		 * Fires after the plugin's data has been removed.
		 *
		 * Entries of the `glossary_term` post type are deliberately kept.
		 */
		do_action( 'cbf_glossary_uninstalled', PostType::NAME );
	}


	/**
	 * Create or update the reference index table using dbDelta().
	 *
	 * One row per reference: an inline mention (is_inline = 1, position in
	 * document order) or an entry added to a post's glossary without one
	 * (is_inline = 0).
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = Index::table();

		dbDelta(
			"CREATE TABLE {$table} (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id     BIGINT UNSIGNED NOT NULL,
  entry_id    BIGINT UNSIGNED NOT NULL,
  position    INT UNSIGNED    NOT NULL DEFAULT 0,
  ref_text    VARCHAR(255)    NOT NULL DEFAULT '',
  form_index  INT                 NULL DEFAULT NULL,
  is_inline   TINYINT(1)      NOT NULL DEFAULT 1,
  is_first    TINYINT(1)      NOT NULL DEFAULT 0,
  render_abbr TINYINT(1)      NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY idx_post (post_id, position),
  KEY idx_entry (entry_id)
) {$charset};"
		);
	}
}
