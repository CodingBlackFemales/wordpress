<?php
/**
 * LearnDash REST API V2 Quiz Form Entries Controller.
 *
 * @since 3.5.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Quiz_Form_Entries_Controller_V2' ) ) && ( class_exists( 'WP_REST_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Quiz Form Entries Controller.
	 *
	 * @since 3.5.0
	 * @uses WP_REST_Controller
	 */
	class LD_REST_Quiz_Form_Entries_Controller_V2 extends WP_REST_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * Version
		 *
		 * @var string
		 */
		protected $version = 'v2';

		/**
		 * Set of types used by class.
		 *
		 * @since 3.5.0
		 *
		 * @var array
		 */
		private $types = array();

		/**
		 * Constructor.
		 *
		 * @since 3.5.0
		 */
		public function __construct() {
			$this->namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;
			$this->rest_base = $this->get_rest_base( 'quizzes-form-entries' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {

			$forms_rest_base = $this->get_rest_base( 'quizzes' ) . '/(?P<quiz>[\d]+)/' . $this->get_rest_base( 'quizzes-form-entries' );
			register_rest_route(
				$this->namespace,
				'/' . $forms_rest_base,
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
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
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

			$quiz_id = $request->get_param( 'quiz' );
			$quiz_id = absint( $quiz_id );
			if ( empty( $quiz_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
					// translators: placeholder: Quiz.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Quiz',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'quiz' )
					) . ' ' . __CLASS__,
					array( 'status' => 404 )
				);
			}

			$pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
			$pro_quiz_id = absint( $pro_quiz_id );
			if ( empty( $pro_quiz_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
					// translators: placeholder: Quiz.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Quiz',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'quiz' )
					) . ' ' . __CLASS__,
					array( 'status' => 404 )
				);
			}

			$quiz_mapper = new WpProQuiz_Model_QuizMapper();
			$quiz        = $quiz_mapper->fetch( $pro_quiz_id );

			if ( $quiz->isFormActivated() ) {
				$form_mapper   = new WpProQuiz_Model_FormMapper();
				$form_elements = $form_mapper->fetch( $pro_quiz_id );
				if ( ! empty( $form_elements ) ) {
					$query_args = array(
						'orderby'  => 'date',
						'order'    => 'DESC',
						'per_page' => 10,
						'page'     => 1,
					);

					$user_id = $request->get_param( 'user' );
					$user_id = absint( $user_id );
					if ( ! empty( $user_id ) ) {
						$query_args['user_id'] = $user_id;
					}

					$per_page = $request->get_param( 'per_page' );
					$per_page = absint( $per_page );
					if ( ! empty( $per_page ) ) {
						$query_args['per_page'] = $per_page;
					}

					$page = $request->get_param( 'page' );
					$page = absint( $page );
					if ( ! empty( $page ) ) {
						$query_args['page'] = $page;
					}

					$orderby = $request->get_param( 'orderby' );
					$orderby = esc_attr( trim( $orderby ) );
					if ( ! empty( $orderby ) ) {
						$query_args['orderby'] = $orderby;
					}

					$order = $request->get_param( 'order' );
					$order = esc_attr( strtoupper( trim( $order ) ) );
					if ( ( ! empty( $order ) ) && ( in_array( $order, array( 'ASC', 'DESC' ), true ) ) ) {
						$query_args['order'] = $order;
					}

					$statistic_ref_mapper = new WpProQuiz_Model_StatisticRefMapper();
					$statistics           = $statistic_ref_mapper->fetchWithForms( $pro_quiz_id, $query_args );
					foreach ( $statistics as $statistic_model ) {
						$form_data_array = $statistic_model->getFormData();
						foreach ( $form_data_array as $form_data_json ) {
							if ( ! empty( $form_data_json ) ) {
								$form_data = (array) json_decode( $form_data_json );

								$user_form_data = array();
								foreach ( $form_elements as $form_element_model ) {
									$field_id   = $form_element_model->getFormId();
									$field_name = $form_element_model->getFieldname();

									$user_value = '';
									if ( isset( $form_data[ $field_id ] ) ) {
										$user_value = $form_data[ $field_id ];
									}

									$user_form_data[] = array(
										'field_id'   => $field_id,
										'field_name' => $field_name,
										'user_value' => $user_value,
									);
								}

								if ( ! empty( $user_form_data ) ) {
									$user_form_header = array(
										'id'        => $statistic_model->getStatisticRefId(),
										'user'      => $statistic_model->getUserId(),
										'quiz'      => $quiz_id,
										'date'      => $this->prepare_date_response( gmdate( 'Y-m-d H:i:s', $statistic_model->getCreateTime() ) ),
										'form_data' => $user_form_data,
									);

									$data[] = $user_form_header;
								}
							}
						}
					}
					$total_items = $statistic_ref_mapper->fetchWithFormsTotal( $pro_quiz_id, $query_args );
				}
			}

			$response = rest_ensure_response( $data );

			$max_pages = ceil( (int) $total_items / (int) $query_args['per_page'] );

			if ( $query_args['page'] > $max_pages && $total_items > 0 ) {
				return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.', 'learndash' ), array( 'status' => 400 ) );
			}

			$response->header( 'X-WP-Total', (int) $total_items );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			return $response;
		}

		/**
		 * Retrieves the question type schema, conforming to JSON Schema.
		 *
		 * @since 3.5.0
		 *
		 * @return array Item schema data.
		 */
		public function get_item_schema() {
			if ( $this->schema ) {
				return $this->add_additional_fields_schema( $this->schema );
			}

			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => $this->get_rest_base( 'quizzes-form-entries' ),
				'type'       => 'object',
				'properties' => array(
					'id'        => array(
						'description' => sprintf(
							// translators: placeholder: quiz.
							esc_html_x(
								'The %s form entry ID',
								'placeholder: quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'user'      => array(
						'description' => esc_html__( 'User ID.', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'quiz'      => array(
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
					'date'      => array(
						'description' => esc_html__( 'Date of entry', 'learndash' ),
						'type'        => array(
							'string',
							'null',
						),
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'form_data' => array(
						'description' => esc_html__( 'Form entry details', 'learndash' ),
						'type'        => array(
							'array',
							'null',
						),
						'context'     => array( 'view' ),
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
		 * @since 3.5.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params() {

			$query_params_default = parent::get_collection_params();

			$query_params_default['context']['default'] = 'view';

			$query_params            = array();
			$query_params['context'] = $query_params_default['context'];
			$query_params['user']    = array(
				'description' => __( 'User ID', 'learndash' ),
				'type'        => 'integer',
				'required'    => false,
				'default'     => 0,
			);
			$query_params['quiz']    = array(
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
				'required'    => false,
				'default'     => 0,
			);
			$query_params['order']   = array(
				'description' => __( 'Order', 'learndash' ),
				'type'        => 'string',
				'default'     => 'DESC',
				'enum'        => array(
					'ASC',
					'DESC',
				),
			);
			$query_params['orderby'] = array(
				'description' => __( 'Order by', 'learndash' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array(
					'date',
				),
			);

			return $query_params;
		}

		/**
		 * Get the REST URL setting.
		 *
		 * @since 3.5.0
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
		 * Checks the post_date_gmt or modified_gmt and prepare any post or
		 * modified date for single post output.
		 *
		 * @since 3.5.0
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
