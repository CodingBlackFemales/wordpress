<?php
/**
 * Zoom integration helpers
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Zoom Integration path.
 *
 * @param string $path Path to zoom integration.
 * @since 1.0.0
 */
function bp_zoom_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'zoom/' . trim( $path, '/\\' );
}

/**
 * Returns Zoom Integration url.
 *
 * @param string $path Path to zoom integration.
 * @since 1.0.0
 */
function bp_zoom_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'zoom/' . trim( $path, '/\\' );
}

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 */
function bp_zoom_enqueue_scripts_and_styles() {
	global $wp;
	$rtl_css = is_rtl() ? '-rtl' : '';
	$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_style( 'bp-zoom', bp_zoom_integration_url( '/assets/css/bp-zoom' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );

	if ( ! wp_script_is( 'bp-nouveau-magnific-popup' ) ) {
		wp_enqueue_script( 'bp-nouveau-magnific-popup', buddypress()->plugin_url . 'bp-core/js/vendor/magnific-popup.js', array(), bp_get_version(), true );
	}
	wp_enqueue_script( 'bp-zoom-mask-js', trailingslashit( bb_platform_pro()->plugin_url ) . 'assets/js/vendor/jquery.mask.js', array(), '5.0.4', true );
	wp_enqueue_script( 'bp-zoom-js', bp_zoom_integration_url( '/assets/js/bp-zoom' . $min . '.js' ), array(), bb_platform_pro()->version, true );
	wp_enqueue_script( 'jquery-countdown', trailingslashit( bb_platform_pro()->plugin_url ) . 'assets/js/vendor/jquery.countdown' . $min . '.js', array(), '1.0.1', true );

	$meetings_url      = '';
	$past_meetings_url = '';
	$webinars_url      = '';
	$past_webinars_url = '';
	$group_id          = false;
	$api_key           = bp_zoom_api_key();
	if ( bp_is_group() ) {
		$group_id          = bp_get_current_group_id();
		$current_group     = groups_get_current_group();
		$group_link        = bp_get_group_permalink( $current_group );
		$meetings_url      = trailingslashit( $group_link . 'zoom' );
		$past_meetings_url = trailingslashit( $group_link . 'zoom/past-meetings' );
		$webinars_url      = trailingslashit( $group_link . 'zoom/webinars' );
		$past_webinars_url = trailingslashit( $group_link . 'zoom/past-webinars' );
		$api_key           = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
	}

	wp_localize_script(
		'bp-zoom-js',
		'bp_zoom_vars',
		array(
			'ajax_url'                => bp_core_ajax_url(),
			'home_url'                => home_url( $wp->request ),
			'is_single_meeting'       => bp_zoom_is_single_meeting(),
			'is_single_webinar'       => bp_zoom_is_single_webinar(),
			'group_id'                => $group_id,
			'group_meetings_url'      => $meetings_url,
			'group_meetings_past_url' => $past_meetings_url,
			'group_webinars_url'      => $webinars_url,
			'group_webinar_past_url'  => $past_webinars_url,
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'bp_staple'               => base64_encode( $api_key ), // Zoom API Key.
			'meeting_delete_nonce'    => wp_create_nonce( 'bp_zoom_meeting_delete' ),
			'meeting_confirm_msg'     => __( 'Are you sure you want to delete this meeting?', 'buddyboss-pro' ),
			'webinar_delete_nonce'    => wp_create_nonce( 'bp_zoom_webinar_delete' ),
			'webinar_confirm_msg'     => __( 'Are you sure you want to delete this webinar?', 'buddyboss-pro' ),
			'user'                    => array(
				'name'  => is_user_logged_in() ? bp_core_get_user_displayname( bp_loggedin_user_id() ) : __( 'Guest', 'buddyboss-pro' ),
				'email' => is_user_logged_in() ? bp_core_get_user_email( bp_loggedin_user_id() ) : 'guest@domain.com',
			),
			'scripts'                 => array(
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/react.production.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/react-dom.production.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/redux.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/redux-thunk.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/lodash.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/jquery.min.js' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/zoom-meeting-2.4.0.min.js' ),// For chrome 100, use 2.4.0 js. before that was zoom-meeting-2.3.0.min.js.
			),
			'styles'                  => array(
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/bootstrap.css' ),
				bp_zoom_integration_url( '/assets/js/zoom-web-sdk/react-select.css' ),
			),
			'strings'                 => array(
				'day'   => esc_html__( 'day', 'buddyboss-pro' ),
				'month' => esc_html__( 'month', 'buddyboss-pro' ),
				'week'  => esc_html__( 'week', 'buddyboss-pro' ),
			),
		)
	);

	$inline_js  = 'var $ = jQuery;';
	$inline_js .= 'var daysStr = "' . esc_html__( 'Days', 'buddyboss-pro' ) . '";';
	$inline_js .= 'var hoursStr = "' . esc_html__( 'Hours', 'buddyboss-pro' ) . '";';
	$inline_js .= 'var minutesStr = "' . esc_html__( 'Minutes', 'buddyboss-pro' ) . '";';
	$inline_js .= 'var secondsStr = "' . esc_html__( 'Seconds', 'buddyboss-pro' ) . '";';
	wp_add_inline_script( 'bp-zoom-js', $inline_js, 'before' );
}

add_action( 'wp_enqueue_scripts', 'bp_zoom_enqueue_scripts_and_styles', 19 );

/**
 * Retrieve an meeting or meetings.
 *
 * The bp_zoom_meeting_get() function shares all arguments with BP_Zoom_Meeting::get().
 * The following is a list of bp_zoom_meeting_get() parameters that have different
 * default values from BP_Zoom_Meeting::get() (value in parentheses is
 * the default for the bp_zoom_meeting_get()).
 *   - 'per_page' (false)
 *
 * @since 1.0.0
 *
 * @see BP_Zoom_Meeting::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Zoom_Meeting::get() for description.
 * @return array $meeting See BP_Zoom_Meeting::get() for description.
 */
function bp_zoom_meeting_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'           => false,        // Maximum number of results to return.
			'fields'        => 'all',
			'page'          => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,        // results per page.
			'sort'          => 'DESC',       // sort ASC or DESC.
			'order_by'      => false,       // order by.
			'live'          => false,       // Live meetings.
			'exclude'       => false,       // Exclude.

			// want to limit the query.
			'group_id'      => false,
			'meeting_id'    => false,
			'activity_id'   => false,
			'user_id'       => false,
			'parent'        => false,
			'since'         => false,
			'from'          => false,
			'recorded'      => false,
			'recurring'     => false,
			'meta_query'    => false, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'search_terms'  => false,        // Pass search terms as a string.
			'count_total'   => false,
			'hide_sitewide' => false,
			'zoom_type'     => false,
		),
		'meeting_get'
	);

	$meeting = BP_Zoom_Meeting::get(
		array(
			'page'          => $r['page'],
			'per_page'      => $r['per_page'],
			'group_id'      => $r['group_id'],
			'meeting_id'    => $r['meeting_id'],
			'activity_id'   => $r['activity_id'],
			'parent'        => $r['parent'],
			'user_id'       => $r['user_id'],
			'since'         => $r['since'],
			'from'          => $r['from'],
			'max'           => $r['max'],
			'sort'          => $r['sort'],
			'live'          => $r['live'],
			'exclude'       => $r['exclude'],
			'order_by'      => $r['order_by'],
			'search_terms'  => $r['search_terms'],
			'count_total'   => $r['count_total'],
			'fields'        => $r['fields'],
			'recorded'      => $r['recorded'],
			'recurring'     => $r['recurring'],
			'meta_query'    => $r['meta_query'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'hide_sitewide' => $r['hide_sitewide'],
			'zoom_type'     => $r['zoom_type'],
		)
	);

	/**
	 * Filters the requested meeting item(s).
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Zoom_Meeting  $meeting Requested meeting object.
	 * @param array     $r     Arguments used for the meeting query.
	 */
	return apply_filters_ref_array( 'bp_zoom_meeting_get', array( &$meeting, &$r ) );
}

/**
 * Fetch specific meeting items.
 *
 * @param array $args { All arguments and defaults are shared with BP_Zoom_Meeting::get(), except for the following.
 * @type string|int|array Single meeting ID, comma-separated list of IDs, or array of IDs.
 * }
 *
 * @return array $activity See BP_Zoom_Meeting::get() for description.
 * @since 1.0.0
 *
 * @see   BP_Zoom_Meeting::get() For more information on accepted arguments.
 */
