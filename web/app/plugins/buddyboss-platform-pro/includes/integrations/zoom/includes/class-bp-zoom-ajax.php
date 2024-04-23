<?php
/**
 * BuddyBoss Zoom AJAX.
 *
 * @since   1.0.0
 * @package BuddyBoss\Zoom\Ajax
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Zoom_Ajax' ) ) {
	/**
	 * Class BP_Zoom_Ajax
	 */
	class BP_Zoom_Ajax {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( ! bbp_pro_is_license_valid() ) {
				return;
			}
			$this->setup_filters();
			$this->setup_actions();
		}

		/**
		 * Setup the group zoom class filters
		 *
		 * @since 1.0.0
		 */
		private function setup_filters() {
		}

		/**
		 * Setup actions.
		 *
		 * @since 1.0.0
		 */
		public function setup_actions() {
			add_action( 'wp_ajax_zoom_meeting_add', array( $this, 'zoom_meeting_add' ) );
			add_action( 'wp_ajax_zoom_meeting_delete', array( $this, 'zoom_meeting_delete' ) );
			add_action( 'wp_ajax_zoom_meeting_create_meeting', array( $this, 'zoom_meeting_create_meeting' ) );
			add_action( 'wp_ajax_zoom_meeting_edit_meeting', array( $this, 'zoom_meeting_edit_meeting' ) );
			add_action( 'wp_ajax_zoom_meeting_occurrence_edit', array( $this, 'zoom_meeting_occurrence_edit' ) );
			add_action( 'wp_ajax_zoom_meetings_sync', array( $this, 'zoom_meetings_sync' ) );

			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
			add_action( 'wp_ajax_nopriv_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
			add_action( 'wp_ajax_zoom_meeting_invitation', array( $this, 'zoom_meeting_invitation' ) );
			add_action( 'wp_ajax_zoom_meeting_load_more', array( $this, 'zoom_meeting_load_more' ) );
			add_action( 'wp_ajax_nopriv_zoom_meeting_load_more', array( $this, 'zoom_meeting_load_more' ) );
			add_action( 'wp_ajax_zoom_meeting_search', array( $this, 'zoom_meeting_search' ) );
			add_action( 'wp_ajax_nopriv_zoom_meeting_search', array( $this, 'zoom_meeting_search' ) );
			add_action( 'wp_ajax_zoom_meeting_get_single_meeting', array( $this, 'zoom_meeting_get_single_meeting' ) );
			add_action(
				'wp_ajax_nopriv_zoom_meeting_get_single_meeting',
				array(
					$this,
					'zoom_meeting_get_single_meeting',
				)
			);

			// Webinars.
			add_action( 'wp_ajax_zoom_webinar_add', array( $this, 'zoom_webinar_add' ) );
			add_action( 'wp_ajax_zoom_webinar_delete', array( $this, 'zoom_webinar_delete' ) );

			add_action( 'wp_ajax_zoom_webinar_load_more', array( $this, 'zoom_webinar_load_more' ) );
			add_action( 'wp_ajax_nopriv_zoom_webinar_load_more', array( $this, 'zoom_webinar_load_more' ) );

			add_action( 'wp_ajax_zoom_webinar_get_single_webinar', array( $this, 'zoom_webinar_get_single_webinar' ) );
			add_action(
				'wp_ajax_nopriv_zoom_webinar_get_single_webinar',
				array(
					$this,
					'zoom_webinar_get_single_webinar',
				)
			);

			add_action( 'wp_ajax_zoom_webinar_edit_webinar', array( $this, 'zoom_webinar_edit_webinar' ) );
			add_action( 'wp_ajax_zoom_webinar_occurrence_edit', array( $this, 'zoom_webinar_occurrence_edit' ) );
			add_action( 'wp_ajax_zoom_webinar_create_webinar', array( $this, 'zoom_webinar_create_webinar' ) );

			add_action( 'wp_ajax_zoom_webinar_search', array( $this, 'zoom_webinar_search' ) );
			add_action( 'wp_ajax_nopriv_zoom_webinar_search', array( $this, 'zoom_webinar_search' ) );

			add_action( 'wp_ajax_zoom_webinar_recordings', array( $this, 'zoom_webinar_recordings' ) );
			add_action( 'wp_ajax_nopriv_zoom_webinar_recordings', array( $this, 'zoom_webinar_recordings' ) );
			add_action( 'wp_ajax_zoom_webinars_sync', array( $this, 'zoom_webinars_sync' ) );

			add_action( 'wp_ajax_zoom_api_get_account_emails', array( $this, 'get_zoom_account_emails' ) );
			add_action( 'wp_ajax_zoom_group_update_secret_token', array( $this, 'bb_zoom_group_update_secret_token' ) );
			add_action( 'wp_ajax_zoom_api_submit_group_zoom_credentials', array( $this, 'bb_zoom_group_save_credentials' ) );
		}

		/**
		 * Function handle recurring meeting update.
		 *
		 * @since 1.0.4
		 */
		public function zoom_meeting_occurrence_edit() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
			}

			$group_id = filter_input( INPUT_POST, 'bp-zoom-meeting-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create meeting in this group.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_zoom_is_group_setup( $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
			}

			$host_email = bb_zoom_group_get_email_account( $group_id );
			$host_type  = bb_zoom_group_get_host_type( $group_id );

			// Connect to Zoom.
			bb_zoom_group_connect_api( $group_id );

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$description = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-description' );

			$start_date = filter_input( INPUT_POST, 'bp-zoom-meeting-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			if ( strtotime( $start_date ) < strtotime( wp_date( 'Y-m-d' ) ) ) {
				wp_send_json_error( array( 'error' => __( 'Please change the meeting date to a future date.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-start-time-meridian' );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$id                    = filter_input( INPUT_POST, 'bp-zoom-meeting-id', FILTER_VALIDATE_INT );
			$occurrence_id         = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-occurrence-id' );
			$meeting_id            = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			$join_before_host      = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video            = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video    = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants     = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room          = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$auto_recording        = filter_input( INPUT_POST, 'bp-zoom-meeting-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$auto_recording_option = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-recording' );

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
			} else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
			}

			$timezone = wp_timezone_string();
			// update parent meeting details.
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
			if ( ! empty( $parent_meeting->id ) ) {
				$timezone = $parent_meeting->timezone;
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			$start_date_time_save = new DateTime( $start_date . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
			$start_date_time_save->setTimeZone( new DateTimeZone( 'UTC' ) );
			$start_date_time_save = $start_date_time_save->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'description'        => $description,
				'start_date'         => $start_date_time,
				'duration'           => $duration,
				'join_before_host'   => $join_before_host,
				'host_video'         => $host_video,
				'participants_video' => $participants_video,
				'mute_participants'  => $mute_participants,
				'waiting_room'       => $waiting_room,
				'auto_recording'     => $auto_recording,
				'meeting_id'         => $meeting_id,
			);

			$zoom_meeting = bp_zoom_conference()->update_meeting_occurrence( $occurrence_id, $data );

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => $zoom_meeting['response']->message );

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_meeting['code'] ) && 204 === $zoom_meeting['code'] ) {
				$meeting                     = new BP_Zoom_Meeting( $id );
				$meeting->description        = $data['description'];
				$meeting->duration           = $data['duration'];
				$meeting->join_before_host   = $data['join_before_host'];
				$meeting->host_video         = $data['host_video'];
				$meeting->participants_video = $data['participants_video'];
				$meeting->mute_participants  = $data['mute_participants'];
				$meeting->waiting_room       = $data['waiting_room'];
				$meeting->auto_recording     = $data['auto_recording'];
				$meeting->start_date_utc     = $start_date_time_save;

				if ( ! $meeting->save() ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the occurrence.', 'buddyboss-pro' ) ) );
				}

				// update parent meeting details.
				if ( ! empty( $parent_meeting->id ) ) {
					$updated_meeting = bp_zoom_conference()->get_meeting_info( $meeting_id, false, true );
					if ( ! empty( $updated_meeting['code'] ) && 200 === $updated_meeting['code'] && ! empty( $updated_meeting['response'] ) ) {
						bp_zoom_meeting_update_meta( $parent_meeting->id, 'zoom_details', wp_json_encode( $updated_meeting['response'] ) );
					}
				}

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $id );

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'meeting_id'   => (int) $meeting_id,
					)
				);
			}

			wp_send_json_error( array( 'error' => __( 'There was a problem when updating an occurrence. Please try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom meeting add.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_add() {
			$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

			if ( ! bp_is_post_request() ) {
				wp_send_json_error( $response_error );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_meeting' ) ) {
				wp_send_json_error( $response_error );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
			}

			$group_id = filter_input( INPUT_POST, 'bp-zoom-meeting-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create meeting in this group.', 'buddyboss-pro' ) ) );
			}

			$host_email = '';
			$host_type  = false;
			if ( ! empty( $group_id ) ) {
				if ( ! bp_zoom_is_group_setup( $group_id ) ) {
					wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
				}

				$host_email = bb_zoom_group_get_email_account( $group_id );
				$host_type  = bb_zoom_group_get_host_type( $group_id );

				// Connect to Zoom.
				bb_zoom_group_connect_api( $group_id );
			}

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$title = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-title' );

			if ( empty( $title ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter the meeting title.', 'buddyboss-pro' ) ) );
			}

			$description = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-description' );
			$timezone    = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-timezone' );
			$start_date  = filter_input( INPUT_POST, 'bp-zoom-meeting-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-start-time-meridian' );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			$recurring  = filter_input( INPUT_POST, 'bp-zoom-meeting-recurring', FILTER_VALIDATE_BOOLEAN );
			$meeting_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			if (
				strtotime( $start_date_time ) < strtotime( wp_date( 'Y-m-d\TH:i:s', strtotime( 'now' ), new DateTimeZone( $timezone ) ) ) &&
				! empty( $meeting_id ) && empty( $recurring )
			) {
				wp_send_json_error( array( 'error' => __( 'Please change the meeting date to a future date.', 'buddyboss-pro' ) ) );
			}

			// If recurring weekly meeting then week day should not be empty.
			if (
				$recurring &&
				2 === filter_input( INPUT_POST, 'bp-zoom-meeting-recurrence', FILTER_VALIDATE_INT ) &&
				empty( filter_input( INPUT_POST, 'bp-zoom-meeting-weekly-days', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) )
			) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting week day.', 'buddyboss-pro' ) ) );
			}

			$password = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-password' );

			$id                     = filter_input( INPUT_POST, 'bp-zoom-meeting-id', FILTER_VALIDATE_INT );
			$meeting_id             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			$registration           = filter_input( INPUT_POST, 'bp-zoom-meeting-registration', FILTER_VALIDATE_BOOLEAN );
			$registration_type      = filter_input( INPUT_POST, 'bp-zoom-meeting-registration-type', FILTER_VALIDATE_INT );
			$join_before_host       = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video             = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video     = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants      = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room           = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$meeting_authentication = filter_input( INPUT_POST, 'bp-zoom-meeting-authentication', FILTER_VALIDATE_BOOLEAN );
			$auto_recording         = filter_input( INPUT_POST, 'bp-zoom-meeting-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$alternative_host_ids   = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-alt-host-ids' );
			$auto_recording_option  = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-recording' );
			$recurring              = filter_input( INPUT_POST, 'bp-zoom-meeting-recurring', FILTER_VALIDATE_BOOLEAN );
			$recurrence             = filter_input( INPUT_POST, 'bp-zoom-meeting-recurrence', FILTER_VALIDATE_INT );
			$alert                  = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-alert' );
			$notification           = filter_input( INPUT_POST, 'bp-zoom-meeting-notification', FILTER_VALIDATE_BOOLEAN );

			$alternative_host_ids = ! empty( $alternative_host_ids ) ? str_replace( ' ', '', $alternative_host_ids ) : '';
			$alternative_host_ids = ! empty( $alternative_host_ids ) ? explode( ',', $alternative_host_ids ) : array();

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
			} else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
			}

			$type = 2;
			if ( $recurring ) {
				if ( 1 === $recurrence || 2 === $recurrence || 3 === $recurrence ) {
					$type = 8;
				}
				if ( - 1 === $recurrence ) {
					$type = 3;
				}
			}

			$data = array(
				'title'                  => $title,
				'description'            => $description,
				'type'                   => $type,
				'host_id'                => $host_email,
				'user_id'                => bp_loggedin_user_id(),
				'start_date_utc'         => $start_date_time,
				'timezone'               => $timezone,
				'duration'               => $duration,
				'password'               => $password,
				'registration'           => $registration,
				'join_before_host'       => $join_before_host,
				'host_video'             => $host_video,
				'participants_video'     => $participants_video,
				'mute_participants'      => $mute_participants,
				'waiting_room'           => $waiting_room,
				'meeting_authentication' => $meeting_authentication,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'recurring'              => $recurring,
				'alert'                  => 0,
			);

			$recurrence_obj = array();
			if ( $recurring && 8 === $type ) {
				$recurrence_obj['type'] = $recurrence;
				$repeat_interval        = filter_input( INPUT_POST, 'bp-zoom-meeting-repeat-interval', FILTER_VALIDATE_INT );
				$end_time_select        = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-end-time-select' );

				if ( 1 === $recurrence ) {
					if ( 90 < $repeat_interval ) {
						$repeat_interval = 90;
					}
				} elseif ( 2 === $recurrence ) {
					if ( 12 < $repeat_interval ) {
						$repeat_interval = 12;
					}

					$weekly_days                   = filter_input( INPUT_POST, 'bp-zoom-meeting-weekly-days', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					$recurrence_obj['weekly_days'] = implode( ',', $weekly_days );
				} elseif ( 3 === $recurrence ) {
					if ( 3 < $repeat_interval ) {
						$repeat_interval = 3;
					}
					$monthly_occurs_on = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-monthly-occurs-on' );

					if ( 'day' === $monthly_occurs_on ) {
						$monthly_day                   = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-day', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_day'] = $monthly_day;
					} elseif ( 'week' === $monthly_occurs_on ) {
						$monthly_week_day                   = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-week-day', FILTER_VALIDATE_INT );
						$monthly_week                       = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-week', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_week_day'] = $monthly_week_day;
						$recurrence_obj['monthly_week']     = $monthly_week;
					}
				}

				if ( 'date' === $end_time_select ) {
					$end_date_time = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-end-date-time' );
					$end_date_time = new DateTime( $end_date_time . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
					$end_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
					$recurrence_obj['end_date_time'] = $end_date_time->format( 'Y-m-d\TH:i:s\Z' );
				} else {
					$end_times = filter_input( INPUT_POST, 'bp-zoom-meeting-end-times', FILTER_VALIDATE_INT );

					if ( 50 < $end_times ) {
						$end_times = 50;
					}
					$recurrence_obj['end_times'] = $end_times;
				}

				$recurrence_obj['repeat_interval'] = $repeat_interval;

				$data['recurrence'] = $recurrence_obj;

				// add registration type if meeting is recurring of type 1,2,3.
				$data['registration_type'] = $registration_type;
			}

			if ( ! empty( $meeting_id ) ) {
				$data['meeting_id'] = $meeting_id;
				$zoom_meeting       = bp_zoom_conference()->update_meeting( $data );
			} else {
				$zoom_meeting = bp_zoom_conference()->create_meeting( $data );
			}

			if ( ! empty( $zoom_meeting['body'] ) && ! empty( $zoom_meeting['body']->errors ) && ! empty( $zoom_meeting['body']->errors->message ) ) {
				$response_error = array( 'error' => (string) $zoom_meeting['body']->errors->message );
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

				if ( ! empty( $zoom_meeting['response']->message ) ) {
					$response_error = array( 'error' => $zoom_meeting['response']->message );
				}

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			// 201 - created, 204 - updated.
			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 201, 204 ), true ) ) {

				if ( ! empty( $zoom_meeting['response'] ) ) {
					$data['meeting_id'] = $zoom_meeting['response']->id;
					$meeting_id         = $zoom_meeting['response']->id;
				}

				if ( ! empty( $id ) ) {
					$data['id'] = $id;
				}

				if ( ! empty( $meeting_id ) ) {
					$data['meeting_id'] = $meeting_id;
				}

				if ( ! empty( $group_id ) ) {
					$data['group_id'] = $group_id;
				}

				if ( ! empty( $alternative_host_ids ) ) {
					$data['alternative_host_ids'] = implode( ',', $alternative_host_ids );
				}

				// Hide main recurring meeting.
				if ( $recurring && 8 === $type ) {
					$data['hide_sitewide'] = true;
				}

				if ( ! empty( $notification ) ) {
					$data['alert'] = $alert;
				}

				$id = bp_zoom_meeting_add( $data );

				if ( ! $id ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the meeting.', 'buddyboss-pro' ) ) );
				}

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

				if ( bp_is_active( 'groups' ) ) {
					$group_link    = bp_get_group_permalink( groups_get_group( $group_id ) );
					$redirect_url  = trailingslashit( $group_link . 'zoom/meetings/' . $id );
					$occurrence_id = bp_zoom_meeting_get_meta( $id, 'zoom_meeting_occurrence_id', true );

					// if recurring meeting update then reload the page.
					if ( $recurring && 8 === $type && ! empty( $occurrence_id ) ) {
						bp_zoom_meeting_delete_meta( $id, 'zoom_meeting_occurrence_id' );
						$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $occurrence_id );
					}
				} else {
					$redirect_url = '';
				}

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'meeting_id'   => (int) $meeting_id,
					)
				);
			}

			wp_send_json_error( $response_error );
		}

		/**
		 * Zoom meeting delete
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_delete() {
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting_delete' ) ) {
				return;
			}

			$id            = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
			$meeting_id    = bb_pro_filter_input_string( INPUT_POST, 'meeting_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_POST, 'occurrence_id' );

			if ( empty( $id ) || empty( $meeting_id ) ) {
				return;
			}

			$meeting = new BP_Zoom_Meeting( $id );

			if ( empty( $meeting->id ) ) {
				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => false,
					)
				);
			}

			// Get meeting if not exists remove all occurrences and recordings.
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
			if ( empty( $parent_meeting->id ) ) {
				bp_zoom_meeting_delete( array( 'parent' => $meeting_id ) );
				bp_zoom_recording_delete( array( 'meeting_id' => $meeting_id ) );

				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => false,
					)
				);
			}

			$is_past = $meeting->is_past;

			// empty occurrence then parent meeting edit load.
			if ( empty( $occurrence_id ) && ! empty( $meeting_id ) && ! empty( $meeting->zoom_type ) && 'meeting_occurrence' === $meeting->zoom_type && ! empty( $parent_meeting->id ) ) {
				$id = $parent_meeting->id;
			}

			$meeting = new BP_Zoom_Meeting( $id );

			if ( empty( $meeting->group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a meeting in this group.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $meeting->group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a meeting in this group.', 'buddyboss-pro' ) ) );
			}

			// Connect to Zoom.
			bb_zoom_group_connect_api( $meeting->group_id );

			if ( empty( $occurrence_id ) ) {
				$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id );
			} else {
				$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id, $occurrence_id );
			}

			if ( isset( $meeting_deleted['code'] ) && 204 === $meeting_deleted['code'] && bp_zoom_meeting_delete( array( 'id' => $id ) ) ) {

				// Remove all occurrences and recordings.
				if ( $meeting->recurring && empty( $occurrence_id ) ) {
					bp_zoom_meeting_delete( array( 'parent' => $meeting_id ) );
					bp_zoom_recording_delete( array( 'meeting_id' => $meeting_id ) );
				}

				// update parent meeting details.
				if ( ! empty( $meeting_id ) ) {
					$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
					if ( ! empty( $parent_meeting->id ) ) {
						$updated_meeting = bp_zoom_conference()->get_meeting_info( $meeting_id );
						if ( ! empty( $updated_meeting['code'] ) && 200 === $updated_meeting['code'] && ! empty( $updated_meeting['response'] ) ) {
							bp_zoom_meeting_update_meta( $parent_meeting->id, 'zoom_details', wp_json_encode( $updated_meeting['response'] ) );
						}
					}
				}

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => $is_past,
					)
				);
			}

			if ( isset( $meeting_deleted['code'] ) && in_array( $meeting_deleted['code'], array( 400, 404 ), true ) ) {
				$response_error = array( 'error' => $meeting_deleted['response']->message );

				if ( ! empty( $meeting_deleted['response']->errors ) ) {
					$response_error['errors'] = $meeting_deleted['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_success(
				array(
					'deleted' => $meeting_deleted,
					'is_past' => $is_past,
				)
			);
		}

		/**
		 * Zoom meeting invitation.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_invitation() {
			$meeting_id = bb_pro_filter_input_string( INPUT_GET, 'meeting_id' );

			if ( empty( $meeting_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide ID of the meeting.', 'buddyboss-pro' ) ) );
			}

			$meetings = bp_zoom_meeting_get_specific( array( 'meeting_id' => $meeting_id ) );

			if ( ! empty( $meetings['meetings'] ) ) {
				$meeting = $meetings['meetings'][0];

				if ( ! empty( $meeting->group_id ) ) {
					// Connect to Zoom.
					bb_zoom_group_connect_api( $meeting->group_id );
				}
			}

			$invitation_response = bp_zoom_conference()->meeting_invitation( $meeting_id );

			if ( 200 === $invitation_response['code'] && ! empty( $invitation_response['response'] ) ) {
				$invitation = $invitation_response['response']->invitation;

				wp_send_json_success( array( 'invitation' => $invitation ) );
			}

			wp_send_json_error( array( 'error' => __( 'There was a issue in fetching invitation.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom meeting load more list
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_load_more() {
			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = bb_pro_filter_input_string( INPUT_GET, 'search_terms' );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			} else {
				$args['sort']  = 'ASC';
				$args['since'] = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['from']  = false;
			}

			ob_start();
			if ( bp_has_zoom_meetings( $args ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/loop-meeting' );
				}
				if ( bp_zoom_meeting_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_meeting_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom meeting load more list
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_search() {
			ob_start();

			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = bb_pro_filter_input_string( INPUT_GET, 'search_terms' );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			}

			if ( bp_has_zoom_meetings( $args ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/loop-meeting' );
				}
				if ( bp_zoom_meeting_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_meeting_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			} else {
				bp_nouveau_user_feedback( 'meetings-loop-none' );
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom single meeting load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_get_single_meeting() {
			$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

			if ( empty( $id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide meeting ID.', 'buddyboss-pro' ) ) );
			}

			ob_start();
			if ( bp_has_zoom_meetings( array( 'include' => $id ) ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/single-meeting-item' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom create meeting form load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_create_meeting() {
			ob_start();

			bp_get_template_part( 'zoom/create-meeting' );

			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom edit meeting form load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_edit_meeting() {
			$id            = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			$meeting_id    = bb_pro_filter_input_string( INPUT_GET, 'meeting_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_GET, 'occurrence_id' );

			if ( empty( $id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide meeting ID.', 'buddyboss-pro' ) ) );
			}

			$hide_sitewide = false;

			// empty occurrence then parent meeting edit load.
			if ( empty( $occurrence_id ) && ! empty( $meeting_id ) ) {
				$meeting_obj = new BP_Zoom_Meeting( $id );
				if ( ! empty( $meeting_obj->zoom_type ) && 'meeting_occurrence' === $meeting_obj->zoom_type ) {
					$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_obj->parent );
					if ( ! empty( $meeting ) ) {
						$id            = $meeting->id;
						$hide_sitewide = true;
					}
				}
			}

			ob_start();
			if ( bp_has_zoom_meetings(
				array(
					'include'       => $id,
					'hide_sitewide' => $hide_sitewide,
				)
			) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/edit-meeting' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom meetings sync.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meetings_sync() {
			global $wpdb;
			$bp = buddypress();

			$offset   = filter_input( INPUT_POST, 'offset', FILTER_VALIDATE_INT );
			$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			$api_email = bb_zoom_group_get_email_account( $group_id );

			// Connect to Zoom.
			bb_zoom_group_connect_api( $group_id );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$meetings = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->table_prefix}bp_zoom_meetings WHERE group_id = %d AND zoom_type = 'meeting' AND host_id = %s LIMIT 10 OFFSET %d", $group_id, $api_email, $offset ) );

			if ( ! empty( $meetings ) ) {
				foreach ( $meetings as $meeting_id ) {
					$meeting = new BP_Zoom_Meeting( $meeting_id );

					// Recordings enabled then update count meta.
					if ( bp_zoom_is_zoom_recordings_enabled() ) {

						// Sync the recordings.
						bp_zoom_meeting_fetch_recordings( $meeting->meeting_id );

						$recordings_count = bp_zoom_recording_get(
							array(),
							array(
								'meeting_id' => $meeting->meeting_id,
							)
						);

						bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_count', count( $recordings_count ) );

						// Update recording count for the occurrence meetings.
						bb_zoom_update_occurrence_meeting_recording( $meeting->meeting_id );
					}

					// save the meeting.
					$meeting->save();

					$offset ++;
				}

				/* translators: %s total meetings */
				$records_updated = sprintf( __( '%s meetings updated successfully.', 'buddyboss-pro' ), number_format_i18n( $offset ) );

				wp_send_json_success(
					array(
						'status'  => 'running',
						'offset'  => $offset,
						'records' => $records_updated,
					)
				);

			} else {
				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/?sync_meeting_done=1' );
				wp_send_json_success(
					array(
						'status'       => 1,
						'message'      => __( 'Meetings update complete!', 'buddyboss-pro' ),
						'redirect_url' => $redirect_url,
					)
				);
			}

			wp_send_json_error();
		}

		/**
		 * Recording fetch.
		 */
		public function zoom_meeting_recordings() {
			$meeting_id    = bb_pro_filter_input_string( INPUT_GET, 'meeting_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_GET, 'occurrence_id' );
			$title         = bb_pro_filter_input_string( INPUT_GET, 'title' );

			set_query_var( 'recording_fetch', 'yes' );
			set_query_var( 'meeting_id', $meeting_id );
			set_query_var( 'occurrence_id', $occurrence_id );
			set_query_var( 'topic', $title );

			ob_start();
			bp_get_template_part( 'zoom/meeting/recordings' );
			$content = ob_get_clean();

			$id    = 0;
			$count = 0;
			if ( ! empty( $meeting_id ) && empty( $occurrence_id ) ) {
				$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
				if ( ! empty( $meeting->id ) ) {
					$count = bp_zoom_meeting_get_meta( $meeting->id, 'zoom_recording_count', true );
					$id    = $meeting->id;
				}
			} elseif ( ! empty( $meeting_id ) && ! empty( $occurrence_id ) ) {
				$occurrence = BP_Zoom_Meeting::get_meeting_by_meeting_id( $occurrence_id, $meeting_id );
				if ( ! empty( $occurrence->id ) ) {
					$count = bp_zoom_meeting_get_meta( $occurrence->id, 'zoom_recording_count', true );
					$id    = $occurrence->id;
				}
			}

			wp_send_json_success(
				array(
					'contents' => $content,
					'count'    => $count,
					'id'       => $id,
				)
			);
		}

		/**
		 * Zoom webinar add.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_add() {
			$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( $response_error );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_webinar' ) ) {
				wp_send_json_error( $response_error );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
			}

			$group_id = filter_input( INPUT_POST, 'bp-zoom-webinar-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create webinar in this group.', 'buddyboss-pro' ) ) );
			}

			$host_email = '';
			$host_type  = false;
			if ( ! empty( $group_id ) ) {
				if ( ! bp_zoom_is_group_setup( $group_id ) ) {
					wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
				}

				$host_email      = bb_zoom_group_get_email_account( $group_id );
				$host_type       = bb_zoom_group_get_host_type( $group_id );
				$webinar_enabled = bp_zoom_groups_is_webinars_enabled( $group_id );

				if ( empty( $webinar_enabled ) ) {
					wp_send_json_error( array( 'error' => __( 'Webinars not allowed in this group.', 'buddyboss-pro' ) ) );
				}

				// Connect to Zoom.
				bb_zoom_group_connect_api( $group_id );
			}

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$title = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-title' );

			if ( empty( $title ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter the webinar title.', 'buddyboss-pro' ) ) );
			}

			$description = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-description' );
			$timezone    = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-timezone' );
			$start_date  = filter_input( INPUT_POST, 'bp-zoom-webinar-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-webinar-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-webinar-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-webinar-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-start-time-meridian' );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			$recurring  = filter_input( INPUT_POST, 'bp-zoom-webinar-recurring', FILTER_VALIDATE_BOOLEAN );
			$webinar_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			if (
				strtotime( $start_date_time ) < strtotime( wp_date( 'Y-m-d\TH:i:s', strtotime( 'now' ), new DateTimeZone( $timezone ) ) ) &&
				! empty( $webinar_id ) && empty( $recurring )
			) {
				wp_send_json_error( array( 'error' => __( 'Please change the webinar date to a future date.', 'buddyboss-pro' ) ) );
			}

			// If recurring weekly webinar then week day should not be empty.
			if (
				$recurring &&
				2 === filter_input( INPUT_POST, 'bp-zoom-webinar-recurrence', FILTER_VALIDATE_INT ) &&
				empty( filter_input( INPUT_POST, 'bp-zoom-webinar-weekly-days', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) )
			) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar week day.', 'buddyboss-pro' ) ) );
			}

			$password = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-password' );

			$id                     = filter_input( INPUT_POST, 'bp-zoom-webinar-id', FILTER_VALIDATE_INT );
			$webinar_id             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			$registration           = filter_input( INPUT_POST, 'bp-zoom-webinar-registration', FILTER_VALIDATE_BOOLEAN );
			$registration_type      = filter_input( INPUT_POST, 'bp-zoom-webinar-registration-type', FILTER_VALIDATE_INT );
			$host_video             = filter_input( INPUT_POST, 'bp-zoom-webinar-host-video', FILTER_VALIDATE_BOOLEAN );
			$panelists_video        = filter_input( INPUT_POST, 'bp-zoom-webinar-panelists-video', FILTER_VALIDATE_BOOLEAN );
			$practice_session       = filter_input( INPUT_POST, 'bp-zoom-webinar-practice-session', FILTER_VALIDATE_BOOLEAN );
			$on_demand              = filter_input( INPUT_POST, 'bp-zoom-webinar-on-demand', FILTER_VALIDATE_BOOLEAN );
			$meeting_authentication = filter_input( INPUT_POST, 'bp-zoom-webinar-authentication', FILTER_VALIDATE_BOOLEAN );
			$auto_recording         = filter_input( INPUT_POST, 'bp-zoom-webinar-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$alternative_host_ids   = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-alt-host-ids' );
			$auto_recording_option  = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-recording' );
			$recurring              = filter_input( INPUT_POST, 'bp-zoom-webinar-recurring', FILTER_VALIDATE_BOOLEAN );
			$recurrence             = filter_input( INPUT_POST, 'bp-zoom-webinar-recurrence', FILTER_VALIDATE_INT );
			$alert                  = filter_input( INPUT_POST, 'bp-zoom-webinar-alert', FILTER_VALIDATE_INT );
			$notification           = filter_input( INPUT_POST, 'bp-zoom-webinar-notification', FILTER_VALIDATE_BOOLEAN );

			$alternative_host_ids = str_replace( ' ', '', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
			} else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
			}

			$type = 5;
			if ( $recurring ) {
				if ( 1 === $recurrence || 2 === $recurrence || 3 === $recurrence ) {
					$type = 9;
				}
				if ( - 1 === $recurrence ) {
					$type = 6;
				}
			}

			$data = array(
				'title'                  => $title,
				'description'            => $description,
				'type'                   => $type,
				'host_id'                => $host_email,
				'user_id'                => bp_loggedin_user_id(),
				'start_date_utc'         => $start_date_time,
				'timezone'               => $timezone,
				'duration'               => $duration,
				'password'               => $password,
				'registration'           => $registration,
				'host_video'             => $host_video,
				'panelists_video'        => $panelists_video,
				'practice_session'       => $practice_session,
				'on_demand'              => $on_demand,
				'meeting_authentication' => $meeting_authentication,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'recurring'              => $recurring,
				'alert'                  => 0,
			);

			$recurrence_obj = array();
			if ( $recurring && 9 === $type ) {
				$recurrence_obj['type'] = $recurrence;
				$repeat_interval        = filter_input( INPUT_POST, 'bp-zoom-webinar-repeat-interval', FILTER_VALIDATE_INT );
				$end_time_select        = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-end-time-select' );

				if ( 1 === $recurrence ) {
					if ( 90 < $repeat_interval ) {
						$repeat_interval = 90;
					}
				} elseif ( 2 === $recurrence ) {
					if ( 12 < $repeat_interval ) {
						$repeat_interval = 12;
					}

					$weekly_days                   = filter_input( INPUT_POST, 'bp-zoom-webinar-weekly-days', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					$recurrence_obj['weekly_days'] = implode( ',', $weekly_days );
				} elseif ( 3 === $recurrence ) {
					if ( 3 < $repeat_interval ) {
						$repeat_interval = 3;
					}
					$monthly_occurs_on = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-monthly-occurs-on' );

					if ( 'day' === $monthly_occurs_on ) {
						$monthly_day                   = filter_input( INPUT_POST, 'bp-zoom-webinar-monthly-day', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_day'] = $monthly_day;
					} elseif ( 'week' === $monthly_occurs_on ) {
						$monthly_week_day                   = filter_input( INPUT_POST, 'bp-zoom-webinar-monthly-week-day', FILTER_VALIDATE_INT );
						$monthly_week                       = filter_input( INPUT_POST, 'bp-zoom-webinar-monthly-week', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_week_day'] = $monthly_week_day;
						$recurrence_obj['monthly_week']     = $monthly_week;
					}
				}

				if ( 'date' === $end_time_select ) {
					$end_date_time = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-end-date-time' );
					$end_date_time = new DateTime( $end_date_time . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
					$end_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
					$recurrence_obj['end_date_time'] = $end_date_time->format( 'Y-m-d\TH:i:s\Z' );
				} else {
					$end_times = filter_input( INPUT_POST, 'bp-zoom-webinar-end-times', FILTER_VALIDATE_INT );

					if ( 50 < $end_times ) {
						$end_times = 50;
					}
					$recurrence_obj['end_times'] = $end_times;
				}

				$recurrence_obj['repeat_interval'] = $repeat_interval;

				$data['recurrence'] = $recurrence_obj;

				// add registration type if webinar is recurring of type 1,2,3.
				$data['registration_type'] = $registration_type;
			}

			if ( ! empty( $webinar_id ) ) {
				$data['webinar_id'] = $webinar_id;
				$zoom_webinar       = bp_zoom_conference()->update_webinar( $data );
			} else {
				$zoom_webinar = bp_zoom_conference()->create_webinar( $data );
			}

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

				if ( ! empty( $zoom_webinar['response']->message ) ) {
					$response_error = array( 'error' => $zoom_webinar['response']->message );
				}

				if ( ! empty( $zoom_webinar['response']->errors ) ) {
					$response_error['errors'] = $zoom_webinar['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 201, 204 ), true ) ) {

				if ( ! empty( $zoom_webinar['response'] ) ) {
					$data['webinar_id'] = $zoom_webinar['response']->id;
					$webinar_id         = $zoom_webinar['response']->id;
				}

				if ( ! empty( $id ) ) {
					$data['id'] = $id;
				}

				if ( ! empty( $webinar_id ) ) {
					$data['webinar_id'] = $webinar_id;
				}

				if ( ! empty( $group_id ) ) {
					$data['group_id'] = $group_id;
				}

				if ( ! empty( $alternative_host_ids ) ) {
					$data['alternative_host_ids'] = implode( ',', $alternative_host_ids );
				}

				// Hide main recurring webinar.
				if ( $recurring && 9 === $type ) {
					$data['hide_sitewide'] = true;
				}

				if ( $notification ) {
					$data['alert'] = $alert;
				}

				$id = bp_zoom_webinar_add( $data );

				if ( ! $id ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the webinar.', 'buddyboss-pro' ) ) );
				}

				if ( bp_is_active( 'groups' ) ) {
					$group_link    = bp_get_group_permalink( groups_get_group( $group_id ) );
					$redirect_url  = trailingslashit( $group_link . 'zoom/webinars/' . $id );
					$occurrence_id = bp_zoom_webinar_get_meta( $id, 'zoom_webinar_occurrence_id', true );

					// if recurring webinar update then reload the page.
					if ( $recurring && 9 === $type && ! empty( $occurrence_id ) ) {
						bp_zoom_webinar_delete_meta( $id, 'zoom_webinar_occurrence_id' );
						$redirect_url = trailingslashit( $group_link . 'zoom/webinars/' . $occurrence_id );
					}
				} else {
					$redirect_url = '';
				}

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'webinar_id'   => (int) $webinar_id,
					)
				);
			}

			wp_send_json_error( $response_error );
		}

		/**
		 * Zoom webinar load more list
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_load_more() {
			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = bb_pro_filter_input_string( INPUT_GET, 'search_terms' );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			} else {
				$args['sort']  = 'ASC';
				$args['since'] = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['from']  = false;
			}

			ob_start();
			if ( bp_has_zoom_webinars( $args ) ) {
				while ( bp_zoom_webinar() ) {
					bp_the_zoom_webinar();

					bp_get_template_part( 'zoom/loop-webinar' );
				}
				if ( bp_zoom_webinar_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_webinar_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom single webinar load.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_get_single_webinar() {
			$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

			if ( empty( $id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide webinar ID.', 'buddyboss-pro' ) ) );
			}

			ob_start();
			if ( bp_has_zoom_webinars( array( 'include' => $id ) ) ) {
				while ( bp_zoom_webinar() ) {
					bp_the_zoom_webinar();

					bp_get_template_part( 'zoom/single-webinar-item' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom create webinar form load.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_create_webinar() {
			ob_start();

			bp_get_template_part( 'zoom/create-webinar' );

			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom edit webinar form load.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_edit_webinar() {
			$id            = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			$webinar_id    = bb_pro_filter_input_string( INPUT_GET, 'webinar_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_GET, 'occurrence_id' );

			if ( empty( $id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide webinar ID.', 'buddyboss-pro' ) ) );
			}

			$hide_sitewide = false;

			// empty occurrence then parent webinar edit load.
			if ( empty( $occurrence_id ) && ! empty( $webinar_id ) ) {
				$webinar_obj = new BP_Zoom_Webinar( $id );
				if ( ! empty( $webinar_obj->zoom_type ) && 'webinar_occurrence' === $webinar_obj->zoom_type ) {
					$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_obj->parent );
					if ( ! empty( $webinar ) ) {
						$id            = $webinar->id;
						$hide_sitewide = true;
					}
				}
			}

			ob_start();
			if ( bp_has_zoom_webinars(
				array(
					'include'       => $id,
					'hide_sitewide' => $hide_sitewide,
				)
			) ) {
				while ( bp_zoom_webinar() ) {
					bp_the_zoom_webinar();

					bp_get_template_part( 'zoom/edit-webinar' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom webinar delete
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_delete() {
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_webinar_delete' ) ) {
				return;
			}

			$id            = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
			$webinar_id    = bb_pro_filter_input_string( INPUT_POST, 'webinar_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_POST, 'occurrence_id' );

			if ( empty( $id ) || empty( $webinar_id ) ) {
				return;
			}

			$webinar = new BP_Zoom_Webinar( $id );

			if ( empty( $webinar->id ) ) {
				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => false,
					)
				);
			}

			// Get webinar if not exists remove all occurrences and recordings.
			$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );
			if ( empty( $parent_webinar->id ) ) {
				bp_zoom_webinar_delete( array( 'parent' => $webinar_id ) );
				bp_zoom_webinar_recording_delete( array( 'webinar_id' => $webinar_id ) );

				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => false,
					)
				);
			}

			$is_past = $webinar->is_past;

			// empty occurrence then parent webinar edit load.
			if ( empty( $occurrence_id ) && ! empty( $webinar_id ) && ! empty( $webinar->zoom_type ) && 'webinar_occurrence' === $webinar->zoom_type && ! empty( $parent_webinar->id ) ) {
				$id = $parent_webinar->id;
			}

			$webinar = new BP_Zoom_Webinar( $id );

			if ( empty( $webinar->group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a webinar in this group.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $webinar->group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a webinar in this group.', 'buddyboss-pro' ) ) );
			}

			// Connect to Zoom.
			bb_zoom_group_connect_api( $webinar->group_id );

			if ( empty( $occurrence_id ) ) {
				$webinar_deleted = bp_zoom_conference()->delete_webinar( $webinar_id );
			} else {
				$webinar_deleted = bp_zoom_conference()->delete_webinar( $webinar_id, $occurrence_id );
			}

			if ( isset( $webinar_deleted['code'] ) && 204 === $webinar_deleted['code'] && bp_zoom_webinar_delete( array( 'id' => $id ) ) ) {

				// Remove all occurrences and recordings.
				if ( $webinar->recurring && empty( $occurrence_id ) ) {
					bp_zoom_webinar_delete( array( 'parent' => $webinar_id ) );
					bp_zoom_webinar_recording_delete( array( 'webinar_id' => $webinar_id ) );
				}

				// update parent webinar details.
				if ( ! empty( $webinar_id ) ) {
					$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );
					if ( ! empty( $parent_webinar->id ) ) {
						$updated_webinar = bp_zoom_conference()->get_webinar_info( $webinar_id );
						if ( ! empty( $updated_webinar['code'] ) && 200 === $updated_webinar['code'] && ! empty( $updated_webinar['response'] ) ) {
							bp_zoom_webinar_update_meta( $parent_webinar->id, 'zoom_details', wp_json_encode( $updated_webinar['response'] ) );
						}
					}
				}

				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => $is_past,
					)
				);
			}

			if ( isset( $webinar_deleted['code'] ) && in_array( $webinar_deleted['code'], array( 400, 404 ), true ) ) {
				$response_error = array( 'error' => $webinar_deleted['response']->message );

				if ( ! empty( $webinar_deleted['response']->errors ) ) {
					$response_error['errors'] = $webinar_deleted['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_success(
				array(
					'deleted' => $webinar_deleted,
					'is_past' => $is_past,
				)
			);
		}

		/**
		 * Zoom webinar load more list
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_search() {
			ob_start();

			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = bb_pro_filter_input_string( INPUT_GET, 'search_terms' );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			}

			if ( bp_has_zoom_webinars( $args ) ) {
				while ( bp_zoom_webinar() ) {
					bp_the_zoom_webinar();

					bp_get_template_part( 'zoom/loop-webinar' );
				}
				if ( bp_zoom_webinar_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_webinar_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			} else {
				bp_nouveau_user_feedback( 'webinars-loop-none' );
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Function handle recurring webinar update.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_occurrence_edit() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_webinar' ) ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
			}

			$group_id = filter_input( INPUT_POST, 'bp-zoom-webinar-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create webinar in this group.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_zoom_is_group_setup( $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
			}

			$host_email = bb_zoom_group_get_email_account( $group_id );
			$host_type  = bb_zoom_group_get_host_type( $group_id );

			// Connect to Zoom.
			bb_zoom_group_connect_api( $group_id );

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$description = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-description' );

			$start_date = filter_input( INPUT_POST, 'bp-zoom-webinar-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			if ( strtotime( $start_date ) < strtotime( wp_date( 'Y-m-d' ) ) ) {
				wp_send_json_error( array( 'error' => __( 'Please change the webinar date to a future date.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-webinar-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-webinar-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-webinar-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-start-time-meridian' );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$id                    = filter_input( INPUT_POST, 'bp-zoom-webinar-id', FILTER_VALIDATE_INT );
			$occurrence_id         = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-occurrence-id' );
			$webinar_id            = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			$host_video            = filter_input( INPUT_POST, 'bp-zoom-webinar-host-video', FILTER_VALIDATE_BOOLEAN );
			$panelists_video       = filter_input( INPUT_POST, 'bp-zoom-webinar-panelists-video', FILTER_VALIDATE_BOOLEAN );
			$auto_recording        = filter_input( INPUT_POST, 'bp-zoom-webinar-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$auto_recording_option = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-recording' );

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
			} else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
			}

			$timezone = wp_timezone_string();
			// update parent webinar details.
			$parent_webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );
			if ( ! empty( $parent_webinar->id ) ) {
				$timezone = $parent_webinar->timezone;
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			$start_date_time_save = new DateTime( $start_date . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
			$start_date_time_save->setTimeZone( new DateTimeZone( 'UTC' ) );
			$start_date_time_save = $start_date_time_save->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'description'     => $description,
				'start_date'      => $start_date_time,
				'duration'        => $duration,
				'host_video'      => $host_video,
				'panelists_video' => $panelists_video,
				'auto_recording'  => $auto_recording,
				'webinar_id'      => $webinar_id,
			);

			$zoom_webinar = bp_zoom_conference()->update_webinar_occurrence( $occurrence_id, $data );

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => $zoom_webinar['response']->message );

				if ( ! empty( $zoom_webinar['response']->errors ) ) {
					$response_error['errors'] = $zoom_webinar['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_webinar['code'] ) && 204 === $zoom_webinar['code'] ) {
				$webinar                  = new BP_Zoom_Webinar( $id );
				$webinar->description     = $data['description'];
				$webinar->duration        = $data['duration'];
				$webinar->host_video      = $data['host_video'];
				$webinar->panelists_video = $data['panelists_video'];
				$webinar->auto_recording  = $data['auto_recording'];
				$webinar->start_date_utc  = $start_date_time_save;

				if ( ! $webinar->save() ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the occurrence.', 'buddyboss-pro' ) ) );
				}

				// update parent webinar details.
				if ( ! empty( $parent_webinar->id ) ) {
					$updated_webinar = bp_zoom_conference()->get_webinar_info( $webinar_id, false, true );
					if ( ! empty( $updated_webinar['code'] ) && 200 === $updated_webinar['code'] && ! empty( $updated_webinar['response'] ) ) {
						bp_zoom_webinar_update_meta( $parent_webinar->id, 'zoom_details', wp_json_encode( $updated_webinar['response'] ) );
					}
				}

				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/webinars/' . $id );

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'meeting_id'   => (int) $webinar_id,
					)
				);
			}

			wp_send_json_error( array( 'error' => __( 'There was a problem when updating an occurrence. Please try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Webinar Recording fetch.
		 */
		public function zoom_webinar_recordings() {
			$webinar_id    = bb_pro_filter_input_string( INPUT_GET, 'webinar_id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_GET, 'occurrence_id' );
			$title         = bb_pro_filter_input_string( INPUT_GET, 'title' );

			set_query_var( 'recording_fetch', 'yes' );
			set_query_var( 'webinar_id', $webinar_id );
			set_query_var( 'occurrence_id', $occurrence_id );
			set_query_var( 'topic', $title );

			ob_start();
			bp_get_template_part( 'zoom/webinar/recordings' );
			$content = ob_get_clean();

			$id    = 0;
			$count = 0;
			if ( ! empty( $webinar_id ) && empty( $occurrence_id ) ) {
				$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );
				if ( ! empty( $webinar->id ) ) {
					$count = bp_zoom_webinar_get_meta( $webinar->id, 'zoom_recording_count', true );
					$id    = $webinar->id;
				}
			} elseif ( ! empty( $webinar_id ) && ! empty( $occurrence_id ) ) {
				$occurrence = BP_Zoom_Webinar::get_webinar_by_webinar_id( $occurrence_id, $webinar_id );
				if ( ! empty( $occurrence->id ) ) {
					$count = bp_zoom_webinar_get_meta( $occurrence->id, 'zoom_recording_count', true );
					$id    = $occurrence->id;
				}
			}

			wp_send_json_success(
				array(
					'contents' => $content,
					'count'    => $count,
					'id'       => $id,
				)
			);
		}

		/**
		 * Zoom webinars sync.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinars_sync() {
			global $wpdb;
			$bp = buddypress();

			$offset   = filter_input( INPUT_POST, 'offset', FILTER_VALIDATE_INT );
			$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			$api_email = bb_zoom_group_get_email_account( $group_id );

			// Connect to Zoom.
			bb_zoom_group_connect_api( $group_id );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$webinars = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->table_prefix}bp_zoom_webinars WHERE group_id = %d AND zoom_type = 'webinar' AND host_id = %s LIMIT 10 OFFSET %d", $group_id, $api_email, $offset ) );

			if ( ! empty( $webinars ) ) {
				foreach ( $webinars as $webinar_id ) {
					$webinar = new BP_Zoom_Webinar( $webinar_id );

					// Recordings enabled then update count meta.
					if ( bp_zoom_is_zoom_recordings_enabled() ) {

						// Sync the recordings.
						bp_zoom_webinar_fetch_recordings( $webinar->webinar_id );

						$recordings_count = bp_zoom_webinar_recording_get(
							array(),
							array(
								'webinar_id' => $webinar->webinar_id,
							)
						);

						bp_zoom_webinar_update_meta( $webinar_id, 'zoom_recording_count', count( $recordings_count ) );

						// Update the count for the recordings of webinar.
						bb_zoom_update_occurrence_webinar_recording( $webinar->webinar_id );
					}

					// save the meeting.
					$webinar->save();

					$offset ++;
				}

				/* translators: %s total meetings */
				$records_updated = sprintf( __( '%s webinars updated successfully.', 'buddyboss-pro' ), number_format_i18n( $offset ) );

				wp_send_json_success(
					array(
						'status'  => 'running',
						'offset'  => $offset,
						'records' => $records_updated,
					)
				);

			} else {
				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/webinars/?sync_webinar_done=1' );
				wp_send_json_success(
					array(
						'status'       => 1,
						'message'      => __( 'Webinars update complete!', 'buddyboss-pro' ),
						'redirect_url' => $redirect_url,
					)
				);
			}

			wp_send_json_error();
		}

		/**
		 * Get zoom accounts.
		 *
		 * @since 2.3.91
		 */
		public function get_zoom_account_emails() {
			$account_id    = bb_pro_filter_input_string( INPUT_POST, 'account_id' );
			$client_id     = bb_pro_filter_input_string( INPUT_POST, 'client_id' );
			$client_secret = bb_pro_filter_input_string( INPUT_POST, 'client_secret' );
			$_nonce        = bb_pro_filter_input_string( INPUT_POST, '_nonce' );
			$group_id      = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			$field_disabled_attributes = 'is-disabled';

			if (
				empty( $_nonce ) ||
				(
					empty( $group_id ) &&
					! wp_verify_nonce( $_nonce, 'fetch-gutenberg-zoom-accounts' )
				) ||
				(
					! empty( $group_id ) &&
					! wp_verify_nonce( $_nonce, 'fetch-group-zoom-accounts' )
				)
			) {
				wp_send_json_success(
					array(
						'email_accounts' => '<option value="">' . __( 'No Zoom account found', 'buddyboss-pro' ) . '</option>',
						'field_disabled' => $field_disabled_attributes,
					)
				);
			}

			$options = bb_zoom_fetch_account_emails(
				array(
					'account_id'    => $account_id,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'group_id'      => $group_id,
					'force_api'     => true,
					'error_type'    => 'bool',
				)
			);

			if ( ! empty( $options ) ) {

				// More than one email found then enabled the email field.
				if ( is_array( $options ) && 1 < count( $options ) ) {
					$field_disabled_attributes = '';
				}

				if ( 0 < $group_id ) {
					$default_api_email = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
				} else {
					$default_api_email = bb_zoom_account_email();
				}

				$all_fetched_emails = array_keys( $options );
				if ( ! in_array( $default_api_email, $all_fetched_emails, true ) ) {
					$default_api_email = '';
				}

				$account_options = '';
				foreach ( $options as $option_key => $option ) {

					if ( empty( $default_api_email ) && str_contains( $option, ' Default' ) ) {
						$default_api_email = $option_key;
					}

					$account_options .= '<option value="' . $option_key . '" ' . selected( $default_api_email, $option_key, false ) . '>' . $option . '</option>';
				}

				wp_send_json_success(
					array(
						'email_accounts' => $account_options,
						'field_disabled' => $field_disabled_attributes,
					)
				);
			}

			wp_send_json_success(
				array(
					'email_accounts' => '<option value="">' . __( 'No Zoom account found', 'buddyboss-pro' ) . '</option>',
					'field_disabled' => $field_disabled_attributes,
				)
			);
		}

		/**
		 * Update group zoom secret token from wizard.
		 *
		 * @since 2.3.91
		 */
		public function bb_zoom_group_update_secret_token() {
			$secret_token = bb_pro_filter_input_string( INPUT_POST, 'secret_token' );
			$_nonce       = bb_pro_filter_input_string( INPUT_POST, '_nonce' );
			$group_id     = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			if (
				empty( $_nonce ) ||
				! wp_verify_nonce( $_nonce, 'update-group-zoom-secret-token' )
			) {
				wp_send_json_error(
					array( 'error' => __( 'Sorry, something goes wrong please try again.', 'buddyboss-pro' ) )
				);
			}

			if (
				empty( $group_id ) ||
				empty( groups_get_group( $group_id ) )
			) {
				wp_send_json_error(
					array( 'error' => __( 'The group ID is required.', 'buddyboss-pro' ) )
				);
			}

			$connection_type = bb_zoom_group_get_connection_type( $group_id );
			if ( 'group' !== $connection_type ) {
				wp_send_json_error(
					array( 'error' => __( 'The group is not connected with group zoom.', 'buddyboss-pro' ) )
				);
			}

			groups_update_groupmeta( $group_id, 'bb-group-zoom-s2s-secret-token', $secret_token );
			wp_send_json_success();
		}

		/**
		 * Save group zoom credentials from wizard.
		 *
		 * @since 2.3.91
		 */
		public function bb_zoom_group_save_credentials() {
			$account_id    = bb_pro_filter_input_string( INPUT_POST, 'account_id' );
			$client_id     = bb_pro_filter_input_string( INPUT_POST, 'client_id' );
			$client_secret = bb_pro_filter_input_string( INPUT_POST, 'client_secret' );
			$account_email = bb_pro_filter_input_string( INPUT_POST, 'account_email' );
			$_nonce        = bb_pro_filter_input_string( INPUT_POST, '_nonce' );
			$group_id      = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			$notice      = array();
			$notice_type = 'error';
			if ( empty( $account_id ) ) {
				$notice[] = __( 'The Account ID is required.', 'buddyboss-pro' );
			} elseif ( empty( $client_id ) ) {
				$notice[] = __( 'The Client ID is required.', 'buddyboss-pro' );
			} elseif ( empty( $client_secret ) ) {
				$notice[] = __( 'The Client Secret is required.', 'buddyboss-pro' );
			} elseif ( empty( $group_id ) || empty( groups_get_group( $group_id ) ) ) {
				$notice[] = __( 'The group ID is required.', 'buddyboss-pro' );
			} elseif (
				empty( $_nonce ) ||
				! wp_verify_nonce( $_nonce, 'submit-group-zoom-wizard' )
			) {
				$notice[] = __( 'Sorry, something goes wrong please try again.', 'buddyboss-pro' );
			}

			if ( empty( $notice ) ) {
				bb_zoom_group_save_s2s_credentials(
					array(
						'account_id'    => $account_id,
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'account_email' => $account_email,
						'secret_token'  => groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-secret-token' ),
						'group_id'      => $group_id,
					)
				);

				$bb_group_zoom = groups_get_groupmeta( $group_id, 'bb-group-zoom' );

				if ( ! empty( $bb_group_zoom['zoom_errors'] ) ) {
					foreach ( $bb_group_zoom['zoom_errors'] as $error ) {
						$notice[] = esc_html( $error->get_error_message() );
					}
					$bb_group_zoom['zoom_errors'] = array();
				} elseif ( ! empty( $bb_group_zoom['zoom_warnings'] ) ) {
					foreach ( $bb_group_zoom['zoom_warnings'] as $warning ) {
						$notice[]    = esc_html( $warning->get_error_message() );
						$notice_type = 'warning';
					}
					$bb_group_zoom['zoom_warnings'] = array();
				} elseif ( ! empty( $bb_group_zoom['zoom_success'] ) ) {
					$notice[]                      = $bb_group_zoom['zoom_success'];
					$notice_type                   = 'success';
					$bb_group_zoom['zoom_success'] = '';
				}

				groups_update_groupmeta( $group_id, 'bb-group-zoom', $bb_group_zoom );
				groups_update_groupmeta( $group_id, 'bp-group-zoom', true );
			}

			ob_start();
			bb_zoom_group_display_feedback_notice( $notice, $notice_type );
			$html_notice = ob_get_clean();

			wp_send_json_success(
				array(
					'notice' => $html_notice,
					'type'   => $notice_type,
				)
			);
		}
	}

	new BP_Zoom_Ajax();
}
