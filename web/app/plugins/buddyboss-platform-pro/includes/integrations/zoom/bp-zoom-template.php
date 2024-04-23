<?php
/**
 * BuddyBoss Zoom Template Functions.
 *
 * @package BuddyBoss\Zoom\Templates
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the meeting loop.
 *
 * Based on the $args passed, bp_has_meeting() populates the
 * $meeting_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of meeting items.
 *
 * @since 1.0.0

 * @global object $meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the meeting loop. Most arguments
 *     are in the same format as {@link BP_Zoom_Meeting::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_zoom_meetings() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'group_id=4&fields=all').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           meeting fields to retrieve. 'all' to fetch entire meeting objects,
 *                                               'ids' to get only the meeting IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total meeting items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of meeting IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact meeting IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single meeting item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 * }
 * @return bool Returns true when meetings found, otherwise false.
 */
function bp_has_zoom_meetings( $args = '' ) {
	global $zoom_meeting_template, $bp_zoom_current_meeting, $bp_zoom_current_webinar;

	/*
	 * Smart Defaults.
	 */

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'meeting' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	$group_id = false;
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$group_id = bp_get_current_group_id();
	}

	$zoom_type = false;
	$sort      = 'ASC';
	$since     = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
	$from      = false;
	if ( bp_is_current_action( 'zoom' ) && ( ( ! empty( $bp_zoom_current_meeting ) && true === $bp_zoom_current_meeting->is_past && false === $bp_zoom_current_meeting->is_live ) || ( 'past-meetings' === bp_zoom_group_current_meeting_tab() ) ) ) {
		$from  = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
		$since = false;
		$sort  = 'DESC';
	} elseif ( bp_is_current_action( 'zoom' ) && ( ( ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) || ( 'past-webinars' === bp_zoom_group_current_meeting_tab() ) ) ) {
		$from  = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
		$since = false;
		$sort  = 'DESC';
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'       => false,           // Pass an meeting_id or string of IDs comma-separated.
			'exclude'       => false,           // Pass an activity_id or string of IDs comma-separated.
			'sort'          => $sort,           // Sort DESC or ASC.
			'live'          => false,           // Live meetings.
			'order_by'      => false,           // Order by. Default: start_date_utc.
			'page'          => 1,               // Which page to load.
			'per_page'      => 20,              // Number of items per page.
			'page_arg'      => 'acpage',        // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'           => false,           // Max number to return.
			'fields'        => 'all',
			'count_total'   => false,

			// Filtering.
			'group_id'      => $group_id,        // group_id to filter on.
			'meeting_id'    => false,            // meeting_id to filter on.
			'since'         => $since,           // Return only items recorded since this Y-m-d H:i:s date.
			'from'          => $from,            // Return only items recorded from this Y-m-d H:i:s date.
			'recorded'      => false,            // Return only items which have recordings.
			'recurring'     => false,            // Return only recurring items.
			'meta_query'    => false,            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'hide_sitewide' => false,            // Hide sitewide.
			'zoom_type'     => $zoom_type,       // Zoom meeting type.

			// Searching.
			'search_terms'  => $search_terms_default,
		),
		'has_meeting'
	);

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	$s = bb_pro_filter_input_string( INPUT_GET, 's' );
	if ( ! empty( $s ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $s;
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$zoom_meeting_template = new BP_Zoom_Meeting_Template( $r );

	/**
	 * Filters whether or not there are meeting items to display.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $value               Whether or not there are meeting items to display.
	 * @param string $zoom_meeting_template      Current meeting template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Zoom_Meeting_Template class.
	 */
	return apply_filters( 'bp_has_zoom_meetings', $zoom_meeting_template->has_meeting(), $zoom_meeting_template, $r );
}

/**
 * Determine if there are still meeting left in the loop.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool Returns true when meeting are found.
 */
function bp_zoom_meeting() {
	global $zoom_meeting_template;
	return $zoom_meeting_template->user_meetings();
}

/**
 * Get the current meeting object in the loop.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return object The current meeting within the loop.
 */
function bp_the_zoom_meeting() {
	global $zoom_meeting_template;
	return $zoom_meeting_template->the_meeting();
}

/**
 * Output the URL for the Load More link.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_load_more_link() {
	echo esc_url( bp_get_zoom_meeting_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since 1.0.0
 *
 * @return string $link
 */
function bp_get_zoom_meeting_load_more_link() {
	global $zoom_meeting_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $zoom_meeting_template->pag_arg, $zoom_meeting_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $zoom_meeting_template The meeting template loop global.
	 */
	return apply_filters( 'bp_get_zoom_meeting_load_more_link', $link, $url, $zoom_meeting_template );
}

/**
 * Output the meeting pagination count.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 */
function bp_zoom_meeting_pagination_count() {
	echo esc_html( bp_get_zoom_meeting_pagination_count() );
}

/**
 * Return the meeting pagination count.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The pagination text.
 */
function bp_get_zoom_meeting_pagination_count() {
	global $zoom_meeting_template;

	$start_num = intval( ( $zoom_meeting_template->pag_page - 1 ) * $zoom_meeting_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $zoom_meeting_template->pag_num - 1 ) > $zoom_meeting_template->total_meeting_count ) ? $zoom_meeting_template->total_meeting_count : $start_num + ( $zoom_meeting_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $zoom_meeting_template->total_meeting_count );

	$message = sprintf(
		/* translators: %1$s - from page number, %2$s - to page number, %3$s - total pages. */
		_n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $zoom_meeting_template->total_meeting_count, 'buddyboss-pro' ), // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders, WordPress.WP.I18n.MissingSingularPlaceholder
		$from_num,
		$to_num,
		$total
	);

	return $message;
}

/**
 * Output the meeting pagination links.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_pagination_links() {
	echo bp_get_zoom_meeting_pagination_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the meeting pagination links.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The pagination links.
 */
function bp_get_zoom_meeting_pagination_links() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting pagination link output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pag_links Output for the meeting pagination links.
	 */
	return apply_filters( 'bp_get_zoom_meeting_pagination_links', $zoom_meeting_template->pag_links );
}

/**
 * Return true when there are more meeting items to be shown than currently appear.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_zoom_meeting_has_more_items() {
	global $zoom_meeting_template;

	if ( ! empty( $zoom_meeting_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $zoom_meeting_template->pag_page ) ) {
			$remaining_pages = floor( ( $zoom_meeting_template->total_meeting_count - 1 ) / ( $zoom_meeting_template->pag_num * $zoom_meeting_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more meeting items to display.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $has_more_items Whether or not there are more meeting items to display.
	 */
	return apply_filters( 'bp_zoom_meeting_has_more_items', $has_more_items );
}

/**
 * Output the meeting count.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_count() {
	echo esc_html( bp_get_zoom_meeting_count() );
}

/**
 * Return the meeting count.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting count.
 */
function bp_get_zoom_meeting_count() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting count for the meeting template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $meeting_count The count for total meeting.
	 */
	return apply_filters( 'bp_get_zoom_meeting_count', (int) $zoom_meeting_template->meeting_count );
}

/**
 * Output the number of meeting per page.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_per_page() {
	echo esc_html( bp_get_zoom_meeting_per_page() );
}

/**
 * Return the number of meeting per page.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting per page.
 */
function bp_get_zoom_meeting_per_page() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting posts per page value.
	 *
	 * @since 1.0.0
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_zoom_meeting_per_page', (int) $zoom_meeting_template->pag_num );
}

/**
 * Output the meeting ID.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_id() {
	echo esc_html( bp_get_zoom_meeting_id() );
}

/**
 * Return the meeting ID.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting ID.
 */