function bp_zoom_meeting_get_specific( $args = array() ) {

	$r = bp_parse_args(
		$args,
		array(
			'meeting_ids'   => false,      // A single meeting_id or array of IDs.
			'max'           => false,      // Maximum number of results to return.
			'page'          => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,      // Results per page.
			'sort'          => 'DESC',     // Sort ASC or DESC.
			'live'          => false,     // Sort ASC or DESC.
			'order_by'      => false,     // Order by.
			'group_id'      => false,     // Filter by group id.
			'meeting_id'    => false,     // Filter by meeting id.
			'since'         => false,     // Return item since date.
			'from'          => false,     // Return item from date.
			'recorded'      => false,     // Return only recorded items.
			'recurring'     => false,     // Return only recurring items.
			'hide_sitewide' => false,
			'zoom_type'     => false,
			'meta_query'    => false,     // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		),
		'meeting_get_specific'
	);

	$get_args = array(
		'in'            => $r['meeting_ids'],
		'max'           => $r['max'],
		'page'          => $r['page'],
		'per_page'      => $r['per_page'],
		'sort'          => $r['sort'],
		'live'          => $r['live'],
		'order_by'      => $r['order_by'],
		'group_id'      => $r['group_id'],
		'meeting_id'    => $r['meeting_id'],
		'since'         => $r['since'],
		'from'          => $r['from'],
		'recorded'      => $r['recorded'],
		'recurring'     => $r['recurring'],
		'meta_query'    => $r['meta_query'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'hide_sitewide' => $r['hide_sitewide'],
		'zoom_type'     => $r['zoom_type'],
	);

	/**
	 * Filters the requested specific meeting item.
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Zoom_Meeting      $meeting    Requested meeting object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_zoom_meeting_get_specific', BP_Zoom_Meeting::get( $get_args ), $args, $get_args );
}

/**
 * Add an meeting item.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an meeting ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $group_id           ID of the blog Default: current group id.
 *     @type string   $title             Optional. The title of the meeting item.

 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'                     => false,
			'group_id'               => false,
			'activity_id'            => false,
			'user_id'                => bp_loggedin_user_id(),
			'host_id'                => '',
			'title'                  => '',
			'description'            => '',
			'timezone'               => '',
			'duration'               => false,
			'meeting_authentication' => false,
			'password'               => false,
			'join_before_host'       => false,
			'waiting_room'           => false,
			'host_video'             => false,
			'participants_video'     => false,
			'mute_participants'      => false,
			'recurring'              => false,
			'hide_sitewide'          => false,
			'auto_recording'         => 'none',
			'alternative_host_ids'   => '',
			'meeting_id'             => '',
			'parent'                 => '',
			'zoom_type'              => 'meeting',
			'alert'                  => 0,
			'type'                   => 2,
			'start_date_utc'         => wp_date( 'mysql', null, new DateTimeZone( 'UTC' ) ),
			'error_type'             => 'bool',
		),
		'meeting_add'
	);

	// Setup meeting to be added.
	$meeting                         = new BP_Zoom_Meeting( $r['id'] );
	$meeting->user_id                = (int) $r['user_id'];
	$meeting->group_id               = (int) $r['group_id'];
	$meeting->activity_id            = (int) $r['activity_id'];
	$meeting->host_id                = $r['host_id'];
	$meeting->title                  = $r['title'];
	$meeting->description            = $r['description'];
	$meeting->timezone               = $r['timezone'];
	$meeting->duration               = (int) $r['duration'];
	$meeting->meeting_authentication = (bool) $r['meeting_authentication'];
	$meeting->waiting_room           = (bool) $r['waiting_room'];
	$meeting->recurring              = (bool) $r['recurring'];
	$meeting->join_before_host       = (bool) $r['join_before_host'];
	$meeting->host_video             = (bool) $r['host_video'];
	$meeting->participants_video     = (bool) $r['participants_video'];
	$meeting->mute_participants      = (bool) $r['mute_participants'];
	$meeting->auto_recording         = $r['auto_recording'];
	$meeting->password               = $r['password'];
	$meeting->hide_sitewide          = $r['hide_sitewide'];
	$meeting->alternative_host_ids   = $r['alternative_host_ids'];
	$meeting->meeting_id             = $r['meeting_id'];
	$meeting->start_date_utc         = $r['start_date_utc'];
	$meeting->parent                 = $r['parent'];
	$meeting->type                   = (int) $r['type'];
	$meeting->zoom_type              = $r['zoom_type'];
	$meeting->alert                  = $r['alert'];
	$meeting->error_type             = $r['error_type'];

	// save meeting.
	$save = $meeting->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new meeting item, before returning the new meeting item ID.
	 *
	 * @since 1.0.0
	 *
	 * @param object $meeting Meeting object.
	 * @param array $r Meeting data before save.
	 */
	do_action( 'bp_zoom_meeting_add', $meeting, $r );

	return $meeting->id;
}

/**
 * Delete meeting.
 *
 * @since 1.0.0
 *
 * @param array|string $args To delete specific meeting items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Zoom_Meeting::get().
 *                           See that method for a description.
 *
 * @return bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_delete( $args = '' ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'          => false,
			'meeting_id'  => false,
			'group_id'    => false,
			'activity_id' => false,
			'user_id'     => false,
			'parent'      => false,
		)
	);

	/**
	 * Fires before an meeting item proceeds to be deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments to be used with the meeting deletion.
	 */
	do_action( 'bp_before_zoom_meeting_delete', $args );

	$meeting_ids_deleted = BP_Zoom_Meeting::delete( $args );
	if ( empty( $meeting_ids_deleted ) ) {
		return false;
	}

	// Delete meeting meta.
	foreach ( $meeting_ids_deleted as $id ) {
		bp_zoom_meeting_delete_meta( $id );
	}

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments used with the meeting deletion.
	 */
	do_action( 'bp_zoom_meeting_delete', $args );

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meeting_ids_deleted Array of affected meeting item IDs.
	 */
	do_action( 'bp_zoom_meeting_deleted_meetings', $meeting_ids_deleted );

	return true;
}

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an meeting item.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the meeting
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            meeting item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_zoom_meeting_delete_meta( $meeting_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_zoom_meeting_get_meta( $meeting_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'meeting', $meeting_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given meeting item.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            meeting item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_zoom_meeting_get_meta( $meeting_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'meeting', $meeting_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified meeting item.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $retval      The meta values for the meeting item.
	 * @param int    $meeting_id ID of the meeting item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_zoom_meeting_get_meta', $retval, $meeting_id, $meta_key, $single );
}

/**
 * Update a piece of meeting meta.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_zoom_meeting_update_meta( $meeting_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'meeting', $meeting_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of meeting metadata.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_zoom_meeting_add_meta( $meeting_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'meeting', $meeting_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}


/**
 * Retrieve an webinar or webinars.
 *
 * The bp_zoom_webinar_get() function shares all arguments with BP_Zoom_Webinar::get().
 * The following is a list of bp_zoom_webinar_get() parameters that have different
 * default values from BP_Zoom_Webinar::get() (value in parentheses is
 * the default for the bp_zoom_webinar_get()).
 *   - 'per_page' (false)
 *
 * @since 1.0.9
 *
 * @see BP_Zoom_Webinar::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Zoom_Webinar::get() for description.
 * @return array $meeting See BP_Zoom_Webinar::get() for description.
 */
function bp_zoom_webinar_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'           => false,        // Maximum number of results to return.
			'fields'        => 'all',
			'page'          => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,        // results per page.
			'sort'          => 'DESC',       // sort ASC or DESC.
			'order_by'      => false,       // order by.
			'live'          => false,       // Live meetings.
			'exclude'       => false,       // Exclude.

			// want to limit the query.
			'group_id'      => false,
			'meeting_id'    => false,
			'activity_id'   => false,
			'user_id'       => false,
			'parent'        => false,
			'since'         => false,
			'from'          => false,
			'recorded'      => false,
			'recurring'     => false,
			'meta_query'    => false, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'search_terms'  => false,        // Pass search terms as a string.
			'count_total'   => false,
			'hide_sitewide' => false,
			'zoom_type'     => false,
		),
		'webinar_get'
	);

	$webinar = BP_Zoom_Webinar::get(
		array(
			'page'          => $r['page'],
			'per_page'      => $r['per_page'],
			'group_id'      => $r['group_id'],
			'meeting_id'    => $r['meeting_id'],
			'activity_id'   => $r['activity_id'],
			'parent'        => $r['parent'],
			'user_id'       => $r['user_id'],
			'since'         => $r['since'],
			'from'          => $r['from'],
			'max'           => $r['max'],
			'sort'          => $r['sort'],
			'live'          => $r['live'],
			'exclude'       => $r['exclude'],
			'order_by'      => $r['order_by'],
			'search_terms'  => $r['search_terms'],
			'count_total'   => $r['count_total'],
			'fields'        => $r['fields'],
			'recorded'      => $r['recorded'],
			'recurring'     => $r['recurring'],
			'meta_query'    => $r['meta_query'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'hide_sitewide' => $r['hide_sitewide'],
			'zoom_type'     => $r['zoom_type'],
		)
	);

	/**
	 * Filters the requested webinar item(s).
	 *
	 * @since 1.0.9
	 *
	 * @param BP_Zoom_Webinar  $webinar Requested webinar object.
	 * @param array     $r     Arguments used for the webinar query.
	 */
	return apply_filters_ref_array( 'bp_zoom_webinar_get', array( &$webinar, &$r ) );
}

/**
 * Fetch specific webinar items.
 *
 * @param array $args { All arguments and defaults are shared with BP_Zoom_Webinar::get(), except for the following.
 * @type string|int|array Single meeting ID, comma-separated list of IDs, or array of IDs.
 * }
 *
 * @return array $activity See BP_Zoom_Webinar::get() for description.
 * @since 1.0.9
 *
 * @see   BP_Zoom_Webinar::get() For more information on accepted arguments.
 */
function bp_zoom_webinar_get_specific( $args = array() ) {

	$r = bp_parse_args(
		$args,
		array(
			'webinar_ids'   => false,      // A single meeting_id or array of IDs.
			'max'           => false,      // Maximum number of results to return.
			'page'          => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,      // Results per page.
			'sort'          => 'DESC',     // Sort ASC or DESC.
			'live'          => false,     // Sort ASC or DESC.
			'order_by'      => false,     // Order by.
			'group_id'      => false,     // Filter by group id.
			'webinar_id'    => false,     // Filter by webinar id.
			'since'         => false,     // Return item since date.
			'from'          => false,     // Return item from date.
			'recorded'      => false,     // Return only recorded items.
			'recurring'     => false,     // Return only recurring items.
			'hide_sitewide' => false,
			'zoom_type'     => false,
			'meta_query'    => false,     // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		),
		'webinar_get_specific'
	);

	$get_args = array(
		'in'            => $r['webinar_ids'],
		'max'           => $r['max'],
		'page'          => $r['page'],
		'per_page'      => $r['per_page'],
		'sort'          => $r['sort'],
		'live'          => $r['live'],
		'order_by'      => $r['order_by'],
		'group_id'      => $r['group_id'],
		'webinar_id'    => $r['webinar_id'],
		'since'         => $r['since'],
		'from'          => $r['from'],
		'recorded'      => $r['recorded'],
		'recurring'     => $r['recurring'],
		'meta_query'    => $r['meta_query'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'hide_sitewide' => $r['hide_sitewide'],
		'zoom_type'     => $r['zoom_type'],
	);

	/**
	 * Filters the requested specific webinar item.
	 *
	 * @since 1.0.9
	 *
	 * @param BP_Zoom_Webinar      $webinar    Requested webinar object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_zoom_webinar_get_specific', BP_Zoom_Webinar::get( $get_args ), $args, $get_args );
}

/**
 * Add an webinar item.
 *
 * @since 1.0.9
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an webinar ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $group_id           ID of the blog Default: current group id.
 *     @type string   $title             Optional. The title of the webinar item.

 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the webinar on success. False on error.
 */
function bp_zoom_webinar_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'                     => false,
			'group_id'               => false,
			'activity_id'            => false,
			'user_id'                => bp_loggedin_user_id(),
			'host_id'                => '',
			'title'                  => '',
			'description'            => '',
			'start_date'             => bp_core_current_time(),
			'timezone'               => '',
			'duration'               => false,
			'meeting_authentication' => false,
			'password'               => false,
			'host_video'             => false,
			'panelists_video'        => false,
			'practice_session'       => false,
			'on_demand'              => false,
			'recurring'              => false,
			'hide_sitewide'          => false,
			'auto_recording'         => 'none',
			'alternative_host_ids'   => '',
			'webinar_id'             => '',
			'parent'                 => '',
			'zoom_type'              => 'webinar',
			'type'                   => 5,
			'start_date_utc'         => wp_date( 'mysql', null, new DateTimeZone( 'UTC' ) ),
			'alert'                  => 0,
			'error_type'             => 'bool',
		),
		'webinar_add'
	);

	// Setup webinar to be added.
	$webinar                         = new BP_Zoom_Webinar( $r['id'] );
	$webinar->user_id                = (int) $r['user_id'];
	$webinar->group_id               = (int) $r['group_id'];
	$webinar->activity_id            = (int) $r['activity_id'];
	$webinar->host_id                = $r['host_id'];
	$webinar->title                  = $r['title'];
	$webinar->description            = $r['description'];
	$webinar->start_date             = $r['start_date'];
	$webinar->timezone               = $r['timezone'];
	$webinar->duration               = (int) $r['duration'];
	$webinar->meeting_authentication = (bool) $r['meeting_authentication'];
	$webinar->recurring              = (bool) $r['recurring'];
	$webinar->host_video             = (bool) $r['host_video'];
	$webinar->panelists_video        = (bool) $r['panelists_video'];
	$webinar->practice_session       = (bool) $r['practice_session'];
	$webinar->on_demand              = (bool) $r['on_demand'];
	$webinar->auto_recording         = $r['auto_recording'];
	$webinar->password               = $r['password'];
	$webinar->hide_sitewide          = $r['hide_sitewide'];
	$webinar->alternative_host_ids   = $r['alternative_host_ids'];
	$webinar->webinar_id             = $r['webinar_id'];
	$webinar->start_date_utc         = $r['start_date_utc'];
	$webinar->parent                 = $r['parent'];
	$webinar->type                   = (int) $r['type'];
	$webinar->zoom_type              = $r['zoom_type'];
	$webinar->alert                  = (int) $r['alert'];
	$webinar->error_type             = $r['error_type'];

	// save meeting.
	$save = $webinar->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new webinar item, before returning the new webinar item ID.
	 *
	 * @since 1.0.9
	 *
	 * @param object $webinar Webinar object.
	 * @param array $r webinar data before save.
	 */
	do_action( 'bp_zoom_webinar_add', $webinar, $r );

	return $webinar->id;
}

/**
 * Delete webinar.
 *
 * @since 1.0.9
 *
 * @param array|string $args To delete specific webinar items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Zoom_Webinar::get().
 *                           See that method for a description.
 *
 * @return bool|int The ID of the webinar on success. False on error.
 */
function bp_zoom_webinar_delete( $args = '' ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'          => false,
			'webinar_id'  => false,
			'group_id'    => false,
			'activity_id' => false,
			'user_id'     => false,
			'parent'      => false,
		)
	);

	/**
	 * Fires before an webinar item proceeds to be deleted.
	 *
	 * @since 1.0.9
	 *
	 * @param array $args Array of arguments to be used with the webinar deletion.
	 */
	do_action( 'bp_before_zoom_webinar_delete', $args );

	$webinar_ids_deleted = BP_Zoom_Webinar::delete( $args );
	if ( empty( $webinar_ids_deleted ) ) {
		return false;
	}

	// Delete webinar meta.
	foreach ( $webinar_ids_deleted as $id ) {
		bp_zoom_webinar_delete_meta( $id );
	}

	/**
	 * Fires after the webinar item has been deleted.
	 *
	 * @since 1.0.9
	 *
	 * @param array $args Array of arguments used with the webinar deletion.
	 */
	do_action( 'bp_zoom_webinar_delete', $args );

	/**
	 * Fires after the webinar item has been deleted.
	 *
	 * @since 1.0.9
	 *
	 * @param array $webinar_ids_deleted Array of affected webinar item IDs.
	 */
	do_action( 'bp_zoom_webinar_deleted_webinars', $webinar_ids_deleted );

	return true;
}

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an webinar item.
 *
 * @since 1.0.9
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $webinar_id ID of the webinar item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the webinar
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            meeting item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_zoom_webinar_delete_meta( $webinar_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_zoom_webinar_get_meta( $webinar_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'webinar', $webinar_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given webinar item.
 *
 * @since 1.0.9
 *
 * @param int    $webinar_id ID of the webinar item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            webinar item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_zoom_webinar_get_meta( $webinar_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'webinar', $webinar_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified webinar item.
	 *
	 * @since 1.0.9
	 *
	 * @param mixed  $retval      The meta values for the meeting item.
	 * @param int    $webinar_id ID of the webinar item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_zoom_webinar_get_meta', $retval, $webinar_id, $meta_key, $single );
}

/**
 * Update a piece of meeting meta.
 *
 * @since 1.0.9
 *
 * @param int    $webinar_id ID of the webinar item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_zoom_webinar_update_meta( $webinar_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'webinar', $webinar_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of webinar metadata.
 *
 * @since 1.0.9
 *
 * @param int    $webinar_id ID of the webinar item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_zoom_webinar_add_meta( $webinar_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'webinar', $webinar_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update recording data for the meeting.
 *
 * @param int         $meeting_id Meeting ID.
 * @param object|bool $meeting Meeting Object.
 *
 * @return bool
 * @since 1.0.0
 */
function bp_zoom_meeting_update_recordings_data( $meeting_id, $meeting = false ) {

	if ( empty( $meeting_id ) ) {
		return false;
	}

	if ( empty( $meeting ) ) {
		$meeting = new BP_Zoom_Meeting( $meeting_id );
	}

	if ( isset( $meeting->is_past ) && ! $meeting->is_past ) {
		return false;
	}

	// check count first.
	$recording_count = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_count', true );

	if ( ! empty( $recording_count ) ) {
		return $recording_count;
	}

	// check if checked first.
	$recording_checked = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_checked', true );

	if ( '1' === $recording_checked ) {
		return false;
	}

	if ( ! empty( $meeting->group_id ) ) {
		$api_key    = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-key', true );
		$api_secret = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-secret', true );

		bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
		bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';
	}

	$recordings = bp_zoom_conference()->recordings_by_meeting( $meeting->meeting_id );

	if ( ! empty( $recordings['response'] ) ) {
		$recordings = $recordings['response'];

		if ( ! empty( $recordings->recording_count ) && $recordings->recording_count > 0 ) {
			bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_count', $recordings->recording_count );
		}

		if ( ! empty( $recordings->recording_files ) ) {
			bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_files', $recordings->recording_files );
		}

		bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_checked', '1' );
	}
}

/**
 * Integration > Zoom Conference > Enable.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_enable_field() {
	?>
	<input name="bp-zoom-enable" id="bp-zoom-enable" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_enabled() ); ?>/>
	<label for="bp-zoom-enable">
		<?php esc_html_e( 'Allow Zoom meetings on this site', 'buddyboss-pro' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom is enabled.
 *
 * @since 1.0.0
 *
 * @param int $default Default option for zoom enable or not.
 *
 * @return bool Is zoom enabled or not.
 */
function bp_zoom_is_zoom_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_enabled', (bool) bp_get_option( 'bp-zoom-enable', $default ) );
}

/**
 * Get if Zoom is setup or not?
 *
 * @since 1.0.0
 * @return bool Is Zoom setup?
 */
function bp_zoom_is_zoom_setup() {
	$email  = bp_zoom_api_email();
	$key    = bp_zoom_api_key();
	$secret = bp_zoom_api_secret();
	$host   = bp_zoom_api_host();
	if ( ! bp_zoom_is_zoom_enabled() || empty( $host ) || empty( $email ) || empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Callback function for api key in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_key_field() {
	?>
	<input name="bp-zoom-api-key" id="bp-zoom-api-key" type="text" value="<?php echo esc_html( bp_zoom_api_key() ); ?>" placeholder="<?php esc_html_e( 'Zoom API Key', 'buddyboss-pro' ); ?>" aria-label="<?php esc_html_e( 'Zoom API Key', 'buddyboss-pro' ); ?>"/>
	<?php /* translators: %s is the buddyboss marketplace link. */ ?>
	<p class="description"><?php printf( esc_html__( 'To find your Zoom API Key, you first need to create a JWT app in the %s.', 'buddyboss-pro' ), '<a href="https://marketplace.zoom.us/" target="_blank">' . esc_html__( 'Zoom Marketplace', 'buddyboss-pro' ) . '</a>' ); ?></p>
	<?php
}

/**
 * Get Zoom API Key
 *
 * @since 1.0.0
 * @param string $default Default option for zoom api key.
 *
 * @return mixed|void Zoom API Key.
 */
function bp_zoom_api_key( $default = '' ) {
	return apply_filters( 'bp_zoom_api_key', bp_get_option( 'bp-zoom-api-key', $default ) );
}

/**
 * Callback function for api secret in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_secret_field() {
	?>
	<input name="bp-zoom-api-secret" id="bp-zoom-api-secret" type="text" value="<?php echo esc_html( bp_zoom_api_secret() ); ?>" placeholder="<?php esc_html_e( 'Zoom API Secret', 'buddyboss-pro' ); ?>" aria-label="<?php esc_html_e( 'Zoom API Secret', 'buddyboss-pro' ); ?>"/>
	<?php /* translators: %s is the buddyboss marketplace link. */ ?>
	<p class="description"><?php printf( esc_html__( 'To find your Zoom API key, you first need to create a JWT app in the %s.', 'buddyboss-pro' ), '<a href="https://marketplace.zoom.us/" target="_blank">' . esc_html__( 'Zoom Marketplace', 'buddyboss-pro' ) . '</a>' ); ?></p>
	<?php
}

/**
 * Get Zoom API Secret
 *
 * @since 1.0.0
 * @param string $default Default option for zoom api secret.
 *
 * @return mixed|void Zoom API secret.
 */
function bp_zoom_api_secret( $default = '' ) {
	return apply_filters( 'bp_zoom_api_secret', bp_get_option( 'bp-zoom-api-secret', $default ) );
}

/**
 * Callback function for api email in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_email_field() {
	?>
	<input name="bp-zoom-api-email" id="bp-zoom-api-email" type="email" value="<?php echo esc_html( bp_zoom_api_email() ); ?>" placeholder="<?php esc_attr_e( 'Zoom Account Email', 'buddyboss-pro' ); ?>" aria-label="<?php esc_attr_e( 'Zoom Account Email', 'buddyboss-pro' ); ?>"/>
	<p class="description"><?php esc_html_e( 'Enter an email from your Zoom account to be used as the default host in Gutenberg blocks.', 'buddyboss-pro' ); ?></p>
	<?php
}

/**
 * Get Zoom Account Email
 *
 * @since 1.0.0
 * @param string $default Default option for zoom api email.
 *
 * @return mixed|void Zoom Account Email.
 */
function bp_zoom_api_email( $default = '' ) {
	return apply_filters( 'bp_zoom_api_email', bp_get_option( 'bp-zoom-api-email', $default ) );
}

/**
 * Callback function for api host in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_host_field() {
	?>
	<input name="bp-zoom-api-host" id="bp-zoom-api-host" type="hidden" value="<?php echo esc_attr( bp_zoom_api_host() ); ?>"/>
	<?php
}

/**
 * Integration > Zoom Conference > Enable Groups
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_groups_enable_field() {
	?>
	<input name="bp-zoom-enable-groups" id="bp-zoom-enable-groups" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_groups_enabled() ); ?>/>
	<label for="bp-zoom-enable-groups">
		<?php esc_html_e( 'Allow Zoom meetings in social groups', 'buddyboss-pro' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom is enabled in groups.
 *
 * @since 1.0.0
 *
 * @param int $default Default option for group zoom enabled or not.
 *
 * @return bool Is zoom enabled in groups or not.
 */
function bp_zoom_is_zoom_groups_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_groups_enabled', (bool) bp_get_option( 'bp-zoom-enable-groups', $default ) );
}

/**
 * Integration > Zoom Conference > Enable Zoom Hide URLs
 *
 * @since 1.0.8
 */
function bp_zoom_settings_callback_hide_zoom_urls_field() {
	?>
	<input name="bp-zoom-hide-zoom-urls" id="bp-zoom-hide-zoom-urls" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_hide_urls_enabled() ); ?> />
	<label for="bp-zoom-hide-zoom-urls">
		<?php esc_html_e( 'Force all Zoom Meeting URLs to be private to the site, with all meetings taking place in the browser', 'buddyboss-pro' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom hide urls is enabled.
 *
 * @since 1.0.8
 *
 * @param int $default Default option for hide urls.
 *
 * @return bool Is zoom hide urls enabled or not
 */
function bp_zoom_is_zoom_hide_urls_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_hide_urls_enabled', (bool) bp_get_option( 'bp-zoom-hide-zoom-urls', $default ) );
}

/**
 * Integration > Zoom Conference > Enable Zoom Hide Webinar URLs
 *
 * @since 1.0.9
 */
function bp_zoom_settings_callback_hide_zoom_webinar_urls_field() {
	?>
	<input name="bp-zoom-hide-zoom-webinar-urls" id="bp-zoom-hide-zoom-webinar-urls" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_hide_webinar_urls_enabled() ); ?> />
	<label for="bp-zoom-hide-zoom-webinar-urls">
		<?php esc_html_e( 'Force all Zoom Webinar URLs to be private to the site, with all webinars taking place in the browser', 'buddyboss-pro' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'The webinar host will still be sent out to Zoom, while attendees will join from the browser. Registering for webinars, Only authenticated user can join, and webinars polls will not work while this option is enabled, as Zoom does not yet support these features in the browser.', 'buddyboss-pro' ); ?></p>
	<?php
}

