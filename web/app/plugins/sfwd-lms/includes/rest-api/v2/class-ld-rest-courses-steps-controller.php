<?php
/**
 * LearnDash REST API V2 Courses Steps Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between the LearnDash Courses (sfwd-courses) and the Course Steps.
 * Course Steps are Lessons (sfwd-lessons), Topics (sfwd-topic), and
 * quizzes (sfwd-quiz) post types.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Steps_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Courses Steps Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Courses_Steps_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Supported Collection Parameters.
		 *
		 * @since 3.3.0
		 *
		 * @var array $supported_collection_params.
		 */
		private $supported_collection_params = array(
			'filter' => 'filter',
		);

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'course' );
			}

			$this->post_type  = $post_type;
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'courses' );
			$this->rest_sub_base = $this->get_rest_base( 'courses-steps' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			$this->register_fields();

			$schema = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);

			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			$courses_namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;
			$courses_rest_base = $this->get_rest_base( 'courses' );

			register_rest_route(
				$courses_namespace,
				'/' . $courses_rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base,
				array(
					'args'   => array(
						'id' => array(
							'description' => sprintf(
								// translators: placeholder: Course.
								esc_html_x(
									'%s ID',
									'placeholder: Course',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_course_steps' ),
						'permission_callback' => array( $this, 'get_course_steps_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_course_steps' ),
						'permission_callback' => array( $this, 'update_course_steps_permissions_check' ),
						'args'                => [
							'sfwd-lessons' => [
								'description'          => sprintf(
									// translators: %1$s: singular course label. %2$s: plural lesson label. %3$s: plural topics label. %4$s: plural quizzes label. %5$s: singular lesson label.
									__( '%1$s %2$s structure with %3$s and %4$s. Keys must be valid %5$s post IDs.', 'learndash' ),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label_lower( 'lessons' ),
									learndash_get_custom_label_lower( 'topics' ),
									learndash_get_custom_label_lower( 'quizzes' ),
									learndash_get_custom_label_lower( 'lesson' )
								),
								'type'                 => 'object',
								'required'             => false,
								'additionalProperties' => [
									'description' => sprintf(
										// translators: %s: singular lesson label.
										__( '%s post ID (e.g., "123")', 'learndash' ),
										learndash_get_custom_label( 'lesson' )
									),
									'type'        => 'object',
									'properties'  => [
										'sfwd-topic' => [
											'description' => sprintf(
												// translators: %1$s: singular topic label. %2$s: singular lesson label. %3$s: singular topic label.
												__( '%1$s within this %2$s. Keys must be valid %3$s post IDs.', 'learndash' ),
												learndash_get_custom_label( 'topic' ),
												learndash_get_custom_label_lower( 'lesson' ),
												learndash_get_custom_label_lower( 'topic' )
											),
											'type'        => 'object',
											'additionalProperties' => [
												'description' => sprintf(
													// translators: %s: singular topic label.
													__( '%s post ID (e.g., "123")', 'learndash' ),
													learndash_get_custom_label( 'topic' )
												),
												'type' => 'object',
												'properties' => [
													'sfwd-quiz' => [
														'description' => sprintf(
															// translators: %1$s: singular quiz label. %2$s: singular topic label.
															__( '%1$s within this %2$s. Keys must be valid %3$s post IDs.', 'learndash' ),
															learndash_get_custom_label( 'quiz' ),
															learndash_get_custom_label_lower( 'topic' ),
															learndash_get_custom_label_lower( 'quiz' )
														),
														'type'       => 'object',
														'additionalProperties' => [
															'description' => sprintf(
																// translators: %s: singular quiz label.
																__( '%s post ID (e.g., "123")', 'learndash' ),
																learndash_get_custom_label( 'quiz' )
															),
															'type' => 'object',
														],
													],
												],
											],
											'example'     => [
												'3' => new stdClass(),
												'4' => [
													LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) => [
														'5' => new stdClass(),
													],
												],
											],
										],
										'sfwd-quiz'  => [
											'description' => sprintf(
												// translators: %1$s: plural quiz label. %2$s: singular lesson label. %3$s: singular quiz label.
												__( '%1$s directly within this %2$s. Keys must be valid %3$s post IDs.', 'learndash' ),
												learndash_get_custom_label( 'quizzes' ),
												learndash_get_custom_label_lower( 'lesson' ),
												learndash_get_custom_label_lower( 'quiz' )
											),
											'type'        => 'object',
											'additionalProperties' => [
												'description' => sprintf(
													// translators: %s: singular quiz label.
													__( '%s post ID (e.g., "123")', 'learndash' ),
													learndash_get_custom_label( 'quiz' )
												),
												'type' => 'object',
											],
											'example'     => [
												'5' => new stdClass(),
											],
										],
									],
								],
								'example'              => [
									'1' => new stdClass(),
									'2' => [
										LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ) => [
											'3' => new stdClass(),
											'4' => [
												LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) => [
													'5' => new stdClass(),
												],
											],
										],
									],
								],
							],
							'sfwd-quiz'    => [
								'description'          => sprintf(
									// translators: %1$s: singular course label. %2$s: plural quiz label. %3$s: singular quiz label.
									__( '%1$s %2$s structure. Keys must be valid %3$s post IDs.', 'learndash' ),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label_lower( 'quizzes' ),
									learndash_get_custom_label( 'quiz' )
								),
								'type'                 => 'object',
								'required'             => false,
								'additionalProperties' => [
									'description' => sprintf(
										// translators: %s: singular quiz label.
										__( '%s post ID (e.g., "123")', 'learndash' ),
										learndash_get_custom_label( 'quiz' )
									),
									'type'        => 'object',
								],
								'example'              => [
									'6' => new stdClass(),
								],
							],
						],
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Gets public schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_schema() {

			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'course-steps',
				'parent'     => 'course',
				'type'       => 'object',
				'properties' => array(
					'id'   => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
						'readonly'    => true,
					),
					'type' => array(
						'description' => sprintf(
							// translators: placeholder: course.
							esc_html_x( 'The %s step type.', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
						'type'        => 'string',
						'enum'        => array(
							'all',
							'h',
							'l',
							't',
							'r',
							'co',
							'sections',
							'legacy',
						),
						'context'     => array( 'view', 'edit' ),
					),
				),
			);

			return $schema;
		}

		/**
		 * Retrieves the query params for collections.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params() {
			$query_params_default = parent::get_collection_params();

			$query_params_default['context']['default'] = 'view';

			$query_params            = array();
			$query_params['context'] = $query_params_default['context'];
			$query_params['type']    = array(
				'description' => __( 'Filter returned results by step type.', 'learndash' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => array(
					'all',
					'h',
					'l',
					't',
					'r',
					'co',
					'sections',
					'legacy',
				),
			);

			return $query_params;
		}

		/**
		 * Permissions check for getting course steps.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_course_steps_permissions_check( $request ) {
			if ( ! is_user_logged_in() ) {
				/**
				 * Filter to allow anonymous access to Course Steps REST endpoint.
				 *
				 * @since 3.4.2
				 * @param bool $allow true/false.
				 * @return bool true Return true to allow access to anonymous user.
				 */
				if ( apply_filters( 'learndash_rest_course_steps_allow_anonymous_read', false ) ) {
					return true;
				}
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			} elseif ( learndash_is_admin_user() ) {
				return true;
			}

			return new WP_Error(
				'ld_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		/**
		 * Checks permission to update course steps.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function update_course_steps_permissions_check( $request ) {
			if ( current_user_can( 'edit_courses' ) ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Retrieves course steps.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_course_steps( $request ) {
			$data = array();

			$course = $this->get_post( $request['id'] );
			if ( is_wp_error( $course ) ) {
				return $course;
			}

			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( absint( $course->ID ) );
			if ( $ld_course_steps_object ) {
				$data = $ld_course_steps_object->get_steps( $request['type'] );

				// Are we handling embeds today?
				if ( isset( $_GET['_embed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$data_embed = $ld_course_steps_object->get_steps( 't' );
					if ( ( isset( $data_embed ) ) && ( ! empty( $data_embed ) ) ) {
						$embeds = array();

						foreach ( $data_embed as $post_type => $posts_ids ) {
							if ( ( ! empty( $posts_ids ) ) && ( ( in_array( $post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) ) {

								$base_slug = $post_type;
								if ( 'sfwd-courses' === $post_type ) {
									$base_slug = 'lessons';
								} elseif ( 'sfwd-lessons' === $post_type ) {
									$base_slug = 'lessons';
								} elseif ( 'sfwd-topic' === $post_type ) {
									$base_slug = 'topics';
								} elseif ( 'sfwd-quiz' === $post_type ) {
									$base_slug = 'quizzes';
								}

								$route_url = '/' . $this->namespace . '/' . $this->get_rest_base( $base_slug );
								$request   = new WP_REST_Request( 'GET', $route_url );
								$request->set_query_params( array( 'include' => $posts_ids ) );

								$response  = rest_do_request( $request );
								$server    = rest_get_server();
								$rest_data = $server->response_to_data( $response, false );
								if ( ! empty( $rest_data ) ) {
									foreach ( $rest_data as $rest_post ) {
										if ( ( is_array( $rest_post ) ) && ( isset( $rest_post['id'] ) ) ) {
											$embeds[ $rest_post['id'] ] = $rest_post;
										}
									}
								}
							}
						}

						if ( ! empty( $embeds ) ) {
							$embedded  = array();
							$has_links = count( array_filter( $embeds ) );
							if ( $has_links ) {
								$embedded['steps'] = $embeds;
							}
							$data['_embedded'] = $embedded;
						}
						// }
					}
				}
			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
		}

		/**
		 * Updates the course steps.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_course_steps( $request ) {
			$current_user_id = get_current_user_id();
			if ( empty( $current_user_id ) ) {
				return new WP_Error( 'rest_not_logged_in', esc_html__( 'You are not currently logged in.', 'learndash' ), array( 'status' => 401 ) );
			}
			$current_user = wp_get_current_user();

			$course = $this->get_post( $request['id'] );
			if ( is_wp_error( $course ) ) {
				return $course;
			}

			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course->ID ) );

			$body = $request->get_body();
			if ( ! empty( $body ) ) {
				$body = json_decode( $body, true );
				if ( ( $body ) && ( json_last_error() == JSON_ERROR_NONE ) ) {
					$steps = array();

					$steps['sfwd-lessons'] = array();
					$steps['sfwd-quiz']    = array();

					if ( ( isset( $body['sfwd-lessons'] ) ) && ( ! empty( $body['sfwd-lessons'] ) ) ) {
						foreach ( $body['sfwd-lessons'] as $lesson_id => $lesson_set ) {
							$steps['sfwd-lessons'][ $lesson_id ]               = array();
							$steps['sfwd-lessons'][ $lesson_id ]['sfwd-topic'] = array();
							$steps['sfwd-lessons'][ $lesson_id ]['sfwd-quiz']  = array();

							if ( ( isset( $lesson_set['sfwd-topic'] ) ) && ( ! empty( $lesson_set['sfwd-topic'] ) ) ) {

								foreach ( $lesson_set['sfwd-topic'] as $topic_id => $topic_set ) {
									$steps['sfwd-lessons'][ $lesson_id ]['sfwd-topic'][ $topic_id ]              = array();
									$steps['sfwd-lessons'][ $lesson_id ]['sfwd-topic'][ $topic_id ]['sfwd-quiz'] = array();

									if ( ( isset( $topic_set['sfwd-quiz'] ) ) && ( ! empty( $topic_set['sfwd-quiz'] ) ) ) {
										foreach ( $topic_set['sfwd-quiz'] as $quiz_id => $quiz_set ) {
											$steps['sfwd-lessons'][ $lesson_id ]['sfwd-topic'][ $topic_id ]['sfwd-quiz'][ $quiz_id ] = array();
										}
									}
								}
							}

							if ( ( isset( $lesson_set['sfwd-quiz'] ) ) && ( ! empty( $lesson_set['sfwd-quiz'] ) ) ) {
								foreach ( $lesson_set['sfwd-quiz'] as $quiz_id => $quiz_set ) {
									$steps['sfwd-lessons'][ $lesson_id ]['sfwd-quiz'][ $quiz_id ] = array();
								}
							}
						}
					}

					if ( ( isset( $body['sfwd-quiz'] ) ) && ( ! empty( $body['sfwd-quiz'] ) ) ) {
						$steps['sfwd-quiz'] = $body['sfwd-quiz'];
					}

					$ld_course_steps_object->set_steps( $steps );
				}
			}

			$ld_course_steps_object->load_steps();
			$course_steps = $ld_course_steps_object->get_steps( 'h' );
			$data         = $course_steps;

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
		}

		// End of functions.
	}
}
