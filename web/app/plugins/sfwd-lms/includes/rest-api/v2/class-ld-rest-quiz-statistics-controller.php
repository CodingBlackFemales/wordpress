<?php
/**
 * LearnDash REST API Quiz Statistics Controller.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Quiz_Statistics_Controller_V2' ) ) && class_exists( 'WP_REST_Controller' ) ) {

	/**
	 * Class LearnDash REST API Quiz Statistics Controller.
	 *
	 * @since 3.3.0
	 * @uses WP_REST_Controller
	 */
	class LD_REST_Quiz_Statistics_Controller_V2 extends WP_REST_Controller implements Iterator /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * API version.
		 *
		 * @var string
		 */
		private $version;

		/**
		 * Records per page.
		 *
		 * @var int
		 */
		private $per_page;

		/**
		 * Page number currently being viewed.
		 *
		 * @var int
		 */
		private $page;

		/**
		 * Statistics refs list for current request.
		 *
		 * @var array
		 */
		private $stat_refs = array();

		/**
		 * Statistics refs count.
		 *
		 * @var int
		 */
		private $stat_refs_count = 0;

		/**
		 * Pointer for traversing over stat refs.
		 *
		 * @var int
		 */
		private $position = 0;

		/**
		 * Request object.
		 *
		 * @var WP_REST_Request
		 */
		private $request;

		/**
		 * User quiz data list.
		 * This will act as repository of information to
		 * avoid repetitive lookups.
		 *
		 * @var array
		 */
		private $user_quiz_data = array();

		/**
		 * Users to query for statistics.
		 *
		 * @var array
		 */
		private $users_for_stats = null;

		/**
		 * Constructor.
		 *
		 * @since 3.3.0
		 */
		public function __construct() {

			$this->version   = 'v2';
			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = $this->get_rest_base( 'quizzes-statistics' );
		}

		/**
		 * Retrieve current node.
		 *
		 * @since 3.3.0
		 *
		 * @return mixed
		 */
		public function current() {

			return $this->stat_refs[ $this->position ];
		}

		/**
		 * Retrieve next node
		 *
		 * @since 3.3.0
		 */
		public function next() {

			++$this->position;
		}

		/**
		 * Retrieve current position of traversal pointer.
		 *
		 * @since 3.3.0
		 *
		 * @return bool|float|int|string|null
		 */
		public function key() {

			return $this->position;
		}

		/**
		 * Check if node is valid.
		 *
		 * @since 3.3.0
		 *
		 * @return bool
		 */
		public function valid() {
			return isset( $this->stat_refs[ $this->position ] );
		}

		/**
		 * Reset the pointer to first node.
		 *
		 * @since 3.3.0
		 *
		 * @return void
		 */
		public function rewind() {

			$this->position = 0;
		}

		/**
		 * Find the statistics record in memory.
		 *
		 * @since 3.3.0
		 *
		 * @param int $stat_ref_id Statistics reference ID.
		 *
		 * @return null|WpProQuiz_Model_StatisticRefModel
		 */
		public function find( $stat_ref_id = 0 ) {
			$found = null;

			foreach ( $this->stat_refs as $record ) {
				if ( $stat_ref_id === (int) $record->getStatisticRefId() ) {
					$found = $record;
					break;
				}
			}

			return $found;
		}

		/**
		 * Register API routes.
		 *
		 * @since 3.3.0
		 */
		public function register_routes() {
			if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
				return;
			}

			add_filter( 'rest_request_before_callbacks', array( $this, 'pre_callback_ops' ), 10, 3 );
			add_filter( 'rest_request_after_callbacks', array( $this, 'post_callback_ops' ), 10, 3 );
			add_filter( 'learndash_rest_statistic_response', array( $this, 'statistic_response_embed_links' ), 10, 1 );
			add_filter( 'learndash_rest_statistic_question_response', array( $this, 'statistic_question_response_embed_links' ), 10, 1 );

			foreach ( $this->routes() as $route => $route_args ) {
				register_rest_route(
					$this->namespace,
					'/' . $route,
					$route_args
				);
			}
		}

		/**
		 * Set the current request object in property.
		 * Also includes the necessary files.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response Response object.
		 * @param array            $handler  Route handler used for the request.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response Filtered response. This will have no modification in this case.
		 */
		public function pre_callback_ops( $response, $handler, $request ) {
			if ( $this->is_qs_endpoint( $request ) ) {
				$this->reset_state();

				/**
				 * Include necessary classes.
				 */
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/interfaces/interface-ldlms-answer.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-base-answer-type.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-sort-answer-type.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-assessment-answer-type.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-cloze-answer-type.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-free-answer-type.php';
				include untrailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . '/includes/classes/answer-types/class-ldlms-essay-answer-type.php';

				/**
				 * Add necessary filters for statistics query.
				 */
				add_filter( 'learndash_statrefs_joins', array( $this, 'join_quiz_master_and_postmeta' ) );
				add_filter( 'learndash_statrefs_where', array( $this, 'statistics_ref_where' ) );
				add_filter( 'learndash_statrefs_where', array( $this, 'statistics_ref_users_where' ), 20 );
				add_filter( 'learndash_statrefs_where', array( $this, 'statistics_ref_quiz_where' ), 25 );
				add_filter( 'learndash_statrefs_where', array( $this, 'statistics_ref_duration_where' ), 25 );

				$this->request = $request;
				$this->fetch_stat_refs();
				$this->stat_refs = array_values( $this->stat_refs );
			}

			return $response;
		}

		/**
		 * Perform operations after the callback is executed.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response Response object.
		 * @param array            $handler  Route handler used for the request.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response Filtered response. This will have no modification in this case.
		 */
		public function post_callback_ops( $response, $handler, $request ) {
			if ( $this->is_qs_endpoint( $request ) ) {
				$this->restore_state();
			}
			return $response;
		}

		/**
		 * Retrieve list of quiz statistics.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object from REST.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_items( $request ) {
			if ( ! $this->stat_refs ) {
				return new WP_Error( 404, __( 'No records found for this request.', 'learndash' ) );
			}

			$stats_list = array();

			do {

				$request_clone = clone $request;
				$request_clone->set_param( 'id', $this->current()->getStatisticRefId() );
				$statistics   = $this->get_item( $request_clone );
				$stat_data    = $statistics->get_data();
				$stats_list[] = $stat_data;

				$this->next();
			} while ( $this->valid() );

			$response = rest_ensure_response( $stats_list );

			// Add the pagination links to response header.
			$total_items = $this->stat_refs_count;
			$max_pages   = ceil( $total_items / (int) $this->per_page );

			if ( $this->page > $max_pages && $total_items > 0 ) {
				return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.', 'learndash' ), array( 'status' => 400 ) );
			}

			$response->header( 'X-WP-Total', (int) $total_items );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			$request_params = $request->get_query_params();
			$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

			if ( $this->page > 1 ) {
				$prev_page = $this->page - 1;

				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}

				$prev_link = add_query_arg( 'page', $prev_page, $base );
				$response->link_header( 'prev', $prev_link );
			}
			if ( $max_pages > $this->page ) {
				$next_page = $this->page + 1;
				$next_link = add_query_arg( 'page', $next_page, $base );

				$response->link_header( 'next', $next_link );
			}

			return $response;
		}

		/**
		 * Retrieve a single item from statistics based on ID.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object from REST.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_item( $request ) {
			if ( ! $this->valid() ) {
				return new WP_Error( 404, __( 'Invalid entry', 'learndash' ) );
			}

			$stat_ref_id = $this->current()->getStatisticRefId();
			$stat_object = $this->stat_response_object();

			$stat_object = apply_filters( 'learndash_rest_statistic_response', (array) $stat_object );
			return new WP_REST_Response( $stat_object );
		}

		/**
		 * Retrieve list of questions for a particular stat ref ID.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object from REST.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_questions( $request ) {
			if ( ! $this->valid() ) {
				return new WP_Error( 404, __( 'Invalid entry', 'learndash' ) );
			}

			$stat_ref_id = $this->current()->getStatisticRefId();

			$question_id      = 0;
			$stat_question_id = $request->get_param( 'id' );
			if ( ! empty( $stat_question_id ) ) {
				list( $ref_id, $question_id ) = explode( '_', $stat_question_id );
				if ( absint( $ref_id ) === $stat_ref_id ) {
					$question_id = absint( $question_id );
				} else {
					$question_id = 0;
				}
			}

			try {
				$stat_questions  = array();
				$question_mapper = new WpProQuiz_Model_QuestionMapper();
				$statistic       = ( new WpProQuiz_Model_StatisticMapper() )->fetchAllByRef( $stat_ref_id );
				$questions       = $question_mapper->fetchByStatId( $stat_ref_id, $question_id, $this->per_page, $this->per_page * ( $this->page - 1 ) );
				$questions_count = $question_mapper->fetchByStatIdCount( $stat_ref_id, $question_id );

				foreach ( $questions as $question ) {
					$stat_model = $this->get_statistics_mode_from_list( $statistic, 'getQuestionId', (int) $question->getId() );

					$answer_type_obj = $this->make_answer_obj(
						$question->getAnswerType(),
						array(
							$question,
							$stat_model->getAnswerData(),
							$this->current(),
						)
					);

					$answer_type_obj->setup();

					$question_response = new stdClass();

					$question_response->id            = $stat_ref_id . '_' . $question->getId(); // Need to have a unique ID.
					$question_response->statistic     = (int) $stat_ref_id;
					$question_response->quiz          = $this->getQuizId();
					$question_response->question      = learndash_get_question_post_by_pro_id( $question->getId() ) ? learndash_get_question_post_by_pro_id( $question->getId() ) : null;
					$question_response->question_type = $question->getAnswerType();
					$question_response->points_scored = $this->get_count( $statistic, 'getPoints' );
					$question_response->points_total  = (int) $this->current()->getMapper()->fetchTotalPoints( $this->current()->getStatisticRefId() );
					$question_response->answers       = $answer_type_obj->get_answers();
					$question_response->student       = (object) $answer_type_obj->get_student_answers();

					$question_response = (array) $question_response;

					/**
					 * Filters the response object for each question.
					 *
					 * @since 3.3.0
					 *
					 * @param array                             $question_response Response array for question.
					 * @param WpProQuiz_Model_Question          $question          Question object.
					 * @param string                            $student_answers   Submitted answer data by student.
					 * @param WpProQuiz_Model_StatisticRefModel $stat_ref_model    Statistics ref model.
					 */
					$question_response = apply_filters(
						'learndash_rest_statistic_question_response',
						$question_response,
						$question,
						$stat_model->getAnswerData(),
						$this->current()
					);

					$stat_questions[] = $question_response;
				}

				$response = rest_ensure_response( $stat_questions );

				// Add the pagination links to response header.
				$total_items = $questions_count;
				$max_pages   = ceil( $total_items / (int) $this->per_page );

				if ( $this->page > $max_pages && $total_items > 0 ) {
					return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.', 'learndash' ), array( 'status' => 400 ) );
				}

				$response->header( 'X-WP-Total', (int) $total_items );
				$response->header( 'X-WP-TotalPages', (int) $max_pages );

				$request_params = $request->get_query_params();
				$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

				if ( $this->page > 1 ) {
					$prev_page = $this->page - 1;

					if ( $prev_page > $max_pages ) {
						$prev_page = $max_pages;
					}

					$prev_link = add_query_arg( 'page', $prev_page, $base );
					$response->link_header( 'prev', $prev_link );
				}
				if ( $max_pages > $this->page ) {
					$next_page = $this->page + 1;
					$next_link = add_query_arg( 'page', $next_page, $base );

					$response->link_header( 'next', $next_link );
				}

				return $response;

			} catch ( Throwable $throwable ) { // phpcs:ignore PHPCompatibility.Interfaces.NewInterfaces.throwableFound
				// Executed only in PHP 7+, will not match in PHP 5.x.
				$code = $throwable->getCode() > 0 ? $throwable->getCode() : 400;

				return new WP_Error( $code, $throwable->getMessage() );
			} catch ( Exception $exception ) {
				// Executed only in PHP 5.x. Will not be reached in PHP 7+.
				$code = $exception->getCode() > 0 ? $exception->getCode() : 400;

				return new WP_Error( $code, $exception->getMessage() );
			}
		}

		/**
		 * Permission callback for quiz-statistics endpoint.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return bool If stats can be accessed by user.
		 */
		public function can_access_stats( $request ) {
			if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
				return false;
			}

			// Check we have a valid Quiz ID.
			$quiz_id = $request->get_param( 'quiz' );
			if ( empty( $quiz_id ) ) {
				return false;
			}

			// Check the Quiz has Statistics enabled.
			$quiz_pro_statistics_on = learndash_get_setting( $quiz_id, 'statisticsOn', true );
			if ( ! $quiz_pro_statistics_on ) {
				return false;
			}

			$stat_ref_id = $request->get_param( 'statistic' );
			$stat_users  = (array) $this->users_for_stats();

			if ( $stat_ref_id && $this->valid() ) {
				$stat_user  = (int) $this->current()->getUserId();
				$stat_users = array_map( 'absint', $stat_users );
				return empty( $stat_users ) || in_array( $stat_user, $stat_users, true );
			}

			return true;
		}

		/**
		 * Permission callback for quiz-statistics endpoint.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return bool If stats can be accessed by user.
		 */
		public function can_access_stats_questions( $request ) {
			if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
				return false;
			}

			// Check we have a valid Quiz ID.
			$quiz_id = $request->get_param( 'quiz' );
			if ( empty( $quiz_id ) ) {
				return false;
			}

			// Check the Quiz has Statistics enabled.
			$quiz_pro_statistics_on = learndash_get_setting( $quiz_id, 'statisticsOn', true );
			if ( ! $quiz_pro_statistics_on ) {
				return false;
			}

			$stat_ref_id = $request->get_param( 'statistic' );
			$stat_users  = (array) $this->users_for_stats();

			if ( $stat_ref_id && $this->valid() ) {
				$stat_user  = (int) $this->current()->getUserId();
				$stat_users = array_map( 'absint', $stat_users );
				return empty( $stat_users ) || in_array( $stat_user, $stat_users, true );
			}

			return true;
		}

		/**
		 * Add the embed links to quiz statistic response item.
		 *
		 * @since 3.3.0
		 *
		 * @param array $response    Response object in which link will be added.
		 *
		 * @return array
		 */
		public function statistic_response_embed_links( array $response ) {

			if ( ( isset( $response['quiz'] ) ) && ( ! empty( $response['quiz'] ) ) ) {

				$quiz_rest_base       = sprintf( '%s/%s/%d', $this->namespace, $this->get_rest_base( 'quizzes' ), absint( $response['quiz'] ) );
				$statistics_rest_base = $quiz_rest_base . '/' . $this->get_rest_base( 'quizzes-statistics' );

				$response['_links']['collection'] = array(
					array(
						'href' => rest_url( $statistics_rest_base ),
					),
				);

				if ( ( isset( $response['id'] ) ) && ( ! empty( $response['id'] ) ) ) {
					$response['_links']['self'] = array(
						array(
							'href' => rest_url( $statistics_rest_base . '/' . absint( $response['id'] ) ),
						),
					);

					$response['_links']['questions'] = array(
						array(
							'href'       => rest_url( $statistics_rest_base . '/' . absint( $response['id'] ) . '/questions' ),
							'embeddable' => true,
						),
					);
				}

				if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( absint( $response['quiz'] ) ) ) {
					$response['_links']['quiz'] = array(
						array(
							'href'       => rest_url( $quiz_rest_base ),
							'embeddable' => true,
						),
					);
				}
			}

			if ( ( isset( $response['user'] ) ) && ( ! empty( $response['user'] ) ) ) {
				$user = get_user_by( 'id', $response['user'] );
				if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
					$response['_links']['user'] = array(
						array(
							// I hate that WP doesn't have some defined vars to know the namespace and version.
							'href'       => rest_url( 'wp/v2/users/' . $response['user'] ),
							'embeddable' => true,
						),
					);
				}
			}

			return $response;
		}

		/**
		 * Add the embed links to quiz statistic question response item.
		 *
		 * @since 3.3.0
		 *
		 * @param array $response    Response object in which link will be added.
		 *
		 * @return array
		 */
		public function statistic_question_response_embed_links( array $response ) {

			if ( ( isset( $response['quiz'] ) ) && ( ! empty( $response['quiz'] ) ) && ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( absint( $response['quiz'] ) ) ) ) {

				$quiz_rest_base = sprintf( '%s/%s/%d', $this->namespace, $this->get_rest_base( 'quizzes' ), absint( $response['quiz'] ) );

				$statistics_collection_rest_base = sprintf( '%s/%s', $quiz_rest_base, $this->get_rest_base( 'quizzes-statistics' ) );

				$statistics_single_rest_base = sprintf( '%s/%d', $statistics_collection_rest_base, absint( $response['statistic'] ) );

				$statistics_questions_collection_rest_base = sprintf( '%s/%s', $statistics_single_rest_base, $this->get_rest_base( 'quizzes-statistics-questions' ) );

				$response['_links']['collection'] = array(
					array(
						'href' => rest_url( $statistics_questions_collection_rest_base ),
					),
				);

				if ( ( isset( $response['id'] ) ) && ( ! empty( $response['id'] ) ) ) {
					$response['_links']['self'] = array(
						array(
							'href' => rest_url( $statistics_questions_collection_rest_base . '/' . $response['id'] ),
						),
					);
				}

				if ( ( isset( $response['statistic'] ) ) && ( ! empty( $response['statistic'] ) ) ) {
					$response['_links']['statistic'] = array(
						array(
							'href' => rest_url( $statistics_single_rest_base ),
						),
					);
				}

				if ( ( isset( $response['question_type'] ) ) && ( ! empty( $response['question_type'] ) ) ) {
					$question_type_rest_base = $this->namespace . '/' . $this->get_rest_base( 'question-types' );

					$response['_links'][ $this->get_rest_base( 'question-types' ) ] = array(
						array(
							'href'       => rest_url( $question_type_rest_base . '/' . $response['question_type'] ),
							'embeddable' => true,
						),
					);
				}

				$response['_links']['quiz'] = array(
					array(
						'href'       => rest_url( $quiz_rest_base ),
						'embeddable' => true,
					),
				);
			}

			if ( ( isset( $response['question'] ) ) && ( ! empty( $response['question'] ) ) ) {
				$question_rest_base = sprintf( '%s/%s/%d', $this->namespace, $this->get_rest_base( 'questions' ), absint( $response['question'] ) );

				$response['_links']['question'] = array(
					array(
						// I hate that WP doesn't have some defined vars to know the namespace and version.
						'href'       => rest_url( $question_rest_base ),
						'embeddable' => true,
					),
				);
			}

			if ( ( isset( $response['user'] ) ) && ( ! empty( $response['user'] ) ) ) {
				$user = get_user_by( 'id', $response['user'] );
				if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
					$response['_links']['user'] = array(
						array(
							// I hate that WP doesn't have some defined vars to know the namespace and version.
							'href'       => rest_url( 'wp/v2/users/' . $response['user'] ),
							'embeddable' => true,
						),
					);
				}
			}

			return $response;
		}

		/**
		 * Join query for fetching statistics for which quiz_setting is enabled.
		 * Quiz > Settings > Quiz statistics > Enable front end display
		 *
		 * @since 3.3.0
		 *
		 * @param string $join Join clause for query.
		 *
		 * @return string
		 */
		public function join_quiz_master_and_postmeta( $join ) {
			global $wpdb;

			return $join;
		}

		/**
		 * Filter the where clause of the query to fetch statistics refs.
		 *
		 * @since 3.3.0
		 *
		 * @param string $where Where clause.
		 *
		 * @return string
		 */
		public function statistics_ref_where( $where ) {
			return $where;
		}

		/**
		 * Filter the where clause of the query to fetch statistics refs
		 * based on user IDs.
		 *
		 * @since 3.3.0
		 *
		 * 1. Admins have access to all statistics.
		 * 2. Group leaders will have access to statistics of only those
		 *    users which belong to their groups.
		 * 3. Other logged in users will have access to only their stats.
		 * 4. Logged out user cannot access stats.
		 *
		 * @param string $where Where clause for query.
		 *
		 * @return string
		 */
		public function statistics_ref_users_where( $where ) {
			global $wpdb;

			$user_id = $this->request->get_param( 'user' );
			if ( $user_id ) {
				$where .= $wpdb->prepare( " AND statref.user_id=%d ", (int) $user_id ); //phpcs:ignore
			} else {

				$users = $this->users_for_stats();

				if ( ( $users ) ) {
					$how_many     = count( $users );
					$placeholders = array_fill( 0, $how_many, '%d' );
					$format       = implode( ', ', $placeholders );
					$where       .= $wpdb->prepare( " AND statref.user_id IN ($format) ", $users ); //phpcs:ignore
				}
			}

			return $where;
		}

		/**
		 * If quiz id is passed explicitly via request, filter the results.
		 *
		 * @since 3.3.0
		 *
		 * @param string $where Where clause.
		 *
		 * @return string
		 */
		public function statistics_ref_quiz_where( $where ) {
			global $wpdb;

			$quiz_id = $this->request->get_param( 'quiz' );
			if ( $quiz_id ) {
				$quiz_pro_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
				$quiz_pro_id = absint( $quiz_pro_id );
				if ( ! empty( $quiz_pro_id ) ) {
					$where .= $wpdb->prepare( " AND statref.quiz_id=%d ", (int) $quiz_pro_id ); //phpcs:ignore
				}
			}

			return $where;
		}

		/**
		 * If before date and/or after date are passed, add
		 * conditions in where clause.
		 *
		 * @since 3.3.0
		 *
		 * @param string $where WHERE Clause for query.
		 *
		 * @return string
		 */
		public function statistics_ref_duration_where( $where ) {
			global $wpdb;
			$after_date  = $this->request->get_param( 'after' );
			$before_date = $this->request->get_param( 'before' );

			if ( $after_date ) {
				$s_date = strtotime( $after_date );
				$where .= ( false !== $s_date ) ? $wpdb->prepare( ' AND statref.create_time >= %s', $s_date ) : '';
			}

			if ( $before_date ) {
				$e_date = strtotime( $before_date );
				$where .= ( false !== $e_date ) ? $wpdb->prepare( ' AND statref.create_time <= %s', $e_date ) : '';
			}

			return $where;
		}

		/**
		 * Return the route information in form of an array.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		private function routes() {
			$quiz_rest_base                                      = $this->get_rest_base( 'quizzes' ) . '/(?P<quiz>[\d]+)/' . $this->get_rest_base( 'quizzes-statistics' );
			$routes[ $quiz_rest_base ]                           = array(
				'args'   => array(
					'quiz' => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_access_stats' ),
					'args'                => $this->get_collection_params_statistics(),
				),
				'schema' => array( $this, 'get_stats_schema' ),
			);
			$routes[ $quiz_rest_base . '/(?P<statistic>[\d]+)' ] = array(
				'args' => array(
					'quiz' => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'can_access_stats' ),
				),
			);

			$quizzes_statistics_questions_base            = $quiz_rest_base . '/(?P<statistic>[\d]+)/' . $this->get_rest_base( 'quizzes-statistics-questions' );
			$routes[ $quizzes_statistics_questions_base ] = array(
				'args'   => array(
					'quiz'      => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
					'statistic' => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s Statistic identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_questions' ),
					'permission_callback' => array( $this, 'can_access_stats_questions' ),
					'args'                => $this->get_collection_params_statistics_questions(),
				),
				'schema' => array( $this, 'get_stats_questions_schema' ),
			);

			$routes[ $quizzes_statistics_questions_base . '/(?P<id>[\w-]+)' ] = array(
				'args' => array(
					'quiz'      => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
					'statistic' => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'Unique %s Statistic identifier for the object.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'required'    => true,
					),
					'id'        => array(
						'description' => sprintf(
							// translators: placeholder: Quiz, Question.
							esc_html_x(
								'Unique %1$s Statistic %2$s identifier for the object.',
								'placeholder: Quiz, Question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' ),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'text',
						'required'    => true,
					),

				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_questions' ),
					'permission_callback' => array( $this, 'can_access_stats_questions' ),
				),
			);

			return $routes;
		}

		/**
		 * Check if the current endpoint is quiz statistics endpoint.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return bool
		 */
		private function is_qs_endpoint( WP_REST_Request $request ) {
			$is_qs_endpoint = false;

			$current_route = str_replace( trailingslashit( $this->namespace ), '', $request->get_route() );
			$routes        = array_keys( $this->routes() );

			foreach ( $routes as $route ) {
				if ( preg_match( '~' . $route . '~', ltrim( $current_route, '/' ) ) ) {
					$is_qs_endpoint = true;
					break;
				}
			}

			return $is_qs_endpoint;
		}

		/**
		 * Fetches expected parameters from request and sets
		 * the properties based on their values; else default
		 * will be assigned.
		 *
		 * @since 3.3.0
		 *
		 * @return void
		 */
		private function build_params() {

			$this->per_page = $this->request->get_param( 'per_page' ) ? $this->request->get_param( 'per_page' ) : (int) get_option( 'posts_per_page' );
			$this->page     = $this->request->get_param( 'page' ) ? $this->request->get_param( 'page' ) : 1;
		}

		/**
		 * Fetch the stat refs records for current request.
		 *
		 * @since 3.3.0
		 *
		 * @return void
		 */
		private function fetch_stat_refs() {
			$this->build_params();
			$stat_ref_id     = $this->request->get_param( 'statistic' );
			$stat_ref_mapper = new WpProQuiz_Model_StatisticRefMapper();

			if ( ! $stat_ref_id ) {
				$args = array(
					'limit'  => $this->per_page,
					'offset' => $this->per_page * ( $this->page - 1 ),
				);

				$order   = $this->request->get_param( 'order' );
				$orderby = $this->request->get_param( 'orderby' );
				if ( ( ! empty( $order ) ) && ( ! empty( $orderby ) ) ) {
					$args['order']   = $order;
					$args['orderby'] = $orderby;
				}

				$stat_refs             = $stat_ref_mapper->fetchSelected( $args );
				$this->stat_refs_count = $stat_ref_mapper->fetchSelectedCount( $args );

				if ( $stat_refs ) {
					foreach ( $stat_refs as $ref ) {
						$ref->setMapper( $stat_ref_mapper );
						$this->stat_refs[ $ref->getStatisticRefId() ] = $ref;
					}
				}

				return;
			}

			$this->stat_refs[ $stat_ref_id ] = $stat_ref_mapper->fetchAllByRef( $stat_ref_id );
			$this->stat_refs[ $stat_ref_id ] = $this->stat_refs[ $stat_ref_id ] ? $this->stat_refs[ $stat_ref_id ]->setMapper( $stat_ref_mapper ) : null;
		}

		/**
		 * User IDs for fetching statistics.
		 *
		 * @since 3.3.0
		 *
		 * 1. Admins have access to all statistics, thus null will be returned.
		 * 2. Group leaders will have access to statistics of only those
		 *    users which belong to their groups.
		 * 3. Other logged in users will have access to only their stats.
		 * 4. Logged out user cannot access stats.
		 */
		private function users_for_stats() {

			if ( ! is_null( $this->users_for_stats ) ) {
				return $this->users_for_stats;
			}

			if ( is_null( $this->request ) ) {
				return $this->users_for_stats;
			}

			$quiz_id = $this->request->get_param( 'quiz' );
			if ( empty( $quiz_id ) ) {
				$this->users_for_stats = array( 0 );
				return $this->users_for_stats;
			}

			if ( learndash_is_admin_user() ) {
				$this->users_for_stats = array();
			} elseif ( learndash_is_group_leader_user() ) {
				if ( learndash_get_group_leader_manage_courses() ) {
					/**
					 * If the Group Leader can manage_courses they have will access
					 * to all quizzes. So they are treated like the admin user.
					 */
					$this->users_for_stats = array();
				} else {
					/**
					 * Else we need to figure out of the quiz requested is part of a
					 * Course within Group managed by the Group Leader.
					 */
					$quiz_users     = array();
					$leader_courses = learndash_get_groups_administrators_courses( get_current_user_id() );
					if ( ! empty( $leader_courses ) ) {
						$quiz_courses = array();
						if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
							$quiz_courses = learndash_get_courses_for_step( $quiz_id, true );
						} else {
							$quiz_course = learndash_get_setting( $quiz_id, 'course' );
							if ( ! empty( $quiz_course ) ) {
								$quiz_courses = array( $quiz_course );
							}
						}

						if ( ! empty( $quiz_courses ) ) {
							$common_courses = array_intersect( $quiz_courses, $leader_courses );
							if ( ! empty( $common_courses ) ) {
								/**
								 * The following will get a list of all users within the Groups
								 * managed by the Group Leader. This list of users will be passed
								 * to the query logic to limit the selected rows.
								 *
								 * This is not 100% accurate because we don't limit the rows based
								 * on the associated courses. Consider if Shared Course steps is
								 * enabled and the quiz is part of two courses and those courses
								 * are associated with multiple groups. And the user is in both
								 * groups. So potentially we will pull in statistics records for
								 * the other course quizzes.
								 */
								$quiz_users = learndash_get_groups_administrators_users( get_current_user_id() );
							}
						}
					}

					if ( ! empty( $quiz_users ) ) {
						$this->users_for_stats = $quiz_users;
					} else {
						$this->users_for_stats = array( 0 );
					}
				}
			} else {
				// If here then non-admin and non-group leader user.
				$quiz_id = $this->request->get_param( 'quiz' );
				if ( ! empty( $quiz_id ) ) {
					if ( get_post_meta( $quiz_id, '_viewProfileStatistics', true ) ) {
						$this->users_for_stats = (array) get_current_user_id();
						return $this->users_for_stats;
					}
				}

				$this->users_for_stats = array( 0 );
			}

			return $this->users_for_stats;
		}

		/**
		 * Get correct/incorrect count for a particular statistics_ref_id.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $stats Stats for particular statistic ref.
		 * @param string $type  Correct or incorrect count.
		 *
		 * @return integer
		 */
		private function get_count( array $stats, $type = 'getCorrectCount' ) {
			$count = 0;

			foreach ( $stats as $stat ) {
				$count = $count + $stat->$type();
			}

			return $count;
		}

		/**
		 * Prepare the single statistics object for API response.
		 *
		 * @since 3.3.0
		 *
		 * @return stdClass
		 */
		private function stat_response_object() {
			$stats               = ( new WpProQuiz_Model_StatisticMapper() )->fetchAllByRef( $this->current()->getStatisticRefId() );
			$stat_response       = new stdClass();
			$stat_response->id   = $this->current()->getStatisticRefId();
			$stat_response->quiz = $this->getQuizId();
			$stat_response->user = $this->current()->getUserId();
			$stat_response->date = $this->prepare_date_response( gmdate( 'Y-m-d H:i:s', $this->current()->getCreateTime() ) );

			$stat_response->answers_correct   = $this->get_count( $stats, 'getCorrectCount' );
			$stat_response->answers_incorrect = $this->get_count( $stats, 'getIncorrectCount' );
			$stat_response->points_scored     = $this->get_count( $stats, 'getPoints' );
			$stat_response->points_total      = (int) $this->current()->getMapper()->fetchTotalPoints( $this->current()->getStatisticRefId() );

			return $stat_response;
		}

		/**
		 * Get user's quiz details from usermeta.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		private function get_user_quiz_details() {
			if ( isset( $this->user_quiz_data[ $this->current()->getUserId() ] ) ) {

				return $this->user_quiz_data[ $this->current()->getUserId() ];
			}

			$user_quizzes       = (array) get_user_meta( $this->current()->getUserId(), '_sfwd-quizzes', true );
			$user_quizzes_stats = array();
			/**
			 * We want to rebuild/re-index the quizzes listing to be by
			 * the statistics ref ID.
			 */
			if ( ! empty( $user_quizzes ) ) {
				foreach ( $user_quizzes as $user_quiz ) {
					if ( ( ! isset( $user_quiz['statistic_ref_id'] ) ) || ( empty( $user_quiz['statistic_ref_id'] ) ) ) {
						continue;
					}

					$statistic_ref_id                        = absint( $user_quiz['statistic_ref_id'] );
					$user_quizzes_stats[ $statistic_ref_id ] = $user_quiz;
				}
			}
			$this->user_quiz_data[ $this->current()->getUserId() ] = $user_quizzes_stats;

			return $this->user_quiz_data[ $this->current()->getUserId() ];
		}

		/**
		 * Get quiz id for current statistics.
		 *
		 * @since 3.3.0
		 *
		 * @return int
		 */
		private function getQuizId() {
			$quiz_data = $this->get_user_quiz_details();

			$current_stat_id = (int) $this->current()->getStatisticRefId();
			if ( isset( $quiz_data[ $current_stat_id ]['quiz'] ) ) {
				return $quiz_data[ $current_stat_id ]['quiz'];
			}

			return 0;
		}

		/**
		 * Build the answer type object and returns it.
		 * It throws exception for any invalid type which will
		 * be caught by callee.
		 *
		 * @since 3.3.0
		 *
		 * @param string $type Type of answer object we want to build.
		 * @param array  $args Argument for class constructor.
		 *
		 * @return LDLMS_Answer
		 * @throws Exception Exception for invalid type.
		 */
		private function make_answer_obj( $type, $args = array() ) {

			switch ( $type ) {

				case 'single':
				case 'multiple':
					$object = new ReflectionClass( LDLMS_Base_Answer_Type::class );
					return $object->newInstanceArgs( $args );

				case 'sort_answer':
				case 'matrix_sort_answer':
					$object = new ReflectionClass( LDLMS_Sort_Answer::class );
					return $object->newInstanceArgs( $args );

				case 'assessment_answer':
					$object = new ReflectionClass( LDLMS_Assessment_Answer::class );
					return $object->newInstanceArgs( $args );

				case 'cloze_answer':
					$object = new ReflectionClass( LDLMS_Cloze_Answer::class );
					return $object->newInstanceArgs( $args );

				case 'free_answer':
					$object = new ReflectionClass( LDLMS_Free_Answer::class );
					return $object->newInstanceArgs( $args );

				case 'essay':
					$object = new ReflectionClass( LDLMS_Essay_Answer::class );
					return $object->newInstanceArgs( $args );

				default:
					// translators: placeholder: question.
					throw new Exception( sprintf( esc_html_x( 'Invalid %s type supplied', 'placeholder: question', 'learndash' ), learndash_get_custom_label( 'question' ) ), 404 );
			}
		}

		/**
		 * Get WpProQuiz_Model_Statistic object from list of a particular stat.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $stats    Statistics for the current ref id.
		 * @param string $method   Method to call.
		 * @param mixed  $expected Expected result.
		 *
		 * @return WpProQuiz_Model_Statistic|null
		 */
		private function get_statistics_mode_from_list( array $stats, $method, $expected ) {

			foreach ( $stats as $key => $stat ) {
				if ( $stat instanceof WpProQuiz_Model_Statistic && method_exists( $stat, $method ) ) {
					$result = $stat->$method();

					if ( $expected === $result ) {
						return $stats[ $key ];
					}
				}
			}

			return null;
		}

		/**
		 * Reset current state of an object and store current state
		 * in a global variable.
		 *
		 * This will be useful because if we pass embed parameter, same class
		 * will be called with a new request and Iterator pointers need to be
		 * reset at that time.
		 *
		 * @since 3.3.0
		 */
		private function reset_state() {

			global $ld_qs_api_vars;
			/**
			 * Reset the state only if we have some data in memory upfront
			 * because embed will be called only when there is valid data
			 * available in memory.
			 */
			if ( ! empty( $this->stat_refs ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$GLOBALS['ld_qs_api_vars'] = $ld_qs_api_vars ? $ld_qs_api_vars : array();

				$object_vars = get_object_vars( $this );

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$GLOBALS['ld_qs_api_vars'][] = $object_vars;

				foreach ( $object_vars as $property => $var ) {

					switch ( gettype( $var ) ) {

						case 'integer':
							$this->$property = 0;
							break;
						case 'string':
							$this->$property = '';
							break;
						case 'array':
							$this->$property = array();
							break;
						default:
							$this->$property = null;
					}
				}
			}
		}

		/**
		 * Restore the previously stored state of an object from
		 * global variable.
		 *
		 * @since 3.3.0
		 */
		private function restore_state() {
			global $ld_qs_api_vars;

			if ( is_array( $ld_qs_api_vars ) && count( $ld_qs_api_vars ) ) {
				$recent_state = array_pop( $ld_qs_api_vars );

				foreach ( $recent_state as $property => $val ) {
					$this->$property = $val;
				}
			}
		}

		/**
		 * Retrieves the query params for the posts collection.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params_statistics() {
			$query_params = parent::get_collection_params();

			if ( isset( $query_params['search'] ) ) {
				unset( $query_params['search'] );
			}

			$query_params['order'] = array(
				'description' => __( 'Order sort attribute ascending or descending.', 'learndash' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			);

			$query_params['orderby'] = array(
				'description' => __( 'Sort collection by object attribute.', 'learndash' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array(
					'date',
					'id',
					'quiz',
					'user',
				),
			);

			if ( ! isset( $query_params['quiz'] ) ) {
				$query_params['quiz'] = array(
					'description' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x(
							'Filter by %s ID',
							'placeholder: Quiz',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				);
			}

			if ( ! isset( $query_params['user'] ) ) {
				$query_params['user'] = array(
					'description' => esc_html__( 'Filter by User ID', 'learndash' ),
					'type'        => 'integer',
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				);
			}

			$query_params['after'] = array(
				'description' => __( 'Limit response items after a given ISO8601 compliant date.', 'learndash' ),
				'type'        => 'string',
				'format'      => 'date-time',
			);

			$query_params['before'] = array(
				'description' => __( 'Limit response to items before a given ISO8601 compliant date.', 'learndash' ),
				'type'        => 'string',
				'format'      => 'date-time',
			);

			return $query_params;
		}

		/**
		 * Retrieves the query params for the posts collection.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params_statistics_questions() {
			$query_params = parent::get_collection_params();

			if ( isset( $query_params['search'] ) ) {
				unset( $query_params['search'] );
			}

			return $query_params;
		}

		/**
		 * Get the REST URL setting.
		 *
		 * @since 3.3.0
		 *
		 * @param string $rest_slug Settings REST slug.
		 * @param string $default_value Default value if rest_slug is not found.
		 */
		protected function get_rest_base( $rest_slug = '', $default_value = '' ) {
			$rest_base_value = null;
			if ( ! empty( $rest_slug ) ) {
				$rest_slug      .= '_' . $this->version;
				$rest_base_value = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', $rest_slug, $default_value );
			}

			if ( is_null( $rest_base_value ) ) {
				$rest_base_value = $default_value;
			}

			return $rest_base_value;
		}

		/**
		 * Retrieves the Stats schema, conforming to JSON Schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array Item schema data.
		 */
		public function get_stats_questions_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'quiz-statistics-questions',
				'parent'     => 'quiz-statistics',
				'type'       => 'object',
				'properties' => array(
					'id'            => array(
						'description' => __( 'Unique ID for Statistics Question.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'statistic'     => array(
						'description' => __( 'Statistics Ref ID.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'quiz'          => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'%s ID',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'question'      => array(
						'description' => sprintf(
							// translators: placeholder: Question.
							esc_html_x(
								'%s ID',
								'placeholder: Question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'question_type' => array(
						'description' => sprintf(
							// translators: placeholder: Question.
							esc_html_x(
								'%s Type',
								'placeholder: Question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'points_scored' => array(
						'description' => esc_html__( 'Points scored.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'points_total'  => array(
						'description' => esc_html__( 'Points total.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'answers'       => array(
						'description' => sprintf(
							// translators: placeholder: Question.
							esc_html_x(
								'The collection of %s answers.',
								'placeholder: Question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'object',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'student'       => array(
						'description' => __( 'The collection of student submitted answers.', 'learndash' ),
						'type'        => 'object',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);

			return $schema;
		}

		/**
		 * Retrieves the Stats schema, conforming to JSON Schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array Item schema data.
		 */
		public function get_stats_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'quiz-statistics',
				'parent'     => 'quiz',
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'description' => __( 'Statistics Ref ID.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'quiz'              => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'%s ID.',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'user'              => array(
						'description' => __( 'User ID.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'date'              => array(
						'description' => __( 'Date.', 'learndash' ),
						'type'        => array( 'string', null ),
						'format'      => 'date-time',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'answers_correct'   => array(
						'description' => __( 'Answer correct.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'answers_incorrect' => array(
						'description' => __( 'Answer incorrect.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
				),
			);

			return $schema;
		}

		/**
		 * Checks the post_date_gmt or modified_gmt and prepare any post or
		 * modified date for single post output.
		 *
		 * @since 3.4.2
		 *
		 * @param string      $date_gmt GMT publication time.
		 * @param string|null $date     Optional. Local publication time. Default null.
		 * @return string|null ISO8601/RFC3339 formatted datetime, otherwise null.
		 */
		protected function prepare_date_response( $date_gmt, $date = null ) {
			if ( '0000-00-00 00:00:00' === $date_gmt ) {
				return null;
			}

			if ( isset( $date ) ) {
				return mysql_to_rfc3339( $date ); // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
			}

			return mysql_to_rfc3339( $date_gmt ); // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		}

		// End of functions.
	}
}