/**
 * Checks if zoom hide webinar urls is enabled.
 *
 * @since 1.0.9
 *
 * @param int $default Default option for hide webinar urls.
 *
 * @return bool Is zoom hide webinar urls enabled or not
 */
function bp_zoom_is_zoom_hide_webinar_urls_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_hide_webinar_urls_enabled', (bool) bp_get_option( 'bp-zoom-hide-zoom-webinar-urls', $default ) );
}

/**
 * Integration > Zoom Conference > Enable Recordings
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_recordings_enable_field() {
	?>
	<input name="bp-zoom-enable-recordings" id="bp-zoom-enable-recordings" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_recordings_enabled() ); ?>/>
	<label for="bp-zoom-enable-recordings">
		<?php esc_html_e( 'Display Zoom recordings for past meetings', 'buddyboss-pro' ); ?>
	</label>
	<br/>
	<input name="bp-zoom-enable-recordings-links" id="bp-zoom-enable-recordings-links" type="checkbox" value="1"
		<?php echo ! bp_zoom_is_zoom_recordings_enabled() ? 'disabled="disabled"' : ''; ?>
		<?php checked( bp_zoom_is_zoom_recordings_links_enabled() ); ?>
	/>
	<label for="bp-zoom-enable-recordings-links">
		<?php esc_html_e( "Display buttons to 'Download' recording, and to 'Copy Link' to the recording", 'buddyboss-pro' ); ?>
	</label>
	<script type="application/javascript">
		jQuery(document).ready(function(){
			jQuery( '#bp-zoom-enable-recordings' ).change(
				function () {
					if ( ! this.checked) {
						jQuery( '#bp-zoom-enable-recordings-links' ).prop( 'disabled', true );
						jQuery( '#bp-zoom-enable-recordings-links' ).attr( 'checked', false );
					} else {
						jQuery( '#bp-zoom-enable-recordings-links' ).prop( 'disabled', false );
					}
				}
			);
		});
	</script>
	<?php
}

/**
 * Checks if zoom recordings are enabled.
 *
 * @since 1.0.0
 *
 * @param integer $default recordings enabled by default.
 *
 * @return bool Is zoom recordings enabled or not.
 */
function bp_zoom_is_zoom_recordings_enabled( $default = 1 ) {

	/**
	 * Filters zoom recordings enabled settings.
	 *
	 * @param bool $recording_enabled settings if recordings enabled or no.
	 *
	 * @since 1.0.0
	 */
	return (bool) apply_filters( 'bp_zoom_is_zoom_recordings_enabled', (bool) bp_get_option( 'bp-zoom-enable-recordings', $default ) );
}

/**
 * Checks if zoom recordings links are enabled.
 *
 * @since 1.0.2
 *
 * @param integer $default recordings links enabled by default.
 *
 * @return bool Is zoom recordings links enabled or not.
 */
function bp_zoom_is_zoom_recordings_links_enabled( $default = 1 ) {

	/**
	 * Filters zoom recordings links enabled settings.
	 *
	 * @param bool $recording_enabled settings if recording links enabled or no.
	 *
	 * @since 1.0.2
	 */
	return (bool) apply_filters( 'bp_zoom_is_zoom_recordings_links_enabled', (bool) bp_get_option( 'bp-zoom-enable-recordings-links', $default ) );
}

/**
 * Get Zoom API Host
 *
 * @since 1.0.0
 * @param string $default default zoom api host.
 *
 * @return mixed|void Zoom API Host
 */
function bp_zoom_api_host( $default = '' ) {
	return apply_filters( 'bp_zoom_api_host', bp_get_option( 'bp-zoom-api-host', $default ) );
}

/**
 * Get Zoom API Host User
 *
 * @since 1.0.0
 * @param string $default default zoom api host user.
 *
 * @return mixed|void Zoom API Host User
 */
function bp_zoom_api_host_user( $default = '' ) {
	return apply_filters( 'bp_zoom_api_host_user', json_decode( bp_get_option( 'bp-zoom-api-host-user', $default ) ) );
}

/**
 * Get default group host's display data.
 *
 * @return string
 * @since 1.0.0
 */
function bp_zoom_api_host_show() {
	if ( ! bp_zoom_is_zoom_setup() ) {
		return '';
	}
	$api_host_user = bp_zoom_api_host_user();

	if ( ! empty( $api_host_user ) ) {

		$return = '';
		if ( ! empty( $api_host_user->first_name ) ) {
			$return .= $api_host_user->first_name;
		}
		if ( ! empty( $api_host_user->last_name ) ) {
			$return .= ' ' . $api_host_user->last_name;
		}

		if ( empty( $return ) && ! empty( $api_host_user->email ) ) {
			$return = $api_host_user->email;
		}

		return $return;
	}

	return '';
}

/**
 * Check connection to zoom conference button.
 *
 * @since 1.0.0
 */
function bp_zoom_api_check_connection_button() {
	?>
	<p>
		<?php if ( version_compare( BP_PLATFORM_VERSION, '1.5.7.3', '<=' ) ) { ?>
			<a class="button" href="
				<?php
				echo esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => 88334,
							),
							'admin.php'
						)
					)
				);
				?>
				"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
		<?php } ?>
		<a class="button-primary" href="#" id="bp-zoom-check-connection"><?php esc_html_e( 'Check Connection', 'buddyboss-pro' ); ?></a>
	</p>
	<?php
}

/**
 * Zoom settings tutorial.
 *
 * @since 1.0.0
 */
function bp_zoom_api_zoom_settings_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 88334,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
	</p>
	<?php
}

/**
 * Link to Zoom Settings tutorial
 *
 * @since 1.0.0
 */
function bp_zoom_settings_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 88334,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
	</p>
	<?php
}

/**
 * Callback function for webinar module in zoom integration.
 *
 * @since 1.0.9
 */
