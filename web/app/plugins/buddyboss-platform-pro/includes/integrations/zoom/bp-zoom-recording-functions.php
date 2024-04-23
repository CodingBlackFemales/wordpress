<?php
/**
 * Zoom Recordings helpers
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add recording for meeting.
 *
 * @param array $args         {
 *                            Arguments for adding recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * }
 *
 * @return int Inserted Recording ID.
 * @since 1.0.0
 */
function bp_zoom_recording_add( $args = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => bp_core_current_time(),
		)
	);

	$wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$bp_prefix . 'bp_zoom_recordings',
		array(
			'id'           => $r['id'],
			'recording_id' => $r['recording_id'],
			'meeting_id'   => $r['meeting_id'],
			'uuid'         => $r['uuid'],
			'details'      => is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'],
			'password'     => $r['password'],
			'file_type'    => $r['file_type'],
			'start_time'   => $r['start_time'],
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	return $wpdb->insert_id;
}

/**
 * Update recording.
 *
 * @since 1.0.0
 *
 * @param array $args         {
 *                            Arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * }
 * @param array $where {
 *                            Where arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded.
 * }
 *
 * @return bool|false|int
 */
function bp_zoom_recording_update( $args = array(), $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['meeting_id'] ) ) {
		$value['meeting_id'] = $r['meeting_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$value['start_time'] = $r['start_time'];
		$value_args[]        = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$where_value['file_type'] = $r['file_type'];
		$where_value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$where_value['start_time'] = $r['start_time'];
		$where_value_args[]        = '%s';
	}

	return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bp_prefix . 'bp_zoom_recordings',
		$value,
		$where_value,
		$value_args,
		$where_value_args
	);
}

/**
 * Delete meeting recording.
 *
 * @since 1.0.0
 *
 * @param array $where {
 *                            Where arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 *
 * @return bool|false|int True if deleted, false otherwise.
 */
function bp_zoom_recording_delete( $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
		)
	);

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	return $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bp_prefix . 'bp_zoom_recordings',
		$where_value,
		$where_value_args
	);
}

/**
 * Get recordings.
 *
 * @since 1.0.0
 *
 * @param array $col {
 *                            Arguments for getting columns for recording row.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 * @param array $where {
 *                            Where arguments for getting recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $meeting_id   Meeting ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 *
 * @return array|object Recording results.
 */
function bp_zoom_recording_get( $col = array(), $where = array() ) {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$col,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
			'date_min'     => false,
			'date_max'     => false,
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['meeting_id'] ) ) {
		$value['meeting_id'] = $r['meeting_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	$where_conditions = array();
	foreach ( $where_value as $w_key => $w_value ) {
		$where_conditions[] = $w_key . ' = "' . $w_value . '"';
	}

	if ( ! empty( $w['date_min'] ) || ! empty( $w['date_max'] ) ) {

		$timezone_offset = wp_date( 'P', strtotime( 'now' ) );

		if ( ! empty( $w['date_min'] ) && ! empty( $w['date_max'] ) ) {
			$translated_date_min = new DateTime( $w['date_min'], new DateTimeZone( 'UTC' ) );
			$translated_date_min = $translated_date_min->format( 'Y-m-d H:i:s' );
			$translated_date_max = new DateTime( $w['date_max'], new DateTimeZone( 'UTC' ) );
			$translated_date_max = $translated_date_max->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_min ) && ! empty( $translated_date_max ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) >= %s AND CONVERT_TZ(start_time,"+00:00",%s) < %s', $timezone_offset, $translated_date_min, $timezone_offset, $translated_date_max );
			}
		} elseif ( ! empty( $w['date_min'] ) && empty( $w['date_max'] ) ) {
			$translated_date_min = new DateTime( $w['date_min'], new DateTimeZone( 'UTC' ) );
			$translated_date_min = $translated_date_min->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_min ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) >= %s', $timezone_offset, $translated_date_min );
			}
		} elseif ( empty( $w['date_min'] ) && ! empty( $w['date_max'] ) ) {
			$translated_date_max = new DateTime( $w['date_max'], new DateTimeZone( 'UTC' ) );
			$translated_date_max = $translated_date_max->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_max ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) <= %s', $timezone_offset, $translated_date_max );
			}
		}
	}

	$where_conditions[] = 'file_type != "TIMELINE"';

	$query =
		'SELECT ' . ( empty( $value ) ? '*' : implode( ',', $value ) ) . " 
						FROM {$bp_prefix}bp_zoom_recordings " . ( ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '' ) . ' ORDER BY start_time DESC';

	if ( count( $value ) > 1 || empty( $value ) ) {
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	} else {
		return $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}
}

