<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * API class
 *
 * This class manages public LearnDash app integration in Zapier.
 */
class LearnDash_Zapier_Api {
	/**
	 * Site API key
	 * @var string
	 */
	private static $api_key;

	/**
	 * Init class
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'generate_api_key' ) );

		self::$api_key = get_option( 'learndash_zapier_api_key' );

		// Create an endpoint
		add_action( 'init', array( __CLASS__, 'api_endpoint' ) );

		// Init triggers
		add_action( 'learndash_update_course_access', array( __CLASS__, 'init_trigger_enrolled_into_course' ), 10, 4 );
		add_action( 'ld_added_group_access', array( __CLASS__, 'init_trigger_enrolled_into_course_via_group' ), 10, 2 );
		add_action( 'ld_added_course_group_access', array( __CLASS__, 'init_trigger_enrolled_into_course_via_group_course_update' ), 10, 2 );

		add_action( 'ld_added_group_access', array( __CLASS__, 'init_trigger_enrolled_into_group' ), 10, 2 );
		add_action( 'learndash_group_completed', array( __CLASS__, 'init_trigger_group_completed' ), 10, 1 );
		add_action( 'learndash_course_completed', array( __CLASS__, 'init_trigger_course_completed' ), 20, 1 ); // Priority: 20, let learndash_course_completed_store_time() runs first
		add_action( 'learndash_lesson_completed', array( __CLASS__, 'init_trigger_lesson_completed' ), 10, 1 );
		add_action( 'learndash_topic_completed', array( __CLASS__, 'init_trigger_topic_completed' ), 10, 1 );
		// All quiz triggers: quiz_passed, quiz_failed, quiz_completed
		add_action( 'learndash_quiz_submitted', array( __CLASS__, 'init_trigger_quiz' ), 10, 2 );
		add_action( 'learndash_essay_all_quiz_data_updated', array( __CLASS__, 'init_trigger_quiz_from_graded_essay' ), 10, 4 );
		add_action( 'learndash_new_essay_submitted', array( __CLASS__, 'init_trigger_essay_submitted' ), 10, 2 );

		// Filter payload
		add_filter( 'learndash_zapier_api_payload', array( __CLASS__, 'filter_api_payload' ), 10, 1 );
		add_filter( 'learndash_zapier_api_action_payload', array( __CLASS__, 'filter_api_payload' ), 10, 1 );
	}

	public static function generate_api_key() {
		$api_key = get_option( 'learndash_zapier_api_key', false );

		if ( ! $api_key ) {
			$api_key = strtoupper( substr( str_shuffle( md5( home_url() . time() ) ), 0, 20 ) );

			update_option( 'learndash_zapier_api_key', $api_key, true );
		}
	}

	/**
	 * API endpoint to be used by public Zapier App
	 * @return void
	 */
	public static function api_endpoint() {
		if ( ! isset( $_GET['learndash-integration'] ) || $_GET['learndash-integration'] !== 'zapier' ) {
			return;
		}

		// Parse data and get site API key
		$request = array_map( 'sanitize_text_field', $_GET );
		$request = array_map( 'trim', $request );
		$payload = file_get_contents( 'php://input' );
		$payload = ! empty( $payload ) ? json_decode( $payload, true ) : array();
		array_walk_recursive(
			$payload,
			function( &$value ) {
				$value = sanitize_text_field( $value );
			}
		);

		$args = array_merge( $request, $payload );

		// Validate Site URL format
		if ( ! empty( $request['site_url'] ) ) {
			if ( ! preg_match( '/\/$/', $request['site_url'] ) ) {
				self::bail( __( 'Bad request: site URL needs to have trailing slash at the end of the URL.', 'learndash-zapier' ), 400 );
			}
		}

		// Authenticate
		if ( ! self::is_authenticated( $request ) ) {
			self::bail( __( 'Invalid API key', 'learndash-zapier' ), 401 );
		}

		if ( isset( $request['action'] ) ) {
			switch ( $request['action'] ) {
				// Dynamic fields
				case 'get_course_field':
					$response = self::get_course_field();
					self::bail( $response );
					break;

				case 'get_group_field':
					$response = self::get_group_field();
					self::bail( $response );
					break;

				// Polling triggers
				case 'get_course_list':
					$response = self::get_courses_list( $args );
					self::bail( $response );
					break;

				case 'get_group_list':
					$response = self::get_groups_list( $args );
					self::bail( $response );
					break;

				case 'get_lesson_list':
					$response = self::get_lessons_list( $args );
					self::bail( $response );
					break;

				case 'get_topic_list':
					$response = self::get_topics_list( $args );
					self::bail( $response );
					break;

				case 'get_quiz_list':
					$response = self::get_quizzes_list( $args );
					self::bail( $response );
					break;

				// Triggers
				case 'get_sample':
					// The response has to be wrapped in array because it's expected by Zapier
					$response = array( self::get_trigger_sample( $request['trigger'], array_merge( $payload, $request ) ) );
					self::bail( $response );
					break;

				case 'subscribe':
					$response = self::add_hook_subscription( $request['trigger'], $payload );
					self::bail( $response );
					break;

				case 'unsubscribe':
					self::remove_hook_subscription( $request['trigger'], $payload );
					break;

				// Actions
				case 'init_action':
					$response = self::init_action( $request['action_key'], $payload );
					self::bail( $response );
					break;

				default:
					self::bail( __( 'Unknown request parameters', 'learndash-zapier' ), 404 );
					break;
			}
		}

		self::bail( __( 'Success', 'learndash-zapier' ) );
	}