function bp_zoom_settings_callback_webinar_enable_field() {
	?>
	<input name="bp-zoom-enable-webinar" id="bp-zoom-enable-webinar" type="checkbox" value="1" <?php checked( bp_zoom_is_zoom_webinar_enabled() ); ?>/>
	<label for="bp-zoom-enable-webinar">
		<?php esc_html_e( 'Allow Zoom Webinars in the blocks', 'buddyboss-pro' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom webinar are enabled.
 *
 * @since 1.0.9
 *
 * @param integer $default webinar enabled by default.
 *
 * @return bool Is zoom webinar enabled or not.
 */
function bp_zoom_is_zoom_webinar_enabled( $default = 0 ) {

	/**
	 * Filters zoom webinar enabled settings.
	 *
	 * @param bool $webinar_enabled settings if webinar enabled or no.
	 *
	 * @since 1.0.9
	 */
	return (bool) apply_filters( 'bp_zoom_is_zoom_webinar_enabled', (bool) bp_get_option( 'bp-zoom-enable-webinar', $default ) );
}

/**
 * Group zoom meeting slug for sub nav items.
 *
 * @since 1.0.0
 * @param string $slug Nouveau group secondary nav parent slug.
 *
 * @return string slug of nav
 */
function bp_zoom_nouveau_group_secondary_nav_parent_slug( $slug ) {
	if ( ! bp_is_group() ) {
		return $slug;
	}
	return bp_get_current_group_slug() . '_zoom';
}

/**
 * Selected and current class for current nav item in group zoom tabs.
 *
 * @since 1.0.0
 * @param string $classes_str Classes string comma separated.
 * @param string $classes Array of classes.
 * @param string $nav_item Nav item being worked on.
 *
 * @return string classes for the nav items
 */
function bp_zoom_nouveau_group_secondary_nav_selected_classes( $classes_str, $classes, $nav_item ) {
	global $bp_zoom_current_meeting, $bp_zoom_current_webinar;
	if ( bp_is_current_action( 'zoom' ) ) {

		if ( ! empty( $bp_zoom_current_meeting ) ) {
			if ( true === $bp_zoom_current_meeting->is_past && false === $bp_zoom_current_meeting->is_live ) {
				if ( 'past-meetings' === $nav_item->slug ) {
					$classes = array_merge( $classes, array( 'current', 'selected' ) );
				}
			} elseif ( 'meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		} elseif ( ! empty( $bp_zoom_current_webinar ) ) {
			if ( true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) {
				if ( 'past-webinars' === $nav_item->slug ) {
					$classes = array_merge( $classes, array( 'current', 'selected' ) );
				}
			} elseif ( 'webinars' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		} else {
			if ( bp_zoom_is_meetings() && 'meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( bp_zoom_is_create_meeting() && 'create-meeting' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( bp_zoom_is_past_meetings() && 'past-meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( bp_zoom_is_webinars() && 'webinars' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( bp_zoom_is_create_webinar() && 'create-webinar' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( bp_zoom_is_past_webinars() && 'past-webinars' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		}

		if ( 'create-meeting' === $nav_item->slug || 'create-webinar' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'bp-hide' ) );
		}

		if ( bp_zoom_is_create_meeting() && 'meetings' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		} elseif ( bp_zoom_is_create_webinar() && 'webinars' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		}

		if ( bp_zoom_is_create_meeting() && in_array( $nav_item->slug, array( 'webinars', 'past-webinars', 'create-webinar' ), true ) ) {
			$classes = array_merge( $classes, array( 'bp-hide' ) );
		}

		if ( bp_zoom_is_create_webinar() && in_array( $nav_item->slug, array( 'meetings', 'past-meetings', 'create-meeting' ), true ) ) {
			$classes = array_merge( $classes, array( 'bp-hide' ) );
		}

		if ( ( ( bp_zoom_is_meetings() || bp_zoom_is_past_meetings() ) && ( 'webinars' === $nav_item->slug || 'past-webinars' === $nav_item->slug ) ) || ( ( bp_zoom_is_webinars() || bp_zoom_is_past_webinars() ) && ( 'meetings' === $nav_item->slug || 'past-meetings' === $nav_item->slug ) ) ) {
			$classes = array_merge( $classes, array( 'bp-hide' ) );
		}

		if ( bp_zoom_is_groups_zoom() && ! bp_zoom_is_meetings() && ! bp_zoom_is_past_meetings() && ! bp_zoom_is_webinars() && ! bp_zoom_is_past_webinars() && ! bp_zoom_is_create_meeting() && ! bp_zoom_is_create_webinar() ) {
			if ( 'webinars' === $nav_item->slug || 'create-webinar' === $nav_item->slug || 'past-webinars' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'bp-hide' ) );
			}

			if ( 'meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		}

		$classes = array_merge( $classes, array( $nav_item->slug ) );

		return join( ' ', $classes );
	}
	return $classes_str;
}

/**
 * Check if current request is groups zoom or not.
 *
 * @since 1.0.0
 * @return bool $is_zoom return true if group zoom page otherwise false
 */
function bp_zoom_is_groups_zoom() {
	$is_zoom = false;
	if ( bp_is_groups_component() && bp_is_group() && bp_is_current_action( 'zoom' ) ) {
		$is_zoom = true;
	}

	/**
	 * Filters the current group zoom page or not.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_zoom Current page is groups zoom page or not.
	 */
	return apply_filters( 'bp_zoom_is_groups_zoom', $is_zoom );
}

/**
 * Get timezones
 *
 * @since 1.0.0
 */
function bp_zoom_get_timezone_options() {
	$zones = array(
		'Pacific/Midway'                 => '(GMT-11:00) Midway Island, Samoa',
		'Pacific/Pago_Pago'              => '(GMT-11:00) Pago Pago',
		'Pacific/Honolulu'               => '(GMT-10:00) Hawaii',
		'America/Anchorage'              => '(GMT-9:00) Alaska',
		'America/Juneau'                 => '(GMT-9:00) Juneau',
		'America/Vancouver'              => '(GMT-8:00) Vancouver',
		'America/Los_Angeles'            => '(GMT-8:00) Pacific Time (US and Canada)',
		'America/Tijuana'                => '(GMT-8:00) Tijuana',
		'America/Edmonton'               => '(GMT-7:00) Edmonton',
		'America/Denver'                 => '(GMT-7:00) Mountain Time (US and Canada)',
		'America/Phoenix'                => '(GMT-7:00) Arizona',
		'America/Mazatlan'               => '(GMT-7:00) Mazatlan',
		'Canada/Yukon'                   => '(GMT-7:00) Yukon',
		'America/Winnipeg'               => '(GMT-6:00) Winnipeg',
		'America/Regina'                 => '(GMT-6:00) Saskatchewan',
		'America/Chicago'                => '(GMT-6:00) Central Time (US and Canada)',
		'America/Mexico_City'            => '(GMT-6:00) Mexico City',
		'America/Guatemala'              => '(GMT-6:00) Guatemala',
		'America/El_Salvador'            => '(GMT-6:00) El Salvador',
		'America/Managua'                => '(GMT-6:00) Managua',
		'America/Costa_Rica'             => '(GMT-6:00) Costa Rica',
		'America/Tegucigalpa'            => '(GMT-6:00) Tegucigalpa',
		'America/Chihuahua'              => '(GMT-6:00) Chihuahua',
		'America/Monterrey'              => '(GMT-6:00) Monterrey',
		'America/Montreal'               => '(GMT-5:00) Montreal',
		'America/New_York'               => '(GMT-5:00) Eastern Time (US and Canada)',
		'America/Indianapolis'           => '(GMT-5:00) Indiana (East)',
		'America/Panama'                 => '(GMT-5:00) Panama',
		'America/Bogota'                 => '(GMT-5:00) Bogota',
		'America/Lima'                   => '(GMT-5:00) Lima',
		'Brazil/Acre'                    => '(GMT-5:00) Acre',
		'America/Halifax'                => '(GMT-4:00) Halifax',
		'America/Puerto_Rico'            => '(GMT-4:00) Puerto Rico',
		'America/Caracas'                => '(GMT-4:00) Caracas',
		'Canada/Atlantic'                => '(GMT-4:00) Atlantic Time (Canada)',
		'America/La_Paz'                 => '(GMT-4:00) La Paz',
		'America/Guyana'                 => '(GMT-4:00) Guyana',
		'America/St_Johns'               => '(GMT-3:30) Newfoundland and Labrador',
		'America/Santiago'               => '(GMT-3:00) Santiago',
		'America/Montevideo'             => '(GMT-3:00) Montevideo',
		'America/Recife'                 => '(GMT-3:00) Recife',
		'America/Argentina/Buenos_Aires' => '(GMT-3:00) Buenos Aires, Georgetown',
		'America/Godthab'                => '(GMT-3:00) Greenland',
		'America/Sao_Paulo'              => '(GMT-3:00) Sao Paulo',
		'America/Noronha'                => '(GMT-2:00) Fernando de Noronha',
		'Atlantic/Azores'                => '(GMT-1:00) Azores',
		'Atlantic/Cape_Verde'            => '(GMT-1:00) Cape Verde Islands',
		'UTC'                            => '(GMT+0:00) Universal Time UTC',
		'Etc/Greenwich'                  => '(GMT+0:00) Greenwich Mean Time',
		'Atlantic/Reykjavik'             => '(GMT+0:00) Reykjavik',
		'Europe/Dublin'                  => '(GMT+1:00) Dublin',
		'Europe/London'                  => '(GMT+0:00) London',
		'Europe/Lisbon'                  => '(GMT+0:00) Lisbon',
		'Africa/Nouakchott'              => '(GMT+0:00) Nouakchott',
		'Europe/Belgrade'                => '(GMT+1:00) Belgrade, Bratislava, Ljubljana',
		'CET'                            => '(GMT+1:00) Sarajevo, Skopje, Zagreb',
		'Africa/Casablanca'              => '(GMT+1:00) Casablanca',
		'Europe/Oslo'                    => '(GMT+1:00) Oslo',
		'Europe/Copenhagen'              => '(GMT+1:00) Copenhagen',
		'Europe/Brussels'                => '(GMT+1:00) Brussels',
		'Europe/Berlin'                  => '(GMT+1:00) Amsterdam, Berlin, Rome, Stockholm, Vienna',
		'Europe/Amsterdam'               => '(GMT+1:00) Amsterdam',
		'Europe/Rome'                    => '(GMT+1:00) Rome',
		'Europe/Stockholm'               => '(GMT+1:00) Stockholm',
		'Europe/Vienna'                  => '(GMT+1:00) Vienna',
		'Europe/Luxembourg'              => '(GMT+1:00) Luxembourg',
		'Europe/Paris'                   => '(GMT+1:00) Paris',
		'Europe/Zurich'                  => '(GMT+1:00) Zurich',
		'Europe/Madrid'                  => '(GMT+1:00) Madrid',
		'Africa/Bangui'                  => '(GMT+1:00) West Central Africa',
		'Africa/Algiers'                 => '(GMT+1:00) Algiers',
		'Africa/Tunis'                   => '(GMT+1:00) Tunis',
		'Europe/Warsaw'                  => '(GMT+1:00) Warsaw',
		'Europe/Prague'                  => '(GMT+1:00) Prague Bratislava',
		'Europe/Budapest'                => '(GMT+1:00) Budapest',
		'Europe/Helsinki'                => '(GMT+2:00) Helsinki',
		'Africa/Harare'                  => '(GMT+2:00) Harare, Pretoria',
		'Europe/Sofia'                   => '(GMT+2:00) Sofia',
		'Europe/Athens'                  => '(GMT+2:00) Athens',
		'Europe/Bucharest'               => '(GMT+2:00) Bucharest',
		'Asia/Nicosia'                   => '(GMT+2:00) Nicosia',
		'Asia/Beirut'                    => '(GMT+2:00) Beirut',
		'Asia/Jerusalem'                 => '(GMT+2:00) Jerusalem',
		'Africa/Tripoli'                 => '(GMT+2:00) Tripoli',
		'Africa/Cairo'                   => '(GMT+2:00) Cairo',
		'Africa/Johannesburg'            => '(GMT+2:00) Johannesburg',
		'Africa/Khartoum'                => '(GMT+2:00) Khartoum',
		'Europe/Kiev'                    => '(GMT+2:00) Kyiv',
		'Europe/Chisinau'                => '(GMT+2:00) Chisinau',
		'Africa/Nairobi'                 => '(GMT+3:00) Nairobi',
		'Europe/Istanbul'                => '(GMT+3:00) Istanbul',
		'Asia/Damascus'                  => '(GMT+3:00) Damascus',
		'Asia/Amman'                     => '(GMT+3:00) Amman',
		'Europe/Moscow'                  => '(GMT+3:00) Moscow',
		'Asia/Baghdad'                   => '(GMT+3:00) Baghdad',
		'Asia/Kuwait'                    => '(GMT+3:00) Kuwait',
		'Asia/Riyadh'                    => '(GMT+3:00) Riyadh',
		'Asia/Bahrain'                   => '(GMT+3:00) Bahrain',
		'Asia/Qatar'                     => '(GMT+3:00) Qatar',
		'Asia/Aden'                      => '(GMT+3:00) Aden',
		'Africa/Djibouti'                => '(GMT+3:00) Djibouti',
		'Africa/Mogadishu'               => '(GMT+3:00) Mogadishu',
		'Europe/Minsk'                   => '(GMT+3:00) Minsk',
		'Asia/Tehran'                    => '(GMT+3:30) Tehran',
		'Asia/Dubai'                     => '(GMT+4:00) Dubai',
		'Asia/Muscat'                    => '(GMT+4:00) Muscat',
		'Asia/Baku'                      => '(GMT+4:00) Baku, Tbilisi, Yerevan',
		'Asia/Kabul'                     => '(GMT+4:30) Kabul',
		'Asia/Yekaterinburg'             => '(GMT+5:00) Yekaterinburg',
		'Asia/Tashkent'                  => '(GMT+5:00) Islamabad, Karachi, Tashkent',
		'IST'                            => '(GMT+5:30) India',
		'Asia/Kolkata'                   => '(GMT+5:30) Mumbai, Kolkata, New Delhi',
		'Asia/Colombo'                   => '(GMT+5:30) Colombo',
		'Asia/Kathmandu'                 => '(GMT+5:45) Kathmandu',
		'Asia/Almaty'                    => '(GMT+6:00) Almaty',
		'Asia/Dacca'                     => '(GMT+6:00) Dacca',
		'Asia/Dhaka'                     => '(GMT+6:00) Astana, Dhaka',
		'Asia/Rangoon'                   => '(GMT+6:30) Rangoon',
		'Asia/Novosibirsk'               => '(GMT+7:00) Novosibirsk',
		'Asia/Krasnoyarsk'               => '(GMT+7:00) Krasnoyarsk',
		'Asia/Bangkok'                   => '(GMT+7:00) Bangkok',
		'Asia/Saigon'                    => '(GMT+7:00) Vietnam',
		'Asia/Jakarta'                   => '(GMT+7:00) Jakarta',
		'Asia/Irkutsk'                   => '(GMT+8:00) Irkutsk, Ulaanbaatar',
		'Asia/Shanghai'                  => '(GMT+8:00) Beijing, Shanghai',
		'Asia/Hong_Kong'                 => '(GMT+8:00) Hong Kong SAR',
		'Asia/Taipei'                    => '(GMT+8:00) Taipei',
		'Asia/Kuala_Lumpur'              => '(GMT+8:00) Kuala Lumpur',
		'Asia/Singapore'                 => '(GMT+8:00) Singapore',
		'Australia/Perth'                => '(GMT+8:00) Perth',
		'Asia/Yakutsk'                   => '(GMT+9:00) Yakutsk',
		'Asia/Seoul'                     => '(GMT+9:00) Seoul',
		'Asia/Tokyo'                     => '(GMT+9:00) Osaka, Sapporo, Tokyo',
		'Australia/Darwin'               => '(GMT+9:30) Darwin',
		'Asia/Vladivostok'               => '(GMT+10:00) Vladivostok',
		'Pacific/Port_Moresby'           => '(GMT+10:00) Guam, Port Moresby',
		'Australia/Brisbane'             => '(GMT+10:00) Brisbane',
		'Australia/Adelaide'             => '(GMT+10:30) Adelaide',
		'Australia/Sydney'               => '(GMT+11:00) Canberra, Melbourne, Sydney',
		'Australia/Hobart'               => '(GMT+11:00) Hobart',
		'Asia/Magadan'                   => '(GMT+10:00) Magadan',
		'SST'                            => '(GMT+11:00) Solomon Islands',
		'Pacific/Noumea'                 => '(GMT+11:00) New Caledonia',
		'Australia/Lord_Howe'            => '(GMT+11:00) Lord Howe IsIand',
		'Asia/Kamchatka'                 => '(GMT+12:00) Kamchatka',
		'Pacific/Fiji'                   => '(GMT+12:00) Fiji Islands, Marshall Islands',
		'Pacific/Auckland'               => '(GMT+13:00) Auckland, Wellington',
		'Pacific/Apia'                   => '(GMT+13:00) Independent State of Samoa',
	);

	return apply_filters( 'bp_zoom_get_timezone_options', $zones );
}

/**
 * Get timezone label.
 *
 * @param string $timezone Timezone.
 *
 * @since 1.0.0
 * @return string Timezone.
 */
function bp_zoom_get_timezone_label( $timezone = '' ) {
	$timezones          = bp_zoom_get_timezone_options();
	$selected_time_zone = $timezone;
	if ( empty( $timezone ) ) {
		$wp_timezone_str = get_option( 'timezone_string' );
		if ( empty( $wp_timezone_str ) ) {
			$wp_timezone_str_offset = get_option( 'gmt_offset' );
		} else {
			$time                   = new DateTime( 'now', new DateTimeZone( $wp_timezone_str ) );
			$wp_timezone_str_offset = $time->getOffset() / 60 / 60;
		}

		if ( ! empty( $timezones ) ) {
			foreach ( $timezones as $key => $time_zone ) {
				if ( $key === $wp_timezone_str ) {
					$selected_time_zone = $key;
					break;
				}

				$date            = new DateTime( 'now', new DateTimeZone( $key ) );
				$offset_in_hours = $date->getOffset() / 60 / 60;

				if ( (float) $wp_timezone_str_offset === (float) $offset_in_hours ) {
					$selected_time_zone = $key;
				}
			}
		}
	}

	if ( empty( $selected_time_zone ) ) {
		return '';
	}

	$timezone_label = substr( $timezones[ $selected_time_zone ], strpos( $timezones[ $selected_time_zone ], ' ' ), strlen( $timezones[ $selected_time_zone ] ) );
	return ltrim( $timezone_label );
}

/**
 * Filter for adding meeting/webinar loop none case.
 *
 * @since 1.0.0
 * @param array $messages Array of feedback messages.
 *
 * @return mixed
 */
function bp_zoom_nouveau_feedback_messages( $messages ) {
	$messages['meetings-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'Sorry, no meetings were found.', 'buddyboss-pro' ),
	);

	$messages['webinars-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'Sorry, no webinars were found.', 'buddyboss-pro' ),
	);

	return $messages;
}
add_filter( 'bp_nouveau_feedback_messages', 'bp_zoom_nouveau_feedback_messages' );

/**
 * Get if group has zoom enabled or not.
 *
 * @since 1.0.0
 * @param int $group_id group ID.
 *
 * @return bool True if all details required are not empty otherwise false.
 */
function bp_zoom_group_is_zoom_enabled( $group_id ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}
	return groups_get_groupmeta( $group_id, 'bp-group-zoom', true );
}

/**
 * Check group zoom is setup or not.
 *
 * @since 1.0.0
 * @param int $group_id Group ID.
 *
 * @return bool Returns true if zoom is setup.
 */
function bp_zoom_is_group_setup( $group_id ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}

	$group_zoom = groups_get_groupmeta( $group_id, 'bp-group-zoom', true );
	$api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
	$api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
	$api_email  = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
	$api_host   = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host', true );

	if ( ! $group_zoom || empty( $api_key ) || empty( $api_secret ) || empty( $api_email ) || empty( $api_host ) ) {
		return false;
	}

	return true;
}

/**
 * Get default group api key.
 *
 * @param int $group_id Group ID.
 * @since 1.1.4
 * @return string API key string.
 */
function bb_zoom_group_api_key( $group_id ) {
	if ( empty( $group_id ) || ! bp_zoom_is_group_setup( $group_id ) ) {
		return '';
	}
	$api_key = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );

	return $api_key;
}

/**
 * Get default group api secret.
 *
 * @param int $group_id Group ID.
 * @since 1.1.4
 * @return string API secret string.
 */
function bb_zoom_group_api_secret( $group_id ) {
	if ( empty( $group_id ) || ! bp_zoom_is_group_setup( $group_id ) ) {
		return '';
	}
	$api_key = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );

	return $api_key;
}

/**
 * Get default group api signature.
 *
 * @param string $api_key Key.
 * @param string $api_secret Secret.
 * @param int    $meeting_number Meeting ID.
 * @param int    $role Role ID.
 * @since 1.1.4
 * @return string API signature string.
 */
function bb_get_meeting_signature( $api_key, $api_secret, $meeting_number, $role ) {

	$time = time() * 1000 - 30000; // time in milliseconds (or close enough).

	$data = base64_encode( $api_key . $meeting_number . $time . $role ); // phpcs:ignore

	$hash = hash_hmac( 'sha256', $data, $api_secret, true );

    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	$_sig = $api_key . '.' . $meeting_number . '.' . $time . '.' . $role . '.' . base64_encode( $hash );

	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	return rtrim( strtr( base64_encode( $_sig ), '+/', '-_' ), '=' );
}

/**
 * Get default group host's display data.
 *
 * @param int $group_id Group ID.
 * @since 1.0.0
 * @return string API Host display string.
 */
function bp_zoom_groups_api_host_show( $group_id ) {
	if ( empty( $group_id ) || ! bp_zoom_is_group_setup( $group_id ) ) {
		return '';
	}
	$api_host_user = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-user', true );

	if ( ! empty( $api_host_user ) ) {
		$api_host_user = json_decode( $api_host_user );

		$return = '';
		if ( ! empty( $api_host_user->first_name ) ) {
			$return .= $api_host_user->first_name;
		}
		if ( ! empty( $api_host_user->last_name ) ) {
			$return .= ' ' . $api_host_user->last_name;
		}

		if ( empty( $return ) && ! empty( $return->email ) ) {
			$return = $return->email;
		}
		return $return;
	}
	return '';
}

/**
 * Output the 'checked' value, if needed, for a given status on the group admin screen
 *
 * @since 1.0.0
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function bp_zoom_group_show_manager_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$status = bp_zoom_group_get_manager( $group_id );

	if ( $setting === $status ) {
		echo ' checked="checked"';
	}
}

/**
 * Get the zoom manager of a group.
 *
 * This function can be used either in or out of the loop.
 *
 * @since 1.0.0
 *
 * @param int|bool $group_id Optional. The ID of the group whose status you want to
 *                           check. Default: the displayed group, or the current group
 *                           in the loop.
 * @return bool|string Returns false when no group can be found. Otherwise
 *                     returns the group zoom manager, from among 'members',
 *                     'mods', and 'admins'.
 */
function bp_zoom_group_get_manager( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$manager = groups_get_groupmeta( $group_id, 'bp-group-zoom-manager', true );

	// Backward compatibility. When '$manager' is not set, fall back to a default value.
	if ( ! $manager ) {
		$manager = apply_filters( 'bp_zoom_group_manager_fallback', 'admins' );
	}

	/**
	 * Filters the album status of a group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $manager Membership level needed to manage albums.
	 * @param int    $group_id      ID of the group whose manager is being checked.
	 */
	return apply_filters( 'bp_zoom_group_get_manager', $manager, $group_id );
}

/**
 * Check whether a user is allowed to manage zoom meetings in a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_zoom( $user_id, $group_id ) {
	$is_allowed = false;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'members' === $manager ) {
		$is_allowed = true;
	} elseif ( 'mods' === $manager && ( $is_mod || $is_admin ) ) {
		$is_allowed = true;
	} elseif ( 'admins' === $manager && $is_admin ) {
		$is_allowed = true;
	}

	return apply_filters( 'bp_zoom_groups_can_user_manage_zoom', $is_allowed );
}

/**
 * Check whether a user is allowed to manage zoom meetings in a given group.
 *
 * @since 1.0.0
 *
 * @param int $meeting_id ID of the Meeting.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_meeting( $meeting_id ) {
	if ( ! is_user_logged_in() || empty( $meeting_id ) ) {
		return false;
	}

	$meeting = new BP_Zoom_Meeting( $meeting_id );

	if ( empty( $meeting->id ) ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	$group_id = bp_get_current_group_id();
	$user_id  = bp_loggedin_user_id();

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'mods' === $manager && ( $is_mod || $is_admin ) ) {
		return true;
	} elseif ( 'admins' === $manager && $is_admin ) {
		return true;
	}

	if ( $user_id !== $meeting->user_id ) {
		return false;
	}

	return true;
}

/**
 * Check whether a user is allowed to manage zoom webinars in a given group.
 *
 * @since 1.0.9
 *
 * @param int $webinar_id ID of the Webinar.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_webinar( $webinar_id ) {
	if ( ! is_user_logged_in() || empty( $webinar_id ) ) {
		return false;
	}

	$webinar = new BP_Zoom_Webinar( $webinar_id );

	if ( empty( $webinar->id ) ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	$group_id = bp_get_current_group_id();
	$user_id  = bp_loggedin_user_id();

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'mods' === $manager && ( $is_mod || $is_admin ) ) {
		return true;
	} elseif ( 'admins' === $manager && $is_admin ) {
		return true;
	}

	if ( $user_id !== $webinar->user_id ) {
		return false;
	}

	return true;
}

/**
 * Get group meetings url.
 *
 * @param object|bool $group Group object.
 * @since 1.0.9
 * @return string URL to group meetings page.
 */
function bp_zoom_get_groups_meetings_url( $group = false ) {

	if ( empty( $group ) ) {
		$group = groups_get_current_group();
	}

	if ( empty( $group ) ) {
		return '';
	}

	$group_link = bp_get_group_permalink( $group );

	return trailingslashit( $group_link . 'zoom/meetings/' );
}

/**
 * Get group webinars url.
 *
 * @param object|bool $group Group object.
 * @since 1.0.9
 * @return string URL to group webinars page.
 */
function bp_zoom_get_groups_webinars_url( $group = false ) {

	if ( empty( $group ) ) {
		$group = groups_get_current_group();
	}

	if ( empty( $group ) ) {
		return '';
	}

	$group_link = bp_get_group_permalink( $group );

	return trailingslashit( $group_link . 'zoom/webinars/' );
}

/**
 * Check if meeting page.
 *
 * @since 1.0.8
 * @return bool true if meetings page otherwise false.
 */
function bp_zoom_is_meetings() {
	return bp_zoom_is_groups_zoom() && 'meetings' === bp_action_variable( 0 );
}

/**
 * Check if past meeting page.
 *
 * @since 1.0.8
 * @return bool true if past meetings page otherwise false.
 */
function bp_zoom_is_past_meetings() {
	return bp_zoom_is_groups_zoom() && 'past-meetings' === bp_action_variable( 0 );
}

/**
 * Check if single meeting page
 *
 * @since 1.0.0
 * @return bool true if single meeting page otherwise false.
 */
function bp_zoom_is_single_meeting() {
	return bp_zoom_is_groups_zoom() && 'meetings' === bp_action_variable( 0 ) && is_numeric( bp_action_variable( 1 ) );
}

/**
 * Check if current request is create meeting.
 *
 * @since 1.0.0
 */
function bp_zoom_is_create_meeting() {
	if ( bp_zoom_is_groups_zoom() && 'create-meeting' === bp_action_variable( 0 ) ) {
		return true;
	}
	return false;
}

/**
 * Check if current request is create meeting.
 *
 * @since 1.0.0
 */
function bp_zoom_is_edit_meeting() {
	if ( bp_zoom_is_groups_zoom() && 'meetings' === bp_action_variable( 0 ) && 'edit' === bp_action_variable( 1 ) ) {
		return true;
	}
	return false;
}

/**
 * Get edited meeting id.
 *
 * @return false|int ID of the meeting or false otherwise.
 */
function bp_zoom_get_edit_meeting_id() {
	if ( bp_zoom_is_edit_meeting() ) {
		return (int) bp_action_variable( 2 );
	}
	return false;
}

/**
 * Get edit meeting.
 *
 * @since 1.0.0
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_edit_meeting() {
	$meeting_id = bp_zoom_get_edit_meeting_id();
	if ( $meeting_id ) {
		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			return $meeting;
		}
	}
	return false;
}

/**
 * Get single meeting.
 *
 * @since 1.0.0
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting() {
	global $bp_zoom_current_meeting;
	if ( bp_zoom_is_single_meeting() && empty( $bp_zoom_current_meeting ) ) {
		$meeting_id = (int) bp_action_variable( 1 );
		$meeting    = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			$bp_zoom_current_meeting = $meeting;
			return $bp_zoom_current_meeting;
		}
	}

	return $bp_zoom_current_meeting;
}

/**
 * Get single meeting id.
 *
 * @since 1.0.0
 * @return int|bool ID of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting_id() {
	if ( bp_zoom_is_single_meeting() ) {
		return (int) bp_action_variable( 1 );
	}
	return false;
}

/**
 * Check if current user has permission to start meeting.
 *
 * @since 1.0.0
 * @param int $meeting_id Meeting ID.
 *
 * @return bool true if user has permission otherwise false.
 */
function bp_zoom_can_current_user_start_meeting( $meeting_id ) {
	// check is user loggedin.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// get meeting exists.
	$meeting = new BP_Zoom_Meeting( $meeting_id );

	// check meeting exists.
	if ( empty( $meeting->id ) || empty( $meeting->group_id ) ) {
		return false;
	}

	$current_userdata = get_userdata( get_current_user_id() );

	if ( ! empty( $current_userdata ) ) {
		$userinfo = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-host-user', true );

		if ( ! empty( $userinfo ) ) {
			$userinfo = json_decode( $userinfo );
			if ( $current_userdata->user_email === $userinfo->email ) {
				return true;
			}
			// check meeting alt user ids have current user's id or not.
			if ( in_array( $current_userdata->user_email, explode( ',', $meeting->alternative_host_ids ), true ) ) {
				return true;
			}
		}
	}

	// return false atleast.
	return false;
}

/**
 * Check if current user has permission to start webinar.
 *
 * @since 1.0.9
 * @param int $webinar_id Webinar ID.
 *
 * @return bool true if user has permission otherwise false.
 */
function bp_zoom_can_current_user_start_webinar( $webinar_id ) {
	// check is user loggedin.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// get webinar exists.
	$webinar = new BP_Zoom_Webinar( $webinar_id );

	// check webinar exists.
	if ( empty( $webinar->id ) || empty( $webinar->group_id ) ) {
		return false;
	}

	$current_userdata = get_userdata( get_current_user_id() );

	if ( ! empty( $current_userdata ) ) {
		$userinfo = groups_get_groupmeta( $webinar->group_id, 'bp-group-zoom-api-host-user', true );

		if ( ! empty( $userinfo ) ) {
			$userinfo = json_decode( $userinfo );
			if ( $current_userdata->user_email === $userinfo->email ) {
				return true;
			}
			// check meeting alt user ids have current user's id or not.
			if ( in_array( $current_userdata->user_email, explode( ',', $webinar->alternative_host_ids ), true ) ) {
				return true;
			}
		}
	}

	// return false atleast.
	return false;
}

/**
 * Returns the current group meeting tab slug.
 *
 * @since 1.0.0
 *
 * @return bool|string $tab The current meeting tab's slug, false otherwise.
 */
function bp_zoom_group_current_meeting_tab() {
	$tab = false;
	if ( bp_is_groups_component() && bp_is_current_action( 'zoom' ) ) {
		if ( false !== bp_action_variable( 0 ) ) {
			$tab = bp_action_variable( 0 );
		} else {
			$tab = 'zoom';
		}
	}

	/**
	 * Filters the current group meeting tab slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Current group meeting tab slug.
	 */
	return apply_filters( 'bp_zoom_get_group_current_meeting_tab', $tab );
}

/**
 * Returns the current group meeting tab slug.
 *
 * @since 1.0.9
 *
 * @return bool|string $tab The current meeting tab's slug, false otherwise.
 */
function bp_zoom_group_current_tab() {
	$tab = false;
	if ( bp_is_groups_component() && bp_is_current_action( 'zoom' ) ) {
		if ( false !== bp_action_variable( 0 ) ) {
			$tab = bp_action_variable( 0 );
		} else {
			$tab = 'zoom';
		}
	}

	/**
	 * Filters the current group zoom tab slug.
	 *
	 * @since 1.0.9
	 *
	 * @param string $tab Current group zoom tab slug.
	 */
	return apply_filters( 'bp_zoom_group_current_tab', $tab );
}

/**
 * Check if webinars enabled in group.
 *
 * @param int $group_id Group id.
 * @since 1.0.9
 * @return bool true if webinars enabled otherwise false.
 */
function bp_zoom_groups_is_webinars_enabled( $group_id ) {
	$webinar_enabled = groups_get_groupmeta( $group_id, 'bp-group-zoom-enable-webinar', true );

	if ( ! empty( $webinar_enabled ) ) {
		return true;
	}
	return false;
}

/**
 * Check if webinars page
 *
 * @since 1.0.8
 * @return bool true if webinars page otherwise false.
 */
function bp_zoom_is_webinars() {
	return bp_zoom_is_groups_zoom() && 'webinars' === bp_action_variable( 0 );
}

/**
 * Check if past webinars page
 *
 * @since 1.0.8
 * @return bool true if past webinars page otherwise false.
 */
function bp_zoom_is_past_webinars() {
	return bp_zoom_is_groups_zoom() && 'past-webinars' === bp_action_variable( 0 );
}

/**
 * Check if single webinar page
 *
 * @since 1.0.8
 * @return bool true if single webinar page otherwise false.
 */
function bp_zoom_is_single_webinar() {
	return bp_zoom_is_groups_zoom() && 'webinars' === bp_action_variable( 0 ) && is_numeric( bp_action_variable( 1 ) );
}

/**
 * Check if current request is create webinar.
 *
 * @since 1.0.8
 */
function bp_zoom_is_create_webinar() {
	if ( bp_zoom_is_groups_zoom() && 'create-webinar' === bp_action_variable( 0 ) ) {
		return true;
	}
	return false;
}

/**
 * Check if current request is create webinar.
 *
 * @since 1.0.8
 */
function bp_zoom_is_edit_webinar() {
	if ( bp_zoom_is_groups_zoom() && 'webinars' === bp_action_variable( 0 ) && 'edit' === bp_action_variable( 1 ) ) {
		return true;
	}
	return false;
}

/**
 * Get edited webinar id.
 *
 * @return false|int ID of the webinar or false otherwise.
 */
function bp_zoom_get_edit_webinar_id() {
	if ( bp_zoom_is_edit_webinar() ) {
		return (int) bp_action_variable( 2 );
	}
	return false;
}

/**
 * Get edit webinar.
 *
 * @since 1.0.8
 * @return object|bool object of the webinar or false if not found.
 */
function bp_zoom_get_edit_webinar() {
	$webinar_id = bp_zoom_get_edit_webinar_id();
	if ( $webinar_id ) {
		$webinar = new BP_Zoom_Meeting( $webinar_id );

		if ( ! empty( $webinar->id ) ) {
			return $webinar;
		}
	}
	return false;
}

/**
 * Get single webinar.
 *
 * @since 1.0.8
 * @return object|bool object of the webinar or false if not found.
 */
function bp_zoom_get_current_webinar() {
	global $bp_zoom_current_webinar;
	if ( bp_zoom_is_single_webinar() && empty( $bp_zoom_current_webinar ) ) {
		$webinar_id = (int) bp_action_variable( 1 );
		$webinar    = new BP_Zoom_Webinar( $webinar_id );

		if ( ! empty( $webinar->id ) ) {
			$bp_zoom_current_webinar = $webinar;
			return $bp_zoom_current_webinar;
		}
	}

	return $bp_zoom_current_webinar;
}

/**
 * Get single webinar id.
 *
 * @since 1.0.8
 * @return int|bool ID of the webinar or false if not found.
 */
function bp_zoom_get_current_webinar_id() {
	if ( bp_zoom_is_single_webinar() ) {
		return (int) bp_action_variable( 1 );
	}
	return false;
}

/**
 * Delete activities when meeting deleted.
 *
 * @since 1.0.0
 * @param array $meetings Meetings list or array.
 */
function bp_zoom_meeting_delete_meeting_activity( $meetings ) {
	if ( ! empty( $meetings ) && bp_is_active( 'activity' ) ) {
		// Pluck the activity IDs out of the $meetings array.
		$activity_ids = wp_parse_id_list( wp_list_pluck( $meetings, 'activity_id' ) );
		foreach ( $activity_ids as $activity_id ) {
			bp_activity_delete( array( 'id' => $activity_id ) );
		}

		// Delete notification activity for simple meeting.
		foreach ( $meetings as $meeting_id ) {
			$activity_id = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_notification_activity_id', true );
			if ( ! empty( $activity_id ) ) {
				bp_activity_delete( array( 'id' => $activity_id ) );
			}
		}
	}
}
add_action( 'bp_zoom_meeting_after_delete', 'bp_zoom_meeting_delete_meeting_activity' );

/**
 * Delete notifications when meeting deleted.
 *
 * @since 1.0.5
 * @param array $meetings Meetings deleted.
 */
function bp_zoom_meeting_delete_meeting_notifications( $meetings ) {
	if ( ! empty( $meetings ) && bp_is_active( 'notifications' ) ) {
		foreach ( $meetings as $meeting ) {
			bp_notifications_delete_all_notifications_by_type( $meeting->group_id, buddypress()->groups->id, 'zoom_meeting_created', $meeting->id );
		}
	}
}
add_action( 'bp_zoom_meeting_after_delete', 'bp_zoom_meeting_delete_meeting_notifications' );

/**
 * Get the recurrence label for a meeting
 *
 * @param int         $meeting_id Meeting ID in the site.
 * @param object|bool $meeting_details Meeting object from zoom.
 *
 * @since 1.0.4
 * @return bool|string|void Recurrence label.
 */
function bp_zoom_get_recurrence_label( $meeting_id, $meeting_details = false ) {
	if ( ! empty( $meeting_id ) && empty( $meeting_details ) ) {

		$meeting = new BP_Zoom_Meeting( $meeting_id );
		if ( 'meeting_occurrence' === $meeting->zoom_type ) {
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );
			if ( ! empty( $parent_meeting ) ) {
				$meeting_id = $parent_meeting->id;
			}
		}

		$meeting_details = json_decode( wp_json_encode( bp_get_zoom_meeting_zoom_details( $meeting_id ) ) );
	}

	if ( empty( $meeting_id ) && empty( $meeting_details ) ) {
		return false;
	}

	$recurrence  = array();
	$occurrences = array();
	if ( ! empty( $meeting_details ) ) {
		if ( ! empty( $meeting_details->recurrence ) ) {
			$recurrence = $meeting_details->recurrence;
		}

		if ( ! empty( $meeting_details->occurrences ) ) {
			$occurrences = $meeting_details->occurrences;
		}
	}

	if ( empty( $recurrence ) || empty( $occurrences ) ) {
		return false;
	}

	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			unset( $occurrences[ $occurrence_key ] );
		}
	}

	$meeting_date              = false;
	$current_occurrence_offset = 0;
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( wp_date( 'U', strtotime( 'now' ) ) < strtotime( $occurrence->start_time ) ) {
			$meeting_date = $occurrence->start_time;
			break;
		}
		$current_occurrence_offset++;
	}

	if ( empty( $meeting_date ) ) {
		return;
	}

	$future_occurrences   = array_slice( $occurrences, $current_occurrence_offset, count( $occurrences ) );
	$no_of_occurrences    = count( $future_occurrences );
	$last_occurrence_date = end( $occurrences )->start_time;

	$return = '';
	switch ( $recurrence->type ) {
		case 1:
			$return = __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'days', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'day', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $meeting_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 2:
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'weeks on', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'week on', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->weekly_days ) ) {
				$weekly_days = explode( ',', $recurrence->weekly_days );

				// Changing weekly days to always return integer array values.
				$weekly_days = array_map(
					function ( $weekly_day ) {
						return (int) $weekly_day;
					},
					$weekly_days
				);

				if ( in_array( 1, $weekly_days, true ) ) {
					$return .= __( ' Sun', 'buddyboss-pro' );
				}
				if ( in_array( 2, $weekly_days, true ) ) {
					$return .= __( ' Mon', 'buddyboss-pro' );
				}
				if ( in_array( 3, $weekly_days, true ) ) {
					$return .= __( ' Tue', 'buddyboss-pro' );
				}
				if ( in_array( 4, $weekly_days, true ) ) {
					$return .= __( ' Wed', 'buddyboss-pro' );
				}
				if ( in_array( 5, $weekly_days, true ) ) {
					$return .= __( ' Thu', 'buddyboss-pro' );
				}
				if ( in_array( 6, $weekly_days, true ) ) {
					$return .= __( ' Fri', 'buddyboss-pro' );
				}
				if ( in_array( 7, $weekly_days, true ) ) {
					$return .= __( ' Sat', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $meeting_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 3:
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'months on the', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'month on the', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_day ) ) {
				$return .= ' ' . $recurrence->monthly_day . ' ' . __( 'of the month', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_week ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week ) {
					$return .= __( 'First', 'buddyboss-pro' );
				} elseif ( 2 === $recurrence->monthly_week ) {
					$return .= __( 'Second', 'buddyboss-pro' );
				} elseif ( 3 === $recurrence->monthly_week ) {
					$return .= __( 'Third', 'buddyboss-pro' );
				} elseif ( 4 === $recurrence->monthly_week ) {
					$return .= __( 'Fourth', 'buddyboss-pro' );
				} elseif ( - 1 === $recurrence->monthly_week ) {
					$return .= __( 'Last', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->monthly_week_day ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sun', 'buddyboss-pro' );
				}
				if ( 2 === $recurrence->monthly_week_day ) {
					$return .= __( 'Mon', 'buddyboss-pro' );
				}
				if ( 3 === $recurrence->monthly_week_day ) {
					$return .= __( 'Tue', 'buddyboss-pro' );
				}
				if ( 4 === $recurrence->monthly_week_day ) {
					$return .= __( 'Wed', 'buddyboss-pro' );
				}
				if ( 5 === $recurrence->monthly_week_day ) {
					$return .= __( 'Thu', 'buddyboss-pro' );
				}
				if ( 6 === $recurrence->monthly_week_day ) {
					$return .= __( 'Fri', 'buddyboss-pro' );
				}
				if ( 7 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sat', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $meeting_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		default:
			break;
	}

	/**
	 * Filters the recurrence label for a meeting.
	 *
	 * @since 1.0.4
	 *
	 * @param string      $return          Recurrence meeting label.
	 * @param int         $meeting_id      Meeting ID in the site.
	 * @param object|bool $meeting_details Meeting object from zoom.
	 */
	return apply_filters( 'bp_zoom_get_recurrence_label', $return, $meeting_id, $meeting_details );
}

/**
 * Get the recurrence label for a webinar
 *
 * @param int         $webinar_id      Webinar ID in the site.
 * @param object|bool $webinar_details Webinar object from zoom.
 *
 * @return bool|string|void Recurrence label.
 * @since 1.0.9
 */
function bp_zoom_get_webinar_recurrence_label( $webinar_id, $webinar_details = false ) {
	if ( ! empty( $webinar_id ) && empty( $webinar_details ) ) {

		$webinar = new BP_Zoom_Webinar( $webinar_id );
		if ( 'webinar_occurrence' === $webinar->zoom_type ) {
			$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );
			if ( ! empty( $parent_webinar ) ) {
				$webinar_id = $parent_webinar->id;
			}
		}

		$webinar_details = json_decode( wp_json_encode( bp_get_zoom_webinar_zoom_details( $webinar_id ) ) );
	}

	if ( empty( $webinar_id ) && empty( $webinar_details ) ) {
		return false;
	}

	$recurrence  = array();
	$occurrences = array();
	if ( ! empty( $webinar_details ) ) {
		if ( ! empty( $webinar_details->recurrence ) ) {
			$recurrence = $webinar_details->recurrence;
		}

		if ( ! empty( $webinar_details->occurrences ) ) {
			$occurrences = $webinar_details->occurrences;
		}
	}

	if ( empty( $recurrence ) || empty( $occurrences ) ) {
		return false;
	}

	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			unset( $occurrences[ $occurrence_key ] );
		}
	}

	$meeting_date              = false;
	$current_occurrence_offset = 0;
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( wp_date( 'U', strtotime( 'now' ) ) < strtotime( $occurrence->start_time ) ) {
			$meeting_date = $occurrence->start_time;
			break;
		}
		$current_occurrence_offset++;
	}

	if ( empty( $meeting_date ) ) {
		return;
	}

	$future_occurrences   = array_slice( $occurrences, $current_occurrence_offset, count( $occurrences ) );
	$no_of_occurrences    = count( $future_occurrences );
	$last_occurrence_date = end( $occurrences )->start_time;

	$return = '';
	switch ( $recurrence->type ) {
		case 1:
			$return = __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'days', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'day', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $webinar_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 2:
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'weeks on', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'week on', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->weekly_days ) ) {
				$weekly_days = explode( ',', $recurrence->weekly_days );

				// Changing weekly days to always return integer array values.
				$weekly_days = array_map(
					function ( $weekly_day ) {
						return (int) $weekly_day;
					},
					$weekly_days
				);

				if ( in_array( 1, $weekly_days, true ) ) {
					$return .= __( ' Sun', 'buddyboss-pro' );
				}
				if ( in_array( 2, $weekly_days, true ) ) {
					$return .= __( ' Mon', 'buddyboss-pro' );
				}
				if ( in_array( 3, $weekly_days, true ) ) {
					$return .= __( ' Tue', 'buddyboss-pro' );
				}
				if ( in_array( 4, $weekly_days, true ) ) {
					$return .= __( ' Wed', 'buddyboss-pro' );
				}
				if ( in_array( 5, $weekly_days, true ) ) {
					$return .= __( ' Thu', 'buddyboss-pro' );
				}
				if ( in_array( 6, $weekly_days, true ) ) {
					$return .= __( ' Fri', 'buddyboss-pro' );
				}
				if ( in_array( 7, $weekly_days, true ) ) {
					$return .= __( ' Sat', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $webinar_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 3:
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'months on the', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'month on the', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_day ) ) {
				$return .= ' ' . $recurrence->monthly_day . ' ' . __( 'of the month', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_week ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week ) {
					$return .= __( 'First', 'buddyboss-pro' );
				} elseif ( 2 === $recurrence->monthly_week ) {
					$return .= __( 'Second', 'buddyboss-pro' );
				} elseif ( 3 === $recurrence->monthly_week ) {
					$return .= __( 'Third', 'buddyboss-pro' );
				} elseif ( 4 === $recurrence->monthly_week ) {
					$return .= __( 'Fourth', 'buddyboss-pro' );
				} elseif ( - 1 === $recurrence->monthly_week ) {
					$return .= __( 'Last', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->monthly_week_day ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sun', 'buddyboss-pro' );
				}
				if ( 2 === $recurrence->monthly_week_day ) {
					$return .= __( 'Mon', 'buddyboss-pro' );
				}
				if ( 3 === $recurrence->monthly_week_day ) {
					$return .= __( 'Tue', 'buddyboss-pro' );
				}
				if ( 4 === $recurrence->monthly_week_day ) {
					$return .= __( 'Wed', 'buddyboss-pro' );
				}
				if ( 5 === $recurrence->monthly_week_day ) {
					$return .= __( 'Thu', 'buddyboss-pro' );
				}
				if ( 6 === $recurrence->monthly_week_day ) {
					$return .= __( 'Fri', 'buddyboss-pro' );
				}
				if ( 7 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sat', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ), new DateTimeZone( $webinar_details->timezone ) );
			}

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		default:
			break;
	}

	/**
	 * Filters the recurrence label for a webinar.
	 *
	 * @since 1.0.7
	 *
	 * @param string      $return          Recurrence meeting label.
	 * @param int         $webinar_id      Webinar ID in the site.
	 * @param object|bool $webinar_details Webinar object from zoom.
	 */
	return apply_filters( 'bp_zoom_get_webinar_recurrence_label', $return, $webinar_id, $webinar_details );
}

/**
 * Get the first occurrence date for a meeting
 *
 * @param int         $meeting_id Meeting ID in the site.
 * @param object|bool $meeting_details Meeting object from zoom.
 *
 * @since 1.0.9
 * @return bool|string|void Recurrence date.
 */
function bp_zoom_get_first_occurrence_date_utc( $meeting_id, $meeting_details = false ) {
	if ( ! empty( $meeting_id ) && empty( $meeting_details ) ) {

		$meeting = new BP_Zoom_Meeting( $meeting_id );
		if ( 'meeting_occurrence' === $meeting->zoom_type ) {
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );
			if ( ! empty( $parent_meeting ) ) {
				$meeting_id = $parent_meeting->id;
			}
		}

		$meeting_details = json_decode( wp_json_encode( bp_get_zoom_meeting_zoom_details( $meeting_id ) ) );
	}

	if ( empty( $meeting_id ) && empty( $meeting_details ) ) {
		return false;
	}

	$occurrences = array();
	if ( ! empty( $meeting_details ) && ! empty( $meeting_details->occurrences ) ) {
		$occurrences = $meeting_details->occurrences;
	}

	if ( empty( $occurrences ) ) {
		return false;
	}

	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			unset( $occurrences[ $occurrence_key ] );
		}
	}

	$meeting_date = false;
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		$meeting_date = $occurrence->start_time;
		break;
	}

	if ( empty( $meeting_date ) ) {
		return false;
	}

	/**
	 * Filters the first occurrence date for a meeting.
	 *
	 * @since 1.0.9
	 *
	 * @param string      $meeting_date    Meeting first occurrence date.
	 * @param int         $meeting_id      Meeting ID in the site.
	 * @param object|bool $meeting_details Meeting object from zoom.
	 */
	return apply_filters( 'bp_zoom_get_first_occurrence_date_utc', $meeting_date, $meeting_id, $meeting_details );
}

