<?php
/**
 * LearnDash ProPanel Filtering: Group.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'LearnDash_ProPanel_Reporting_Filter_Groups' )
	&& class_exists( 'LearnDash_ProPanel_Filtering' )
) {
	/**
	 * ProPanel reporting filter widget for groups.
	 *
	 * @since 4.17.0
	 */
	class LearnDash_ProPanel_Reporting_Filter_Groups extends LearnDash_ProPanel_Filtering {
		/**
		 * Registers filters and template paths.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->filter_key                = 'groups';
			$this->filter_search_placeholder = __( 'Search Groups', 'learndash' );

			// Path relative to the plugin templates directory.
			$this->filter_template_table = 'reporting-filters/groups/ld-propanel-reporting-filter-group-table.php';
			$this->filter_template_row   = 'reporting-filters/groups/ld-propanel-reporting-filter-group-row.php';

			add_filter( 'ld_propanel_filtering_register_filters', array( $this, 'filter_register' ), 10 );

			add_filter( 'ld_propanel_reporting_post_args', array( $this, 'filter_post_args' ), 10, 2 );
			add_filter( 'ld_propanel_reporting_activity_args', array( $this, 'filter_activity_args' ), 10, 3 );
		}

		/**
		 * Normalizes group filter values from the request.
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

			$req_filters = ( isset( $_get['filters'] ) && is_array( $_get['filters'] ) )
				? $_get['filters']
				: array();
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
		 * Restricts activity query to courses and users for selected groups.
		 *
		 * @since 4.17.0
		 *
		 * @param array<string, mixed> $activity_args Activity query arguments.
		 * @param array<string, mixed> $post_data     Posted filter data.
		 * @param array<string, mixed> $_get          Request GET data.
		 * @return array<string, mixed>
		 */
		public function filter_activity_args( $activity_args = array(), $post_data = array(), $_get = array() ) {
			$post_filters = ( isset( $post_data['filters'] ) && is_array( $post_data['filters'] ) )
				? $post_data['filters']
				: array();
			$group_key    = $this->filter_key;

			if ( ! empty( $activity_args ) ) {
				if (
					isset( $post_filters[ $group_key ] )
					&& is_array( $post_filters[ $group_key ] )
					&& ! empty( $post_filters[ $group_key ] )
				) {
					$selected_group_ids = array();
					foreach ( $post_filters[ $group_key ] as $gid ) {
						$selected_group_ids[] = (int) $gid;
					}

					if (
						! isset( $activity_args['post_ids'] )
						|| empty( $activity_args['post_ids'] )
					) {
						$group_course_ids = array();

						foreach ( $selected_group_ids as $group_id ) {
							$course_ids = learndash_group_enrolled_courses( $group_id );
							if ( ! empty( $course_ids ) ) {
								$group_course_ids = array_merge( $group_course_ids, $course_ids );
							}
						}

						if ( ! empty( $group_course_ids ) ) {
							if (
								isset( $post_filters['courses'] )
								&& is_array( $post_filters['courses'] )
								&& ! empty( $post_filters['courses'] )
							) {
								$course_ids_filtered = array();
								foreach ( $post_filters['courses'] as $cid ) {
									$course_ids_filtered[] = (int) $cid;
								}
								$activity_args['post_ids'] = array_intersect( $group_course_ids, $course_ids_filtered );
							} else {
								$activity_args['post_ids'] = $group_course_ids;
							}
						} else {
							// If the group has no courses, abort and return.
							$activity_args = array();
							return $activity_args;
						}
					}

					if (
						! isset( $activity_args['user_ids'] )
						|| empty( $activity_args['user_ids'] )
					) {
						if (
							isset( $post_filters['users'] )
							&& is_array( $post_filters['users'] )
							&& ! empty( $post_filters['users'] )
						) {
							$user_ids_filtered = array();
							foreach ( $post_filters['users'] as $uid ) {
								$user_ids_filtered[] = (int) $uid;
							}
							$activity_args['user_ids']        = $user_ids_filtered;
							$activity_args['user_ids_action'] = 'IN';
						} else {
							$group_user_ids = array();

							foreach ( $selected_group_ids as $group_id ) {
								$user_ids = learndash_get_groups_user_ids( $group_id );
								if ( ! empty( $user_ids ) ) {
									$group_user_ids = array_merge( $group_user_ids, $user_ids );
								}
							}

							if ( ! empty( $group_user_ids ) ) {
								$activity_args['user_ids']        = $group_user_ids;
								$activity_args['user_ids_action'] = 'IN';
							} else {
								// If the group has no users, abort and return.
								$activity_args = array();
								return $activity_args;
							}
						}
					}
				}
			}

			return $activity_args;
		}

		/**
		 * Returns markup for the group filter select control.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function filter_display() {
			if ( learndash_is_admin_user( get_current_user_id() ) ) {
				if ( ! ld_propanel_count_post_type( 'groups' ) ) {
					return '';
				}
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				$leader_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( empty( $leader_group_ids ) ) {
					return '';
				}
			} else {
				$use_group_ids = learndash_get_users_group_ids( get_current_user_id() );
				if ( empty( $use_group_ids ) ) {
					return '';
				}
			}

			return '<select class="filter-groups select2" data-ajax--cache="true" data-allow-clear="true" data-placeholder="' . esc_html__( 'All Groups', 'learndash' ) . '"><option value="">' . esc_html__( 'All Groups', 'learndash' ) . '</option></select>';
		}

		/**
		 * Groups search for Select2.
		/**
		 * Groups search for Select2.
		 *
		 * @return array<string, mixed>
		 */
		public function filter_search() {
			$groups_data = array(
				'total' => 0,
				'items' => array(),
			);

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified by ProPanel filters_search handler.
			$page = isset( $_GET['page'] ) ? absint( wp_unslash( $_GET['page'] ) ) : 1;
			if ( $page < 1 ) {
				$page = 1;
			}

			$group_query_args = array(
				'post_type'      => 'groups',
				'post_status'    => 'publish',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'posts_per_page' => 10,
				'paged'          => $page,
			);

			if (
				isset( $_GET['search'] )
				&& is_string( $_GET['search'] )
				&& $_GET['search'] !== ''
			) {
				$group_query_args['s'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$post_filters     = array();
			$filter_courses   = array();
			$filter_users_grp = array();

			if (
				isset( $this->post_data['filters'] )
				&& is_array( $this->post_data['filters'] )
			) {
				$post_filters = $this->post_data['filters'];
			}

			if (
				isset( $post_filters['courses'] )
				&& is_array( $post_filters['courses'] )
			) {
				foreach ( $post_filters['courses'] as $cid ) {
					$filter_courses[] = (int) $cid;
				}
			}

			if (
				isset( $post_filters['users'] )
				&& is_array( $post_filters['users'] )
			) {
				foreach ( $post_filters['users'] as $uid ) {
					$filter_users_grp[] = (int) $uid;
				}
			}

			if ( learndash_is_admin_user( get_current_user_id() ) ) {
				$courses_group_ids = array();
				if ( ! empty( $filter_courses ) ) {
					foreach ( $filter_courses as $course_id ) {
						$group_ids = learndash_get_course_groups( $course_id );
						if ( ! empty( $group_ids ) ) {
							$courses_group_ids = array_merge( $courses_group_ids, $group_ids );
						}
					}
				}

				$users_group_ids = array();
				if ( ! empty( $filter_users_grp ) ) {
					foreach ( $filter_users_grp as $user_id ) {
						$group_ids = learndash_get_users_group_ids( $user_id, true );
						if ( ! empty( $group_ids ) ) {
							$users_group_ids = array_merge( $users_group_ids, $group_ids );
						}
					}
				}

				if (
					! empty( $filter_courses )
					&& ! empty( $filter_users_grp )
				) {
					if (
						! empty( $courses_group_ids )
						&& ! empty( $users_group_ids )
					) {
						$group_query_args['post__in'] = array_intersect( $courses_group_ids, $users_group_ids );
					} else {
						$group_query_args = array();
					}
				} elseif ( ! empty( $filter_courses ) ) {
					if ( ! empty( $courses_group_ids ) ) {
						$group_query_args['post__in'] = $courses_group_ids;
					} else {
						$group_query_args = array();
					}
				} elseif ( ! empty( $filter_users_grp ) ) {
					if ( ! empty( $users_group_ids ) ) {
						$group_query_args['post__in'] = $users_group_ids;
					} else {
						$group_query_args = array();
					}
				}
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				$admin_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( ! empty( $admin_group_ids ) ) {
					$search_group_ids = array();

					if ( ! empty( $filter_courses ) ) {
						foreach ( $filter_courses as $course_id ) {
							$group_ids = learndash_get_course_groups( $course_id );
							if ( ! empty( $group_ids ) ) {
								$group_ids = array_intersect( $group_ids, $admin_group_ids );
								if ( ! empty( $group_ids ) ) {
									$search_group_ids = array_merge( $search_group_ids, $group_ids );
								}
							}
						}
					}

					if ( ! empty( $filter_users_grp ) ) {
						foreach ( $filter_users_grp as $user_id ) {
							$group_ids = learndash_get_users_group_ids( $user_id, true );
							if ( ! empty( $group_ids ) ) {
								$search_group_ids = array_merge( $search_group_ids, $group_ids );
							}
						}
					}

					if ( ! empty( $search_group_ids ) ) {
						$group_query_args['post__in'] = $search_group_ids;
					} else {
						$group_query_args['post__in'] = $admin_group_ids;
					}
				} else {
					// Group leader with no groups: return no results.
					$group_query_args = array();
				}
			} else {
				$user_group_ids = learndash_get_users_group_ids( get_current_user_id(), true );
				if ( ! empty( $user_group_ids ) ) {
					if ( ! empty( $filter_courses ) ) {
						$course_group_ids = array();
						foreach ( $filter_courses as $course_id ) {
							$group_ids = learndash_get_course_groups( $course_id );
							if ( ! empty( $group_ids ) ) {
								$course_group_ids = array_merge( $course_group_ids, $group_ids );
							}
						}

						if ( ! empty( $course_group_ids ) ) {
							$user_group_ids = array_intersect( $user_group_ids, $course_group_ids );
						}
					}

					if ( ! empty( $user_group_ids ) ) {
						$group_query_args['post__in'] = $user_group_ids;
					} else {
						$group_query_args = array();
					}
				} else {
					// User with no groups: return no results.
					$group_query_args = array();
				}
			}

			if ( ! empty( $group_query_args ) ) {
				$group_query = new WP_Query( $group_query_args );
				if ( $group_query->have_posts() ) {
					$groups_data['total'] = intval( $group_query->found_posts );

					foreach ( $group_query->posts as $group ) {
						$groups_data['items'][] = array(
							'id'   => $group->ID,
							'text' => wp_strip_all_tags( $group->post_title ),
						);
					}
				}
			}

			/** This filter is documented elsewhere. */
			return apply_filters( 'ld_propanel_filter_search', $groups_data, $this->filter_key, $group_query_args );
		}

		/**
		 * Renders the filter results table for groups.
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
			$this->filter_headers = array();

			if ( 'widget' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'checkbox' => esc_html__( 'Checkbox', 'learndash' ),
					'course'   => esc_html( LearnDash_Custom_Label::get_label( 'courses' ) ),
					// Translators: Course Progress column label.
					'progress' => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
				);
			} elseif ( 'full' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'checkbox'    => esc_html__( 'Checkbox', 'learndash' ),
					// Translators: Course ID column label.
					'course_id'   => sprintf( esc_html_x( '%s ID', 'Course ID', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'course'      => esc_html( LearnDash_Custom_Label::get_label( 'course' ) ),
					// Translators: User ID column label.
					'user_id'     => esc_html_x( 'U-ID', 'User ID', 'learndash' ),
					'user'        => esc_html__( 'User', 'learndash' ),
					// Translators: Course Progress column label.
					'progress'    => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
					// Translators: Course completion date column label.
					'last_update' => esc_html_x( 'Completed On', 'Course completion data', 'learndash' ),
				);
			} elseif ( 'shortcode' === $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'course'   => esc_html( LearnDash_Custom_Label::get_label( 'courses' ) ),
					// Translators: Course Progress column label.
					'progress' => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
				);
			}

			return apply_filters( 'ld-propanel-reporting-headers', $this->filter_headers, $this->filter_key ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Legacy hook.
		}

		/**
		 * Builds HTML rows for group course activity.
		 *
		 * @since 4.17.0
		 *
		 * @param int $group_id Group post ID.
		 * @return array<string, mixed>
		 */
		public function filter_result_rows( $group_id = 0 ) {
			$response = array(
				'rows_html' => '',
			);

			$this->filter_table_headers();

			$activity_query_defaults = array(
				'post_types'      => 'sfwd-courses',
				'activity_types'  => 'course',
				'activity_status' => '',
				'orderby_order'   => 'posts.post_title, users.display_name',
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
						$activities['pager']['current_page'] = $this->activity_query_args['paged'];
						$response['pager']                   = $activities['pager'];
					}

					foreach ( $activities['results'] as $idx => $activity ) {
						$row      = array();
						$row_html = '<tr id="ld-propanel-tr-' . $idx . '">';

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

			return $response;
		}
	}
}

add_action(
	'learndash_propanel_filtering_init',
	function () {
		new LearnDash_ProPanel_Reporting_Filter_Groups();
	}
);
