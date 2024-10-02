<?php

namespace WPForms\Pro\AntiSpam;

/**
 * Class Helpers.
 *
 * @since 1.9.1
 */
class Helpers {

	/**
	 * Get the number of days to delete spam entries.
	 *
	 * @since 1.9.1
	 *
	 * @return int|false
	 * @noinspection PhpUndefinedConstantInspection
	 */
	public static function get_delete_spam_entries_days() {

		$last_n_days = defined( 'WPFORMS_DELETE_SPAM_ENTRIES' )
			? WPFORMS_DELETE_SPAM_ENTRIES
			: wpforms_setting( 'delete-spam-entries', 90 );
		$last_n_days = is_bool( $last_n_days ) ? $last_n_days : absint( $last_n_days );

		/**
		 * The $last_n_days variable can be boolean or integer.
		 *
		 * When false, then we do not delete spam entries.
		 * When true, then we delete entries for the last 90 days.
		 * When it is number, we take the number.
		 */
		return $last_n_days === true ? 90 : $last_n_days;
	}
}
