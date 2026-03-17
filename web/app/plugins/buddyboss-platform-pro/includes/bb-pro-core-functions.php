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
 * @return bool License is valid then true otherwise false.
 */
function bbp_pro_is_license_valid() {
	if ( bb_pro_check_staging_server() ) {
		return true;
	}

	$license_exists = false;

	if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		$connector      = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
		$license_status = $connector->getLicenseActivationStatus();

		if (
			! empty( $license_status ) &&
			class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) &&
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-platform-pro' )
		) {
			$license_exists = true;
		}
	} else {
		$license_exists = false;
	}

	return $license_exists;
}

/**
 * Check if Platform Pro features should be locked due to DRM.
 *
 * This function implements grace period support - features remain enabled during
 * the warning period (days 1-30) and only lock after day 31.
 *
 * @since 2.11.0
 *
 * @return bool True if features should be locked, false otherwise.
 */
function bb_pro_should_lock_features() {
	// Check if DRM Registry is available.
	if ( class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry' ) ) {
		// Use DRM system to check if features should be locked.
		// This respects the grace period (days 1-20 features work, day 21+ locked).
		return \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' );
	}

	// Fallback to legacy license check if DRM not available.
	// This maintains backwards compatibility with older Platform versions.
	return ! bbp_pro_is_license_valid();
}

/**
 * Check if the current site is a staging or development server.
 *
 * This function checks the provided domain (or the current site URL if none is provided)
 * against a list of known staging/development indicators, including reserved words,
 * hosting provider domains, and local development TLDs.
 *
 * @since 2.10.0
 *
 * @param string $raw_domain Optional. The domain to check. If empty, uses the current site URL.
 * @return bool True if the domain indicates a staging/development environment, false otherwise.
 */
function bb_pro_check_staging_server( $raw_domain = '' ) {
	// If no domain provided, use the current site URL.
	if ( empty( $raw_domain ) ) {
		$raw_domain = site_url();
	}

	// Reserved hosting provider domains that indicate staging/development.
	$reserved_hosting_provider_domains = array(
		'accessdomain',     // Generic hosting.
		'cloudwaysapps',    // Cloudways.
		'flywheelsites',    // Flywheel.
		'kinsta',           // Kinsta.
		'mybluehost',       // BlueHost.
		'myftpupload',      // GoDaddy.
		'netsolhost',       // Network Solutions.
		'pantheonsite',     // Pantheon.
		'sg-host',          // SiteGround.
		'wpengine',         // WP Engine (old).
		'wpenginepowered',  // WP Engine.
		'rapydapps.cloud',  // Rapyd.
	);

	// Reserved words that indicate testing/staging environments.
	$reserved_words = array(
		'dev',
		'develop',
		'development',
		'test',
		'testing',
		'stg',
		'stage',
		'staging',
		'demo',
		'sandbox',
		'preview',
	);

	// Reserved TLDs for local development.
	$reserved_tlds = array(
		'local',
		'localhost',
		'test',
		'example',
		'invalid',
		'dev',
		'staging',
	);

	// Known local development tool domains.
	$reserved_local_domains = array(
		'lndo.site',        // Lando.
		'ddev.site',        // DDEV.
		'docksal',          // Docksal.
		'localwp.com',      // Local by Flywheel.
		'local.test',       // Generic local.
		'docker.internal',  // Docker.
		'ngrok.io',         // ngrok tunneling.
		'localtunnel.me',   // localtunnel.
	);

	// Parse the URL to get the host.
	$parsed_url = wp_parse_url( $raw_domain );
	$domain     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $raw_domain;

	// Remove www prefix if present.
	$domain = preg_replace( '/^www\./i', '', $domain );

	// Check if domain is localhost or an IP address.
	if ( 'localhost' === $domain || filter_var( $domain, FILTER_VALIDATE_IP ) ) {
		return true;
	}

	// Check for port numbers (often indicates local development).
	if ( isset( $parsed_url['port'] ) && ! in_array( $parsed_url['port'], array( 80, 443 ), true ) ) {
		return true;
	}

	// Extract domain parts.
	$domain_parts = explode( '.', $domain );
	$tld          = end( $domain_parts );

	// Check for reserved TLDs.
	if ( in_array( $tld, $reserved_tlds, true ) ) {
		return true;
	}

	// Check for reserved testing words in subdomains.
	$subdomain_pattern = '/(\.|-)(' . implode( '|', $reserved_words ) . ')(\.|-)|(^(' . implode( '|', $reserved_words ) . ')\.)/i';
	if ( preg_match( $subdomain_pattern, $domain ) ) {
		return true;
	}

	// Check for known hosting provider staging domains.
	$hosting_pattern = '/\.(' . implode( '|', $reserved_hosting_provider_domains ) . ')\./i';
	if ( preg_match( $hosting_pattern, '.' . $domain . '.' ) ) {
		return true;
	}

	// Check for known development tool domains.
	$dev_tools_pattern = '/(' . implode( '|', array_map( 'preg_quote', $reserved_local_domains ) ) . ')$/i';
	if ( preg_match( $dev_tools_pattern, $domain ) ) {
		return true;
	}

	// Check WordPress-specific staging indicators.
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'production' !== WP_ENVIRONMENT_TYPE ) {
		return true;
	}


	// Check for common WordPress staging constants.
	if ( defined( 'WP_STAGING' ) && WP_STAGING ) {
		return true;
	}

	// Additional WordPress multisite check.
	if ( is_multisite() ) {
		$network_domain = parse_url( network_site_url(), PHP_URL_HOST );
		if ( $network_domain !== $domain ) {
			// Check if this is a staging subdomain in multisite.
			if ( preg_match( $subdomain_pattern, $network_domain ) ) {
				return true;
			}
		}
	}

	return false;
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