function bp_get_zoom_meeting_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting ID being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The meeting group ID.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_id', $zoom_meeting_template->meeting->id );
}

/**
 * Output the meeting ID.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_group_id() {
	echo esc_html( bp_get_zoom_meeting_group_id() );
}

/**
 * Return the meeting ID.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting group ID.
 */
function bp_get_zoom_meeting_group_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting group ID being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id The meeting group ID.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_group_id', $zoom_meeting_template->meeting->group_id );
}

/**
 * Output the meeting user id.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_user_id() {
	echo esc_html( bp_get_zoom_meeting_user_id() );
}

/**
 * Return the meeting user id.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting user id.
 */
function bp_get_zoom_meeting_user_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting user id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The meeting user id.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_user_id', $zoom_meeting_template->meeting->user_id );
}

/**
 * Output the meeting host id.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_host_id() {
	echo esc_html( bp_get_zoom_meeting_host_id() );
}

/**
 * Return the meeting host id.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting host id.
 */
function bp_get_zoom_meeting_host_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting host id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $host_id The meeting host id.
	 */
	return apply_filters( 'bp_get_zoom_meeting_host_id', $zoom_meeting_template->meeting->host_id );
}

/**
 * Output the meeting title.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_title() {
	echo esc_html( bp_get_zoom_meeting_title() );
}

/**
 * Return the meeting title.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting title.
 */
function bp_get_zoom_meeting_title() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting title being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title The meeting title.
	 */
	return apply_filters( 'bp_get_zoom_meeting_title', $zoom_meeting_template->meeting->title );
}

/**
 * Output the meeting description.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_description() {
	echo esc_html( bp_get_zoom_meeting_description() );
}

/**
 * Return the meeting description.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting description.
 */
function bp_get_zoom_meeting_description() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting description being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $description The meeting description.
	 */
	return apply_filters( 'bp_get_zoom_meeting_description', $zoom_meeting_template->meeting->description );
}

/**
 * Output the meeting start date.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_start_date() {
	echo esc_html( bp_get_zoom_meeting_start_date() );
}

/**
 * Return the meeting start date.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting start date.
 */
function bp_get_zoom_meeting_start_date() {
	global $zoom_meeting_template;

	$start_date = wp_date( 'Y-m-d H:i:s', strtotime( $zoom_meeting_template->meeting->start_date_utc ), new DateTimeZone( $zoom_meeting_template->meeting->timezone ) );

	/**
	 * Filters the meeting start date being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date The meeting start date.
	 */
	return apply_filters( 'bp_get_zoom_meeting_start_date', $start_date );
}

/**
 * Output the meeting start date UTC.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_start_date_utc() {
	echo esc_html( bp_get_zoom_meeting_start_date_utc() );
}

/**
 * Return the meeting start date UTC.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting start date UTC.
 */
function bp_get_zoom_meeting_start_date_utc() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting start date UTC being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date The meeting start date UTC.
	 */
	return apply_filters( 'bp_get_zoom_meeting_start_date_utc', $zoom_meeting_template->meeting->start_date_utc );
}

/**
 * Output the meeting timezone.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_timezone() {
	echo esc_html( bp_get_zoom_meeting_timezone() );
}

/**
 * Return the meeting timezone.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting timezone.
 */
function bp_get_zoom_meeting_timezone() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting timezone being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone The meeting timezone.
	 */
	return apply_filters( 'bp_get_zoom_meeting_timezone', $zoom_meeting_template->meeting->timezone );
}

/**
 * Return the meeting authentication option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting authentication option.
 */
function bp_get_zoom_meeting_authentication() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting authentication being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $meeting_authentication The meeting authentication option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_authentication', $zoom_meeting_template->meeting->meeting_authentication );
}

/**
 * Output the meeting password.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_password() {
	echo esc_html( bp_get_zoom_meeting_password() );
}

/**
 * Return the meeting password.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting password.
 */
function bp_get_zoom_meeting_password() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting password.
	 *
	 * @since 1.0.0
	 *
	 * @param string $password The meeting password.
	 */
	return apply_filters( 'bp_get_zoom_meeting_password', $zoom_meeting_template->meeting->password );
}

/**
 * Return the meeting registration url.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @param int $id The meeting id.
 *
 * @return string The meeting Registration URL.
 */
function bp_get_zoom_meeting_registration_url( $id = 0 ) {
	global $zoom_meeting_template;

	if ( empty( $id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
		if ( ! empty( $zoom_meeting_template->meeting->parent ) ) {
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_template->meeting->parent );
			if ( ! empty( $meeting ) ) {
				$id = $meeting->id;
			}
		} else {
			$id = $zoom_meeting_template->meeting->id;
		}
	}

	$zoom_registration_url = bp_zoom_meeting_get_meta( $id, 'zoom_registration_url', true );

	/**
	 * Filters the meeting Registration URL
	 *
	 * @since 1.0.0
	 *
	 * @param string $zoom_registration_url The meeting enforce login option.
	 */
	return apply_filters( 'bp_get_zoom_meeting_registration_url', $zoom_registration_url );
}

/**
 * Return the meeting registration type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @param int $id The meeting id.
 *
 * @return int The meeting Registration type.
 */
function bp_get_zoom_meeting_registration_type( $id = 0 ) {
	global $zoom_meeting_template;

	if ( empty( $id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
		$id = $zoom_meeting_template->meeting->id;

		if ( ! empty( $zoom_meeting_template->meeting->parent ) ) {
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_template->meeting->parent );
			if ( ! empty( $meeting ) ) {
				$id = $meeting->id;
			}
		}
	}

	$registration_type = bp_zoom_meeting_get_meta( $id, 'zoom_registration_type', true );

	/**
	 * Filters the meeting Registration type.
	 *
	 * @since 1.0.0
	 *
	 * @param int $registration_type The meeting Registration type option.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_registration_type', $registration_type );
}

/**
 * Return the meeting waiting room option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting waiting room option.
 */
function bp_get_zoom_meeting_waiting_room() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting waiting room option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $waiting_room The meeting waiting room option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_waiting_room', $zoom_meeting_template->meeting->waiting_room );
}

/**
 * Return the meeting recurring option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting recurring option.
 */
function bp_get_zoom_meeting_recurring() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting recurring option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $recurring The meeting recurring option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_recurring', $zoom_meeting_template->meeting->recurring );
}

/**
 * Return the meeting recurring details.
 *
 * @since 1.0.4
 * @param int $meeting_id ID of the meeting.
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return array The meeting recurring details.
 */
function bp_get_zoom_meeting_recurring_details( $meeting_id = 0 ) {
	global $zoom_meeting_template;
	$zoom_details = false;

	if ( ! empty( $zoom_meeting_template->meeting->id ) ) {
		$zoom_details = bp_get_zoom_meeting_zoom_details();
	}

	if ( ! empty( $meeting_id ) ) {
		$zoom_details = bp_get_zoom_meeting_zoom_details( $meeting_id );
	}

	$recurrence = array();
	if ( ! empty( $zoom_details ) ) {

		if ( ! empty( $zoom_details['recurrence'] ) ) {
			$recurrence['recurrence'] = $zoom_details['recurrence'];
		}

		if ( ! empty( $zoom_details['occurrences'] ) ) {
			$recurrence['occurrences'] = $zoom_details['occurrences'];
		}
	}

	/**
	 * Filters the meeting start url.
	 *
	 * @since 1.0.4
	 *
	 * @param array $recurrence The meeting recurring details.
	 */
	return (array) apply_filters( 'bp_get_zoom_meeting_recurring_details', $recurrence );
}

