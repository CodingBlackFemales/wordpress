<?php
/**
 * Zoom integration actions
 *
 * @package BuddyBoss\Zoom
 * @since   1.0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bbp_pro_update_to_1_0_4', 'bp_zoom_pro_update_to_1_0_4' );
add_action( 'bbp_pro_update_to_1_0_7', 'bp_zoom_pro_update_to_1_0_7' );
add_action( 'bbp_pro_update_to_1_0_9', 'bp_zoom_pro_update_to_1_0_9' );
add_action( 'bp_init', 'bp_zoom_pro_has_access_meeting_web', 10 );
add_action( 'bp_init', 'bb_zoom_notification_registered' );
add_action( 'bp_template_redirect', 'bp_zoom_pro_has_access_recording_url', 999999 );

add_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_after_save_update_meeting_data', 1 );
add_action( 'bp_zoom_webinar_after_save', 'bp_zoom_webinar_after_save_update_webinar_data', 1 );

add_action( 'bp_groups_includes', 'bb_load_pro_groups_notifications', 21 );
add_action( 'updated_user_meta', 'bb_zoom_migrate_preferences', 10, 4 );

add_action( 'bbp_pro_update_to_2_1_5', 'bb_zoom_pro_update_to_2_1_5' );

// Zoom dismiss the site-wide notice.
add_action( 'wp_ajax_zoom_dismiss_notice', 'bb_pro_zoom_dismiss_sitewide_notice' );

/**
 * BuddyBoss Pro zoom update to 1.0.4
 *
 * @since 1.0.4
 */