/**
 * Get the first occurrence date for a webinar
 *
 * @param int         $webinar_id      Webinar ID in the site.
 * @param object|bool $webinar_details Webinar object from zoom.
 *
 * @return bool|string|void Recurrence date.
 * @since 1.0.9
 */
function bp_zoom_get_webinar_first_occurrence_date_utc( $webinar_id, $webinar_details = false ) {
	if ( ! empty( $webinar_id ) && empty( $webinar_details ) ) {

		$webinar = new BP_Zoom_Webinar( $webinar_id );
		if ( 'webinar_occurrence' === $webinar->zoom_type ) {
			$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );
			if ( ! empty( $parent_webinar ) ) {
				$webinar_id = $parent_webinar->id;
			}
		}

		$webinar_details = json_decode( wp_json_encode( bp_get_zoom_webinar_zoom_details( $webinar_id ) ) );
	}

	if ( empty( $webinar_id ) && empty( $webinar_details ) ) {
		return false;
	}

	$occurrences = array();
	if ( ! empty( $webinar_details ) && ! empty( $webinar_details->occurrences ) ) {
		$occurrences = $webinar_details->occurrences;
	}

	if ( empty( $occurrences ) ) {
		return false;
	}

	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			unset( $occurrences[ $occurrence_key ] );
		}
	}

	$webinar_date = false;
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		$webinar_date = $occurrence->start_time;
		break;
	}

	if ( empty( $webinar_date ) ) {
		return false;
	}

	/**
	 * Filters the first occurrence date for a webinar.
	 *
	 * @since 1.0.9
	 *
	 * @param string      $webinar_date    Webinar first occurrence date.
	 * @param int         $webinar_id      Webinar ID in the site.
	 * @param object|bool $webinar_details Webinar object from zoom.
	 */
	return apply_filters( 'bp_zoom_get_webinar_first_occurrence_date_utc', $webinar_date, $webinar_id, $webinar_details );
}

