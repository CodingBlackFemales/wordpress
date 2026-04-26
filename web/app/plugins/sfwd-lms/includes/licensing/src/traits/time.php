<?php
/**
 * LearnDash Licensing Time Trait.
 *
 * @since 4.17.0
 * @package LearnDash
 */

namespace Hub\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Manipulate the time.
 */
trait Time {
	/**
	 * Sync the timezone from a normal date time string and a date time string with timezone.
	 *
	 * @param string $time The normal date time string.
	 * @param string $time_with_tz The date time string with timezone.
	 *
	 * @return int The timestamp
	 */
	public function sync_timezone( string $time, string $time_with_tz ): int {
		$c_datetime = new \DateTime( $time_with_tz );
		$b_datetime = new \DateTime( $time, $c_datetime->getTimezone() );

		return $b_datetime->getTimestamp();
	}

	/**
	 * Format a Unix timestamp to a date time string using the site's timezone.
	 *
	 * @since 4.17.0
	 * @since 4.20.0 The $i18n parameter is no longer used.
	 *
	 * @param int  $timestamp The Unix timestamp.
	 * @param bool $i18n      Deprecated. Whether to localize the date time string. Default true.
	 *
	 * @return false|string
	 */
	public function format_date_time( int $timestamp, bool $i18n = true ) {
		if ( ! $i18n ) {
			_deprecated_argument( __METHOD__, '4.20.0', 'The $i18n argument is no longer used' );
		}

		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return learndash_adjust_date_time_display( $timestamp, $format );
	}
}