/**
 * Fetch Zoom Meeting recordings via API.
 *
 * @param int $meeting_id Meeting ID from Zoom.
 * @since 1.0.6
 */
function bp_zoom_meeting_fetch_recordings( $meeting_id ) {
	// Check if Zoom Recordings enabled from the integration settings.
	if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
		return;
	}

	$recordings = false;

	$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );

	if ( ! empty( $meeting->group_id ) ) {

		// Check groups component active or not.
		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		// Connect to Zoom.
		bb_zoom_group_connect_api( $meeting->group_id );
	}

	// Get all meeting instances.
	$instances = bp_zoom_conference()->meeting_instances( $meeting_id );

	// Meeting instances found.
	if ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ! empty( $instances['response']->meetings ) ) {
		foreach ( $instances['response']->meetings as $response_meeting ) {

			$uuid = $response_meeting->uuid;
			// Add comma for slashed uuids.
			if ( false !== strpos( $response_meeting->uuid, '/' ) || false !== strpos( $response_meeting->uuid, '//' ) ) {
				$uuid = '"' . $response_meeting->uuid . '"';
			}

			// Get recordings by uuid.
			$uuid_recordings_response = bp_zoom_conference()->recordings_by_meeting( $uuid );

			// Check uuid response.
			if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
				$uuid_recordings_response = $uuid_recordings_response['response'];

				// Check recording files found or not.
				if ( ! empty( $uuid_recordings_response->recording_files ) ) {
					$recordings = true;

					// Get recording settings by uuid.
					$recording_settings = bp_zoom_conference()->recording_settings( $uuid );
					if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
						$recording_settings = $recording_settings['response'];
					} else {
						$recording_settings = false;
					}

					foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {

						// Check recording has id.
						if ( isset( $uuid_recordings_response_recording_file->id ) ) {

							// Get recordings already in system.
							$recording_exists = bp_zoom_recording_get(
								array(),
								array(
									'recording_id' => $uuid_recordings_response_recording_file->id,
									'meeting_id'   => $meeting_id,
									'uuid'         => $uuid_recordings_response->uuid,
								)
							);

							$args = array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'meeting_id'   => $meeting_id,
								'uuid'         => $uuid_recordings_response->uuid,
								'details'      => $uuid_recordings_response_recording_file,
								'file_type'    => $uuid_recordings_response_recording_file->file_type,
								'start_time'   => $uuid_recordings_response->start_time,
							);

							if ( ! empty( $recording_settings->password ) ) {
								$args['password'] = $recording_settings->password;
							}

							// Recording does exists.
							if ( ! empty( $recording_exists ) ) {

								// Update recording in system.
								bp_zoom_recording_update( $args, array( 'id' => $recording_exists[0]->id ) );
							} else {

								// Add recording in system.
								bp_zoom_recording_add( $args );
							}
						}
					}
				}
			}
		}
	}

	// When instances not found or no recordings found in instances.
	if ( ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ( empty( $instances['response']->meetings ) || $instances['response']->meetings ) ) || ! $recordings ) {

		// Get recordings by uuid.
		$uuid_recordings_response = bp_zoom_conference()->recordings_by_meeting( $meeting_id );
		if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
			$uuid_recordings_response = $uuid_recordings_response['response'];

			// Check recording files found or not.
			if ( ! empty( $uuid_recordings_response->recording_files ) ) {

				// Get recording settings by uuid.
				$recording_settings = bp_zoom_conference()->recording_settings( $uuid_recordings_response->uuid );
				if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
					$recording_settings = $recording_settings['response'];
				} else {
					$recording_settings = false;
				}

				foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {

					// Check recording has id.
					if ( isset( $uuid_recordings_response_recording_file->id ) ) {

						// Get recordings already in system.
						$recording_exists = bp_zoom_recording_get(
							array(),
							array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'meeting_id'   => $meeting_id,
								'uuid'         => $uuid_recordings_response->uuid,
							)
						);

						$args = array(
							'recording_id' => $uuid_recordings_response_recording_file->id,
							'meeting_id'   => $meeting_id,
							'uuid'         => $uuid_recordings_response->uuid,
							'details'      => $uuid_recordings_response_recording_file,
							'file_type'    => $uuid_recordings_response_recording_file->file_type,
							'start_time'   => $uuid_recordings_response->start_time,
						);

						if ( ! empty( $recording_settings->password ) ) {
							$args['password'] = $recording_settings->password;
						}

						// Recording does exists.
						if ( ! empty( $recording_exists ) ) {

							// Update recording in system.
							bp_zoom_recording_update( $args, array( 'id' => $recording_exists[0]->id ) );
						} else {

							// Add recording in system.
							bp_zoom_recording_add( $args );
						}
					}
				}
			}
		}
	}
}