function bp_zoom_pro_update_to_1_0_4() {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	$zoom_meeting_query = "DELETE FROM {$bp_prefix}bp_zoom_recordings WHERE file_type = 'TIMELINE'";
	$wpdb->query( $zoom_meeting_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * BuddyBoss Pro zoom update to 1.0.7
 *
 * @since 1.0.7
 */
function bp_zoom_pro_update_to_1_0_7() {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	$zoom_meeting_query = "UPDATE {$bp_prefix}bp_zoom_meetings SET hide_sitewide = 1 WHERE parent = 0 AND zoom_type = 'meeting' AND type = 8 AND recurring = 1;";
	$wpdb->query( $zoom_meeting_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * BuddyBoss Pro zoom update to 1.0.9
 *
 * @since 1.0.9
 */
function bp_zoom_pro_update_to_1_0_9() {
	// Install new tables for webinars.
	bp_zoom_pro_core_install_zoom_integration();

	// Setup webinar for site integration settings.
	bp_zoom_pro_setup_webinar_integration();

	// Get meta for duplicate emails.
	$update_completed = bp_get_option( 'bp-zoom-pro-1-0-9-update-completed' );

	if ( ! empty( $update_completed ) ) {
		return;
	}

	// Update meta for duplicate emails.
	bp_update_option( 'bp-zoom-pro-1-0-9-update-completed', true );

	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = array();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}
}

/**
 * Check user access to singular posts if recording url hit, otherwise returns 404.
 *
 * @since 1.0.8
 */
function bp_zoom_pro_has_access_recording_url() {
	$recording_id = filter_input( INPUT_GET, 'zoom-recording', FILTER_VALIDATE_INT );

	if ( is_singular() && ! empty( $recording_id ) && ! bp_is_group() ) {

		if ( ! apply_filters( 'bp_zoom_pro_has_access_recording_url', current_user_can( 'read', get_the_ID() ) ) ) {
			bp_do_404();

			return;
		}

		// get recording data.
		$recordings = bp_zoom_recording_get( array(), array( 'id' => $recording_id ) );

		// check if exists in the system and has meeting id.
		if ( empty( $recordings[0]->meeting_id ) ) {
			bp_do_404();

			return;
		}

		$recording_file = json_decode( $recordings[0]->details );

		$download_url = filter_input( INPUT_GET, 'download', FILTER_VALIDATE_INT );

		// download url if download option true.
		if ( ! empty( $recording_file->download_url ) && ! empty( $download_url ) && 1 === $download_url ) {
			wp_redirect( $recording_file->download_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		if ( ! empty( $recording_file->play_url ) ) {
			wp_redirect( $recording_file->play_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		bp_do_404();

		return;
	}
}

/**
 * Zoom web meeting start div element to footer.
 *
 * @since 1.0.8
 */
function bp_zoom_pro_has_access_meeting_web() {
	$zoom_web_meeting = filter_input( INPUT_GET, 'wm', FILTER_VALIDATE_INT );
	$meeting_id       = bb_pro_filter_input_string( INPUT_GET, 'mi' );

	if ( ! empty( $meeting_id ) && 1 === $zoom_web_meeting ) {

		// Check for block.
		if ( is_singular() && ! bp_is_group() && apply_filters( 'bp_zoom_pro_start_meeting_web', current_user_can( 'read', get_the_ID() ) ) ) {
			add_action( 'wp_footer', 'bp_zoom_pro_add_zoom_web_meeting_append_div' );
		}
	}
}

/**
 * Load look to register zoom notification.
 *
 * @since 1.2.1
 */
function bb_zoom_notification_registered() {
	if (
		! function_exists( 'bb_enabled_legacy_email_preference' ) ||
		(
			function_exists( 'bb_enabled_legacy_email_preference' ) &&
			true === bb_enabled_legacy_email_preference()
		)
	) {
		add_action( 'groups_screen_notification_settings', 'bp_zoom_groups_screen_notification_settings', 10 );
	}
}

/**
 * Add zoom meeting scheduled notifications settings to the notifications settings page.
 *
 * @since 1.0.9
 */
function bp_zoom_groups_screen_notification_settings() {

	$zoom_meeting_scheduled = bp_get_user_meta( bp_displayed_user_id(), 'notification_zoom_meeting_scheduled', true );
	$zoom_webinar_scheduled = bp_get_user_meta( bp_displayed_user_id(), 'notification_zoom_webinar_scheduled', true );

	if ( ! $zoom_meeting_scheduled ) {
		$zoom_meeting_scheduled = 'yes';
	}

	if ( ! $zoom_webinar_scheduled ) {
		$zoom_webinar_scheduled = 'yes';
	}

	?>
	<tr id="groups-notification-settings-zoom-meeting-scheduled">
		<td></td>
		<td><?php esc_html_e( 'A Zoom meeting is scheduled in a group', 'buddyboss-pro' ); ?></td>
		<td class="yes">
			<div class="bp-radio-wrap">
				<input type="radio" name="notifications[notification_zoom_meeting_scheduled]"  id="notification-zoom-meeting-scheduled-yes" class="bs-styled-radio" value="yes" <?php checked( $zoom_meeting_scheduled, 'yes', true ); ?> />
				<label for="notification-zoom-meeting-scheduled-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss-pro' ); ?></span></label>
			</div>
		</td>
		<td class="no">
			<div class="bp-radio-wrap">
				<input type="radio" name="notifications[notification_zoom_meeting_scheduled]" id="notification-zoom-meeting-scheduled-no" class="bs-styled-radio" value="no" <?php checked( $zoom_meeting_scheduled, 'no', true ); ?> />
				<label for="notification-zoom-meeting-scheduled-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss-pro' ); ?></span></label>
			</div>
		</td>
	</tr>
	<tr id="groups-notification-settings-zoom-webinar-scheduled">
		<td></td>
		<td><?php esc_html_e( 'A Zoom webinar is scheduled in a group', 'buddyboss-pro' ); ?></td>
		<td class="yes">
			<div class="bp-radio-wrap">
				<input type="radio" name="notifications[notification_zoom_webinar_scheduled]"  id="notification-zoom-webinar-scheduled-yes" class="bs-styled-radio" value="yes" <?php checked( $zoom_webinar_scheduled, 'yes', true ); ?> />
				<label for="notification-zoom-webinar-scheduled-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss-pro' ); ?></span></label>
			</div>
		</td>
		<td class="no">
			<div class="bp-radio-wrap">
				<input type="radio" name="notifications[notification_zoom_webinar_scheduled]" id="notification-zoom-webinar-scheduled-no" class="bs-styled-radio" value="no" <?php checked( $zoom_webinar_scheduled, 'no', true ); ?> />
				<label for="notification-zoom-webinar-scheduled-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss-pro' ); ?></span></label>
			</div>
		</td>
	</tr>
	<?php
}

/**
 * Update meeting data after save.
 *
 * @since 1.0.9
 *
 * @param BP_Zoom_Meeting $meeting Current instance of meeting item being saved. Passed by reference.
 */
function bp_zoom_meeting_after_save_update_meeting_data( $meeting ) {

	if ( 'meeting' !== $meeting->zoom_type ) {
		return;
	}

	// Connect to Zoom.
	bb_zoom_group_connect_api( $meeting->group_id );

	$zoom_meeting = bp_zoom_conference()->get_meeting_info( $meeting->meeting_id, false, true );

	if ( 404 === $zoom_meeting['code'] && ! empty( $zoom_meeting['response'] ) && isset( $zoom_meeting['response']->code ) && 3001 === $zoom_meeting['response']->code ) {
		bp_zoom_meeting_delete( array( 'parent' => $meeting->meeting_id ) );
		bp_zoom_recording_delete( array( 'meeting_id' => $meeting->meeting_id ) );
		bp_zoom_meeting_delete( array( 'id' => $meeting->id ) );

		return;
	}

	if ( empty( $zoom_meeting['code'] ) || 200 !== $zoom_meeting['code'] || empty( $zoom_meeting['response'] ) ) {
		return;
	}

	$object = json_decode( wp_json_encode( $zoom_meeting['response'] ), true );

	remove_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_after_save_update_meeting_data', 1 );

	$zoom_meeting_id = $meeting->meeting_id;

	if ( isset( $object['topic'] ) ) {
		$meeting->title = $object['topic'];
	}

	if ( isset( $object['timezone'] ) ) {
		$meeting->timezone = $object['timezone'];
	}

	if ( isset( $object['start_time'] ) ) {
		$meeting->start_date_utc = $object['start_time'];
		$meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting->start_date_utc ), new DateTimeZone( $meeting->timezone ) );
	} elseif ( isset( $object['created_at'] ) ) {
		$meeting->start_date_utc = $object['created_at'];
		$meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting->start_date_utc ), new DateTimeZone( $meeting->timezone ) );
	}

	if ( isset( $object['duration'] ) ) {
		$meeting->duration = (int) $object['duration'];
	}

	if ( isset( $object['agenda'] ) ) {
		$meeting->description = $object['agenda'];
	}

	bp_zoom_meeting_update_meta( $meeting->id, 'zoom_details', wp_json_encode( $zoom_meeting['response'] ) );

	if ( isset( $object['start_url'] ) ) {
		bp_zoom_meeting_update_meta( $meeting->id, 'zoom_start_url', $object['start_url'] );
	}

	if ( isset( $object['join_url'] ) ) {
		bp_zoom_meeting_update_meta( $meeting->id, 'zoom_join_url', $object['join_url'] );
	}

	delete_transient( 'bp_zoom_meeting_invitation_' . $zoom_meeting_id );

	if ( isset( $object['password'] ) ) {
		$meeting->password = $object['password'];
	}

	if ( isset( $object['type'] ) ) {
		$meeting->type = $object['type'];
	}

	if ( isset( $object['settings'] ) ) {
		$settings = $object['settings'];

		if ( isset( $settings['host_video'] ) ) {
			$meeting->host_video = (bool) $settings['host_video'];
		}

		if ( isset( $settings['participant_video'] ) ) {
			$meeting->participants_video = (bool) $settings['participant_video'];
		}

		if ( isset( $settings['join_before_host'] ) ) {
			$meeting->join_before_host = (bool) $settings['join_before_host'];
		}

		if ( isset( $settings['mute_upon_entry'] ) ) {
			$meeting->mute_participants = (bool) $settings['mute_upon_entry'];
		}

		if ( isset( $settings['approval_type'] ) ) {
			$approval_type = (int) $settings['approval_type'];

			if (
				in_array(
					$approval_type,
					array(
						0,
						1,
					),
					true
				) && isset( $object['registration_url'] ) && ! empty( $object['registration_url'] ) ) {
				bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_url', $object['registration_url'] );
			} else {
				bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_url' );
			}
		}

		if ( 8 === $object['type'] && isset( $settings['registration_type'] ) ) {
			bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_type', $settings['registration_type'] );
		} else {
			bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_type' );
		}

		if ( isset( $settings['auto_recording'] ) ) {
			$meeting->auto_recording = $settings['auto_recording'];
		}

		if ( isset( $settings['alternative_hosts'] ) ) {
			$meeting->alternative_host_ids = $settings['alternative_hosts'];
		}

		if ( isset( $settings['waiting_room'] ) ) {
			$meeting->waiting_room = (bool) $settings['waiting_room'];
		}

		if ( isset( $settings['meeting_authentication'] ) ) {
			$meeting->meeting_authentication = (bool) $settings['meeting_authentication'];
		}
	}

	$data = array(
		'title'                  => $meeting->title,
		'type'                   => $meeting->type,
		'description'            => $meeting->description,
		'group_id'               => $meeting->group_id,
		'user_id'                => $meeting->user_id,
		'host_id'                => $meeting->host_id,
		'timezone'               => $meeting->timezone,
		'meeting_authentication' => $meeting->meeting_authentication,
		'password'               => $meeting->password,
		'join_before_host'       => $meeting->join_before_host,
		'host_video'             => $meeting->host_video,
		'participants_video'     => $meeting->participants_video,
		'mute_participants'      => $meeting->mute_participants,
		'waiting_room'           => $meeting->waiting_room,
		'auto_recording'         => $meeting->auto_recording,
		'alternative_host_ids'   => $meeting->alternative_host_ids,
		'alert'                  => $meeting->alert,
	);

	if ( 8 === $zoom_meeting['response']->type && ! empty( $zoom_meeting['response']->occurrences ) ) {
		$meeting->hide_sitewide = 1;
		$meeting->recurring     = 1;
		if ( ! empty( $zoom_meeting['response']->occurrences ) ) {

			$occurrence_add = 0;
			$occurrence_id  = false;
			foreach ( $zoom_meeting['response']->occurrences as $meeting_occurrence ) {
				if ( isset( $data['id'] ) ) {
					unset( $data['id'] );
				}

				// Get current occurrence if available.
				$occurrence = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_occurrence->occurrence_id, $zoom_meeting['response']->id );

				if ( ! empty( $occurrence->id ) ) {

					// delete occurrence.
					if ( 'deleted' === $meeting_occurrence->status ) {
						bp_zoom_meeting_delete( array( 'id' => $occurrence->id ) );
						continue;
					}

					$data['id'] = $occurrence->id;
				}

				$meeting_occurrence_info = bp_zoom_conference()->get_meeting_info( $zoom_meeting['response']->id, $meeting_occurrence->occurrence_id );
				if ( 200 === $meeting_occurrence_info['code'] && ! empty( $meeting_occurrence_info['response'] ) ) {
					$data['title']                  = $meeting_occurrence_info['response']->topic;
					$data['type']                   = $meeting_occurrence_info['response']->type;
					$data['description']            = $meeting_occurrence_info['response']->agenda;
					$data['meeting_authentication'] = $meeting_occurrence_info['response']->settings->meeting_authentication;
					$data['join_before_host']       = $meeting_occurrence_info['response']->settings->join_before_host;
					$data['host_video']             = $meeting_occurrence_info['response']->settings->host_video;
					$data['participants_video']     = $meeting_occurrence_info['response']->settings->participant_video;
					$data['mute_participants']      = $meeting_occurrence_info['response']->settings->mute_upon_entry;
					$data['waiting_room']           = $meeting_occurrence_info['response']->settings->waiting_room;
					$data['auto_recording']         = $meeting_occurrence_info['response']->settings->auto_recording;
					$data['alternative_host_ids']   = $meeting_occurrence_info['response']->settings->alternative_hosts;
				}

				$data['hide_sitewide']  = false;
				$data['meeting_id']     = $meeting_occurrence->occurrence_id;
				$data['duration']       = $meeting_occurrence->duration;
				$data['parent']         = $zoom_meeting['response']->id;
				$data['zoom_type']      = 'meeting_occurrence';
				$data['start_date_utc'] = $meeting_occurrence->start_time;
				$data['recurring']      = false;
				$occurrence_added_id    = bp_zoom_meeting_add( $data );

				if ( false === $occurrence_id ) {
					$meeting_occurrence->start_time = str_replace( 'T', ' ', $meeting_occurrence->start_time );
					$occurrence_date                = new DateTime( $meeting_occurrence->start_time, new DateTimeZone( 'UTC' ) );
					$current_date                   = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
					if ( $occurrence_date->format( 'U' ) > $current_date->format( 'U' ) ) {
						$occurrence_id = $occurrence_added_id;
						bp_zoom_meeting_update_meta( $meeting->id, 'zoom_meeting_occurrence_id', $occurrence_added_id );
					}
				}

				$occurrence_add ++;
			}

			// Get occurrences from system.
			$occurrences = bp_zoom_meeting_get( array( 'parent' => $zoom_meeting_id ) );

			if ( ! empty( $occurrences['meetings'] ) ) {
				$occurrence_ids     = wp_list_pluck( $occurrences['meetings'], 'meeting_id' );
				$api_occurrence_ids = wp_list_pluck( $zoom_meeting['response']->occurrences, 'occurrence_id' );

				// Delete occurrences which are not in zoom and exists in system.
				$to_delete_occurrences = array_diff( $occurrence_ids, $api_occurrence_ids );

				if ( ! empty( $to_delete_occurrences ) ) {
					foreach ( $to_delete_occurrences as $to_delete_occurrence ) {
						bp_zoom_meeting_delete( array( 'meeting_id' => $to_delete_occurrence ) );
					}
				}
			}
		} else {
			// delete current occurrences and store new ones from zoom api.
			bp_zoom_meeting_delete( array( 'parent' => $meeting->meeting_id ) );
		}
	} else {
		$meeting->hide_sitewide = 0;
		$meeting->recurring     = 0;
		// delete all occurrences of the meeting and then start fresh.
		bp_zoom_meeting_delete( array( 'parent' => $meeting->meeting_id ) );
	}

	$meeting->save();

	add_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_after_save_update_meeting_data', 1 );
}

/**
 * Update webinar data after save.
 *
 * @since 1.0.9
 *
 * @param BP_Zoom_Webinar $webinar Current instance of webinar item being saved. Passed by reference.
 */
function bp_zoom_webinar_after_save_update_webinar_data( $webinar ) {

	if ( 'webinar' !== $webinar->zoom_type ) {
		return;
	}

	// Connect to Zoom.
	bb_zoom_group_connect_api( $webinar->group_id );

	$zoom_webinar = bp_zoom_conference()->get_webinar_info( $webinar->webinar_id, false, true );

	if ( 404 === $zoom_webinar['code'] && ! empty( $zoom_webinar['response'] ) && isset( $zoom_webinar['response']->code ) && 3001 === $zoom_webinar['response']->code ) {
		bp_zoom_webinar_delete( array( 'parent' => $webinar->webinar_id ) );
		bp_zoom_webinar_recording_delete( array( 'webinar_id' => $webinar->webinar_id ) );
		bp_zoom_webinar_delete( array( 'id' => $webinar->id ) );

		return;
	}

	if ( empty( $zoom_webinar['code'] ) || 200 !== $zoom_webinar['code'] || empty( $zoom_webinar['response'] ) ) {
		return;
	}

	$object = json_decode( wp_json_encode( $zoom_webinar['response'] ), true );

	remove_action( 'bp_zoom_webinar_after_save', 'bp_zoom_webinar_after_save_update_webinar_data', 1 );

	if ( isset( $object['topic'] ) ) {
		$webinar->title = $object['topic'];
	}

	if ( isset( $object['timezone'] ) ) {
		$webinar->timezone = $object['timezone'];
	}

	if ( isset( $object['start_time'] ) ) {
		$webinar->start_date_utc = $object['start_time'];
		$webinar->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $webinar->start_date_utc ), new DateTimeZone( $webinar->timezone ) );
	} elseif ( isset( $object['created_at'] ) ) {
		$webinar->start_date_utc = $object['created_at'];
		$webinar->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $webinar->start_date_utc ), new DateTimeZone( $webinar->timezone ) );
	}

	if ( isset( $object['duration'] ) ) {
		$webinar->duration = (int) $object['duration'];
	}

	if ( isset( $object['agenda'] ) ) {
		$webinar->description = $object['agenda'];
	}

	bp_zoom_webinar_update_meta( $webinar->id, 'zoom_details', wp_json_encode( $zoom_webinar['response'] ) );

	if ( isset( $object['start_url'] ) ) {
		bp_zoom_webinar_update_meta( $webinar->id, 'zoom_start_url', $object['start_url'] );
	}

	if ( isset( $object['join_url'] ) ) {
		bp_zoom_webinar_update_meta( $webinar->id, 'zoom_join_url', $object['join_url'] );
	}

	if ( isset( $object['password'] ) ) {
		$webinar->password = $object['password'];
	}

	if ( isset( $object['type'] ) ) {
		$webinar->type = $object['type'];
	}

	if ( isset( $object['settings'] ) ) {
		$settings = $object['settings'];

		if ( isset( $settings['host_video'] ) ) {
			$webinar->host_video = (bool) $settings['host_video'];
		}

		if ( isset( $settings['panelists_video'] ) ) {
			$webinar->panelists_video = (bool) $settings['panelists_video'];
		}

		if ( isset( $settings['practice_session'] ) ) {
			$webinar->practice_session = (bool) $settings['practice_session'];
		}

		if ( isset( $settings['on_demand'] ) ) {
			$webinar->on_demand = (bool) $settings['on_demand'];
		}

		if ( isset( $settings['approval_type'] ) ) {
			$approval_type = (int) $settings['approval_type'];

			if (
				in_array(
					$approval_type,
					array(
						0,
						1,
					),
					true
				) && isset( $object['registration_url'] ) && ! empty( $object['registration_url'] ) ) {
				bp_zoom_webinar_update_meta( $webinar->id, 'zoom_registration_url', $object['registration_url'] );
			} else {
				bp_zoom_webinar_delete_meta( $webinar->id, 'zoom_registration_url' );
			}
		}

		if ( 9 === $object['type'] && isset( $settings['registration_type'] ) ) {
			bp_zoom_webinar_update_meta( $webinar->id, 'zoom_registration_type', $settings['registration_type'] );
		} else {
			bp_zoom_webinar_delete_meta( $webinar->id, 'zoom_registration_type' );
		}

		if ( isset( $settings['auto_recording'] ) ) {
			$webinar->auto_recording = $settings['auto_recording'];
		}

		if ( isset( $settings['alternative_hosts'] ) ) {
			$webinar->alternative_host_ids = $settings['alternative_hosts'];
		}

		if ( isset( $settings['meeting_authentication'] ) ) {
			$webinar->meeting_authentication = (bool) $settings['meeting_authentication'];
		}
	}

	$data = array(
		'title'                  => $webinar->title,
		'type'                   => $webinar->type,
		'description'            => $webinar->description,
		'group_id'               => $webinar->group_id,
		'user_id'                => $webinar->user_id,
		'host_id'                => $webinar->host_id,
		'timezone'               => $webinar->timezone,
		'meeting_authentication' => $webinar->meeting_authentication,
		'password'               => $webinar->password,
		'host_video'             => $webinar->host_video,
		'panelists_video'        => $webinar->panelists_video,
		'practice_session'       => $webinar->practice_session,
		'on_demand'              => $webinar->on_demand,
		'auto_recording'         => $webinar->auto_recording,
		'alternative_host_ids'   => $webinar->alternative_host_ids,
		'alert'                  => $webinar->alert,
	);

	if ( 9 === $zoom_webinar['response']->type && ! empty( $zoom_webinar['response']->occurrences ) ) {
		$webinar->hide_sitewide = 1;
		$webinar->recurring     = 1;
		if ( ! empty( $zoom_webinar['response']->occurrences ) ) {

			$occurrence_add = 0;
			$occurrence_id  = false;
			foreach ( $zoom_webinar['response']->occurrences as $webinar_occurrence ) {
				if ( isset( $data['id'] ) ) {
					unset( $data['id'] );
				}

				// Get current occurrence if available.
				$occurrence = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_occurrence->occurrence_id, $zoom_webinar['response']->id );

				if ( ! empty( $occurrence->id ) ) {

					// delete occurrence.
					if ( 'deleted' === $webinar_occurrence->status ) {
						bp_zoom_webinar_delete( array( 'id' => $occurrence->id ) );
						continue;
					}

					$data['id'] = $occurrence->id;
				}

				$webinar_occurrence_info = bp_zoom_conference()->get_webinar_info( $zoom_webinar['response']->id, $webinar_occurrence->occurrence_id );
				if ( 200 === $webinar_occurrence_info['code'] && ! empty( $webinar_occurrence_info['response'] ) ) {
					$data['title']                  = $webinar_occurrence_info['response']->topic;
					$data['type']                   = $webinar_occurrence_info['response']->type;
					$data['description']            = $webinar_occurrence_info['response']->agenda;
					$data['webinar_authentication'] = $webinar_occurrence_info['response']->settings->meeting_authentication;
					$data['host_video']             = $webinar_occurrence_info['response']->settings->host_video;
					$data['panelists_video']        = $webinar_occurrence_info['response']->settings->panelists_video;
					$data['practice_session']       = $webinar_occurrence_info['response']->settings->practice_session;
					$data['on_demand']              = $webinar_occurrence_info['response']->settings->on_demand;
					$data['auto_recording']         = $webinar_occurrence_info['response']->settings->auto_recording;
					$data['alternative_host_ids']   = $webinar_occurrence_info['response']->settings->alternative_hosts;
				}

				$data['hide_sitewide']  = false;
				$data['webinar_id']     = $webinar_occurrence->occurrence_id;
				$data['duration']       = $webinar_occurrence->duration;
				$data['parent']         = $zoom_webinar['response']->id;
				$data['zoom_type']      = 'webinar_occurrence';
				$data['start_date_utc'] = $webinar_occurrence->start_time;
				$data['recurring']      = false;
				$occurrence_added_id    = bp_zoom_webinar_add( $data );

				if ( false === $occurrence_id ) {
					$webinar_occurrence->start_time = str_replace( 'T', ' ', $webinar_occurrence->start_time );
					$occurrence_date                = new DateTime( $webinar_occurrence->start_time, new DateTimeZone( 'UTC' ) );
					$current_date                   = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
					if ( $occurrence_date->format( 'U' ) > $current_date->format( 'U' ) ) {
						$occurrence_id = $occurrence_added_id;
						bp_zoom_webinar_update_meta( $webinar->id, 'zoom_webinar_occurrence_id', $occurrence_added_id );
					}
				}

				$occurrence_add ++;
			}

			// Get occurrences from system.
			$occurrences = bp_zoom_webinar_get( array( 'parent' => $webinar->webinar_id ) );

			if ( ! empty( $occurrences['webinars'] ) ) {
				$occurrence_ids     = wp_list_pluck( $occurrences['webinars'], 'webinar_id' );
				$api_occurrence_ids = wp_list_pluck( $zoom_webinar['response']->occurrences, 'occurrence_id' );

				// Delete occurrences which are not in zoom and exists in system.
				$to_delete_occurrences = array_diff( $occurrence_ids, $api_occurrence_ids );

				if ( ! empty( $to_delete_occurrences ) ) {
					foreach ( $to_delete_occurrences as $to_delete_occurrence ) {
						bp_zoom_webinar_delete( array( 'webinar_id' => $to_delete_occurrence ) );
					}
				}
			}
		} else {
			// delete current occurrences and store new ones from zoom api.
			bp_zoom_webinar_delete( array( 'parent' => $webinar->webinar_id ) );
		}
	} else {
		$webinar->hide_sitewide = 0;
		$webinar->recurring     = 0;
		// delete all occurrences of the webinar and then start fresh.
		bp_zoom_webinar_delete( array( 'parent' => $webinar->webinar_id ) );
	}

	$webinar->save();

	add_action( 'bp_zoom_webinar_after_save', 'bp_zoom_webinar_after_save_update_webinar_data', 1 );
}

