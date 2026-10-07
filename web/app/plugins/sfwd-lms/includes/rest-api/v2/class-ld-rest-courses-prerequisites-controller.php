<?php
/**
 * LearnDash REST API V2 Courses Prerequisites Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between the LearnDash Courses (sfwd-courses) and Course
 * Prerequisites.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Prerequisites_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Courses Prerequisites Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Courses_Prerequisites_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

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

			$this->post_type = $post_type;

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'courses' );
			$this->rest_sub_base = $this->get_rest_base( 'courses-prerequisites' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {

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
						'callback'            => array( $this, 'get_courses_prerequisites' ),
						'permission_callback' => array( $this, 'get_courses_prerequisites_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Filter Course Prerequisites Query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			$route_url    = $request->get_route();
			$ld_route_url = '/' . $this->namespace . '/' . $this->rest_base . '/' . absint( $request['id'] ) . '/' . $this->rest_sub_base;
			if ( ( ! empty( $route_url ) ) && ( $ld_route_url === $route_url ) ) {
				$course_id = (int) $request['id'];
				if ( ! empty( $course_id ) ) {
					$this->course_post = get_post( $course_id );
					if ( ( $this->course_post ) && ( is_a( $this->course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $this->course_post->post_type ) ) {
						$course_pre = learndash_get_course_prerequisites( $this->course_post->ID, false );
						if ( ! empty( $course_pre ) ) {
							$query_args['post__in'] = $query_args['post__in'] ? array_intersect( array_keys( $course_pre ), $query_args['post__in'] ) : array_keys( $course_pre );
						} else {
							$query_args['post__in'] = array( 0 );
						}
					}
				}
			}

			return $query_args;
		}

		/**
		 * Permissions check for getting course groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_courses_prerequisites_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Retrieves a course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_courses_prerequisites( $request ) {
			return $this->get_items( $request );
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

			$schema['title']  = 'course-prerequisites';
			$schema['parent'] = 'course';

			return $schema;
		}

		// End of functions.
	}
}
