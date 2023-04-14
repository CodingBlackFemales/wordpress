<?php
/**
 * BuddyBoss Platform Pro Core Functions.
 *
 * @package BuddyBossPro/Functions
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if bb pro license is valid or not.
 *
 * @since 1.0.0
 *
 * @return bool License is valid then true otherwise true.
 */
function bbp_pro_is_license_valid() {
	$server_name = ! empty( $_SERVER['SERVER_NAME'] ) ? wp_unslash( $_SERVER['SERVER_NAME'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$whitelist_domain = array(
		'.test',
		'.dev',
		'staging.',
		'localhost',
		'.local',
	);

	foreach ( $whitelist_domain as $domain ) {
		if ( false !== strpos( $server_name, $domain ) ) {
			return true;
		}
	}

	$saved_licenses = get_option( 'bboss_updater_saved_licenses' );
	if ( is_multisite() ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active_for_network( bb_platform_pro()->basename ) ) {
			$saved_licenses = get_site_option( 'bboss_updater_saved_licenses' );
		}
	}

	$license_exists = false;
	if ( ! empty( $saved_licenses ) ) {
		foreach ( $saved_licenses as $package_id => $license_details ) {
			if ( ! empty( $license_details['license_key'] ) && ! empty( $license_details['product_keys'] ) && is_array( $license_details['product_keys'] ) && ( in_array( 'BB_THEME', $license_details['product_keys'], true ) || in_array( 'BB_PLATFORM_PRO', $license_details['product_keys'], true ) ) ) {
				$license_exists = true;
				break;
			}
		}
	}

	return $license_exists;
}

/**
 * Output the BB Platform pro database version.
 *
 * @since 1.0.4
 */
function bbp_pro_db_version() {
	echo bbp_pro_get_db_version(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
/**
 * Return the BB Platform pro database version.
 *
 * @since 1.0.4
 *
 * @return string The BB Platform pro database version.
 */
function bbp_pro_get_db_version() {
	return bb_platform_pro()->db_version;
}

/**
 * Output the BB Platform pro database version.
 *
 * @since 1.0.4
 */
function bbp_pro_db_version_raw() {
	echo bbp_pro_get_db_version_raw(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the BB Platform pro database version.
 *
 * @since 1.0.4
 *
 * @return string The BB Platform pro version direct from the database.
 */
function bbp_pro_get_db_version_raw() {
	$bbp = bb_platform_pro();
	return ! empty( $bbp->db_version_raw ) ? $bbp->db_version_raw : 0;
}

/**
 * WordPress Compatibility less than 5.3.0 version.
 */

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Retrieves the date, in localized format.
	 *
	 * This is a newer function, intended to replace `date_i18n()` without legacy quirks in it.
	 *
	 * Note that, unlike `date_i18n()`, this function accepts a true Unix timestamp, not summed
	 * with timezone offset.
	 *
	 * @param string       $format    PHP date format.
	 * @param int          $timestamp Optional. Unix timestamp. Defaults to current time.
	 * @param DateTimeZone $timezone  Optional. Timezone to output result in. Defaults to timezone
	 *                                from site settings.
	 *
	 * @return string|false The date, translated if locale specifies it. False on invalid timestamp input.
	 * @since BuddyBoss Pro 1.0.5
	 */
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		global $wp_locale;

		if ( null === $timestamp ) {
			$timestamp = time();
		} elseif ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		if ( ! $timezone ) {
			$timezone = wp_timezone();
		}

		$datetime = date_create( '@' . $timestamp );
		$datetime->setTimezone( $timezone );

		if ( empty( $wp_locale->month ) || empty( $wp_locale->weekday ) ) {
			$date = $datetime->format( $format );
		} else {
			// We need to unpack shorthand `r` format because it has parts that might be localized.
			$format = preg_replace( '/(?<!\\\\)r/', DATE_RFC2822, $format );

			$new_format    = '';
			$format_length = strlen( $format );
			$month         = $wp_locale->get_month( $datetime->format( 'm' ) );
			$weekday       = $wp_locale->get_weekday( $datetime->format( 'w' ) );

			for ( $i = 0; $i < $format_length; $i ++ ) {
				switch ( $format[ $i ] ) {
					case 'D':
						$new_format .= addcslashes( $wp_locale->get_weekday_abbrev( $weekday ), '\\A..Za..z' );
						break;
					case 'F':
						$new_format .= addcslashes( $month, '\\A..Za..z' );
						break;
					case 'l':
						$new_format .= addcslashes( $weekday, '\\A..Za..z' );
						break;
					case 'M':
						$new_format .= addcslashes( $wp_locale->get_month_abbrev( $month ), '\\A..Za..z' );
						break;
					case 'a':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'a' ) ), '\\A..Za..z' );
						break;
					case 'A':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'A' ) ), '\\A..Za..z' );
						break;
					case '\\':
						$new_format .= $format[ $i ];

						// If character follows a slash, we add it without translating.
						if ( $i < $format_length ) {
							$new_format .= $format[ ++ $i ];
						}
						break;
					default:
						$new_format .= $format[ $i ];
						break;
				}
			}

			$date = $datetime->format( $new_format );
			$date = wp_maybe_decline_date( $date, $format );
		}

		/**
		 * Filters the date formatted based on the locale.
		 *
		 * @param string       $date      Formatted date string.
		 * @param string       $format    Format to display the date.
		 * @param int          $timestamp Unix timestamp.
		 * @param DateTimeZone $timezone  Timezone.
		 *
		 * @since 5.3.0
		 */
		$date = apply_filters( 'wp_date', $date, $format, $timestamp, $timezone );

		return $date;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @return DateTimeZone Timezone object.
	 * @since BuddyBoss Pro 1.0.5
	 */
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}