/**
 * Add zoom 30 mins schedule to cron schedules.
 *
 * @param array $schedules Array of schedules for cron.
 *
 * @return array $schedules Array of schedules from cron with bp_zoom_30min.
 * @since 1.0.4
 */
function bp_zoom_meeting_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['bp_zoom_5min'] ) ) {
		if ( bp_zoom_is_zoom_enabled() ) {
			$schedules['bp_zoom_5min'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Once in 5 minutes', 'buddyboss-pro' ),
			);
		}
	}

	return $schedules;
}

add_filter( 'cron_schedules', 'bp_zoom_meeting_cron_schedules' ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval

/**
 * Schedule cron for the meeting to check recordings.
 *
 * @since 1.0.4
 */
function bp_zoom_meeting_schedule_cron() {
	if ( bp_zoom_is_zoom_enabled() ) {
		if ( ! wp_next_scheduled( 'bp_zoom_meeting_alerts_hook' ) ) {
			wp_schedule_event( time(), 'bp_zoom_5min', 'bp_zoom_meeting_alerts_hook' );
		}
		if ( ! wp_next_scheduled( 'bp_zoom_webinar_alerts_hook' ) ) {
			wp_schedule_event( time(), 'bp_zoom_5min', 'bp_zoom_webinar_alerts_hook' );
		}
	} else {
		wp_clear_scheduled_hook( 'bp_zoom_meeting_alerts_hook' );
		wp_clear_scheduled_hook( 'bp_zoom_webinar_alerts_hook' );
	}
}

add_action( 'bp_init', 'bp_zoom_meeting_schedule_cron' );

/**
 * Check zoom meeting recurring.
 *
 * @since 1.0.4
 */
function bp_zoom_meeting_alerts() {
	// return if groups not active.
	if ( ! bp_is_active( 'groups' ) || ( ! bp_is_active( 'activity' ) && ! bp_is_active( 'notifications' ) ) ) {
		return;
	}

	global $wpdb, $bp;

	$date_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$from     = $date_utc->format( 'Y-m-d H:i:s' );
	$from     = bp_get_option( 'bp_zoom_meeting_alert_last_checked_time', $from );
	$since    = $date_utc->modify( '+5 minutes' );
	$since    = $since->format( 'Y-m-d H:i:s' );

	// Update last checked time.
	bp_update_option( 'bp_zoom_meeting_alert_last_checked_time', $since );

	/**
	 * Query to generate the notification time from start date and meeting meta for the notification alert.
	 * Check the current time between notification time and meeting start time and alert not sent.
	 */
	$query = "SELECT DISTINCT m.id FROM {$bp->table_prefix}bp_zoom_meetings as m INNER JOIN {$bp->table_prefix}bp_zoom_meeting_meta AS mm 
        WHERE ( 
            m.alert != 0 AND            
            DATE_SUB( m.start_date_utc, INTERVAL m.alert MINUTE ) BETWEEN '{$from}' AND '{$since}' 
            AND mm.meta_key != 'bp_zoom_meeting_alert_sent' AND UTC_TIMESTAMP < m.start_date_utc
        ) OR (
            m.alert != 0 AND 
            mm.meta_key != 'bp_zoom_meeting_alert_sent' AND 
            ( UTC_TIMESTAMP BETWEEN DATE_SUB( m.start_date_utc, INTERVAL m.alert MINUTE ) AND m.start_date_utc ) 
       )";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$meetings = $wpdb->get_col( $query );

	if ( ! empty( $meetings ) ) {
		foreach ( $meetings as $id ) {
			$meeting = new BP_Zoom_Meeting( (int) $id );

			if ( empty( $meeting->id ) ) {
				continue;
			}

			// Get meeting alert meta.
			$alert_sent = bp_zoom_meeting_get_meta( $meeting->id, 'bp_zoom_meeting_alert_sent', true );

			// Check if alert already sent.
			if ( $alert_sent ) {
				continue;
			}

			// Get the group.
			$group = groups_get_group( $meeting->group_id );

			// Not exists.
			if ( empty( $group->id ) ) {
				continue;
			}

			$alert = $meeting->alert;

			// Check if occurrence meeting then find parent alert settings.
			if ( 'meeting_occurrence' === $meeting->zoom_type ) {
				$parent = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );

				if ( ! empty( $parent->id ) ) {
					$alert = $parent->alert;
				}
			}

			// No alerts for this meeting.
			if ( empty( $alert ) ) {
				continue;
			}

			// Create activity.
			bp_zoom_groups_create_meeting_activity( $meeting->id, 'zoom_meeting_notify' );

			// Send notifications.
			bp_zoom_groups_send_meeting_notifications( $meeting->id, true );

			// Update meta for meeting when alert is sent.
			bp_zoom_meeting_update_meta( $meeting->id, 'bp_zoom_meeting_alert_sent', true );
		}
	}
}

