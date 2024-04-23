<?php
/**
 * BP Zoom Conference API
 *
 * @package BuddyBossPro/Integration/Zoom
 */

use \BuddyBoss\Zoom\Firebase\JWT\JWT;

/**
 * Class Connecting Zoom API
 *
 * @since   1.0.0
 */
if ( ! class_exists( 'BP_Zoom_Conference_Api' ) ) {

	/**
	 * Class BP_Zoom_Conference_Api
	 */
	class BP_Zoom_Conference_Api {

		/**
		 * Instance of BP_Zoom_Conference_Api
		 *
		 * @var object
		 */
		protected static $instance;

		/**
		 * Zoom API URL
		 *
		 * @var string
		 */
		private $api_url = 'https://api.zoom.us/v2/';

		/**
		 * Zoom oauth token URL
		 *
		 * @var string
		 */
		private $api_oauth_token_url = 'https://zoom.us/oauth/token';

		/**
		 * Zoom API Account ID.
		 *
		 * @var string
		 */
		public $zoom_api_account_id;

		/**
		 * Zoom API Client ID.
		 *
		 * @var string
		 */
		public $zoom_api_client_id;

		/**
		 * Zoom API Client Secret.
		 *
		 * @var string
		 */
		public $zoom_api_client_secret;

		/**
		 * Zoom meeting SDK Client ID.
		 *
		 * @var mixed|string
		 */
		public $zoom_meeting_sdk_client_id;

		/**
		 * Zoom meeting SDK Client Secret.
		 *
		 * @var mixed|string
		 */
		public $zoom_meeting_sdk_client_secret;

		/**
		 * Group ID.
		 *
		 * @var int
		 */
		public static $group_id;

		/**
		 * Validate the JWT/S2S credentials when it's true.
		 *
		 * @var bool
		 */
		public static $is_validate = true;

		/**
		 * Create only one instance so that it may not Repeat
		 *
		 * @since 1.2.10
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * BP_Zoom_Conference_Api constructor.
		 *
		 * @param string $account_id    Zoom account ID.
		 * @param string $client_id     Zoom API client ID.
		 * @param string $client_secret Zoom client secret.
		 */
		public function __construct( $account_id = '', $client_id = '', $client_secret = '' ) {
			$this->zoom_api_account_id    = $account_id;
			$this->zoom_api_client_id     = $client_id;
			$this->zoom_api_client_secret = $client_secret;
		}

		/**
		 * Send Request.
		 *
		 * @param string $called_function Called function.
		 * @param array  $data            Data arguments.
		 * @param string $request         Type of request.
		 *
		 * @return array
		 */
		protected function send_request( $called_function, $data, $request = 'GET' ) {
			$request_url = $this->api_url . $called_function;

			$token = $this->get_access_token( $this->zoom_api_account_id, $this->zoom_api_client_id, $this->zoom_api_client_secret, self::$group_id );

			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
			);

			if ( 'POST' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'POST';
				$response       = wp_remote_post( $request_url, $args );
			} elseif ( 'DELETE' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'DELETE';
				$response       = wp_remote_request( $request_url, $args );
			} elseif ( 'PATCH' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'PATCH';
				$response       = wp_remote_request( $request_url, $args );
			} else {
				$args['body'] = ! empty( $data ) ? $data : array();
				$response     = wp_remote_get( $request_url, $args );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response      = wp_remote_retrieve_body( $response );
			$response_body = in_array( $response_code, array( 400, 401 ), true ) && $this->is_xml( $response ) ? simplexml_load_string( $response ) : json_decode( $response );
			if ( self::$is_validate && in_array( $response_code, array( 400, 401 ), true ) ) {
				$this->validate_s2s_settings(
					array(
						'account_id'    => $this->zoom_api_account_id,
						'client_id'     => $this->zoom_api_client_id,
						'client_secret' => $this->zoom_api_client_secret,
						'response'      => $response,
						'group_id'      => self::$group_id,
					)
				);
			}

			// Reset the variable.
			self::$is_validate = true;

			return array(
				'response' => json_decode( $response ),
				'code'     => $response_code,
				'body'     => $response_body,
			);
		}

		/**
		 * Creates a User
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Create user data.
		 *
		 * @return array|bool|string
		 */
		public function create_user( $data = array() ) {
			$args              = array();
			$args['action']    = $data['action'];
			$args['user_info'] = array(
				'email'      => $data['email'],
				'type'       => $data['type'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			);

			return $this->send_request( 'users', $args, 'POST' );
		}

		/**
		 * Updates a User
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Update user data.
		 *
		 * @return array|bool|string
		 */
		public function update_user( $data = array() ) {
			$args = array(
				'type'       => $data['type'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			);

			return $this->send_request( 'users/' . $data['user_id'], $args, 'PATCH' );
		}

		/**
		 * Get user list
		 *
		 * @since 1.0.0
		 *
		 * @param int $page Page number.
		 *
		 * @return array
		 */
		public function list_users( $page = 1 ) {
			$args                = array();
			$args['page_size']   = 300;
			$args['page_number'] = absint( $page );

			return $this->send_request( 'users', $args, 'GET' );
		}

		/**
		 * Get users info by user ID
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id User ID.
		 *
		 * @return array|bool|string
		 */
		public function get_user_info( $user_id ) {
			$args = array();

			return $this->send_request( 'users/' . $user_id, $args );
		}

		/**
		 * Delete a User
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id User ID.
		 *
		 * @return array|bool|string
		 */
		public function delete_user( $user_id ) {
			return $this->send_request( 'users/' . $user_id, array(), 'DELETE' );
		}

		/**
		 * Get Meetings
		 *
		 * @since 1.0.0
		 *
		 * @param string $host_id Host ID.
		 *
		 * @return array
		 */
		public function list_meetings( $host_id ) {
			$args              = array();
			$args['page_size'] = 300;

			return $this->send_request( 'users/' . $host_id . '/meetings', $args, 'GET' );
		}

		/**
		 * Create A meeting API
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Meeting data.
		 *
		 * @return array
		 */
		public function create_meeting( $data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $data['alternative_host_ids'] ) ) {
				if ( is_array( $data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $data['title'] ) ? $data['title'] : '';
			$args['agenda']     = ! empty( $data['description'] ) ? $data['description'] : '';
			$args['type']       = ! empty( $data['type'] ) ? $data['type'] : 2; // Scheduled.
			$args['start_time'] = ! empty( $data['start_date_utc'] ) ? $data['start_date_utc'] : '';
			$args['timezone']   = bb_zoom_get_remote_allowed_timezone( $data['timezone'] );
			$args['password']   = ! empty( $data['password'] ) ? $data['password'] : '';
			$args['duration']   = ! empty( $data['duration'] ) ? $data['duration'] : 60;
			if ( ! empty( $data['recurrence'] ) ) {
				$args['recurrence'] = $data['recurrence'];
			}
			$args['settings'] = array(
				'join_before_host'       => ! empty( $data['join_before_host'] ) ? true : false,
				'host_video'             => ! empty( $data['host_video'] ) ? true : false,
				'participant_video'      => ! empty( $data['participants_video'] ) ? true : false,
				'mute_upon_entry'        => ! empty( $data['mute_participants'] ) ? true : false,
				'approval_type'          => ! empty( $data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $data['meeting_authentication'] ) ? true : false,
				'waiting_room'           => ! empty( $data['waiting_room'] ) ? true : false,
				'auto_recording'         => ! empty( $data['auto_recording'] ) ? $data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $data['registration_type'] ) ? $data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'users/' . $data['host_id'] . '/meetings', $args, 'POST' );
		}

		/**
		 * Updating Meeting Info
		 *
		 * @since 1.0.0
		 *
		 * @param array $update_data Meeting data.
		 *
		 * @return array
		 */
		public function update_meeting( $update_data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $update_data['alternative_host_ids'] ) ) {
				if ( is_array( $update_data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $update_data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $update_data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $update_data['title'] ) ? $update_data['title'] : '';
			$args['agenda']     = ! empty( $update_data['description'] ) ? $update_data['description'] : '';
			$args['type']       = ! empty( $update_data['type'] ) ? $update_data['type'] : 2; // Scheduled.
			$args['start_time'] = ! empty( $update_data['start_date_utc'] ) ? $update_data['start_date_utc'] : '';
			$args['timezone']   = ! empty( $update_data['timezone'] ) ? bb_zoom_get_remote_allowed_timezone( $update_data['timezone'] ) : 'UTC';
			$args['password']   = ! empty( $update_data['password'] ) ? $update_data['password'] : '';
			$args['duration']   = ! empty( $update_data['duration'] ) ? $update_data['duration'] : 60;
			if ( ! empty( $update_data['recurrence'] ) ) {
				$args['recurrence'] = $update_data['recurrence'];
			}
			$args['settings'] = array(
				'join_before_host'       => ! empty( $update_data['join_before_host'] ) ? true : false,
				'host_video'             => ! empty( $update_data['host_video'] ) ? true : false,
				'participant_video'      => ! empty( $update_data['participants_video'] ) ? true : false,
				'mute_upon_entry'        => ! empty( $update_data['mute_participants'] ) ? true : false,
				'approval_type'          => ! empty( $update_data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $update_data['meeting_authentication'] ) ? true : false,
				'waiting_room'           => ! empty( $update_data['waiting_room'] ) ? true : false,
				'auto_recording'         => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $update_data['registration_type'] ) ? $update_data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'meetings/' . $update_data['meeting_id'], $args, 'PATCH' );
		}

		/**
		 * Updating Meeting Occurrence Info
		 *
		 * @since 1.0.4
		 *
		 * @param int   $occurrence_id Occurrence ID.
		 * @param array $update_data   Occurrence update data.
		 *
		 * @return array
		 */
		public function update_meeting_occurrence( $occurrence_id, $update_data = array() ) {
			$args = array();

			$post_time          = $update_data['start_date'];
			$start_time         = gmdate( 'Y-m-d\TH:i:s', strtotime( $post_time ) );
			$args['start_time'] = $start_time;

			if ( isset( $update_data['duration'] ) ) {
				$args['duration'] = $update_data['duration'];
			}

			if ( isset( $update_data['description'] ) ) {
				$args['agenda'] = $update_data['description'];
			}

			$args['settings'] = array(
				'join_before_host'  => ! empty( $update_data['join_before_host'] ),
				'host_video'        => ! empty( $update_data['host_video'] ),
				'participant_video' => ! empty( $update_data['participants_video'] ),
				'mute_upon_entry'   => ! empty( $update_data['mute_participants'] ),
				'waiting_room'      => ! empty( $update_data['waiting_room'] ),
				'auto_recording'    => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
			);

			return $this->send_request( 'meetings/' . $update_data['meeting_id'] . '/?occurrence_id=' . $occurrence_id, $args, 'PATCH' );
		}

		/**
		 * Get a Meeting Info
		 *
		 * @since 1.0.0
		 *
		 * @param int      $meeting_id                Meeting ID.
		 * @param int|bool $occurrence_id             Occurrence ID.
		 * @param bool     $show_previous_occurrences Whether to show previous occurrences.
		 *
		 * @return array
		 */
		public function get_meeting_info( $meeting_id, $occurrence_id = false, $show_previous_occurrences = false ) {
			$args      = array();
			$query_url = '';

			if ( ! empty( $occurrence_id ) ) {
				$query_url .= '/?occurrence_id=' . $occurrence_id;
			} elseif ( ! empty( $show_previous_occurrences ) ) {
				$query_url .= '/?show_previous_occurrences=' . true;
			}

			return $this->send_request( 'meetings/' . $meeting_id . $query_url, $args, 'GET' );
		}

		/**
		 * Delete A Meeting
		 *
		 * @since 1.0.0
		 *
		 * @param int      $meeting_id    Meeting ID.
		 * @param int|bool $occurrence_id Occurrence ID.
		 *
		 * @return array
		 */
		public function delete_meeting( $meeting_id, $occurrence_id = false ) {
			$args = array();

			$occurrence_url = '';
			if ( $occurrence_id ) {
				$occurrence_url = '/?occurrence_id=' . $occurrence_id;
			}

			return $this->send_request( 'meetings/' . $meeting_id . $occurrence_url, $args, 'DELETE' );
		}

		/**
		 * Create A webinar API
		 *
		 * @since 1.0.9
		 *
		 * @param array $data Webinar data.
		 *
		 * @return array
		 */
		public function create_webinar( $data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $data['alternative_host_ids'] ) ) {
				if ( is_array( $data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $data['title'] ) ? $data['title'] : '';
			$args['agenda']     = ! empty( $data['description'] ) ? $data['description'] : '';
			$args['type']       = ! empty( $data['type'] ) ? $data['type'] : 2; // Scheduled.
			$args['start_time'] = ! empty( $data['start_date_utc'] ) ? $data['start_date_utc'] : '';
			$args['timezone']   = bb_zoom_get_remote_allowed_timezone( $data['timezone'] );
			$args['password']   = ! empty( $data['password'] ) ? $data['password'] : '';
			$args['duration']   = ! empty( $data['duration'] ) ? $data['duration'] : 60;
			if ( ! empty( $data['recurrence'] ) ) {
				$args['recurrence'] = $data['recurrence'];
			}
			$args['settings'] = array(
				'host_video'             => ! empty( $data['host_video'] ) ? true : false,
				'panelists_video'        => ! empty( $data['panelists_video'] ) ? true : false,
				'practice_session'       => ! empty( $data['practice_session'] ) ? true : false,
				'on_demand'              => ! empty( $data['on_demand'] ) ? true : false,
				'approval_type'          => ! empty( $data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $data['meeting_authentication'] ) ? true : false,
				'auto_recording'         => ! empty( $data['auto_recording'] ) ? $data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $data['registration_type'] ) ? $data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'users/' . $data['host_id'] . '/webinars', $args, 'POST' );
		}

		/**
		 * Updating Webinar Info
		 *
		 * @since 1.0.9
		 *
		 * @param array $update_data Webinar data.
		 *
		 * @return array
		 */
		public function update_webinar( $update_data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $update_data['alternative_host_ids'] ) ) {
				if ( is_array( $update_data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $update_data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $update_data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $update_data['title'] ) ? $update_data['title'] : '';
			$args['agenda']     = ! empty( $update_data['description'] ) ? $update_data['description'] : '';
			$args['type']       = ! empty( $update_data['type'] ) ? $update_data['type'] : 2; // Scheduled.
			$args['start_time'] = ! empty( $update_data['start_date_utc'] ) ? $update_data['start_date_utc'] : '';
			$args['timezone']   = ! empty( $update_data['timezone'] ) ? bb_zoom_get_remote_allowed_timezone( $update_data['timezone'] ) : 'UTC';
			$args['password']   = ! empty( $update_data['password'] ) ? $update_data['password'] : '';
			$args['duration']   = ! empty( $update_data['duration'] ) ? $update_data['duration'] : 60;
			if ( ! empty( $update_data['recurrence'] ) ) {
				$args['recurrence'] = $update_data['recurrence'];
			}
			$args['settings'] = array(
				'host_video'             => ! empty( $update_data['host_video'] ) ? true : false,
				'panelists_video'        => ! empty( $update_data['panelists_video'] ) ? true : false,
				'practice_session'       => ! empty( $update_data['practice_session'] ) ? true : false,
				'on_demand'              => ! empty( $update_data['on_demand'] ) ? true : false,
				'approval_type'          => ! empty( $update_data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $update_data['meeting_authentication'] ) ? true : false,
				'auto_recording'         => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $update_data['registration_type'] ) ? $update_data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'webinars/' . $update_data['webinar_id'], $args, 'PATCH' );
		}

		/**
		 * Get a webinar Info
		 *
		 * @since 1.0.9
		 *
		 * @param int      $webinar_id                Webinar ID.
		 * @param int|bool $occurrence_id             Occurrence ID.
		 * @param bool     $show_previous_occurrences Whether to show previous occurrences.
		 *
		 * @return array
		 */
		public function get_webinar_info( $webinar_id, $occurrence_id = false, $show_previous_occurrences = false ) {
			$args      = array();
			$query_url = '';

			if ( ! empty( $occurrence_id ) ) {
				$query_url .= '/?occurrence_id=' . $occurrence_id;
			} elseif ( ! empty( $show_previous_occurrences ) ) {
				$query_url .= '/?show_previous_occurrences=' . true;
			}

			return $this->send_request( 'webinars/' . $webinar_id . $query_url, $args, 'GET' );
		}

		/**
		 * Delete A webinar
		 *
		 * @since 1.0.9
		 *
		 * @param int      $webinar_id    Webinar ID.
		 * @param int|bool $occurrence_id Occurrence ID.
		 *
		 * @return array
		 */
		public function delete_webinar( $webinar_id, $occurrence_id = false ) {
			$args = array();

			$occurrence_url = '';
			if ( $occurrence_id ) {
				$occurrence_url = '/?occurrence_id=' . $occurrence_id;
			}

			return $this->send_request( 'webinars/' . $webinar_id . $occurrence_url, $args, 'DELETE' );
		}

		/**
		 * Updating Webinar Occurrence Info
		 *
		 * @since 1.0.9
		 *
		 * @param int   $occurrence_id Occurrence ID.
		 * @param array $update_data   Occurrence update data.
		 *
		 * @return array
		 */
		public function update_webinar_occurrence( $occurrence_id, $update_data = array() ) {
			$args = array();

			$post_time          = $update_data['start_date'];
			$start_time         = gmdate( 'Y-m-d\TH:i:s', strtotime( $post_time ) );
			$args['start_time'] = $start_time;

			if ( isset( $update_data['duration'] ) ) {
				$args['duration'] = $update_data['duration'];
			}

			if ( isset( $update_data['description'] ) ) {
				$args['agenda'] = $update_data['description'];
			}

			$args['settings'] = array(
				'host_video'      => ! empty( $update_data['host_video'] ),
				'panelists_video' => ! empty( $update_data['panelists_video'] ),
				'auto_recording'  => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
			);

			return $this->send_request( 'webinars/' . $update_data['webinar_id'] . '/?occurrence_id=' . $occurrence_id, $args, 'PATCH' );
		}

		/**
		 * Get daily account reports by month
		 *
		 * @since 1.0.0
		 *
		 * @param int $month Month for the report.
		 * @param int $year  Year for the report.
		 *
		 * @return bool|mixed
		 */
		public function get_daily_report( $month, $year ) {
			$args          = array();
			$args['year']  = $year;
			$args['month'] = $month;

			return $this->send_request( 'report/daily', $args, 'GET' );
		}

		/**
		 * Get Account Reports
		 *
		 * @since 1.0.0
		 *
		 * @param string $zoom_account_from start date yyyy-mm-dd.
		 * @param string $zoom_account_to   end date yyyy-mm-dd.
		 *
		 * @return array
		 */
		public function get_account_report( $zoom_account_from, $zoom_account_to ) {
			$args              = array();
			$args['from']      = $zoom_account_from;
			$args['to']        = $zoom_account_to;
			$args['page_size'] = 300;

			return $this->send_request( 'report/users', $args, 'GET' );
		}

		/**
		 * Register webiner participants
		 *
		 * @since 1.0.0
		 *
		 * @param int    $webinar_id Webinar ID.
		 * @param string $first_name First name.
		 * @param string $last_name  Last name.
		 * @param string $email      Email ID.
		 *
		 * @return mixed
		 */
		public function register_webinar_participants( $webinar_id, $first_name, $last_name, $email ) {
			$data               = array();
			$data['first_name'] = $first_name;
			$data['last_name']  = $last_name;
			$data['email']      = $email;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'POST' );
		}

		/**
		 * List webinars
		 *
		 * @since 1.0.0
		 *
		 * @param string $user_id User ID.
		 *
		 * @return bool|mixed
		 */
		public function list_webinar( $user_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'users/' . $user_id . '/webinars', $data, 'GET' );
		}

		/**
		 * List Webinar Participants
		 *
		 * @since 1.0.0
		 *
		 * @param int $webinar_id Webinar ID.
		 *
		 * @return bool|mixed
		 */
		public function list_webinar_participants( $webinar_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'GET' );
		}

		/**
		 * Get recording by meeting ID
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function recordings_by_meeting( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings', array(), 'GET' );
		}

		/**
		 * Get recording by meeting ID
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function recordings_by_webinar( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings', array(), 'GET' );
		}

		/**
		 * Get instances by meeting ID
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function meeting_instances( $meeting_id ) {
			return $this->send_request( 'past_meetings/' . $meeting_id . '/instances', array(), 'GET' );
		}

		/**
		 * Get instances by meeting ID
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function webinar_instances( $meeting_id ) {
			return $this->send_request( 'past_meetings/' . $meeting_id . '/instances', array(), 'GET' );
		}

		/**
		 * Get all recordings by USER ID
		 *
		 * @since 1.0.0
		 *
		 * @param string $host_id Host ID.
		 * @param array  $data    From and to dates.
		 *
		 * @return bool|mixed
		 */
		public function list_recording( $host_id, $data = array() ) {
			$post_data = array();
			$from      = gmdate( 'Y-m-d', strtotime( '-1 year', time() ) );
			$to        = gmdate( 'Y-m-d', time() );

			$post_data['from'] = ! empty( $data['from'] ) ? $data['from'] : $from;
			$post_data['to']   = ! empty( $data['to'] ) ? $data['to'] : $to;

			return $this->send_request( 'users/' . $host_id . '/recordings', $post_data, 'GET' );
		}

		/**
		 * Get meeting recording settings.
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function recording_settings( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings/settings', array(), 'GET' );
		}

		/**
		 * Get meeting invitation template.
		 *
		 * @since 1.0.0
		 *
		 * @param int $meeting_id Meeting ID.
		 *
		 * @return bool|mixed
		 */
		public function meeting_invitation( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/invitation', array(), 'GET' );
		}

		/**
		 * Get user settings.
		 *
		 * @since 1.0.9
		 *
		 * @param string $id     User id.
		 * @param string $option Query param.
		 *
		 * @return array
		 */
		public function get_user_settings( $id, $option = '' ) {
			$args = array();
			if ( in_array( $option, array( 'meeting_authentication', 'recording_authentication', 'meeting_security' ), true ) ) {
				$args = array( $option );
			}

			return $this->send_request( 'users/' . $id . '/settings', $args, 'GET' );
		}

		/**
		 * Generate zoom access token.
		 *
		 * @since 2.3.91
		 *
		 * @param string $zoom_api_account_id    Account ID.
		 * @param string $zoom_api_client_id     Client ID.
		 * @param string $zoom_api_client_secret Client Secret.
		 *
		 * @return array|object|WP_Error
		 */
		private function generate_access_token( $zoom_api_account_id, $zoom_api_client_id, $zoom_api_client_secret ) {

			if ( empty( $zoom_api_account_id ) ) {
				return new \WP_Error( 'Account ID', 'Account ID is missing' );
			} elseif ( empty( $zoom_api_client_id ) ) {
				return new \WP_Error( 'Client ID', 'Client ID is missing' );
			} elseif ( empty( $zoom_api_client_secret ) ) {
				return new \WP_Error( 'Client Secret', 'Client Secret is missing' );
			}

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$base64_encoded = base64_encode( $zoom_api_client_id . ':' . $zoom_api_client_secret );
			$result         = new \WP_Error( 0, 'Something went wrong' );

			$args = array(
				'method'  => 'POST',
				'headers' => array(
					'Host'          => 'zoom.us',
					'Authorization' => "Basic $base64_encoded",
				),
				'body'    => array(
					'grant_type' => 'account_credentials',
					'account_id' => $zoom_api_account_id,
				),
			);

			$response              = wp_safe_remote_post( $this->api_oauth_token_url, $args );
			$response_code         = wp_remote_retrieve_response_code( $response );
			$response_message      = wp_remote_retrieve_response_message( $response );
			$decoded_response_body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 200 === $response_code ) {
				if ( ! empty( $decoded_response_body->access_token ) ) {
					$result = $decoded_response_body;
				} elseif ( ! empty( $decoded_response_body->errorCode ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$result = new \WP_Error( $decoded_response_body->errorCode, $decoded_response_body->errorMessage ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			} else {
				$error  = ! empty( $decoded_response_body->error ) ? $decoded_response_body->error : '';
				$reason = ! empty( $decoded_response_body->reason ) ? $decoded_response_body->reason : '';
				$result = new \WP_Error(
					$response_code,
					$response_message,
					array(
						'error'  => $error,
						'reason' => $reason,
					)
				);
			}

			return $result;
		}

		/**
		 * Get zoom access token.
		 *
		 * @since 2.3.91
		 *
		 * @param string $zoom_api_account_id    Account ID.
		 * @param string $zoom_api_client_id     Client ID.
		 * @param string $zoom_api_client_secret Client Secret.
		 * @param int    $group_id               Group ID. Default 0.
		 *
		 * @return string
		 */
		public function get_access_token( $zoom_api_account_id, $zoom_api_client_id, $zoom_api_client_secret, $group_id = 0 ) {
			$transient_key = $zoom_api_account_id . '_' . $zoom_api_client_id . '_' . $zoom_api_client_secret;
			if ( ! empty( $group_id ) ) {
				$transient_key = $transient_key . '_' . $group_id;
			}

			$transient_key = md5( $transient_key );

			// Get token from transient.
			$token = get_transient( $transient_key );

			// If token available then return.
			if ( false !== $token ) {
				return $token;
			}

			$token_object = $this->generate_access_token( $zoom_api_account_id, $zoom_api_client_id, $zoom_api_client_secret );
			$token        = ! is_wp_error( $token_object ) ? $token_object->access_token : $token_object->get_error_message();

			// Set transient for 55 minutes. Zoom will expire token in 59 minutes.
			set_transient( $transient_key, $token, MINUTE_IN_SECONDS * 55 );

			return $token;
		}

		/**
		 * Get zoom meeting SDK signature.
		 *
		 * @since 2.3.91
		 *
		 * @param string $sdk_client_key Client Key.
		 * @param string $sdk_secret_key Secret key.
		 * @param int    $meeting_number Meeting ID.
		 * @param int    $role           Role ID.
		 *
		 * @return string|bool
		 */
		public function generate_sdk_signature( $sdk_client_key, $sdk_secret_key, $meeting_number, $role ) {

			// If a client key is not passed, get this->zoom_meeting_sdk_client_id.
			if ( empty( $sdk_client_key ) ) {
				$sdk_client_key = $this->zoom_meeting_sdk_client_id;
			}

			// If a secret key is not passed, get this->zoom_meeting_sdk_client_secret.
			if ( empty( $sdk_secret_key ) ) {
				$sdk_secret_key = $this->zoom_meeting_sdk_client_secret;
			}

			if ( empty( $sdk_client_key ) || empty( $sdk_secret_key ) ) {
				return false;
			}

			// Get validation from transient.
			$is_validation_exists = get_transient( 'bb-zoom-meeting-sdk-validate' );

			// If validation is not available.
			if ( false === $is_validation_exists ) {
				$is_validate = $this->bb_zoom_validate_meeting_sdk( $sdk_client_key, $sdk_secret_key );
				set_transient( 'bb-zoom-meeting-sdk-validate', $is_validate, MINUTE_IN_SECONDS * 30 );
				if ( true !== $is_validate ) {
					// Get settings.
					$settings = bb_get_zoom_block_settings();

					// Update SDK settings.
					$settings['zoom_sdk_is_connected'] = false;
					$settings['zoom_sdk_errors']       = array();
					if ( is_wp_error( $is_validate ) ) {
						$settings['zoom_sdk_errors'][] = $is_validate;
					}
					bp_update_option( 'bb-zoom', $settings );

					return false;
				}
			} elseif ( is_wp_error( $is_validation_exists ) ) {
				return false;
			}

			$iat     = round( ( time() * 1000 - 30000 ) / 1000 );
			$exp     = $iat + 86400;
			$payload = array(
				'sdkKey'   => $sdk_client_key,
				'mn'       => $meeting_number,
				'role'     => $role,
				'iat'      => $iat,
				'exp'      => $exp,
				'appKey'   => $sdk_client_key,
				'tokenExp' => $exp,
			);

			return JWT::encode( $payload, $sdk_secret_key, 'HS256' );
		}

		/**
		 * Validate the S2S credential when updated the settings from the Zoom account.
		 *
		 * @since 2.3.91
		 *
		 * @param array $args Array of s2s credentials.
		 */
		private function validate_s2s_settings( $args ) {
			$settings = bp_parse_args(
				$args,
				array(
					'account_id'    => $this->zoom_api_account_id,
					'client_id'     => $this->zoom_api_client_id,
					'client_secret' => $this->zoom_api_client_secret,
					'response'      => '',
					'group_id'      => self::$group_id,
				)
			);

			$token_object = $this->generate_access_token( $settings['account_id'], $settings['client_id'], $settings['client_secret'] );

			if ( is_wp_error( $token_object ) ) {
				if ( ! empty( $settings['group_id'] ) && 'group' === bb_zoom_group_get_connection_type( $settings['group_id'] ) ) {
					$app_settings = groups_get_groupmeta( $settings['group_id'], 'bb-group-zoom' );
				} else {
					$app_settings = bb_get_zoom_block_settings();
				}

				if ( empty( $app_settings ) ) {
					$app_settings = array();
				}

				$decoded_response = ! empty( $settings['response'] ) ? json_decode( $settings['response'] ) : new stdClass();

				$app_settings['zoom_is_connected'] = false;
				$app_settings['zoom_errors']       = array();
				if ( ! empty( $decoded_response->message ) ) {
					$app_settings['zoom_errors'][] = new WP_Error( 'api_error', $decoded_response->message );
				} else {
					$app_settings['zoom_errors'][] = new WP_Error( 'api_error', __( 'Invalid credentials.', 'buddyboss-pro' ) );
				}

				// Update the settings.
				if ( ! empty( $settings['group_id'] ) && 'group' === bb_zoom_group_get_connection_type( $settings['group_id'] ) ) {
					groups_update_groupmeta( $settings['group_id'], 'bb-group-zoom', $app_settings );
					groups_delete_groupmeta( $settings['group_id'], 'bb-zoom-account-emails' );
					groups_delete_groupmeta( $settings['group_id'], 'bb-group-zoom-s2s-api-email' );
				} else {
					$app_settings['account-email'] = '';
					bp_update_option( 'bb-zoom', $app_settings );
					bp_delete_option( 'bb-zoom-account-emails' );
				}

				// Delete the access token for S2S.
				$transient_key = $settings['account_id'] . '_' . $settings['client_id'] . '_' . $settings['client_secret'];
				if ( ! empty( $settings['group_id'] ) ) {
					$transient_key = $transient_key . '_' . $settings['group_id'];
				}

				$transient_key = md5( $transient_key );

				// Delete token from transient.
				delete_transient( $transient_key );
			}
		}

		/**
		 * Zoom webhook handler for blocks and groups.
		 *
		 * @since 2.3.91
		 *
		 * @param array $json     Array of data.
		 * @param int   $group_id Group ID.
		 *
		 * @return false|void
		 */
		public static function zoom_webhook_callback( $json, $group_id = 0 ) {
			$event  = ! empty( $json['event'] ) ? $json['event'] : '';
			$object = isset( $json['payload']['object'] ) ? $json['payload']['object'] : array();

			if ( empty( $event ) ) {
				return false;
			}

			if (
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				0 < $group_id &&
				! empty( groups_get_group( $group_id ) )
			) {

				$group_token     = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-secret-token' );
				$connection_type = bb_zoom_group_get_connection_type( $group_id );
				if ( empty( $group_token ) ) {
					if ( 'site' === $connection_type ) {
						$group_token = bb_zoom_secret_token();
					} else {
						$group_token = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-secret-token' );
					}
				}

				if ( empty( trim( $group_token ) ) ) {
					self::forbid( 'No token detected' );
				}

				if ( 'endpoint.url_validation' === $event && ! empty( $json['payload']['plainToken'] ) ) {
					self::zoom_url_validate( $json, $group_token );
				}

				if ( ! bp_zoom_is_group_setup( $group_id ) ) {
					return;
				}

				if (
					! empty( $object ) &&
					in_array(
						$event,
						array(
							'meeting.started',
							'meeting.ended',
							'meeting.updated',
							'meeting.deleted',
							'recording.completed',
						),
						true
					)
				) {
					$zoom_meeting_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_meeting    = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_id );
					$meeting         = false;

					if ( ! empty( $zoom_meeting ) ) {
						$meeting = new BP_Zoom_Meeting( $zoom_meeting->id );

						if ( empty( $meeting->id ) ) {
							self::forbid( 'No meeting detected' );
						}
					}

					if ( empty( $meeting ) ) {
						self::forbid( 'No meeting detected' );
					}

					if ( $meeting->group_id !== $group_id ) {
						self::forbid( 'This meeting does not belong to group provided' );
					}

					switch ( $event ) {
						case 'meeting.started':
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'started' );

							// Recurring meeting than check occurrences dates and update those as well and remove parent's status.
							if ( 8 === $meeting->type ) {
								$occurrences = bp_zoom_meeting_get(
									array(
										'parent' => $meeting->meeting_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['meetings'] ) ) {
									foreach ( $occurrences['meetings'] as $occurrence ) {
										$zoom_meeting_occurrence = new BP_Zoom_Meeting( $occurrence );
										$occurrence_date         = new DateTime( $zoom_meeting_occurrence->start_date_utc );
										$occurrence_date->setTimezone( wp_timezone() );
										if ( $occurrence_date->format( 'Y-m-d' ) === wp_date( 'Y-m-d', strtotime( 'now' ) ) ) {
											bp_zoom_meeting_update_meta( $occurrence, 'meeting_status', 'started' );
											bp_zoom_meeting_delete_meta( $meeting->id, 'meeting_status' );
											break;
										}
									}
								}
							}
							break;

						case 'meeting.ended':
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'ended' );

							// Recurring meeting than check occurrences and remove their status.
							if ( 8 === $meeting->type ) {
								$occurrences = bp_zoom_meeting_get(
									array(
										'parent' => $meeting->meeting_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['meetings'] ) ) {
									foreach ( $occurrences['meetings'] as $occurrence ) {
										bp_zoom_meeting_delete_meta( $occurrence, 'meeting_status' );
									}
								}
							}
							break;

						case 'meeting.deleted':
							if ( ! empty( $object['occurrences'] ) ) {
								foreach ( $object['occurrences'] as $occurrence ) {
									bp_zoom_meeting_delete( array( 'meeting_id' => $occurrence['occurrence_id'] ) );
								}
							} else {
								bp_zoom_meeting_delete( array( 'id' => $meeting->id ) );
							}
							break;

						case 'meeting.updated':
							$meeting->save();
							break;
						case 'recording.completed':
							if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
								break;
							}
							$password        = ! empty( $object['password'] ) ? $object['password'] : '';
							$recording_files = ! empty( $object['recording_files'] ) ? $object['recording_files'] : array();
							$start_time      = ! empty( $object['start_time'] ) ? $object['start_time'] : '';
							if ( ! empty( $recording_files ) ) {
								foreach ( $recording_files as $recording_file ) {
									$recording_id = ( isset( $recording_file['id'] ) ? $recording_file['id'] : '' );
									if ( ! empty( $recording_id ) && empty( bp_zoom_recording_get( array(), array( 'recording_id' => $recording_id ) ) ) ) {
										bp_zoom_recording_add(
											array(
												'recording_id' => $recording_id,
												'meeting_id' => $zoom_meeting_id,
												'uuid'     => $object['uuid'],
												'details'  => $recording_file,
												'password' => $password,
												'file_type' => $recording_file['file_type'],
												'start_time' => $start_time,
											)
										);
									}
								}

								$count = bp_zoom_recording_get(
									array(),
									array(
										'meeting_id' => $zoom_meeting_id,
									)
								);

								bp_zoom_meeting_update_meta( $meeting->id, 'zoom_recording_count', (int) count( $count ) );
							}
							break;
					}
				}

				if (
					! empty( $object ) &&
					in_array(
						$event,
						array(
							'webinar.started',
							'webinar.ended',
							'webinar.updated',
							'webinar.deleted',
							'recording.completed',
						),
						true
					)
				) {
					$zoom_webinar_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_webinar    = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_id );
					$webinar         = false;

					if ( ! empty( $zoom_webinar ) ) {
						$webinar = new BP_Zoom_Webinar( $zoom_webinar->id );

						if ( empty( $webinar->id ) ) {
							self::forbid( 'No webinar detected' );
						}
					}

					if ( empty( $webinar ) ) {
						self::forbid( 'No webinar detected' );
					}

					if ( $webinar->group_id !== $group_id ) {
						self::forbid( 'This webinar does not belong to group provided' );
					}

					switch ( $event ) {
						case 'webinar.started':
							bp_zoom_webinar_update_meta( $webinar->id, 'webinar_status', 'started' );

							// Recurring webinar than check occurrences dates and update those as well and remove parent's status.
							if ( 9 === $webinar->type ) {
								$occurrences = bp_zoom_webinar_get(
									array(
										'parent' => $webinar->webinar_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['webinars'] ) ) {
									foreach ( $occurrences['webinars'] as $occurrence ) {
										$zoom_webinar_occurrence = new BP_Zoom_Webinar( $occurrence );
										$occurrence_date         = new DateTime( $zoom_webinar_occurrence->start_date_utc );
										$occurrence_date->setTimezone( wp_timezone() );
										if ( $occurrence_date->format( 'Y-m-d' ) === wp_date( 'Y-m-d', strtotime( 'now' ) ) ) {
											bp_zoom_webinar_update_meta( $occurrence, 'webinar_status', 'started' );
											bp_zoom_webinar_delete_meta( $webinar->id, 'webinar_status' );
											break;
										}
									}
								}
							}
							break;

						case 'webinar.ended':
							bp_zoom_webinar_update_meta( $webinar->id, 'webinar_status', 'ended' );

							// Recurring webinar than check occurrences and remove their status.
							if ( 8 === $webinar->type ) {
								$occurrences = bp_zoom_webinar_get(
									array(
										'parent' => $webinar->webinar_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['webinars'] ) ) {
									foreach ( $occurrences['webinars'] as $occurrence ) {
										bp_zoom_webinar_delete_meta( $occurrence, 'webinar_status' );
									}
								}
							}
							break;

						case 'webinar.deleted':
							if ( ! empty( $object['occurrences'] ) ) {
								foreach ( $object['occurrences'] as $occurrence ) {
									bp_zoom_webinar_delete( array( 'webinar_id' => $occurrence['occurrence_id'] ) );
								}
							} else {
								bp_zoom_webinar_delete( array( 'id' => $webinar->id ) );
							}
							break;

						case 'webinar.updated':
							$webinar->save();
							break;
						case 'recording.completed':
							if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
								break;
							}
							$password        = ! empty( $object['password'] ) ? $object['password'] : '';
							$recording_files = ! empty( $object['recording_files'] ) ? $object['recording_files'] : array();
							$start_time      = ! empty( $object['start_time'] ) ? $object['start_time'] : '';
							if ( ! empty( $recording_files ) ) {
								foreach ( $recording_files as $recording_file ) {
									$recording_id = ( isset( $recording_file['id'] ) ? $recording_file['id'] : '' );
									if ( ! empty( $recording_id ) && empty( bp_zoom_webinar_recording_get( array(), array( 'recording_id' => $recording_id ) ) ) ) {
										bp_zoom_webinar_recording_add(
											array(
												'recording_id' => $recording_id,
												'webinar_id' => $zoom_webinar_id,
												'uuid'     => $object['uuid'],
												'details'  => $recording_file,
												'password' => $password,
												'file_type' => $recording_file['file_type'],
												'start_time' => $start_time,
											)
										);
									}
								}

								$count = bp_zoom_webinar_recording_get(
									array(),
									array(
										'webinar_id' => $zoom_webinar_id,
									)
								);

								bp_zoom_webinar_update_meta( $webinar->id, 'zoom_recording_count', (int) count( $count ) );
							}
							break;
					}
				}
			} else {
				$token = bb_zoom_secret_token();

				if ( empty( trim( $token ) ) ) {
					self::forbid( 'No token detected' );
				}

				if (
					'endpoint.url_validation' === $event &&
					! empty( $json['payload']['plainToken'] )
				) {
					self::zoom_url_validate( $json, $token );
				}

				if ( ! bp_zoom_is_zoom_setup() ) {
					return;
				}

				$meeting_id = ! empty( $object['id'] ) ? $object['id'] : false;

				if (
					! empty( $meeting_id ) &&
					in_array( $event, array( 'meeting.updated', 'meeting.deleted' ), true )
				) {
					delete_transient( 'bp_zoom_meeting_block_' . $meeting_id );
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );

					self::update_zoom_meeting_blocks( $meeting_id, $event, 'meeting' );
				} elseif (
					! empty( $meeting_id ) &&
					in_array( $event, array( 'webinar.updated', 'webinar.deleted' ), true )
				) {
					delete_transient( 'bp_zoom_webinar_block_' . $meeting_id );

					self::update_zoom_meeting_blocks( $meeting_id, $event, 'webinar' );
				}
			}
		}

		/**
		 * Validate zoom webhook URL.
		 *
		 * @since 2.3.0
		 *
		 * @param array  $parameters  Webhook validate API request params.
		 * @param string $group_token zoom api webhook token.
		 */
		public static function zoom_url_validate( $parameters, $group_token ) {
			$plain_token     = $parameters['payload']['plainToken'];
			$encrypted_token = hash_hmac( 'sha256', $plain_token, $group_token );
			$retval          = array(
				'plainToken'     => $plain_token,
				'encryptedToken' => $encrypted_token,
			);

			// setup status code.
			http_response_code( 200 );

			echo wp_json_encode( $retval );

			// stop executing.
			exit;
		}

		/**
		 * Forbid zoom webhook.
		 *
		 * @since 1.0.0
		 *
		 * @param string $reason Reason to print on screen.
		 */
		public static function forbid( $reason ) {
			// format the error.
			$error = '=== ERROR: ' . $reason . " ===\n*** ACCESS DENIED ***\n";

			// forbid.
			http_response_code( 403 );

			echo esc_html( $error );

			// stop executing.
			exit;
		}

		/**
		 * Update zoom meeting blocks in the content.
		 *
		 * @since 2.3.91
		 *
		 * @param int    $meeting_id Meeting ID.
		 * @param string $event      Type of event.
		 * @param string $type       Type of meeting.
		 */
		public static function update_zoom_meeting_blocks( $meeting_id, $event, $type = 'meeting' ) {

			if ( empty( $meeting_id ) || empty( $event ) ) {
				return;
			}

			global $wpdb;

			$text_to_search    = 'wp:bp-zoom-meeting/create-meeting';
			$block_name        = 'bp-zoom-meeting/create-meeting';
			$attribute_id_name = 'meetingId';
			if ( 'meeting' !== $type ) {
				$text_to_search    = 'wp:bp-zoom-meeting/create-webinar';
				$block_name        = 'bp-zoom-meeting/create-webinar';
				$attribute_id_name = 'webinarId';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$block_query = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts} WHERE ( post_content LIKE %s OR post_excerpt LIKE %s ) AND post_status = %s",
					'%' . $wpdb->esc_like( $text_to_search ) . '%',
					'%' . $wpdb->esc_like( $text_to_search ) . '%',
					'publish'
				)
			);

			if ( ! empty( $block_query ) ) {
				foreach ( $block_query as $zoom_block_post ) {
					$parsed_blocks = parse_blocks( $zoom_block_post->post_content );

					$manipulated_blocks = array();
					$is_update_content  = false;
					if ( ! empty( $parsed_blocks ) ) {
						foreach ( $parsed_blocks as $block ) {
							if (
								$block_name === $block['blockName'] &&
								! empty( $block['attrs'] ) &&
								! empty( $block['attrs'][ $attribute_id_name ] ) &&
								$meeting_id === $block['attrs'][ $attribute_id_name ]
							) {
								if ( 'meeting' === $type ) {
									$manipulated_blocks[] = self::bb_zoom_sync_zoom_meeting_block( $block, $meeting_id );
								} else {
									$manipulated_blocks[] = self::bb_zoom_sync_zoom_webinar_block( $block, $meeting_id );
								}

								$is_update_content = true;
							} else {
								$manipulated_blocks[] = $block;
							}
						}

						if ( $is_update_content && ! empty( $manipulated_blocks ) ) {
							$final_block_content = serialize_blocks( array_filter( $manipulated_blocks ) );

							// Update the post content with zoom block.
							wp_update_post(
								array(
									'ID'           => $zoom_block_post->ID,
									'post_content' => $final_block_content,
								)
							);
						}
					}
				}
			}
		}

		/**
		 * Sync zoom meeting blocks in the content.
		 *
		 * @since 2.3.91
		 *
		 * @param array $block      Block array.
		 * @param int   $meeting_id Meeting ID.
		 *
		 * @return array $block Block array.
		 */
		public static function bb_zoom_sync_zoom_meeting_block( $block = array(), $meeting_id = 0 ) {
			if ( empty( $block ) || empty( $meeting_id ) ) {
				return $block;
			}

			$map_fields = array(
				'topic'    => 'title',
				'agenda'   => 'description',
				'duration' => 'duration',
				'settings' => array(
					'alternative_hosts'      => 'alt_hosts',
					'approval_type'          => 'registration',
					'registration_type'      => 'registration_type',
					'host_video'             => 'hostVideo',
					'participant_video'      => 'participantsVideo',
					'join_before_host'       => 'joinBeforeHost',
					'mute_upon_entry'        => 'muteParticipants',
					'waiting_room'           => 'waitingRoom',
					'meeting_authentication' => 'authentication',
					'auto_recording'         => 'autoRecording',
				),
			);

			$meeting_info = bp_zoom_conference()->get_meeting_info( $meeting_id );

			if ( isset( $meeting_info['code'] ) && 200 === $meeting_info['code'] ) {
				$meeting   = $meeting_info['response'];
				$form_type = $block['attrs']['meetingFormType'];

				// Re-initialize the attributes.
				$block['attrs'] = array(
					'meetingId'       => $meeting_id,
					'meetingFormType' => $form_type,
					'hostId'          => $meeting->host_id,
				);

				foreach ( $map_fields as $res_field => $block_field ) {
					if ( isset( $meeting->$res_field ) ) {
						if ( 'duration' === $res_field ) {
							$block['attrs'][ $block_field ] = "{$meeting->$res_field}";
						} elseif ( 'settings' === $res_field ) {
							foreach ( $block_field as $setting_res_field => $setting_block_field ) {
								if ( isset( $meeting->$res_field->$setting_res_field ) ) {

									if (
										'approval_type' === $setting_res_field &&
										0 === $meeting->$res_field->$setting_res_field
									) {
										$block['attrs'][ $setting_block_field ] = true;
									} elseif (
										'auto_recording' === $setting_res_field &&
										'none' !== $meeting->$res_field->$setting_res_field
									) {
										$block['attrs'][ $setting_block_field ] = $meeting->$res_field->$setting_res_field;
									} else {
										$block['attrs'][ $setting_block_field ] = $meeting->$res_field->$setting_res_field;
									}
								}
							}
						} else {
							$block['attrs'][ $block_field ] = $meeting->$res_field;
						}
					}
				}

				$timezone = $meeting->timezone;
				if ( ! empty( $meeting->occurrences ) && ! empty( $meeting->created_at ) ) {
					$start_time = bp_zoom_convert_date_time( $meeting->created_at, $timezone, true );
				} else {
					$start_time = bp_zoom_convert_date_time( $meeting->start_time, $timezone, true );
				}

				if ( ! empty( $meeting->recurrence ) ) {
					$block['attrs']['recurring'] = true;

					if ( ! empty( $meeting->recurrence->type ) ) {
						$block['attrs']['recurrence'] = (int) $meeting->recurrence->type;
					}

					if ( ! empty( $meeting->recurrence->end_times ) ) {
						$block['attrs']['end_time_select'] = 'times';
						$block['attrs']['end_times']       = (int) $meeting->recurrence->end_times;
					}

					if ( ! empty( $meeting->recurrence->end_date_time ) ) {
						$block['attrs']['end_time_select'] = 'date';
						$block['attrs']['end_date_time']   = $meeting->recurrence->end_date_time;
					}

					if ( ! empty( $meeting->recurrence->repeat_interval ) ) {
						$block['attrs']['repeat_interval'] = $meeting->recurrence->repeat_interval;
					}

					if ( ! empty( $meeting->recurrence->weekly_days ) ) {
						$block['attrs']['weekly_days'] = explode( ',', $meeting->recurrence->weekly_days );
					}

					if ( ! empty( $meeting->recurrence->monthly_day ) ) {
						$block['attrs']['monthly_day']       = (int) $meeting->recurrence->monthly_day;
						$block['attrs']['monthly_occurs_on'] = 'day';
					}

					if ( ! empty( $meeting->recurrence->monthly_week ) ) {
						$block['attrs']['monthly_week']      = (int) $meeting->recurrence->monthly_week;
						$block['attrs']['monthly_occurs_on'] = 'week';
					}

					if ( ! empty( $meeting->recurrence->monthly_week_day ) ) {
						$block['attrs']['monthly_week_day']  = (int) $meeting->recurrence->monthly_week_day;
						$block['attrs']['monthly_occurs_on'] = 'week';
					}

					if ( ! empty( $meeting->occurrences ) ) {
						$block['attrs']['duration'] = "{$meeting->occurrences[0]->duration}";

						foreach ( $meeting->occurrences as $o_key => $occurrence ) {
							$meeting->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
						}

						foreach ( $meeting->occurrences as $o_meeting ) {
							if ( 'deleted' !== $o_meeting->status ) {
								$start_time                 = $o_meeting->start_time;
								$block['attrs']['duration'] = "{$o_meeting->duration}";
								break;
							}
						}
					}
					$block['attrs']['occurrences'] = $meeting->occurrences;
				}

				$block['attrs']['startDate'] = $start_time;
				$block['attrs']['timezone']  = bb_zoom_get_server_allowed_timezone( $timezone );
			} else {
				$block = array();
			}

			return $block;
		}

		/**
		 * Sync zoom webinar blocks in the content.
		 *
		 * @since 2.3.91
		 *
		 * @param array $block      Block array.
		 * @param int   $webinar_id Webinar ID.
		 *
		 * @return array $block Block array.
		 */
		public static function bb_zoom_sync_zoom_webinar_block( $block = array(), $webinar_id = 0 ) {
			if ( empty( $block ) || empty( $webinar_id ) ) {
				return $block;
			}

			$map_fields = array(
				'topic'    => 'title',
				'agenda'   => 'description',
				'duration' => 'duration',
				'settings' => array(
					'host_video'             => 'hostVideo',
					'auto_recording'         => 'autoRecording',
					'panelists_video'        => 'panelistsVideo',
					'practice_session'       => 'practiceSession',
					'meeting_authentication' => 'authentication',
					'alternative_hosts'      => 'alt_hosts',
				),
			);

			$webinar_info = bp_zoom_conference()->get_webinar_info( $webinar_id );

			if ( isset( $webinar_info['code'] ) && 200 === $webinar_info['code'] ) {
				$webinar   = $webinar_info['response'];
				$form_type = $block['attrs']['webinarFormType'];

				// Re-initialize the attributes.
				$block['attrs'] = array(
					'webinarId'       => $webinar_id,
					'webinarFormType' => $form_type,
					'hostId'          => $webinar->host_id,
				);

				foreach ( $map_fields as $res_field => $block_field ) {
					if ( isset( $webinar->$res_field ) ) {
						if ( 'duration' === $res_field ) {
							$block['attrs'][ $block_field ] = "{$webinar->$res_field}";
						} elseif ( 'settings' === $res_field ) {
							foreach ( $block_field as $setting_res_field => $setting_block_field ) {
								if ( isset( $webinar->$res_field->$setting_res_field ) ) {

									if (
										'auto_recording' === $setting_res_field &&
										'none' !== $webinar->$res_field->$setting_res_field
									) {
										$block['attrs'][ $setting_block_field ] = $webinar->$res_field->$setting_res_field;
									} else {
										$block['attrs'][ $setting_block_field ] = $webinar->$res_field->$setting_res_field;
									}
								}
							}
						} else {
							$block['attrs'][ $block_field ] = $webinar->$res_field;
						}
					}
				}

				$timezone = $webinar->timezone;
				if ( ! empty( $webinar->occurrences ) && ! empty( $webinar->created_at ) ) {
					$start_time = bp_zoom_convert_date_time( $webinar->created_at, $timezone, true );
				} else {
					$start_time = bp_zoom_convert_date_time( $webinar->start_time, $timezone, true );
				}

				if ( ! empty( $webinar->recurrence ) ) {
					$block['attrs']['recurring'] = true;

					if ( ! empty( $webinar->recurrence->type ) ) {
						$block['attrs']['recurrence'] = (int) $webinar->recurrence->type;
					}

					if ( ! empty( $webinar->recurrence->repeat_interval ) ) {
						$block['attrs']['repeat_interval'] = $webinar->recurrence->repeat_interval;
					}

					if ( ! empty( $webinar->recurrence->end_times ) ) {
						$block['attrs']['end_time_select'] = 'times';
						$block['attrs']['end_times']       = (int) $webinar->recurrence->end_times;
					}

					if ( ! empty( $webinar->recurrence->end_date_time ) ) {
						$block['attrs']['end_time_select'] = 'date';
						$block['attrs']['end_date_time']   = $webinar->recurrence->end_date_time;
					}

					if ( ! empty( $webinar->recurrence->weekly_days ) ) {
						$block['attrs']['weekly_days'] = explode( ',', $webinar->recurrence->weekly_days );
					}

					if ( ! empty( $webinar->recurrence->monthly_day ) ) {
						$block['attrs']['monthly_day']       = (int) $webinar->recurrence->monthly_day;
						$block['attrs']['monthly_occurs_on'] = 'day';
					}

					if ( ! empty( $webinar->recurrence->monthly_week ) ) {
						$block['attrs']['monthly_week']      = (int) $webinar->recurrence->monthly_week;
						$block['attrs']['monthly_occurs_on'] = 'week';
					}

					if ( ! empty( $webinar->recurrence->monthly_week_day ) ) {
						$block['attrs']['monthly_week_day']  = (int) $webinar->recurrence->monthly_week_day;
						$block['attrs']['monthly_occurs_on'] = 'week';
					}

					if ( ! empty( $webinar->occurrences ) ) {
						$block['attrs']['duration'] = "{$webinar->occurrences[0]->duration}";

						foreach ( $webinar->occurrences as $o_key => $occurrence ) {
							$webinar->occurrences[ $o_key ]->start_time = bp_zoom_convert_date_time( $occurrence->start_time, $timezone, true );
						}

						foreach ( $webinar->occurrences as $o_meeting ) {
							if ( 'deleted' !== $o_meeting->status ) {
								$start_time                 = $o_meeting->start_time;
								$block['attrs']['duration'] = "{$o_meeting->duration}";
								break;
							}
						}
					}
					$block['attrs']['occurrences'] = $webinar->occurrences;
				}

				$block['attrs']['startDate'] = $start_time;
				$block['attrs']['timezone']  = bb_zoom_get_server_allowed_timezone( $timezone );
			} else {
				$block = array();
			}

			return $block;
		}

		/**
		 * Validate Meeting SDK Credentials.
		 *
		 * @since 2.3.91
		 *
		 * @param string $client_id     Meeting SDK client id.
		 * @param string $client_secret Meeting SDK client secret.
		 *
		 * @return bool|WP_Error
		 */
		public function bb_zoom_validate_meeting_sdk( $client_id, $client_secret ) {

			if (
				empty( $client_id ) ||
				empty( $client_secret )
			) {
				return false;
			}

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$base64_encoded = base64_encode( $client_id . ':' . $client_secret );

			$args = array(
				'method'  => 'POST',
				'headers' => array(
					'Host'          => 'zoom.us',
					'Authorization' => "Basic $base64_encoded",
				),
				'body'    => array(
					'grant_type' => 'client_credentials',
				),
			);

			$response              = wp_safe_remote_post( $this->api_oauth_token_url, $args );
			$response_code         = wp_remote_retrieve_response_code( $response );
			$decoded_response_body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 200 === $response_code && ! empty( $decoded_response_body->access_token ) ) {
				return true;
			} else {
				$error  = ! empty( $decoded_response_body->error ) ? $decoded_response_body->error : '';
				$reason = ! empty( $decoded_response_body->reason ) ? $decoded_response_body->reason : '';
				$result = new \WP_Error(
					$response_code,
					$reason,
					array(
						'error'  => $error,
						'reason' => $reason,
					)
				);
			}

			return $result;
		}

		/**
		 * Whether the string is XML.
		 *
		 * @since 2.3.91
		 *
		 * @param string $string string of data.
		 *
		 * @return bool
		 */
		public function is_xml( $string ) {
			// Check if the string starts with the XML declaration <?xml .
			return preg_match( '/^<\?xml/', $string ) === 1;
		}
	}

	/**
	 * Singleton class return.
	 *
	 * @return BP_Zoom_Conference_Api|object
	 */
	function bp_zoom_conference() {
		return BP_Zoom_Conference_Api::instance();
	}

	// setup zoom.
	if ( bb_zoom_is_s2s_connected() ) {
		bp_zoom_conference()->zoom_api_account_id    = bb_zoom_account_id();
		bp_zoom_conference()->zoom_api_client_id     = bb_zoom_client_id();
		bp_zoom_conference()->zoom_api_client_secret = bb_zoom_client_secret();
	}
}
