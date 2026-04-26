<?php
/**
 * LearnDash Assignments (sfwd-assignment) Posts Listing.
 *
 * @since 3.2.3
 * @package LearnDash\Assignment\Listing
 */

use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\WhereQueryBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Assignments_Listing' ) ) ) {

	/**
	 * Class LearnDash Assignments (sfwd-assignment) Posts Listing.
	 *
	 * @since 3.2.3
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Assignments_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.3
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'assignment' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}

			$this->selectors = array(
				'author'          => array(
					'type'                     => 'user',
					'show_all_value'           => '',
					'show_all_label'           => esc_html__( 'All Authors', 'learndash' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_author' ),
					'selector_value_function'  => array( $this, 'selector_value_for_author' ),
					'selector_filters'         => array( 'group_id' ),
				),
				'approval_status' => array(
					'type'                   => 'early',
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'Approval Status', 'learndash' ),
					'options'                => array(
						'approved'     => esc_html__( 'Approved', 'learndash' ),
						'not_approved' => esc_html__( 'Not Approved', 'learndash' ),
					),
					'listing_query_function' => array( $this, 'filter_by_approval_status' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'group_id'        => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'group' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'All %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_group' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_group' ),
					'selector_value_function'  => array( $this, 'selector_value_for_group' ),
				),
				'course_id'       => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_value_function'  => array( $this, 'selector_value_for_course' ),
					'selector_filters'         => array( 'group_id' ),
				),
				'lesson_id'       => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'lesson' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( 'All %s', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_lesson' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_lesson' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
					'selector_filters'         => array( 'course_id' ),
				),
				'topic_id'        => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'topic' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( 'All %s', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_topic' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_topic' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
					'selector_filters'         => array( 'course_id', 'lesson_id' ),
				),
			);

			$this->columns = array(
				'approval_status' => array(
					'label'   => esc_html__( 'Status / Points', 'learndash' ),
					'after'   => 'author',
					'display' => array( $this, 'show_column_assignment_approval_status' ),
				),
				'course'          => array(
					'label'    => sprintf(
						// translators: Assigned Course Label.
						esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'approval_status',
					'display'  => array( $this, 'show_column_step_course' ),
					'required' => true,
				),
				'lesson_topic'    => array(
					'label'   => sprintf(
						// translators: Placeholders: Lesson, Topic.
						esc_html_x( 'Assigned %1$s / %2$s', 'Placeholders: Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'after'   => 'course',
					'display' => array( $this, 'show_column_step_lesson_or_topic' ),
				),
			);

			parent::listing_init();

			$this->listing_init_done = true;

			add_filter(
				'learndash_listing_selector_user_selector_query_args',
				array( $this, 'learndash_listing_selector_user_query_args_assignments' ),
				30,
				2
			);
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.3
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				add_action( 'admin_footer', array( $this, 'assignment_bulk_actions' ), 30 );
				add_filter( 'learndash_listing_table_query_vars_filter', array( $this, 'listing_table_query_vars_filter_assignments' ), 30, 3 );
				add_action( 'admin_footer', array( $this, 'add_view_modal' ) );
				add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 20, 2 );

				$this->assignment_bulk_actions_approve();
			}
		}

		/**
		 * Listing table query vars
		 *
		 * Restricts the assignments list so that:
		 * - Admins see all assignments (optionally filtered by author/group selectors).
		 * - Group Leaders with advanced user management see all assignments.
		 * - Group Leaders with advanced course access only (not advanced users) see assignments
		 *   submitted to courses they created or manage via their groups, OR submitted by users
		 *   within their groups.
		 * - Group Leaders with basic settings see only assignments from users in their groups.
		 * - Users with edit_others_assignments see all.
		 * - Other users see only their own assignments.
		 *
		 * @since 3.2.3
		 *
		 * @param array  $q_vars    Array of query vars.
		 * @param string $post_type Post Type being displayed.
		 * @param array  $query     Main Query.
		 */
		public function listing_table_query_vars_filter_assignments( $q_vars, $post_type, $query ) {
			if ( $post_type === $this->post_type ) {

				// Admins can see all assignments.
				if ( learndash_is_admin_user() ) {
					return $this->apply_selector_filters( $q_vars );
				}

				// Group Leaders: visibility controlled by GL settings (basic/advanced).
				if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
					$is_advanced_users   = ( 'advanced' === learndash_get_group_leader_manage_users() );
					$is_advanced_courses = ( 'advanced' === learndash_get_group_leader_manage_courses() );

					if ( $is_advanced_users ) {
						// Advanced user management: can see all assignments.
						return $this->apply_selector_filters( $q_vars );
					}

					if ( $is_advanced_courses ) {
						// Advanced course access only: can see assignments in their courses (created or
						// managed via groups) OR submitted by users within their groups.
						$assignment_ids     = $this->get_advanced_courses_group_leader_assignment_ids();
						$q_vars['post__in'] = ! empty( $assignment_ids ) ? $assignment_ids : array( 0 );

						return $this->apply_selector_filters( $q_vars );
					}

					// Basic GL: can only see assignments from users in their groups.
					$gl_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
					if ( ! empty( $gl_user_ids ) ) {
						$q_vars['author__in'] = $gl_user_ids;
					} else {
						// GL has no groups - show only their own assignments.
						$q_vars['author__in'] = array( get_current_user_id() );
					}

					return $this->apply_selector_filters( $q_vars );
				}

				// Users with edit_others_assignments capability (but not admin/GL) can see all.
				if ( current_user_can( 'edit_others_assignments' ) ) {
					return $this->apply_selector_filters( $q_vars );
				}

				// Regular users: can only see their own assignments.
				$q_vars['author__in'] = array( get_current_user_id() );
			}

			return $q_vars;
		}

		/**
		 * Collect all assignment IDs visible to a Group Leader with advanced course access only.
		 *
		 * Combines two sources with OR logic (not natively supported by WP_Query):
		 * - Assignments submitted to courses the GL created (post_author) or manages via groups.
		 * - Assignments submitted by users within the GL's groups.
		 *
		 * Uses StellarWP DB to avoid multiple unbounded get_posts() calls.
		 *
		 * @since 5.0.5
		 *
		 * @return array<int, int> Assignment post IDs.
		 */
		protected function get_advanced_courses_group_leader_assignment_ids(): array {
			$current_user_id = get_current_user_id();

			// Courses the GL manages via their groups.
			$group_course_ids = array_map( 'absint', (array) learndash_get_groups_administrators_courses( $current_user_id ) );

			// Courses the GL authored directly — a targeted single-column query.
			$created_course_ids = array_map(
				'absint',
				(array) DB::get_col(
					DB::table( 'posts' )
						->select( 'ID' )
						->where( 'post_type', learndash_get_post_type_slug( 'course' ) )
						->where( 'post_author', $current_user_id )
						->where( 'post_status', 'trash', '!=' )
						->getSQL()
				)
			);

			$all_course_ids = array_unique( array_merge( $group_course_ids, $created_course_ids ) );
			$gl_user_ids    = array_map( 'absint', (array) learndash_get_groups_administrators_users( $current_user_id ) );

			if (
				empty( $all_course_ids )
				&& empty( $gl_user_ids )
			) {
				return array();
			}

			// Build a single query combining both sources with OR — avoids two separate unbounded lookups.
			$assignment_type = learndash_get_post_type_slug( 'assignment' );

			$query = DB::table( 'posts', 'p' )
				->select( 'p.ID' )
				->distinct()
				->where( 'p.post_type', $assignment_type )
				->where( 'p.post_status', 'trash', '!=' );

			if ( ! empty( $all_course_ids ) ) {
				$query->join(
					function ( JoinQueryBuilder $builder ) {
						$builder
							->leftJoin( 'postmeta', 'pm' )
							->on( 'p.ID', 'pm.post_id' )
							->andOn( 'pm.meta_key', 'course_id', true );
					}
				);
			}

			if (
				! empty( $all_course_ids )
				&& ! empty( $gl_user_ids )
			) {
				$query->where(
					function ( WhereQueryBuilder $builder ) use ( $all_course_ids, $gl_user_ids ) {
						$builder
							->whereIn( 'pm.meta_value', $all_course_ids )
							->orWhereIn( 'p.post_author', $gl_user_ids );
					}
				);
			} elseif ( ! empty( $all_course_ids ) ) {
				$query->whereIn( 'pm.meta_value', $all_course_ids );
			} else {
				$query->whereIn( 'p.post_author', $gl_user_ids );
			}

			return array_map(
				'absint',
				(array) DB::get_col( $query->getSQL() )
			);
		}

		/**
		 * Apply author/group selector filters to query vars.
		 *
		 * Intersects any existing `author__in` restriction with the selector values,
		 * so that pre-existing role-based restrictions are always respected.
		 *
		 * @since 5.0.5
		 *
		 * @param array<string, mixed> $q_vars Query vars.
		 *
		 * @return array<string, mixed> Modified query vars.
		 */
		protected function apply_selector_filters( array $q_vars ): array {
			$author_selector = $this->get_selector( 'author' );
			$group_selector  = $this->get_selector( 'group_id' );

			// Filter by selected author, intersecting with any existing restriction.
			if ( ! empty( $author_selector['selected'] ) ) {
				$selected_author = absint( $author_selector['selected'] );

				if ( ! empty( $q_vars['author__in'] ) ) {
					if ( in_array( $selected_author, (array) $q_vars['author__in'], true ) ) {
						$q_vars['author__in'] = array( $selected_author );
					} else {
						$q_vars['author__in'] = array( 0 );
					}
				} else {
					$q_vars['author__in'] = array( $selected_author );
				}
			}

			// Filter by selected group's users, intersecting with any existing restriction.
			if ( ! empty( $group_selector['selected'] ) ) {
				$user_ids = learndash_get_groups_user_ids( absint( $group_selector['selected'] ) );

				if ( ! empty( $user_ids ) ) {
					if ( ! empty( $q_vars['author__in'] ) ) {
						$q_vars['author__in'] = array_values( array_intersect( (array) $q_vars['author__in'], $user_ids ) );

						if ( empty( $q_vars['author__in'] ) ) {
							$q_vars['author__in'] = array( 0 );
						}
					} else {
						$q_vars['author__in'] = $user_ids;
					}
				} else {
					$q_vars['author__in'] = array( 0 );
				}
			}

			return $q_vars;
		}

		/**
		 * Listing user selector filter
		 *
		 * @since 3.3.0
		 *
		 * @param array  $q_vars    Array of query vars.
		 * @param string $post_type Post Type being displayed.
		 */
		public function learndash_listing_selector_user_query_args_assignments( $q_vars, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$group_selector = $this->get_selector( 'group_id' );
				if ( ( isset( $group_selector['selected'] ) ) && ( ! empty( $group_selector['selected'] ) ) ) {
					$user_ids = learndash_get_groups_user_ids( absint( $group_selector['selected'] ) );
					if ( ! empty( $user_ids ) ) {
						if ( ! empty( $q_vars['include'] ) ) {
							$user_ids_intersect = array_intersect( $q_vars['include'], $user_ids );
							if ( ! empty( $user_ids_intersect ) ) {
								$q_vars['include'] = $user_ids_intersect;
							} else {
								$q_vars['include'] = array( 0 );
							}
						} else {
							$q_vars['include'] = $user_ids;
						}
					} else {
						$q_vars['include'] = array( 0 );
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the course_id
		 *
		 * @since 3.2.3.4
		 *
		 * @param object $q_vars   Query vars used for the table listing.
		 * @param array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_course( $q_vars, $selector = array() ) {
			// Determine if user is a basic GL (needs course restrictions).
			$is_basic_gl = false;
			if (
				! learndash_is_admin_user()
				&& learndash_is_group_leader_user( get_current_user_id() )
			) {
				$is_advanced_users   = ( 'advanced' === learndash_get_group_leader_manage_users() );
				$is_advanced_courses = ( 'advanced' === learndash_get_group_leader_manage_courses() );
				// Basic GL has neither advanced setting. Advanced-courses-only GLs are restricted
				// via post__in in listing_table_query_vars_filter_assignments, so no extra
				// per-course validation is needed here for them.
				$is_basic_gl = ( ! $is_advanced_users ) && ( ! $is_advanced_courses );
			}

			// Check for "No Course" filter.
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => 'course_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'course_id',
							'value'   => '0',
							'compare' => '=',
						),
					);

					return $q_vars;
				}
			}

			// Filter by specific course if selected.
			if ( ! empty( $selector['selected'] ) ) {
				// Basic GL: verify they have access to this course.
				if ( $is_basic_gl ) {
					$gl_course_ids = learndash_get_groups_administrators_courses( get_current_user_id() );
					$gl_course_ids = array_map( 'absint', $gl_course_ids );
					if ( ! in_array( absint( $selector['selected'] ), $gl_course_ids, true ) ) {
						$selector['selected'] = 0;
					}
				}

				if ( ! empty( $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'key'   => 'course_id',
						'value' => absint( $selector['selected'] ),
					);
				}

				return $q_vars;
			}

			// No course selected. For basic GL, author filter in listing_table_query_vars_filter_assignments
			// already restricts to users in their groups; no need to add course meta_query here.

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the lesson_id
		 *
		 * @since 3.2.3.4
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_lesson( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => 'lesson_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'lesson_id',
							'value'   => '0',
							'compare' => '=',
						),
					);
				} else {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}

					$lesson_ids      = array( absint( $selector['selected'] ) );
					$course_selector = $this->get_selector( 'course_id' );
					if ( ( $course_selector ) && ( isset( $course_selector['selected'] ) ) && ( ! empty( $course_selector['selected'] ) ) ) {
						$topics = learndash_get_topic_list( $selector['selected'], $course_selector['selected'] );
						if ( ! empty( $topics ) ) {
							$lesson_ids = array_merge( $lesson_ids, wp_list_pluck( $topics, 'ID' ) );
						}
					}

					$q_vars['meta_query'][] = array(
						'key'     => 'lesson_id',
						'compare' => 'IN',
						'value'   => $lesson_ids,
					);
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the topic_id
		 *
		 * @since 3.2.3.4
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_topic( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
					$steps_ids = learndash_course_get_steps_by_type( $selector['selected'], $this->post_type );
					if ( ! empty( $steps_ids ) ) {
						$q_vars['post__in'] = $steps_ids;
						$q_vars['orderby']  = 'post__in';
					}
				} else {

					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					} else {
						$lesson_item_found = false;
						foreach ( $q_vars['meta_query'] as $meta_idx => &$meta_item ) {
							if ( ( isset( $meta_item['key'] ) ) && ( 'lesson_id' === $meta_item['key'] ) ) {
								$lesson_item_found  = true;
								$meta_item['value'] = absint( $selector['selected'] );
								break;
							}
						}
						if ( ! $lesson_item_found ) {
							$q_vars['meta_query'][] = array(
								'key'   => 'lesson_id',
								'value' => absint( $selector['selected'] ),
							);
						}
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Show the assignment Approval Status.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     Assignment Post ID.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_assignment_approval_status( $post_id = 0, $column_meta = array() ) {
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$lesson_id = intval( get_post_meta( $post_id, 'lesson_id', true ) );
				if ( ! empty( $lesson_id ) ) {
					$approval_status_flag = learndash_is_assignment_approved_by_meta( $post_id );
					if ( 1 == $approval_status_flag ) {
						$approval_status_slug  = 'approved';
						$approval_status_label = _x( 'Approved', 'Assignment approval status', 'learndash' );
					} else {
						$approval_status_slug  = 'not_approved';
						$approval_status_label = _x( 'Not Approved', 'Assignment approval status', 'learndash' );
					}

					echo '<div class="ld-approval-status">' . sprintf(
						// translators: placeholder: Status label, Status value.
						esc_html_x( '%1$s: %2$s', 'placeholder: Status label, Status value', 'learndash' ),
						'<span class="learndash-listing-row-field-label">' . esc_html__( 'Status', 'learndash' ) . '</span>',
						esc_html( $approval_status_label )
					) . '</div>';

					echo '<div class="ld-approval-points">';
					if ( learndash_assignment_is_points_enabled( $post_id ) ) {
						$max_points = 0;
						$max_points = learndash_get_setting( $lesson_id, 'lesson_assignment_points_amount' );

						$current_points = get_post_meta( $post_id, 'points', true );
						if ( 1 != $approval_status_flag ) {
							$points_label = '<label class="learndash-listing-row-field-label" for="assignment_points_' . absint( $post_id ) . '">' . esc_html__( 'Points', 'learndash' ) . '</label>';
							$points_input = '<input id="assignment_points_' . absint( $post_id ) . '" class="small-text learndash-award-points" type="number" value="' . absint( $current_points ) . '" max="' . absint( $max_points ) . '" min="0" step="1" name="assignment_points[' . absint( $post_id ) . ']" />';
							echo sprintf(
								// translators: placeholders: Points label, points input, maximum points.
								esc_html_x( '%1$s: %2$s / %3$d', 'placeholders: Points label, points input, maximum points', 'learndash' ),
								$points_label, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$points_input, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								absint( $max_points )
							);
						} else {
							$points_field = '<span class="learndash-listing-row-field-label">' . esc_html__( 'Points', 'learndash' ) . '</span>';
							echo sprintf(
								// translators: placeholders: Points label, current points, maximum points.
								esc_html_x( '%1$s: %2$d / %3$d', 'placeholders: Points label, points input, maximum points', 'learndash' ),
								$points_field, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								absint( $current_points ),
								absint( $max_points )
							);
						}
					} else {
						echo sprintf(
							// translators: placeholder: Points label.
							esc_html_x( '%s: Not Enabled', 'placeholder: Points label', 'learndash' ),
							'<span class="learndash-listing-row-field-label">' . esc_html__( 'Points', 'learndash' ) . '</span>'
						);
					}
					echo '</div>';

					if ( 1 != $approval_status_flag ) {
						?>
						<div class="ld-approval-action">
							<button id="assignment_approve_<?php echo absint( $post_id ); ?>" class="small assignment_approve_single"><?php esc_html_e( 'approve', 'learndash' ); ?></button>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Adds a 'Approve' option next to certain selects on assignment edit screen in admin.
		 *
		 * Fires on `admin_footer` hook.
		 *
		 * @since 3.2.3
		 *
		 * @global WP_Post $post Global post object.
		 *
		 * @todo  check if needed, jQuery selector seems incorrect
		 */
		public function assignment_bulk_actions() {
			global $post;

			if ( ( ! empty( $post->post_type ) ) && ( learndash_get_post_type_slug( 'assignment' ) === $post->post_type ) ) {
				$approve_text = esc_html__( 'Approve', 'learndash' );
				?>
					<script type="text/javascript">
						jQuery( function() {
							jQuery('<option>').val('approve_assignment').text('<?php echo esc_attr( $approve_text ); ?>').appendTo("select[name='action']");
							jQuery('<option>').val('approve_assignment').text('<?php echo esc_attr( $approve_text ); ?>').appendTo("select[name='action2']");
						});
					</script>
				<?php
			}
		}

		/**
		 * Handles approval of assignments in bulk.
		 *
		 * @since 3.2.3
		 */
		protected function assignment_bulk_actions_approve() {

			if ( ( ! isset( $_REQUEST['ld-listing-nonce'] ) ) || ( empty( $_REQUEST['ld-listing-nonce'] ) ) || ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ld-listing-nonce'] ) ), get_called_class() ) ) ) {
				return;
			}

			if ( ( ! isset( $_REQUEST['post'] ) ) || ( empty( $_REQUEST['post'] ) ) || ( ! is_array( $_REQUEST['post'] ) ) ) {
				return;
			}

			if ( ( ! isset( $_REQUEST['post_type'] ) ) || ( learndash_get_post_type_slug( 'assignment' ) !== $_REQUEST['post_type'] ) ) {
				return;
			}

			$action = '';
			if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );

			} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );

			} elseif ( ( isset( $_REQUEST['ld_action'] ) ) && ( 'approve_assignment' === $_REQUEST['ld_action'] ) ) {
				$action = 'approve_assignment';
			}

			if ( 'approve_assignment' === $action ) {
				if ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) ) { // @phpstan-ignore-line
					if ( ! is_array( $_REQUEST['post'] ) ) {
						$assignments = array( $_REQUEST['post'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					} else {
						$assignments = $_REQUEST['post']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					}

					foreach ( $assignments as $assignment_id ) {

						$assignment_post = get_post( $assignment_id );
						if ( ( ! empty( $assignment_post ) ) && ( is_a( $assignment_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'assignment' ) === $assignment_post->post_type ) ) {

							$user_id   = absint( $assignment_post->post_author );
							$lesson_id = get_post_meta( $assignment_post->ID, 'lesson_id', true );

							if ( learndash_assignment_is_points_enabled( $assignment_id ) === true ) {

								if ( ( isset( $_REQUEST['assignment_points'] ) ) && ( isset( $_REQUEST['assignment_points'][ $assignment_id ] ) ) ) {
									$assignment_points = absint( $_REQUEST['assignment_points'][ $assignment_id ] );

									$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
									if ( ! empty( $assignment_settings_id ) ) {
										$max_points = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
									} else {
										$max_points = 0;
									}

									// Double check the assigned points is NOT larger than max points.
									if ( $assignment_points > $max_points ) {
										$assignment_points = $max_points;
									}

									update_post_meta( $assignment_id, 'points', $assignment_points );
								}
							}

							learndash_approve_assignment( $user_id, $lesson_id, $assignment_id );
						}
					}
				}
			}
		}

		/**
		 * This function fill filter the table listing items based on filters selected.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function filter_by_approval_status( $q_vars, $selector ) {

			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}

				if ( 'approved' === $selector['selected'] ) {
					$q_vars['meta_query'][] = array(
						'key'   => 'approval_status',
						'value' => 1,
					);
				} elseif ( 'not_approved' === $selector['selected'] ) {
					$q_vars['meta_query'][] = array(
						'key'     => 'approval_status',
						'compare' => 'NOT EXISTS',
					);
				}
			}

			return $q_vars;
		}

		/**
		 * Hides the list table views for non admin users.
		 *
		 * Fires on `views_edit-sfwd-essays` and `views_edit-sfwd-assignment` hook.
		 *
		 * @since 3.2.3
		 *
		 * @param array $views Optional. An array of available list table views. Default empty array.
		 */
		public function edit_list_table_views( $views = array() ) {
			if ( ! learndash_is_admin_user() ) {
				$views = array();
			}

			return $views;
		}

		/**
		 * Modify a view link, so it opens a modal window.
		 *
		 * @since 4.1.0
		 *
		 * @param array   $row_actions Existing Row actions for an assignment.
		 * @param WP_Post $post        Assignment post for the current row.
		 *
		 * @return array $row_actions
		 */
		public function post_row_actions( $row_actions = array(), $post = null ): array {
			if ( ! $post ) {
				return $row_actions;
			}

			$row_actions = parent::post_row_actions( $row_actions, $post );

			$file_name = get_post_meta( $post->ID, 'file_name', true );

			// Path is not accessible. We need to grab the download URL.
			$file_url = learndash_assignment_get_download_url( $post->ID );

			// Quick view.

			$file_is_image = in_array(
				strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ),
				array( 'jpg', 'jpeg', 'png', 'gif' ),
				true
			);

			if ( $file_is_image ) {
				$view_label = __( 'Quick View', 'learndash' );

				$row_actions['quick_view'] = sprintf(
					'<a class="view-learndash-assignment" href="%s" data-title="%s" aria-label="%s">%s</a>',
					esc_url( $file_url ),
					esc_attr( $file_name ),
					esc_attr( $view_label ),
					esc_html( $view_label )
				);
			}

			// Download.

			if ( ! empty( $file_url ) ) {
				$row_actions['download'] = sprintf(
					'<a download href="%s" target="_blank">%s</a>',
					esc_url( $file_url ),
					esc_html__( 'Download', 'learndash' )
				);
			}

			return $row_actions;
		}

		/**
		 * Add modal for a view action.
		 *
		 * @since 4.1.0
		 */
		public function add_view_modal() {
			?>
			<div id='learndash-admin-table-modal' style="display: none"></div>
			<?php
		}

		// End of functions.
	}
}
new Learndash_Admin_Assignments_Listing();