/**
 * Setup webinar on plugin update.
 *
 * @since 1.0.9
 *
 * @param string $client_id     Client ID.
 * @param string $client_secret Client Secret.
 * @param string $account_id    Account ID.
 */
function bp_zoom_pro_setup_webinar_integration( $account_id = '', $client_id = '', $client_secret = '' ) {
	bp_zoom_conference()->zoom_api_account_id    = empty( $account_id ) ? bb_zoom_account_id() : $account_id;
	bp_zoom_conference()->zoom_api_client_id     = empty( $client_id ) ? bb_zoom_client_id() : $client_id;
	bp_zoom_conference()->zoom_api_client_secret = empty( $client_secret ) ? bb_zoom_client_secret() : $client_secret;

	$email = bb_zoom_account_email();

	if ( ! empty( $email ) ) {
		$user_info = bp_zoom_conference()->get_user_info( $email );

		if ( 200 === $user_info['code'] ) {
			$settings = bb_get_zoom_block_settings();

			$settings['account_host_user'] = $user_info['response'];

			// Get user settings of host user.
			$user_settings = bp_zoom_conference()->get_user_settings( $user_info['response']->id );

			// Save user settings into group meta.
			if ( 200 === $user_settings['code'] && ! empty( $user_settings['response'] ) ) {
				$settings['account_host_user_settings'] = $user_settings['response'];

				if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
					bp_update_option( 'bp-zoom-enable-webinar', true );
				} else {
					bp_delete_option( 'bp-zoom-enable-webinar' );
				}
			} else {
				$settings['account_host_user']          = array();
				$settings['account_host_user_settings'] = array();
			}

			bp_update_option( 'bb-zoom', $settings );
		}
	}
}

