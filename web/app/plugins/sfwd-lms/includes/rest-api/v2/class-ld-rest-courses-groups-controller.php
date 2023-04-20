<?php
/**
 * LearnDash REST API V2 Courses Groups Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between the LearnDash Courses (sfwd-courses) and Groups (groups)
 * custom post types.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Groups_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Courses Groups Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Courses_Groups_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'group' );
			}

			$this->post_type  = $post_type;
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'courses' );
			$this->rest_sub_base = $this->get_rest_base( 'courses-groups' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			$this->register_fields();

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
				'/' . $this->rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base,
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
						'callback'            => array( $this, 'get_courses_groups' ),
						'permission_callback' => array( $this, 'get_courses_groups_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_courses_groups' ),
						'permission_callback' => array( $this, 'update_courses_groups_permissions_check' ),
						'args'                => array(
							'group_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Group, Course.
									esc_html_x(
										'%1$s IDs to enroll into %2$s.',
										'placeholder: Group, Course',
										'learndash'
									),
									learndash_get_custom_label( 'group' ),
									learndash_get_custom_label( 'course' )
								),
								'required'    => true,
								'type'        => 'array',
								'items'       => array(
									'type' => 'integer',
								),
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_courses_groups' ),
						'permission_callback' => array( $this, 'delete_courses_groups_permissions_check' ),
						'args'                => array(
							'group_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Group, Course.
									esc_html_x(
										'%1$s IDs to remove from %2$s.',
										'placeholder: Group, Course',
										'learndash'
									),
									learndash_get_custom_label( 'group' ),
									learndash_get_custom_label( 'course' )
								),
								'required'    => true,
								'type'        => 'array',
								'items'       => array(
									'type' => 'integer',
								),
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
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

			$schema['title']  = 'course-groups';
			$schema['parent'] = 'course';

			return $schema;
		}

		/**
		 * Filter Course Groups query args.
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
					$query_args['post_type'] = learndash_get_post_type_slug( 'group' );

					$course_has_groups = false;

					$this->course_post = get_post( $course_id );
					if ( ( $this->course_post ) && ( is_a( $this->course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $this->course_post->post_type ) ) {
						$course_groups = learndash_get_course_groups( $this->course_post->ID, true );
						if ( ! empty( $course_groups ) ) {
							$course_has_groups      = true;
							$query_args['post__in'] = $query_args['post__in'] ? array_intersect( $course_groups, $query_args['post__in'] ) : $course_groups;
						}
					}

					if ( true !== $course_has_groups ) {
						$query_args['post__in'] = array( 0 );
					}
				}
			}

			return $query_args;
		}

		/**
		 * Override the REST response links.
		 *
		 * When WP renders the post response the 'self' and 'collection' links will have
		 * have a path containing the course slug '/wp-json/ldlms/v2/sfwd-courses/XXX'
		 * even though the post type is a group. So this function corrects those links
		 * to correctly point to the group post.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response WP_REST_Response instance.
		 * @param WP_Post          $post     WP_Post instance.
		 * @param WP_REST_Request  $request  WP_REST_Request instance.
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
			$course_id = (int) $request['id'];
			if ( ! empty( $course_id ) ) {
				// Need to compare the requested route to this controller route.
				$route_url    = $request->get_route();
				$ld_route_url = '/' . $this->namespace . '/' . $this->rest_base . '/' . $course_id . '/' . $this->get_rest_base( 'groups' );
				if ( ( ! empty( $route_url ) ) && ( $ld_route_url === $route_url ) && ( $post->post_type === $this->post_type ) ) {
					$current_links = $response->get_links();

					if ( ! empty( $current_links ) ) {
						foreach ( $current_links as $rel => $links ) {
							if ( in_array( $rel, array( 'self', 'collection' ), true ) ) {
								$links_changed = false;
								foreach ( $links as $lidx => $link ) {
									if ( ( isset( $link['href'] ) ) && ( ! empty( $link['href'] ) ) ) {
										$link_href = str_replace(
											'/' . $this->namespace . '/' . $this->rest_base,
											'/' . $this->namespace . '/' . $this->get_rest_base( 'groups' ),
											$link['href']
										);
										if ( $link['href'] !== $link_href ) {
											$links[ $lidx ]['href'] = $link_href;
											$links_changed          = true;
										}
									}
								}

								if ( true === $links_changed ) {
									$response->remove_link( $rel );
									$response->add_links( array( $rel => $links ) );
								}
							}
						}
					}
				}
			}

			return $response;
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
		public function get_courses_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to update a course groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function update_courses_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to delete a course groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return bool|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
		 */
		public function delete_courses_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Updates a course groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_courses_groups( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_post = get_post( $course_id );
			if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$group_ids = $request['group_ids'];
			if ( ( ! is_array( $group_ids ) ) || ( empty( $group_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Missing %s IDs.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array(
						'status' => 404,
					)
				);
			}
			$group_ids = array_map( 'absint', $group_ids );

			$data = array();

			foreach ( $group_ids as $group_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$group_post = get_post( $group_id );
				if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_invalid_id';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_group_access( $course_id, $group_id, false );
				if ( true === $ret ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'success';
					$data_item->code     = 'learndash_rest_enroll_success';
					$data_item->message  = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s enrolled in %2$s success.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_enroll_failed';
					$data_item->message  = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s already enrolled in %2$s.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				}
				$data[] = $data_item;
			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;

		}

		/**
		 * Delete course groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function delete_courses_groups( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_post = get_post( $course_id );
			if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$group_ids = $request['group_ids'];
			if ( ( ! is_array( $group_ids ) ) || ( empty( $group_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Missing %s IDs.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array(
						'status' => 404,
					)
				);
			}
			$group_ids = array_map( 'absint', $group_ids );

			$data = array();

			foreach ( $group_ids as $group_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$group_post = get_post( $group_id );
				if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_invalid_id';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_group_access( $course_id, $group_id, true );
				if ( true === $ret ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'success';
					$data_item->code     = 'learndash_rest_unenroll_success';
					$data_item->message  = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s enrolled from %2$s success.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_unenroll_failed';
					$data_item->message  = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s not unenrolled from %2$s.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				}
				$data[] = $data_item;
			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
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
		public function get_courses_groups( $request ) {
			return parent::get_items( $request );
		}

		// End of functions.
	}
}