/**
 * Get zoom recording rewrited URL.
 *
 * @param string $original_url Recording URL.
 * @param int    $id           Recording ID.
 * @param bool   $download     Download URL or not.
 *
 * @return string Rewrited URL or Original URL.
 */
function bp_zoom_get_recording_rewrite_url( $original_url, $id, $download = false ) {
	global $bp_zoom_meeting_block;

	// Check if zoom hide urls enabled or not.
	if ( ! bb_zoom_is_meeting_hide_urls_enabled() ) {
		return $original_url;
	}

	// get recording data.
	$recordings = bp_zoom_recording_get( array(), array( 'id' => $id ) );

	// check if exists in the system and has meeting id.
	if ( ! empty( $recordings[0]->meeting_id ) ) {
		$block = filter_input( INPUT_GET, 'block', FILTER_VALIDATE_INT );

		// check if on any post, page or cpt single page.
		if ( ! empty( $bp_zoom_meeting_block ) || 1 <= $block ) {
			$post_id = get_the_ID();
			if ( 1 <= $block ) {
				$post_id = $block;
			}
			$recording_url = get_permalink( $post_id ) . '?zoom-recording=' . $id;

			if ( $download ) {
				$recording_url .= '&download=1';
			}

			return $recording_url;
		}

		// get meeting data.
		$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $recordings[0]->meeting_id );

		// check meeting exists and has group assigned.
		if ( ! empty( $meeting->id ) && bp_is_active( 'groups' ) && $meeting->group_id ) {

			// get group data.
			$group = groups_get_group( $meeting->group_id );

			// check group empty or exits.
			if ( ! empty( $group ) ) {
				$group_link = bp_get_group_permalink( $group );

				$recording_url = trailingslashit( $group_link . 'zoom/meetings/' . $meeting->id ) . '?zoom-recording=' . $id;

				if ( $download ) {
					$recording_url .= '&download=1';
				}

				return $recording_url;
			}
		}
	}

	return $original_url;
}


/**
 * Add recording for webinar.
 *
 * @param array $args         {
 *                            Arguments for adding recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * }
 *
 * @return int Inserted Recording ID.
 * @since 1.0.9
 */