/**
 * Register the zoom group notifications.
 *
 * @since 1.2.1
 */
function bb_load_pro_groups_notifications() {
	if ( class_exists( 'BP_Core_Notification_Abstract' ) && class_exists( 'BP_Zoom_Groups_Notification' ) ) {
		BP_Zoom_Groups_Notification::instance();
	}
}

/**
 * Migrate Zoom preferences while legacy to modern.
 *
 * @since 1.2.1
 *
 * @param int    $meta_id    ID of updated metadata entry.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 *
 * @return void
 */
function bb_zoom_migrate_preferences( $meta_id, $object_id, $meta_key, $meta_value ) {

	if ( empty( $meta_key ) ) {
		return;
	}

	switch ( $meta_key ) {
		case 'notification_zoom_meeting_scheduled':
			$webinar_data = get_user_meta( $object_id, 'notification_zoom_webinar_scheduled', true );

			if ( 'no' === $webinar_data && 'no' === $meta_value ) {
				update_user_meta( $object_id, 'bb_groups_new_zoom', 'no' );
			} else {
				update_user_meta( $object_id, 'bb_groups_new_zoom', 'yes' );
			}

			break;

		case 'notification_zoom_webinar_scheduled':
			$meeting_data = get_user_meta( $object_id, 'notification_zoom_meeting_scheduled', true );

			if ( 'no' === $meeting_data && 'no' === $meta_value ) {
				update_user_meta( $object_id, 'bb_groups_new_zoom', 'no' );
			} else {
				update_user_meta( $object_id, 'bb_groups_new_zoom', 'yes' );
			}

			break;

		case 'bb_groups_new_zoom':
			update_user_meta( $object_id, 'notification_zoom_meeting_scheduled', $meta_value );
			update_user_meta( $object_id, 'notification_zoom_webinar_scheduled', $meta_value );

			break;
	}
}

