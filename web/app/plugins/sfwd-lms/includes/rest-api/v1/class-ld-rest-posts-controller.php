<?php
/**
 * LearnDash REST API V1 Post Controller.
 *
 * @since 2.5.8
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Posts_Controller_V1' ) ) && ( class_exists( 'WP_REST_Posts_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Post Controller.
	 *
	 * @since 2.5.8
	 */
	abstract class LD_REST_Posts_Controller_V1 extends WP_REST_Posts_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * REST API version.
		 *
		 * @var $version string.
		 */
		protected $version = 'v1';

		/**
		 * REST API Sub-Controllers
		 *
		 * @var array $sub_controllers.
		 */
		protected $sub_controllers = array();

		/**
		 * Current Course Post object
		 *
		 * @var WP_Post $course_post.
		 */
		protected $course_post = null;

		/**
		 * Current Lesson Post object
		 *
		 * @var WP_Post $lesson_post.
		 */
		protected $lesson_post = null;

		/**
		 * Current Topic Post object
		 *
		 * @var WP_Post $topic_post.
		 */
		protected $topic_post = null;

		/**
		 * Taxonomies
		 *
		 * @var array
		 */
		protected $taxonomies = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.5.8
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			parent::__construct( $post_type );

			add_filter( "rest_{$this->post_type}_collection_params", array( $this, 'rest_collection_params_filter' ), 20, 2 );
			add_filter( "rest_{$this->post_type}_query", array( $this, 'rest_query_filter' ), 20, 2 );
			add_filter( "rest_prepare_{$this->post_type}", array( $this, 'rest_prepare_response_filter' ), 20, 3 );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 2.5.8
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes_wpv2() {

			if ( class_exists( 'LD_REST_Posts_Gutenberg_Controller' ) ) {
					$g = new LD_REST_Posts_Gutenberg_Controller( $this->post_type );
				$g->register_routes();
			}
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 2.5.8
		 */
		public function register_fields() {
			global $sfwd_lms;

			$post_args_fields = $sfwd_lms->get_post_args_section( $this->post_type, 'fields' );

			if ( ! empty( $post_args_fields ) ) {
				foreach ( $post_args_fields as $field_key => $field_set ) {

					if ( ( isset( $field_set['show_in_rest'] ) ) && ( true === $field_set['show_in_rest'] ) ) {
						if ( ( isset( $field_set['rest_args'] ) ) && ( is_array( $field_set['rest_args'] ) ) ) {
							$field_args = $field_set['rest_args'];
						} else {
							$field_args = array();
						}

						if ( ! isset( $field_args['get_callback'] ) ) {
							$field_args['get_callback'] = array( $this, 'ld_get_field_value' );
						}

						if ( ! isset( $rest_field_args['update_callback'] ) ) {
							$field_args['update_callback'] = array( $this, 'ld_update_field_value' );
						}

						if ( ! isset( $field_args['sanitize_callback'] ) ) {
							$field_args['sanitize_callback'] = 'sanitize_key';
						}

						$field_args['schema']['validate_callback'] = array( $this, 'ld_rest_validate_request_arg' );

						if ( ( ! isset( $field_args['schema'] ) ) || ( empty( $field_args['schema'] ) ) ) {
							$field_args['schema'] = array();
						}

						if ( ( ! isset( $field_args['schema']['description'] ) ) && ( isset( $field_set['name'] ) ) ) {
							$field_args['schema']['description'] = $field_set['name'];
						}

						if ( ( ! isset( $field_args['schema']['type'] ) ) && ( isset( $field_set['type'] ) ) ) {
							switch ( $field_set['type'] ) {
								case 'select':
								case 'multiselect':
									$field_args['schema']['type'] = 'string';
									break;

								case 'checkbox':
									$field_args['schema']['type'] = 'boolean';
									break;

								default:
									$field_args['schema']['type'] = $field_set['type'];
									break;
							}
						}

						if ( ( ! isset( $field_args['schema']['required'] ) ) || ( empty( $field_args['schema']['required'] ) ) ) {
							$field_args['schema']['required'] = false;
						}

						if ( ( ! isset( $field_args['schema']['default'] ) ) && ( isset( $field_set['default'] ) ) ) {
							$field_args['schema']['default'] = $field_set['default'];
						}

						if ( ( ! isset( $field_args['schema']['enum'] ) ) && ( ( isset( $field_set['initial_options'] ) ) && ( ! empty( $field_set['initial_options'] ) ) ) ) {
							$field_args['schema']['enum'] = array_keys( $field_set['initial_options'] );
						}

						if ( ! isset( $field_args['schema']['context'] ) ) {
							$field_args['schema']['context'] = array( 'view', 'edit' );
						}

						register_rest_field(
							$this->post_type,
							$field_key,
							$field_args
						);

					}
				}
			}
		}

		/**
		 * Validate REST request args.
		 *
		 * @since 2.5.8
		 *
		 * @param mixed  $value REST request value.
		 * @param array  $args  REST request args.
		 * @param string $param REST request param.
		 */
		public function ld_rest_validate_request_arg( $value, $args, $param = '' ) {
			return true;
		}

		/**
		 * Get Field Value
		 *
		 * @since 2.5.8
		 *
		 * @param array           $postdata   Post data.
		 * @param string          $field_name Field name.
		 * @param WP_REST_Request $request    WP_REST_Request instance.
		 * @param string          $post_type  Post Type.
		 */
		public function ld_get_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			if ( ( isset( $postdata['id'] ) ) && ( ! empty( $postdata['id'] ) ) ) {
				$ld_post = get_post( $postdata['id'] );
				if ( ( is_a( $ld_post, 'WP_Post' ) ) && ( $ld_post->post_type == $this->post_type ) ) {
					$field_value = learndash_get_setting( $ld_post, $field_name );

					switch ( $field_name ) {
						case 'course_materials':
							$field_value = wp_specialchars_decode( $field_value, ENT_QUOTES );
							if ( ! empty( $field_value ) ) {
								$field_value = do_shortcode( $field_value );
							}
							break;

						case 'course_price_type':
							if ( 'paynow' === $field_value ) {
								$field_value = 'buynow';
							}

							break;

						default:
							break;
					}

					return $field_value;
				}
			}
		}

		/**
		 * Update Field Value
		 *
		 * @since 2.5.8
		 *
		 * @param mixed           $value      Field value.
		 * @param WP_Post         $post       WP_Post instance.
		 * @param string          $field_name Field name.
		 * @param WP_REST_Request $request    WP_REST_Request instance.
		 * @param string          $post_type  Post Type.
		 */
		public function ld_update_field_value( $value, WP_Post $post, $field_name, WP_REST_Request $request, $post_type ) {
			switch ( $field_name ) {
				case 'course_prerequisite_enabled':
					if ( true === $value ) {
						$value = 'on';
					}
					break;

				case 'course_price_type':
					if ( 'buynow' === $value ) {
						$value = 'paynow';
					}
					break;

				default:
					break;
			}
			learndash_update_setting( $post->ID, $field_name, $value );

			return true;
		}

		/**
		 * Filters collection parameters for the posts controller.
		 *
		 * @since 2.5.8
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type object.
		 */
		public function rest_collection_params_filter( $query_params, $post_type ) {
			global $learndash_post_types;

			if ( in_array( $this->post_type, $learndash_post_types, true ) ) {

				if ( ( isset( $query_params['orderby']['default'] ) ) && ( 'title' != $query_params['orderby']['default'] ) ) {
					$query_params['orderby']['default'] = 'title';
				}

				if ( ( isset( $query_params['order']['default'] ) ) && ( 'asc' != $query_params['order']['default'] ) ) {
					$query_params['order']['default'] = 'asc';
				}
			}

			return $query_params;
		}

		/**
		 * Filter query args.
		 *
		 * @since 2.5.8
		 *
		 * @param array           $args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $args, $request ) {
			return $args;
		}

		/**
		 * Override the REST response links. This is needed when Course Shared Steps is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param WP_REST_Response $response WP_REST_Response instance.
		 * @param WP_Post          $post     WP_Post instance.
		 * @param WP_REST_Request  $request  WP_REST_Request instance.
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
			if ( ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) && ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {
				$request_params_json = $request->get_json_params();
				if ( ( isset( $request_params_json['course_id'] ) ) && ( ! empty( $request_params_json['course_id'] ) ) ) {
					$course_id = absint( $request_params_json['course_id'] );
					if ( ! empty( $course_id ) ) {
						$link                                 = learndash_get_step_permalink( $post->ID, $course_id );
						$response->data['link']               = $link;
						$response->data['permalink_template'] = str_replace( $post->post_name, '%pagename%', $link );

						// These are not needed or used on the Gutenberg UI but change anyway.
						$response->data['guid']['rendered'] = $link;
						$response->data['guid']['raw']      = $link;
					}
				}
			}

			return $response;
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
