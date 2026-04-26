<?php
/**
 * LearnDash ProPanel Filtering: Course.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'LearnDash_ProPanel_Reporting_Filter_Courses' )
	&& class_exists( 'LearnDash_ProPanel_Filtering' )
) {
	/**
	 * ProPanel reporting filter widget for courses.
	 *
	 * @since 4.17.0
	 */
	class LearnDash_ProPanel_Reporting_Filter_Courses extends LearnDash_ProPanel_Filtering {
		/**
		 * Registers filters and template paths.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->filter_key                = 'courses';
			$this->filter_search_placeholder = __( 'Search Users', 'learndash' );
			$this->filter_template_table     = 'reporting-filters/courses/ld-propanel-reporting-filter-course-table.php';
			$this->filter_template_row       = 'reporting-filters/courses/ld-propanel-reporting-filter-course-row.php';

			add_filter( 'ld_propanel_filtering_register_filters', array( $this, 'filter_register' ), 20 );

			add_filter( 'ld_propanel_reporting_post_args', array( $this, 'filter_post_args' ), 20, 2 );
			add_filter( 'ld_propanel_reporting_activity_args', array( $this, 'filter_activity_args' ), 20, 3 );
		}

		/**
		 * Normalizes course filter values from the request.
		 *
		 * @since 4.17.0
		 *
		 * @param array<string, mixed> $post_args Post arguments.
		 * @param array<string, mixed> $_get      Request GET data.
		 * @return array<string, mixed>
		 */
		public function filter_post_args( $post_args = array(), $_get = array() ) {
			if (
				! isset( $post_args['filters'] )
				|| ! is_array( $post_args['filters'] )
			) {
				$post_args['filters'] = array();
			}

			$req_filters = ( isset( $_get['filters'] ) && is_array( $_get['filters'] ) ) ? $_get['filters'] : array();
			$key         = $this->filter_key;
			if (
				isset( $req_filters[ $key ] )
				&& ! empty( $req_filters[ $key ] )
			) {
				$raw = $req_filters[ $key ];
				if ( is_string( $raw ) ) {
					$ids = explode( ',', $raw );
				} elseif ( is_array( $raw ) ) {
					$ids = $raw;
				} else {
					$ids = array( $raw );
				}

				$int_ids = array();
				foreach ( $ids as $id ) {
					$int_ids[] = (int) $id;
				}
				$post_args['filters'][ $key ] = $int_ids;
			}

			return $post_args;
		}

		/**
		 * Merges selected courses into activity query arguments.
		 *
		 * @since 4.17.0
		 *
		 * @param array<string, mixed> $activity_args Activity query arguments.
		 * @param array<string, mixed> $post_data     Posted filter data.
		 * @param array<string, mixed> $_get          Request GET data.
		 * @return array<string, mixed>
		 */
		public function filter_activity_args( $activity_args = array(), $post_data = array(), $_get = array() ) {
			$post_filters = ( isset( $post_data['filters'] ) && is_array( $post_data['filters'] ) ) ? $post_data['filters'] : array();
			$course_key   = $this->filter_key;

			if ( ! empty( $activity_args ) ) {
				if (
					isset( $post_filters[ $course_key ] )
					&& ! empty( $post_filters[ $course_key ] )
				) {
					if (
						! isset( $activity_args['post_ids'] )
						|| empty( $activity_args['post_ids'] )
					) {
						$activity_args['post_ids'] = $post_filters[ $course_key ];
					}
				} elseif (
					! isset( $activity_args['post_ids'] )
					|| empty( $activity_args['post_ids'] )
				) {
					$filtered_user_ids = array();
					if (
						isset( $post_filters['users'] )
						&& is_array( $post_filters['users'] )
					) {
						foreach ( $post_filters['users'] as $uid ) {
							$filtered_user_ids[] = (int) $uid;
						}
					}

					if ( learndash_is_admin_user( get_current_user_id() ) ) {
						if (
							empty( $activity_args['post_ids'] )
							&& ! empty( $filtered_user_ids )
						) {
							$users_course_ids = array();
							foreach ( $filtered_user_ids as $user_id ) {
								$course_ids = learndash_user_get_enrolled_courses( $user_id, true );
								if ( ! empty( $course_ids ) ) {
									$users_course_ids = array_merge( $users_course_ids, $course_ids );
								}
							}
							if ( ! empty( $users_course_ids ) ) {
								$activity_args['post_ids'] = $users_course_ids;
							} else {
								$activity_args = array();
							}
						}
					} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
						// Ensure the group leader may access courses for the selected context.
						$group_ids  = learndash_get_administrators_group_ids( get_current_user_id() );
						$course_ids = array();
						if ( ! empty( $group_ids ) ) {
							// When users are filtered, restrict courses to those users' groups.
							if ( ! empty( $filtered_user_ids ) ) {
								$user_group_ids = array();
								foreach ( $filtered_user_ids as $user_id ) {
									$user_group_ids = array_merge( $user_group_ids, learndash_get_users_group_ids( $user_id ) );
								}

								if ( ! empty( $user_group_ids ) ) {
									$group_ids = array_intersect( $group_ids, $user_group_ids );
								} else {
									$group_ids = array();
								}
							}
							if ( ! empty( $group_ids ) ) {
								foreach ( $group_ids as $group_id ) {
									$group_course_ids = learndash_group_enrolled_courses( $group_id );
									if ( ! empty( $group_course_ids ) ) {
										$course_ids = array_merge( $course_ids, $group_course_ids );
									}
								}
							}

							if ( ! empty( $course_ids ) ) {
								$activity_args['post_ids'] = $course_ids;
							} else {
								$activity_args = array();
							}
						}
					} else {
						$course_ids = learndash_user_get_enrolled_courses( get_current_user_id() );
						if ( ! empty( $course_ids ) ) {
							$activity_args['post_ids'] = $course_ids;
						} else {
							$activity_args = array();
						}
					}
				}
			}

			return $activity_args;
		}

		/**
		 * Returns markup for the course filter select control.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function filter_display() {
			return '<select class="filter-courses select2" data-ajax--cache="true" data-allow-clear="true" data-placeholder="' . sprintf(
				// Translators: placeholder is the courses label.
				esc_html_x( 'All %s', 'All Courses', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'courses' )
			) . '"><option value="">' . sprintf(
				// Translators: placeholder is the courses label.
				esc_html_x( 'All %s', 'All Courses', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'courses' )
			) . '</option></select>';
		}

		/**
		 * Courses search.
		 *
		 * @since 4.17.0
		 * @since 4.25.1 excluded open courses from the list of courses because we don't support open course reporting.
		 *
		 * @return array<string, mixed> The courses data.
		 */
		public function filter_search(): array {
			$courses_data = array(
				'total' => 0,
				'items' => array(),
			);

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified by ProPanel filters_search handler.
			$page = isset( $_GET['page'] ) ? absint( wp_unslash( $_GET['page'] ) ) : 1;
			if ( $page < 1 ) {
				$page = 1;
			}

			$course_query_args = array(
				'post_type'      => 'sfwd-courses',
				'post_status'    => 'publish',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'posts_per_page' => 10,
				'offset'         => ( $page - 1 ) * 10,
				'paged'          => $page,
			);

			if (
				isset( $_GET['search'] )
				&& is_string( $_GET['search'] )
				&& $_GET['search'] !== ''
			) {
				$course_query_args['s'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$post_filters = ( isset( $this->post_data['filters'] ) && is_array( $this->post_data['filters'] ) )
				? $this->post_data['filters']
				: array();

			$filter_groups = array();
			if (
				isset( $post_filters['groups'] )
				&& is_array( $post_filters['groups'] )
			) {
				foreach ( $post_filters['groups'] as $gid ) {
					$filter_groups[] = (int) $gid;
				}
			}

			$filter_users_cf = array();
			if (
				isset( $post_filters['users'] )
				&& is_array( $post_filters['users'] )
			) {
				foreach ( $post_filters['users'] as $uid ) {
					$filter_users_cf[] = (int) $uid;
				}
			}

			if ( learndash_is_admin_user( get_current_user_id() ) ) {
				$groups_course_ids = array();
				if ( ! empty( $filter_groups ) ) {
					foreach ( $filter_groups as $group_id ) {
						$course_ids = learndash_group_enrolled_courses( $group_id );
						if ( ! empty( $course_ids ) ) {
							$groups_course_ids = array_merge( $groups_course_ids, $course_ids );
						}
					}
					if ( empty( $groups_course_ids ) ) {
						$course_query_args = array();
					}
				}

				$users_course_ids = array();
				if ( ! empty( $filter_users_cf ) ) {
					foreach ( $filter_users_cf as $user_id ) {
						$course_ids = learndash_user_get_enrolled_courses( $user_id );
						if ( ! empty( $course_ids ) ) {
							$users_course_ids = array_merge( $users_course_ids, $course_ids );
						}
						if ( empty( $users_course_ids ) ) {
							$course_query_args = array();
						}
					}
				}

				if (
					! empty( $groups_course_ids )
					&& ! empty( $users_course_ids )
				) {
					$course_query_args['post__in'] = array_intersect( $groups_course_ids, $users_course_ids );
				} elseif ( ! empty( $groups_course_ids ) ) {
					$course_query_args['post__in'] = $groups_course_ids;
				} elseif ( ! empty( $users_course_ids ) ) {
					$course_query_args['post__in'] = $users_course_ids;
				}
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				$groups_course_ids = array();
				if ( ! empty( $filter_groups ) ) {
					foreach ( $filter_groups as $group_id ) {
						$course_ids = learndash_group_enrolled_courses( $group_id );
						if ( ! empty( $course_ids ) ) {
							$groups_course_ids = array_merge( $groups_course_ids, $course_ids );
						}
					}
					if ( empty( $groups_course_ids ) ) {
						$course_query_args = array();
					}
				}

				$users_course_ids = array();
				if ( ! empty( $filter_users_cf ) ) {
					foreach ( $filter_users_cf as $user_id ) {
						$course_ids = learndash_user_get_enrolled_courses( $user_id );
						if ( ! empty( $course_ids ) ) {
							$users_course_ids = array_merge( $users_course_ids, $course_ids );
						}
					}
					if ( empty( $users_course_ids ) ) {
						$course_query_args = array();
					}
				}

				if (
					! empty( $groups_course_ids )
					&& ! empty( $users_course_ids )
				) {
					$course_query_args['post__in'] = array_intersect( $groups_course_ids, $users_course_ids );
				} elseif ( ! empty( $groups_course_ids ) ) {
					$course_query_args['post__in'] = $groups_course_ids;
				} elseif ( ! empty( $users_course_ids ) ) {
					$course_query_args['post__in'] = $users_course_ids;
				} else {
					// If no group or user filter, list every course this group leader may manage.
					$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
					if ( ! empty( $group_ids ) ) {
						$course_ids = learndash_get_groups_courses_ids( get_current_user_id(), $group_ids );
						if ( ! empty( $course_ids ) ) {
							$course_query_args['post__in'] = $course_ids;
						} else {
							$course_query_args = array();
						}
					} else {
						$course_query_args = array();
					}
				}
			} else {
				$user_course_ids = learndash_user_get_enrolled_courses( get_current_user_id() );
				if ( ! empty( $user_course_ids ) ) {
					$course_query_args['post__in'] = $user_course_ids;
				} else {
					$course_query_args = array();
				}
			}

			// Remove open courses from the list of courses. Added in v4.25.1.
			$open_course_ids = learndash_get_open_courses();
			if ( ! empty( $open_course_ids ) ) {
				$course_query_args['post__not_in'] = $open_course_ids;
			}

			if ( ! empty( $course_query_args ) ) {
				$course_query = new WP_Query( $course_query_args );
				if ( $course_query->have_posts() ) {
					$courses_data['total'] = intval( $course_query->found_posts );

					foreach ( $course_query->posts as $course ) {
						$courses_data['items'][] = array(
							'id'   => $course->ID,
							'text' => wp_strip_all_tags( $course->post_title ),
						);
					}
				}
			}

			/** This filter is documented in includes/reports/templates/reporting-filters/groups/ld-propanel-reporting-filter-group.php. */
			return apply_filters( 'ld_propanel_filter_search', $courses_data, $this->filter_key, $course_query_args );
		}

		/**
		 * Renders the filter results table for courses.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function filter_build_table() {
			$this->filter_table_headers();

			ob_start();
			include ld_propanel_get_template( $this->filter_template_table );
			$output = ob_get_clean();

			return is_string( $output ) ? $output : '';
		}

		/**
		 * Sets column headers based on container type.
		 *
		 * @since 4.17.0
		 *
		 * @return array<int|string, string>
		 */
		public function filter_table_headers() {
			if ( 'widget' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'checkbox' => __( 'Checkbox', 'learndash' ),
					'user'     => __( 'User', 'learndash' ),
					'progress' => __( 'Progress', 'learndash' ),
				);
			} elseif ( 'full' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'checkbox'    => __( 'Checkbox', 'learndash' ),
					'user_id'     => __( 'User ID', 'learndash' ),
					'user'        => __( 'User', 'learndash' ),
					'progress'    => __( 'Progress', 'learndash' ),
					'last_update' => __( 'Completed On', 'learndash' ),
				);
			} elseif ( 'shortcode' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'user'     => __( 'User', 'learndash' ),
					'progress' => __( 'Progress', 'learndash' ),
				);
			}

			return apply_filters( 'ld-propanel-reporting-headers', $this->filter_headers, $this->filter_key ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Legacy hook.
		}

		/**
		 * Builds HTML rows for a course activity result set.
		 *
		 * @since 4.17.0
		 *
		 * @param int $course_id Course post ID.
		 * @return array<string, mixed>
		 */
		public function filter_result_rows( $course_id ) {
			// Default response if queries return nothing.
			$response = array(
				'total_rows'  => 0,
				'rows_html'   => '',
				'total_users' => 0,
			);

			$this->filter_table_headers();

			$activity_query_defaults = array(
				'post_types'      => 'sfwd-courses',
				'activity_types'  => 'course',
				'activity_status' => '',
				'orderby_order'   => 'users.display_name, posts.post_title',
			);

			$this->activity_query_args = wp_parse_args( $this->activity_query_args, $activity_query_defaults );

			$this->activity_query_args = ld_propanel_load_activity_query_args( $this->activity_query_args, $this->post_data );

			if ( ! empty( $this->activity_query_args ) ) {
				$this->activity_query_args = ld_propanel_adjust_admin_users( $this->activity_query_args );
				$this->activity_query_args = ld_propanel_convert_fewer_users( $this->activity_query_args );

				$activities = learndash_reports_get_activity( $this->activity_query_args );

				if (
					isset( $activities['results'] )
					&& ! empty( $activities['results'] )
				) {
					if (
						isset( $activities['pager'] )
						&& ! empty( $activities['pager'] )
					) {
						$response['total_rows']  = $activities['pager']['total_items'];
						$response['total_users'] = $activities['pager']['total_items'];

						$activities['pager']['current_page'] = $this->activity_query_args['paged'];
						$response['pager']                   = $activities['pager'];
					}

					foreach ( $activities['results'] as $activity ) {
						$row      = array();
						$row_html = '<tr>';

						foreach ( $this->filter_headers as $header_key => $header_label ) {
							ob_start();
							include ld_propanel_get_template( $this->filter_template_row );
							$row       = ob_get_clean();
							$row_html .= '<td class="' . apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ) . '">' . $row . '</td>'; // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Legacy hook.
						}
						$row_html              .= '</tr>';
						$response['rows_html'] .= $row_html;
					}
				}
			}

			if ( empty( $response['rows_html'] ) ) {
				ob_start();
				include ld_propanel_get_template( 'ld-propanel-reporting-no-results.php' );
				$response['rows_html'] = ob_get_clean();
			}

			if ( ! empty( $response['user_ids'] ) ) {
				$response['user_ids'] = array_values( $response['user_ids'] );
			}

			return $response;
		}
	}
}

add_action(
	'learndash_propanel_filtering_init',
	function () {
		new LearnDash_ProPanel_Reporting_Filter_Courses();
	}
);