add_action( 'bp_zoom_meeting_alerts_hook', 'bp_zoom_meeting_alerts' );

/**
 * Check zoom webinar recurring.
 *
 * @since 1.0.9
 */
function bp_zoom_webinar_alerts() {
	// return if groups not active.
	if ( ! bp_is_active( 'groups' ) || ( ! bp_is_active( 'activity' ) && ! bp_is_active( 'notifications' ) ) ) {
		return;
	}

	global $wpdb, $bp;

	$date_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$from     = $date_utc->format( 'Y-m-d H:i:s' );
	$from     = bp_get_option( 'bp_zoom_webinar_alert_last_checked_time', $from );
	$since    = $date_utc->modify( '+5 minutes' );
	$since    = $since->format( 'Y-m-d H:i:s' );

	// Update last checked time.
	bp_update_option( 'bp_zoom_webinar_alert_last_checked_time', $since );

	/**
	 * Query to generate the notification time from start date and webinar meta for the notification alert.
	 * Check the current time between notification time and webinar start time and alert not sent.
	 */
	$query = "SELECT DISTINCT m.id FROM {$bp->table_prefix}bp_zoom_webinars as m INNER JOIN {$bp->table_prefix}bp_zoom_webinar_meta AS mm 
        WHERE ( 
            m.alert != 0 AND            
            DATE_SUB( m.start_date_utc, INTERVAL m.alert MINUTE ) BETWEEN '{$from}' AND '{$since}' 
            AND mm.meta_key != 'bp_zoom_webinar_alert_sent' AND UTC_TIMESTAMP < m.start_date_utc
        ) OR (
            m.alert != 0 AND 
            mm.meta_key != 'bp_zoom_webinar_alert_sent' AND 
            ( UTC_TIMESTAMP BETWEEN DATE_SUB( m.start_date_utc, INTERVAL m.alert MINUTE ) AND m.start_date_utc ) 
       )";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$webinars = $wpdb->get_col( $query );

	if ( ! empty( $webinars ) ) {
		foreach ( $webinars as $id ) {
			$webinar = new BP_Zoom_Webinar( (int) $id );

			if ( empty( $webinar->id ) ) {
				continue;
			}

			// Get webinar alert meta.
			$alert_sent = bp_zoom_webinar_get_meta( $webinar->id, 'bp_zoom_webinar_alert_sent', true );

			// Check if alert already sent.
			if ( $alert_sent ) {
				continue;
			}

			// Get the group.
			$group = groups_get_group( $webinar->group_id );

			// Not exists.
			if ( empty( $group->id ) ) {
				continue;
			}

			$alert = $webinar->alert;

			// Check if occurrence webinar then find parent alert settings.
			if ( 'webinar_occurrence' === $webinar->zoom_type ) {
				$parent = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );

				if ( ! empty( $parent->id ) ) {
					$alert = $parent->alert;
				}
			}

			// No alerts for this webinar.
			if ( empty( $alert ) ) {
				continue;
			}

			// Create activity.
			bp_zoom_groups_create_webinar_activity( $webinar->id, 'zoom_webinar_notify' );

			// Send notifications.
			bp_zoom_groups_send_webinar_notifications( $webinar->id, true );

			// Update meta for webinar when alert is sent.
			bp_zoom_webinar_update_meta( $webinar->id, 'bp_zoom_webinar_alert_sent', true );
		}
	}
}

add_action( 'bp_zoom_webinar_alerts_hook', 'bp_zoom_webinar_alerts' );

/**
 * Get converted date time.
 *
 * @param string $date_time Date and Time.
 * @param string $timezone Timezone string.
 * @param bool   $is_utc_date is UTC or not.
 *
 * @since 1.0.4
 * @return string Format date and time.
 */
function bp_zoom_convert_date_time( $date_time, $timezone, $is_utc_date = false ) {
	if ( 'Asia/Calcutta' === $timezone ) {
		$timezone = 'Asia/Kolkata';
	}
	if ( $is_utc_date ) {
		$date_time = new DateTime( $date_time, new DateTimeZone( 'UTC' ) );
	} else {
		$date_time = new DateTime( $date_time );
	}

	$date_time->setTimezone( new DateTimeZone( $timezone ) );

	return $date_time->format( 'Y-m-d\TH:i:s' );
}

/**
 * Return the meeting current live status.
 *
 * @param int $id                    ID of the meeting.
 *
 * @return string The meeting current live status.
 * @global object $zoom_meeting_template {@link BP_Zoom_Meeting_Template}
 *
 * @since 1.0.5
 */
function bp_get_zoom_meeting_current_status( $id = 0 ) {
	global $zoom_meeting_template;
	$meeting_status = '';

	if ( empty( $id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
		$id = $zoom_meeting_template->meeting->id;
	}

	if ( ! empty( $id ) ) {
		$meeting = new BP_Zoom_Meeting( $id );

		$meeting_status = bp_zoom_meeting_get_meta( $id, 'meeting_status', true );

		// if meeting occurrence, find parent and status.
		if ( empty( $meeting_status ) && ! empty( $meeting->id ) && 'meeting_occurrence' === $meeting->zoom_type ) {
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );
			if ( ! empty( $parent_meeting ) ) {
				$id             = $parent_meeting->id;
				$meeting_status = bp_zoom_meeting_get_meta( $id, 'meeting_status', true );
			}
		}
	}

	/**
	 * Filters the meeting current live status.
	 *
	 * @param string $meeting_status The meeting current live status.
	 *
	 * @since 1.0.5
	 */
	return apply_filters( 'bp_get_zoom_meeting_current_status', $meeting_status );
}

/**
 * Return the webinar current live status.
 *
 * @param int $id                    ID of the webinar.
 *
 * @return string The webinar current live status.
 * @global object $zoom_webinar_template {@link BP_Zoom_Webinar_Template}
 *
 * @since 1.0.9
 */
function bp_get_zoom_webinar_current_status( $id = 0 ) {
	global $zoom_webinar_template;
	$webinar_status = '';

	if ( empty( $id ) && ! empty( $zoom_webinar_template->webinar->id ) ) {
		$id = $zoom_webinar_template->webinar->id;
	}

	if ( ! empty( $id ) ) {
		$webinar = new BP_Zoom_Webinar( $id );

		$webinar_status = bp_zoom_webinar_get_meta( $id, 'webinar_status', true );

		// if webinar occurrence, find parent and status.
		if ( empty( $webinar_status ) && ! empty( $webinar->id ) && 'webinar_occurrence' === $webinar->zoom_type ) {
			$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );
			if ( ! empty( $parent_webinar ) ) {
				$id             = $parent_webinar->id;
				$webinar_status = bp_zoom_webinar_get_meta( $id, 'webinar_status', true );
			}
		}
	}

	/**
	 * Filters the webinar current live status.
	 *
	 * @param string $webinar_status The webinar current live status.
	 *
	 * @since 1.0.9
	 */
	return apply_filters( 'bp_get_zoom_webinar_current_status', $webinar_status );
}

/**
 * Get zoom meeting rewrited URL.
 *
 * @param string $original_url Meeting URL.
 * @param int    $id           Meeting ID.
 * @param int    $meeting_id   Meeting ID in zoom.
 *
 * @return false|string Rewrited URL or false.
 */
