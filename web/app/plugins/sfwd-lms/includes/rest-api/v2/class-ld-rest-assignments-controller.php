<?php
/**
 * LearnDash REST API V2 Assignment (sfwd-assignment) Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type Assignments (sfwd-assignment).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.

 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Utilities\Cast;

if ( ( ! class_exists( 'LD_REST_Assignments_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {
	/**
	 * Class LearnDash REST API V2 Assignment (sfwd-assignment) Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Assignments_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * Assignment Post data
		 *
		 * @var array $assignment_post_data.
		 */
		private $assignment_post_data = array();

		/**
		 * LearnDash course steps object
		 *
		 * @var object
		 */
		protected $ld_course_steps_object = null;

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'assignment' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			$this->route_methods_singular   = array( WP_REST_Server::READABLE, WP_REST_Server::EDITABLE );
			$this->route_methods_collection = array( WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE );

			parent::__construct( $this->post_type );

			add_filter( "rest_{$post_type}_collection_params", array( $this, 'rest_collection_params_filter' ), 20, 2 );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'assignments' );
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields() {
			register_rest_field(
				$this->post_type,
				'course',
				array(
					'schema'          => array(
						'field_key'   => 'course',
						'description' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'%s ID',
								'placeholder: Course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'lesson',
				array(
					'schema'          => array(
						'field_key'   => 'lesson',
						'description' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x(
								'%s ID',
								'placeholder: Lesson',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'topic',
				array(
					'schema'          => array(
						'field_key'   => 'topic',
						'description' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x(
								'%s ID',
								'placeholder: Topic',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'approved_status',
				array(
					'schema'          => array(
						'field_key'   => 'approved_status',
						'description' => esc_html__( 'Assignment Approved Status', 'learndash' ),
						'type'        => 'string',
						'default'     => '',
						'required'    => false,
						'enum'        => array(
							'',
							'approved',
							'not_approved',
						),
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'points_enabled',
				array(
					'schema'          => array(
						'field_key'   => 'points_enabled',
						'description' => esc_html__( 'Assignment Points Enabled', 'learndash' ),
						'type'        => 'boolean',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'points_max',
				array(
					'schema'          => array(
						'field_key'   => 'points_max',
						'description' => esc_html__( 'Assignment Points Maximum', 'learndash' ),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);
			register_rest_field(
				$this->post_type,
				'points_awarded',
				array(
					'schema'          => array(
						'field_key'   => 'points_awarded',
						'description' => esc_html__( 'Assignment Points Awarded', 'learndash' ),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			$this->register_fields_metabox();

			do_action( 'learndash_rest_register_fields', $this->post_type, $this );
		}

		/**
		 * Register the Settings Fields from the Post Metaboxes.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields_metabox() {
			return true;
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

			$schema['title'] = 'assignment';

			return $schema;
		}

		/**
		 * Check user permission to get/access single item.
		 *
		 * @since 3.3.0
		 * @since 4.10.3 Only admins can access it.
		 * @since 5.0.0 Only logged-in users can access it. Results are filtered based on the logged in user's permissions.
		 *
		 * @param WP_REST_Request<array<string,mixed>> $request WP_REST_Request instance.
		 *
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_item_permissions_check( $request ) {
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'learndash_rest_cannot_view',
					esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			$assignment_id   = Cast::to_int( $request->get_param( 'id' ) );
			$assignment_post = get_post( $assignment_id );

			// If the Assignment does not exist, return an error.
			if (
				empty( $assignment_post )
				|| ! $assignment_post instanceof WP_Post
				|| $assignment_post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::ASSIGNMENT )
			) {
				return new WP_Error(
					'learndash_rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Assignment label.
						esc_html__( 'Invalid %s ID.', 'learndash' ),
						learndash_get_custom_label( 'assignment' )
					),
					[ 'status' => 404 ]
				);
			}

			$can_view = $this->can_view_assignment( Assignment::create_from_post( $assignment_post ) );

			if ( ! $can_view ) {
				return new WP_Error(
					'learndash_rest_cannot_view',
					esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			return true;
		}

		/**
		 * Check user permission to get/access Lessons.
		 *
		 * @since 3.3.0
		 * @since 4.10.3 Only admins can access it.
		 * @since 5.0.0 Only logged-in users can access it. Results are filtered by permissions in the rest_query_filter() method.
		 *
		 * @param object $request  WP_REST_Request instance.
		 *
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			if ( is_user_logged_in() ) {
				return true;
			}

			return new WP_Error(
				'learndash_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		/**
		 * Filter Assignments query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array<string,mixed>                  $query_args Key value array of query var to query value.
		 * @param WP_REST_Request<array<string,mixed>> $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			$meta_query = array();

			$filters = array(
				'course_id' => 0,
				'lesson_id' => 0,
				'topic_id'  => 0,
			);

			if ( current_user_can( 'edit_others_assignments' ) ) {
				$filters['course_id'] = $request['course'];
				$filters['course_id'] = absint( Cast::to_int( $filters['course_id'] ) );

				if ( ! empty( $filters['course_id'] ) ) {
					$filters['lesson_id'] = $request['lesson'];
					$filters['lesson_id'] = absint( Cast::to_int( $filters['lesson_id'] ) );

					$filters['topic_id'] = $request['topic'];
					$filters['topic_id'] = absint( Cast::to_int( $filters['topic_id'] ) );

					if ( ( ! empty( $filters['topic_id'] ) ) && ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $filters['topic_id'] ) ) ) {
						$filters['lesson_id'] = absint( $filters['topic_id'] );
					}
				}

				if ( learndash_is_group_leader_user() ) {
					$group_leader_course_ids  = [];
					$valid_user_ids           = [ get_current_user_id() ]; // Default to the Group Leader's ID.

					$authors = $request->get_param( 'author' );

					if ( ! is_array( $authors ) ) {
						$authors = [ $authors ];
					}

					if ( empty( array_filter( $authors ) ) ) {
						$authors = [];
					}

					$authors = array_map(
						'absint',
						array_map(
							[
								Cast::class,
								'to_int',
							],
							$authors
						)
					);

					$group_leader_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
					if ( ! empty( $group_leader_group_ids ) ) {
						foreach ( $group_leader_group_ids as $group_id ) {
							$course_ids = learndash_group_enrolled_courses( $group_id );
							if ( ! empty( $course_ids ) ) {
								$group_leader_course_ids = array_merge( $group_leader_course_ids, $course_ids );
							}

							$user_ids = learndash_get_groups_user_ids( $group_id );
							if ( ! empty( $user_ids ) ) {
								$valid_user_ids = array_merge( $valid_user_ids, $user_ids );
							}
						}
					}

					/**
					 * If an Author is specified, filter the Assignments by the specified Author IDs but only
					 * if they are in the Group Leader's groups or it is the Group Leader's ID.
					 */
					if ( ! empty( $authors ) ) {
						$valid_user_ids = array_values( array_intersect( $valid_user_ids, $authors ) );
					}

					if (
						! empty( $group_leader_course_ids )
						&& ! empty( $valid_user_ids )
					) {
						$group_leader_course_ids = array_map( 'absint', $group_leader_course_ids );
						$valid_user_ids          = array_map( 'absint', $valid_user_ids );

						if ( ! empty( $filters['course_id'] ) ) {
							if ( ! in_array( $filters['course_id'], $group_leader_course_ids, true ) ) {
								$query_args['post__in'] = [ 0 ];
							}
						} else {
							$filters['course_id'] = $group_leader_course_ids;
						}

						$query_args['author__in'] = $valid_user_ids;
					} else {
						$query_args['post__in'] = [ 0 ];
					}
				}
			} else {
				// If the logged-in user cannot edit others' Assignments, they should only see their own Assignments.
				$query_args['author__in'] = [ get_current_user_id() ];

				$course_id = Cast::to_int( $request->get_param( 'course' ) );

				if ( $course_id > 0 ) {
					$filters['course_id'] = $course_id;
				}

				$user_course_ids = learndash_user_get_enrolled_courses( get_current_user_id() );
				$user_course_ids = array_map( 'absint', $user_course_ids );

				/**
				 * If the user is not enrolled in Courses or the set Course ID is not in the user's
				 * enrolled Courses, filter to exclude all Assignments.
				 */
				if (
					empty( $user_course_ids )
					|| (
						$course_id > 0
						&& ! in_array( $course_id, $user_course_ids, true )
					)
				) {
					$query_args['post__in'] = [ 0 ];
				}
			}

			if ( ! empty( $filters['course_id'] ) ) {
				$meta_query[] = array(
					'key'     => 'course_id',
					'value'   => $filters['course_id'],
					'compare' => is_array( $filters['course_id'] ) ? 'IN' : '=',
				);

				if ( ! empty( $filters['lesson_id'] ) ) {
					$meta_query[] = array(
						'key'     => 'lesson_id',
						'value'   => $filters['lesson_id'],
						'compare' => is_array( $filters['lesson_id'] ) ? 'IN' : '=',
					);
				}

				if ( ! empty( $filters['topic_id'] ) ) {
					$meta_query[] = array(
						'key'     => 'lesson_id',
						'value'   => $filters['topic_id'],
						'compare' => is_array( $filters['topic_id'] ) ? 'IN' : '=',
					);
				}
			}

			$filter_approved_status = $request['approved_status'];
			if ( ! empty( $filter_approved_status ) ) {
				if ( 'approved' === $filter_approved_status ) {
					$meta_query[] = array(
						'key'   => 'approval_status',
						'value' => 1,
					);
				} elseif ( 'not_approved' === $filter_approved_status ) {
					$meta_query[] = array(
						'key'     => 'approval_status',
						'compare' => 'NOT EXISTS',
					);
				}
			}

			if ( ! empty( $meta_query ) ) {
				if ( ( ! isset( $query_args['meta_query'] ) ) || ( empty( $query_args['meta_query'] ) ) ) {
					$query_args['meta_query']   = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$query_args['meta_query'][] = array( 'relation' => 'AND' );
				} else {
					// Get the 'relation' and set to 'AND'.
					$relation_item = array();
					foreach ( $query_args['meta_query'] as $meta_idx => $meta_item ) {
						if ( ( isset( $meta_item['relation'] ) ) && ( 'AND' !== strtoupper( $meta_item['relation'] ) ) ) {
							$query_args['meta_query'][ $meta_idx ]['relation'] = 'AND';
						}
					}
				}

				$query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

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
			$current_links = $response->get_links();

			list( $course_id, $lesson_id, $topic_id, $parent_id ) = $this->get_assignment_post_data( $post->ID );

			$links = array();
			if ( ( ! isset( $response->links['course'] ) ) && ( ! empty( $course_id ) ) ) {
				$links['course'] = array(
					'href'       => rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'courses' ) . '/' . $course_id ),
					'embeddable' => true,
				);
			}

			if ( ( ! isset( $response->links['lesson'] ) ) && ( ! empty( $lesson_id ) ) ) {
				$lesson_url = rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'lessons' ) . '/' . $lesson_id );

				if ( ! empty( $course_id ) ) {
					$lesson_url = add_query_arg( 'course', $course_id, $lesson_url );
				}
				$links['lesson'] = array(
					'href'       => $lesson_url,
					'embeddable' => true,
				);
			}

			if ( ( ! isset( $response->links['topic'] ) ) && ( ! empty( $topic_id ) ) ) {
				$topic_url = rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'topics' ) . '/' . $topic_id );

				if ( ! empty( $course_id ) ) {
					$topic_url = add_query_arg( 'course', $course_id, $topic_url );
				}

				if ( ! empty( $lesson_id ) ) {
					$topic_url = add_query_arg( 'lesson', $lesson_id, $topic_url );
				}

				$links['topic'] = array(
					'href'       => $topic_url,
					'embeddable' => true,
				);
			}

			if ( ! isset( $response->links['assignment_link'] ) ) {
				$links['assignment_link'] = array(
					'href'       => learndash_assignment_get_download_url( $post->ID ),
					'embeddable' => false,
				);
			}

			if ( ! empty( $links ) ) {
				$response->add_links( $links );
			}

			return $response;
		}

		/**
		 * Add our collection parameters.
		 *
		 * This is added only for GET/OPTIONS Requests.
		 *
		 * @since 3.3.0
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type string.
		 */
		public function rest_collection_params_filter( array $query_params, WP_Post_Type $post_type ) {
			if ( $post_type->name === $this->post_type ) {
				if ( isset( $query_params['status'] ) ) {
					$query_params['status']['default']       = 'any';
					$query_params['status']['items']['enum'] = array_intersect( array( 'graded', 'not_graded' ), $query_params['status']['items']['enum'] );
				}

				// We add 'course' to the filtering as an option to filter course steps by.
				if ( ! isset( $query_params['course'] ) ) {
					$query_params['course'] = array(
						'description' => sprintf(
							// translators: placeholder: course.
							esc_html_x(
								'Filter by %s ID',
								'placeholder: course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'required'    => false,
						'context'     => array( 'view', 'edit' ),
					);
				}

				if ( ! isset( $query_params['lesson'] ) ) {
					$query_params['lesson'] = array(
						'description' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x( 'Filter by %s ID', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view' ),
						'required'    => false,
					);
				}

				if ( ! isset( $query_params['topic'] ) ) {
					$query_params['topic'] = array(
						'description' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x( 'Filter by %s ID', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view' ),
						'required'    => false,
					);
				}

				if ( ! isset( $query_params['approved_status'] ) ) {
					$query_params['approved_status'] = array(
						'context'     => [ 'view' ],
						'default'     => '',
						'description' => sprintf(
							// translators: placeholder: %1$s: Assignment label, %2$s: Assignments label lowercase.
							__( 'Filter by %1$s Approved Status. "approved" will show only approved %2$s, "not_approved" will show only not approved %2$s, "" will show all %2$s.', 'learndash' ),
							learndash_get_custom_label( 'assignment' ),
							learndash_get_custom_label_lower( 'assignments' ),
						),
						'example'     => 'approved',
						'required'    => false,
						'type'        => 'string',
						'enum'        => [
							'',
							'approved',
							'not_approved',
						],
					);
				}
			}

			return $query_params;
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
			if ( ( isset( $postdata['id'] ) ) && ( ! empty( $postdata['id'] ) ) && ( $post_type == $this->post_type ) ) {
				$field_value = '';

				list( $course_id, $lesson_id, $topic_id, $parent_id ) = $this->get_assignment_post_data( $postdata['id'] );

				switch ( $field_name ) {
					case 'course':
						if ( ! empty( $course_id ) ) {
							$course_post = get_post( $course_id );
							if ( ( ! $course_post ) && ( ! is_a( $course_post, 'WP_Post' ) ) ) {
								$course_id = 0;
							}
						} else {
							$course_id = 0;
						}
						$field_value = (int) $course_id;
						break;

					case 'lesson':
						if ( ! empty( $lesson_id ) ) {
							$lesson_post = get_post( $lesson_id );
							if ( ( ! $lesson_post ) && ( ! is_a( $lesson_post, 'WP_Post' ) ) ) {
								$lesson_id = 0;
							}
						} else {
							$lesson_id = 0;
						}
						$field_value = (int) $lesson_id;
						break;

					case 'topic':
						if ( ! empty( $topic_id ) ) {
							$topic_post = get_post( $topic_id );
							if ( ( ! $topic_post ) && ( ! is_a( $topic_post, 'WP_Post' ) ) ) {
								$topic_id = 0;
							}
						} else {
							$topic_id = 0;
						}
						$field_value = (int) $topic_id;
						break;

					case 'approved_status':
						$field_value = learndash_is_assignment_approved_by_meta( $postdata['id'] );
						if ( '1' === $field_value ) {
							$field_value = 'approved';
						} else {
							$field_value = 'not_approved';
						}
						break;

					case 'points_enabled':
						$field_value = learndash_assignment_is_points_enabled( $postdata['id'] );
						break;

					case 'points_max':
						if ( learndash_assignment_is_points_enabled( $postdata['id'] ) ) {
							$field_value = (int) learndash_get_setting( $parent_id, 'lesson_assignment_points_amount' );
						} else {
							$field_value = 0;
						}
						break;

					case 'points_awarded':
						if ( learndash_assignment_is_points_enabled( $postdata['id'] ) ) {
							$field_value = (int) get_post_meta( $postdata['id'], 'points', true );
						} else {
							$field_value = 0;
						}
						break;

					default:
						break;
				}

				return $field_value;
			}
		}

		/**
		 * Initialize the Assignment Post data elements used by many class functions.
		 *
		 * @since 3.3.0
		 *
		 * @param int $post_id Assignment Post ID.
		 */
		protected function get_assignment_post_data( $post_id = 0 ) {
			$course_id = 0;
			$lesson_id = 0;
			$topic_id  = 0;
			$parent_id = 0;

			if ( ! empty( $post_id ) ) {
				if ( ! isset( $this->assignment_post_data[ $post_id ] ) ) {
					$course_id = (int) get_post_meta( $post_id, 'course_id', true );
					$lesson_id = (int) get_post_meta( $post_id, 'lesson_id', true );

					if ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $lesson_id ) ) {
						$topic_id  = absint( $lesson_id );
						$lesson_id = (int) learndash_course_get_single_parent_step( $course_id, $topic_id );
					}

					$this->assignment_post_data[ $post_id ]['course_id'] = $course_id;
					$this->assignment_post_data[ $post_id ]['lesson_id'] = $lesson_id;
					$this->assignment_post_data[ $post_id ]['topic_id']  = $topic_id;
				} else {
					if ( isset( $this->assignment_post_data[ $post_id ]['course_id'] ) ) {
						$course_id = $this->assignment_post_data[ $post_id ]['course_id'];
					}
					if ( isset( $this->assignment_post_data[ $post_id ]['lesson_id'] ) ) {
						$lesson_id = $this->assignment_post_data[ $post_id ]['lesson_id'];
					}
					if ( isset( $this->assignment_post_data[ $post_id ]['topic_id'] ) ) {
						$topic_id = $this->assignment_post_data[ $post_id ]['topic_id'];
					}
				}

				if ( ! empty( $topic_id ) ) {
					$parent_id = $topic_id;
				} elseif ( ! empty( $lesson_id ) ) {
					$parent_id = $lesson_id;
				}
			}

			return array( $course_id, $lesson_id, $topic_id, $parent_id );
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
				$lesson_id = (int) get_post_meta( $post->ID, 'lesson_id', true );
				if ( empty( $lesson_id ) ) {
					return false;
				}

				$lesson_post = get_post( $lesson_id );
				if ( ( ! $lesson_post ) && ( ! is_a( $lesson_post, 'WP_Post' ) ) ) {
					return false;
				}

				switch ( $field_name ) {
					case 'approved_status':
						// We don't allow assignment status to revert to unapproved.
						if ( $post_value ) {
							learndash_approve_assignment( $post->post_author, $lesson_id, $post->ID );
						}
						break;

					case 'points_awarded':
						if ( learndash_assignment_is_points_enabled( $post->ID ) ) {
							$max_points     = absint( Cast::to_int( learndash_get_setting( $lesson_id, 'lesson_assignment_points_amount' ) ) );
							$points_awarded = absint( Cast::to_int( $post_value ) );

							if ( $points_awarded > $max_points ) {
								$points_awarded = $max_points;
							}

							update_post_meta( $post->ID, 'points', $points_awarded );
						}
						break;

					// We don't allow updates to Course, Lesson, Lesson Points enabled, Lesson Points max.
					case 'course':
					case 'lesson':
					case 'points_enabled':
					case 'points_max':
					default:
						break;
				}
			}
		}

		/**
		 * Checks if a given post type can be viewed or managed.
		 *
		 * @since 4.10.3
		 *
		 * @param WP_Post_Type|string $post_type Post type name or object.
		 *
		 * @return bool Whether the post type is allowed in REST.
		 */
		protected function check_is_post_type_allowed( $post_type ) {
			return true;
		}

		/**
		 * Checks if a given request has access to update a post.
		 *
		 * @since 4.10.3
		 * @since 5.0.0 Admins and Group Leaders can now update assignments.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function update_item_permissions_check( $request ) {
			$assignment_id   = Cast::to_int( $request->get_param( 'id' ) );
			$assignment_post = get_post( $assignment_id );

			// If the Assignment does not exist, return an error.
			if (
				empty( $assignment_post )
				|| ! $assignment_post instanceof WP_Post
				|| $assignment_post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::ASSIGNMENT )
			) {
				return new WP_Error(
					'learndash_rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Assignment label.
						esc_html__( 'Invalid %s ID.', 'learndash' ),
						learndash_get_custom_label( 'assignment' )
					),
					[ 'status' => 404 ]
				);
			}

			$assignment = Assignment::create_from_post( $assignment_post );

			// Non-admins cannot update their own assignments.
			if (
				! learndash_is_admin_user( get_current_user_id() )
				&& $assignment->get_post_author_id() === get_current_user_id()
			) {
				return new WP_Error(
					'learndash_rest_cannot_edit',
					esc_html__( 'Sorry, you are not allowed to edit this post.', 'learndash' ),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			$can_view = $this->can_view_assignment( $assignment );

			if ( ! $can_view ) {
				return new WP_Error(
					'learndash_rest_cannot_edit',
					esc_html__( 'Sorry, you are not allowed to edit this post.', 'learndash' ),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			return true;
		}

		/**
		 * Checks if a given user can view an assignment.
		 *
		 * @since 5.0.0
		 *
		 * @param Assignment $assignment Assignment model.
		 * @param ?int       $user_id    User ID. If null, the current user will be used.
		 *
		 * @return bool
		 */
		private function can_view_assignment( Assignment $assignment, ?int $user_id = null ): bool {
			if (
				$user_id === null
				|| $user_id <= 0
			) {
				$user_id = get_current_user_id();
			}

			if ( ! $user_id ) {
				return false;
			}

			if (
				$assignment->get_post_author_id() === $user_id // Assignment owners can view their own assignments.
				|| learndash_is_admin_user( $user_id ) // Admins can view all assignments.
			) {
				return true;
			}

			// Group Leaders can view assignments submitted by users in their groups.
			if ( learndash_is_group_leader_user() ) {
				$group_leader_group_ids  = learndash_get_administrators_group_ids( $user_id );
				$group_leader_course_ids = [];
				$group_leader_user_ids   = [];

				foreach ( $group_leader_group_ids as $group_id ) {
					$group_leader_course_ids = array_unique(
						array_merge(
							$group_leader_course_ids,
							learndash_group_enrolled_courses( $group_id )
						)
					);

					$group_leader_user_ids = array_unique(
						array_merge(
							$group_leader_user_ids,
							learndash_get_groups_user_ids( $group_id )
						)
					);
				}

				// If this Group Leader has no Users or Courses in its Groups, they cannot view any assignments.
				if (
					empty( $group_leader_user_ids )
					|| empty( $group_leader_course_ids )
				) {
					return false;
				}

				$assignment_course_id = $assignment->get_course_id();

				// If the Assignment was not created in a Course that the Group Leader has access to, they cannot view it.
				if (
					$assignment_course_id > 0
					&& ! in_array(
						$assignment_course_id,
						$group_leader_course_ids,
						true
					)
				) {
					return false;
				}

				// If the Assignment was created by a user in the Group Leader's Groups, they can view it.
				if (
					! in_array(
						$assignment->get_post_author_id(),
						$group_leader_user_ids,
						true
					)
				) {
					return false;
				}
			}

			// After all other checks, if the user can edit others' assignments, they can view the assignment.
			if ( current_user_can( 'edit_others_assignments' ) ) {
				return true;
			}

			return false;
		}
	}
}