function bp_zoom_webinar_recording_add( $args = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => bp_core_current_time(),
		)
	);

	$wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$bp_prefix . 'bp_zoom_webinar_recordings',
		array(
			'id'           => $r['id'],
			'recording_id' => $r['recording_id'],
			'webinar_id'   => $r['webinar_id'],
			'uuid'         => $r['uuid'],
			'details'      => is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'],
			'password'     => $r['password'],
			'file_type'    => $r['file_type'],
			'start_time'   => $r['start_time'],
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	return $wpdb->insert_id;
}

/**
 * Update webinar recording.
 *
 * @since 1.0.9
 *
 * @param array $args         {
 *                            Arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 * }
 * @param array $where {
 *                            Where arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * @type array|bool $start_time   Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded.
 * }
 *
 * @return bool|false|int
 */
function bp_zoom_webinar_recording_update( $args = array(), $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['webinar_id'] ) ) {
		$value['webinar_id'] = $r['webinar_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$value['start_time'] = $r['start_time'];
		$value_args[]        = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['webinar_id'] ) ) {
		$where_value['webinar_id'] = $w['webinar_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$where_value['file_type'] = $r['file_type'];
		$where_value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$where_value['start_time'] = $r['start_time'];
		$where_value_args[]        = '%s';
	}

	return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bp_prefix . 'bp_zoom_webinar_recordings',
		$value,
		$where_value,
		$value_args,
		$where_value_args
	);
}

/**
 * Delete webinar recording.
 *
 * @since 1.0.9
 *
 * @param array $where {
 *                            Where arguments for updating recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 *
 * @return bool|false|int True if deleted, false otherwise.
 */
function bp_zoom_webinar_recording_delete( $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
		)
	);

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['webinar_id'] ) ) {
		$where_value['webinar_id'] = $w['webinar_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	return $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bp_prefix . 'bp_zoom_webinar_recordings',
		$where_value,
		$where_value_args
	);
}

/**
 * Get webinar recordings.
 *
 * @since 1.0.9
 *
 * @param array $col {
 *                            Arguments for getting columns for recording row.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $details      Recording Data stored as json from API.
 * @type string     $password     Password of the Recording session if provided.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 * @param array $where {
 *                            Where arguments for getting recording.
 *
 * @type int        $id           ID of the recording in the site.
 * @type string     $recording_id Recording ID from Zoom API.
 * @type int        $webinar_id   Webinar ID from Zoom API.
 * @type string     $uuid         UUID from Zoom API.
 * @type string     $file_type    Type of file like 'MP4' or 'M4A'.
 * }
 *
 * @return array|object Recording results.
 */
function bp_zoom_webinar_recording_get( $col = array(), $where = array() ) {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$col,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'webinar_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
			'date_min'     => false,
			'date_max'     => false,
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['webinar_id'] ) ) {
		$value['webinar_id'] = $r['webinar_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? wp_json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['webinar_id'] ) ) {
		$where_value['webinar_id'] = $w['webinar_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	$where_conditions = array();
	foreach ( $where_value as $w_key => $w_value ) {
		$where_conditions[] = $w_key . ' = "' . $w_value . '"';
	}

	if ( ! empty( $w['date_min'] ) || ! empty( $w['date_max'] ) ) {

		$timezone_offset = wp_date( 'P', strtotime( 'now' ) );

		if ( ! empty( $w['date_min'] ) && ! empty( $w['date_max'] ) ) {
			$translated_date_min = new DateTime( $w['date_min'], new DateTimeZone( 'UTC' ) );
			$translated_date_min = $translated_date_min->format( 'Y-m-d H:i:s' );
			$translated_date_max = new DateTime( $w['date_max'], new DateTimeZone( 'UTC' ) );
			$translated_date_max = $translated_date_max->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_min ) && ! empty( $translated_date_max ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) >= %s AND CONVERT_TZ(start_time,"+00:00",%s) < %s', $timezone_offset, $translated_date_min, $timezone_offset, $translated_date_max );
			}
		} elseif ( ! empty( $w['date_min'] ) && empty( $w['date_max'] ) ) {
			$translated_date_min = new DateTime( $w['date_min'], new DateTimeZone( 'UTC' ) );
			$translated_date_min = $translated_date_min->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_min ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) >= %s', $timezone_offset, $translated_date_min );
			}
		} elseif ( empty( $w['date_min'] ) && ! empty( $w['date_max'] ) ) {
			$translated_date_max = new DateTime( $w['date_max'], new DateTimeZone( 'UTC' ) );
			$translated_date_max = $translated_date_max->format( 'Y-m-d H:i:s' );

			if ( ! empty( $translated_date_max ) ) {
				$where_conditions[] = $wpdb->prepare( 'CONVERT_TZ(start_time,"+00:00",%s) <= %s', $timezone_offset, $translated_date_max );
			}
		}
	}

	$where_conditions[] = 'file_type != "TIMELINE"';

	$query =
		'SELECT ' . ( empty( $value ) ? '*' : implode( ',', $value ) ) . " 
						FROM {$bp_prefix}bp_zoom_webinar_recordings " . ( ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '' ) . ' ORDER BY start_time DESC';

	if ( count( $value ) > 1 || empty( $value ) ) {
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	} else {
		return $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}
}

