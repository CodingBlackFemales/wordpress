<?php
/**
 * LearnDash REST API V2 Courses Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type Courses (sfwd-courses).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Courses Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Courses_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

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
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'courses' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			// Register all the default routes first.
			parent::register_routes();

			// Added support for nested wp-json/courses/<course ID>/steps URL.
			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-steps-controller.php';
			$this->sub_controllers['LD_REST_Courses_Steps_Controller_V2'] = new LD_REST_Courses_Steps_Controller_V2();
			$this->sub_controllers['LD_REST_Courses_Steps_Controller_V2']->register_routes();

			// Added support for nested wp-json/courses/<course ID>/prerequisites URL.
			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-prerequisites-controller.php';
			$this->sub_controllers['LD_REST_Courses_Prerequisites_Controller_V2'] = new LD_REST_Courses_Prerequisites_Controller_V2();
			$this->sub_controllers['LD_REST_Courses_Prerequisites_Controller_V2']->register_routes();

			// Added support for nested wp-json/courses/<course ID>/users URL.
			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-users-controller.php';
			$this->sub_controllers['LD_REST_Courses_Users_Controller_V2'] = new LD_REST_Courses_Users_Controller_V2();
			$this->sub_controllers['LD_REST_Courses_Users_Controller_V2']->register_routes();

			// Added support for nested wp-json/courses/<course ID>/groups URL.
			include LEARNDASH_REST_API_DIR . '/' . $this->version . '/class-ld-rest-courses-groups-controller.php';
			$this->sub_controllers['LD_REST_Courses_Groups_Controller_V2'] = new LD_REST_Courses_Groups_Controller_V2();
			$this->sub_controllers['LD_REST_Courses_Groups_Controller_V2']->register_routes();
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
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-display-content.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Display_Content'] = LearnDash_Settings_Metabox_Course_Display_Content::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] = LearnDash_Settings_Metabox_Course_Access_Settings::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-navigation-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Navigation_Settings'] = LearnDash_Settings_Metabox_Course_Navigation_Settings::add_metabox_instance();

			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as $metabox ) {
					$metabox->load_settings_values();
					$metabox->load_settings_fields();
					$this->register_rest_fields( $metabox->get_settings_metabox_fields(), $metabox );
				}
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

			$schema['title'] = 'course';

			return $schema;
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

					if ( ! isset( $current_links['price-type'] ) ) {
						$course_price_type = learndash_get_course_meta_setting( $post->ID, 'course_price_type' );
						if ( ! empty( $course_price_type ) ) {
							$links[ $this->get_rest_base( 'price-types' ) ] = array(
								'href'       => rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'price-types' ) . '/' . $course_price_type ),
								'embeddable' => true,
							);
						}
					}

					if ( ! isset( $current_links['prerequisites'] ) ) {
						$links['prerequisites'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-prerequisites' ),
							'embeddable' => true,
						);
					}

					if ( ! isset( $current_links['steps'] ) ) {
						$links['steps'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-steps' ),
							'embeddable' => true,
						);
					}

					if ( ! isset( $current_links['users'] ) ) {
						$links['users'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-users' ),
							'embeddable' => true,
						);
					}

					if ( ! isset( $current_links['groups'] ) ) {
						$links['groups'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-groups' ),
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

		/**
		 * Intercept the Request and ensure our standard Course parameters are set.
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
			return $query_args;
		}

		// End of functions.
	}
}