/**
 * Return the meeting parent.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting parent option.
 */
function bp_get_zoom_meeting_parent() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting parent option.
	 *
	 * @since 1.0.4
	 *
	 * @param string $parent The meeting parent option.
	 */
	return apply_filters( 'bp_get_zoom_meeting_parent', $zoom_meeting_template->meeting->parent );
}

/**
 * Return the meeting type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting type.
 */
function bp_get_zoom_meeting_type() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting type.
	 *
	 * @since 1.0.4
	 *
	 * @param int $type The meeting type.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_type', $zoom_meeting_template->meeting->type );
}

/**
 * Return the meeting zoom type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting zoom type.
 */
function bp_get_zoom_meeting_zoom_type() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting zoom type.
	 *
	 * @since 1.0.4
	 *
	 * @param string $zoom_type The meeting zoom type.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_type', $zoom_meeting_template->meeting->zoom_type );
}

/**
 * Output the meeting duration.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_duration() {
	echo esc_html( bp_get_zoom_meeting_duration() );
}

/**
 * Return the meeting duration.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_duration() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $duration The meeting duration.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_duration', $zoom_meeting_template->meeting->duration );
}

/**
 * Output the meeting activity id.
 *
 * @since 1.0.4
 */
function bp_zoom_meeting_activity_id() {
	echo esc_html( bp_get_zoom_meeting_activity_id() );
}

/**
 * Return the meeting activity id.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return int The meeting activity id.
 */
function bp_get_zoom_meeting_activity_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting activity id being displayed.
	 *
	 * @since 1.0.4
	 *
	 * @param int $duration The meeting activity id.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_activity_id', $zoom_meeting_template->meeting->activity_id );
}

/**
 * Output the meeting join before host option.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_join_before_host() {
	echo esc_html( bp_get_zoom_meeting_join_before_host() );
}

/**
 * Return the meeting join before host option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting join before host option.
 */
function bp_get_zoom_meeting_join_before_host() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting join before host option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $join_before_host The meeting join before host option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_join_before_host', $zoom_meeting_template->meeting->join_before_host );
}

/**
 * Output the meeting host video option.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_host_video() {
	echo esc_html( bp_get_zoom_meeting_host_video() );
}

/**
 * Return the meeting host video option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting host video option.
 */
function bp_get_zoom_meeting_host_video() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting host video option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $host_video The meeting host video option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_host_video', $zoom_meeting_template->meeting->host_video );
}

/**
 * Output the meeting participants video option.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_participants_video() {
	echo esc_html( bp_get_zoom_meeting_participants_video() );
}

/**
 * Return the meeting participants video option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting participants video option.
 */
function bp_get_zoom_meeting_participants_video() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting participants video option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $participants_video The meeting participants video option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_participants_video', $zoom_meeting_template->meeting->participants_video );
}

/**
 * Output the meeting mute participants option.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_mute_participants() {
	echo esc_html( bp_get_zoom_meeting_mute_participants() );
}

/**
 * Return the meeting mute participants option.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return bool The meeting mute participants option.
 */
function bp_get_zoom_meeting_mute_participants() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting mute participants option.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $mute_participants The meeting mute participants option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_mute_participants', $zoom_meeting_template->meeting->mute_participants );
}

/**
 * Output the meeting auto recording.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_auto_recording() {
	echo esc_html( bp_get_zoom_meeting_auto_recording() );
}

/**
 * Return the meeting auto recording.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting auto recording.
 */
function bp_get_zoom_meeting_auto_recording() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting auto recording.
	 *
	 * @since 1.0.0
	 *
	 * @param string $auto_recording The meeting auto recording.
	 */
	return apply_filters( 'bp_get_zoom_meeting_auto_recording', $zoom_meeting_template->meeting->auto_recording );
}

/**
 * Return the meeting alternative host ids.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting alternative host ids.
 */
function bp_get_zoom_meeting_alternative_host_ids() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting alternative host ids.
	 *
	 * @since 1.0.0
	 *
	 * @param string $alternative_host_ids The meeting alternative host ids.
	 */
	return apply_filters( 'bp_get_zoom_meeting_alternative_host_ids', $zoom_meeting_template->meeting->alternative_host_ids );
}

/**
 * Return the meeting details from zoom api.
 *
 * @since 1.0.0
 * @param int $meeting_id ID of the meeting.
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return array The meeting details from zoom api.
 */
function bp_get_zoom_meeting_zoom_details( $meeting_id = 0 ) {
	global $zoom_meeting_template;
	$zoom_details = false;

	if ( ! empty( $zoom_meeting_template->meeting->zoom_details ) ) {
		$zoom_details = $zoom_meeting_template->meeting->zoom_details;
	}

	if ( ! empty( $meeting_id ) ) {
		$zoom_details = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_details', true );
	}

	/**
	 * Filters the meeting details from zoom api.
	 *
	 * @since 1.0.0
	 *
	 * @param array $zoom_details The meeting details from zoom api.
	 */
	return (array) apply_filters( 'bp_get_zoom_meeting_zoom_details', json_decode( $zoom_details, true ) );
}

/**
 * Return the meeting start url.
 *
 * @since 1.0.0
 * @param int $meeting_id ID of the meeting.
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting start url.
 */
function bp_get_zoom_meeting_zoom_start_url( $meeting_id = 0 ) {
	global $zoom_meeting_template;
	$zoom_start_url = '';

	if ( ! empty( $zoom_meeting_template->meeting->parent ) ) {
		$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_template->meeting->parent );
		if ( ! empty( $meeting ) ) {
			$meeting_id = $meeting->id;

			$zoom_start_url = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_start_url', true );
		}
	}

	if ( empty( $zoom_start_url ) && ! empty( $zoom_meeting_template->meeting->start_url ) ) {
		$zoom_start_url = $zoom_meeting_template->meeting->start_url;
	}

	if ( empty( $zoom_start_url ) && ! empty( $meeting_id ) ) {
		$zoom_start_url = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_start_url', true );
	}

	/**
	 * Filters the meeting start url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $zoom_start_url The meeting start url.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_start_url', $zoom_start_url );
}

/**
 * Output the meeting id.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_zoom_meeting_id() {
	echo esc_html( bp_get_zoom_meeting_zoom_meeting_id() );
}

/**
 * Return the zoom meeting id.
 *
 * @since 1.0.0
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The zoom meeting id.
 */
function bp_get_zoom_meeting_zoom_meeting_id() {
	global $zoom_meeting_template;

	$zoom_meeting_id = $zoom_meeting_template->meeting->meeting_id;
	if ( ! empty( $zoom_meeting_template->meeting->parent ) ) {
		$zoom_meeting_id = $zoom_meeting_template->meeting->parent;
	}

	/**
	 * Filters the zoom meeting id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $zoom_meeting_id The zoom meeting id.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_meeting_id', $zoom_meeting_id );
}

/**
 * Output the meeting id.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_occurrence_id() {
	echo esc_html( bp_get_zoom_meeting_occurrence_id() );
}

/**
 * Return the zoom meeting occurrence id.
 *
 * @since 1.0.4
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The zoom meeting occurrence id.
 */
function bp_get_zoom_meeting_occurrence_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the zoom meeting occurrence id.
	 *
	 * @since 1.0.4
	 *
	 * @param string $meeting_id The zoom meeting occurrence id.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_meeting_id', $zoom_meeting_template->meeting->meeting_id );
}

