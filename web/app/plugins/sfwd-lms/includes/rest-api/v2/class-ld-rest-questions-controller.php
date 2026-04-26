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

use LearnDash\Core\Enums\Models\Question_Type;
use LearnDash\Core\Utilities\Cast;

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
				'_answerData'                     => 'answers',
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
							// translators: %1$s: Question label (lowercase), %2$s: Question types.
							__( 'The type of %1$s. Options include: %2$s.', 'learndash' ),
							learndash_get_custom_label_lower( 'question' ),
							implode(
								', ',
								array_map(
									function ( $type ) {
										return "'{$type->getValue()}' ({$type->get_label()})";
									},
									Question_Type::values()
								)
							)
						),
						'type'        => 'string',
						'enum'        => array_values(
							array_map(
								function ( $type ) {
									return $type->getValue();
								},
								Question_Type::values()
							)
						),
						'required'    => false,
						'default'     => Question_Type::SINGLE_CHOICE()->getValue(),
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
						'default'     => 0,
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
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_show_in_message' => array(
					'schema'          => array(
						'field_key'   => 'points_show_in_message',
						'description' => esc_html__( 'Show reached points in the correct/incorrect message? Requires "Different points for each answer" to be enabled.', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'points_diff_modus'      => array(
					'schema'          => array(
						'field_key'   => 'points_diff_modus',
						'description' => esc_html(
							sprintf(
								// translators: placeholder: %1$s - question label, %2$s - question type value.
								__( 'Whether different points can be awarded for each answer. Requires "Different points for each answer" to be enabled and for the %1$s Type to be "%2$s".', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'question' ),
								Question_Type::SINGLE_CHOICE()->getValue()
							)
						),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),

				'disable_correct'        => array(
					'schema'          => array(
						'field_key'   => 'disable_correct',
						'description' => esc_html__( 'Disable the distinction between correct and incorrect answers. Requires "points_diff_modus" to be enabled.', 'learndash' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
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
						'type'        => 'string',
						'required'    => false,
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'incorrect_message'      => array(
					'schema'          => array(
						'field_key'   => 'incorrect_message',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html__(
								'Message shown when %s is incorrect. Cannot be used when the "Same correct and incorrect message text" setting is enabled.',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'string',
						'required'    => false,
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'correct_same'           => array(
					'schema'          => array(
						'field_key'   => 'correct_same',
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html__(
								'Whether to use the same correct and incorrect message text for this %s.',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
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
						'default'     => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				),
				'hints_message'          => array(
					'schema'          => array(
						'field_key'   => 'hints_message',
						'description' => esc_html__( 'Hint message.', 'learndash' ),
						'type'        => 'string',
						'required'    => false,
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
						'type'        => 'array',
						'required'    => true,
						'context'     => array( 'view', 'edit' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'_answer'             => [
									'type'        => 'string',
									'description' => __( 'The answer text.', 'learndash' ),
								],
								'_html'               => [
									'type'        => 'boolean',
									'description' => __( 'Whether the HTML is allowed in the answer or not', 'learndash' ),
								],
								'_points'             => [
									'type'        => 'integer',
									'description' => sprintf(
										// translators: placeholder: %s - question label.
										__( 'The number of points that can be obtained from the answer. Only used if "points_per_answer" is enabled for the %s.', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'question' ),
									),
								],
								'_correct'            => [
									'type'        => 'boolean',
									'description' => __( 'Whether the answer is correct.', 'learndash' ),
								],
								'_sortString'         => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: placeholder: %1$s - matrix sort answer question type, %2$s - question label.
										__( 'Sort String. Only used for the "%1$s" %2$s type. This is the draggable element that you match with the "_answer" field.', 'learndash' ),
										Question_Type::MATRIX_SORTING_CHOICE()->getValue(),
										LearnDash_Custom_Label::get_label( 'question' ),
									),
								],
								'_sortStringHtml'     => [
									'type'        => 'boolean',
									'description' => sprintf(
										// translators: placeholder: %1$s - matrix sort answer question type, %2$s - question label.
										__( 'Whether HTML is enabled for _sortString. Only used for the "%1$s" %2$s type.', 'learndash' ),
										Question_Type::MATRIX_SORTING_CHOICE()->getValue(),
										LearnDash_Custom_Label::get_label( 'question' ),
									),
								],
								'_graded'             => [
									'type'        => 'boolean',
									'description' => __( 'Whether the answer can be graded or not.', 'learndash' ),
								],
								'_gradingProgression' => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: placeholder: %1$s - question label, %2$s - essay question type.
										__( 'Determines how should the answer to this %1$s be marked and graded upon submission. Only applies to the "%2$s" %1$s type', 'learndash' ),
										learndash_get_custom_label_lower( 'question' ),
										Question_Type::ESSAY()->getValue(),
									),
									'enum'        => [
										'not-graded-none',
										'not-graded-full',
										'graded-full',
									],
								],
								'_gradedType'         => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: placeholder: %1$s - essay question type, %2$s - question label.
										__( 'Determines how a user can submit answer. Only applies to the "%1$s" %2$s type', 'learndash' ),
										Question_Type::ESSAY()->getValue(),
										LearnDash_Custom_Label::get_label( 'question' ),
									),
									'enum'        => [
										'text',
										'upload',
									],
								],
							],
						],
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
		 * @since 5.0.0 Corrected the type of $request to WP_REST_Request<array<string,mixed>>.
		 * @since 5.0.0 Corrected the return type to true|WP_Error.
		 *
		 * @param WP_REST_Request<array<string,mixed>> $request  WP_REST_Request instance.
		 *
		 * @return true|WP_Error True is used can get item, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			$this->rest_init_request_posts( $request );

			if ( learndash_is_admin_user() ) {
				return $return;
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
		 * Checks if a given request has access to read a post.
		 * We override this to implement our own permissions check.
		 *
		 * @since 4.10.3
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
		 */
		public function get_item_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			}

			return new WP_Error(
				'ld_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
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
				$question_pro_id = Cast::to_int( get_post_meta( $question_id, 'question_pro_id', true ) );
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
		 * Get question data.
		 *
		 * @since 3.3.0
		 *
		 * @deprecated 5.0.0 Use LD_REST_Questions_Controller_V2::get_item() instead.
		 *
		 * @param int $question_id The question ID.
		 *
		 * @return array<string,mixed>
		 */
		public function get_question_data( $question_id = 0 ) {
			_deprecated_function( __METHOD__, '5.0.0', 'LD_REST_Questions_Controller_V2::get_item()' );

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
		 * @since 5.0.0 Now returns answer data.
		 *
		 * @param array<string, mixed> $postdata   Post data array.
		 * @param string               $field_name Field Name for $postdata value.
		 * @param WP_REST_Request      $request    Request object.
		 * @param string               $post_type  Post Type for request.
		 *
		 * @return mixed
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			static $question_pro_data = array();

			$return = null;

			$field_map_idx = array_search( $field_name, $this->fields_map, true );
			if ( $field_map_idx === false ) {
				return $return;
			}

			$question_post_id = 0;
			if ( ! empty( $postdata['id'] ) ) {
				$question_post_id = absint( Cast::to_int( $postdata['id'] ) );
			}

			if ( $question_post_id <= 0 ) {
				return $return;
			}

			$quiz_pro_id = get_post_meta( $question_post_id, 'question_pro_id', true );
			$quiz_pro_id = absint( $quiz_pro_id );

			// Use static cache if available, otherwise store to cache.
			if ( isset( $question_pro_data[ $question_post_id ] ) ) {
				$question_data = $question_pro_data[ $question_post_id ];
			} else {
				$question_mapper = new WpProQuiz_Model_QuestionMapper();
				$question_model  = $question_mapper->fetch( $quiz_pro_id );
				$question_data   = $question_model->get_object_as_array();

				$question_pro_data[ $question_post_id ] = $question_data;
			}

			switch ( $field_name ) {
				case 'quiz':
					$return = absint( learndash_get_setting( $question_post_id, 'quiz' ) );
					break;

				case 'correct_message':
				case 'incorrect_message':
				case 'hints_message':
					$return = array(
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						'rendered' => apply_filters( 'the_content', $question_data[ $field_map_idx ] ),
					);

					// If the context is 'edit' we provide the raw content.
					if ( 'edit' === $request['context'] ) {
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
					$return = [];

					foreach ( $question_data['_answerData'] as $answer ) {
						$return[] = $answer->get_object_as_array();
					}

					break;

				default:
					if ( isset( $question_data[ $field_map_idx ] ) ) {
						$return = $question_data[ $field_map_idx ];
					}

					break;
			}

			return $return;
		}

		/**
		 * Update REST Settings Field value.
		 *
		 * @since 5.0.0
		 *
		 * @param mixed                                $post_value REST Field value.
		 * @param WP_Post                              $post       WP Post object.
		 * @param string                               $field_name REST Field name.
		 * @param WP_REST_Request<array<string,mixed>> $request    WP_REST_Request object.
		 * @param string                               $post_type  Post type.
		 *
		 * @return void
		 */
		public function update_rest_settings_field_value( $post_value, WP_Post $post, $field_name, $request, $post_type ) {
			$question_pro_id = $this->maybe_create_pro_quiz_question( $post );

			$internal_key = array_search( $field_name, $this->fields_map, true );

			if ( false === $internal_key ) {
				return;
			}

			switch ( $field_name ) {
				case 'answers':
					if ( is_string( $post_value ) ) {
						$post_value = json_decode( $post_value, true );
					}
					break;
				case 'points':
					update_post_meta( $post->ID, 'question_points', $post_value );
					break;
				case 'question':
					wp_update_post(
						[
							'ID'           => $post->ID,
							'post_content' => wp_slash( Cast::to_string( $post_value ) ),
						]
					);
					break;
				case 'quiz':
					$quiz_id = Cast::to_int( $post_value );

					$questions = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
					$questions = is_array( $questions ) ? $questions : [];

					$questions[ $post->ID ] = $question_pro_id;

					update_post_meta( $quiz_id, 'ld_quiz_questions', $questions );

					learndash_update_setting( $post->ID, 'quiz', $quiz_id );
					update_post_meta( $post->ID, 'quiz_id', $quiz_id );

					break;
				default:
					break;
			}

			$question_mapper = new WpProQuiz_Model_QuestionMapper();
			$question_model  = $question_mapper->fetch( $question_pro_id );

			$question_model->set_array_to_object(
				[
					$internal_key => $post_value,
				]
			);

			$question_mapper->save( $question_model );

			learndash_proquiz_sync_question_fields( $post->ID, $question_pro_id );
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
						$quiz_pro_id = absint( Cast::to_int( get_post_meta( $post->ID, 'question_pro_id', true ) ) );

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

		/**
		 * Creates a new Pro Quiz Question if one is not assigned to the Question Post.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Post $question The question post.
		 *
		 * @return int The question pro ID.
		 */
		private function maybe_create_pro_quiz_question( WP_Post $question ): int {
			$question_pro_id = (int) get_post_meta( $question->ID, 'question_pro_id', true );

			if ( $question_pro_id > 0 ) {
				return $question_pro_id;
			}

			// Create a new Pro Quiz Question.
			$question_pro_id = learndash_update_pro_question(
				0,
				[
					'action'       => 'new_step',
					'post_type'    => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ),
					'post_status'  => $question->post_status,
					'post_title'   => $question->post_title,
					'post_content' => $question->post_content,
				]
			);

			update_post_meta( $question->ID, 'question_pro_id', $question_pro_id );

			return $question_pro_id;
		}
	}
}