/**
 * Fetch Zoom Webinar recordings via API.
 *
 * @param int $webinar_id Webinar ID from Zoom.
 * @since 1.0.9
 */
function bp_zoom_webinar_fetch_recordings( $webinar_id ) {
	// Check if Zoom Recordings enabled from the integration settings.
	if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
		return;
	}

	$recordings = false;

	$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );

	if ( ! empty( $webinar->group_id ) ) {

		// Check groups component active or not.
		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		// Connect to Zoom.
		bb_zoom_group_connect_api( $webinar->group_id );
	}

	// Get all webinar instances.
	$instances = bp_zoom_conference()->webinar_instances( $webinar_id );

	// Webinar instances found.
	if ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ! empty( $instances['response']->meetings ) ) {
		foreach ( $instances['response']->meetings as $response_webinar ) {

			$uuid = $response_webinar->uuid;
			// Add comma for slashed uuids.
			if ( false !== strpos( $response_webinar->uuid, '/' ) || false !== strpos( $response_webinar->uuid, '//' ) ) {
				$uuid = '"' . $response_webinar->uuid . '"';
			}

			// Get recordings by uuid.
			$uuid_recordings_response = bp_zoom_conference()->recordings_by_webinar( $uuid );

			// Check uuid response.
			if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
				$uuid_recordings_response = $uuid_recordings_response['response'];

				// Check recording files found or not.
				if ( ! empty( $uuid_recordings_response->recording_files ) ) {
					$recordings = true;

					// Get recording settings by uuid.
					$recording_settings = bp_zoom_conference()->recording_settings( $uuid );
					if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
						$recording_settings = $recording_settings['response'];
					} else {
						$recording_settings = false;
					}

					foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {

						// Check recording has id.
						if ( isset( $uuid_recordings_response_recording_file->id ) ) {

							// Get recordings already in system.
							$recording_exists = bp_zoom_webinar_recording_get(
								array(),
								array(
									'recording_id' => $uuid_recordings_response_recording_file->id,
									'webinar_id'   => $webinar_id,
									'uuid'         => $uuid_recordings_response->uuid,
								)
							);

							$args = array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'webinar_id'   => $webinar_id,
								'uuid'         => $uuid_recordings_response->uuid,
								'details'      => $uuid_recordings_response_recording_file,
								'file_type'    => $uuid_recordings_response_recording_file->file_type,
								'start_time'   => $uuid_recordings_response->start_time,
							);

							if ( ! empty( $recording_settings->password ) ) {
								$args['password'] = $recording_settings->password;
							}

							// Recording does exists.
							if ( ! empty( $recording_exists ) ) {

								// Update recording in system.
								bp_zoom_webinar_recording_update( $args, array( 'id' => $recording_exists[0]->id ) );
							} else {

								// Add recording in system.
								bp_zoom_webinar_recording_add( $args );
							}
						}
					}
				}
			}
		}
	}

	// When instances not found or no recordings found in instances.
	if ( ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ( empty( $instances['response']->meetings ) || $instances['response']->meetings ) ) || ! $recordings ) {

		// Get recordings by uuid.
		$uuid_recordings_response = bp_zoom_conference()->recordings_by_webinar( $webinar_id );
		if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
			$uuid_recordings_response = $uuid_recordings_response['response'];

			// Check recording files found or not.
			if ( ! empty( $uuid_recordings_response->recording_files ) ) {

				// Get recording settings by uuid.
				$recording_settings = bp_zoom_conference()->recording_settings( $uuid_recordings_response->uuid );
				if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
					$recording_settings = $recording_settings['response'];
				} else {
					$recording_settings = false;
				}

				foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {

					// Check recording has id.
					if ( isset( $uuid_recordings_response_recording_file->id ) ) {

						// Get recordings already in system.
						$recording_exists = bp_zoom_webinar_recording_get(
							array(),
							array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'webinar_id'   => $webinar_id,
								'uuid'         => $uuid_recordings_response->uuid,
							)
						);

						$args = array(
							'recording_id' => $uuid_recordings_response_recording_file->id,
							'webinar_id'   => $webinar_id,
							'uuid'         => $uuid_recordings_response->uuid,
							'details'      => $uuid_recordings_response_recording_file,
							'file_type'    => $uuid_recordings_response_recording_file->file_type,
							'start_time'   => $uuid_recordings_response->start_time,
						);

						if ( ! empty( $recording_settings->password ) ) {
							$args['password'] = $recording_settings->password;
						}

						// Recording does exists.
						if ( ! empty( $recording_exists ) ) {

							// Update recording in system.
							bp_zoom_webinar_recording_update( $args, array( 'id' => $recording_exists[0]->id ) );
						} else {

							// Add recording in system.
							bp_zoom_webinar_recording_add( $args );
						}
					}
				}
			}
		}
	}
}