function bp_zoom_get_meeting_rewrite_url( $original_url, $id = 0, $meeting_id = 0 ) {
	global $bp_zoom_meeting_block;

	// Check if zoom hide urls enabled or not.
	if ( ! bp_zoom_is_zoom_hide_urls_enabled() ) {
		return $original_url;
	}

	// check if on any post, page or cpt single page.
	if ( ! empty( $bp_zoom_meeting_block ) && ! bp_is_group() ) {
		return trailingslashit( get_permalink( get_the_ID() ) ) . '?wm=1&mi=' . $bp_zoom_meeting_block->id;
	}

	$meeting = false;

	if ( ! empty( $id ) ) {
		// get meeting data.
		$meeting = new BP_Zoom_Meeting( $id );
	} elseif ( empty( $id ) && ! empty( $meeting_id ) ) {
		// get meeting data.
		$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
	}

	// check if id provided for site to look up.
	if ( ! empty( $meeting ) && bp_is_active( 'groups' ) && ! empty( $meeting->id ) && ! empty( $meeting->group_id ) ) {
		// get group data.
		$group = groups_get_group( $meeting->group_id );

		// check group empty or exits.
		if ( ! empty( $group ) ) {
			$group_link = bp_get_group_permalink( $group );

			$meeting_id = $meeting->meeting_id;
			if ( 'meeting_occurrence' === $meeting->zoom_type ) {
				$parent = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );

				if ( ! empty( $parent->meeting_id ) ) {
					$meeting_id = $parent->meeting_id;
				}
			}

			return trailingslashit( $group_link . 'zoom/meetings/' . $meeting->id ) . '?wm=1&mi=' . $meeting_id;
		}
	}

	return $original_url;
}

/**
 * Get zoom webinar rewrited URL.
 *
 * @param string $original_url Webinar URL.
 * @param int    $id           Webinar ID.
 * @param int    $webinar_id   Webinar ID in zoom.
 *
 * @return false|string Rewrited URL or false.
 */
function bp_zoom_get_webinar_rewrite_url( $original_url, $id = 0, $webinar_id = 0 ) {
	global $bp_zoom_webinar_block;

	// Check if zoom hide urls enabled or not.
	if ( ! bp_zoom_is_zoom_hide_webinar_urls_enabled() ) {
		return $original_url;
	}

	// check if on any post, page or cpt single page.
	if ( ! empty( $bp_zoom_webinar_block ) && ! bp_is_group() ) {
		return trailingslashit( get_permalink( get_the_ID() ) ) . '?wm=1&wi=' . $bp_zoom_webinar_block->id;
	}

	$webinar = false;

	if ( ! empty( $id ) ) {
		// get webinar data.
		$webinar = new BP_Zoom_Webinar( $id );
	} elseif ( empty( $id ) && ! empty( $webinar_id ) ) {
		// get webinar data.
		$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );
	}

	// check if id provided for site to look up.
	if ( ! empty( $webinar ) && bp_is_active( 'groups' ) && ! empty( $webinar->id ) && ! empty( $webinar->group_id ) ) {
		// get group data.
		$group = groups_get_group( $webinar->group_id );

		// check group empty or exits.
		if ( ! empty( $group ) ) {
			$group_link = bp_get_group_permalink( $group );

			$webinar_id = $webinar->webinar_id;
			if ( 'webinar_occurrence' === $webinar->zoom_type ) {
				$parent = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar->parent );

				if ( ! empty( $parent->webinar_id ) ) {
					$webinar_id = $parent->webinar_id;
				}
			}

			return trailingslashit( $group_link . 'zoom/webinars/' . $webinar->id ) . '?wm=1&wi=' . $webinar_id;
		}
	}

	return $original_url;
}

/**
 * Zoom web meeting start div element to footer.
 *
 * @since 1.0.8
 */
function bp_zoom_pro_add_zoom_web_meeting_append_div() {
	?>
	<div id="bp-zoom-dummy-web-div" style="position:absolute;z-index:9999;top: 0;background-color: black;width: 99999999px;height: 999999999999px;"></div>
	<?php
}

/**
 * Set zoom meeting tokens.
 *
 * @param BP_Email $bp_email         Email class.
 * @param array    $formatted_tokens Formatted tokens.
 * @param array    $tokens           Tokens.
 *
 * @return string
 * @since 1.0.9
 */
function bp_zoom_meeting_email_token_zoom_meeting( $bp_email, $formatted_tokens, $tokens ) {
	$output = '';

	$meeting = isset( $tokens['zoom_meeting'] ) ? $tokens['zoom_meeting'] : false;
	if ( empty( $meeting ) ) {
		$meeting_id = isset( $tokens['zoom_meeting.id'] ) ? $tokens['zoom_meeting.id'] : false;
		if ( empty( $meeting_id ) ) {
			return $output;
		}

		$meeting = new BP_Zoom_Meeting( $meeting_id );
	}

	if ( empty( $meeting ) ) {
		return $output;
	}

	$zoom_meeting_id = $meeting->meeting_id;
	if ( ! empty( $meeting->parent ) ) {
		$zoom_meeting_id = $meeting->parent;
	}

	$occurance_meeting = ( ! empty( $meeting->recurring ) ? bp_zoom_get_next_meeting_occurrence( $meeting->id ) : '' );

	$settings = bp_email_get_appearance_settings();

	ob_start();
	?>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['body_bg'] ); ?>; border-top: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-collapse: separate !important">
		<tbody>
		<tr>
			<td style="padding: 20px 0 0 0;">
				<?php echo '<h2 style="margin: 0 0 8px 0;font-size: 18px;">' . esc_html( $meeting->title ) . '</h2>'; ?>
				<?php echo '<p style="font-size: 13px;margin: 0 0 25px 0;">' . esc_html( $meeting->description ) . '</p>'; ?>
				<?php
				if ( ! empty( $meeting->recurring ) && ! empty( $occurance_meeting ) ) {
					$utc_date_time = $occurance_meeting->start_date_utc;
					$time_zone     = $occurance_meeting->timezone;
				} else {
					$utc_date_time = $meeting->start_date_utc;
					$time_zone     = $meeting->timezone;
				}

				$date  = wp_date( bp_core_date_format( false, true ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );
				$date .= __( ' at ', 'buddyboss-pro' );
				$date .= wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );
				?>
				<table style="margin: 0 !important;">
					<tbody>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Meeting ID', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;"><?php echo esc_attr( $zoom_meeting_id ); ?>
								<?php
								if ( ! empty( $meeting->recurring ) || 'meeting_occurrence' === $meeting->zoom_type ) {
									?>
									<br/><span style="font-size: 13px;color: #7F868F;"><?php echo esc_html( bp_zoom_get_recurrence_label( $meeting->id ) ); ?></span>
								<?php } ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Date and Time', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<?php echo '<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;">' . esc_html( $date ) . ( ! empty( $time_zone ) ? ' <span style="font-weight: normal;color: #7F868F;">(' . esc_html( bp_zoom_get_timezone_label( $time_zone ) ) . ')</span>' : '' ) . '</p>'; ?>
						</td>
					</tr>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;">
								<?php
								$duration = $meeting->duration;
								$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
								$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
								if ( 0 < $hours ) {
									/* translators: %d number of hours */
									echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								if ( 0 < $minutes ) {
									/* translators: %d number of minutes */
									echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</p>
						</td>
					</tr>

					<?php
					$registration_url = bp_get_zoom_meeting_registration_url( $meeting->id );
					if ( ! empty( $registration_url ) ) {
						?>
						<tr>
							<td style="width: 30%;vertical-align: top;">
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Registration Link', 'buddyboss-pro' ); ?></p>
							</td>
							<td>
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;word-break: break-all;">
									<a style="color: #007CFF; text-decoration: none;" target="_blank" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
								</p>
							</td>
						</tr>
						<?php
					}

					$join_url = bp_get_zoom_meeting_zoom_join_url( $meeting->id );
					if ( ! empty( $join_url ) ) {
						?>
						<tr>
							<td style="width: 30%;vertical-align: top;">
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Meeting Link', 'buddyboss-pro' ); ?></p>
							</td>
							<td>
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;word-break: break-all;">
									<a style="color: #007CFF; text-decoration: none;" <?php echo ! bp_zoom_is_zoom_hide_urls_enabled() ? 'target="_blank"' : ''; ?> href="<?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, $meeting->id ) ); ?>"><?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, $meeting->id ) ); ?></a>
								</p>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td style="width: 100%;vertical-align: top;" colspan="2">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 10px 0 15px 0;">
								<a style="color: #007CFF; text-decoration: none;" target="_blank" href="<?php echo esc_url( bp_get_zoom_meeting_url( $meeting->group_id, ( ( ! empty( $meeting->recurring ) && ! empty( $occurance_meeting ) ) ? $occurance_meeting->id : $meeting->id ) ) ); ?>"><?php esc_html_e( 'Meeting Details', 'buddyboss-pro' ); ?></a>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
	<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
	<?php
	$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

	return $output;
}

/**
 * Set zoom webinar tokens.
 *
 * @param BP_Email $bp_email         Email class.
 * @param array    $formatted_tokens Formatted tokens.
 * @param array    $tokens           Tokens.
 *
 * @return string
 * @since 1.0.9
 */
function bp_zoom_webinar_email_token_zoom_webinar( $bp_email, $formatted_tokens, $tokens ) {
	$output = '';

	$webinar = isset( $tokens['zoom_webinar'] ) ? $tokens['zoom_webinar'] : false;
	if ( empty( $webinar ) ) {
		$webinar_id = isset( $tokens['zoom_webinar.id'] ) ? $tokens['zoom_webinar.id'] : false;
		if ( empty( $webinar_id ) ) {
			return $output;
		}

		$webinar = new BP_Zoom_Webinar( $webinar_id );
	}

	if ( empty( $webinar ) ) {
		return $output;
	}

	$zoom_webinar_id = $webinar->webinar_id;
	if ( ! empty( $webinar->parent ) ) {
		$zoom_webinar_id = $webinar->parent;
	}

	$settings = bp_email_get_appearance_settings();

	ob_start();
	?>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['body_bg'] ); ?>; border-top: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-collapse: separate !important">
		<tbody>
		<tr>
			<td style="padding: 20px 0 0 0;">
				<?php echo '<h2 style="margin: 0 0 8px 0;font-size: 18px;">' . esc_html( $webinar->title ) . '</h2>'; ?>
				<?php echo '<p style="font-size: 13px;margin: 0 0 25px 0;">' . esc_html( $webinar->description ) . '</p>'; ?>
				<?php
				$utc_date_time = $webinar->start_date_utc;
				$time_zone     = $webinar->timezone;
				$date          = wp_date( bp_core_date_format( false, true ), strtotime( $utc_date_time ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );

				?>
				<table style="margin: 0 !important;">
					<tbody>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Webinar ID', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;"><?php echo esc_attr( $zoom_webinar_id ); ?>
								<?php
								if ( ! empty( $webinar->recurring ) || 'webinar_occurrence' === $webinar->zoom_type ) {
									?>
									<br/><span style="font-size: 13px;color: #7F868F;"><?php echo esc_html( bp_zoom_get_recurrence_label( $webinar->id ) ); ?></span>
								<?php } ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Date and Time', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<?php echo '<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;">' . esc_html( $date ) . ( ! empty( $time_zone ) ? ' <span style="font-weight: normal;color: #7F868F;">(' . esc_html( bp_zoom_get_timezone_label( $time_zone ) ) . ')</span>' : '' ) . '</p>'; ?>
						</td>
					</tr>
					<tr>
						<td style="width: 30%;vertical-align: top;">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></p>
						</td>
						<td>
							<p style="font-size: 13px;color: #23282d;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;font-weight: bold;">
								<?php
								$duration = $webinar->duration;
								$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
								$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
								if ( 0 < $hours ) {
									/* translators: %d number of hours */
									echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								if ( 0 < $minutes ) {
									/* translators: %d number of minutes */
									echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</p>
						</td>
					</tr>

					<?php
					$registration_url = bp_get_zoom_webinar_registration_url( $webinar->id );
					if ( ! empty( $registration_url ) ) {
						?>
						<tr>
							<td style="width: 30%;vertical-align: top;">
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Registration Link', 'buddyboss-pro' ); ?></p>
							</td>
							<td>
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;word-break: break-all;">
									<a style="color: #007CFF" target="_blank" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
								</p>
							</td>
						</tr>
						<?php
					}

					$join_url = bp_get_zoom_webinar_zoom_join_url( $webinar->id );
					if ( ! empty( $join_url ) ) {
						?>
						<tr>
							<td style="width: 30%;vertical-align: top;">
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;"><?php esc_html_e( 'Webinar Link', 'buddyboss-pro' ); ?></p>
							</td>
							<td>
								<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 0 0 15px 0;word-break: break-all;">
									<a style="color: #007CFF; text-decoration: none;" <?php echo ! bp_zoom_is_zoom_hide_urls_enabled() ? 'target="_blank"' : ''; ?> href="<?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, $webinar->id ) ); ?>"><?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, $webinar->id ) ); ?></a>
								</p>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td style="width: 100%;vertical-align: top;" colspan="2">
							<p style="font-size: 13px;color: #7F868F;letter-spacing: 0.24px;line-height: 19px;margin: 10px 0 15px 0;">
								<a style="color: #007CFF; text-decoration: none;" target="_blank" href="<?php echo esc_url( bp_get_zoom_webinar_url( $webinar->group_id, $webinar->id ) ); ?>"><?php esc_html_e( 'Webinar Details', 'buddyboss-pro' ); ?></a>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
	<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
	<?php
	$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

	return $output;
}

/**
 * Get first occurrance of the meeting.
 *
 * @since 1.1.0
 *
 * @param int $id Zoom meeting ID.
 *
 * @return mixed|void
 */
function bp_zoom_get_next_meeting_occurrence( $id ) {
	if ( empty( $id ) ) {
		return;
	}

	$meeting = new BP_Zoom_Meeting( $id );

	if ( empty( $meeting ) || empty( $meeting->recurring ) ) {
		return;
	}

	$occurrences = bp_zoom_meeting_get(
		array(
			'parent' => $meeting->meeting_id,
			'sort'   => 'ASC',
		)
	);

	if (
		empty( $occurrences ) ||
		empty( $occurrences['meetings'] ) ||
		is_wp_error( $occurrences )
	) {
		return;
	}

	$next_occurrence = '';
	foreach ( $occurrences['meetings'] as $occurrence_key => $occurrence ) {
		if ( wp_date( 'U', strtotime( 'now' ) ) < strtotime( $occurrence->start_date_utc ) ) {
			$next_occurrence = $occurrence;
			break;
		}
	}

	/**
	 * Filters next occurrence meeting.
	 *
	 * @since 1.1.0
	 *
	 * @param string $next_occurrence Next occurrence.
	 * @param int    $id              Meeting ID in the site.
	 */
	return apply_filters( 'bp_zoom_get_next_meeting_occurrence', $next_occurrence, $id );
}
