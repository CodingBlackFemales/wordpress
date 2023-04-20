<?php
/**
 * LearnDash REST API V2 Price Types Controller.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Price_Types_Controller_V2' ) ) && ( class_exists( 'WP_REST_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Price Types Controller.
	 *
	 * @since 3.3.0
	 * @uses WP_REST_Controller
	 */
	class LD_REST_Price_Types_Controller_V2 extends WP_REST_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
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
			$this->rest_base = $this->get_rest_base( 'price-types' );
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
							'description' => __( 'An alphanumeric identifier for the price type.', 'learndash' ),
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
				'open'      => array(
					'slug'        => 'open',
					'name'        => esc_html__( 'Open', 'learndash' ),
					'description' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'free'      => array(
					'slug'        => 'free',
					'name'        => esc_html__( 'Free', 'learndash' ),
					'description' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'The %s is protected. Registration and enrollment are required in order to access the content.', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'paynow'    => array(
					'slug'        => 'paynow',
					'name'        => esc_html__( 'Buy now', 'learndash' ),
					'description' => sprintf(
						// translators: placeholder: course, course.
						esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (one-time fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'subscribe' => array(
					'slug'        => 'subscribe',
					'name'        => esc_html__( 'Recurring', 'learndash' ),
					'description' => sprintf(
						// translators: placeholder: course, course.
						esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (recurring fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'closed'    => array(
					'slug'        => 'closed',
					'label'       => esc_html__( 'Closed', 'learndash' ),
					'description' => sprintf(
						// translators: placeholder: course, group.
						esc_html_x( 'The %1$s can only be accessed through admin enrollment (manual), %2$s enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'group' )
					),
				),
			);
		}

		/**
		 * Checks whether a given request has permission to read price type.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			return true;
		}

		/**
		 * Retrieves all price types.
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
				$price_type    = $this->prepare_item_for_response( $item, $request );
				$data[ $slug ] = $this->prepare_response_for_collection( $price_type );
			}

			return rest_ensure_response( $data );
		}

		/**
		 * Checks if a given request has access to read a price type.
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
		 * Retrieves a specific price type.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			$price_type_slug = $request['slug'];
			if ( ( empty( $price_type_slug ) ) || ( ! isset( $this->types[ $price_type_slug ] ) ) ) {
				return new WP_Error(
					'rest_price_type_invalid',
					__( 'Invalid Price Type.', 'learndash' ),
					array( 'status' => 404 )
				);
			}

			$data = $this->prepare_item_for_response( $this->types[ $price_type_slug ], $request );

			return rest_ensure_response( $data );
		}

		/**
		 * Prepares a price type object for serialization.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $price_type Price Type item array.
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response Post status data.
		 */
		public function prepare_item_for_response( $price_type, $request ) {

			$fields     = $this->get_fields_for_response( $request );
			$data       = array();
			$price_type = (array) $price_type;

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( isset( $price_type[ $field ] ) ) {
						$data[ $field ] = $price_type[ $field ];
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
		 * Retrieves the price type schema, conforming to JSON Schema.
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
				'title'      => $this->get_rest_base( 'price-types' ),
				'type'       => 'object',
				'properties' => array(
					'name'        => array(
						'description' => __( 'The title for the price type.', 'learndash' ),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'description' => array(
						'description' => __( 'The description for the price type.', 'learndash' ),
						'type'        => 'string',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'slug'        => array(
						'description' => __( 'An alphanumeric identifier for the price type.', 'learndash' ),
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