/**
 * Return the meeting join url.
 *
 * @since 1.0.0
 * @param int $meeting_id ID of the meeting.
 *
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @return string The meeting join url.
 */
function bp_get_zoom_meeting_zoom_join_url( $meeting_id = 0 ) {
	global $zoom_meeting_template;
	$zoom_join_url = '';

	if ( ! empty( $zoom_meeting_template->meeting->parent ) ) {
		$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_template->meeting->parent );
		if ( ! empty( $meeting ) ) {
			$meeting_id = $meeting->id;

			$zoom_join_url = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_join_url', true );
		}
	}

	if ( empty( $zoom_join_url ) && ! empty( $zoom_meeting_template->meeting->join_url ) ) {
		$zoom_join_url = $zoom_meeting_template->meeting->join_url;
	}

	if ( empty( $zoom_join_url ) && ! empty( $meeting_id ) ) {
		$meeting = new BP_Zoom_Meeting( $meeting_id );
		if ( ! empty( $meeting->parent ) ) {
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );
			if ( ! empty( $meeting ) ) {
				$meeting_id = $meeting->id;
			}
		}
		$zoom_join_url = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_join_url', true );
	}

	/**
	 * Filters the meeting join url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $zoom_join_url The meeting join url.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_join_url', $zoom_join_url );
}

/**
 * Return the meeting recording count.
 *
 * @param int $id                    ID of the meeting.
 *
 * @return int The meeting recording count.
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @since 1.0.5
 */
function bp_get_zoom_meeting_recording_count( $id = 0 ) {
	global $zoom_meeting_template;
	$recording_count = 0;

	if ( empty( $id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
		$id = $zoom_meeting_template->meeting->id;
	}

	if ( ! empty( $id ) ) {
		$recording_count = bp_zoom_meeting_get_meta( $id, 'zoom_recording_count', true );
	}

	/**
	 * Filters the meeting recording count.
	 *
	 * @param int $recording_count The meeting recording count.
	 *
	 * @since 1.0.5
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_recording_count', $recording_count );
}

/**
 * Output the meeting url.
 *
 * @param int $group_id Current Group ID.
 * @param int $meeting_id Current Meeting ID.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_url( $group_id = 0, $meeting_id = 0 ) {
	echo esc_url( bp_get_zoom_meeting_url( $group_id, $meeting_id ) );
}

/**
 * Return the meeting url.
 *
 * @param int $group_id Current Group ID.
 * @param int $meeting_id Current Meeting ID.
 *
 * @return bool|mixed|void
 * @since 1.0.0
 */
function bp_get_zoom_meeting_url( $group_id, $meeting_id ) {

	if ( empty( $group_id ) ) {
		$group_id = bp_get_zoom_meeting_group_id();
	}

	if ( empty( $meeting_id ) ) {
		$meeting_id = bp_get_zoom_meeting_id();
	}

	$group = groups_get_group( $group_id );

	if ( empty( $group_id ) || empty( $meeting_id ) || empty( $group ) ) {
		return false;
	}

	/**
	 * Filters the meeting url.
	 *
	 * @param string $meeting_url The meeting url.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'bp_get_zoom_meeting_url', trailingslashit( bp_get_group_permalink( $group ) . 'zoom/meetings/' . $meeting_id ), $group_id, $meeting_id );
}

/**
 * Output the meeting is past or not.
 *
 * @since 1.0.0
 */
function bp_zoom_meeting_is_past() {
	echo esc_html( bp_get_zoom_meeting_is_past() );
}

/**
 * Return to check meeting is past or not.
 *
 * @return bool
 * @since 1.0.0
 */
function bp_get_zoom_meeting_is_past() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting is past or not.
	 *
	 * @param boolean $is_past The meeting is past or not.
	 *
	 * @since 1.0.0
	 */
	return (bool) apply_filters( 'bp_get_zoom_meeting_is_past', $zoom_meeting_template->meeting->is_past );
}

/**
 * Return the meeting invitation.
 *
 * @param int    $meeting_id            ID of the meeting.
 * @param int    $id                    ID of the meeting in the site.
 * @param string $join_url              Join URL of the meeting.
 *
 * @return int The meeting invitation.
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @since 1.0.0
 */
function bp_get_zoom_meeting_invitation( $meeting_id = 0, $id = 0, $join_url = '' ) {
	global $zoom_meeting_template;
	$invitation = '';
	$meeting    = false;

	if ( ! empty( $zoom_meeting_template->meeting->invitation ) ) {
		$invitation = $zoom_meeting_template->meeting->invitation;
		$meeting    = $zoom_meeting_template->meeting;
	}

	if ( empty( $invitation ) && ! empty( $meeting_id ) ) {
		if ( ! empty( $id ) ) {
			$meeting = new BP_Zoom_Meeting( $id );
		} else {
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
		}

		if ( ! empty( $meeting->group_id ) ) {
			// Connect to Zoom.
			bb_zoom_group_connect_api( $meeting->group_id );
		}

		$invitation_response = get_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );

		if ( empty( $invitation_response ) ) {
			$invitation_response = bp_zoom_conference()->meeting_invitation( $meeting_id );

			if ( 200 === $invitation_response['code'] && ! empty( $invitation_response['response'] ) ) {
				$invitation = $invitation_response['response']->invitation;

				set_transient( 'bp_zoom_meeting_invitation_' . $meeting_id, $invitation, 2 * HOUR_IN_SECONDS );

				if ( ! empty( $id ) ) {
					bp_zoom_meeting_update_meta( $id, 'zoom_meeting_invitation', $invitation );
				}
			}
		} else {
			$invitation = $invitation_response;
		}
	}

	// search and replace meeting join url with rewrited url.
	if ( ! empty( $meeting->id ) ) {
		$join_url = bp_zoom_meeting_get_meta( $meeting->id, 'zoom_join_url', true );
		if ( ! empty( $join_url ) ) {
			$invitation = str_replace( $join_url, bp_zoom_get_meeting_rewrite_url( $join_url, $meeting->id ), $invitation );
		}
	} elseif ( ! empty( $meeting_id ) && ! empty( $join_url ) ) {
		$invitation = str_replace( $join_url, bp_zoom_get_meeting_rewrite_url( $join_url ), $invitation );
	}

	/**
	 * Filters the meeting invitation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $invitation The meeting invitation.
	 */
	return apply_filters( 'bp_get_zoom_meeting_invitation', $invitation );
}

/**
 * Meeting container classes.
 *
 * @since 1.0.0
 * @param array $classes Class names.
 */
function bp_zoom_meeting_group_classes( $classes = array() ) {
	global $bp_zoom_current_meeting;

	if ( bp_zoom_is_groups_zoom() ) {
		if ( bp_zoom_is_single_meeting() ) {
			if ( ! empty( $bp_zoom_current_meeting ) ) {
				if ( true === $bp_zoom_current_meeting->is_past ) {
					$classes[] = 'bp-past-meeting';
				} elseif ( false === $bp_zoom_current_meeting->is_past ) {
					$classes[] = 'bp-future-meeting';
				}
			}
		}
		if ( bp_zoom_is_create_meeting() ) {
			$classes[] = 'bp-create-meeting';
		}
		if ( bp_zoom_is_edit_meeting() ) {
			$classes[] = 'bp-edit-meeting';
		}
	}

	$classes = apply_filters( 'bp_zoom_meeting_group_classes', $classes );

	echo esc_html( implode( ' ', $classes ) );
}

/**
 * Get Meeting loop classes.
 *
 * @since 1.0.5
 */
function bp_zoom_meeting_loop_classes() {
	echo esc_html( bp_get_zoom_meeting_loop_classes() );
}

/**
 * Return Meeting loop classes.
 *
 * @since 1.0.5
 */
function bp_get_zoom_meeting_loop_classes() {
	global $bp_zoom_current_meeting;

	$classes = ( ! empty( $bp_zoom_current_meeting->id ) && bp_get_zoom_meeting_id() === $bp_zoom_current_meeting->id ) ? ' current' : '';

	/**
	 * Filters zoom meeting loop classes.
	 *
	 * @param string $classes CSS classes.
	 *
	 * @since 1.0.5
	 */
	return apply_filters( 'bp_zoom_meeting_loop_classes', $classes );
}

/**
 * Return Meeting alert time.
 *
 * @since 1.0.9
 */
function bp_get_zoom_meeting_alert() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting alert time.
	 *
	 * @since 1.0.9
	 *
	 * @param int $alert The meeting alert.
	 */
	return (int) apply_filters( 'bp_get_zoom_meeting_alert', $zoom_meeting_template->meeting->alert );
}


