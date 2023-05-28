<?php

namespace Hub\Traits;

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
	 * @param int  $timestamp
	 * @param bool $i18n
	 *
	 * @return false|string
	 */
	public function format_date_time( int $timestamp, bool $i18n = true ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		if ( $i18n === false ) {
			return gmdate( $format, $timestamp );
		}
		$time = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' );

		return date_i18n( $format, strtotime( $time ) );
	}

}
