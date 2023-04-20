<?php
/**
 * LearnDash REST API V2 Question Types Controller.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Question_Types_Controller_V2' ) ) && ( class_exists( 'WP_REST_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Question Types Controller.
	 *
	 * @since 3.3.0
	 * @uses WP_REST_Controller
	 */
	class LD_REST_Question_Types_Controller_V2 extends WP_REST_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * Version
		 *
		 * @var string
		 */
		protected $version = 'v2';

		/**
		 * Set of types used by class.
		 *
		 * @since 3.3.0
		 *
		 * @var array
		 */
		private $types = array();

		/**
		 * Constructor.
		 *
		 * @since 3.3.0
		 */
		public function __construct() {
			$this->namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;
			$this->rest_base = $this->get_rest_base( 'question-types' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {

			$this->init_types_set();

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<slug>[\w-]+)',
				array(
					'args'   => array(
						'slug' => array(
							'description' => sprintf(
								// translators: placeholder: question.
								esc_html_x( 'An alphanumeric identifier for the %s type', 'placeholder: question', 'learndash' ),
								learndash_get_custom_label_lower( 'question ' )
							),
							'type'        => 'string',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
						'args'                => array(
							'context' => $this->get_context_param( array( 'default' => 'view' ) ),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Initialize the types dataset for use within the class.
		 *
		 * @since 3.3.0
		 */
		protected function init_types_set() {
			$this->types = array(
				'single'             => array(
					'slug'        => 'single',
					'name'        => esc_html__( 'Single choice', 'learndash' ),
					'description' => '',
				),
				'multiple'           => array(
					'slug'        => 'multiple',
					'name'        => esc_html__( 'Multiple choice', 'learndash' ),
					'description' => '',
				),
				'free_answer'        => array(
					'slug'        => 'free_answer',
					'name'        => esc_html__( '"Free" choice', 'learndash' ),
					'description' => '',
				),

				'sort_answer'        => array(
					'slug'        => 'sort_answer',
					'name'        => esc_html__( '"Sorting" choice', 'learndash' ),
					'description' => '',
				),
				'matrix_sort_answer' => array(
					'slug'        => 'matrix_sort_answer',
					'name'        => esc_html__( '"Matrix Sorting" choice', 'learndash' ),
					'description' => '',
				),
				'cloze_answer'       => array(
					'slug'        => 'cloze_answer',
					'name'        => esc_html__( 'Closed', 'learndash' ),
					'description' => '',
				),
				'assessment_answer'  => array(
					'slug'        => 'assessment_answer',
					'name'        => esc_html__( 'Assessment', 'learndash' ),
					'description' => '',
				),
				'essay'              => array(
					'slug'        => 'essay',
					'name'        => esc_html__( 'Essay / Open Answer', 'learndash' ),
					'description' => '',
				),
			);
		}

		/**
		 * Checks whether a given request has permission to read question type.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			return true;
		}

		/**
		 * Retrieves all question types.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_items( $request ) {
			$data = array();

			foreach ( $this->types as $slug => $item ) {
				$question_type = $this->prepare_item_for_response( $item, $request );
				$data[ $slug ] = $this->prepare_response_for_collection( $question_type );
			}

			return rest_ensure_response( $data );
		}

		/**
		 * Checks if a given request has access to read a question type.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_item_permissions_check( $request ) {
			return true;
		}

		/**
		 * Retrieves a specific question type.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			$type_slug = $request['slug'];
			if ( ( empty( $type_slug ) ) || ( ! isset( $this->types[ $type_slug ] ) ) ) {
				return new WP_Error(
					'rest_question_type_invalid',
					sprintf(
						// translators: placeholder: Question.
						_x( 'Invalid %s Type.', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					array( 'status' => 404 )
				);
			}

			$data = $this->prepare_item_for_response( $this->types[ $type_slug ], $request );

			return rest_ensure_response( $data );
		}

		/**
		 * Prepares a question type object for serialization.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $question_type Question type item array.
		 * @param WP_REST_Request $request       Full details about the request.
		 *
		 * @return WP_REST_Response Post status data.
		 */
		public function prepare_item_for_response( $question_type, $request ) {

			$fields        = $this->get_fields_for_response( $request );
			$data          = array();
			$question_type = (array) $question_type;

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( isset( $question_type[ $field ] ) ) {
						$data[ $field ] = $question_type[ $field ];
					}
				}
			}

			$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
			$data    = $this->add_additional_fields_to_object( $data, $request );
			$data    = $this->filter_response_by_context( $data, $context );

			$response = rest_ensure_response( $data );
			return $response;
		}

		/**
		 * Retrieves the question type schema, conforming to JSON Schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array Item schema data.
		 */
		public function get_item_schema() {
			if ( $this->schema ) {
				return $this->add_additional_fields_schema( $this->schema );
			}

			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => $this->get_rest_base( 'question-types' ),
				'type'       => 'object',
				'properties' => array(
					'name'        => array(
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'The title for the %s type',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::label_to_lower( 'question' )
						),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'description' => array(
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'The description for the %s type.',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::label_to_lower( 'question' )
						),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),

					'slug'        => array(
						'description' => sprintf(
							// translators: placeholder: question.
							esc_html_x(
								'An alphanumeric identifier for the %s type',
								'placeholder: question',
								'learndash'
							),
							LearnDash_Custom_Label::label_to_lower( 'question' )
						),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
				),
			);

			$this->schema = $schema;

			return $this->add_additional_fields_schema( $this->schema );
		}

		/**
		 * Retrieves the query params for collections.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params() {
			return array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
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
		// End of functions.
	}
}
