<?php
/**
 * LearnDash V2 REST API User Controller.
 *
 * This Controller is used as the parent Controller for all LearnDash
 * User related REST requests. For example Group Users, Course Users, etc.
 *
 * This Controller class extends the WordPress WP_REST_Users_Controller class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Users_Controller_V2' ) ) && ( class_exists( 'WP_REST_Users_Controller' ) ) ) {

	/**
	 * Class LearnDash V2 REST API User Controller.
	 *
	 * @since 3.3.0
	 * @uses WP_REST_Users_Controller
	 */
	class LD_REST_Users_Controller_V2 extends WP_REST_Users_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * REST API version.
		 *
		 * @var $version string.
		 */
		protected $version = 'v2';

		/**
		 * REST API Sub-Controllers
		 *
		 * @var array $sub_controllers.
		 */
		protected $sub_controllers = array();

		/**
		 * REST API Sub-Base path.
		 *
		 * This is used on nested REST paths like
		 * /ldlms/v2/users/X/groups where '/groups'
		 * is the $sub_base.
		 *
		 * @var array $sub_controllers.
		 */
		protected $rest_sub_base = '';

		/**
		 * Protected constructor for class
		 *
		 * @since 3.3.0
		 */
		public function __construct() {
			parent::__construct();

			/**
			 * Set the namespace and rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;

			add_filter( 'rest_user_collection_params', array( $this, 'rest_collection_params_filter' ), 20 );
			add_filter( 'rest_user_query', array( $this, 'rest_query_filter' ), 20, 2 );
			add_filter( 'rest_prepare_user', array( $this, 'rest_prepare_response_filter' ), 20, 3 );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {

			$collection_params = $this->get_collection_params();
			$schema            = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
		}

		/**
		 * For LearnDash post type we override the default order/orderby
		 * to ASC/title instead of the WP default DESC/date.
		 *
		 * @since 3.3.0
		 *
		 * @param array $query_params Quest params array.
		 */
		public function rest_collection_params_filter( array $query_params ) {
			return $query_params;
		}

		/**
		 * Filter Users query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			return $query_args;
		}

		/**
		 * Override the User REST response links.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_User          $user     User object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_User $user, WP_REST_Request $request ) {
			// We override the namespace because we want all the links we are adding to use our namespace path.
			$namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;
			$base      = sprintf( '/%s/%s', $namespace, $this->rest_base );

			$links = array();

			$current_links = $response->get_links();

			if ( ! isset( $current_links['courses'] ) ) {
				$links['courses'] = array(
					'href'       => rest_url( trailingslashit( $base ) . $user->ID ) . '/' . $this->get_rest_base( 'users-courses' ),
					'embeddable' => true,
				);
			}

			if ( ! isset( $current_links['groups'] ) ) {
				$links['groups'] = array(
					'href'       => rest_url( trailingslashit( $base ) . $user->ID ) . '/' . $this->get_rest_base( 'users-groups' ),
					'embeddable' => true,
				);
			}
			if ( ! isset( $current_links['course-progress'] ) ) {
				$links['course-progress'] = array(
					'href'       => rest_url( trailingslashit( $base ) . $user->ID ) . '/' . $this->get_rest_base( 'users-course-progress' ),
					'embeddable' => true,
				);
			}
			if ( ! isset( $current_links['quiz-progress'] ) ) {
				$links['quiz_progress'] = array(
					'href'       => rest_url( trailingslashit( $base ) . $user->ID ) . '/' . $this->get_rest_base( 'users-quiz-progress' ),
					'embeddable' => true,
				);
			}

			if ( ! empty( $links ) ) {
				$response->add_links( $links );
			}

			return $response;
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
		 * Check if REST Request is for this version/route.
		 *
		 * @since 3.4.2
		 *
		 * @param WP_REST_Request $request WP_REST_Request Request instance.
		 *
		 * @return bool true if match.
		 */
		protected function is_rest_request( WP_REST_Request $request ) {
			$request_route_base = '/' . $this->namespace . '/' . $this->rest_base;
			if ( strncasecmp( $request->get_route(), $request_route_base, strlen( $request_route_base ) ) === 0 ) {
				return true;
			}
			return false;
		}

		// End of functions.
	}
}
