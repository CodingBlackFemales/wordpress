<?php
/**
 * LearnDash REST API V1 Courses Post Controller.
 *
 * @since 2.5.8
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Courses Post Controller.
	 *
	 * @since 2.5.8
	 */
	class LD_REST_Courses_Controller_V1 extends LD_REST_Posts_Controller_V1 /* //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 2.5.8
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			$this->post_type  = 'sfwd-courses';
			$this->taxonomies = array();

			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'sfwd-courses' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 2.5.8
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			$this->register_fields();

			parent::register_routes_wpv2();

			$collection_params = $this->get_collection_params();
			$schema            = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

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
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'create_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					),
					'schema' => array( $this, 'get_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					'args'   => array(
						'id' => array(
							'description' => esc_html__( 'Unique identifier for the object.', 'learndash' ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
						'args'                => $get_item_args,
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_item' ),
						'permission_callback' => array( $this, 'update_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_item' ),
						'permission_callback' => array( $this, 'delete_item_permissions_check' ),
						'args'                => array(
							'force' => array(
								'type'        => 'boolean',
								'default'     => false,
								'description' => esc_html__( 'Whether to bypass trash and force deletion.', 'learndash' ),
							),
						),
					),
					'schema' => array( $this, 'get_schema' ),
				)
			);

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-steps-controller.php';
			$this->sub_controllers['class-ld-rest-courses-steps-controller'] = new LD_REST_Courses_Steps_Controller_V1();
			$this->sub_controllers['class-ld-rest-courses-steps-controller']->register_routes();

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-users-controller.php';
			$this->sub_controllers['class-ld-rest-courses-users-controller'] = new LD_REST_Courses_Users_Controller_V1();
			$this->sub_controllers['class-ld-rest-courses-users-controller']->register_routes();

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-groups-controller.php';
			$this->sub_controllers['class-ld-rest-courses-groups-controller'] = new LD_REST_Courses_Groups_Controller_V1();
			$this->sub_controllers['class-ld-rest-courses-groups-controller']->register_routes();
		}

		/**
		 * Gets Course schema.
		 *
		 * @since 2.5.8
		 *
		 * @return array
		 */
		public function get_schema() {
			$schema = $this->get_public_item_schema();

			$schema['title'] = 'course';

			return $schema;
		}

		/**
		 * Override the REST response links.
		 *
		 * @since 2.5.8
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
					if ( ! isset( $response->links['steps'] ) ) {
						$links['steps'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/steps',
							'embeddable' => true,
						);
					}
					if ( ! isset( $response->links['users'] ) ) {
						$links['users'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/users',
							'embeddable' => true,
						);
					}
					if ( ! isset( $response->links['groups'] ) ) {
						$links['groups'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/groups',
							'embeddable' => true,
						);
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
