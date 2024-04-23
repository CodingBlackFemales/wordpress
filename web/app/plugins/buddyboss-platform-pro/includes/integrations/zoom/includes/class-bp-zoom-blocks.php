<?php
/**
 * BuddyBoss Zoom Blocks.
 *
 * @package BuddyBoss\Zoom\Blocks
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Zoom_Blocks' ) ) {
	/**
	 * Class BP_Zoom_Blocks
	 */
	class BP_Zoom_Blocks {
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

			// Webhook for blocks.
			add_action( 'bp_init', array( $this, 'bb_zoom_block_webhook' ), 10 );

			if ( ! bp_zoom_is_zoom_setup() ) {
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
			add_filter( 'bp_block_category_post_types', array( $this, 'bp_block_category_post_types' ) );
		}

		/**
		 * Setup actions.
		 *
		 * @since 1.0.0
		 */
		public function setup_actions() {
			add_action( 'init', array( $this, 'register_blocks' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );

			// Meeting.
			add_action( 'wp_ajax_zoom_meeting_block_add', array( $this, 'zoom_meeting_block_add' ) );
			add_action( 'wp_ajax_zoom_meeting_block_update_occurrence', array( $this, 'zoom_meeting_block_update_occurrence' ) );
			add_action( 'wp_ajax_zoom_meeting_block_delete_occurrence', array( $this, 'zoom_meeting_block_delete_occurrence' ) );
			add_action( 'wp_ajax_zoom_meeting_block_delete_meeting', array( $this, 'zoom_meeting_block_delete_meeting' ) );
			add_action( 'wp_ajax_zoom_meeting_sync', array( $this, 'zoom_meeting_sync' ) );
			add_shortcode( 'zoom_meeting', array( $this, 'render_meeting_shortcode' ) );

			// Webinar.
			if ( bp_zoom_is_zoom_webinar_enabled() ) {
				add_action( 'wp_ajax_zoom_webinar_block_add', array( $this, 'zoom_webinar_block_add' ) );
				add_action( 'wp_ajax_zoom_webinar_block_update_occurrence', array( $this, 'zoom_webinar_block_update_occurrence' ) );
				add_action( 'wp_ajax_zoom_webinar_block_delete_occurrence', array( $this, 'zoom_webinar_block_delete_occurrence' ) );
				add_action( 'wp_ajax_zoom_webinar_block_delete_webinar', array( $this, 'zoom_webinar_block_delete_webinar' ) );
				add_action( 'wp_ajax_zoom_webinar_sync', array( $this, 'zoom_webinar_sync' ) );
				add_shortcode( 'zoom_webinar', array( $this, 'render_webinar_shortcode' ) );
			}

			// Webhook for blocks.
			add_action( 'bp_init', array( $this, 'bb_zoom_block_webhook' ), 10 );
		}

		/**
		 * Register blocks
		 *
		 * @since 1.0.0
		 */
		public function register_blocks() {
			register_block_type(
				'bp-zoom-meeting/create-meeting',
				array(
					'editor_script'   => 'bp-zoom-meeting-block-js',
					'render_callback' => array( $this, 'render_meeting_block' ),
				)
			);

			if ( bp_zoom_is_zoom_webinar_enabled() ) {
				register_block_type(
					'bp-zoom-meeting/create-webinar',
					array(
						'editor_script'   => 'bp-zoom-meeting-block-js',
						'render_callback' => array( $this, 'render_webinar_block' ),
					)
				);
			}
		}

		/**
		 * Enqueue editor scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_editor_assets() {
			$rtl_css = is_rtl() ? '-rtl' : '';
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_style( 'bp-zoom-block-css', bp_zoom_integration_url( '/assets/css/bp-zoom-block' . $rtl_css . $min . '.css' ), array( 'wp-edit-blocks' ), bb_platform_pro()->version );

			wp_enqueue_script(
				'bp-zoom-meeting-block-js',
				bp_zoom_integration_url( '/assets/js/blocks/build/bp-zoom-meeting-block.js' ),
				array(
					'wp-block-editor',
					'wp-blocks',
					'wp-date',
					'wp-element',
					'wp-i18n',
					'wp-components',
					'wp-hooks',
				),
				bb_platform_pro()->version,
				false
			);

			$timezones          = bp_zoom_get_timezone_options();
			$timezones_val      = array();
			$wp_timezone_str    = get_option( 'timezone_string' );
			$selected_time_zone = '';

			if ( empty( $wp_timezone_str ) ) {
				$wp_timezone_str_offset = get_option( 'gmt_offset' );
			} else {
				$time                   = new DateTime( 'now', new DateTimeZone( $wp_timezone_str ) );
				$wp_timezone_str_offset = $time->getOffset() / 60 / 60;
			}

			foreach ( $timezones as $key => $timezone ) {
				$timezones_val[] = array(
					'label' => $timezone,
					'value' => $key,
				);
			}

			foreach ( $timezones as $key => $timezone ) {
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

			$host_user_type  = 1;
			$default_host_id = bb_zoom_account_email();
			$api_host_user   = bb_zoom_get_host_user();

			if ( ! empty( $api_host_user ) && 2 === (int) $api_host_user->type ) {
				$host_user_type = 2;
			}

			wp_localize_script(
				'bp-zoom-meeting-block-js',
				'bpZoomMeetingBlock',
				array(
					'timezones'                     => $timezones_val,
					'wp_timezone'                   => $selected_time_zone,
					'wp_date_time'                  => wp_date( 'Y-m-d\TH:i:s', strtotime( 'now' ) ),
					'default_host_id'               => $default_host_id,
					'default_host_user'             => bp_zoom_api_host_show(),
					'default_host_user_type'        => $host_user_type,
					'bp_zoom_meeting_nonce'         => wp_create_nonce( 'bp_zoom_meeting' ),
					'bp_zoom_webinar_nonce'         => wp_create_nonce( 'bp_zoom_webinar' ),
					'delete_occurrence_confirm_str' => __( 'Are you sure you want to delete this occurrence?', 'buddyboss-pro' ),
					'webinar_enabled'               => bp_zoom_is_zoom_webinar_enabled(),
					'private_webinar'               => bb_zoom_is_webinar_hide_urls_enabled(),
					'block_zoom_meeting'            => __( 'Zoom Meeting', 'buddyboss-pro' ),
					'block_create_add_zoom'         => __( 'Create meeting or add existing meeting.', 'buddyboss-pro' ),
					'block_create_zoom_meeting'     => __( 'Create Meeting', 'buddyboss-pro' ),
					'block_create_meeting_in_zoom'  => __( 'Create meeting in Zoom', 'buddyboss-pro' ),
					'block_add_zoom_meeting'        => __( 'Add Existing Meeting', 'buddyboss-pro' ),
					'block_existing_meeting'        => __( 'Existing Meeting', 'buddyboss-pro' ),
					'block_zoom_save'               => __( 'Save', 'buddyboss-pro' ),
					'block_zoom_cancel'             => __( 'Cancel', 'buddyboss-pro' ),
					'block_zoom_meeting_id'         => __( 'Meeting ID', 'buddyboss-pro' ),
					'block_enter_meeting_id'        => __( 'Enter meeting ID without spaces…', 'buddyboss-pro' ),
					'block_meeting_synced'          => __( 'Meeting Synced.', 'buddyboss-pro' ),
					'block_meeting_updated'         => __( 'Meeting Updated.', 'buddyboss-pro' ),
					'block_zoom_sync'               => __( 'Sync', 'buddyboss-pro' ),
					'block_zoom_title'              => __( 'Title', 'buddyboss-pro' ),
					'block_zoom_when'               => __( 'When', 'buddyboss-pro' ),
					'block_zoom_timezone'           => __( 'Timezone', 'buddyboss-pro' ),
					'block_zoom_search_timezone'    => __( 'Search timezone', 'buddyboss-pro' ),
					'block_zoom_no_results'         => __( 'No results found', 'buddyboss-pro' ),
					'block_zoom_auto_recording'     => __( 'Auto Recording', 'buddyboss-pro' ),
					'block_zoom_no_recordings'      => __( 'No Recordings', 'buddyboss-pro' ),
					'block_zoom_local'              => __( 'Local', 'buddyboss-pro' ),
					'block_zoom_cloud'              => __( 'Cloud', 'buddyboss-pro' ),
					'block_zoom_save_meeting'       => __( 'Save Meeting', 'buddyboss-pro' ),
					'block_zoom_delete'             => __( 'Delete', 'buddyboss-pro' ),
					'block_zoom_sunday'             => __( 'Sunday', 'buddyboss-pro' ),
					'block_zoom_monday'             => __( 'Monday', 'buddyboss-pro' ),
					'block_zoom_tuesday'            => __( 'Tuesday', 'buddyboss-pro' ),
					'block_zoom_wednesday'          => __( 'Wednesday', 'buddyboss-pro' ),
					'block_zoom_thursday'           => __( 'Thursday', 'buddyboss-pro' ),
					'block_zoom_friday'             => __( 'Friday', 'buddyboss-pro' ),
					'block_zoom_saturday'           => __( 'Saturday', 'buddyboss-pro' ),
					'block_zoom_day'                => __( 'Day', 'buddyboss-pro' ),
					'block_zoom_days'               => __( 'Days', 'buddyboss-pro' ),
					'block_zoom_occures'            => __( 'Occures on', 'buddyboss-pro' ),
					'block_zoom_day_month'          => __( 'Day of the month', 'buddyboss-pro' ),
					'block_zoom_week_month'         => __( 'Week of the month', 'buddyboss-pro' ),
					'block_zoom_week_daily'         => __( 'Daily', 'buddyboss-pro' ),
					'block_zoom_week_weekly'        => __( 'Weekly', 'buddyboss-pro' ),
					'block_zoom_week_monthly'       => __( 'Monthly', 'buddyboss-pro' ),
					'block_zoom_of_month'           => __( 'of the month', 'buddyboss-pro' ),
					'block_zoom_first'              => __( 'First', 'buddyboss-pro' ),
					'block_zoom_second'             => __( 'Second', 'buddyboss-pro' ),
					'block_zoom_third'              => __( 'Third', 'buddyboss-pro' ),
					'block_zoom_fourth'             => __( 'Fourth', 'buddyboss-pro' ),
					'block_zoom_last'               => __( 'Last', 'buddyboss-pro' ),
					'block_zoom_occures_on'         => __( 'Occures on', 'buddyboss-pro' ),
					'block_zoom_occurrences'        => __( 'Occurrences', 'buddyboss-pro' ),
					'block_zoom_occurrences_low'    => __( 'occurences', 'buddyboss-pro' ),
					'block_zoom_occurrence_del'     => __( 'Occurrence Deleted.', 'buddyboss-pro' ),
					'block_zoom_edit'               => __( 'Edit', 'buddyboss-pro' ),
					'block_zoom_date'               => __( 'Date', 'buddyboss-pro' ),
					'block_zoom_end_by'             => __( 'End by', 'buddyboss-pro' ),
					'block_zoom_end_after'          => __( 'End After', 'buddyboss-pro' ),
					'block_zoom_webinar'            => __( 'Zoom Webinar', 'buddyboss-pro' ),
					'block_zoom_create_webinar'     => __( 'Create webinar in Zoom', 'buddyboss-pro' ),
					'block_zoom_create_add_webinar' => __( 'Create webinar or add existing webinar.', 'buddyboss-pro' ),
					'block_create_webinar'          => __( 'Create Webinar', 'buddyboss-pro' ),
					'block_existing_webinar'        => __( 'Existing Webinar', 'buddyboss-pro' ),
					'block_add_webinar'             => __( 'Add Existing Webinar', 'buddyboss-pro' ),
					'block_webinar_id'              => __( 'Webinar ID', 'buddyboss-pro' ),
					'block_enter_webinar_id'        => __( 'Enter webinar ID without spaces…', 'buddyboss-pro' ),
					'block_webinar_sync'            => __( 'Webinar Synced.', 'buddyboss-pro' ),
					'block_webinar_deleted'         => __( 'Webinar Deleted.', 'buddyboss-pro' ),
					'block_webinar_updated'         => __( 'Webinar Updated.', 'buddyboss-pro' ),
					'block_webinar_save'            => __( 'Save Webinar', 'buddyboss-pro' ),
					'block_zoom_duration'           => __( 'Duration (minutes)', 'buddyboss-pro' ),
					'block_zoom_settings'           => __( 'Settings', 'buddyboss-pro' ),
					'block_zoom_description'        => __( 'Description (optional)', 'buddyboss-pro' ),
					'block_zoom_passcode'           => __( 'Passcode (optional)', 'buddyboss-pro' ),
					'block_zoom_default_host'       => __( 'Default Host', 'buddyboss-pro' ),
					'block_zoom_alt_hosts'          => __( 'Alternative Hosts', 'buddyboss-pro' ),
					'block_zoom_example'            => __( 'Example: mary@company.com', 'buddyboss-pro' ),
					'block_zoom_email_enter'        => __( 'Entered by email, comma separated. Each email added needs to match with a user in your Zoom account.', 'buddyboss-pro' ),
					'block_zoom_start_video'        => __( 'Start video when host joins', 'buddyboss-pro' ),
					'block_zoom_start_video_par'    => __( 'Start video when participants join', 'buddyboss-pro' ),
					'block_zoom_require_reg'        => __( 'Require Registration', 'buddyboss-pro' ),
					'block_enable_practice_session' => __( 'Enable practice session', 'buddyboss-pro' ),
					'block_only_auth'               => __( 'Only authenticated users can join', 'buddyboss-pro' ),
					'block_enable_wait_room'        => __( 'Enable waiting room', 'buddyboss-pro' ),
					'block_mute_part'               => __( 'Mute participants upon entry', 'buddyboss-pro' ),
					'block_enable_join_before'      => __( 'Enable join before host', 'buddyboss-pro' ),
					'block_recurring_options'       => __( 'Recurring Options', 'buddyboss-pro' ),
					'block_recurring_webinar'       => __( 'Recurring Webinar', 'buddyboss-pro' ),
					'block_recurrence'              => __( 'Recurrence', 'buddyboss-pro' ),
					'block_recurring_meeting'       => __( 'Recurring Meeting', 'buddyboss-pro' ),
					'block_repeat_every'            => __( 'Repeat every', 'buddyboss-pro' ),
					'block_att_any'                 => __( 'Attendees register once and can attend any of the occurrences', 'buddyboss-pro' ),
					'block_att_each'                => __( 'Attendees need to register for each occurrence to attend', 'buddyboss-pro' ),
					'block_att_choose'              => __( 'Attendees register once and can choose one or more occurrences to attend', 'buddyboss-pro' ),
					'block_meeting_deleted'         => __( 'Meeting Deleted.', 'buddyboss-pro' ),
				)
			);
		}

		/**
		 * Get all registred post types.
		 *
		 * @return array Array of registered post types.
		 * @since 1.0.0
		 */
		public function get_registered_post_types() {
			$post_types = get_post_types( array( 'public' => true ), 'objects' );

			$registered_post_types = array();
			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $slug => $post_type ) {

					// Ignore attachment post type.
					if ( 'attachment' === $slug ) {
						continue;
					}

					$registered_post_types[ $slug ] = $post_type->label;
				}
			}

			return $registered_post_types;
		}

		/**
		 * Register meeting block to post types.
		 *
		 * @param array $post_types Post types.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function bp_block_category_post_types( $post_types = array() ) {

			$registered_post_types = $this->get_registered_post_types();
			if ( ! empty( $registered_post_types ) ) {
				$registered_post_types = array_keys( $registered_post_types );

				$post_types = array_unique(
					array_merge(
						$post_types,
						$registered_post_types
					)
				);
			}

			return $post_types;
		}

		/**
		 * Render meeting block on front end.
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content Content of block.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function render_meeting_block( $attributes, $content ) {
			global $bp_zoom_meeting_block;
			if ( empty( $attributes['meetingId'] ) || is_admin() ) {
				return $content;
			}

			$meeting_id            = $attributes['meetingId'];
			$bp_zoom_meeting_block = bb_zoom_get_meeting_block( $meeting_id, true );

			if ( empty( $bp_zoom_meeting_block ) ) {
				return $content;
			}

			$bp_zoom_meeting_block->block_class_name = $attributes['className'] ?? '';

			ob_start();
			bp_get_template_part( 'zoom/blocks/meeting-block' );
			$content = ob_get_clean();

			$bp_zoom_meeting_block = false;

			return $content;
		}

		/**
		 * Render meeting shortcode on front end.
		 *
		 * @param array $attributes Block attributes.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function render_meeting_shortcode( $attributes ) {
			global $bp_zoom_meeting_block;

			$args = shortcode_atts(
				array(
					'id' => false,
				),
				$attributes
			);

			if ( empty( $args['id'] ) || is_admin() ) {
				return false;
			}

			$meeting_id            = $args['id'];
			$bp_zoom_meeting_block = bb_zoom_get_meeting_block( $meeting_id );

			ob_start();
			bp_get_template_part( 'zoom/blocks/meeting-block' );
			$content = ob_get_clean();

			$bp_zoom_meeting_block = false;

			return $content;
		}

		/**
		 * Delete occurrence of meeting.
		 *
		 * @since 1.0.4
		 */
		public function zoom_meeting_block_delete_occurrence() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$meeting_id    = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-occurrence-id' );

			$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id, $occurrence_id );

			if ( isset( $meeting_deleted['code'] ) && 204 === $meeting_deleted['code'] ) {
				delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
				delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				wp_send_json_success(
					array(
						'deleted' => true,
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
				)
			);
		}

		/**
		 * Delete meeting from block.
		 *
		 * @since 1.0.4
		 */
		public function zoom_meeting_block_delete_meeting() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$meeting_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );

			if ( bb_zoom_is_meeting_deleted( $meeting_id ) ) {
				wp_send_json_success(
					array(
						'deleted' => true,
					)
				);
			}

			$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id );

			if ( isset( $meeting_deleted['code'] ) && 204 === $meeting_deleted['code'] ) {
				delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
				delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				wp_send_json_success(
					array(
						'deleted' => true,
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
				)
			);
		}

		/**
		 * Update occurrence of meeting.
		 *
		 * @since 1.0.4
		 */
		public function zoom_meeting_block_update_occurrence() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$meeting_id           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			$occurrence_id        = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-occurrence-id' );
			$start_time           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-start-time' );
			$timezone             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-timezone' );
			$duration             = filter_input( INPUT_POST, 'bp-zoom-meeting-duration', FILTER_VALIDATE_INT );
			$auto_recording       = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-recording' );
			$alternative_host_ids = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-alt-host-ids' );
			$join_before_host     = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video           = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video   = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants    = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room         = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$enforce_login        = filter_input( INPUT_POST, 'bp-zoom-meeting-authentication', FILTER_VALIDATE_BOOLEAN );

			$alternative_host_ids = str_replace( ', ', ',', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_time = new DateTime( $start_time, new DateTimeZone( $timezone ) );
			$start_time = $start_time->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'meeting_id'             => $meeting_id,
				'host_id'                => $host_id,
				'start_date'             => $start_time,
				'duration'               => $duration,
				'join_before_host'       => $join_before_host,
				'host_video'             => $host_video,
				'participants_video'     => $participants_video,
				'mute_participants'      => $mute_participants,
				'waiting_room'           => $waiting_room,
				'meeting_authentication' => $enforce_login,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
			);

			$zoom_meeting = bp_zoom_conference()->update_meeting_occurrence( $occurrence_id, $data );

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 201, 204 ), true ) ) {
				delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
				delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				wp_send_json_success();
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 404, 400, 429 ), true ) ) {
				$response_error = array( 'error' => $zoom_meeting['response']->message );

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom meeting add in API.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_block_add() {
			$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

			if ( ! bp_is_post_request() ) {
				wp_send_json_error( $response_error );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting' ) ) {
				wp_send_json_error( $response_error );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$auto_recording       = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-recording' );
			$alternative_host_ids = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-alt-host-ids' );
			$title                = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-title', array( FILTER_FLAG_NO_ENCODE_QUOTES ) );
			$description          = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-description', array( FILTER_FLAG_NO_ENCODE_QUOTES ) );
			$meeting_id           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-zoom-id' );
			$start_date           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-start-date' );
			$duration             = filter_input( INPUT_POST, 'bp-zoom-meeting-duration', FILTER_VALIDATE_INT );
			$timezone             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-timezone' );
			$password             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-password' );
			$approval_type        = filter_input( INPUT_POST, 'bp-zoom-meeting-registration', FILTER_VALIDATE_BOOLEAN );
			$registration_type    = filter_input( INPUT_POST, 'bp-zoom-meeting-registration-type', FILTER_VALIDATE_INT );
			$join_before_host     = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video           = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video   = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants    = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room         = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$enforce_login        = filter_input( INPUT_POST, 'bp-zoom-meeting-authentication', FILTER_VALIDATE_BOOLEAN );
			$type                 = filter_input( INPUT_POST, 'bp-zoom-meeting-type', FILTER_VALIDATE_INT );
			$recurrence           = filter_input( INPUT_POST, 'bp-zoom-meeting-recurrence', FILTER_VALIDATE_INT );
			$end_time_select      = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-end-time-select' );

			$alternative_host_ids = str_replace( ', ', ',', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_date         = new DateTime( $start_date, new DateTimeZone( $timezone ) );
			$start_meeting_time = $start_date->format( 'H:i:s' );
			$start_date         = $start_date->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'host_id'                => $host_id,
				'start_date_utc'         => $start_date,
				'timezone'               => bb_zoom_get_remote_allowed_timezone( $timezone ),
				'duration'               => $duration,
				'password'               => $password,
				'registration'           => $approval_type,
				'join_before_host'       => $join_before_host,
				'host_video'             => $host_video,
				'participants_video'     => $participants_video,
				'mute_participants'      => $mute_participants,
				'waiting_room'           => $waiting_room,
				'meeting_authentication' => $enforce_login,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'title'                  => $title,
				'description'            => $description,
			);

			$recurrence_obj = array();
			if ( 8 === $type ) {
				$recurrence_obj['type'] = $recurrence;
				$repeat_interval        = filter_input( INPUT_POST, 'bp-zoom-meeting-repeat-interval', FILTER_VALIDATE_INT );

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
					$end_date_time = new DateTime( $end_date_time, new DateTimeZone( $timezone ) );
					$end_date_time = $end_date_time->format( 'Y-m-d' );
					$end_date_time = new DateTime( $end_date_time . ' ' . $start_meeting_time, new DateTimeZone( $timezone ) );
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

				$data['type']              = $type;
				$data['recurrence']        = $recurrence_obj;
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

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 201, 204 ), true ) ) {
				if ( ! empty( $zoom_meeting['response'] ) && null !== $zoom_meeting['response'] ) {
					delete_transient( 'bp_zoom_meeting_block_' . $zoom_meeting['response']->id );
					delete_transient( 'bp_zoom_meeting_invitation_' . $zoom_meeting['response']->id );

					if ( ! empty( $zoom_meeting['response']->occurrences ) ) {
						foreach ( $zoom_meeting['response']->occurrences as $o_key => $occurrence ) {
							$zoom_meeting['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
						}
					}

					if ( ! empty( $zoom_meeting['response']->recurrence->end_date_time ) ) {
						$zoom_meeting['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $zoom_meeting['response']->recurrence->end_date_time, $timezone, true );
					}

					wp_send_json_success(
						array(
							'meeting' => $zoom_meeting['response'],
						)
					);
				}

				delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
				delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );

				$meeting_info = bp_zoom_conference()->get_meeting_info( $meeting_id );

				if ( ! empty( $meeting_info['response']->occurrences ) ) {
					foreach ( $meeting_info['response']->occurrences as $o_key => $occurrence ) {
						$meeting_info['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
					}
				}

				if ( ! empty( $meeting_info['response']->recurrence->end_date_time ) ) {
					$meeting_info['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $meeting_info['response']->recurrence->end_date_time, $timezone, true );
				}

				wp_send_json_success(
					array(
						'meeting' => $meeting_info['response'],
					)
				);
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 404, 400, 429 ), true ) ) {
				$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

				if ( ! empty( $zoom_meeting['response']->message ) ) {
					$response_error = array( 'error' => $zoom_meeting['response']->message );
				}

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_error( $response_error );
		}

		/**
		 * Update meeting from block or from zoom dashboard in to the site.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_sync() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$meeting_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-meeting-id' );

			if ( empty( $meeting_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide Meeting ID.', 'buddyboss-pro' ) ) );
			}

			$meeting_info = bp_zoom_conference()->get_meeting_info( $meeting_id );

			if ( ! empty( $meeting_info['code'] ) && 200 === $meeting_info['code'] && ! empty( $meeting_info['response'] ) ) {
				$host_id = $meeting_info['response']->host_id;

				$user_info = bp_zoom_conference()->get_user_info( $host_id );

				$host_name  = '';
				$host_email = '';
				if ( 200 === $user_info['code'] && ! empty( $user_info['response'] ) ) {
					if ( ! empty( $user_info['response']->first_name ) ) {
						$host_name .= $user_info['response']->first_name;
					}
					if ( ! empty( $user_info['response']->last_name ) ) {
						$host_name .= ' ' . $user_info['response']->last_name;
					}

					if ( empty( $host_name ) && ! empty( $user_info['response']->email ) ) {
						$host_name                         = $user_info['response']->email;
						$host_email                        = $user_info['response']->email;
						$meeting_info['response']->host_id = $host_email;
					}
				}

				$timezone = $meeting_info['response']->timezone;

				if ( ! empty( $meeting_info['response']->occurrences ) && ! empty( $meeting_info['response']->created_at ) ) {
					$start_time = bp_zoom_convert_date_time( $meeting_info['response']->created_at, $timezone, true );
				} else {
					$start_time = bp_zoom_convert_date_time( $meeting_info['response']->start_time, $timezone, true );
				}

				if ( ! empty( $meeting_info['response']->occurrences ) ) {
					foreach ( $meeting_info['response']->occurrences as $o_key => $occurrence ) {
						$meeting_info['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
					}
					foreach ( $meeting_info['response']->occurrences as $occurrence ) {
						if ( 'deleted' !== $occurrence->status ) {
							$start_time = $occurrence->start_time;
							break;
						}
					}
				}

				$meeting_info['response']->start_time = $start_time;
				$meeting_info['response']->timezone   = bb_zoom_get_server_allowed_timezone( $timezone );

				if ( ! empty( $meeting_info['response']->recurrence->end_date_time ) ) {
					$meeting_info['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $meeting_info['response']->recurrence->end_date_time, $timezone, true );
				}

				// Delete transients for meeting.
				delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
				delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );

				wp_send_json_success(
					array(
						'meeting'    => $meeting_info['response'],
						'host_name'  => $host_name,
						'host_email' => $host_email,
					)
				);
			}

			if ( ! empty( $meeting_info['code'] ) && in_array( $meeting_info['code'], array( 400, 404, 429 ), true ) ) {
				wp_send_json_error( array( 'error' => $meeting_info['response']->message ) );
			}

			wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Render webinar block on front end.
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content Content of block.
		 *
		 * @return string
		 * @since 1.0.9
		 */
		public function render_webinar_block( $attributes, $content ) {
			global $bp_zoom_webinar_block;
			if ( empty( $attributes['webinarId'] ) || is_admin() ) {
				return $content;
			}

			$webinar_id            = $attributes['webinarId'];
			$bp_zoom_webinar_block = bb_zoom_get_webinar_block( $webinar_id );

			if ( empty( $bp_zoom_webinar_block ) ) {
				return $content;
			}

			$bp_zoom_webinar_block->block_class_name = $attributes['className'] ?? '';

			ob_start();
			bp_get_template_part( 'zoom/blocks/webinar-block' );
			$content = ob_get_clean();

			$bp_zoom_webinar_block = false;

			return $content;
		}

		/**
		 * Render webinar shortcode on front end.
		 *
		 * @param array $attributes Block attributes.
		 *
		 * @return string
		 * @since 1.0.9
		 */
		public function render_webinar_shortcode( $attributes ) {
			global $bp_zoom_webinar_block;

			$args = shortcode_atts(
				array(
					'id' => false,
				),
				$attributes
			);

			if ( empty( $args['id'] ) || is_admin() ) {
				return false;
			}

			$webinar_id            = $args['id'];
			$bp_zoom_webinar_block = bb_zoom_get_webinar_block( $webinar_id );

			ob_start();
			bp_get_template_part( 'zoom/blocks/webinar-block' );
			$content = ob_get_clean();

			$bp_zoom_webinar_block = false;

			return $content;
		}

		/**
		 * Delete occurrence of webinar.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_block_delete_occurrence() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_webinar' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$webinar_id    = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			$occurrence_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-occurrence-id' );

			$webinar_deleted = bp_zoom_conference()->delete_webinar( $webinar_id, $occurrence_id );

			if ( isset( $webinar_deleted['code'] ) && 204 === $webinar_deleted['code'] ) {
				delete_transient( 'bp_zoom_webinar_block_' . $webinar_id );
				wp_send_json_success(
					array(
						'deleted' => true,
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
				)
			);
		}

		/**
		 * Delete webinar from block.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_block_delete_webinar() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_webinar' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$webinar_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );

			if ( bb_zoom_is_webinar_deleted( $webinar_id ) ) {
				wp_send_json_success(
					array(
						'deleted' => true,
					)
				);
			}

			$webinar_deleted = bp_zoom_conference()->delete_webinar( $webinar_id );

			if ( isset( $webinar_deleted['code'] ) && 204 === $webinar_deleted['code'] ) {
				delete_transient( 'bp_zoom_webinar_block_' . $webinar_id );
				wp_send_json_success(
					array(
						'deleted' => true,
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
				)
			);
		}

		/**
		 * Update occurrence of webinar.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_block_update_occurrence() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_webinar' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$webinar_id           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			$occurrence_id        = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-occurrence-id' );
			$start_time           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-start-time' );
			$timezone             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-timezone' );
			$duration             = filter_input( INPUT_POST, 'bp-zoom-webinar-duration', FILTER_VALIDATE_INT );
			$auto_recording       = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-recording' );
			$alternative_host_ids = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-alt-host-ids' );
			$host_video           = filter_input( INPUT_POST, 'bp-zoom-webinar-host-video', FILTER_VALIDATE_BOOLEAN );
			$panelists_video      = filter_input( INPUT_POST, 'bp-zoom-webinar-panelists-video', FILTER_VALIDATE_BOOLEAN );
			$practice_session     = filter_input( INPUT_POST, 'bp-zoom-webinar-practice-session', FILTER_VALIDATE_BOOLEAN );
			$on_demand            = filter_input( INPUT_POST, 'bp-zoom-webinar-on-demand', FILTER_VALIDATE_BOOLEAN );
			$enforce_login        = filter_input( INPUT_POST, 'bp-zoom-webinar-authentication', FILTER_VALIDATE_BOOLEAN );

			$alternative_host_ids = str_replace( ', ', ',', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_time = new DateTime( $start_time, new DateTimeZone( $timezone ) );
			$start_time = $start_time->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'webinar_id'             => $webinar_id,
				'host_id'                => $host_id,
				'start_date'             => $start_time,
				'duration'               => $duration,
				'host_video'             => $host_video,
				'panelists_video'        => $panelists_video,
				'practice_session'       => $practice_session,
				'on_demand'              => $on_demand,
				'meeting_authentication' => $enforce_login,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
			);

			$zoom_webinar = bp_zoom_conference()->update_webinar_occurrence( $occurrence_id, $data );

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 201, 204 ), true ) ) {
				delete_transient( 'bp_zoom_webinar_block_' . $webinar_id );
				wp_send_json_success();
			}

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 300, 404, 400, 429 ), true ) ) {
				$response_error = array( 'error' => $zoom_webinar['response']->message );

				if ( ! empty( $zoom_webinar['response']->errors ) ) {
					$response_error['errors'] = $zoom_webinar['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom webinar add in API.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_block_add() {
			$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

			if ( ! bp_is_post_request() ) {
				wp_send_json_error( $response_error );
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_webinar' ) ) {
				wp_send_json_error( $response_error );
			}

			$host_id = bb_zoom_account_email();

			// check user host.
			if ( empty( $host_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host Email in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$auto_recording       = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-recording' );
			$alternative_host_ids = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-alt-host-ids' );
			$title                = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-title' );
			$description          = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-description' );
			$webinar_id           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-zoom-id' );
			$start_date           = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-start-date' );
			$duration             = filter_input( INPUT_POST, 'bp-zoom-webinar-duration', FILTER_VALIDATE_INT );
			$timezone             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-timezone' );
			$password             = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-password' );
			$approval_type        = filter_input( INPUT_POST, 'bp-zoom-webinar-registration', FILTER_VALIDATE_BOOLEAN );
			$registration_type    = filter_input( INPUT_POST, 'bp-zoom-webinar-registration-type', FILTER_VALIDATE_INT );
			$host_video           = filter_input( INPUT_POST, 'bp-zoom-webinar-host-video', FILTER_VALIDATE_BOOLEAN );
			$panelists_video      = filter_input( INPUT_POST, 'bp-zoom-webinar-panelists-video', FILTER_VALIDATE_BOOLEAN );
			$practice_session     = filter_input( INPUT_POST, 'bp-zoom-webinar-practice-session', FILTER_VALIDATE_BOOLEAN );
			$on_demand            = filter_input( INPUT_POST, 'bp-zoom-webinar-on-demand', FILTER_VALIDATE_BOOLEAN );
			$enforce_login        = filter_input( INPUT_POST, 'bp-zoom-webinar-authentication', FILTER_VALIDATE_BOOLEAN );
			$type                 = filter_input( INPUT_POST, 'bp-zoom-webinar-type', FILTER_VALIDATE_INT );
			$recurrence           = filter_input( INPUT_POST, 'bp-zoom-webinar-recurrence', FILTER_VALIDATE_INT );
			$end_time_select      = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-end-time-select' );

			$alternative_host_ids = str_replace( ', ', ',', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the webinar duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_date         = new DateTime( $start_date, new DateTimeZone( $timezone ) );
			$start_webinar_time = $start_date->format( 'H:i:s' );
			$start_date         = $start_date->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'host_id'                => $host_id,
				'start_date_utc'         => $start_date,
				'timezone'               => bb_zoom_get_remote_allowed_timezone( $timezone ),
				'duration'               => $duration,
				'password'               => $password,
				'registration'           => $approval_type,
				'host_video'             => $host_video,
				'panelists_video'        => $panelists_video,
				'practice_session'       => $practice_session,
				'on_demand'              => $on_demand,
				'meeting_authentication' => $enforce_login,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'title'                  => $title,
				'description'            => $description,
				'type'                   => $type,
			);

			$recurrence_obj = array();
			if ( 9 === $type ) {
				$recurrence_obj['type'] = $recurrence;
				$repeat_interval        = filter_input( INPUT_POST, 'bp-zoom-webinar-repeat-interval', FILTER_VALIDATE_INT );

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
					$end_date_time = new DateTime( $end_date_time, new DateTimeZone( $timezone ) );
					$end_date_time = $end_date_time->format( 'Y-m-d' );
					$end_date_time = new DateTime( $end_date_time . ' ' . $start_webinar_time, new DateTimeZone( $timezone ) );
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

				$data['recurrence']        = $recurrence_obj;
				$data['registration_type'] = $registration_type;
			}

			if ( ! empty( $webinar_id ) ) {
				$data['webinar_id'] = $webinar_id;
				$zoom_webinar       = bp_zoom_conference()->update_webinar( $data );
			} else {
				$zoom_webinar = bp_zoom_conference()->create_webinar( $data );
			}

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 201, 204 ), true ) ) {
				if ( ! empty( $zoom_webinar['response'] ) && null !== $zoom_webinar['response'] ) {
					delete_transient( 'bp_zoom_webinar_block_' . $zoom_webinar['response']->id );

					if ( ! empty( $zoom_webinar['response']->occurrences ) ) {
						foreach ( $zoom_webinar['response']->occurrences as $o_key => $occurrence ) {
							$zoom_webinar['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
						}
					}

					if ( ! empty( $zoom_webinar['response']->recurrence->end_date_time ) ) {
						$zoom_webinar['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $zoom_webinar['response']->recurrence->end_date_time, $timezone, true );
					}

					wp_send_json_success(
						array(
							'webinar' => $zoom_webinar['response'],
						)
					);
				}

				delete_transient( 'bp_zoom_webinar_block_' . $webinar_id );

				$webinar_info = bp_zoom_conference()->get_webinar_info( $webinar_id, false, true );

				if ( ! empty( $webinar_info['response']->occurrences ) ) {
					foreach ( $webinar_info['response']->occurrences as $o_key => $occurrence ) {
						$webinar_info['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
					}
				}

				if ( ! empty( $webinar_info['response']->recurrence->end_date_time ) ) {
					$webinar_info['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $webinar_info['response']->recurrence->end_date_time, $timezone, true );
				}

				wp_send_json_success(
					array(
						'webinar' => $webinar_info['response'],
					)
				);
			}

			if ( ! empty( $zoom_webinar['code'] ) && in_array( $zoom_webinar['code'], array( 300, 404, 400, 429 ), true ) ) {
				$response_error = array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) );

				if ( ! empty( $zoom_webinar['response']->message ) ) {
					$response_error = array( 'error' => $zoom_webinar['response']->message );
				}

				if ( ! empty( $zoom_webinar['response']->errors ) ) {
					$response_error['errors'] = $zoom_webinar['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			wp_send_json_error( $response_error );
		}

		/**
		 * Update webinar from block or from zoom dashboard in to the site.
		 *
		 * @since 1.0.9
		 */
		public function zoom_webinar_sync() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_webinar' ) ) {
				wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
			}

			$webinar_id = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-webinar-id' );

			if ( empty( $webinar_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide Webinar ID.', 'buddyboss-pro' ) ) );
			}

			$webinar_info = bp_zoom_conference()->get_webinar_info( $webinar_id, false, true );

			if ( ! empty( $webinar_info['code'] ) && 200 === $webinar_info['code'] && ! empty( $webinar_info['response'] ) ) {
				$host_id = $webinar_info['response']->host_id;

				$user_info = bp_zoom_conference()->get_user_info( $host_id );

				$host_name  = '';
				$host_email = '';
				if ( 200 === $user_info['code'] && ! empty( $user_info['response'] ) ) {
					if ( ! empty( $user_info['response']->first_name ) ) {
						$host_name .= $user_info['response']->first_name;
					}
					if ( ! empty( $user_info['response']->last_name ) ) {
						$host_name .= ' ' . $user_info['response']->last_name;
					}

					if ( empty( $host_name ) && ! empty( $user_info['response']->email ) ) {
						$host_name                         = $user_info['response']->email;
						$host_email                        = $user_info['response']->email;
						$webinar_info['response']->host_id = $host_email;
					}
				}

				$timezone = $webinar_info['response']->timezone;

				if ( ! empty( $webinar_info['response']->occurrences ) && ! empty( $webinar_info['response']->created_at ) ) {
					$start_time = bp_zoom_convert_date_time( $webinar_info['response']->created_at, $timezone, true );
				} else {
					$start_time = bp_zoom_convert_date_time( $webinar_info['response']->start_time, $timezone, true );
				}

				if ( ! empty( $webinar_info['response']->occurrences ) ) {
					foreach ( $webinar_info['response']->occurrences as $o_key => $occurrence ) {
						$webinar_info['response']->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
					}
					foreach ( $webinar_info['response']->occurrences as $occurrence ) {
						if ( 'deleted' !== $occurrence->status ) {
							$start_time = $occurrence->start_time;
							break;
						}
					}
				}

				$webinar_info['response']->start_time = $start_time;
				$webinar_info['response']->timezone   = bb_zoom_get_server_allowed_timezone( $timezone );

				if ( ! empty( $webinar_info['response']->recurrence->end_date_time ) ) {
					$webinar_info['response']->recurrence->end_date_time = bp_zoom_convert_date_time( $webinar_info['response']->recurrence->end_date_time, $timezone, true );
				}

				// Delete transients for webinar.
				delete_transient( 'bp_zoom_webinar_block_' . $webinar_id );

				wp_send_json_success(
					array(
						'webinar'    => $webinar_info['response'],
						'host_name'  => $host_name,
						'host_email' => $host_email,
					)
				);
			}

			if ( ! empty( $webinar_info['code'] ) && in_array( $webinar_info['code'], array( 400, 404, 429 ), true ) ) {
				wp_send_json_error( array( 'error' => $webinar_info['response']->message ) );
			}

			wp_send_json_error( array( 'error' => __( 'Something went wrong. If passcode is entered then please make sure it matches Zoom Passcode requirements and try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom webhook handler for blocks.
		 *
		 * @since 2.3.91
		 */
		public function bb_zoom_block_webhook() {
			$zoom_webhook = filter_input( INPUT_GET, 'zoom_webhook', FILTER_VALIDATE_INT );
			$group_id     = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );

			if ( ! empty( $zoom_webhook ) && 1 === $zoom_webhook && empty( $group_id ) ) {
				$content = file_get_contents( 'php://input' );
				$json    = json_decode( $content, true );

				// Validate zoom webhook for blocks.
				BP_Zoom_Conference_Api::zoom_webhook_callback( $json );
			}
		}
	}
}
