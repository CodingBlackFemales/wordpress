<?php
/**
 * @license GPL-2.0
 *
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\DB\QueryBuilder\Concerns;

use StellarWP\Learndash\StellarWP\DB\QueryBuilder\Clauses\RawSQL;

/**
 * @since 1.0.0
 */
trait TablePrefix {
	/**
	 * @param  string|RawSQL  $table
	 *
	 * @return string
	 */
	public static function prefixTable( $table ) {
		global $wpdb;

		//  Shared tables in  multisite environment
		$sharedTables = [
			'users'	=> $wpdb->users,
			'usermeta' => $wpdb->usermeta,
		];

		if ( $table instanceof RawSQL ) {
			return $table->sql;
		}

		if ( array_key_exists( $table, $sharedTables ) ) {
			return $sharedTables[ $table ];
		}

		return $wpdb->prefix . $table;
	}
}
