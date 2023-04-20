<?php
/**
 * LearnDash REST API V1 Groups Post Controller.
 *
 * @since 2.5.8
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Groups_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Groups Post Controller.
	 *
	 * @since 2.5.8
	 */
	class LD_REST_Groups_Controller_V1 extends LD_REST_Posts_Controller_V1 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 2.5.8
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			$this->post_type  = 'groups';
			$this->taxonomies = array();

			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', $this->post_type );
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

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-groups-courses-controller.php';
			$this->sub_controllers['class-ld-rest-groups-courses-controller'] = new LD_REST_Groups_Courses_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-courses-controller']->register_routes();

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-groups-leaders-controller.php';
			$this->sub_controllers['class-ld-rest-groups-leaders-controller'] = new LD_REST_Groups_Leaders_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-leaders-controller']->register_routes();

			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-groups-users-controller.php';
			$this->sub_controllers['class-ld-rest-groups-users-controller'] = new LD_REST_Groups_Users_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-users-controller']->register_routes();

		}

		/**
		 * Check Groups Read Permissions.
		 *
		 * @since 2.5.8
		 *
		 * @param object $request WP_REST_Request instance.
		 */
		public function get_items_permissions_check( $request ) {
			if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Gets groups schema.
		 *
		 * @since 2.5.8
		 *
		 * @return array
		 */
		public function get_schema() {
			$schema = $this->get_public_item_schema();

			$schema['title'] = 'group';

			return $schema;
		}

		/**
		 * Filter query args.
		 *
		 * @since 2.5.8
		 *
		 * @param array           $args     Key value array of query var to query value.
		 * @param WP_REST_Request $request  The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $args;
			}

			if ( learndash_is_group_leader_user() ) {
				$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( ! empty( $group_ids ) ) {
					$args['post__in'] = $group_ids;
				} else {
					$args['post__in'] = array( 0 );
				}
			}

			return $args;
		}

		/**
		 * Prepare REST response.
		 *
		 * @since 2.5.8
		 *
		 * @param object $response WP_REST_Response instance.
		 * @param object $post     WP_Post instance.
		 * @param object $request  WP_REST_Request instance.
		 */
		public function rest_prepare_response( $response, $post, $request ) {

			$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

			// Entity meta.
			$links = array(
				'users'   => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/users' ),
				),
				'leaders' => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/leaders' ),
				),
				'courses' => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/courses' ),
				),
			);
			$response->add_links( $links );

			return $response;
		}

		// End of functions.
	}
}