if ( ! function_exists( 'wp_timezone_string' ) ) {
	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * Uses the `timezone_string` option to get a proper timezone if available,
	 * otherwise falls back to an offset.
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 * @since BuddyBoss Pro 1.0.5
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

/**
 * It's a alias for wp_safe_remote_post but allows filters.
 *
 * @since 2.0.3
 *
 * @param string $url  URL for the remote post.
 * @param array  $args array of arguments.
 *
 * @return array|WP_Error
 */
function bbpro_remote_post( $url, $args = array() ) {

	$url      = apply_filters( 'bbpro_remote_post_url', $url, $args );
	$args     = apply_filters( 'bbpro_remote_post_args', $args, $url );
	$response = wp_safe_remote_post( $url, $args );
	$response = apply_filters( 'bbpro_remote_post_response', $response, $url, $args );

	return $response;
}

/**
 * It's a alias for wp_remote_get but allows filters.
 *
 * @since 2.0.3
 *
 * @param string $url  URL for the remote post.
 * @param array  $args array of arguments.
 *
 * @return array|WP_Error
 */
function bbpro_remote_get( $url, $args = array() ) {
	$url      = apply_filters( 'bbpro_remote_get_url', $url, $args );
	$args     = apply_filters( 'bbpro_remote_get_args', $args, $url );
	$response = wp_safe_remote_get( $url, $args );
	$response = apply_filters( 'bbpro_remote_get_response', $response, $url, $args );

	return $response;
}

if ( ! function_exists( 'bb_pro_filter_input_string' ) ) {
	/**
	 * Function used to sanitize user input in a manner similar to the (deprecated) FILTER_SANITIZE_STRING.
	 *
	 * In many cases, the usage of `FILTER_SANITIZE_STRING` can be easily replaced with `FILTER_SANITIZE_FULL_SPECIAL_CHARS` but
	 * in some cases, especially when storing the user input, encoding all special characters can result in an stored XSS injection
	 * so this function can be used to preserve the pre PHP 8.1 behavior where sanitization is expected during the retrieval
	 * of user input.
	 *
	 * @since BuddyBoss 2.3.0
	 *
	 * @param string $type          One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
	 * @param string $variable_name Name of a variable to retrieve.
	 * @param int[]  $flags         Array of supported filter options and flags.
	 *                              Accepts `FILTER_REQUIRE_ARRAY` in order to require the input to be an array.
	 *                              Accepts `FILTER_FLAG_NO_ENCODE_QUOTES` to prevent encoding of quotes.
	 * @return string|string[]|null|boolean Value of the requested variable on success, `false` if the filter fails, or `null` if the `$variable_name` variable is not set.
	 */
	function bb_pro_filter_input_string( $type, $variable_name, $flags = array() ) {

		$require_array = in_array( FILTER_REQUIRE_ARRAY, $flags, true );
		$string        = filter_input( $type, $variable_name, FILTER_UNSAFE_RAW, $require_array ? FILTER_REQUIRE_ARRAY : array() );

		// If we have an empty string or the input var isn't found we can return early.
		if ( empty( $string ) ) {
			return $string;
		}

		/**
		 * This differs from strip_tags() because it removes the contents of
		 * the `<script>` and `<style>` tags. E.g. `strip_tags( '<script>something</script>' )`
		 * will return 'something'. wp_strip_all_tags will return ''
		 */
		$string = $require_array ? array_map( 'strip_tags', $string ) : strip_tags( $string );

		if ( ! in_array( FILTER_FLAG_NO_ENCODE_QUOTES, $flags, true ) ) {
			$string = str_replace( array( "'", '"' ), array( '&#39;', '&#34;' ), $string );
		}

		return $string;

	}
}
