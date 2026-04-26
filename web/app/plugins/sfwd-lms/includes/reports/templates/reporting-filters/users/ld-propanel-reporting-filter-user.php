<?php
/**
 * LearnDash ProPanel Filtering: User.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'LearnDash_ProPanel_Reporting_Filter_Users' )
	&& class_exists( 'LearnDash_ProPanel_Filtering' )
) {
	class LearnDash_ProPanel_Reporting_Filter_Users extends LearnDash_ProPanel_Filtering {
		public function __construct() {
			$this->filter_key                = 'users';
			$this->filter_search_placeholder = sprintf(
				// translators: Search Courses
				esc_html_x( 'Search %s', 'Search Courses', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'courses' )
			);

			// Path relative to the plugin templates directory
			$this->filter_template_table = 'reporting-filters/users/ld-propanel-reporting-filter-user-table.php';
			$this->filter_template_row   = 'reporting-filters/users/ld-propanel-reporting-filter-user-row.php';

			add_filter( 'ld_propanel_filtering_register_filters', array( $this, 'filter_register' ), 30 );

			add_filter( 'ld_propanel_reporting_post_args', array( $this, 'filter_post_args' ), 30, 2 );
			add_filter( 'ld_propanel_reporting_activity_args', array( $this, 'filter_activity_args' ), 30, 3 );
		}

		public function filter_post_args( $post_args = array(), $_get = array() ) {
			if (
				isset( $_get['filters'][ $this->filter_key ] )
				&& ! empty( $_get['filters'][ $this->filter_key ] )
			) {
				if ( is_string( $_get['filters'][ $this->filter_key ] ) ) {
					$post_args['filters'][ $this->filter_key ] = explode( ',', $_get['filters'][ $this->filter_key ] );
				} else {
					$post_args['filters'][ $this->filter_key ] = $_get['filters'][ $this->filter_key ];
				}

				$post_args['filters'][ $this->filter_key ] = array_map( 'intval', $post_args['filters'][ $this->filter_key ] );
			}

			if ( learndash_is_admin_user( get_current_user_id() ) ) {
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			} else {
				$post_args['filters'][ $this->filter_key ] = array( get_current_user_id() );

				if (
					isset( $post_args['template'] )
					&& (
						$post_args['template'] == 'course-reporting'
						|| $post_args['template'] == 'group-reporting'
					)
				) {
					$post_args['template'] = 'user-reporting';
				}

				if (
					isset( $post_args['filters']['type'] )
					&& (
						$post_args['filters']['type'] == 'course'
						|| $post_args['filters']['type'] == 'group'
					)
				) {
					$post_args['filters']['type'] = 'user';
				}
			}

			return $post_args;
		}

		public function filter_activity_args( $activity_args = array(), $post_data = array(), $_get = array() ) {
			if ( ! empty( $activity_args ) ) {
				if (
					isset( $post_data['filters'][ $this->filter_key ] )
					&& ! empty( $post_data['filters'][ $this->filter_key ] )
				) {
					$activity_args['user_ids'] = $post_data['filters'][ $this->filter_key ];
				} elseif (
					! isset( $activity_args['user_ids'] )
					|| empty( $activity_args['user_ids'] )
				) {
					if ( learndash_is_admin_user( get_current_user_id() ) ) {
						if (
							! isset( $activity_args['user_ids'] )
							|| empty( $activity_args['user_ids'] )
						) {
							// $exclude_admin_users = ld_propanel_exclude_admin_users();

							if ( ! empty( $activity_args['post_ids'] ) ) {
								$course_user_ids = array();
								$course_has_open = false;
								foreach ( $activity_args['post_ids'] as $course_id ) {
									if ( 'open' === learndash_get_setting( $course_id, 'course_price_type' ) ) {
										$course_has_open = true;
										// If any of the courses are 'free' price type the we abort and don't include any user_ids.
										// This will cause the query to user all users.
										$course_user_ids = array();
										break;
									} else {
										$course_user_query = learndash_get_users_for_course( $course_id, array(), ld_propanel_exclude_admin_users() );
										if ( $course_user_query instanceof WP_User_Query ) {
											$user_ids = $course_user_query->get_results();
											if ( ! empty( $user_ids ) ) {
												$course_user_ids = array_merge( $course_user_ids, $user_ids );
											}
										}
									}
								}

								if ( ! $course_has_open ) {
									if (
										! ld_propanel_exclude_admin_users()
										&& ld_propanel_auto_enroll_admin_users()
									) {
										$admin_user_ids = ld_propanel_get_admin_user_ids();
										if ( ! empty( $admin_user_ids ) ) {
											$course_user_ids = array_merge( $course_user_ids, $admin_user_ids );
										}
									}
								}

								if ( ! empty( $course_user_ids ) ) {
									$activity_args['user_ids'] = array_unique( $course_user_ids );
								} elseif ( ! $course_has_open ) {
										$activity_args = array();
								} elseif ( is_multisite() ) {
									$course_user_ids = get_users( array( 'fields' => array( 'ID' ) ) );
									if ( ! empty( $course_user_ids ) ) {
										$course_user_ids = wp_list_pluck( $course_user_ids, 'ID' );
										error_log( 'course_user_ids<pre>' . print_r( $course_user_ids, true ) . '</pre>' );
										$activity_args['user_ids'] = $course_user_ids;
									}
								}
							}

							/*
							else {
								$user_query_args = array(
									'fields'    =>  'ID',
									'role'      =>  'administrator'
								);
								$user_query = new WP_User_Query( $user_query_args );
								$course_user_ids = $user_query->get_results();
								if ( !empty( $course_user_ids ) ) {
									$activity_args['user_ids'] = $course_user_ids;
									$activity_args['user_ids_action'] = 'NOT IN';
								}
							}
							*/
						}
					} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
						$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						if ( ! empty( $group_ids ) ) {
							$user_ids = array();
							foreach ( $group_ids as $group_id ) {
								$group_user_ids = learndash_get_groups_user_ids( $group_id );
								if ( ! empty( $group_user_ids ) ) {
									$user_ids = array_merge( $user_ids, $group_user_ids );
								}
							}

							if ( ! empty( $user_ids ) ) {
								$activity_args['user_ids'] = $user_ids;
							} else {
								$activity_args = array();
							}
						} else {
							$activity_args = array();
						}
					} else {
						$activity_args['user_ids'] = get_current_user_id();
					}
				}
			}

			return $activity_args;      }

		public function filter_display() {
			if ( learndash_is_admin_user( get_current_user_id() ) ) {
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			} else {
				return '';
			}

			return '<select class="filter-users select2" data-ajax--cache="true" data-allow-clear="true" data-placeholder="' . esc_html__( 'All Users', 'learndash' ) . '"><option value="">' . esc_html__( 'Select User', 'learndash' ) . '</option></select>';
		}

		public function filter_search() {
			$response = array(
				'total' => 0,
				'items' => array(),
			);

			if ( ld_propanel_get_users_count() ) {
				$users = array();

				$user_query_args = array(
					'orderby' => 'display_name',
					'order'   => 'ASC',
					'number'  => 10,
					'paged'   => intval( $_GET['page'] ),
				);

				if (
					isset( $_GET['search'] )
					&& ! empty( $_GET['search'] )
				) {
					$user_query_args['search']         = sprintf( '*%s*', $_GET['search'] );
					$user_query_args['search_columns'] = array( 'display_name' );
				}

				$post_filters       = ( isset( $this->post_data['filters'] ) && is_array( $this->post_data['filters'] ) ) ? $this->post_data['filters'] : array();
				$filter_groups_uid  = ( isset( $post_filters['groups'] ) && is_array( $post_filters['groups'] ) ) ? array_map( 'intval', $post_filters['groups'] ) : array();
				$filter_courses_uid = ( isset( $post_filters['courses'] ) && is_array( $post_filters['courses'] ) ) ? array_map( 'intval', $post_filters['courses'] ) : array();

				if ( learndash_is_admin_user( get_current_user_id() ) ) {
					// Here we check the group selector first. If the group is selected then we don't need to check the course selector
					if ( ! empty( $filter_groups_uid ) ) {
						$groups_user_ids = array();
						foreach ( $filter_groups_uid as $group_id ) {
							$user_ids = learndash_get_groups_user_ids( $group_id );
							if ( ! empty( $user_ids ) ) {
								$groups_user_ids = array_merge( $groups_user_ids, $user_ids );
							} else {
								$user_query_args = array();
							}
						}
						if ( ! empty( $groups_user_ids ) ) {
							$user_query_args['include'] = $groups_user_ids;
						}
					} elseif ( ! empty( $filter_courses_uid ) ) {
						$courses_user_ids = array();
						foreach ( $filter_courses_uid as $course_id ) {
							$course_user_query = learndash_get_users_for_course( $course_id, array(), ld_propanel_exclude_admin_users() );
							if ( $course_user_query instanceof WP_User_Query ) {
								$user_ids = $course_user_query->get_results();
								if ( ! empty( $user_ids ) ) {
									$courses_user_ids = array_merge( $courses_user_ids, $user_ids );
								}
							}
						}
						if (
							! ld_propanel_exclude_admin_users()
							&& ld_propanel_auto_enroll_admin_users()
						) {
							$admin_user_ids = ld_propanel_get_admin_user_ids();
							if ( ! empty( $admin_user_ids ) ) {
								$course_user_ids = array_merge( $course_user_ids, $admin_user_ids );
							}
						}

						if ( ! empty( $courses_user_ids ) ) {
							$user_query_args['include'] = array_unique( $courses_user_ids );
						}
					}
				} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
					// Here we check the group selector first. If the group is selected then we don't need to check the course selector
					if ( ! empty( $filter_groups_uid ) ) {
						$groups_user_ids = array();
						foreach ( $filter_groups_uid as $group_id ) {
							$user_ids = learndash_get_groups_user_ids( $group_id );
							if ( ! empty( $user_ids ) ) {
								$groups_user_ids = array_merge( $groups_user_ids, $user_ids );
							}
						}
						if ( ! empty( $groups_user_ids ) ) {
							$user_query_args['include'] = $groups_user_ids;
						} else {
							$user_query_args = array();
						}
					} elseif ( ! empty( $filter_courses_uid ) ) {
						$admin_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						if ( ! empty( $admin_group_ids ) ) {
							$group_ids = array();
							foreach ( $admin_group_ids as $group_id ) {
								$group_course_ids = learndash_group_enrolled_courses( $group_id );
								if ( ! empty( $group_course_ids ) ) {
									$course_ids_intersect = array_intersect( $filter_courses_uid, $group_course_ids );
									if ( ! empty( $course_ids_intersect ) ) {
										$group_ids[] = $group_id;
									}
								}
							}

							$groups_user_ids = array();
							if ( ! empty( $group_ids ) ) {
								foreach ( $group_ids as $group_id ) {
									$user_ids = learndash_get_groups_user_ids( $group_id );
									if ( ! empty( $user_ids ) ) {
										$groups_user_ids = array_merge( $groups_user_ids, $user_ids );
									}
								}
							}

							if ( ! empty( $groups_user_ids ) ) {
								$user_query_args['include'] = $groups_user_ids;
							} else {
								$user_query_args = array();
							}
						} else {
							$user_query_args = array();
						}
					} else {
						$admin_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						if ( ! empty( $admin_group_ids ) ) {
							$groups_user_ids = array();
							foreach ( $admin_group_ids as $group_id ) {
								$user_ids = learndash_get_groups_user_ids( $group_id );
								if ( ! empty( $user_ids ) ) {
									$groups_user_ids = array_merge( $groups_user_ids, $user_ids );
								}
							}
							if ( ! empty( $groups_user_ids ) ) {
								$user_query_args['include'] = $groups_user_ids;
							} else {
								$user_query_args = array();
							}
						} else {
							$user_query_args = array();
						}
					}
				} else {
					$user_query_args['include'] = get_current_user_id();
				}

				if ( ! empty( $user_query_args ) ) {
					// if ( ( isset( $this->post_data['filters']['courses'] ) ) && ( !empty( $this->post_data['filters']['courses'] ) ) ) {
					// if ( ( !isset( $this->post_data['filters']['groups'] ) ) || ( empty( $this->post_data['filters']['groups'] ) ) ) {
					// if ( ld_propanel_exclude_admin_users() ) {
					// $user_query_args['role__not_in'] = array('administrator');
					// }
					// }
					// }

					if ( ld_propanel_exclude_admin_users() ) {
						$user_query_args['role__not_in'] = array( 'administrator' );
					}

					$user_query_args = apply_filters( 'ld_propanel_reporting_user_search_args', $user_query_args );
					if ( ! empty( $user_query_args ) ) {
						$user_query = new WP_User_Query( $user_query_args );

						if ( ! empty( $user_query->results ) ) {
							$response['total'] = $user_query->get_total();

							foreach ( $user_query->get_results() as $user ) {
								$users[] = array(
									'id'   => $user->ID,
									'text' => $user->display_name,
								);
							}
						}
					}
				}

				/**
				 * Filter users returned in search
				 */
				$response['items'] = apply_filters( 'ld_propanel_reporting_user_search_results', $users, $user_query_args );
			}

			return $response;
		}


		function filter_build_table() {
			$this->filter_table_headers();
			$container_type = $_GET['container_type'];

			ob_start();
			include ld_propanel_get_template( $this->filter_template_table );
			return ob_get_clean();
		}

		function filter_table_headers() {
			$this->filter_headers = array();

			if ( 'widget' == $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'course'   => esc_html( LearnDash_Custom_Label::get_label( 'courses' ) ),
					// translators: Course Progress.
					'progress' => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
				);
			} elseif ( 'full' == $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					// translators: Course ID.
					'course_id'   => sprintf( esc_html_x( '%s ID', 'Course ID', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'course'      => esc_html( LearnDash_Custom_Label::get_label( 'course' ) ),
					// translators: Course Progress.
					'progress'    => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
					// translators: Course completion date
					'last_update' => esc_html_x( 'Completed On', 'Course completion date', 'learndash' ),
				);
			} elseif ( 'widget' == $this->post_data['container_type'] ) {
				$this->filter_headers = array(
					'course'   => esc_html( LearnDash_Custom_Label::get_label( 'courses' ) ),
					// translators: Course Progress.
					'progress' => esc_html_x( 'Progress', 'Course Progress', 'learndash' ),
				);
			}

			return apply_filters( 'ld-propanel-reporting-headers', $this->filter_headers, $this->filter_key );
		}

		function filter_result_rows( $user_id ) {
			/**
			 * Build the response
			 */
			$response = array(
				'total_rows'  => 0,
				// 'rows' => array(),
				'rows_html'   => '',
				'total_users' => 1,
			);

			$this->filter_table_headers();

			$activity_query_defaults = array(
				'post_types'      => 'sfwd-courses',
				'activity_types'  => 'course',
				'activity_status' => '',
				'orderby_order'   => 'users.display_name, posts.post_title',
				'date_format'     => 'F j, Y H:i:s',
			);

			$this->activity_query_args = wp_parse_args( $this->activity_query_args, $activity_query_defaults );
			$this->activity_query_args = ld_propanel_load_activity_query_args( $this->activity_query_args, $this->post_data );

			/**
			 * Build Course Query args
			 * Search args column indexes are different on full vs widget
			 */

			if (
				isset( $this->activity_query_args['s'] )
				&& ! empty( $this->activity_query_args['s'] )
			) {
				$this->activity_query_args['s_context'] = 'post_title';
			}

			/**
			 * Get the goodies
			 */
			if ( ! empty( $this->activity_query_args ) ) {
				$this->activity_query_args = ld_propanel_adjust_admin_users( $this->activity_query_args );

				$response['total_users'] = count( $this->activity_query_args['user_ids'] );

				$this->activity_query_args = ld_propanel_convert_fewer_users( $this->activity_query_args );

				// error_log('users: activity_query_args<pre>'. print_r($this->activity_query_args, true) .'</pre>');
				$activities = learndash_reports_get_activity( $this->activity_query_args );
				// error_log('users: activities<pre>'. print_r($activities, true) .'</pre>');
				if (
					isset( $activities['results'] )
					&& ! empty( $activities['results'] )
				) {
					if (
						isset( $activities['pager'] )
						&& ! empty( $activities['pager'] )
					) {
						$response['total_rows'] = $activities['pager']['total_items'];

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
							$row_html .= '<td class="' . apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ) . '">' . $row . '</td>';
						}
						$row_html              .= '</tr>';
						$response['rows_html'] .= $row_html;
					}

					// Just in case the pager returns empties
					if (
						empty( $response['total_rows'] )
						&& count( $response['rows'] )
					) {
						$response['total_rows'] = count( $response['rows'] );
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

		// End of functions
	}
}

add_action(
	'learndash_propanel_filtering_init',
	function () {
		new LearnDash_ProPanel_Reporting_Filter_Users();
	}
);
