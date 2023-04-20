<?php
/**
 * LearnDash Assignments (sfwd-assignment) Posts Listing.
 *
 * @since 3.2.3
 * @package LearnDash\Assignment\Listing
 */

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
		 * @since 3.2.3
		 *
		 * @param array  $q_vars    Array of query vars.
		 * @param string $post_type Post Type being displayed.
		 * @param array  $query     Main Query.
		 */
		public function listing_table_query_vars_filter_assignments( $q_vars, $post_type, $query ) {
			if ( $post_type === $this->post_type ) {

				$author_selector = $this->get_selector( 'author' );
				$group_selector  = $this->get_selector( 'group_id' );
				if ( ( isset( $author_selector['selected'] ) ) && ( ! empty( $author_selector['selected'] ) ) ) {
					if ( learndash_is_admin_user() ) {
						$q_vars['author__in'] = array( $author_selector['selected'] );
					} elseif ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
						if ( learndash_is_group_leader_of_user( get_current_user_id(), $author_selector['selected'] ) ) {
							$q_vars['author__in'] = array( $author_selector['selected'] );
						} else {
							$q_vars['author__in'] = array( 0 );
						}
					} else {
						$q_vars['author__in'] = array( get_current_user_id() );
					}
				} elseif ( ( isset( $group_selector['selected'] ) ) && ( ! empty( $group_selector['selected'] ) ) ) {
					$user_ids = learndash_get_groups_user_ids( absint( $group_selector['selected'] ) );
					if ( ! empty( $user_ids ) ) {
						if ( ! empty( $q_vars['author__in'] ) ) {
							$user_ids_intersect = array_intersect( $q_vars['author__in'], $user_ids );
							if ( ! empty( $user_ids_intersect ) ) {
								$q_vars['author__in'] = $user_ids_intersect;
							} else {
								$q_vars['author__in'] = array( 0 );
							}
						} else {
							$q_vars['author__in'] = $user_ids;
						}
					} else {
						$q_vars['author__in'] = array( 0 );
					}
				} else {
					if ( ! learndash_is_admin_user() ) {
						if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
							$gl_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
							if ( ! empty( $gl_user_ids ) ) {
								$q_vars['author__in'] = $gl_user_ids;
							} else {
								$q_vars['author__in'] = array( 0 );
							}
						} else {
							$q_vars['author__in'] = get_current_user_id();
						}
					}
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
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_course( $q_vars, $selector = array() ) {
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
				} else {
					if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
						$gl_course_ids = learndash_get_groups_administrators_courses( get_current_user_id() );
						$gl_course_ids = array_map( 'absint', $gl_course_ids );
						if ( ! in_array( $selector['selected'], $gl_course_ids, true ) ) {
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
				}
			} elseif ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
				$gl_course_ids = learndash_get_groups_administrators_courses( get_current_user_id() );
				$gl_course_ids = array_map( 'absint', $gl_course_ids );
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}

				if ( ! empty( $gl_course_ids ) ) {
					$q_vars['meta_query'][] = array(
						'key'     => 'course_id',
						'compare' => 'IN',
						'value'   => $gl_course_ids,
					);
				} else {
					$q_vars['meta_query'][] = array(
						'key'     => 'course_id',
						'compare' => 'IN',
						'value'   => array( 0 ),
					);
				}
			}

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
		 * @param WP_Post $post Assignment post for the current row.
		 *
		 * @return array $row_actions
		 */
		public function post_row_actions( $row_actions = array(), $post = null ): array {
			$row_actions = parent::post_row_actions( $row_actions, $post );

			$file_url = get_post_meta( $post->ID, 'file_link', true );

			// Quick view.

			$file_is_image = in_array(
				strtolower( pathinfo( $file_url, PATHINFO_EXTENSION ) ),
				array( 'jpg', 'jpeg', 'png', 'gif' ),
				true
			);

			if ( $file_is_image ) {
				$view_label = __( 'Quick View', 'learndash' );

				$row_actions['quick_view'] = sprintf(
					'<a class="view-learndash-assignment" href="%s" data-title="%s" aria-label="%s">%s</a>',
					esc_url( $file_url ),
					esc_attr( get_post_meta( $post->ID, 'file_name', true ) ),
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