/************************ Webinar Template Funtions ************************/

/**
 * Initialize the webinar loop.
 *
 * Based on the $args passed, bp_has_webinar() populates the
 * $webinar_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of webinar items.
 *
 * @since 1.0.9

 * @global object $webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the webinar loop. Most arguments
 *     are in the same format as {@link BP_Zoom_Webinar::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_zoom_webinars() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'group_id=4&fields=all').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           webinar fields to retrieve. 'all' to fetch entire webinar objects,
 *                                               'ids' to get only the webinar IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total webinar items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of webinar IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact webinar IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single webinar item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 * }
 * @return bool Returns true when webinars found, otherwise false.
 */
function bp_has_zoom_webinars( $args = '' ) {
	global $zoom_webinar_template, $bp_zoom_current_webinar;

	/*
	 * Smart Defaults.
	 */

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'webinar' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	$group_id = false;
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$group_id = bp_get_current_group_id();
	}

	$zoom_type = false;
	$sort      = 'ASC';
	$since     = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
	$from      = false;
	if ( bp_is_current_action( 'zoom' ) && ( ( ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) || ( 'past-webinars' === bp_zoom_group_current_tab() ) ) ) {
		$from  = wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) );
		$since = false;
		$sort  = 'DESC';
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'       => false,           // Pass an webinar_id or string of IDs comma-separated.
			'exclude'       => false,           // Pass an activity_id or string of IDs comma-separated.
			'sort'          => $sort,           // Sort DESC or ASC.
			'live'          => false,           // Live webinars.
			'order_by'      => false,           // Order by. Default: start_date_utc.
			'page'          => 1,               // Which page to load.
			'per_page'      => 20,              // Number of items per page.
			'page_arg'      => 'acpage',        // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'           => false,           // Max number to return.
			'fields'        => 'all',
			'count_total'   => false,

			// Filtering.
			'group_id'      => $group_id,        // group_id to filter on.
			'webinar_id'    => false,            // webinar_id to filter on.
			'since'         => $since,           // Return only items recorded since this Y-m-d H:i:s date.
			'from'          => $from,            // Return only items recorded from this Y-m-d H:i:s date.
			'recorded'      => false,            // Return only items which have recordings.
			'recurring'     => false,            // Return only recurring items.
			'meta_query'    => false,            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'hide_sitewide' => false,            // Hide sitewide.
			'zoom_type'     => $zoom_type,       // Zoom webinar type.

			// Searching.
			'search_terms'  => $search_terms_default,
		),
		'has_webinar'
	);

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	$s = bb_pro_filter_input_string( INPUT_GET, 's' );
	if ( ! empty( $s ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $s;
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$zoom_webinar_template = new BP_Zoom_Webinar_Template( $r );

	/**
	 * Filters whether or not there are webinar items to display.
	 *
	 * @since 1.0.9
	 *
	 * @param bool   $value               Whether or not there are webinar items to display.
	 * @param string $zoom_webinar_template      Current webinar template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Zoom_Webinar_Template class.
	 */
	return apply_filters( 'bp_has_zoom_webinars', $zoom_webinar_template->has_webinar(), $zoom_webinar_template, $r );
}

/**
 * Determine if there are still webinar left in the loop.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool Returns true when webinar are found.
 */
function bp_zoom_webinar() {
	global $zoom_webinar_template;
	return $zoom_webinar_template->user_webinars();
}

/**
 * Get the current webinar object in the loop.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return object The current webinar within the loop.
 */
function bp_the_zoom_webinar() {
	global $zoom_webinar_template;
	return $zoom_webinar_template->the_webinar();
}

/**
 * Output the URL for the Load More link.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_load_more_link() {
	echo esc_url( bp_get_zoom_webinar_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since 1.0.9
 *
 * @return string $link
 */
function bp_get_zoom_webinar_load_more_link() {
	global $zoom_webinar_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $zoom_webinar_template->pag_arg, $zoom_webinar_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since 1.0.9
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $zoom_webinar_template The webinar template loop global.
	 */
	return apply_filters( 'bp_get_zoom_webinar_load_more_link', $link, $url, $zoom_webinar_template );
}

/**
 * Output the webinar pagination count.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 */
function bp_zoom_webinar_pagination_count() {
	echo esc_html( bp_get_zoom_webinar_pagination_count() );
}

/**
 * Return the webinar pagination count.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The pagination text.
 */
function bp_get_zoom_webinar_pagination_count() {
	global $zoom_webinar_template;

	$start_num = intval( ( $zoom_webinar_template->pag_page - 1 ) * $zoom_webinar_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $zoom_webinar_template->pag_num - 1 ) > $zoom_webinar_template->total_webinar_count ) ? $zoom_webinar_template->total_webinar_count : $start_num + ( $zoom_webinar_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $zoom_webinar_template->total_webinar_count );

	$message = sprintf(
	/* translators: %1$s - from page number, %2$s - to page number, %3$s - total pages. */
		_n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $zoom_webinar_template->total_webinar_count, 'buddyboss-pro' ), // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders, WordPress.WP.I18n.MissingSingularPlaceholder
		$from_num,
		$to_num,
		$total
	);

	return $message;
}

/**
 * Output the webinar pagination links.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_pagination_links() {
	echo bp_get_zoom_webinar_pagination_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the webinar pagination links.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The pagination links.
 */
function bp_get_zoom_webinar_pagination_links() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar pagination link output.
	 *
	 * @since 1.0.9
	 *
	 * @param string $pag_links Output for the webinar pagination links.
	 */
	return apply_filters( 'bp_get_zoom_webinar_pagination_links', $zoom_webinar_template->pag_links );
}

/**
 * Return true when there are more webinar items to be shown than currently appear.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_zoom_webinar_has_more_items() {
	global $zoom_webinar_template;

	if ( ! empty( $zoom_webinar_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $zoom_webinar_template->pag_page ) ) {
			$remaining_pages = floor( ( $zoom_webinar_template->total_webinar_count - 1 ) / ( $zoom_webinar_template->pag_num * $zoom_webinar_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more webinar items to display.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $has_more_items Whether or not there are more webinar items to display.
	 */
	return apply_filters( 'bp_zoom_webinar_has_more_items', $has_more_items );
}

/**
 * Output the webinar count.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_count() {
	echo esc_html( bp_get_zoom_webinar_count() );
}

/**
 * Return the webinar count.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar count.
 */
function bp_get_zoom_webinar_count() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar count for the webinar template.
	 *
	 * @since 1.0.9
	 *
	 * @param int $webinar_count The count for total webinar.
	 */
	return apply_filters( 'bp_get_zoom_webinar_count', (int) $zoom_webinar_template->webinar_count );
}