/**
 * BuddyBoss Pro zoom update to 2.1.8
 *
 * @since 2.1.8
 */
function bb_zoom_pro_update_to_2_1_5() {
	global $wpdb;

    // phpcs:ignore
    $meeting_start_date_exists = $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}bp_zoom_meetings LIKE 'start_date'" );
	if ( ! is_null( $meeting_start_date_exists ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}bp_zoom_meetings DROP COLUMN `start_date`" ); // phpcs:ignore
	}

    // phpcs:ignore
	$webinar_start_date_exists = $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}bp_zoom_webinars LIKE 'start_date'" );
	if ( ! is_null( $webinar_start_date_exists ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}bp_zoom_webinars DROP COLUMN `start_date`" ); // phpcs:ignore
	}
}

/**
 * Hide site-wise notice.
 *
 * @since 2.3.91
 *
 * @return void
 */
function bb_pro_zoom_dismiss_sitewide_notice() {
	$wp_nonce = bb_pro_filter_input_string( INPUT_POST, 'nonce' );

	// Nonce check!
	if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bb-pro-zoom-dismiss-notice' ) ) {
		wp_send_json_error( array( 'error' => __( 'Sorry, something goes wrong please try again.', 'buddyboss-pro' ) ) );
	}

	$settings = bb_get_zoom_block_settings();
	if (
		! empty( $settings['sidewide_errors'] ) &&
		is_array( $settings['sidewide_errors'] ) &&
		in_array( 'upgrade_jwt_to_s2s', $settings['sidewide_errors'], true )
	) {
		$key = array_search( 'upgrade_jwt_to_s2s', $settings['sidewide_errors'], true );
		unset( $settings['sidewide_errors'][ $key ] );
	}

	bp_update_option( 'bb-zoom', $settings );

	wp_send_json_success(
		array(
			'success' => true,
		)
	);
}