if ( ! function_exists( 'bb_pro_is_heartbeat' ) ) {
	/**
	 * Check if the request is heartbeat.
	 *
	 * @since 2.4.50
	 *
	 * @return bool
	 */
	function bb_pro_is_heartbeat() {
		return isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'];
	}
}

/**
 * Get the BuddyBoss Platform min version for a poll.
 *
 * @since 2.6.00
 *
 * @return string
 */
function bb_platform_poll_version() {
	return '2.6.90';
}

/**
 * Get the telemetry platform pro options.
 *
 * @since 2.6.30
 *
 * @param array $bb_telemetry_data Telemetry data.
 *
 * @return array Telemetry options.
 */
function bb_telemetry_platform_pro_data( $bb_telemetry_data ) {
	global $wpdb;
	$bb_telemetry_data = ! empty( $bb_telemetry_data ) ? $bb_telemetry_data : array();

	// Filterable list of BuddyBoss Platform Pro options to fetch from the database.
	$bb_pro_db_options = apply_filters(
		'bb_telemetry_pro_options',
		array(
			'bb-pusher-enabled',
			'bp-force-friendship-to-message',
			'bb-access-control-send-message',
			'bb-access-control-friends',
			'bb-access-control-upload-media',
			'bb-access-control-upload-document',
			'bp-zoom-enable',
			'bp-zoom-enable-groups',
			'bp-zoom-enable-recordings',
			'bb-access-control-create-activity',
			'bb-access-control-upload-video',
			'bb-onesignal-enabled-web-push',
			'bb-onesignal-enable-soft-prompt',
			'bb-enable-sso',
			'bb_social_login',
			'bb-meprlms',
			'bboss_updater_saved_licenses',
			'bb-sso-reg-options',
			'bb-pro-cover-profile-width',
			'bb-pro-cover-profile-height',
			'bb-pro-cover-group-width',
			'bb-pro-cover-group-height',
			'bb-enable-group-activity-topics',
			'bb-group-activity-topics-options',
		)
	);

	// Added those options that are not available in the option table.
	$bb_telemetry_data['bb_platform_pro_version'] = bb_platform_pro()->version;

	if (
		function_exists( 'bb_topics_manager_instance' ) &&
		function_exists( 'bb_is_enabled_activity_topics' ) &&
		bb_is_enabled_activity_topics() &&
		function_exists( 'bb_is_enabled_group_activity_topics' ) &&
		bb_is_enabled_group_activity_topics()
	) {
		$group_topics_count = bb_topics_manager_instance()->bb_get_topics(
			array(
				'item_type'   => 'groups',
				'count_total' => true,
				'per_page'    => 1,
			)
		);
		if ( isset( $group_topics_count['total'] ) ) {
			$bb_telemetry_data['bb_enabled_topic_group_count'] = $group_topics_count['total'];
		}
	}

	// Fetch options from the database.
	$bp_prefix = $wpdb->base_prefix;
	$query     = "SELECT option_name, option_value FROM {$bp_prefix}options WHERE option_name IN ('" . implode( "','", $bb_pro_db_options ) . "');";
	$results   = $wpdb->get_results( $query, ARRAY_A );

	if ( ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$bb_telemetry_data[ $result['option_name'] ] = $result['option_value'];
		}
	}

	unset( $bp_prefix, $query, $results, $bb_pro_db_options );

	return $bb_telemetry_data;
}

/**
 * Get the BuddyBoss Platform Pro integration.
 *
 * @since 2.6.30
 *
 * @param array $integrations Integrations.
 *
 * @return array Telemetry options.
 */
function bb_pro_active_integrations( $integrations ) {
	$integrations['bb-onesignal'] = function_exists( 'bb_onesignal_app_is_connected' ) && bb_onesignal_app_is_connected();
	$integrations['bb-pusher']    = function_exists( 'bb_pusher_is_enabled' ) && bb_pusher_is_enabled();
	$integrations['bp-zoom']      = false;

	$settings = function_exists( 'bb_get_zoom_block_settings' ) ? bb_get_zoom_block_settings() : array();
	if (
		! empty( $settings['s2s-account-id'] ) &&
		! empty( $settings['s2s-client-id'] ) &&
		! empty( $settings['s2s-client-secret'] ) &&
		! empty( $settings['zoom_is_connected'] )
	) {
		$integrations['bp-zoom'] = true;

	}

	$integrations['bb-tutorlms'] = is_plugin_active( 'tutor/tutor.php' ) && function_exists( 'bb_tutorlms_enable' ) && bb_tutorlms_enable();
	$integrations['bb-meprlms']  = class_exists( 'memberpress\courses\helpers\Courses' ) && function_exists( 'bb_meprlms_enable' ) && bb_meprlms_enable();

	return $integrations;
}

/**
 * Get the BuddyBoss Platform version required for the topic.
 *
 * @since [BBVERSION}
 *
 * @return string
 */
function bb_platform_topics_version() {
	return '2.8.80';
}

/**
 * Get the BuddyBoss Platform min version for a activity post feature image.
 *
 * @since 2.9.0
 *
 * @return string
 */
function bb_platform_activity_post_feature_image_version() {
	return '2.13.0';
}