/**
 * Output the number of webinar per page.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_per_page() {
	echo esc_html( bp_get_zoom_webinar_per_page() );
}

/**
 * Return the number of webinar per page.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar per page.
 */
function bp_get_zoom_webinar_per_page() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar posts per page value.
	 *
	 * @since 1.0.9
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_zoom_webinar_per_page', (int) $zoom_webinar_template->pag_num );
}

/**
 * Output the webinar ID.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_id() {
	echo esc_html( bp_get_zoom_webinar_id() );
}

/**
 * Return the webinar ID.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar ID.
 */
function bp_get_zoom_webinar_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar ID being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param int $id The webinar group ID.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_id', $zoom_webinar_template->webinar->id );
}

/**
 * Output the webinar ID.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_group_id() {
	echo esc_html( bp_get_zoom_webinar_group_id() );
}

/**
 * Return the webinar ID.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar group ID.
 */
function bp_get_zoom_webinar_group_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar group ID being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_id The webinar group ID.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_group_id', $zoom_webinar_template->webinar->group_id );
}

/**
 * Output the webinar user id.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_user_id() {
	echo esc_html( bp_get_zoom_webinar_user_id() );
}

/**
 * Return the webinar user id.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar user id.
 */
function bp_get_zoom_webinar_user_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar user id.
	 *
	 * @since 1.0.9
	 *
	 * @param int $user_id The webinar user id.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_user_id', $zoom_webinar_template->webinar->user_id );
}

/**
 * Output the webinar host id.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_host_id() {
	echo esc_html( bp_get_zoom_webinar_host_id() );
}

/**
 * Return the webinar host id.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar host id.
 */
function bp_get_zoom_webinar_host_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar host id.
	 *
	 * @since 1.0.9
	 *
	 * @param string $host_id The webinar host id.
	 */
	return apply_filters( 'bp_get_zoom_webinar_host_id', $zoom_webinar_template->webinar->host_id );
}

/**
 * Output the webinar title.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_title() {
	echo esc_html( bp_get_zoom_webinar_title() );
}

/**
 * Return the webinar title.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar title.
 */
function bp_get_zoom_webinar_title() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar title being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param string $title The webinar title.
	 */
	return apply_filters( 'bp_get_zoom_webinar_title', $zoom_webinar_template->webinar->title );
}

/**
 * Output the webinar description.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_description() {
	echo esc_html( bp_get_zoom_webinar_description() );
}

/**
 * Return the webinar description.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar description.
 */
function bp_get_zoom_webinar_description() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar description being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param string $description The webinar description.
	 */
	return apply_filters( 'bp_get_zoom_webinar_description', $zoom_webinar_template->webinar->description );
}

/**
 * Output the webinar start date.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_start_date() {
	echo esc_html( bp_get_zoom_webinar_start_date() );
}

/**
 * Return the webinar start date.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar start date.
 */
function bp_get_zoom_webinar_start_date() {
	global $zoom_webinar_template;

	$start_date = wp_date( 'Y-m-d H:i:s', strtotime( $zoom_webinar_template->webinar->start_date_utc ), new DateTimeZone( $zoom_webinar_template->webinar->timezone ) );
	/**
	 * Filters the webinar start date being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param string $start_date The webinar start date.
	 */
	return apply_filters( 'bp_get_zoom_webinar_start_date', $start_date );
}

/**
 * Output the webinar start date UTC.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_start_date_utc() {
	echo esc_html( bp_get_zoom_webinar_start_date_utc() );
}

/**
 * Return the webinar start date UTC.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar start date UTC.
 */
function bp_get_zoom_webinar_start_date_utc() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar start date UTC being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param string $start_date The webinar start date UTC.
	 */
	return apply_filters( 'bp_get_zoom_webinar_start_date_utc', $zoom_webinar_template->webinar->start_date_utc );
}

/**
 * Output the webinar timezone.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_timezone() {
	echo esc_html( bp_get_zoom_webinar_timezone() );
}

/**
 * Return the webinar timezone.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar timezone.
 */
function bp_get_zoom_webinar_timezone() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar timezone being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param string $timezone The webinar timezone.
	 */
	return apply_filters( 'bp_get_zoom_webinar_timezone', $zoom_webinar_template->webinar->timezone );
}

/**
 * Return the webinar authentication option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar authentication option.
 */
function bp_get_zoom_webinar_authentication() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar authentication being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $meeting_authentication The webinar authentication option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_authentication', $zoom_webinar_template->webinar->meeting_authentication );
}

/**
 * Output the webinar password.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_password() {
	echo esc_html( bp_get_zoom_webinar_password() );
}

/**
 * Return the webinar password.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar password.
 */
function bp_get_zoom_webinar_password() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar password.
	 *
	 * @since 1.0.9
	 *
	 * @param string $password The webinar password.
	 */
	return apply_filters( 'bp_get_zoom_webinar_password', $zoom_webinar_template->webinar->password );
}

/**
 * Return the webinar registration url.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @param int $id The webinar id.
 *
 * @return string The webinar Registration URL.
 */
function bp_get_zoom_webinar_registration_url( $id = 0 ) {
	global $zoom_webinar_template;

	if ( empty( $id ) && ! empty( $zoom_webinar_template->webinar->id ) ) {
		if ( ! empty( $zoom_webinar_template->webinar->parent ) ) {
			$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_template->webinar->parent );
			if ( ! empty( $webinar ) ) {
				$id = $webinar->id;
			}
		} else {
			$id = $zoom_webinar_template->webinar->id;
		}
	}

	$zoom_registration_url = bp_zoom_webinar_get_meta( $id, 'zoom_registration_url', true );

	/**
	 * Filters the webinar Registration URL
	 *
	 * @since 1.0.9
	 *
	 * @param string $zoom_registration_url The webinar enforce login option.
	 */
	return apply_filters( 'bp_get_zoom_webinar_registration_url', $zoom_registration_url );
}

/**
 * Return the webinar registration type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @param int $id The webinar id.
 *
 * @return int The webinar Registration type.
 */
function bp_get_zoom_webinar_registration_type( $id = 0 ) {
	global $zoom_webinar_template;

	if ( empty( $id ) && ! empty( $zoom_webinar_template->webinar->id ) ) {
		$id = $zoom_webinar_template->webinar->id;

		if ( ! empty( $zoom_webinar_template->webinar->parent ) ) {
			$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_template->webinar->parent );
			if ( ! empty( $webinar ) ) {
				$id = $webinar->id;
			}
		}
	}

	$registration_type = bp_zoom_webinar_get_meta( $id, 'zoom_registration_type', true );

	/**
	 * Filters the webinar Registration type.
	 *
	 * @since 1.0.9
	 *
	 * @param int $registration_type The webinar Registration type option.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_registration_type', $registration_type );
}

/**
 * Return the webinar recurring option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar recurring option.
 */
function bp_get_zoom_webinar_recurring() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar recurring option.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $recurring The webinar recurring option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_recurring', $zoom_webinar_template->webinar->recurring );
}

/**
 * Return the webinar recurring details.
 *
 * @since 1.0.4
 * @param int $webinar_id ID of the webinar.
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return array The webinar recurring details.
 */