/**
 * Get zoom recording rewrited URL.
 *
 * @param string $original_url Recording URL.
 * @param int    $id           Recording ID.
 * @param bool   $download     Download URL or not.
 *
 * @return string Rewrited URL or Original URL.
 */
function bp_zoom_get_webinar_recording_rewrite_url( $original_url, $id, $download = false ) {
	global $bp_zoom_webinar_block;

	// Check if zoom hide urls enabled or not.
	if ( ! bb_zoom_is_meeting_hide_urls_enabled() ) {
		return $original_url;
	}

	// get recording data.
	$recordings = bp_zoom_webinar_recording_get( array(), array( 'id' => $id ) );

	// check if exists in the system and has webinar id.
	if ( ! empty( $recordings[0]->webinar_id ) ) {
		$block = filter_input( INPUT_GET, 'block', FILTER_VALIDATE_INT );

		// check if on any post, page or cpt single page.
		if ( ! empty( $bp_zoom_webinar_block ) || 1 <= $block ) {
			$post_id = get_the_ID();
			if ( 1 <= $block ) {
				$post_id = $block;
			}
			$recording_url = get_permalink( $post_id ) . '?zoom-recording=' . $id;

			if ( $download ) {
				$recording_url .= '&download=1';
			}

			return $recording_url;
		}

		// get webinar data.
		$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $recordings[0]->webinar_id );

		// check webinar exists and has group assigned.
		if ( ! empty( $webinar->id ) && bp_is_active( 'groups' ) && $webinar->group_id ) {

			// get group data.
			$group = groups_get_group( $webinar->group_id );

			// check group empty or exits.
			if ( ! empty( $group ) ) {
				$group_link = bp_get_group_permalink( $group );

				$recording_url = trailingslashit( $group_link . 'zoom/webinars/' . $webinar->id ) . '?zoom-recording=' . $id;

				if ( $download ) {
					$recording_url .= '&download=1';
				}

				return $recording_url;
			}
		}
	}

	return $original_url;
}
