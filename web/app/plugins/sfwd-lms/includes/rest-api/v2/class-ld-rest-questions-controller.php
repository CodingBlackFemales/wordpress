<?php
/**
 * LearnDash REST API V2 Quiz Questions Post Controller.
 *
 * This Controller class is used for the LearnDash Questions (sfwd-question)
 * custom post type.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Questions_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Quiz Questions Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Questions_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Current Post Metaboxes Fields
		 *
		 * @var array $fields.
		 */
		protected $fields = array();

		/**
		 * Metaboxes fields map
		 *
		 * @var array
		 */
		protected $fields_map = array();

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'question' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'questions' );
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields() {
			$this->register_fields_metabox();

			do_action( 'learndash_rest_register_fields', $this->post_type, $this );
		}

		/**
		 * Register the Settings Fields from the Post Metaboxes.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields_metabox() {
			global $learndash_question_types;

			$this->fields_map = array(
				'_quizId'                         => 'quiz',
				'_answerType'                     => 'question_type',
				'_points'                         => 'points_total',
				'_answerPointsActivated'          => 'points_per_answer',
				'_showPointsInBox'                => 'points_show_in_message',
				'_answerPointsDiffModusActivated' => 'points_diff_modus',
				'_disableCorrect'                 => 'disable_correct',
				'_correctMsg'                     => 'correct_message',
				'_incorrectMsg'                   => 'incorrect_message',
				'_correctSameText'                => 'correct_same',
				'_tipEnabled'                     => 'hints_enabled',
				'_tipMsg'                         => 'hints_message',
				'_answer_data'                    => 'answers',
			);

			$this->fields = array(
				'quiz'                   => array(
					'schema'          => array(
						'field_key'   => 'quiz',
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
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'question_type'          => array(
					'schema'          => array(
						'field_key'   => 'question_type',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'%s type.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'enum',
						'enum'        => array_keys( $learndash_question_types ),
						'required'    => false,
						'default'     => 'single',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_total'           => array(
					'schema'          => array(
						'field_key'   => 'points_total',
						'description' => esc_html__( 'Total Points amount', 'learndash' ),
						'type'        => 'integer',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_per_answer'      => array(
					'schema'          => array(
						'field_key'   => 'points_per_answer',
						'description' => esc_html__( 'Different points for each answer', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_show_in_message' => array(
					'schema'          => array(
						'field_key'   => 'points_show_in_message',
						'description' => esc_html__( 'Show reached points in the correct/incorrect message?', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_diff_modus'      => array(
					'schema'          => array(
						'field_key'   => 'points_diff_modus',
						'description' => esc_html__( 'Different points - modus 2 activate', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),

				'disable_correct'        => array(
					'schema'          => array(
						'field_key'   => 'disable_correct',
						'description' => esc_html__( 'Disable answer correct setting.', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'correct_message'        => array(
					'schema'          => array(
						'field_key'   => 'correct_message',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'Message shown when %s is correct.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'object',
						'required'    => false,
						'properties'  => array(
							'raw'      => array(
								'description' => 'Content for the object, as it exists in the database.',
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => 'HTML content for the object, transformed for display.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'incorrect_message'      => array(
					'schema'          => array(
						'field_key'   => 'incorrect_message',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'Message shown when %s is correct.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'object',
						'required'    => false,
						'properties'  => array(
							'raw'      => array(
								'description' => 'Content for the object, as it exists in the database.',
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => 'HTML content for the object, transformed for display.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'correct_same'           => array(
					'schema'          => array(
						'field_key'   => 'correct_same',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'Activate hint for this %s.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'hints_enabled'          => array(
					'schema'          => array(
						'field_key'   => 'hints_enabled',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'Activate hint for this %s.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'hints_message'          => array(
					'schema'          => array(
						'field_key'   => 'hints_message',
						'description' => esc_html__( 'Hint message.', 'learndash' ),
						'type'        => 'object',
						'required'    => false,
						'properties'  => array(
							'raw'      => array(
								'description' => 'Content for the object, as it exists in the database.',
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => 'HTML content for the object, transformed for display.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'answers'                => array(
					'schema'          => array(
						'field_key'   => 'answers',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'%s answer sets.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'object',
						'required'    => true,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
			);

			foreach ( $this->fields as $field_key => $field_args ) {
				register_rest_field(
					$this->post_type,
					$field_key,
					$field_args
				);
			}
		}

		/**
		 * Gets public schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_schema() {

			$schema = parent::get_public_item_schema();

			$schema['title'] = 'question';

			return $schema;
		}

		/**
		 * For LearnDash post type we override the default order/orderby
		 * to ASC/title instead of the WP default DESC/date.
		 *
		 * @since 3.3.0
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type string.
		 */
		public function rest_collection_params_filter( array $query_params, WP_Post_Type $post_type ) {
			$query_params = parent::rest_collection_params_filter( $query_params, $post_type );

			if ( ! isset( $query_params['quiz'] ) ) {
				$query_params['quiz'] = array(
					'description' => sprintf(
						// translators: placeholder: quiz.
						esc_html_x(
							'Limit results to be within a specific %s.',
							'placeholder: quiz',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'type'        => 'integer',
				);
			}

			return $query_params;
		}

		/**
		 * Check user permission to get/access Quizzes.
		 *
		 * @since 3.3.0
		 *
		 * @param object $request  WP_REST_Request instance.
		 * @return bool True is used can get item.
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			if ( ( true === $return ) && ( 'view' === $request['context'] ) ) {
				$this->rest_init_request_posts( $request );

				// If the archive setting is enabled we allow full listing.
				if ( ! $this->rest_post_type_has_archive( $this->post_type ) ) {
					if ( is_null( $this->quiz_post ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: Quiz.
								esc_html_x(
									'Invalid %s ID',
									'placeholder: Quiz',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'quiz' )
							) . ' ' . __CLASS__,
							array( 'status' => 404 )
						);
					}

					if ( ! sfwd_lms_has_access( $this->quiz_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		/**
		 * Filter Topics query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args  Key value array of query var to query value.
		 * @param WP_REST_Request $request     The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			// The course_post should be set in the local method get_items_permissions_check().
			if ( ! is_null( $this->quiz_post ) ) {
				$questions_ids = array_keys( learndash_get_quiz_questions( $this->quiz_post->ID ) );
				if ( ! empty( $questions_ids ) ) {
					$query_args['post__in'] = $query_args['post__in'] ? array_intersect( $questions_ids, $query_args['post__in'] ) : $questions_ids;

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ! isset( $_GET['orderby'] ) ) {
						$query_args['orderby'] = 'post__in';
					}

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ! isset( $_GET['order'] ) ) {
						$query_args['order'] = '';
					}
				} else {
					$query_args['post__in'] = array( 0 );
				}
			}

			return $query_args;
		}

		/**
		 * Delete one item from the collection.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 *
		 * @return WP_Error|WP_REST_Request
		 */
		public function delete_item( $request ) {
			$params      = $request->get_params();
			$question_id = $params['id'];
			if ( ! empty( $question_id ) ) {
				$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
				$question_mapper = new \WpProQuiz_Model_QuestionMapper();

				if ( false !== $question_mapper->delete( $question_pro_id ) &&
					false !== wp_delete_post( $params['id'], false ) ) {
					return new WP_REST_Response( true, 200 );
				}
			}

			return new WP_Error(
				'cant-delete',
				sprintf(
				// translators: placeholder: Question label.
					esc_html_x( 'Could not delete the %s.', 'placeholder: Question label', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'question' )
				),
				array( 'status' => 500 )
			);
		}

		/**
		 * Update one item from the collection
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 *
		 * @return WP_Error|WP_REST_Request
		 */
		public function update_item( $request ) {
			$params      = $request->get_params();
			$question_id = $params['id'];
			if ( ! empty( $question_id ) ) {
				$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
				$question_mapper = new \WpProQuiz_Model_QuestionMapper();

				$question_model = $question_mapper->fetch( $question_pro_id );

				// Update answer data if available.
				if ( isset( $params['_answerData'] ) && is_string( $params['_answerData'] ) ) {
					$params['_answerData'] = json_decode( $params['_answerData'], true );
				}

				// Also save points at question's post meta data.
				if ( isset( $params['_points'] ) ) {
					update_post_meta( $question_id, 'question_points', $params['_points'] );
				}

				// Update question's post content.
				if ( isset( $params['_question'] ) ) {
					wp_update_post(
						array(
							'ID'           => $question_id,
							'post_content' => wp_slash( $params['_question'] ),
						)
					);
				}

				// Update the question object with new data.
				$question_model->set_array_to_object( $params );

				// Save the new data to database.
				$question_mapper->save( $question_model );

				return new WP_REST_Response( $this->get_question_data( $question_id ), 200 );
			}

			return new WP_Error(
				'cant-delete',
				sprintf(
				// translators: placeholder: Question.
					esc_html_x( 'Could not update the %s.', 'placeholder: Question', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'question' )
				),
				array( 'status' => 500 )
			);
		}

		/**
		 * Get question data.
		 *
		 * @since 3.3.0
		 *
		 * @param int $question_id The question ID.
		 *
		 * @return object
		 */
		public function get_question_data( $question_id = 0 ) {
			$data = array();

			if ( ! empty( $question_id ) ) {
				// Get Answers from Question.
				$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
				$question_mapper = new \WpProQuiz_Model_QuestionMapper();

				if ( ! empty( $question_pro_id ) ) {
					$question_model = $question_mapper->fetch( $question_pro_id );
				} else {
					$question_model = $question_mapper->fetch( null );
				}

				// Get data as array.
				$question_data = $question_model->get_object_as_array();

				$answer_data = array();

				// Get answer data.
				foreach ( $question_data['_answerData'] as $answer ) {
					$answer_data[] = $answer->get_object_as_array();
				}

				unset( $question_data['_answerData'] );

				$question_data['_answerData'] = $answer_data;

				// Generate output object.
				$data = array_merge(
					$question_data,
					array(
						'question_id'         => $question_id,
						'question_post_title' => get_the_title( $question_id ),
					)
				);
			}
			return $data;
		}

		/**
		 * Get REST Setting Field value.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $postdata   Post data array.
		 * @param string          $field_name Field Name for $postdata value.
		 * @param WP_REST_Request $request    Request object.
		 * @param string          $post_type  Post Type for request.
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			static $question_pro_data = array();

			$return = null;

			$field_map_idx = array_search( $field_name, $this->fields_map, true );
			if ( false !== $field_map_idx ) {

				$question_post_id = 0;
				if ( ( isset( $postdata['id'] ) ) && ( ! empty( $postdata['id'] ) ) ) {
					$question_post_id = absint( $postdata['id'] );
				}

				if ( ! empty( $question_post_id ) ) {
					if ( isset( $question_pro_data[ $question_post_id ] ) ) {
						$question_data = $question_pro_data[ $question_post_id ];
					} else {
						$question_data = array();

						$quiz_pro_id = get_post_meta( $question_post_id, 'question_pro_id', true );
						$quiz_pro_id = absint( $quiz_pro_id );
						if ( ! empty( $quiz_pro_id ) ) {
							$question_mapper = new \WpProQuiz_Model_QuestionMapper();
							$question_model  = $question_mapper->fetch( $quiz_pro_id );
							$question_data   = $question_model->get_object_as_array();

							$question_pro_data[ $question_post_id ] = $question_data;
						}
					}

					switch ( $field_name ) {
						case 'quiz':
							$return = get_post_meta( $question_post_id, 'quiz_id', true );
							$return = absint( $return );
							break;

						case 'correct_message':
						case 'incorrect_message':
						case 'hints_message':
							$return = array(
								// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
								'rendered' => apply_filters( 'the_content', $question_data[ $field_map_idx ] ),
							);

							// If the context is 'edit' we provide the raw content.
							if ( ( 'edit' === $request['context'] ) ) {
								$return['raw'] = $question_data[ $field_map_idx ];
							}

							break;

						case 'points_per_answer':
						case 'points_show_in_message':
						case 'points_diff_modus':
						case 'disable_correct':
						case 'correct_same':
						case 'hints_enabled':
							$return = (bool) $question_data[ $field_map_idx ];
							break;

						case 'answers':
							break;

						default:
							if ( isset( $question_data[ $field_map_idx ] ) ) {
								$return = $question_data[ $field_map_idx ];
							}
							break;
					}
				}
			}
			return $return;
		}

		/**
		 * Override the REST response links.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response WP_REST_Response instance.
		 * @param WP_Post          $post     WP_Post instance.
		 * @param WP_REST_Request  $request  WP_REST_Request instance.
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {

			if ( $this->post_type === $post->post_type ) {
				$base          = sprintf( '/%s/%s', $this->namespace, $this->rest_base );
				$request_route = $request->get_route();

				if ( ( ! empty( $request_route ) ) && ( strpos( $request_route, $base ) !== false ) ) {

					$links = array();

					$current_links = $response->get_links();

					if ( ! isset( $current_links[ $this->get_rest_base( 'question-types' ) ] ) ) {
						$quiz_pro_id = get_post_meta( $post->ID, 'question_pro_id', true );
						$quiz_pro_id = absint( $quiz_pro_id );
						if ( ! empty( $quiz_pro_id ) ) {
							$question_mapper      = new \WpProQuiz_Model_QuestionMapper();
							$question_model       = $question_mapper->fetch( $quiz_pro_id );
							$question_answer_type = $question_model->getAnswerType();
							if ( ! empty( $question_answer_type ) ) {
								$links[ $this->get_rest_base( 'question-types' ) ] = array(
									'href'       => rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'question-types' ) . '/' . $question_answer_type ),
									'embeddable' => true,
								);
							}
						}
					}

					if ( ! empty( $links ) ) {
						$response->add_links( $links );
					}
				}
			}

			return $response;
		}

		// End of functions.
	}
}
