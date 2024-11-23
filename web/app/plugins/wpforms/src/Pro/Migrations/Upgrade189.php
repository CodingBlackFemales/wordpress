<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;

/**
 * Class upgrade for 1.8.9 release.
 *
 * @since        1.8.9
 *
 * @noinspection PhpUnused
 */
class Upgrade189 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.8.9
	 *
	 * @return bool|null Upgrade result:
	 *                    true - the upgrade completed successfully,
	 *                    false - in the case of failure,
	 *                    null - upgrade started but not yet finished (background task).
	 */
	public function run() {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}wpforms_entry_fields MODIFY COLUMN field_id VARCHAR(16);" );

		return true;
	}
}