function bp_get_zoom_webinar_recurring_details( $webinar_id = 0 ) {
	global $zoom_webinar_template;
	$zoom_details = false;

	if ( ! empty( $zoom_webinar_template->webinar->id ) ) {
		$zoom_details = bp_get_zoom_webinar_zoom_details();
	}

	if ( ! empty( $webinar_id ) ) {
		$zoom_details = bp_get_zoom_webinar_zoom_details( $webinar_id );
	}

	$recurrence = array();
	if ( ! empty( $zoom_details ) ) {

		if ( ! empty( $zoom_details['recurrence'] ) ) {
			$recurrence['recurrence'] = $zoom_details['recurrence'];
		}

		if ( ! empty( $zoom_details['occurrences'] ) ) {
			$recurrence['occurrences'] = $zoom_details['occurrences'];
		}
	}

	/**
	 * Filters the webinar start url.
	 *
	 * @since 1.0.4
	 *
	 * @param array $recurrence The webinar recurring details.
	 */
	return (array) apply_filters( 'bp_get_zoom_webinar_recurring_details', $recurrence );
}

/**
 * Return the webinar parent.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar parent option.
 */
function bp_get_zoom_webinar_parent() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar parent option.
	 *
	 * @since 1.0.4
	 *
	 * @param string $parent The webinar parent option.
	 */
	return apply_filters( 'bp_get_zoom_webinar_parent', $zoom_webinar_template->webinar->parent );
}

/**
 * Return the webinar type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar type.
 */
function bp_get_zoom_webinar_type() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar type.
	 *
	 * @since 1.0.4
	 *
	 * @param int $type The webinar type.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_type', $zoom_webinar_template->webinar->type );
}

/**
 * Return the webinar zoom type.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar zoom type.
 */
function bp_get_zoom_webinar_zoom_type() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar zoom type.
	 *
	 * @since 1.0.4
	 *
	 * @param string $zoom_type The webinar zoom type.
	 */
	return apply_filters( 'bp_get_zoom_webinar_zoom_type', $zoom_webinar_template->webinar->zoom_type );
}

/**
 * Output the webinar duration.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_duration() {
	echo esc_html( bp_get_zoom_webinar_duration() );
}

/**
 * Return the webinar duration.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar duration.
 */
function bp_get_zoom_webinar_duration() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar duration being displayed.
	 *
	 * @since 1.0.9
	 *
	 * @param int $duration The webinar duration.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_duration', $zoom_webinar_template->webinar->duration );
}

/**
 * Output the webinar activity id.
 *
 * @since 1.0.4
 */
function bp_zoom_webinar_activity_id() {
	echo esc_html( bp_get_zoom_webinar_activity_id() );
}

/**
 * Return the webinar activity id.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return int The webinar activity id.
 */
function bp_get_zoom_webinar_activity_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar activity id being displayed.
	 *
	 * @since 1.0.4
	 *
	 * @param int $duration The webinar activity id.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_activity_id', $zoom_webinar_template->webinar->activity_id );
}

/**
 * Output the webinar host video option.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_host_video() {
	echo esc_html( bp_get_zoom_webinar_host_video() );
}

/**
 * Return the webinar host video option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar host video option.
 */
function bp_get_zoom_webinar_host_video() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar host video option.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $host_video The webinar host video option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_host_video', $zoom_webinar_template->webinar->host_video );
}

/**
 * Output the webinar panelists video option.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_panelists_video() {
	echo esc_html( bp_get_zoom_webinar_panelists_video() );
}

/**
 * Return the webinar panelists video option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar panelists video option.
 */
function bp_get_zoom_webinar_panelists_video() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar panelists video option.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $panelists_video The webinar panelists video option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_panelists_video', $zoom_webinar_template->webinar->panelists_video );
}

/**
 * Output the webinar practice session option.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_practice_session() {
	echo esc_html( bp_get_zoom_webinar_practice_session() );
}

/**
 * Return the webinar practice session option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar panelists video option.
 */
function bp_get_zoom_webinar_practice_session() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar practice session option.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $participants_video The webinar practice session option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_practice_session', $zoom_webinar_template->webinar->practice_session );
}

/**
 * Output the webinar on demand option.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_on_demand() {
	echo esc_html( bp_get_zoom_webinar_on_demand() );
}

/**
 * Return the webinar on demand option.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return bool The webinar panelists video option.
 */
function bp_get_zoom_webinar_on_demand() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar on demand option.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $participants_video The webinar on demand option.
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_on_demand', $zoom_webinar_template->webinar->on_demand );
}

/**
 * Output the webinar auto recording.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_auto_recording() {
	echo esc_html( bp_get_zoom_webinar_auto_recording() );
}

/**
 * Return the webinar auto recording.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar auto recording.
 */
function bp_get_zoom_webinar_auto_recording() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar auto recording.
	 *
	 * @since 1.0.9
	 *
	 * @param string $auto_recording The webinar auto recording.
	 */
	return apply_filters( 'bp_get_zoom_webinar_auto_recording', $zoom_webinar_template->webinar->auto_recording );
}

/**
 * Return the webinar alternative host ids.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar alternative host ids.
 */
function bp_get_zoom_webinar_alternative_host_ids() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar alternative host ids.
	 *
	 * @since 1.0.9
	 *
	 * @param string $alternative_host_ids The webinar alternative host ids.
	 */
	return apply_filters( 'bp_get_zoom_webinar_alternative_host_ids', $zoom_webinar_template->webinar->alternative_host_ids );
}

/**
 * Return the webinar details from zoom api.
 *
 * @since 1.0.9
 * @param int $webinar_id ID of the webinar.
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return array The webinar details from zoom api.
 */
function bp_get_zoom_webinar_zoom_details( $webinar_id = 0 ) {
	global $zoom_webinar_template;
	$zoom_details = false;

	if ( ! empty( $zoom_webinar_template->webinar->zoom_details ) ) {
		$zoom_details = $zoom_webinar_template->webinar->zoom_details;
	}

	if ( ! empty( $webinar_id ) ) {
		$zoom_details = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_details', true );
	}

	/**
	 * Filters the webinar details from zoom api.
	 *
	 * @since 1.0.9
	 *
	 * @param array $zoom_details The webinar details from zoom api.
	 */
	return (array) apply_filters( 'bp_get_zoom_webinar_zoom_details', json_decode( $zoom_details, true ) );
}

/**
 * Return the webinar start url.
 *
 * @since 1.0.9
 * @param int $webinar_id ID of the webinar.
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar start url.
 */
function bp_get_zoom_webinar_zoom_start_url( $webinar_id = 0 ) {
	global $zoom_webinar_template;
	$zoom_start_url = '';

	if ( ! empty( $zoom_webinar_template->webinar->parent ) ) {
		$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_template->webinar->parent );
		if ( ! empty( $webinar ) ) {
			$webinar_id = $webinar->id;

			$zoom_start_url = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_start_url', true );
		}
	}

	if ( empty( $zoom_start_url ) && ! empty( $zoom_webinar_template->webinar->start_url ) ) {
		$zoom_start_url = $zoom_webinar_template->webinar->start_url;
	}

	if ( empty( $zoom_start_url ) && ! empty( $webinar_id ) ) {
		$zoom_start_url = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_start_url', true );
	}

	/**
	 * Filters the webinar start url.
	 *
	 * @since 1.0.9
	 *
	 * @param string $zoom_start_url The webinar start url.
	 */
	return apply_filters( 'bp_get_zoom_webinar_zoom_start_url', $zoom_start_url );
}

/**
 * Output the webinar id.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_zoom_webinar_id() {
	echo esc_html( bp_get_zoom_webinar_zoom_webinar_id() );
}

/**
 * Return the zoom webinar id.
 *
 * @since 1.0.9
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The zoom webinar id.
 */