	/**
	 * Check if request is authenticated
	 * @param  array   $request Request params
	 * @return boolean          True if it's authenticated|false otherwise
	 */
	public static function is_authenticated( $request ) {
		if ( isset( $request['api_key'] ) && strtoupper( $request['api_key'] ) === strtoupper( self::$api_key ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Authenticate request
	 * @return void
	 */
	// public static function authenticate( $request ) {
	//     if ( strtoupper( $request['api_key'] ) === strtoupper( self::$api_key ) ) {
	//         $response = array(
	//             'status'  => 200,
	//             'message' => 'Success',
	//         );
	//         http_response_code( 200 );
	//     } else {
	//         $response = array(
	//             'status' => 404,
	//             'message' => 'Invalid API key'
	//         );
	//         http_response_code( 404 );
	//     }
	//     echo json_encode( $response );
	//     exit();
	// }

	/**
	 * Add hook subscription
	 *
	 * @param string $trigger Trigger key
	 * @param array  $payload Payload arguments
	 * @return void
	 */
	public static function add_hook_subscription( $trigger, $payload ) {
		// Parse trigger
		if ( $trigger === 'quiz_completed' ) {
			if ( ! empty( $payload['quiz_result'] ) && strtolower( $payload['quiz_result'] ) == 'passed' ) {
				$trigger = 'quiz_passed';
			} elseif ( ! empty( $payload['quiz_result'] ) && strtolower( $payload['quiz_result'] ) == 'failed' ) {
				$trigger = 'quiz_failed';
			}
		}

		$subscriptions = get_option( 'learndash_zapier_hook_subscriptions', array() );
		if ( ! is_array( $subscriptions ) ) {
			$subscriptions = array();
		}

		$last_key = ! empty( $subscriptions ) ? array_keys( $subscriptions )[ count( $subscriptions ) - 1 ] : -1;
		$current_key = ++$last_key;

		$hook_url = @$payload['hook_url'] ?? @$payload['hookUrl'];
		unset( $payload['hook_url'] );
		unset( $payload['hookUrl'] );

		// Only add unique hook URL to DB
		$hook_urls = array();
		if ( isset( $subscriptions[0]['hook_url'] ) ) {
			$hook_urls   = wp_list_pluck( $subscriptions, 'hook_url' );
		}
		$hook_urls_2 = array();
		if ( isset( $subscriptions[0]['hookUrl'] ) ) {
			$hook_urls_2 = wp_list_pluck( $subscriptions, 'hookUrl' );
		}

		$hook_urls_3 = array();
		foreach ( $subscriptions as $subscription ) {
			if ( ! empty( $subscription['payload']['hook_url'] ) || ! empty( $subscription['payload']['hookUrl'] ) ) {
				$hook_urls_3[] = $subscription['payload']['hook_url'] ?? $subscription['payload']['hookUrl'];
			}
		}

		$hook_urls = array_unique( array_merge( $hook_urls, $hook_urls_2, $hook_urls_3 ) );

		if ( in_array( $hook_url, $hook_urls ) ) {
			return array(
				'id' => array_search( $hook_url, $hook_urls ),
				'url' => $hook_url,
			);
		}

		$subscriptions[ $current_key ] = array(
			'trigger'   => $trigger,
			'hook_url'  => $hook_url,
			'payload'   => $payload,
		);

		update_option( 'learndash_zapier_hook_subscriptions', $subscriptions );

		return array(
			'id'  => $current_key,
			'url' => $hook_url,
		);
	}

	/**
	 * Remove hook subscription
	 *
	 * @param string $trigger Trigger key
	 * @param array  $payload Request payload
	 * @return void
	 */
	public static function remove_hook_subscription( $trigger, $payload ) {
		$subscriptions = get_option( 'learndash_zapier_hook_subscriptions', array() );

		unset( $subscriptions[ $payload['subscribe_id'] ] );

		update_option( 'learndash_zapier_hook_subscriptions', $subscriptions );
	}

	/**
	 * Trigger methods
	 */

	public static function init_trigger( $trigger, $payload = array() ) {
		$subscriptions = get_option( 'learndash_zapier_hook_subscriptions', array() );

		$hook_urls = array();
		$subscriptions = array_filter(
			$subscriptions,
			function( $subscription ) use ( $trigger, &$hook_urls, $payload ) {
				$hook_url = $subscription['hook_url'] ?? $subscription['payload']['hook_url'] ?? $subscription['payload']['hookUrl'] ?? false;

				if ( ! $hook_url ) {
					return false;
				}

				// Course filter
				if ( ! empty( $payload['course']->ID ) && ! empty( $subscription['payload']['courses_ids'] ) && ! in_array( $payload['course']->ID, $subscription['payload']['courses_ids'] ) ) {
					return false;
				}

				// Group filter
				if ( ! empty( $payload['group']->ID ) && ! empty( $subscription['payload']['groups_ids'] ) && ! in_array( $payload['group']->ID, $subscription['payload']['groups_ids'] ) ) {
					return false;
				}

				// Lesson filter
				if ( ! empty( $payload['lesson']->ID ) && ! empty( $subscription['payload']['lessons_ids'] ) && ! in_array( $payload['lesson']->ID, $subscription['payload']['lessons_ids'] ) ) {
					return false;
				}

				// Topic filter
				if ( ! empty( $payload['topic']->ID ) && ! empty( $subscription['payload']['topics_ids'] ) && ! in_array( $payload['topic']->ID, $subscription['payload']['topics_ids'] ) ) {
					return false;
				}

				// Quiz filter
				if ( ! empty( $payload['quiz']->ID ) && ! empty( $subscription['payload']['quizzes_ids'] ) && ! in_array( $payload['quiz']->ID, $subscription['payload']['quizzes_ids'] ) ) {
					return false;
				}

				// Only send to unique webhook URL
				if ( in_array( $hook_url, $hook_urls ) ) {
					return false;
				}

				$hook_urls[] = $hook_url;

				return $subscription['trigger'] === $trigger;
			}
		);

		foreach ( $subscriptions as $subscription ) {
			self::send_trigger( $subscription, $payload, $trigger );
		}
	}

	public static function send_trigger( $subscription, $payload, $trigger ) {
		if ( isset( $payload['user']->user_pass ) ) {
			unset( $payload['user']->user_pass );
		}

		/**
		 * Filter API payload data
		 *
		 * @var array  $payload
		 * @var array  $subscription
		 * @var string $trigger
		 */
		$payload = apply_filters( 'learndash_zapier_api_payload', $payload, $subscription, $trigger );

		// Get hook URL from specific hook_url key or original payload hookUrl key
		$hook_url = $subscription['hook_url'] ?? $subscription['payload']['hook_url'] ?? $subscription['payload']['hookUrl'] ?? false;

		if ( ! $hook_url ) {
			error_log( 'Zapier hook URL is not found for subscription: ' . print_r( $subscription, true ) );
			return;
		}

		$response = wp_remote_post(
			$hook_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'charset' => 'utf-8',
				),
				'body' => json_encode( $payload ),
			)
		);
	}

	/**
	 * Init trigger enrolled_into_course
	 *
	 * @param int   $user_id        ID of user who enroll
	 * @param int   $course_id      ID of course enrolled into
	 * @param array $access_list    List of users who have access to the course
	 * @param bool  $remove         True if remove user access from a course | false otherwise
	 */
	public static function init_trigger_enrolled_into_course( $user_id, $course_id, $access_list, $remove ) {
		if ( $remove ) {
			return;
		}

		$course_started_on = ld_course_access_from( $course_id, $user_id );
		$course_started_on = date( 'Y-m-d H:i:s', $course_started_on );

		$payload = array(
			'course' => self::get_response( 'course', $course_id ),
			'user' => self::get_response( 'user', $user_id ),
			'course_started_on' => self::get_response( 'course_started_on', $course_started_on ),
		);

		self::init_trigger( 'enrolled_into_course', $payload );
	}

	/**
	 * Init trigger enrolled_into_course via group
	 *
	 * @param int   $user_id        ID of user who enroll
	 * @param int   $group_id       ID of group enrolled into
	 */
	public static function init_trigger_enrolled_into_course_via_group( $user_id, $group_id ) {
		$group_courses = learndash_group_enrolled_courses( $group_id, true );

		if ( empty( $group_courses ) ) {
			return;
		}

		foreach ( $group_courses as $course_id ) {
			$course_started_on = ld_course_access_from( $course_id, $user_id );
			$course_started_on = date( 'Y-m-d H:i:s', $course_started_on );

			$payload = array(
				'course' => self::get_response( 'course', $course_id ),
				'user' => self::get_response( 'user', $user_id ),
				'course_started_on' => self::get_response( 'course_started_on', $course_started_on ),
			);

			self::init_trigger( 'enrolled_into_course', $payload );
		}
	}

	/**
	 * Init trigger enrolled_into_course via group's courses update
	 *
	 * @param int   $course_id      ID of new course
	 * @param int   $group_id       ID of group course added to
	 */
	public static function init_trigger_enrolled_into_course_via_group_course_update( $course_id, $group_id ) {
		$group_users = learndash_get_groups_users( $group_id, true );

		if ( empty( $group_users ) ) {
			return;
		}

		foreach ( $group_users as $user ) {
			$course_started_on = ld_course_access_from( $course_id, $user->ID );
			$course_started_on = date( 'Y-m-d H:i:s', $course_started_on );

			$payload = array(
				'course' => self::get_response( 'course', $course_id ),
				'user' => self::get_response( 'user', $user->ID ),
				'course_started_on' => self::get_response( 'course_started_on', $course_started_on ),
			);

			self::init_trigger( 'enrolled_into_course', $payload );
		}
	}

	/**
	 * Init trigger enrolled_into_group
	 *
	 * @param int   $user_id        ID of user who enroll
	 * @param int   $group_id       ID of group enrolled into
	 */
	public static function init_trigger_enrolled_into_group( $user_id, $group_id ) {
		$group_started_on = learndash_get_user_group_started_timestamp( $group_id, $user_id );
		$group_started_on = date( 'Y-m-d H:i:s', $group_started_on );

		$payload = array(
			'group' => self::get_response( 'group', $group_id ),
			'user' => self::get_response( 'user', $user_id ),
			'group_started_on' => self::get_response( 'group_started_on', $group_started_on ),
		);

		self::init_trigger( 'enrolled_into_group', $payload );
	}

	/**
	 * Init trigger group_completed
	 *
	 * @since  2.2.1 New trigger in LD Zapier app v1.2.0
	 * @param  array  $data Group completion data with keys:
	 *                      'user' (WP_User object)
	 *                      'group' (WP_Post group object)
	 *                      'progress' (array)
	 *                      'group_completed' => (int timestamp)
	 * @return void
	 */
	public static function init_trigger_group_completed( $data ) {
		$payload = array(
			'group' => self::get_response( 'group', $data['group']->ID ),
			'user' => self::get_response( 'user', $data['user']->ID ),
			'group_progress' => self::get_response( 'group_progress', $data['progress'] ),
			'group_started_on' => self::get_response( 'group_started_on', date( 'Y-m-d H:i:s', learndash_get_user_group_started_timestamp( $data['group']->ID, $data['user']->ID ) ) ),
			'group_completed_on' => self::get_response( 'group_completed_on', $data['group_completed'] ),
			'group_certificate_link' => self::get_response( 'certificate_link', learndash_get_group_certificate_link( $data['group']->ID, $data['user']->ID ) ),
		);

		self::init_trigger( 'group_completed', $payload );
	}

	/**
	 * Init trigger course_completed
	 *
	 * @param array $data Course data with keys:
	 *                    'user' (user object),
	 *                    'course' (post object),
	 *                    'progress' (array)
	 */
	public static function init_trigger_course_completed( $data ) {
		$course_started_on   = date( 'Y-m-d H:i:s', ld_course_access_from( $data['course']->ID, $data['user']->ID ) );
		$course_completed_on = date( 'Y-m-d H:i:s', learndash_user_get_course_completed_date( $data['user']->ID, $data['course']->ID ) );

		$payload = array(
			'user'     => self::get_response( 'user', $data['user']->ID ),
			'course'   => self::get_response( 'course', $data['course'] ),
			'progress' => self::get_response( 'course_progress', $data['progress'] ),
			'course_info' => self::get_response(
				'course_info',
				array(
					'user_id' => $data['user']->ID,
					'course_id' => $data['course']->ID,
				)
			),
			// Kept for backward compatibility
			'course_started_on' => self::get_response( 'course_started_on', $course_started_on ),
			'course_completed_on' => self::get_response( 'course_completed_on', $course_completed_on ),
			'course_certificate_link' => self::get_response( 'course_certificate_link', learndash_get_course_certificate_link( $data['course']->ID, $data['user']->ID ) ),
		);

		self::init_trigger( 'course_completed', $payload );
	}

	/**
	 * Init trigger lesson_completed
	 *
	 * @param array $data Lesson data with array keys:
	 *                    'user' (int),
	 *                    'course' (post object),
	 *                    'lesson' (post object),
	 *                    'progress' (array)
	 */
	public static function init_trigger_lesson_completed( $data ) {
		$payload = array(
			'user'     => self::get_response( 'user', $data['user']->ID ),
			'course'   => self::get_response( 'course', $data['course'] ),
			'lesson'   => self::get_response( 'lesson', $data['lesson'] ),
			'progress' => self::get_response( 'progress', $data['progress'] ),
		);

		self::init_trigger( 'lesson_completed', $payload );
	}

	/**
	 * Init trigger topic_completed
	 *
	 * @param array $data Topic data with array keys:
	 *                    'user' (int),
	 *                    'course' (post object),
	 *                    'lesson' (post object),
	 *                    'topic' (post object),
	 *                    'progress' (array)
	 */
	public static function init_trigger_topic_completed( $data ) {
		$payload = array(
			'user'     => self::get_response( 'user', $data['user']->ID ),
			'course'   => self::get_response( 'course', $data['course'] ),
			'lesson'   => self::get_response( 'lesson', $data['lesson'] ),
			'topic'    => self::get_response( 'topic', $data['topic'] ),
			'progress' => self::get_response( 'progress', $data['progress'] ),
		);

		self::init_trigger( 'topic_completed', $payload );
	}

	/**
	 * Init trigger quiz_passed, quiz_failed, quiz_completed
	 *
	 * @param array     $quiz_result    Data of the quiz result
	 * @param object    $user           User WP object who takes the quiz
	 */
	public static function init_trigger_quiz( $quiz_result, $user ) {
		$payload = array_merge(
			self::get_response( 'quiz_result', $quiz_result ),
			array( 'user' => self::get_response( 'user', $user ) )
		);

		// Init quiz_completed
		self::init_trigger( 'quiz_completed', $payload );

		if ( $quiz_result['has_graded'] ) {
			foreach ( $quiz_result['graded'] as $id => $essay ) {
				if ( $essay['status'] == 'not_graded' ) {
					return;
				}
			}
		}

		if ( $quiz_result['pass'] === 1 ) {
			// Init quiz_passed
			self::init_trigger( 'quiz_passed', $payload );
		} elseif ( $quiz_result['pass'] === 0 ) {
			// Init quiz_failed
			self::init_trigger( 'quiz_failed', $payload );
		}
	}

	/**
	 * Init trigger quiz_passed, quiz_failed after quiz essays are graded
	 *
	 * @param  int $quiz_id             Quiz ID
	 * @param  int $question_id         Question ID
	 * @param  object $updated_scoring  Essay object
	 * @param  object $essay            Submitted essay object
	 */
	public static function init_trigger_quiz_from_graded_essay( $quiz_id, $question_id, $updated_scoring, $essay ) {
		if ( $essay->post_status !== 'graded' ) {
			return;
		}

		$user_id      = $essay->post_author;
		$real_quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
		$course_id    = learndash_get_course_id( $real_quiz_id );
		$lesson_id    = learndash_get_lesson_id( $real_quiz_id );

		$user_quiz_result = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

		foreach ( $user_quiz_result as $quiz_result ) {
			if ( $quiz_id == $quiz_result['pro_quizid'] ) {
				if ( $quiz_result['has_graded'] ) {
					foreach ( $quiz_result['graded'] as $id => $essay ) {
						if ( $essay['status'] == 'not_graded' ) {
							return;
						}
					}
				}

				$payload = array_merge(
					self::get_response( 'quiz_result', $quiz_result ),
					array( 'user' => self::get_response( 'user', $user_id ) )
				);

				if ( $quiz_result['pass'] == 1 ) {
					// Init trigger quiz_passed
					self::init_trigger( 'quiz_passed', $payload );
				} elseif ( $quiz_result['pass'] == 0 ) {
					// Init trigger quiz_failed
					self::init_trigger( 'quiz_failed', $payload );
				}

				break;
			}
		}
	}

	/**
	 * Init trigger essay_submitted
	 *
	 * @param int   $essay_ID Essay ID
	 * @param array $args     Essay args
	 * @return void
	 */
	public static function init_trigger_essay_submitted( $essay_id, $args ) {
		$user = self::get_response( 'user', $args['post_author'] );

		$args['id'] = $essay_id;

		$payload = array(
			'user'  => self::get_response( 'user', $args['post_author'] ),
			'essay' => self::get_response( 'essay', $args ),
		);

		self::init_trigger( 'essay_submitted', $payload );
	}

	/**
	 * Add more information to payload data
	 *
	 * @param  array  $payload      Payload data
	 * @return array                Payload data
	 */
	public static function filter_api_payload( $payload ) {
		// User
		if ( ! empty( $payload['user']->ID ) ) {
			$payload['user_groups'] = array();

			$user_groups = learndash_get_users_group_ids( $payload['user']->ID );
			if ( is_array( $user_groups ) ) {
				foreach ( $user_groups as $group_id ) {
					$payload['user_groups'][] = array(
						'id'   => $group_id,
						'name' => get_the_title( $group_id ),
					);
				}
			}
		}

		// Essay
		if ( ! empty( $payload['essay'] ) ) {
			if ( ! empty( $payload['essay']['id'] ) ) {
				$payload['essay']['file_link'] = get_post_meta( $payload['essay']['id'], 'upload', true );
			}

			// Keep post_ prefix for backward compatibility
			foreach ( $payload['essay'] as $key => $arg ) {
				$key = str_replace( 'post_', '', $key );
				$payload['essay'][ $key ] = $arg;
			}
			unset( $payload['essay']['type'] );
			unset( $payload['essay']['author'] );
		}

		return $payload;
	}

	/**
	 * Action methods
	 */

	public static function init_action( $action_key, $payload ) {
		$response = false;

		switch ( $action_key ) {
			case 'enroll_into_course':
				$response = self::toggle_course_access( $payload, $remove = false, $create_user = true );
				break;

			case 'remove_from_course':
				$response = self::toggle_course_access( $payload, $remove = true, $create_user = false );
				break;

			case 'add_to_group':
				$response = self::toggle_group_membership( $payload, $remove = false, $create_user = true );
				break;

			case 'remove_from_group':
				$response = self::toggle_group_membership( $payload, $remove = true, $create_user = false );
				break;
		}

		return apply_filters( 'learndash_zapier_api_action_payload', $response, $action_key );
	}

	public static function toggle_course_access( $payload, $remove = false, $create_user = true ) {
		$response = array();
		$response['user'] = self::get_user( $payload, $create_user );

		$response['courses'] = array();
		foreach ( $payload['courses_ids'] as $course_id ) {
			ld_update_course_access( $response['user']->ID, $course_id, $remove );
			$response['courses'][] = get_post( $course_id );
		}

		return $response;
	}

	public static function toggle_group_membership( $payload, $remove = false, $create_user = true ) {
		$response = array();
		$response['user'] = self::get_user( $payload, $create_user );

		$response['groups'] = array();
		foreach ( $payload['groups_ids'] as $group_id ) {
			ld_update_group_access( $response['user']->ID, $group_id, $remove );
			$response['groups'][] = get_post( $group_id );
		}

		return $response;
	}

	public static function create_user( $payload ) {
		$user_id = wp_insert_user(
			array(
				'user_login' => $payload['username'] ?? $payload['user_email'],
				'user_pass'  => wp_generate_password(),
				'user_email' => $payload['user_email'],
				'first_name' => $payload['first_name'] ?? '',
				'last_name'  => $payload['last_name'] ?? '',
				'display_name' => $payload['display_name'] ?? '',
			)
		);

		if ( ! is_wp_error( $user_id ) ) {
			wp_new_user_notification( $user_id, null, 'both' );
			$user = get_user_by( 'ID', $user_id );
			return $user;
		} else {
			return $user_id;
		}
	}

	public static function get_user( $payload, $create = true ) {
		$user = get_user_by( 'email', $payload['user_email'] );
		if ( $user === false && $create ) {
			$user = self::create_user( $payload );

			if ( is_wp_error( $user ) ) {
				self::bail( sprintf( __( 'User could not be created. Error: %s', 'learndash-zapier' ), $user->get_error_message() ), 400 );
			}
		} elseif ( $user === false && ! $create ) {
			self::bail( __( 'User account with specified email address does not exist.', 'learndash-zapier' ), 404 );
		}

		if ( ! empty( $payload['new_user_email'] ) || ! empty( $payload['display_name'] ) ) {

			if ( ! empty( $payload['new_user_email'] ) ) {
				$user->user_email = $payload['new_user_email'];
			}

			if ( ! empty( $payload['display_name'] ) ) {
				$user->display_name = $payload['display_name'];
			}

			wp_update_user( $user );
		}

		$user = self::get_response( 'user', $user );

		return $user;
	}

	/**
	 * Get or parse response before sent to Zapier
	 * @param  string $key Object key
	 * @param  mixed  $id  Object ID, array, or full object
	 * @return mixed       Parsed response
	 */
	public static function get_response( $key = '', $id = '' ) {
		switch ( $key ) {
			case 'user':
				$response = array();
				if ( ! empty( $id ) && is_object( $id ) ) {
					$user = $id;
				} elseif ( ! empty( $id ) && is_numeric( $id ) ) {
					$user = get_user_by( 'id', $id );
				}

				if ( $user ) {
					$response = clone $user;
					unset( $response->data->user_pass );
					unset( $response->user_pass );
					unset( $response->allcaps );
					unset( $response->caps );
					unset( $response->cap_key );

					$response->data->first_name = $response->first_name;
					$response->data->last_name  = $response->last_name;
				}

				break;

			case 'course':
			case 'lesson':
			case 'topic':
			case 'group':
				if ( is_object( $id ) || is_array( $id ) ) {
					$response = $id;
				} else {
					$response = get_post( $id );
				}
				break;

			case 'progress':
			case 'course_progress':
			case 'group_progress':
				if ( ! empty( $id ) ) {
					$response = $id;
				} else {
					$response = array();
				}
				break;

			case 'course_info':
				if ( ! empty( $id['user_id'] ) && ! empty( $id['course_id'] ) ) {
					$response = self::get_course_info( $id );
				} else {
					$response = $id;
				}
				break;

			case 'quiz_result':
				if ( is_array( $id ) ) {
					unset( $id['rank'] );
					unset( $id['questions'] );

					if ( is_numeric( $id['course'] ) && $id['course'] > 0 ) {
						$id['course'] = get_post( $id['course'] );
					} elseif ( ! isset( $id['course'] ) || empty( $id['course'] ) ) {
						$id['course'] = self::get_quiz_result_default_value()['course'];
					}

					if ( is_numeric( $id['lesson'] ) && $id['lesson'] > 0 ) {
						$id['lesson'] = get_post( $id['lesson'] );
					} elseif ( ! isset( $id['lesson'] ) || empty( $id['lesson'] ) ) {
						$id['lesson'] = self::get_quiz_result_default_value()['lesson'];
					}

					if ( is_numeric( $id['topic'] ) && $id['topic'] > 0 ) {
						$id['topic'] = get_post( $id['topic'] );
					} elseif ( ! isset( $id['topic'] ) || empty( $id['topic'] ) ) {
						$id['topic'] = self::get_quiz_result_default_value()['topic'];
					}

					if ( is_numeric( $id['quiz'] ) && $id['quiz'] > 0 ) {
						$id['quiz'] = get_post( $id['quiz'] );
					} elseif ( ! isset( $id['quiz'] ) || empty( $id['quiz'] ) ) {
						$id['quiz'] = self::get_quiz_result_default_value()['quiz'];
					}

					$response = $id;
				} else {
					$response = self::get_quiz_result_default_value();
				}
				break;

			default:
				$response = $id;
				break;
		}

		return $response;
	}

	/**
	 * Get courseinfo array
	 * @param  array  $data Array with keys:
	 *                      'user_id', 'course_id'
	 * @return array        Array of courseinfo returned from courseinfo shortcode
	 */
	public static function get_course_info( $data ) {
		$course_info = array();
		$retrieved_values = array(
			'user_course_time',
			'cumulative_score',
			'cumulative_points',
			'cumulative_total_points',
			'cumulative_percentage',
			'cumulative_timespent',
			'cumulative_count',
			'aggregate_percentage',
			'aggregate_score',
			'aggregate_points',
			'aggregate_total_points',
			'aggregate_timespent',
			'aggregate_count',
			'course_points',
			'completed_on',
			'enrolled_on',
			'course_points',
			'user_course_points',
		);

		foreach ( $retrieved_values as $key ) {
			$course_info[ $key ] = do_shortcode( '[courseinfo show="' . $key . '" user_id="' . $data['user_id'] . '" course_id="' . $data['course_id'] . '" format="Y-m-d H:i:s"]' );
		}

		return $course_info;
	}

	/**
	 * Bail from current operation
	 * @param  mixed   $response    String for message response|
	 *                              array for json response
	 * @param  integer $status_code Status code
	 * @return void
	 */
	public static function bail( $response = '', $status_code = 200 ) {
		if ( ! is_array( $response ) ) {
			$response = array(
				'status'  => $status_code,
				'message' => $response,
			);
		}

		header( 'Content-Type: application/json;charset=utf-8;' );
		http_response_code( $status_code );
		echo json_encode( $response );
		exit();
	}

	/**
	 * Get course field to be used by Zapier
	 * @link https://github.com/zapier/zapier-platform/blob/master/packages/schema/docs/build/schema.md#fieldschema Fields schema that Zapier accept
	 * @return array List of field object keys
	 */
	public static function get_course_field() {
		$courses = self::get_courses_list();

		$field = array(
			'key'      => 'courses_ids',
			'choices'  => $courses,
			'label'    => 'Course(s)',
			'helpText' => 'Course(s) that the user will be enrolled into. Select one or more courses.',
			'list'     => true,
			'required' => true,
		);

		return $field;
	}

	/**
	 * Get courses list of this website
	 * @return array List of courses
	 */
	public static function get_courses_list() {
		$courses = get_posts(
			array(
				'post_type' => 'sfwd-courses',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			)
		);

		$courses = array_map(
			function( $course ) {
				if ( is_numeric( $course ) ) {
					  $course = get_post( $course );
				}

				return array(
					'id'     => $course->ID,
					'label'  => $course->post_title,
					'sample' => $course->ID,
					'value'  => $course->ID,
				);
			},
			$courses
		);

		return array_values( $courses );
	}

	/**
	 * Get lessons list
	 *
	 * @param  array $args
	 * @return array Array of lessons
	 */
	public static function get_lessons_list( $args = array() ) {
		if ( ! empty( $args['course_id'] ) ) {
			$lessons = learndash_course_get_lessons( $args['course_id'] );
		} else {
			$lessons = get_posts(
				array(
					'post_type' => 'sfwd-lessons',
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
		}

		$lessons = array_map(
			function( $lesson ) {
				if ( is_numeric( $lesson ) ) {
					  $lesson = get_post( $lesson );
				}

				return array(
					'id'     => $lesson->ID,
					'label'  => $lesson->post_title,
					'sample' => $lesson->ID,
					'value'  => $lesson->ID,
				);
			},
			$lessons
		);

		return array_values( $lessons );
	}

	/**
	 * Get topics list
	 *
	 * @param  array $args
	 * @return array Array of topics
	 */
	public static function get_topics_list( $args = array() ) {
		if ( ! empty( $args['course_id'] ) && ! empty( $args['lesson_id'] ) ) {
			$topics = learndash_course_get_topics( $args['course_id'], $args['lesson_id'] );
		} elseif ( ! empty( $args['course_id'] ) ) {
			$topics = learndash_course_get_steps_by_type( $args['course_id'], 'sfwd-topic' );
		} else {
			$topics = get_posts(
				array(
					'post_type' => 'sfwd-topic',
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
		}

		$topics = array_map(
			function( $topic ) {
				if ( is_numeric( $topic ) ) {
					  $topic = get_post( $topic );
				}

				return array(
					'id'     => $topic->ID,
					'label'  => $topic->post_title,
					'sample' => $topic->ID,
					'value'  => $topic->ID,
				);
			},
			$topics
		);

		return array_values( $topics );
	}

	/**
	 * Get quizzes list
	 *
	 * @param  array $args
	 * @return array Array of quizzes
	 */
	public static function get_quizzes_list( $args = array() ) {
		if ( ! empty( $args['course_id'] ) && ! empty( $args['topic_id'] ) ) {
			$quizzes = learndash_course_get_quizzes( $args['course_id'], $args['topic_id'] );
		} elseif ( ! empty( $args['course_id'] ) && ! empty( $args['lesson_id'] ) ) {
			$quizzes = learndash_course_get_quizzes( $args['course_id'], $args['lesson_id'] );
		} elseif ( ! empty( $args['course_id'] ) ) {
			$quizzes = learndash_course_get_quizzes( $args['course_id'], $args['course_id'] );
		} else {
			$quizzes = get_posts(
				array(
					'post_type' => 'sfwd-quiz',
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
		}

		$quizzes = array_map(
			function( $quiz ) {
				if ( is_numeric( $quiz ) ) {
					  $quiz = get_post( $quiz );
				}

				return array(
					'id'     => $quiz->ID,
					'label'  => $quiz->post_title,
					'sample' => $quiz->ID,
					'value'  => $quiz->ID,
				);
			},
			$quizzes
		);

		return array_values( $quizzes );
	}

	/**
	 * Get group field to be used by Zapier
	 * @link https://github.com/zapier/zapier-platform/blob/master/packages/schema/docs/build/schema.md#fieldschema Fields schema that Zapier accept
	 * @return array List of field object keys
	 */
	public static function get_group_field() {
		$groups = self::get_groups_list();

		$field = array(
			'key'      => 'groups_ids',
			'choices'  => $groups,
			'label'    => 'Group(s)',
			'helpText' => 'Group(s) that the user will be added to. Select one or more groups.',
			'list'     => true,
			'required' => true,
		);

		return $field;
	}

	/**
	 * Get groups list of the site
	 * @return array List of groups
	 */
	public static function get_groups_list() {
		$groups = get_posts(
			array(
				'post_type'      => 'groups',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			)
		);

		$groups = array_map(
			function( $group ) {
				return array(
					'id'     => $group->ID,
					'label'  => $group->post_title,
					'sample' => $group->ID,
					'value'  => $group->ID,
				);
			},
			$groups
		);

		return array_values( $groups );
	}

	/**
	 * Get sample for trigger data
	 * @param  string $trigger Trigger type
	 * @return array           Array of sample
	 */
	public static function get_trigger_sample( $trigger, $args = array() ) {
		switch ( $trigger ) {
			case 'enrolled_into_course':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'course' => self::get_object_sample( 'course', $args ),
					'course_started_on' => self::get_object_sample( 'course_started_on', $args ),
				);
				break;

			case 'enrolled_into_group':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'group' => self::get_object_sample( 'group', $args ),
					'group_started_on' => self::get_object_sample( 'group_started_on', $args ),
				);
				break;

			case 'group_completed':
				$sample = array(
					'group' => self::get_object_sample( 'group', $args ),
					'user' => self::get_object_sample( 'user', $args ),
					'group_progress' => self::get_object_sample( 'group_progress', $args ),
					'group_started_on' => self::get_object_sample( 'group_started_on', $args ),
					'group_completed_on' => self::get_object_sample( 'group_completed_on', $args ),
					'group_certificate_link' => self::get_object_sample( 'group_certificate_link', $args ),
				);
				break;

			case 'course_completed':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'course' => self::get_object_sample( 'course', $args ),
					'progress' => self::get_object_sample( 'course_progress', $args ),
					'course_info' => self::get_object_sample( 'course_info', $args ),
					'course_started_on' => self::get_object_sample( 'course_started_on', $args ),
					'course_completed_on' => self::get_object_sample( 'course_completed_on', $args ),
					'course_certificate_link' => self::get_object_sample( 'course_certificate_link', $args ),
				);
				break;

			case 'lesson_completed':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'course' => self::get_object_sample( 'course', $args ),
					'lesson' => self::get_object_sample( 'lesson', $args ),
					'progress' => self::get_object_sample( 'course_progress', $args ),
				);
				break;

			case 'topic_completed':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'course' => self::get_object_sample( 'course', $args ),
					'lesson' => self::get_object_sample( 'lesson', $args ),
					'topic' => self::get_object_sample( 'topic', $args ),
					'progress' => self::get_object_sample( 'course_progress', $args ),
				);
				break;

			case 'quiz_completed':
			case 'quiz_passed':
			case 'quiz_failed':
				$sample = array_merge(
					self::get_object_sample( 'quiz_result', $args ),
					array( 'user' => self::get_object_sample( 'user', $args ) )
				);
				break;

			case 'essay_submitted':
				$sample = array(
					'user' => self::get_object_sample( 'user', $args ),
					'essay' => self::get_object_sample( 'essay', $args ),
				);
				break;

			default:
				$sample = false;
				break;
		}

		return $sample;
	}

	/**
	 * Get object sample
	 * @param  string $object Object string
	 * @return mixed          Object sample
	 */
	public static function get_object_sample( $object, $args = array() ) {
		switch ( $object ) {
			case 'user':
				// User
				$users = get_users(
					array(
						'number' => 50,
					)
				);

				$sample = array();
				$i = 0;
				foreach ( $users as $user ) {
					$i++;

					$user_courses = learndash_user_get_enrolled_courses( $user->ID );

					if ( ! empty( $user_courses ) ) {
						$sample = self::get_response( 'user', $user );
						break;
					}

					// If this is last user and there's no enrolled user, return the last user
					if ( count( $users ) === $i ) {
						$sample = self::get_response( 'user', $user );
					}
				}
				break;

			case 'course':
				// Course
				$courses = get_posts(
					array(
						'post_type' => 'sfwd-courses',
						'posts_per_page' => 1,
						'include' => ! empty( $args['courses_ids'] ) ? $args['courses_ids'] : ( ! empty( $args['course_id'] ) ? array( $args['course_id'] ) : array() ),
						'orderby' => 'rand',
					)
				);

				$course = array();
				if ( isset( $courses[0] ) ) {
					$course = self::get_response( 'course', $courses[0] );
				}

				$sample = ! empty( $course ) ? $course : array();
				break;

			case 'lesson':
				// Lesson
				$lessons = get_posts(
					array(
						'post_type' => 'sfwd-lessons',
						'posts_per_page' => 1,
						'include' => ! empty( $args['lessons_ids'] ) ? $args['lessons_ids'] : ( ! empty( $args['lesson_id'] ) ? array( $args['lesson_id'] ) : array() ),
						'orderby' => 'rand',
					)
				);

				$lesson = array();
				if ( isset( $lessons[0] ) ) {
					$lesson = self::get_response( 'lesson', $lessons[0] );
				}

				$sample = ! empty( $lesson ) ? $lesson : array();
				break;

			case 'topic':
				// Topic
				$topics = get_posts(
					array(
						'post_type' => 'sfwd-topic',
						'posts_per_page' => 1,
						'include' => ! empty( $args['topics_ids'] ) ? $args['topics_ids'] : ( ! empty( $args['topic_id'] ) ? array( $args['topic_id'] ) : array() ),
						'orderby' => 'rand',
					)
				);

				$topic = array();
				if ( isset( $topics[0] ) ) {
					$topic = self::get_response( 'topic', $topics[0] );
				}

				$sample = ! empty( $topic ) ? $topic : array();
				break;

			case 'group':
				// Group
				$groups = get_posts(
					array(
						'post_type' => 'groups',
						'posts_per_page' => 1,
						'include' => ! empty( $args['groups_ids'] ) ? $args['groups_ids'] : ( ! empty( $args['group_id'] ) ? array( $args['group_id'] ) : array() ),
						'orderby' => 'rand',
					)
				);

				$group = array();
				if ( isset( $groups[0] ) ) {
					$group = self::get_response( 'group', $groups[0] );
				}

				$sample = ! empty( $group ) ? $group : array();
				break;

			case 'course_progress':
				$sample = self::get_response( 'course_progress', self::get_course_progress_sample() );
				break;

			case 'group_progress':
				$sample = self::get_response( 'group_progress', self::get_group_progress_sample() );
				break;

			case 'course_info':
				$sample = self::get_response( 'course_info', self::get_course_info_sample() );
				break;

			case 'course_started_on':
			case 'course_completed_on':
			case 'group_started_on':
			case 'group_completed_on':
				$sample = self::get_response( 'course_started_on', date( 'Y-m-d H:i:s' ) );
				break;

			case 'quiz_result':
				$sample = self::get_response( 'quiz_result', self::get_quiz_result_sample() );
				break;

			case 'essay':
				$sample = self::get_response( 'essay', self::get_essay_sample() );
				break;

			case 'group_certificate_link':
				$sample = self::get_response(
					'group_certificate_link',
					add_query_arg(
						array(
							'group_id' => 1,
							'user' => 1,
							'cert-nonce' => 'abc123',
						),
						home_url( '/sample-certificate/' )
					)
				);
				break;

			case 'course_certificate_link':
				$sample = self::get_response(
					'course_certificate_link',
					add_query_arg(
						array(
							'course_id' => 1,
							'user' => 1,
							'cert-nonce' => 'abc123',
						),
						home_url( '/sample-certificate/' )
					)
				);
				break;

			default:
				$sample = false;
				break;
		}

		return $sample;
	}

	public static function get_course_progress_sample() {
		$users = get_users(
			array(
				'number' => 50,
			)
		);

		foreach ( $users as $user ) {
			$course_progress = get_user_meta( $user->ID, '_sfwd-course_progress', true );

			if ( ! empty( $course_progress ) && is_array( $course_progress ) ) {
				return $course_progress;
			}
		}

		return array();
	}

	public static function get_group_progress_sample() {
		$users = get_users(
			array(
				'number' => 20,
			)
		);

		foreach ( $users as $user ) {
			$group_ids = learndash_get_users_group_ids( $user->ID );

			foreach ( $group_ids as $group_id ) {
				$group_progress = learndash_get_user_group_progress( $group_id, $user->ID );

				if ( ! empty( $group_progress ) && is_array( $group_progress ) ) {
					return $group_progress;
				}
			}
		}

		return array();
	}

	public static function get_course_info_sample() {
		$courses = get_posts(
			array(
				'post_type' => 'sfwd-courses',
				'posts_per_page' => 10,
			)
		);
		foreach ( $courses as $course ) {
			$course_id = $course->ID;

			$users = learndash_get_users_for_course( $course_id, array(), false );
			if ( is_a( $users, 'WP_User_Query' ) ) {
				$users = $users->get_results();
				if ( ! empty( $users ) ) {
					$user_id = $users[0]->ID;
					break;
				}
			}
		}

		if ( empty( $user_id ) ) {
			$user_id = get_users(
				array(
					'number' => 1,
					'role' => 'administrator',
				)
			)[0]->ID;
		}

		return self::get_course_info( compact( 'user_id', 'course_id' ) );
	}

	public static function get_quiz_result_sample() {
		$users = get_users(
			array(
				'number'  => 5,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		foreach ( $users as $user ) {
			$quiz_result = get_user_meta( $user->ID, '_sfwd-quizzes', true );

			if ( is_array( $quiz_result ) ) {
				$count = count( $quiz_result );

				if ( $count > 0 ) {
					$key = $count - 1;

					if ( ! empty( $quiz_result[ $key ] ) && is_array( $quiz_result[ $key ] ) ) {
						$result_keys  = array_keys( $quiz_result[ $key ] );
						$default_keys = array_keys( self::get_quiz_result_default_value() );
						if ( count( array_diff( $result_keys, $default_keys ) ) < 1
							&& ! is_numeric( $quiz_result[ $key ]['course'] )
							&& ! empty( $quiz_result[ $key ]['course'] )
							&& ! is_numeric( $quiz_result[ $key ]['lesson'] )
							&& ! empty( $quiz_result[ $key ]['lesson'] )
							&& ! is_numeric( $quiz_result[ $key ]['topic'] )
							&& ! empty( $quiz_result[ $key ]['topic'] )
							&& ! is_numeric( $quiz_result[ $key ]['quiz'] )
							&& ! empty( $quiz_result[ $key ]['quiz'] )
						) {
							return self::get_response( 'quiz_result', $quiz_result[ $key ] );
						}
					}
				};
			}
		}

		return self::get_quiz_result_default_value();
	}

	/**
	 * Get quiz_result default value
	 * @return array quiz_result value
	 */
	public static function get_quiz_result_default_value() {
		return array(
			'has_graded' => false,
			'time' => 1601348587,
			'score' => 1,
			'started' => 1601348584,
			'points' => 1,
			'lesson' => array(
				'post_date_gmt' => '2016-05-05 08:31:47',
				'filter' => 'raw',
				'post_title' => 'Test Lesson',
				'post_name' => 'test-lesson',
				'to_ping' => '',
				'post_status' => 'publish',
				'comment_status' => 'open',
				'post_excerpt' => '',
				'post_modified' => '2016-05-05 08:31:47',
				'ID' => 2491,
				'menu_order' => 1,
				'post_content' => 'Test content',
				'post_modified_gmt' => '2016-05-05 08:31:47',
				'comment_count' => '0',
				'pinged' => '',
				'post_content_filtered' => '',
				'guid' => 'http://siteurl.com/?post_type=sfwd-lessons&#038;p=2491',
				'post_mime_type' => '',
				'post_date' => '2016-05-05 08:31:47',
				'post_password' => '',
				'ping_status' => 'closed',
				'post_type' => 'sfwd-lessons',
				'post_author' => '2',
				'post_parent' => 0,
			),
			'total_points' => 1,
			'quiz' => array(
				'post_date_gmt' => '2016-05-05 08:35:49',
				'filter' => 'raw',
				'post_title' => 'Test Quiz',
				'post_name' => 'test-quiz',
				'to_ping' => '',
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'post_excerpt' => '',
				'post_modified' => '2020-09-29 10:02:00',
				'ID' => 2495,
				'menu_order' => 1,
				'post_content' => '',
				'post_modified_gmt' => '2020-09-29 03:02:00',
				'comment_count' => '0',
				'pinged' => '',
				'post_content_filtered' => '',
				'guid' => 'http://siteurl.com/?post_type=sfwd-quiz&#038;p=2495',
				'post_mime_type' => '',
				'post_date' => '2016-05-05 08:35:49',
				'post_password' => '',
				'ping_status' => 'closed',
				'post_type' => 'sfwd-quiz',
				'post_author' => '2',
				'post_parent' => 0,
			),
			'pro_quizid' => 28,
			'question_show_count' => 1,
			'percentage' => 100,
			'course' => array(
				'post_date_gmt' => '2016-05-05 08:31:16',
				'filter' => 'raw',
				'post_title' => 'Test Course',
				'post_name' => 'test-course',
				'to_ping' => '',
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'post_excerpt' => '',
				'post_modified' => '2020-09-29 07:53:12',
				'ID' => 2490,
				'menu_order' => 0,
				'post_content' => 'Test Course',
				'post_modified_gmt' => '2020-09-29 00:53:12',
				'comment_count' => '0',
				'pinged' => '',
				'post_content_filtered' => '',
				'guid' => 'http://siteurl.com/?post_type=sfwd-courses&#038;p=2490',
				'post_mime_type' => '',
				'post_date' => '2016-05-05 08:31:16',
				'post_password' => '',
				'ping_status' => 'closed',
				'post_type' => 'sfwd-courses',
				'post_author' => '2',
				'post_parent' => 0,
			),
			'completed' => 1601348585,
			'pass' => 1,
			'timespent' => 1.582,
			'topic' => array(
				'post_date_gmt' => '2016-05-05 08:35:01',
				'filter' => 'raw',
				'post_title' => 'Test Topic',
				'post_name' => 'test-topic',
				'to_ping' => '',
				'post_status' => 'publish',
				'comment_status' => 'open',
				'post_excerpt' => '',
				'post_modified' => '2018-06-23 11:14:13',
				'ID' => 2493,
				'menu_order' => 1,
				'post_content' => 'Test content',
				'post_modified_gmt' => '2018-06-23 11:14:13',
				'comment_count' => '0',
				'pinged' => '',
				'post_content_filtered' => '',
				'guid' => 'http://siteurl.com/?post_type=sfwd-topic&#038;p=2493',
				'post_mime_type' => '',
				'post_date' => '2016-05-05 08:35:01',
				'post_password' => '',
				'ping_status' => 'closed',
				'post_type' => 'sfwd-topic',
				'post_author' => '2',
				'post_parent' => 0,
			),
			'statistic_ref_id' => 0,
			'count' => 1,
		);
	}

	public static function get_essay_sample() {
		return array(
			'title'     => 'Essay Title',
			'status'    => 'not_graded',
			'content'   => 'Essay content',
			'file_link' => site_url( '/wp-content/uploads/essays/sample.txt' ),
		);
	}
}

LearnDash_Zapier_Api::init();
