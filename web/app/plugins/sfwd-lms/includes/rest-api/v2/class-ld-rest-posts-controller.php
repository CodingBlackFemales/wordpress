<?php
/**
 * LearnDash REST API V2 Post Controller.
 *
 * This Controller is used as the parent Controller for all LearnDash
 * custom post types like Courses (sfwd-courses), Lessons (sfwd-lessons), Topics (sfwd-topic),
 * Quizzes (sfwd-quiz) and, Questions (sfwd-question).
 *
 * This Controller class extends the WordPress WP_REST_Posts_Controller class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Posts_Controller_V2' ) ) && ( class_exists( 'WP_REST_Posts_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Post Controller.
	 *
	 * @since 3.3.0
	 * @uses WP_REST_Posts_Controller
	 */
	class LD_REST_Posts_Controller_V2 extends WP_REST_Posts_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

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
		protected $sub_base = '';

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
		 * Current Quiz Post object
		 *
		 * @var WP_Post $quiz_post.
		 */
		protected $quiz_post = null;

		/**
		 * Current Post Metaboxes for Settings Fields
		 *
		 * @var array $metaboxes.
		 */
		protected $metaboxes = array();

		/**
		 * Current Post Metaboxes Fields
		 *
		 * @var array $metaboxes_fields.
		 */
		protected $metaboxes_fields = array();

		/**
		 * REST Fields
		 *
		 * @var array $rest_fields.
		 */
		protected $rest_fields = array();

		/**
		 * Save Metabox Fields.
		 *
		 * @var array $saved_metabox_fields.
		 */
		protected $saved_metabox_fields = array();

		/**
		 * Save REST Registered Fields.
		 *
		 * @var array $saved_rest_registered_fields.
		 */
		protected $saved_rest_registered_fields = array();

		/**
		 * Route Methods Singular
		 *
		 * @since 3.4.1
		 *
		 * @var array $route_methods_singular.
		 */
		protected $route_methods_singular = array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE );

		/**
		 * Route Methods Collection
		 *
		 * @since 3.4.1
		 *
		 * @var array $route_methods_collection.
		 */
		protected $route_methods_collection = array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE );

		/**
		 * Taxonomies
		 *
		 * @var array
		 */
		protected $taxonomies = array();

		/**
		 * Rest sub base
		 *
		 * @var string
		 */
		protected $rest_sub_base;

		/**
		 * Protected constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			parent::__construct( $post_type );

			/**
			 * Set the namespace and rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->namespace = trailingslashit( LEARNDASH_REST_API_NAMESPACE ) . $this->version;

			add_filter( "rest_{$post_type}_query", array( $this, 'rest_query_filter' ), 20, 2 );
			add_filter( "rest_prepare_{$post_type}", array( $this, 'rest_prepare_response_filter' ), 20, 3 );
			add_filter( "rest_pre_insert_{$post_type}", array( $this, 'rest_pre_insert_filter' ), 20, 2 );

			add_action( "rest_after_insert_{$post_type}", array( $this, 'rest_after_insert_action' ), 20, 3 );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @see register_rest_route() in WordPress core.
		 *
		 * @since 3.3.0
		 */
		public function register_routes() {

			$this->register_fields();

			$methods_collection = array();

			if ( in_array( WP_REST_Server::READABLE, $this->route_methods_collection, true ) ) {
				$methods_collection[] = array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				);
			}
			if ( in_array( WP_REST_Server::CREATABLE, $this->route_methods_collection, true ) ) {
				$methods_collection[] = array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				);
			}

			if ( ! empty( $methods_collection ) ) {
				$methods_collection['schema'] = array( $this, 'get_public_item_schema' );
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base,
					$methods_collection
				);
			}

			$methods_singular = array();

			if ( in_array( WP_REST_Server::READABLE, $this->route_methods_singular, true ) ) {
				$methods_singular[] = array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				);
			}

			if ( in_array( WP_REST_Server::EDITABLE, $this->route_methods_singular, true ) ) {
				$methods_singular[] = array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				);
			}

			if ( in_array( WP_REST_Server::DELETABLE, $this->route_methods_singular, true ) ) {
				$methods_singular[] = array(
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
				);
			}

			if ( ! empty( $methods_singular ) ) {
				$methods_singular['schema'] = array( $this, 'get_public_item_schema' );
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/(?P<id>[\d]+)',
					$methods_singular
				);
			}
		}

		/**
		 * Retrieves all of the registered additional fields for a given object-type.
		 *
		 * @since 3.3.0.2
		 *
		 * @param string $object_type Optional. The object type.
		 * @return array Registered additional fields (if any), empty array if none or if the object type could
		 *               not be inferred.
		 */
		protected function get_additional_fields( $object_type = null ) {
			$this->swap_rest_registered_fields( $this->post_type );

			$additional_fields = parent::get_additional_fields( $object_type );

			$this->reset_rest_registered_fields( $this->post_type );

			return $additional_fields;
		}

		/**
		 * Gets schema for post type.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_schema() {
			$this->swap_rest_registered_fields( $this->post_type );

			$schema = parent::get_public_item_schema();

			$this->reset_rest_registered_fields( $this->post_type );

			return $schema;
		}

		/**
		 * Retrieves a single post.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			$valid_check = $this->get_post( $request['id'] );
			if ( is_wp_error( $valid_check ) ) {
				return $valid_check;
			}

			if ( ( ! $valid_check ) || ( ! is_a( $valid_check, 'WP_Post' ) ) || ( $this->post_type !== $valid_check->post_type ) ) {
				return $valid_check;
			}

			$this->swap_rest_registered_fields( $this->post_type );

			/**
			 * Initialize the metaboxes so we can apply the updates changes.
			 */
			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as &$metabox ) {
					$metabox->init( $valid_check );
				}
			}

			$response = parent::get_item( $request );

			$this->reset_rest_registered_fields( $this->post_type );

			return $response;
		}

		/**
		 * Retrieves a collection of posts.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_items( $request ) {
			$response = parent::get_items( $request );
			return $response;
		}


		/**
		 * Updates a single post.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_item( $request ) {
			$valid_check = $this->get_post( $request['id'] );
			if ( is_wp_error( $valid_check ) ) {
				return $valid_check;
			}

			if ( ( ! $valid_check ) || ( ! is_a( $valid_check, 'WP_Post' ) ) || ( $this->post_type !== $valid_check->post_type ) ) {
				return $valid_check;
			}

			/**
			 * Initialize the metaboxes so we can apply the updates changes.
			 */
			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as &$metabox ) {
					$metabox->init( $valid_check );
				}
			}

			$this->swap_rest_registered_fields( $this->post_type );

			$response = parent::update_item( $request );

			$this->reset_rest_registered_fields( $this->post_type );

			return $response;
		}

		/**
		 * Stub function for base class to register fields.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields() {
			return true;
		}

		/**
		 * Stub function for base class to register metabox fields.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields_metabox() {
			return true;
		}

		/**
		 * Swap Registered fields from V1 to V2.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_type Optional. The object type.
		 */
		protected function swap_rest_registered_fields( $object_type = null ) {
			global $wp_rest_additional_fields;

			if ( ( $object_type ) && ( $object_type === $this->post_type ) ) {
				$this->saved_rest_registered_fields[ $object_type ] = array();

				if ( isset( $wp_rest_additional_fields[ $object_type ] ) ) {
					$this->saved_rest_registered_fields[ $object_type ] = $wp_rest_additional_fields[ $object_type ];

					global $sfwd_lms;
					$post_args_fields = $sfwd_lms->get_post_args_section( $object_type, 'fields' );
					if ( ! empty( $post_args_fields ) ) {
						foreach ( $post_args_fields as $post_args_field_key => $post_args_field ) {
							if ( ( isset( $post_args_field['show_in_rest'] ) ) && ( true === $post_args_field['show_in_rest'] ) ) {
								if ( isset( $wp_rest_additional_fields[ $object_type ][ $post_args_field_key ] ) ) {
									unset( $wp_rest_additional_fields[ $object_type ][ $post_args_field_key ] );
								}
							}
						}
					}

					if ( ! empty( $this->rest_fields ) ) {
						foreach ( $this->rest_fields as $rest_field_key => $rest_field ) {
							$wp_rest_additional_fields[ $object_type ][ $rest_field_key ] = $rest_field;
						}
					}
				} else {
					$this->saved_rest_registered_fields[ $object_type ] = array();
				}
			}
		}

		/**
		 * Reset Registered fields back to V1.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_type Optional. The object type.
		 */
		protected function reset_rest_registered_fields( $object_type = null ) {
			global $wp_rest_additional_fields;

			if ( ( $object_type ) && ( $object_type === $this->post_type ) ) {
				if ( isset( $this->saved_rest_registered_fields[ $object_type ] ) ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$wp_rest_additional_fields[ $object_type ] = $this->saved_rest_registered_fields[ $object_type ];
				}
			}
		}

		/**
		 * Filters a post before it is inserted via the REST API.
		 *
		 * Called from the WP REST filter 'rest_pre_insert_{$post_type}'
		 *
		 * @since 4.2.1
		 *
		 * @param stdClass        $prepared_post An object representing a single post prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 *
		 * @return stdClass $prepared_post
		 */
		public function rest_pre_insert_filter( $prepared_post, $request ) {

			// Materials content.
			if ( ( isset( $this->rest_fields['materials_enabled'] ) ) && ( isset( $this->rest_fields['materials'] ) ) ) {
				if ( true === $request['materials_enabled'] ) {
					if ( is_string( $request['materials'] ) ) {
						$prepared_post->materials = $request['materials'];
					} elseif ( isset( $request['materials']['raw'] ) ) {
						$prepared_post->materials = $request['content']['raw'];
					} else {
						$prepared_post->materials = '';
					}
				} else {
					$prepared_post->materials = '';
				}
			}

			return $prepared_post;
		}

		/**
		 * Fires after a single post is completely created or updated via the REST API.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Post         $post     Inserted or updated post object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		public function rest_after_insert_action( $post, $request, $creating ) {
			if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type === $this->post_type ) && ( ! empty( $this->metaboxes ) ) ) {

				/**
				 * In REST the $saved_metabox_fields is set during the update_rest_settings_field_value()
				 * function.
				 */
				if ( ! empty( $this->saved_metabox_fields ) ) {
					foreach ( $this->saved_metabox_fields as $metabox_class => $metabox_fields_values ) {
						if ( isset( $this->metaboxes[ $metabox_class ] ) ) {
							$metabox = $this->metaboxes[ $metabox_class ];
							$metabox->init( $post );

							$settings_field_updates = $metabox->apply_metabox_settings_fields_changes( $metabox_fields_values );
							$settings_field_updates = $metabox->validate_metabox_settings_post_updates( $settings_field_updates );
							$settings_field_updates = $metabox->trigger_metabox_settings_post_filters( $settings_field_updates );

							$metabox->save_post_meta_box( $post->ID, $post, $creating, $settings_field_updates );

							/**
							 * After we save the meta data we re-initialize the metabox with the
							 * new values. This will reload metabox->setting_option_values
							 */
							$metabox->init( $post, true );
						}
					}
				}
			}
		}

		/**
		 * Get REST Setting Field value.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $postdata   Post data array.
		 * @param string          $field_name Field Name for $postdata value.
		 * @param WP_REST_Request $request    Request object.
		 * @param string          $post_type  Post Type for request.
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			if ( ( isset( $postdata['id'] ) ) && ( ! empty( $postdata['id'] ) ) ) {
				$ld_post = get_post( $postdata['id'] );
				if ( ( is_a( $ld_post, 'WP_Post' ) ) && ( $ld_post->post_type == $this->post_type ) ) {
					$field_value = '';

					if ( isset( $this->metaboxes_fields[ $field_name ] ) ) {
						$field_set = $this->metaboxes_fields[ $field_name ];
						if ( ( isset( $field_set['settings_field']['name'] ) ) && ( ! empty( $field_set['settings_field']['name'] ) ) ) {
							if ( ( isset( $field_set['metabox'] ) ) && ( ! empty( $field_set['metabox'] ) ) && ( 'LearnDash_Settings_Metabox' === get_parent_class( $field_set['metabox'] ) ) ) {
								$metabox_class_name = get_class( $field_set['metabox'] );
								if ( ( $metabox_class_name ) && ( isset( $this->metaboxes[ $metabox_class_name ] ) ) ) {
									$this->metaboxes[ $metabox_class_name ]->init( $ld_post );
									$field_value = $this->metaboxes[ $metabox_class_name ]->get_metabox_settings_value_by_key( $field_set['settings_field']['name'] );
								}
							} else {
								$field_value = learndash_get_setting( $ld_post, $field_set['settings_field']['name'] );
							}
						}
						if ( ( isset( $field_set['settings_field']['args']['validate_callback'] ) ) && ( ! empty( $field_set['settings_field']['args']['validate_callback'] ) ) && ( is_callable( $field_set['settings_field']['args']['validate_callback'] ) ) ) {
							$validate_args['field'] = $field_set['settings_field']['args'];
							$field_value            = call_user_func( $field_set['settings_field']['args']['validate_callback'], $field_value, $field_name, $validate_args );

							$field_instance = LearnDash_Settings_Fields::get_field_instance( $field_set['settings_field']['args']['type'] );
							if ( ( $field_instance ) && ( 'LearnDash_Settings_Fields' === get_parent_class( $field_instance ) ) ) {
								$field_value = $field_instance->field_value_to_rest_value( $field_value, $field_name, $validate_args, $request );
							}
						} else {
							$field_value = $field_value;
						}
					}
					return $field_value;
				}
			}
		}

		/**
		 * Update REST Settings Field value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed           $post_value  Value of setting to update.
		 * @param WP_Post         $post        Post object being updated.
		 * @param string          $field_name  Settings file name/key.
		 * @param WP_REST_Request $request     Request object.
		 * @param string          $post_type   Post type string.
		 */
		public function update_rest_settings_field_value( $post_value, WP_Post $post, $field_name, WP_REST_Request $request, $post_type ) {
			if ( ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type == $this->post_type ) ) {
				if ( isset( $this->metaboxes_fields[ $field_name ] ) ) {
					$field_set = $this->metaboxes_fields[ $field_name ];
					if ( ! isset( $field_set['settings_field']['name'] ) ) {
						return false;
					}
					$setting_field_name = $field_set['settings_field']['name'];

					if ( ! isset( $field_set['settings_field']['args'] ) ) {
						return false;
					}

					$validate_args['field'] = $field_set['settings_field']['args'];
					$field_instance         = LearnDash_Settings_Fields::get_field_instance( $field_set['settings_field']['args']['type'] );
					if ( ( $field_instance ) && ( 'LearnDash_Settings_Fields' === get_parent_class( $field_instance ) ) ) {
						$post_value = $field_instance->rest_value_to_field_value( $post_value, $setting_field_name, $validate_args );
						if ( ( isset( $field_set['metabox'] ) ) && ( ! empty( $field_set['metabox'] ) ) && ( 'LearnDash_Settings_Metabox' === get_parent_class( $field_set['metabox'] ) ) ) {
							$metabox_class_name = get_class( $field_set['metabox'] );
							if ( ( $metabox_class_name ) && ( isset( $this->metaboxes[ $metabox_class_name ] ) ) ) {
								if ( ! isset( $this->saved_metabox_fields[ $metabox_class_name ] ) ) {
									$this->saved_metabox_fields[ $metabox_class_name ] = array();
								}
								$this->saved_metabox_fields[ $metabox_class_name ][ $setting_field_name ] = $post_value;
							}
						} else {
							$field_value = learndash_get_setting( $post, $setting_field_name );
							if ( $field_value !== $post_value ) {
								return learndash_update_setting( $post->ID, $setting_field_name, $post_value );
							}
						}
					}
				}
			}
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
			if ( ( in_array( $this->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {

				if ( ( isset( $query_params['orderby']['default'] ) ) && ( 'title' !== $query_params['orderby']['default'] ) ) {
					$query_params['orderby']['default'] = 'title';
				}

				if ( ( isset( $query_params['order']['default'] ) ) && ( 'asc' !== $query_params['order']['default'] ) ) {
					$query_params['order']['default'] = 'asc';
				}

				// We add 'course' to the filtering as an option to filter course steps by.
				if ( ! isset( $query_params['course'] ) ) {
					if ( $this->rest_post_type_has_archive( $this->post_type ) ) {
						$course_required = false;
					} else {
						$course_required = true;
					}

					$query_params['course'] = array(
						'description' => sprintf(
							// translators: placeholder: course.
							esc_html_x(
								'Limit results to be within a specific %s.',
								'placeholder: course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'required'    => $course_required,
					);
				}
			}

			return $query_params;
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
			return $query_args;
		}

		/**
		 * Override the REST response links. This is needed when Course Shared Steps is enabled.
		 *
		 * @since 3.3.0
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
		 * Register Settings fields to REST.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $fields  Array of fields for section.
		 * @param object $metabox Metabox object.
		 */
		public function register_rest_fields( $fields = array(), $metabox = null ) {

			if ( ! empty( $fields ) ) {
				// First we need to re-index the fields to use the rest field_key if set.
				$fields_rest = array();
				foreach ( $fields as $field_key => $field ) {
					$rest_field_key = esc_attr( $field_key );
					if ( ( isset( $field['args']['rest']['show_in_rest'] ) ) && ( true === $field['args']['rest']['show_in_rest'] ) ) {
						$field_args = $field['args']['rest']['rest_args'];
						if ( ( isset( $field_args['schema']['field_key'] ) ) && ( ! empty( $field_args['schema']['field_key'] ) ) ) {
							$rest_field_key = esc_attr( $field_args['schema']['field_key'] );
						}
					}
					$fields_rest[ $rest_field_key ] = $field;
				}

				foreach ( $fields_rest as $field_key => $field ) {
					if ( ( ! isset( $field['args'] ) ) || ( ! isset( $field['args']['rest'] ) ) ) {
						continue;
					}

					$field_set = $field['args']['rest'];

					if ( ( isset( $field['args']['rest']['show_in_rest'] ) ) && ( true === $field['args']['rest']['show_in_rest'] ) ) {
						if ( ( isset( $field['args']['rest']['rest_args'] ) ) && ( is_array( $field['args']['rest']['rest_args'] ) ) ) {
							$field_args = $field['args']['rest']['rest_args'];
						} else {
							$field_args = array();
						}

						if ( ! isset( $field_args['get_callback'] ) ) {
							$field_args['get_callback'] = array( $this, 'get_rest_settings_field_value' );
						}

						if ( ! isset( $field_args['update_callback'] ) ) {
							$field_args['update_callback'] = array( $this, 'update_rest_settings_field_value' );
						}

						if ( ( ! isset( $field_args['schema'] ) ) || ( empty( $field_args['schema'] ) ) ) {
							$field_args['schema'] = array();
						}

						if ( ( ! isset( $field_args['schema']['description'] ) ) && ( isset( $field['title'] ) ) ) {
							$field_args['schema']['description'] = $field['title'];
						}

						if ( ! isset( $field_args['schema']['type'] ) ) {
							if ( isset( $field['args']['type'] ) ) {
								switch ( $field['args']['type'] ) {
									case 'select':
									case 'multiselect':
										$field_args['schema']['type'] = 'string';
										break;

									case 'checkbox':
									case 'checkbox-switch':
										$field_args['schema']['type'] = 'boolean';
										break;

									default:
										$field_args['schema']['type'] = $field_set['type'];
										break;
								}
							} else {
								$field_args['schema']['type'] = 'string';
							}
						}

						if ( ( ! isset( $field_args['schema']['required'] ) ) || ( empty( $field_args['schema']['required'] ) ) ) {
							$field_args['schema']['required'] = false;
						}

						if ( ( ! isset( $field_args['schema']['default'] ) ) && ( isset( $field['default'] ) ) ) {
							$field_args['schema']['default'] = $field['default'];
						}

						if ( ( ! isset( $field_args['schema']['enum'] ) ) && ( ( isset( $field['initial_options'] ) ) && ( ! empty( $field_set['initial_options'] ) ) ) ) {
							$field_args['schema']['enum'] = array_keys( $field['initial_options'] );
						}

						if ( ! isset( $field_args['schema']['context'] ) ) {
							$field_args['schema']['context'] = array( 'view', 'edit' );
						}

						$this->metaboxes_fields[ $field_key ] = array(
							'post_type'      => $this->post_type,
							'field_key'      => $field_key,
							'settings_field' => $field,
							'rest_field'     => $field_args,
							'metabox'        => $metabox,
						);

						if ( isset( $field['args']['type'] ) ) {
							switch ( $field['args']['type'] ) {
								case 'radio':
								case 'checkbox-switch':
								case 'select':
									if ( ( isset( $field['args']['options'] ) ) && ( ! empty( $field['args']['options'] ) ) ) {
										foreach ( $field['args']['options'] as $option_set ) {
											if ( ( isset( $option_set['inline_fields'] ) ) && ( ! empty( $option_set['inline_fields'] ) ) ) {
												foreach ( $option_set['inline_fields'] as $inline_field_set ) {
													$this->register_rest_fields( $inline_field_set, $metabox );
												}
											}
										}
									}
									break;

								default:
									break;
							}
						}
					}
				}
			}

			$this->rest_fields = wp_list_pluck( $this->metaboxes_fields, 'rest_field' );
		}

		/**
		 * Check if we are allowing the post type to be publicly viewed
		 * without restrictions to course_id.
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type_slug The post type slug to check.
		 *
		 * @return bool true if has archive.
		 */
		protected function rest_post_type_has_archive( $post_type_slug = '' ) {
			if ( learndash_is_admin_user() ) {
				$learndash_rest_archive_bypass = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
				if ( 'yes' === $learndash_rest_archive_bypass ) {
					$learndash_rest_archive_bypass = true;
				} else {
					$learndash_rest_archive_bypass = learndash_post_type_has_archive( $post_type_slug );
				}
			} else {
				$learndash_rest_archive_bypass = learndash_post_type_has_archive( $post_type_slug );
			}

			// If the archive setting is enabled it means any user can see all of that post type.
			return apply_filters( 'learndash_rest_archive_bypass', $learndash_rest_archive_bypass, $post_type_slug );
		}

		/**
		 * Initialize the parent Course, Lesson, Topic posts from the request instance.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request WP_REST_Request instance.
		 * @return void.
		 */
		protected function rest_init_request_posts( WP_REST_Request $request ) {
			$this->course_post = null;
			$course_id         = (int) $request['course'];
			if ( ! empty( $course_id ) ) {
				$course_post = get_post( $course_id );
				if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
					$this->course_post = $course_post;
				}
			}

			$this->lesson_post = null;
			$lesson_id         = (int) $request['lesson'];
			if ( ! empty( $lesson_id ) ) {
				$lesson_post = get_post( $lesson_id );
				if ( ( $lesson_post ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $lesson_post->post_type ) ) {
					$this->lesson_post = $lesson_post;
				}
			}

			$this->topic_post = null;
			$topic_id         = (int) $request['topic'];
			if ( ! empty( $topic_id ) ) {
				$topic_post = get_post( $topic_id );
				if ( ( $topic_post ) && ( is_a( $topic_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'topic' ) === $topic_post->post_type ) ) {
					$this->topic_post = $topic_post;
				}
			}

			$this->quiz_post = null;
			$quiz_id         = (int) $request['quiz'];
			if ( ! empty( $quiz_id ) ) {
				$quiz_post = get_post( $quiz_id );
				if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
					$this->quiz_post = $quiz_post;
				}
			}
		}

		/**
		 * Get the REST URL setting.
		 *
		 * @since 3.3.0
		 *
		 * @param string $rest_slug     Settings REST slug.
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