function bp_get_zoom_webinar_zoom_webinar_id() {
	global $zoom_webinar_template;

	$zoom_webinar_id = $zoom_webinar_template->webinar->webinar_id;
	if ( ! empty( $zoom_webinar_template->webinar->parent ) ) {
		$zoom_webinar_id = $zoom_webinar_template->webinar->parent;
	}

	/**
	 * Filters the zoom webinar id.
	 *
	 * @since 1.0.9
	 *
	 * @param string $zoom_webinar_id The zoom webinar id.
	 */
	return apply_filters( 'bp_get_zoom_webinar_zoom_webinar_id', $zoom_webinar_id );
}

/**
 * Output the webinar id.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_occurrence_id() {
	echo esc_html( bp_get_zoom_webinar_occurrence_id() );
}

/**
 * Return the zoom webinar occurrence id.
 *
 * @since 1.0.4
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The zoom webinar occurrence id.
 */
function bp_get_zoom_webinar_occurrence_id() {
	global $zoom_webinar_template;

	/**
	 * Filters the zoom webinar occurrence id.
	 *
	 * @since 1.0.4
	 *
	 * @param string $webinar_id The zoom webinar occurrence id.
	 */
	return apply_filters( 'bp_get_zoom_webinar_zoom_webinar_id', $zoom_webinar_template->webinar->webinar_id );
}

/**
 * Return the webinar join url.
 *
 * @since 1.0.9
 * @param int $webinar_id ID of the webinar.
 *
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @return string The webinar join url.
 */
function bp_get_zoom_webinar_zoom_join_url( $webinar_id = 0 ) {
	global $zoom_webinar_template;
	$zoom_join_url = '';

	if ( ! empty( $zoom_webinar_template->webinar->parent ) ) {
		$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_template->webinar->parent );
		if ( ! empty( $webinar ) ) {
			$webinar_id = $webinar->id;

			$zoom_join_url = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_join_url', true );
		}
	}

	if ( empty( $zoom_join_url ) && ! empty( $zoom_webinar_template->webinar->join_url ) ) {
		$zoom_join_url = $zoom_webinar_template->webinar->join_url;
	}

	if ( empty( $zoom_join_url ) && ! empty( $webinar_id ) ) {
		$webinar = new BP_Zoom_Webinar( $webinar_id );
		if ( ! empty( $webinar->parent ) ) {
			$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );
			if ( ! empty( $webinar ) ) {
				$webinar_id = $webinar->id;
			}
		}
		$zoom_join_url = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_join_url', true );
	}

	/**
	 * Filters the webinar join url.
	 *
	 * @since 1.0.9
	 *
	 * @param string $zoom_join_url The webinar join url.
	 */
	return apply_filters( 'bp_get_zoom_webinar_zoom_join_url', $zoom_join_url );
}

/**
 * Return the webinar recording count.
 *
 * @param int $id                    ID of the webinar.
 *
 * @return int The webinar recording count.
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @since 1.0.5
 */
function bp_get_zoom_webinar_recording_count( $id = 0 ) {
	global $zoom_webinar_template;
	$recording_count = 0;

	if ( empty( $id ) && ! empty( $zoom_webinar_template->webinar->id ) ) {
		$id = $zoom_webinar_template->webinar->id;
	}

	if ( ! empty( $id ) ) {
		$recording_count = bp_zoom_webinar_get_meta( $id, 'zoom_recording_count', true );
	}

	/**
	 * Filters the webinar recording count.
	 *
	 * @param int $recording_count The webinar recording count.
	 *
	 * @since 1.0.5
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_recording_count', $recording_count );
}

/**
 * Output the webinar url.
 *
 * @param int $group_id Current Group ID.
 * @param int $webinar_id Current Meeting ID.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_url( $group_id = 0, $webinar_id = 0 ) {
	echo esc_url( bp_get_zoom_webinar_url( $group_id, $webinar_id ) );
}

/**
 * Return the webinar url.
 *
 * @param int $group_id Current Group ID.
 * @param int $webinar_id Current Meeting ID.
 *
 * @return bool|mixed|void
 * @since 1.0.9
 */
function bp_get_zoom_webinar_url( $group_id, $webinar_id ) {

	if ( empty( $group_id ) ) {
		$group_id = bp_get_zoom_webinar_group_id();
	}

	if ( empty( $webinar_id ) ) {
		$webinar_id = bp_get_zoom_webinar_id();
	}

	$group = groups_get_group( $group_id );

	if ( empty( $group_id ) || empty( $webinar_id ) || empty( $group ) ) {
		return false;
	}

	/**
	 * Filters the webinar url.
	 *
	 * @param string $webinar_url The webinar url.
	 *
	 * @since 1.0.9
	 */
	return apply_filters( 'bp_get_zoom_webinar_url', trailingslashit( bp_get_group_permalink( $group ) . 'zoom/webinars/' . $webinar_id ), $group_id, $webinar_id );
}

/**
 * Output the webinar is past or not.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_is_past() {
	echo esc_html( bp_get_zoom_webinar_is_past() );
}

/**
 * Return to check webinar is past or not.
 *
 * @return bool
 * @since 1.0.9
 */
function bp_get_zoom_webinar_is_past() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar is past or not.
	 *
	 * @param boolean $is_past The webinar is past or not.
	 *
	 * @since 1.0.9
	 */
	return (bool) apply_filters( 'bp_get_zoom_webinar_is_past', $zoom_webinar_template->webinar->is_past );
}

/**
 * Meeting container classes.
 *
 * @since 1.0.9
 * @param array $classes Class names.
 */
function bp_zoom_webinar_group_classes( $classes = array() ) {
	global $bp_zoom_current_webinar;

	if ( bp_zoom_is_groups_zoom() ) {
		if ( bp_zoom_is_single_webinar() ) {
			if ( ! empty( $bp_zoom_current_webinar ) ) {
				if ( true === $bp_zoom_current_webinar->is_past ) {
					$classes[] = 'bp-past-webinar';
				} elseif ( false === $bp_zoom_current_webinar->is_past ) {
					$classes[] = 'bp-future-webinar';
				}
			}
		}
		if ( bp_zoom_is_create_webinar() ) {
			$classes[] = 'bp-create-webinar';
		}
		if ( bp_zoom_is_edit_webinar() ) {
			$classes[] = 'bp-edit-webinar';
		}
	}

	$classes = apply_filters( 'bp_zoom_webinar_group_classes', $classes );

	echo esc_html( implode( ' ', $classes ) );
}

/**
 * Get Meeting loop classes.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_loop_classes() {
	echo esc_html( bp_get_zoom_webinar_loop_classes() );
}

/**
 * Return Meeting loop classes.
 *
 * @since 1.0.9
 */
function bp_get_zoom_webinar_loop_classes() {
	global $bp_zoom_current_webinar;

	$classes = ( ! empty( $bp_zoom_current_webinar->id ) && bp_get_zoom_webinar_id() === $bp_zoom_current_webinar->id ) ? ' current' : '';

	/**
	 * Filters zoom webinar loop classes.
	 *
	 * @param string $classes CSS classes.
	 *
	 * @since 1.0.9
	 */
	return apply_filters( 'bp_zoom_webinar_loop_classes', $classes );
}

/**
 * Return Webinar alert time.
 *
 * @since 1.0.9
 */
function bp_get_zoom_webinar_alert() {
	global $zoom_webinar_template;

	/**
	 * Filters the webinar alert time.
	 *
	 * @since 1.0.9
	 *
	 * @param int $alert The webinar alert.
	 */
	return (int) apply_filters( 'bp_get_zoom_webinar_alert', $zoom_webinar_template->webinar->alert );
}
