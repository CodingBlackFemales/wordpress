<?php
/**
 * Group functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles group email messages.
 *
 * Fires on `learndash_group_emails` AJAX action.
 *
 * @since 2.1.0
 */
function learndash_group_emails() {
	if ( ( isset( $_POST['action'] ) ) && ( 'learndash_group_emails' === $_POST['action'] ) && ( isset( $_POST['group_email_data'] ) ) && ( ! empty( $_POST['group_email_data'] ) ) ) {

		if ( ! is_user_logged_in() ) {
			exit;
		}
		$current_user = wp_get_current_user();
		if ( ( ! learndash_is_group_leader_user( $current_user->ID ) ) && ( ! learndash_is_admin_user( $current_user->ID ) ) ) {
			exit;
		}

		$group_email_data = json_decode( stripslashes( $_POST['group_email_data'] ), true );

		if ( ( ! isset( $group_email_data['group_id'] ) ) || ( empty( $group_email_data['group_id'] ) ) ) {
			die();
		}
		$group_email_data['group_id'] = intval( $group_email_data['group_id'] );

		if ( ( ! isset( $_POST['nonce'] ) ) || ( empty( $_POST['nonce'] ) ) || ( ! wp_verify_nonce( $_POST['nonce'], 'group_email_nonce_' . $group_email_data['group_id'] . '_' . $current_user->ID ) ) ) {
			die();
		}

		if ( ( ! isset( $group_email_data['email_subject'] ) ) || ( empty( $group_email_data['email_subject'] ) ) ) {
			die();
		}
		$group_email_data['email_subject'] = wp_strip_all_tags( stripcslashes( $group_email_data['email_subject'] ) );

		if ( ( ! isset( $group_email_data['email_message'] ) ) || ( empty( $group_email_data['email_message'] ) ) ) {
			die();
		}
		$group_email_data['email_message'] = wpautop( stripcslashes( $group_email_data['email_message'] ) );

		$group_admin_ids = learndash_get_groups_administrator_ids( $group_email_data['group_id'] );
		if ( in_array( $current_user->ID, $group_admin_ids, true ) === false ) {
			die();
		}

		$mail_args = array(
			'to'          => $current_user->user_email,
			'subject'     => $group_email_data['email_subject'],
			'message'     => $group_email_data['email_message'],
			'attachments' => '',
			'headers'     => array(
				'MIME-Version: 1.0',
				'content-type: text/html',
				'From: ' . $current_user->display_name . ' <' . $current_user->user_email . '>',
				'Reply-to: ' . $current_user->display_name . ' <' . $current_user->user_email . '>',
			),
		);

		$group_user_ids = learndash_get_groups_user_ids( $group_email_data['group_id'] );
		if ( ! empty( $group_user_ids ) ) {
			$email_addresses = array();
			if ( ( defined( 'LEARNDASH_GROUP_EMAIL_SINGLE' ) ) && ( true === LEARNDASH_GROUP_EMAIL_SINGLE ) ) {
				$group_email_error_message = array();
				foreach ( $group_user_ids as $user_id ) {
					$user = get_user_by( 'id', $user_id );

					$group_email_error = null;
					add_action(
						'wp_mail_failed',
						function ( $mail_error ) {
							global $group_email_error;
							$group_email_error = $mail_error; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- It is what it is.
						}
					);

					if ( $user ) {
						$mail_args['to'] = sanitize_email( $user->user_email );

						/**
						 * Filters group email user arguments.
						 *
						 * @param array $mail_args Group mail arguments.
						 */
						$mail_args = apply_filters( 'ld_group_email_users_args', $mail_args );
						if ( ! empty( $mail_args ) ) {

							/**
							 * Fires before sending user group email.
							 *
							 * @param array $mail_args Mail arguments.
							 */
							do_action( 'ld_group_email_users_before', $mail_args );

							$mail_ret = wp_mail( $mail_args['to'], $mail_args['subject'], $mail_args['message'], $mail_args['headers'], $mail_args['attachments'] );

							/**
							 * Fires after sending user group email.
							 *
							 * @param array   $mail_args Mail arguments.
							 * @param boolean $success   Whether the email contents were sent successfully.
							 */
							do_action( 'ld_group_email_users_after', $mail_args, $mail_ret );

							if ( ! $mail_ret ) {
								if ( is_wp_error( $group_email_error ) ) { // @phpstan-ignore-line - No time to investigate.
									$group_email_error_message[ $user->user_email ] = $group_email_error->get_error_message();
								}
								wp_send_json_error(
									array(
										// translators: mail_ret error, group email error message.
										'message' => sprintf( wp_kses_post( __( '<span style="color:red">Error: Email(s) not sent. Please try again or check with your hosting provider.<br />wp_mail() returned %1$d.<br />Error: %2$s</span>', 'learndash' ) ), $mail_ret, $group_email_error_message[ $user->user_email ] ),
									)
								);
								die();
							} else {
								$email_addresses[] = $user->user_email;
							}
						} else {
							wp_send_json_error(
								array(
									'message' => '<span style="color:red">' . esc_html__( 'Mail Args empty. Unexpected condition from filter: ld_group_email_users_args', 'learndash' ) . '</span>',
								)
							);
						}
					}
				}

				wp_send_json_success(
					array(
						'message' => '<span style="color:green">' .
						sprintf(
							// translators: total of users emailed, group.
							esc_html__(
								'Success: Email sent to %1$d %2$s users.',
								'learndash'
							),
							count( $email_addresses ),
							learndash_get_custom_label_lower( 'group' )
						),
						'</span>',
					)
				);
			} else {
				foreach ( $group_user_ids as $user_id ) {
					$user = get_user_by( 'id', $user_id );

					if ( $user ) {
						$email_addresses[] = 'Bcc: ' . sanitize_email( $user->user_email );
					}
				}

				$group_email_error = null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- It is what it is.
				add_action(
					'wp_mail_failed',
					function ( $mail_error ) {
						global $group_email_error;
						$group_email_error = $mail_error; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- It is what it is.
					}
				);

				if ( $email_addresses ) {
					$mail_args['headers'] = array_merge( $mail_args['headers'], $email_addresses );

					/**
					 * Filters group email user arguments.
					 *
					 * @param array $mail_args Group mail arguments.
					 */
					$mail_args = apply_filters( 'ld_group_email_users_args', $mail_args );
					if ( ! empty( $mail_args ) ) {

						/**
						 * Fires before sending user group email.
						 *
						 * @param array $mail_args Mail arguments.
						 */
						do_action( 'ld_group_email_users_before', $mail_args );

						$mail_ret = wp_mail( $mail_args['to'], $mail_args['subject'], $mail_args['message'], $mail_args['headers'], $mail_args['attachments'] );

						/**
						 * Fires after sending user group email.
						 *
						 * @param array   $mail_args Mail arguments.
						 * @param boolean $success   Whether the email contents were sent successfully.
						 */
						do_action( 'ld_group_email_users_after', $mail_args, $mail_ret );

						if ( ! $mail_ret ) {
							$group_email_error_message = '';

							if ( is_wp_error( $group_email_error ) ) { // @phpstan-ignore-line - No time to investigate.
								$group_email_error_message = $group_email_error->get_error_message();
							}
							wp_send_json_error(
								array(
									// translators: mail_ret error, group email error message.
									'message' => sprintf( wp_kses_post( __( '<span style="color:red">Error: Email(s) not sent. Please try again or check with your hosting provider.<br />wp_mail() returned %1$d.<br />Error: %2$s</span>', 'learndash' ) ), $mail_ret, $group_email_error_message ),
								)
							);
						} else {
							wp_send_json_success(
								array(
									'message' => '<span style="color:green">' . sprintf(
										wp_kses_post(
											// translators: total of users emailed, group.
											_nx(
												'Success: Email sent to %1$d %2$s user.',
												'Success: Email sent to %1$d %2$s users.',
												count( $email_addresses ),
												'placeholders: email addresses, group.',
												'learndash'
											)
										),
										number_format_i18n( count( $email_addresses ) ),
										learndash_get_custom_label_lower( 'group' )
									) . '</span>',
								)
							);
						}
					} else {
						wp_send_json_error(
							array(
								'message' => '<span style="color:red">' . esc_html__( 'Mail Args empty. Unexpected condition from filter: ld_group_email_users_args', 'learndash' ) . '</span>',
							)
						);
					}
				}
			}
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No users found.', 'learndash' ),
				)
			);
		}
		wp_send_json_error();
		die();
	}
}
add_action( 'wp_ajax_learndash_group_emails', 'learndash_group_emails' );

/**
 * Adds Group Leader role if it does not exist.
 *
 * Fires on `learndash_activated` hook.
 *
 * @since 2.1.0
 */
function learndash_add_group_admin_role() {
	$group_leader = get_role( 'group_leader' );

	// We can't call the class settings because it is not loaded yet.
	$group_leader_user_caps = get_option( 'learndash_groups_group_leader_user', array() );

	$role_caps = array(
		'read'                      => true,
		'group_leader'              => true,
		'wpProQuiz_show_statistics' => true,
	);

	/**
	 * Controls showing the Group Leader user in the Authors selector shown on the post editor. Seems that metabox query checks the
	 * user_meta key wp_user_level value to ensure the level is greater than 0. By default Group Leaders are set to level 0.
	 */
	if ( ( isset( $group_leader_user_caps['show_authors_selector'] ) ) && ( 'yes' === $group_leader_user_caps['show_authors_selector'] ) ) {
		$role_caps['level_1'] = true;
		$role_caps['level_0'] = false;
	} else {
		$role_caps['level_1'] = false;
		$role_caps['level_0'] = true;
	}

	if ( is_null( $group_leader ) ) {
		$group_leader = add_role(
			'group_leader',
			'Group Leader',
			$role_caps
		);
	} else {
		foreach ( $role_caps as $role_cap => $active ) {
			$group_leader->add_cap( $role_cap, $active );
		}
	}

	/**
	 * Added to correct issues with Group Leader User capabilities.
	 * See LEARNDASH-5707. See changes in
	 * includes/settings/settings-sections/class-ld-settings-section-groups-group-leader-user.php
	 *
	 * @since 3.4.0.2
	 */
	update_option( 'learndash_groups_group_leader_user_activate', time() );
}

add_action( 'learndash_activated', 'learndash_add_group_admin_role' );

/**
 * Allows group leader access to the admin dashboard.
 *
 * WooCommerce prevents access to the dashboard for all non-admin user roles. This filter allows
 * us to check if the current user is group_leader and override WC access.
 * Fires on `woocommerce_prevent_admin_access` hook.
 *
 * @since 2.2.0.1
 *
 * @param boolean $prevent_access value from WC.
 *
 * @return boolean The adjusted value based on user's access/role.
 */
function learndash_check_group_leader_access( $prevent_access ) {
	if ( learndash_is_group_leader_user() ) {

		if ( defined( 'LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS' ) ) {
			if ( LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS == true ) {
				$prevent_access = false;
			} elseif ( LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS == false ) {
				$prevent_access = true;
			}
		} else {
			$prevent_access = false;
		}
	}

	return $prevent_access;
}
add_filter( 'woocommerce_prevent_admin_access', 'learndash_check_group_leader_access', 20, 1 );

/**
 * Gets the list of enrolled courses for a group.
 *
 * @since 2.1.0
 *
 * @param int     $group_id         Optional. Group ID. Default 0.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array An array of course IDs.
 */
function learndash_group_enrolled_courses( $group_id = 0, $bypass_transient = false ) {
	$course_ids = array();

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {

		$query_args = array(
			'post_type'      => learndash_get_post_type_slug( 'course' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'learndash_group_enrolled_' . $group_id,
					'compare' => 'EXISTS',
				),
			),
		);

		$query = new WP_Query( $query_args );
		if ( ( is_a( $query, 'WP_Query' ) ) && ( property_exists( $query, 'posts' ) ) ) {
			$course_ids = $query->posts;
		}
	}

	return $course_ids;
}

/**
 * Sets the list of enrolled courses for a group.
 *
 * @since 2.2.1
 *
 * @param int   $group_id          Optional. Group ID. Default 0.
 * @param array $group_courses_new Optional. An array of courses to enroll a group. Default empty array.
 */
function learndash_set_group_enrolled_courses( $group_id = 0, $group_courses_new = array() ) {
	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {

		$group_courses_old = learndash_group_enrolled_courses( $group_id, true );

		$group_courses_intersect = array_intersect( $group_courses_new, $group_courses_old );

		$group_courses_add = array_diff( $group_courses_new, $group_courses_intersect );
		if ( ! empty( $group_courses_add ) ) {
			foreach ( $group_courses_add as $course_id ) {
				ld_update_course_group_access( $course_id, $group_id, false );
			}
		}

		$group_courses_remove = array_diff( $group_courses_old, $group_courses_intersect );
		if ( ! empty( $group_courses_remove ) ) {
			foreach ( $group_courses_remove as $course_id ) {
				ld_update_course_group_access( $course_id, $group_id, true );
			}
		}

		/**
		 * Finally clear our cache for other services.
		 * $transient_key = 'learndash_group_courses_' . $group_id;
		 * LDLMS_Transients::delete( $transient_key );
		 */
	}
}

/**
 * Groups all the related course ids for a set of groups IDs.
 *
 * @since 2.3.0
 *
 * @param int   $user_id   Optional. The User ID to get the associated groups.
 *                         Defaults to current user ID.
 * @param array $group_ids Optional. An array of group IDs to source the course IDs from.
 *                         If not provided will use group ids based on user_id access.
 *                         Default empty array.
 *
 * @return array An array of course_ids.
 */
function learndash_get_groups_courses_ids( $user_id = 0, $group_ids = array() ) {
	$course_ids = array();

	$user_id = absint( $user_id );
	if ( ( is_array( $group_ids ) ) && ( ! empty( $group_ids ) ) ) {
		$group_ids = array_map( 'absint', $group_ids );
	}

	if ( empty( $user_id ) ) {
		// If the current user is not able to be determined. Then abort.
		if ( ! is_user_logged_in() ) {
			return $course_ids;
		}

		$user_id = get_current_user_id();
	}

	if ( learndash_is_group_leader_user( $user_id ) ) {
		$group_leader_group_ids = learndash_get_administrators_group_ids( $user_id );

		// If user is group leader and the group ids is empty, nothing else to do. abort.
		if ( empty( $group_leader_group_ids ) ) {
			return $course_ids;
		}

		if ( empty( $group_ids ) ) {
			$group_ids = $group_leader_group_ids;
		} else {
			$group_ids = array_intersect( $group_leader_group_ids, $group_ids );
		}
	} elseif ( ! learndash_is_admin_user( $user_id ) ) {
		return $course_ids;
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
		$course_ids = array_unique( $course_ids );
	}

	return $course_ids;
}

/**
 * Checks whether a group is enrolled in a certain course.
 *
 * @since 2.1.0
 *
 * @param int $group_id  Group ID.
 * @param int $course_id Course ID.
 *
 * @return boolean Whether a group is enrolled in a course or not.
 */
function learndash_group_has_course( $group_id = 0, $course_id = 0 ) {
	$group_id  = absint( $group_id );
	$course_id = absint( $course_id );
	if ( ( ! empty( $group_id ) ) && ( ! empty( $course_id ) ) ) {
		return get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
	}

	return false;
}

/**
 * Gets the timestamp of when a course is available to the group.
 *
 * @since 2.1.0
 *
 * @param int $group_id  Group ID.
 * @param int $course_id Course ID.
 *
 * @return string The timestamp of when a course is available to the group.
 */
function learndash_group_course_access_from( $group_id = 0, $course_id = 0 ) {
	$group_id  = absint( $group_id );
	$course_id = absint( $course_id );
	if ( ( ! empty( $group_id ) ) && ( ! empty( $course_id ) ) ) {
		$timestamp = absint( get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true ) );

		/**
		 * Filters group courses order query arguments.
		 *
		 * @param int $timestamp The timestamp when the course was enrolled to the Group.
		 * @param int $group_id  Group ID.
		 * @param int $course_id Course ID.
		 */
		return apply_filters( 'learndash_group_course_access_from', $timestamp, $group_id, $course_id );
	}

	return '';
}

/**
 * Checks whether a course can be accessed by the user's group.
 *
 * @since 2.1.0
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 *
 * @return boolean Whether a course can be accessed by the user's group.
 */
function learndash_user_group_enrolled_to_course( $user_id = 0, $course_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
		$group_ids = learndash_get_users_group_ids( $user_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				if ( learndash_group_has_course( $group_id, $course_id ) ) {
					return true;
				}
			}
		}
	}
	return false;
}



/**
 * Gets timestamp of when the course is available to a user in a group.
 *
 * @since 2.1.0
 *
 * @param int     $user_id   User ID.
 * @param int     $course_id Course ID.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return string|void The timestamp of when a course is available to a user in a group.
 */
function learndash_user_group_enrolled_to_course_from( $user_id = 0, $course_id = 0, $bypass_transient = false ) {
	$enrolled_from = null;
	$user_id       = absint( $user_id );
	$course_id     = absint( $course_id );
	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return $enrolled_from;
	}

	$userdata = get_userdata( $user_id );
	if ( ! $userdata ) {
		return $enrolled_from;
	}
	$user_registered_timestamp = strtotime( $userdata->user_registered );

	$user_group_ids = learndash_get_users_group_ids( $user_id, $bypass_transient );
	if ( empty( $user_group_ids ) ) {
		return $enrolled_from;
	}
	$user_group_ids = array_map( 'absint', $user_group_ids );

	$course_group_ids = learndash_get_course_groups( $course_id );
	if ( empty( $course_group_ids ) ) {
		return $enrolled_from;
	}
	$course_group_ids = array_map( 'absint', $course_group_ids );

	$course_group_ids = array_intersect( $course_group_ids, $user_group_ids );
	if ( empty( $course_group_ids ) ) {
		return $enrolled_from;
	}

	if ( ! empty( $course_group_ids ) ) {
		$group_course_enrolled_times = array();

		foreach ( $course_group_ids as $course_group_id ) {
			$enrolled_from_temp = learndash_group_course_access_from( $course_group_id, $course_id );
			if ( ! empty( $enrolled_from_temp ) ) {
				$group_course_enrolled_times[ $course_group_id ] = absint( $enrolled_from_temp );
			}
		}

		if ( ! empty( $group_course_enrolled_times ) ) {
			asort( $group_course_enrolled_times );

			/**
			 * Filter the user group enrollment to course timestamps.
			 *
			 * @since 3.5.0
			 *
			 * @param array $group_course_enrolled_times Array of course to group enrollment timestamps.
			 * @param int   $user_id                     User ID.
			 * @param int   $course_id                   Course Post ID.
			 */
			$group_course_enrolled_times = apply_filters( 'learndash_user_group_enrolled_to_course_from_timestamps', $group_course_enrolled_times, $user_id, $course_id );

			foreach ( $group_course_enrolled_times as $group_id => $group_course_timestamp ) {
				$enrolled_from = $group_course_timestamp;
				break;
			}
		}
	}

	if ( ! is_null( $enrolled_from ) ) {
		if ( $enrolled_from <= time() ) {
			/** If the user registered AFTER the course was enrolled into the group
			 * then we use the user registration date.
			 */
			if ( $user_registered_timestamp > $enrolled_from ) {
				if ( ( defined( 'LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION' ) ) && ( true === LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION ) ) {
					$enrolled_from = $user_registered_timestamp;
				}
			}
		} else {
			/**
			 * If $enrolled_from is greater than the current timestamp
			 * we reset the enrolled from time to null. Not sure why.
			 */
			$enrolled_from = null;
		}
	}

	/**
	 * Filters user courses order query arguments.
	 *
	 * @param int $enrolled_from Calculated timestamp when user enrolled to course through group.
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 * @param int $group_id  Determined Group ID.
	 */
	return apply_filters( 'learndash_user_group_enrolled_to_course_from', $enrolled_from, $user_id, $course_id, $group_id );
}

/**
 * Gets the list of group IDs administered by the user.
 *
 * @since 2.1.0
 *
 * @global wpdb   $wpdb    WordPress database abstraction object.
 *
 * @param int     $user_id User ID.
 * @param boolean $menu    Optional. Menu. Default false.
 *
 * @return array A list of group ids managed by user.
 */
function learndash_get_administrators_group_ids( $user_id, $menu = false ) {
	$group_ids = array();

	$user_id = absint( $user_id );
	if ( ! empty( $user_id ) ) {
		if ( ( learndash_is_admin_user( $user_id ) ) && ( true !== $menu ) ) {
			$group_ids = learndash_get_groups( true, $user_id );
		} else {
			$all_user_meta = get_user_meta( $user_id );
			if ( ! empty( $all_user_meta ) ) {
				foreach ( $all_user_meta as $meta_key => $meta_set ) {
					if ( 'learndash_group_leaders_' == substr( $meta_key, 0, strlen( 'learndash_group_leaders_' ) ) ) {
						$group_ids = array_merge( $group_ids, $meta_set );
					}
				}
			}

			if ( ! empty( $group_ids ) ) {
				$group_ids = array_map( 'absint', $group_ids );
				$group_ids = array_diff( $group_ids, array( 0 ) ); // Removes zeros.
				$group_ids = learndash_validate_groups( $group_ids );
				if ( ! empty( $group_ids ) ) {
					if ( learndash_is_groups_hierarchical_enabled() ) {
						foreach ( $group_ids as $group_id ) {
							$group_children = learndash_get_group_children( $group_id );
							if ( ! empty( $group_children ) ) {
								$group_ids = array_merge( $group_ids, $group_children );
							}
						}
					}

					$group_ids = array_map( 'absint', $group_ids );
					$group_ids = array_unique( $group_ids, SORT_NUMERIC );
				}
			}
		}
	}

	return $group_ids;
}

/**
 * Makes user an administrator of the given group IDs.
 *
 * @since 2.2.1
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int   $user_id           User ID.
 * @param array $leader_groups_new Optional. A list of group ids. Default empty array.
 *
 * @return array
 */
function learndash_set_administrators_group_ids( $user_id = 0, $leader_groups_new = array() ) {
	global $wpdb;

	$user_id = absint( $user_id );
	if ( ! is_array( $leader_groups_new ) ) {
		$leader_groups_new = array();
	}

	if ( ! empty( $user_id ) ) {
		$leader_groups_old       = learndash_get_administrators_group_ids( $user_id, true );
		$leader_groups_intersect = array_intersect( $leader_groups_new, $leader_groups_old );

		$leader_groups_add = array_diff( $leader_groups_new, $leader_groups_intersect );
		if ( ! empty( $leader_groups_add ) ) {
			foreach ( $leader_groups_add as $group_id ) {
				ld_update_leader_group_access( $user_id, $group_id, false );
			}
		}

		$leader_groups_remove = array_diff( $leader_groups_old, $leader_groups_intersect );
		if ( ! empty( $leader_groups_remove ) ) {
			foreach ( $leader_groups_remove as $group_id ) {
				ld_update_leader_group_access( $user_id, $group_id, true );
			}
		}

		/**
		 * Finally clear our cache for other services.
		 * $transient_key = "learndash_user_groups_" . $user_id;
		 * LDLMS_Transients::delete( $transient_key );
		 */
	}
	return array();
}



/**
 * Gets the list of all groups.
 *
 * @since 2.1.0
 *
 * @param boolean $id_only         Optional. Whether to return only IDs. Default false.
 * @param int     $current_user_id Optional. ID of the user for checking capabilities. Default 0.
 *
 * @return array An array of group IDs.
 */
function learndash_get_groups( $id_only = false, $current_user_id = 0 ) {

	if ( empty( $current_user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$current_user_id = get_current_user_id();
	}

	if ( learndash_is_group_leader_user( $current_user_id ) ) {
		return learndash_get_administrators_group_ids( $current_user_id );
	} elseif ( learndash_is_admin_user( $current_user_id ) ) {

		$groups_query_args = array(
			'post_type'   => 'groups',
			'nopaging'    => true,
			'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private' ),
		);

		if ( $id_only ) {
			$groups_query_args['fields'] = 'ids';
		}

		$groups_query = new WP_Query( $groups_query_args );
		return $groups_query->posts;
	}
	return array();
}

/**
 * Get a users group IDs.
 *
 * @since 2.1.0
 *
 * @param int     $user_id          Optional. User ID. Default 0.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array A list of user's group IDs.
 */
function learndash_get_users_group_ids( $user_id = 0, $bypass_transient = false ) {
	$group_ids = array();

	$user_id = absint( $user_id );
	if ( ! empty( $user_id ) ) {
		$transient_key = 'learndash_user_groups_' . $user_id;
		if ( ! $bypass_transient ) {
			$group_ids_transient = LDLMS_Transients::get( $transient_key );
		} else {
			$group_ids_transient = false;
		}

		if ( false === $group_ids_transient ) {
			if ( learndash_is_group_leader_user( $user_id ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'groups_autoenroll_managed' ) ) ) {
				$group_ids = learndash_get_administrators_group_ids( $user_id );
			} else {
				$all_user_meta = get_user_meta( $user_id );
				if ( ! empty( $all_user_meta ) ) {
					foreach ( $all_user_meta as $meta_key => $meta_set ) {
						if ( 'learndash_group_users_' == substr( $meta_key, 0, strlen( 'learndash_group_users_' ) ) ) {
							$group_ids = array_merge( $group_ids, $meta_set );
						}
					}
				}
			}

			if ( ! empty( $group_ids ) ) {
				$group_ids = array_map( 'absint', $group_ids );
				$group_ids = array_diff( $group_ids, array( 0 ) ); // Removes zeros.
				$group_ids = learndash_validate_groups( $group_ids );
				if ( ! empty( $group_ids ) ) {
					if ( learndash_is_groups_hierarchical_enabled() ) {
						foreach ( $group_ids as $group_id ) {
							$group_children = learndash_get_group_children( $group_id );
							if ( ! empty( $group_children ) ) {
								$group_ids = array_merge( $group_ids, $group_children );
							}
						}
					}

					$group_ids = array_map( 'absint', $group_ids );
					$group_ids = array_unique( $group_ids, SORT_NUMERIC );
				}
			}
			LDLMS_Transients::set( $transient_key, $group_ids, MINUTE_IN_SECONDS );
		} else {
			$group_ids = $group_ids_transient;
		}
	}

	return $group_ids;
}

/**
 * Adds a user to the list of given group IDs.
 *
 * @param int   $user_id         Optional. User ID. Default 0.
 * @param array $user_groups_new Optional. An array of group IDs to add a user. Default empty array.
 */
function learndash_set_users_group_ids( $user_id = 0, $user_groups_new = array() ) {

	$user_id = absint( $user_id );
	if ( ! is_array( $user_groups_new ) ) {
		$user_groups_new = array();
	}

	if ( ! empty( $user_id ) ) {
		$user_groups_old = learndash_get_users_group_ids( $user_id, true );

		$user_groups_intersect = array_intersect( $user_groups_new, $user_groups_old );

		$user_groups_add = array_diff( $user_groups_new, $user_groups_intersect );
		if ( ! empty( $user_groups_add ) ) {
			foreach ( $user_groups_add as $group_id ) {
				ld_update_group_access( $user_id, $group_id, false );
			}
		}

		$user_groups_remove = array_diff( $user_groups_old, $user_groups_intersect );
		if ( ! empty( $user_groups_remove ) ) {
			foreach ( $user_groups_remove as $group_id ) {
				ld_update_group_access( $user_id, $group_id, true );
			}
		}
	}
}

/**
 * Gets the list of groups associated with the course.
 *
 * @since 2.2.1
 *
 * @param int     $course_id        Optional. Course ID. Default 0.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array An array of group IDs associated with the course.
 */
function learndash_get_course_groups( $course_id = 0, $bypass_transient = false ) {
	$group_ids = array();

	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$course_post_meta = get_post_meta( $course_id );
		if ( ! empty( $course_post_meta ) ) {
			foreach ( $course_post_meta as $meta_key => $meta_set ) {
				if ( 'learndash_group_enrolled_' == substr( $meta_key, 0, strlen( 'learndash_group_enrolled_' ) ) ) {
					/**
					 * For Course Groups the meta_value is a datetime. This is the datetime the course
					 * was added to the group. So we need to pull the group_id from the meta_key.
					 */
					$group_id    = str_replace( 'learndash_group_enrolled_', '', $meta_key );
					$group_ids[] = absint( $group_id );
				}
			}

			if ( ! empty( $group_ids ) ) {
				$group_ids = learndash_validate_groups( $group_ids );
			}
		}
	}

	return $group_ids;
}

/**
 * Adds a course to the list of the given group IDs.
 *
 * @param int   $course_id         Optional. Course ID. Default 0.
 * @param array $course_groups_new Optional. A list of group IDs to add a course. Default empty array.
 */
function learndash_set_course_groups( $course_id = 0, $course_groups_new = array() ) {

	$course_id = absint( $course_id );
	if ( ! is_array( $course_groups_new ) ) {
		$course_groups_new = array();
	}

	if ( ! empty( $course_id ) ) {
		$course_groups_old       = learndash_get_course_groups( $course_id, true );
		$course_groups_intersect = array_intersect( $course_groups_new, $course_groups_old );

		$course_groups_add = array_diff( $course_groups_new, $course_groups_intersect );
		if ( ! empty( $course_groups_add ) ) {
			foreach ( $course_groups_add as $group_id ) {
				ld_update_course_group_access( $course_id, $group_id, false );
			}
		}

		$course_groups_remove = array_diff( $course_groups_old, $course_groups_intersect );
		if ( ! empty( $course_groups_remove ) ) {
			foreach ( $course_groups_remove as $group_id ) {
				ld_update_course_group_access( $course_id, $group_id, true );
			}
		}

		// Finally clear our cache for other services.
		$transient_key = 'learndash_course_groups_' . $course_id;
		LDLMS_Transients::delete( $transient_key );
	}
}

/**
 * Gets the list of users ids that belong to a group.
 *
 * @since 2.1.0
 *
 * @param int     $group_id         Optional. Group ID. Default 0.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array An array of user ids that belong to group.
 */
function learndash_get_groups_user_ids( $group_id = 0, bool $bypass_transient = false ): array {
	$group_id = absint( $group_id );

	if ( empty( $group_id ) ) {
		return array();
	}

	$group_users = learndash_get_groups_users( $group_id, $bypass_transient );

	if ( empty( $group_users ) ) {
		return array();
	}

	return wp_list_pluck( $group_users, 'ID' );
}

/**
 * Gets the list of user objects that belong to a group.
 *
 * @since 2.1.2
 *
 * @param int     $group_id         Group ID.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array An array user objects that belong to group.
 */
function learndash_get_groups_users( $group_id, $bypass_transient = false ) {

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		if ( ! $bypass_transient ) {
			$transient_key       = 'learndash_group_users_' . $group_id;
			$group_users_objects = LDLMS_Transients::get( $transient_key );
		} else {
			$group_users_objects = false;
		}

		if ( false === $group_users_objects ) {

			/**
			 * Changed in v2.3 we no longer exclude ALL group leaders from groups.
			 * A group leader CAN be a member of a group user list.
			 *
			 * For this group get the group leaders. They will be excluded from the regular users.
			 * $group_leader_user_ids = learndash_get_groups_administrator_ids( $group_id );
			 */

			$user_query_args = array(
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'meta_query' => array(
					array(
						'key'     => 'learndash_group_users_' . intval( $group_id ),
						'compare' => 'EXISTS',
					),
				),
			);
			$user_query      = new WP_User_Query( $user_query_args );
			if ( isset( $user_query->results ) ) {
				$group_users_objects = $user_query->results;
			} else {
				$group_users_objects = array();
			}

			if ( ! $bypass_transient ) {
				LDLMS_Transients::set( $transient_key, $group_users_objects, MINUTE_IN_SECONDS );
			}
		}

		return $group_users_objects;
	}
	return array();
}


/**
 * Adds the list of given users to the group.
 *
 * @since 2.1.2
 *
 * @param int   $group_id        Optional. Group ID. Default 0.
 * @param array $group_users_new Optional. A list of user IDs to add to the group. Default empty array.
 */
function learndash_set_groups_users( $group_id = 0, $group_users_new = array() ) {

	$group_id = absint( $group_id );
	if ( ( is_array( $group_users_new ) ) && ( ! empty( $group_users_new ) ) ) {
		$group_users_new = array_map( 'absint', $group_users_new );
	} else {
		$group_users_new = array();
	}
	if ( ! empty( $group_id ) ) {
		update_post_meta( $group_id, 'learndash_group_users_' . $group_id, $group_users_new );

		$group_users_old = learndash_get_groups_user_ids( $group_id, true );

		$group_users_intersect = array_intersect( $group_users_new, $group_users_old );

		$group_users_add = array_diff( $group_users_new, $group_users_intersect );
		if ( ! empty( $group_users_add ) ) {
			foreach ( $group_users_add as $user_id ) {
				ld_update_group_access( $user_id, $group_id, false );
			}
		}

		$group_users_remove = array_diff( $group_users_old, $group_users_intersect );
		if ( ! empty( $group_users_remove ) ) {
			foreach ( $group_users_remove as $user_id ) {
				ld_update_group_access( $user_id, $group_id, true );
			}

			/**
			 * Fires after removing a user from the group.
			 *
			 * $group_id           int   ID of the group.
			 * $group_users_remove array An array of user IDs that are removed from the group.
			 */
			do_action( 'learndash_remove_group_users', $group_id, $group_users_remove );
		}

		// Finally clear our cache for other services.
		$transient_key = 'learndash_group_users_' . $group_id;
		LDLMS_Transients::delete( $transient_key );
	}
}

/**
 * Gets the list of administrator IDs for a group.
 *
 * @since 2.1.0
 *
 * @param int     $group_id         Group ID.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default false.
 *
 * @return array An array of group administrator IDs.
 */
function learndash_get_groups_administrator_ids( $group_id = 0, $bypass_transient = false ) {

	$group_leader_user_ids = array();

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		$group_leader_users = learndash_get_groups_administrators( $group_id, $bypass_transient );
		if ( ! empty( $group_leader_users ) ) {
			$group_leader_user_ids = wp_list_pluck( $group_leader_users, 'ID' );
		}
	}
	return $group_leader_user_ids;
}

/**
 * Gets the list of group leaders for the given group ID.
 *
 * @since 2.1.2
 *
 * @param int     $group_id         Group ID.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache or not. Default 0.
 *
 * @return array An array of group leader user objects.
 */
function learndash_get_groups_administrators( $group_id = 0, $bypass_transient = false ) {

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		$transient_key = 'learndash_group_leaders_' . $group_id;

		if ( ! $bypass_transient ) {
			$group_user_objects = LDLMS_Transients::get( $transient_key );
		} else {
			$group_user_objects = false;
		}
		if ( false === $group_user_objects ) {

			$user_query_args = array(
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'meta_query' => array(
					array(
						'key'     => 'learndash_group_leaders_' . intval( $group_id ),
						'value'   => intval( $group_id ),
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			);
			$user_query      = new WP_User_Query( $user_query_args );
			if ( isset( $user_query->results ) ) {
				$group_user_objects = $user_query->results;
			} else {
				$group_user_objects = array();
			}

			if ( ! $bypass_transient ) {
				LDLMS_Transients::set( $transient_key, $group_user_objects, MINUTE_IN_SECONDS );
			}
		}

		return $group_user_objects;
	}
	return array();
}

/**
 * Makes the user leader for the given group ID.
 *
 * @since 2.1.2
 *
 * @param int   $group_id          Optional. Group ID. Default 0.
 * @param array $group_leaders_new Optional. A list of user IDs to make group leader. Default empty array.
 */
function learndash_set_groups_administrators( $group_id = 0, $group_leaders_new = array() ) {

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		$group_leaders_old = learndash_get_groups_administrator_ids( $group_id, true );

		$group_leaders_intersect = array_intersect( $group_leaders_new, $group_leaders_old );
		$group_leaders_add       = array_diff( $group_leaders_new, $group_leaders_intersect );
		if ( ! empty( $group_leaders_add ) ) {
			foreach ( $group_leaders_add as $user_id ) {
				ld_update_leader_group_access( $user_id, $group_id );
			}
		}

		$group_leaders_remove = array_diff( $group_leaders_old, $group_leaders_intersect );
		if ( ! empty( $group_leaders_remove ) ) {
			foreach ( $group_leaders_remove as $user_id ) {
				ld_update_leader_group_access( $user_id, $group_id, true );
			}
		}

		// Finally clear our cache for other services.
		$transient_key = 'learndash_group_leaders_' . $group_id;
		LDLMS_Transients::delete( $transient_key );
	}
}

/**
 * Gets the list of groups associated with the course step.
 *
 * @since 3.1.8
 *
 * @param int $step_id Course Step ID. Required.
 *
 * @return array An array of group IDs associated with the course step.
 */
function learndash_get_course_step_groups( $step_id = 0 ) {
	$step_group_ids = array();

	$step_id = absint( $step_id );
	if ( ! empty( $step_id ) ) {
		$step_courses = learndash_get_courses_for_step( $step_id, true );
		if ( ! empty( $step_courses ) ) {
			foreach ( array_keys( $step_courses ) as $course_id ) {
				$step_group_ids = array_merge( $step_group_ids, learndash_get_course_groups( $course_id ) );
			}
		}
	}

	if ( ! empty( $step_group_ids ) ) {
		$step_group_ids = array_unique( $step_group_ids );
	}

	return $step_group_ids;
}

/**
 * Get all Users within all Groups managed by the Group Leader.
 *
 * @since   3.1.8
 *
 * @param  integer $group_leader_id  WP_User ID.
 * @return array WP_User IDs
 */
function learndash_get_groups_administrators_users( $group_leader_id = 0 ) {
	$user_ids = array();

	$group_leader_id = absint( $group_leader_id );
	if ( ! empty( $group_leader_id ) ) {
		// Get all the Group IDs of Groups they Manage.
		$group_ids = learndash_get_administrators_group_ids( $group_leader_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				// Get all the User IDs belonging to their Groups.
				$user_ids = array_merge( $user_ids, learndash_get_groups_user_ids( $group_id ) );
			}
		}
	}

	// Remove any overlap.
	if ( ! empty( $user_ids ) ) {
		$user_ids = array_unique( $user_ids );
	}

	return $user_ids;
}

/**
 * Get all Courses within all Groups managed by the Group Leader.
 *
 * @since   3.1.8
 *
 * @param integer $group_leader_id WP_User ID.
 * @return array Array of WP_Post Course IDs.
 */
function learndash_get_groups_administrators_courses( $group_leader_id = 0 ) {
	$course_ids = array();

	$group_leader_id = absint( $group_leader_id );
	if ( ! empty( $group_leader_id ) ) {
		// Get all the Group IDs of Groups they Manage.
		$group_ids = learndash_get_administrators_group_ids( $group_leader_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				// Get all the User IDs belonging to their Groups.
				$course_ids = array_merge( $course_ids, learndash_group_enrolled_courses( $group_id ) );
			}
		}
	}

	// Remove any overlap.
	if ( ! empty( $course_ids ) ) {
		$course_ids = array_unique( $course_ids );
	}

	return $course_ids;
}

/**
 * Get the Group Leader user for a specific Course step.
 *
 * @since 3.1.8
 *
 * @param integer $step_id         Course Step Post ID.
 * @param integer $group_leader_id Group Leader User ID. Optional.
 * @return array of user IDs.
 */
function learndash_get_groups_leaders_users_for_course_step( $step_id = 0, $group_leader_id = 0 ) {
	$user_ids = array();

	$step_id = absint( $step_id );

	if ( empty( $group_leader_id ) ) {
		$group_leader_id = get_current_user_id();
		if ( ! learndash_is_group_leader_user( $group_leader_id ) ) {
			$group_leader_id = 0;
		}
	}

	if ( ( ! empty( $step_id ) ) && ( ! empty( $group_leader_id ) ) ) {
		$gl_groups = learndash_get_administrators_group_ids( $group_leader_id );
		if ( ! empty( $gl_groups ) ) {
			$step_groups = learndash_get_course_step_groups( $step_id );
			$gl_groups   = array_intersect( $gl_groups, $step_groups );
		}

		if ( ! empty( $gl_groups ) ) {
			foreach ( $gl_groups as $group_id ) {
				$user_ids = array_merge( $user_ids, learndash_get_groups_user_ids( $group_id ) );
			}
		}
	}

	return $user_ids;
}

/**
 * Filter Quiz Statistics user listing to show only related users.
 *
 * @since 3.1.8
 *
 * @param string $where Statistics WHERE clause string.
 * @param array  $args  Array of query args.
 * @return string $where
 */
function learndash_fetch_quiz_statistic_history_where_filter( $where = '', $args = array() ) {

	if ( ! learndash_is_admin_user( get_current_user_id() ) ) {

		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			$group_user_ids = array();
			if ( ( isset( $args['quiz'] ) ) && ( ! empty( $args['quiz'] ) ) ) {
				$group_user_ids = learndash_get_groups_leaders_users_for_course_step( $args['quiz'], get_current_user_id() );
			} else {
				$group_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
			}

			if ( ! empty( $group_user_ids ) ) {
				$where .= ' AND user_id IN (' . implode( ',', $group_user_ids ) . ') ';
			} else {
				$where .= ' AND user_id = -1 ';
			}
		} else {
			$where .= ' AND user_id =' . get_current_user_id() . ' ';
		}
	}

	// Always return $where.
	return $where;
}
add_filter( 'learndash_fetch_quiz_statistic_history_where', 'learndash_fetch_quiz_statistic_history_where_filter', 10, 2 );
add_filter( 'learndash_fetch_quiz_toplist_history_where', 'learndash_fetch_quiz_statistic_history_where_filter', 10, 2 );
add_filter( 'learndash_fetch_quiz_statistic_overview_where', 'learndash_fetch_quiz_statistic_history_where_filter', 10, 2 );


/**
 * Checks if a user has the group leader capabilities.
 *
 * Replaces the `is_group_leader` function.
 *
 * @since 2.3.9
 *
 * @param int|WP_User $user Optional. The `WP_User` object or user ID to check. Default 0.
 *
 * @return boolean Returns true if the user is group leader otherwise false.
 */
function learndash_is_group_leader_user( $user = 0 ) {
	$user_id = 0;

	if ( ( is_numeric( $user ) ) && ( ! empty( $user ) ) ) {
		$user_id = $user;
	} elseif ( $user instanceof WP_User ) {
		$user_id = $user->ID;
	} else {
		$user_id = get_current_user_id();
	}

	if ( ( ! empty( $user_id ) ) && ( ! learndash_is_admin_user( $user_id ) ) && ( defined( 'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK' ) ) && ( LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK != '' ) ) {
		return user_can( $user_id, LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK );
	}

	return false;
}

/**
 * Checks if a user has the admin capabilities.
 *
 * @param int|WP_User $user Optional. The `WP_User` object or user ID to check. Default 0.
 *
 * @return boolean Returns true if the user is admin otherwise false.
 */
function learndash_is_admin_user( $user = 0 ) {
	$user_id = 0;

	if ( ( is_numeric( $user ) ) && ( ! empty( $user ) ) ) {
		$user_id = $user;
	} elseif ( $user instanceof WP_User ) {
		$user_id = $user->ID;
	} else {
		$user_id = get_current_user_id();
	}

	if ( ( ! empty( $user_id ) ) && ( defined( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) ) && ( LEARNDASH_ADMIN_CAPABILITY_CHECK != '' ) ) {
		return user_can( $user_id, LEARNDASH_ADMIN_CAPABILITY_CHECK );
	}

	return false;
}

/**
 * Checks whether a group leader is an admin of a user's group.
 *
 * @since 2.1.0
 *
 * @param int $group_leader_id Group leader ID.
 * @param int $user_id         User ID.
 *
 * @return boolean Returns true if group leader is an admin of a user's group otherwise false.
 */
function learndash_is_group_leader_of_user( $group_leader_id = 0, $user_id = 0 ) {
	$group_leader_id = absint( $group_leader_id );
	$user_id         = absint( $user_id );

	$admin_groups     = learndash_get_administrators_group_ids( $group_leader_id );
	$has_admin_groups = ! empty( $admin_groups ) && is_array( $admin_groups ) && ! empty( $admin_groups[0] );

	foreach ( $admin_groups as $group_id ) {
		$learndash_is_user_in_group = learndash_is_user_in_group( $user_id, $group_id );

		if ( $learndash_is_user_in_group ) {
			return true;
		}
	}

	return false;
}



/**
 * Checks whether a user is part of the group or not.
 *
 * @since 2.1.0
 *
 * @param int $user_id  User ID.
 * @param int $group_id Group ID.
 *
 * @return boolean Returns true if the user is part of the group otherwise false.
 */
function learndash_is_user_in_group( $user_id = 0, $group_id = 0 ) {
	$user_id  = absint( $user_id );
	$group_id = absint( $group_id );
	if ( ( ! empty( $user_id ) ) && ( ! empty( $group_id ) ) ) {
		if ( learndash_is_groups_hierarchical_enabled() ) {
			$group_ids = learndash_get_users_group_ids( $user_id );
			if ( in_array( $group_id, $group_ids, true ) ) {
				return true;
			}
		} else {
			return get_user_meta( $user_id, 'learndash_group_users_' . $group_id, true );
		}
	}

	return false;
}

/**
 * Deletes group ID from all users meta when the group is deleted.
 *
 * Fires on `delete_post` hook.
 *
 * @todo  restrict function to only run if post type is group
 *        will run against db every time a post is deleted
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param int $pid ID of the group being deleted.
 *
 * @return boolean|void Returns true if the deletion was successful.
 */
function learndash_delete_group( $pid = 0 ) {
	global $wpdb;

	$pid = absint( $pid );
	if ( ! empty( $pid ) && is_numeric( $pid ) ) {
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key'   => 'learndash_group_users_' . $pid,
				'meta_value' => $pid,
			)
		);
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key'   => 'learndash_group_leaders_' . $pid,
				'meta_value' => $pid,
			)
		);
	}

	return true;
}

add_action( 'delete_post', 'learndash_delete_group', 10 );


/**
 * Updates a user's group access.
 *
 * @since 2.1.0
 * @since 3.4.0 Added return boolean.
 *
 * @param int     $user_id  User ID.
 * @param int     $group_id Group ID.
 * @param boolean $remove   Optional. Whether to remove user from the group. Default false.
 *
 * @return bool true on action success otherwise false.
 */
function ld_update_group_access( $user_id = 0, $group_id = 0, $remove = false ): bool {
	$action_success = false;

	$user_id  = absint( $user_id );
	$group_id = absint( $group_id );

	if ( ( ! empty( $user_id ) ) && ( ! empty( $group_id ) ) ) {
		$activity_type = 'group_access_user';

		if ( $remove ) {
			$user_enrolled = get_user_meta( $user_id, 'learndash_group_users_' . $group_id, true );
			if ( $user_enrolled ) {
				$action_success = true;
				delete_user_meta( $user_id, 'learndash_group_users_' . $group_id );

				/**
				 * If the user is removed from the course then also remove the group_progress Activity.
				 */
				$group_user_activity_args = array(
					'activity_type' => 'group_progress',
					'user_id'       => $user_id,
					'post_id'       => $group_id,
					'course_id'     => 0,
				);

				$group_user_activity = learndash_get_user_activity( $group_user_activity_args );
				if ( is_object( $group_user_activity ) ) {
					learndash_delete_user_activity( $group_user_activity->activity_id );
				}

				/**
				 * Fires after the user is removed from group access meta.
				 *
				 * @since 2.1.0
				 *
				 * @param int $user_id  User ID.
				 * @param int $group_id Group ID.
				 */
				do_action( 'ld_removed_group_access', $user_id, $group_id );
			}
		} else {
			$user_enrolled = get_user_meta( $user_id, 'learndash_group_users_' . $group_id, true );
			if ( ! $user_enrolled ) {
				$action_success = true;
				update_user_meta( $user_id, 'learndash_group_users_' . $group_id, $group_id );

				/**
				 * Fires after the user is added to group access meta.
				 *
				 * @since 2.1.0
				 *
				 * @param int $user_id  User ID.
				 * @param int $group_id Group ID.
				 */
				do_action( 'ld_added_group_access', $user_id, $group_id );
			}
		}

		// Purge User Groups cache.
		$transient_key = 'learndash_user_groups_' . $user_id;
		LDLMS_Transients::delete( $transient_key );

		// Purge User Courses cache.
		$transient_key = 'learndash_user_courses_' . $user_id;
		LDLMS_Transients::delete( $transient_key );

	}

	return $action_success;
}


/**
 * Updates the course group access.
 *
 * @since 2.1.0
 * @since 3.4.0 Added return boolean.
 *
 * @param int     $course_id Course ID.
 * @param int     $group_id  Group ID.
 * @param boolean $remove    Optional. Whether to remove the group from the course. Default false.
 *
 * @return boolean true on action success otherwise false.
 */
function ld_update_course_group_access( $course_id = 0, $group_id = 0, $remove = false ) {
	$action_success = false;

	$course_id = absint( $course_id );
	$group_id  = absint( $group_id );

	if ( ( ! empty( $course_id ) ) && ( ! empty( $group_id ) ) ) {
		$activity_type = 'group_access_course';

		if ( $remove ) {
			$group_enrolled = get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
			if ( $group_enrolled ) {
				$action_success = true;
				delete_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id );

				/**
				 * Fires after the user is removed from the course group meta.
				 *
				 * @since 2.1.0
				 *
				 * @param int $user_id  User ID.
				 * @param int $group_id Group ID.
				 */
				do_action( 'ld_removed_course_group_access', $course_id, $group_id );
			}
		} else {
			$group_enrolled = get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
			if ( empty( $group_enrolled ) ) {
				$action_success = true;
				update_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, time() );

				/**
				 * Fires after the user is added to the course group access meta.
				 *
				 * @since 2.1.0
				 *
				 * @param int $user_id  User ID.
				 * @param int $group_id Group ID.
				 */
				do_action( 'ld_added_course_group_access', $course_id, $group_id );
			}
		}
	}

	return $action_success;
}


/**
 * Updates the group access for a group leader.
 *
 * @since 2.2.1
 * @since 3.4.0 Added return boolean.
 *
 * @param int  $user_id       User ID.
 * @param int  $group_id      Group ID.
 * @param bool $remove_access Optional. Whether to remove user from the group. Default false.
 *
 * @return bool True on action success, otherwise false.
 */
function ld_update_leader_group_access( int $user_id, int $group_id, bool $remove_access = false ): bool {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$group_leader_meta_key = 'learndash_group_leaders_' . $group_id;
	$has_group_leader_meta = ! empty( get_user_meta( $user_id, $group_leader_meta_key, true ) );

	// Adding (not updating, updating always returns false).

	if ( ! $remove_access && ! $has_group_leader_meta ) {
		update_user_meta( $user_id, $group_leader_meta_key, $group_id );

		/**
		 * Fires after the user is added to the group as a leader.
		 *
		 * @since 2.1.0
		 *
		 * @param int $user_id  User ID.
		 * @param int $group_id Group ID.
		 */
		do_action( 'ld_added_leader_group_access', $user_id, $group_id );

		return true;
	}

	// Removing.

	if ( $remove_access && $has_group_leader_meta ) {
		delete_user_meta( $user_id, $group_leader_meta_key );

		/**
		 * Fires after the user is removed from a group as a leader.
		 *
		 * @since 2.1.0
		 *
		 * @param int $user_id  User ID.
		 * @param int $group_id Group ID.
		 */
		do_action( 'ld_removed_leader_group_access', $user_id, $group_id );

		return true;
	}

	return false;
}

/**
 * Gets the group's user IDs if the course is associated with the group.
 *
 * @since 2.3.0
 *
 * @param int $course_id Optional. Course ID. Default 0.
 *
 * @return array An array of user IDs.
 */
function learndash_get_course_groups_users_access( $course_id = 0 ) {
	$user_ids = array();

	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$course_groups = learndash_get_course_groups( $course_id );
		if ( ( is_array( $course_groups ) ) && ( ! empty( $course_groups ) ) ) {
			foreach ( $course_groups as $group_id ) {
				$group_users_ids = learndash_get_groups_user_ids( $group_id );
				if ( ! empty( $group_users_ids ) ) {
					$user_ids = array_merge( $user_ids, $group_users_ids );
				}
			}
		}
	}

	if ( ! empty( $user_ids ) ) {
		$user_ids = array_unique( $user_ids );
	}

	return $user_ids;
}

/**
 * Gets all quizzes related to Group Courses.
 *
 * Given a group ID will determine all quizzes associated with courses of the group
 *
 * @since 2.3.0
 *
 * @param int $group_id Optional. Group ID. Default 0.
 *
 * @return array An array of quiz IDs.
 */
function learndash_get_group_course_quiz_ids( $group_id = 0 ) {
	$group_quiz_ids = array();

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		$group_course_ids = learndash_group_enrolled_courses( intval( $group_id ) );
		if ( ! empty( $group_course_ids ) ) {
			foreach ( $group_course_ids as $course_id ) {
				$group_quiz_query_args = array(
					'post_type'  => 'sfwd-quiz',
					'nopaging'   => true,
					'fields'     => 'ids',
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'course_id',
							'value'   => $course_id,
							'compare' => '=',
						),
						array(
							'key'     => 'ld_course_' . $course_id,
							'value'   => $course_id,
							'compare' => '=',
						),
					),
				);

				$group_quiz_query = new WP_Query( $group_quiz_query_args );
				if ( ! empty( $group_quiz_query->posts ) ) {
					$group_quiz_ids = array_merge( $group_quiz_ids, $group_quiz_query->posts );
					$group_quiz_ids = array_unique( $group_quiz_ids );
				}
			}
		}
	}

	return $group_quiz_ids;
}

/**
 * Check and recalculate the the status of the Group Courses for the User.
 *
 * @since 3.2.0
 *
 * @param integer $group_id Group ID to check.
 * @param integer $user_id  User ID to check.
 * @param boolean $recalc   Force the logic to recheck all courses.
 */
function learndash_get_user_group_progress( $group_id = 0, $user_id = 0, $recalc = false ) {
	static $progress_group_user = array();

	$group_id = absint( $group_id );
	$user_id  = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	}

	if ( ( empty( $group_id ) ) || ( empty( $user_id ) ) ) {
		return array();
	}

	if ( ! learndash_is_user_in_group( $user_id, $group_id ) ) {
		return array();
	}

	if ( ( isset( $progress_group_user[ $group_id ][ $user_id ] ) ) && ( ! empty( $progress_group_user[ $group_id ][ $user_id ] ) ) && ( true !== $recalc ) ) {
		return $progress_group_user[ $group_id ][ $user_id ];
	}

	$progress = array(
		'percentage'      => 0,
		'in-progress'     => 0,
		'not-started'     => 0,
		'completed'       => 0,
		'total'           => 0,
		'completed_on'    => 0,
		'started_on'      => 0,
		'course_ids'      => array(),
		'course_activity' => array(),
		'activity_id'     => 0,
		'group_activity'  => array(),
	);

	$group_user_activity_args = array(
		'activity_type' => 'group_progress',
		'user_id'       => $user_id,
		'post_id'       => $group_id,
		'course_id'     => 0,
	);

	$group_user_activity = learndash_get_user_activity( $group_user_activity_args );
	if ( is_object( $group_user_activity ) ) {
		$group_user_activity = json_decode( wp_json_encode( $group_user_activity ), true );
		if ( ( true === $group_user_activity['activity_status'] ) && ( true !== $recalc ) ) {
			$activity_meta = learndash_get_user_activity_meta( $group_user_activity['activity_id'] );
			if ( ( $activity_meta ) && ( ! empty( $activity_meta ) ) ) {

				foreach ( $activity_meta as $activity_set ) {
					if ( ( property_exists( $activity_set, 'activity_meta_key' ) ) && ( ! empty( $activity_set->activity_meta_key ) ) ) {
						if ( property_exists( $activity_set, 'activity_meta_value' ) ) {
							$meta[ $activity_set->activity_meta_key ] = $activity_set->activity_meta_value;
						} else {
							$meta[ $activity_set->activity_meta_key ] = '';
						}
					}
				}

				foreach ( $progress as $key => $val ) {
					switch ( $key ) {
						case 'percentage':
						case 'in-progress':
						case 'not-started':
						case 'completed':
						case 'total':
						case 'completed_on':
						case 'started_on':
							if ( isset( $meta[ $key ] ) ) {
								$progress[ $key ] = intval( $meta[ $key ] );
							}
							break;

						case 'group_activity':
							$progress[ $key ] = $group_user_activity;
							break;

						case 'activity_id':
							if ( isset( $group_user_activity['activity_id'] ) ) {
								$progress[ $key ] = absint( $group_user_activity['activity_id'] );
							}
							break;

						case 'course_ids':
						case 'course_activity':
						default:
							break;
					}
				}

				$progress_group_user[ $group_id ][ $user_id ] = $progress;
				return $progress;
			}
		}
	} else {
		$group_user_activity                    = $group_user_activity_args;
		$group_user_activity['changed']         = true;
		$group_user_activity['activity_status'] = 0;
	}

	$last_completed_course_time = 0;
	$last_started_course_time   = 0;
	$last_updated_course_time   = 0;

	$progress['course_ids'] = learndash_group_enrolled_courses( $group_id );
	if ( ! empty( $progress['course_ids'] ) ) {
		$progress['course_ids'] = array_map( 'absint', $progress['course_ids'] );
		$progress['total']      = count( $progress['course_ids'] );

		$group_courses_activity_args = array(
			'user_ids'       => $user_id,
			'post_types'     => learndash_get_post_type_slug( 'course' ),
			'activity_types' => 'course',
			'course_ids'     => $progress['course_ids'],
			'per_page'       => '',
		);

		$group_courses_activity = learndash_reports_get_activity( $group_courses_activity_args );
		if ( ( isset( $group_courses_activity['results'] ) ) && ( ! empty( $group_courses_activity['results'] ) ) ) {
			$progress['course_activity'] = array();
			foreach ( $group_courses_activity['results'] as $result ) {
				$result->activity_status    = absint( $result->activity_status );
				$result->activity_completed = absint( $result->activity_completed );
				$result->activity_started   = absint( $result->activity_started );
				$result->activity_updated   = absint( $result->activity_updated );

				$progress['course_activity'][ $result->activity_course_id ] = json_decode( wp_json_encode( $result ), true );

				if ( ( empty( $result->activity_started ) ) && ( ! empty( $result->activity_updated ) ) ) {
					$result->activity_started = $result->activity_updated;
				}

				if ( ( empty( $last_started_course_time ) ) || ( $result->activity_started < $last_started_course_time ) ) {
					$last_started_course_time = $result->activity_started;
				}

				if ( ( empty( $last_updated_course_time ) ) || ( $result->activity_updated < $last_updated_course_time ) ) {
					$last_updated_course_time = $result->activity_updated;
				}

				if ( ( 1 === $result->activity_status ) && ( ! empty( $result->activity_completed ) ) ) {
					$progress['completed']++;

					if ( $result->activity_completed > $last_completed_course_time ) {
						$last_completed_course_time = $result->activity_completed;
					}
				} elseif ( ! empty( $result->activity_started ) ) {
					$progress['in-progress']++;
				}
			}
		}
	}

	$progress['completed']   = absint( $progress['completed'] );
	$progress['total']       = absint( $progress['total'] );
	$progress['in-progress'] = absint( $progress['in-progress'] );
	$progress['not-started'] = $progress['total'] - $progress['completed'] - $progress['in-progress'];

	if ( ( ! empty( $progress['total'] ) ) && ( ! empty( $progress['completed'] ) ) ) {
		$progress['percentage'] = ceil( ( $progress['completed'] / $progress['total'] ) * 100 );
	} else {
		$progress['percentage'] = 0;
	}

	// Fire the Group Completed action. But after we add the activity record.
	$send_group_complete_action = false;

	if ( ( ! empty( $progress['total'] ) ) && ( $progress['total'] === $progress['completed'] ) ) {
		if ( true !== $group_user_activity['activity_status'] ) {
			$send_group_complete_action = true;
		}

		$group_user_activity['activity_status']    = true;
		$group_user_activity['activity_completed'] = absint( $last_completed_course_time );
		$progress['completed_on']                  = absint( $last_completed_course_time );
	} else {
		$group_user_activity['activity_status']    = false;
		$group_user_activity['activity_completed'] = 0;
	}

	$group_user_activity['activity_started'] = absint( $last_started_course_time );
	$progress['started_on']                  = absint( $last_started_course_time );

	$group_user_activity['activity_updated'] = absint( $last_updated_course_time );

	$group_user_activity['activity_meta'] = $progress;
	unset( $group_user_activity['activity_meta']['course_activity'] );
	unset( $group_user_activity['activity_meta']['group_activity'] );

	$progress['activity_id'] = learndash_update_user_activity( $group_user_activity );

	if ( true === $send_group_complete_action ) {
		/**
		 *
		 * Fires after the group is completed.
		 *
		 * @param array $group_data An array of group complete data.
		 */
		do_action(
			'learndash_group_completed',
			array(
				'user'            => get_user_by( 'id', $user_id ),
				'group'           => get_post( $group_id ),
				'progress'        => $progress,
				'group_completed' => $group_user_activity['activity_completed'],
			)
		);
	}

	$progress_group_user[ $group_id ][ $user_id ] = $progress;

	return $progress;
}

/**
 * Get User's group status
 *
 * @since 3.2.0
 *
 * @param int  $group_id Group ID.
 * @param int  $user_id  User ID.
 * @param bool $return_slug Optional. Default false.
 */
function learndash_get_user_group_status( $group_id = 0, $user_id = 0, $return_slug = false ) {
	$learndash_group_status_str = '';

	$group_id = absint( $group_id );
	$user_id  = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $learndash_group_status_str;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = absint( $user_id );
	}

	if ( ( empty( $group_id ) ) || ( empty( $user_id ) ) ) {
		return '';
	}

	$progress = learndash_get_user_group_progress( $group_id, $user_id );
	if ( ( ! empty( $progress ) ) && ( is_array( $progress ) ) && ( isset( $progress['percentage'] ) ) ) {
		if ( 100 === absint( $progress['percentage'] ) ) {
			if ( true === $return_slug ) {
				$learndash_group_status_str = 'completed';
			} else {
				$learndash_group_status_str = esc_html__( 'Completed', 'learndash' );
			}
		} elseif ( $progress['in-progress'] > 0 ) {
			if ( true === $return_slug ) {
				$learndash_group_status_str = 'in-progress';
			} else {
				$learndash_group_status_str = esc_html__( 'In Progress', 'learndash' );
			}
		}
	}

	if ( empty( $learndash_group_status_str ) ) {
		if ( true === $return_slug ) {
			$learndash_group_status_str = 'not-started';
		} else {
			$learndash_group_status_str = esc_html__( 'Not Started', 'learndash' );
		}
	}

	return $learndash_group_status_str;
}

/**
 * Get the user started group timestamp.
 *
 * @since 3.2.0
 *
 * @param  integer $group_id Group ID to check.
 * @param  integer $user_id  User ID to check.
 * @return integer time user started group courses.
 */
function learndash_get_user_group_started_timestamp( $group_id = 0, $user_id = 0 ) {
	$group_timestamp = 0;

	$group_id = absint( $group_id );
	$user_id  = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $group_timestamp;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = absint( $user_id );
	}

	if ( ( empty( $group_id ) ) || ( empty( $user_id ) ) ) {
		return '';
	}

	$progress = learndash_get_user_group_progress( $group_id, $user_id );
	if ( ( ! empty( $progress ) ) && ( is_array( $progress ) ) && ( isset( $progress['started_on'] ) ) ) {
		$group_timestamp = absint( $progress['started_on'] );
	}

	return $group_timestamp;
}

/**
 * Get the user completed group timestamp.
 *
 * @since 3.2.0
 *
 * @param  integer $group_id Group ID to check.
 * @param  integer $user_id  User ID to check.
 * @return integer time user started group courses.
 */
function learndash_get_user_group_completed_timestamp( $group_id = 0, $user_id = 0 ) {
	$group_timestamp = 0;

	$group_id = absint( $group_id );
	$user_id  = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $group_timestamp;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = absint( $user_id );
	}

	if ( ( empty( $group_id ) ) || ( empty( $user_id ) ) ) {
		return '';
	}

	$progress = learndash_get_user_group_progress( $group_id, $user_id );
	if ( ( ! empty( $progress ) ) && ( is_array( $progress ) ) && ( isset( $progress['completed_on'] ) ) ) {
		$group_timestamp = absint( $progress['completed_on'] );
	}

	return $group_timestamp;
}

/**
 * Get the user completed group percentage.
 *
 * @since 3.2.0
 *
 * @param  integer $group_id Group ID to check.
 * @param  integer $user_id  User ID to check.
 * @return integer time user started group courses.
 */
function learndash_get_user_group_completed_percentage( $group_id = 0, $user_id = 0 ) {
	$group_percentage = 0;

	$group_id = absint( $group_id );
	$user_id  = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $group_percentage;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = absint( $user_id );
	}

	if ( ( empty( $group_id ) ) || ( empty( $user_id ) ) ) {
		return '';
	}

	$progress = learndash_get_user_group_progress( $group_id, $user_id );
	if ( ( ! empty( $progress ) ) && ( is_array( $progress ) ) && ( isset( $progress['percentage'] ) ) ) {
		$group_percentage = $progress['percentage'];
	}

	return $group_percentage;
}

/**
 * Hook into the User Course Complete action.
 *
 * When the user completes a Course we check if that course
 * is part of any group the user is enrolled into.
 *
 * @since 3.2.0
 *
 * @param array $course_data Array of course data.
 */
function learndash_group_course_completed( $course_data = array() ) {

	if ( ( isset( $course_data['course'] ) ) && ( isset( $course_data['user'] ) ) ) {
		learndash_update_group_course_user_progress( $course_data['course']->ID, $course_data['user']->ID, true );
	}
}
add_action( 'learndash_course_completed', 'learndash_group_course_completed', 30, 1 );


/**
 * Update Group User Course progress.
 *
 * @since 3.2.0
 *
 * @param integer $course_id Course ID.
 * @param integer $user_id   User ID.
 * @param boolean $recalc    Force the logic to recheck all courses.
 */
function learndash_update_group_course_user_progress( $course_id = 0, $user_id = 0, $recalc = false ) {
	$course_id = absint( $course_id );
	$user_id   = absint( $user_id );

	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
		$user_group_ids = learndash_get_users_group_ids( $user_id );
		if ( empty( $user_group_ids ) ) {
			return;
		}

		$course_group_ids = learndash_get_course_groups( $course_id );
		if ( empty( $course_group_ids ) ) {
			return;
		}

		$group_ids = array_intersect( $user_group_ids, $course_group_ids );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				learndash_get_user_group_progress( $group_id, $user_id, $recalc );
			}
		}
	}
}

/**
 * Utility function to return all groups below the parent.
 *
 * @since 3.2.0
 *
 * @param integer $group_id Group parent ID.
 * @return array of children groups IDs.
 */
function learndash_get_group_children( $group_id = 0 ) {
	$group_children = array();

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {

		$child_args = array(
			'post_parent' => $group_id, // The parent id.
			'post_type'   => learndash_get_post_type_slug( 'group' ),
		);

		$children = get_children( $child_args );
		if ( ! empty( $children ) ) {
			foreach ( $children as $child_group ) {
				$group_children[] = $child_group->ID;
				$children2        = learndash_get_group_children( $child_group->ID );
				if ( ! empty( $children2 ) ) {
					$group_children = array_merge( $group_children, $children2 );
				}
			}
		}
	}

	if ( ! empty( $group_children ) ) {
		$group_children = array_map( 'absint', $group_children );
		$group_children = array_unique( $group_children, SORT_NUMERIC );
	}

	return $group_children;
}

/**
 * Validate an array of Group post IDs.
 *
 * @param array $group_ids Array of Groups post IDs to check.
 * @return array validated Group post IDS.
 */
function learndash_validate_groups( $group_ids = array() ) {
	if ( ( is_array( $group_ids ) ) && ( ! empty( $group_ids ) ) ) {
		$groups_query_args = array(
			'post_type'      => learndash_get_post_type_slug( 'group' ),
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post__in'       => $group_ids,
			'posts_per_page' => -1,
		);

		$groups_query = new WP_Query( $groups_query_args );
		if ( ( is_a( $groups_query, 'WP_Query' ) ) && ( property_exists( $groups_query, 'posts' ) ) ) {
			return $groups_query->posts;
		}
	}

	return array();
}

/**
 * Gets the group courses per page setting.
 *
 * @since 3.2.0
 *
 * @param int $group_id Optional. The ID of the group. Default 0.
 *
 * @return int The number of lessons per page or 0.
 */
function learndash_get_group_courses_per_page( $group_id = 0 ) {
	$group_courses_per_page = 0;

	// From the WP > Settings > Reading > Posts per page.
	$group_courses_per_page = (int) get_option( 'posts_per_page' );

	// From the LearnDash > Settings > General > Global Pagination Settings > Shortcodes & Widgets per page.
	$group_courses_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page', $group_courses_per_page );

	// From the LearnDash > Courses > Settings > Global Group Management > Group Table Pagination > Courses per page.
	$group_global_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Groups_Management_Display' );
	if ( ( isset( $group_global_settings['group_pagination_enabled'] ) ) && ( 'yes' === $group_global_settings['group_pagination_enabled'] ) ) {
		if ( isset( $group_global_settings['group_pagination_courses'] ) ) {
			$group_courses_per_page = absint( $group_global_settings['group_pagination_courses'] );
		} else {
			$group_courses_per_page = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
		}
	} else {
		$group_courses_per_page = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
	}

	if ( ! empty( $group_id ) ) {
		$group_settings = learndash_get_setting( intval( $group_id ) );

		if ( ( isset( $group_settings['group_courses_per_page_enabled'] ) ) && ( 'CUSTOM' === $group_settings['group_courses_per_page_enabled'] ) && ( isset( $group_settings['group_courses_per_page_custom'] ) ) ) {
			$group_courses_per_page = absint( $group_settings['group_courses_per_page_custom'] );
		}
	}

	/**
	 * Filters group courses per page.
	 *
	 * @since 3.2.0
	 *
	 * @param int $group_courses_per_page Per page value.
	 * @param int $group_id               Group ID.
	 */
	return apply_filters( 'learndash_group_courses_per_page', $group_courses_per_page, $group_id );
}

/**
 * Gets the group courses order query arguments.
 *
 * @since 3.2.0
 *
 * @param int $group_id Optional. The ID of the group. Default 0.
 *
 * @return array An array of group courses order query arguments.
 */
function learndash_get_group_courses_order( $group_id = 0 ) {
	$group_courses_args = array(
		'order'   => LEARNDASH_DEFAULT_GROUP_ORDER,
		'orderby' => LEARNDASH_DEFAULT_GROUP_ORDERBY,
	);

	$group_global_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Groups_Management_Display' );
	if ( ( isset( $group_global_settings['group_courses_orderby'] ) ) && ( LEARNDASH_DEFAULT_GROUP_ORDERBY !== $group_global_settings['group_courses_orderby'] ) ) {
		$group_courses_args['orderby'] = esc_attr( $group_global_settings['group_courses_orderby'] );
	}
	if ( ( isset( $group_global_settings['group_courses_order'] ) ) && ( LEARNDASH_DEFAULT_GROUP_ORDER !== $group_global_settings['group_courses_order'] ) ) {
		$group_courses_args['order'] = esc_attr( $group_global_settings['group_courses_order'] );
	}

	if ( ! empty( $group_id ) ) {
		$group_settings = learndash_get_setting( $group_id );
		if ( ( isset( $group_settings['group_courses_order_enabled'] ) ) && ( 'on' === $group_settings['group_courses_order_enabled'] ) ) {
			if ( ( isset( $group_settings['group_courses_order'] ) ) && ( ! empty( $group_settings['group_courses_order'] ) ) ) {
				$group_courses_args['order'] = esc_attr( $group_settings['group_courses_order'] );
			}

			if ( ( isset( $group_settings['group_courses_orderby'] ) ) && ( ! empty( $group_settings['group_courses_orderby'] ) ) ) {
				$group_courses_args['orderby'] = esc_attr( $group_settings['group_courses_orderby'] );
			}
		}
	}

	/**
	 * Filters group courses order query arguments.
	 *
	 * @since 3.2.0
	 *
	 * @param array $group_courses_args An array of group courses order arguments.
	 * @param int   $group_id          Group ID.
	 */
	return apply_filters( 'learndash_group_courses_order', $group_courses_args, $group_id );
}


/**
 * Gets the list of enrolled courses for a group.
 *
 * @since 2.1.0
 * @since 4.0.0 Added `$query_args` parameter.
 *
 * @param int   $group_id   Optional. Group ID. Default 0.
 * @param array $query_args Optional. An array of query arguments to get lesson list. Default empty array. (@since 4.0.0).
 *
 * @return array An array of course IDs.
 */
function learndash_get_group_courses_list( $group_id = 0, $query_args = array() ) {
	global $course_pager_results;

	$courses_ids = array();

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {

		if ( ! isset( $query_args['paged'] ) ) {
			$query_args['paged'] = 1;
			if ( isset( $_GET['ld-group-courses-page'] ) ) {
				$query_args['paged'] = absint( $_GET['ld-group-courses-page'] );
			}
		}

		if ( isset( $query_args['num'] ) ) {
			$query_args['per_page'] = intval( $query_args['num'] );
			unset( $query_args['num'] );
		}

		if ( isset( $query_args['posts_per_page'] ) ) {
			if ( ( ! isset( $query_args['per_page'] ) ) || ( empty( $query_args['per_page'] ) ) ) {
				$query_args['per_page'] = intval( $query_args['posts_per_page'] );
			}
			unset( $query_args['posts_per_page'] );
		}

		if ( ! isset( $query_args['per_page'] ) ) {
			$query_args['per_page'] = learndash_get_group_courses_per_page( $group_id );
		}
		$group_courses_order_args = learndash_get_group_courses_order( $group_id );

		$query_args = array(
			'post_type'      => learndash_get_post_type_slug( 'course' ),
			'fields'         => 'ids',
			'posts_per_page' => $query_args['per_page'],
			'paged'          => $query_args['paged'],
			'meta_query'     => array(
				array(
					'key'     => 'learndash_group_enrolled_' . $group_id,
					'compare' => 'EXISTS',
				),
			),
		);
		$query_args = array_merge( $query_args, $group_courses_order_args );

		$query = new WP_Query( $query_args );
		if ( ( is_a( $query, 'WP_Query' ) ) && ( property_exists( $query, 'posts' ) ) ) {
			$course_ids = $query->posts;

			if ( ! isset( $course_pager_results['pager'] ) ) {
				$course_pager_results['pager'] = array();
			}
			$course_pager_results['pager']['paged']       = $query_args['paged'];
			$course_pager_results['pager']['total_items'] = $query->found_posts;
			$course_pager_results['pager']['total_pages'] = $query->max_num_pages;
		}
	}

	return $course_ids;
}

/**
 * Utility function to check if Groups post type is hierarchical.
 *
 * @since 3.2.1
 *
 * @return bool Returns true if hierarchical.
 */
function learndash_is_groups_hierarchical_enabled() {
	$group_hierarchical_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Management_Display', 'group_hierarchical_enabled' );
	if ( 'yes' === $group_hierarchical_enabled ) {
		$group_hierarchical_enabled = true;
	} else {
		$group_hierarchical_enabled = false;
	}

	return $group_hierarchical_enabled;
}

/**
 * Get all Courses having Group associations.
 *
 * @since 3.2.3
 * @return array Array of Course ID or empty array.
 */
function learndash_get_all_courses_with_groups() {
	$query_args = array(
		'post_type'      => learndash_get_post_type_slug( 'course' ),
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '[LD_XXX_GROUP_LIKE_FILTER]',
				'compare' => 'EXISTS',
			),
		),
	);

	add_filter( 'posts_where', 'learndash_filter_by_group_where_filter' );
	$query = new WP_Query( $query_args );
	remove_filter( 'posts_where', 'learndash_filter_by_group_where_filter' );
	if ( ( is_a( $query, 'WP_Query' ) ) && ( property_exists( $query, 'posts' ) ) ) {
		return $query->posts;
	}

	return array();
}

/**
 * Filter by group WHERE filter
 *
 * @since 3.2.3
 *
 * @param string $where WHERE clause.
 */
function learndash_filter_by_group_where_filter( $where ) {
	if ( false !== strpos( $where, '[LD_XXX_GROUP_LIKE_FILTER]' ) ) {
		return str_replace( "meta_key = '[LD_XXX_GROUP_LIKE_FILTER]'", "meta_key LIKE 'learndash_group_enrolled_%'", $where );
	}
}

/**
 * Utility function to check if a Group Leader can manage Groups.
 *
 * @since 3.2.3
 */
function learndash_get_group_leader_manage_groups() {
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_groups_enabled' ) ) {
		return LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_groups_capabilities' );
	}
}

/**
 * Utility function to check if a Group Leader can manage Courses.
 *
 * @since 3.2.3
 */
function learndash_get_group_leader_manage_courses() {
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_enabled' ) ) {
		return LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_capabilities' );
	}
}

/**
 * Utility function to check if a Group Leader can manage Users.
 *
 * @since 3.2.3
 */
function learndash_get_group_leader_manage_users() {
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_users_enabled' ) ) {
		return LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_users_capabilities' );
	}
}

/**
 * Check if the Group Leader can edit the Group or Course posts.
 *
 * Override the default WordPress user capability when editing a Group.
 * See wp-includes/class-wp-user.php for details.
 *
 * @since 3.2.3
 *
 * @param bool|array   $allcaps Array of key/value pairs where keys represent a capability name
 *                              and boolean values represent whether the user has that capability.
 * @param string|array $cap     Required primitive capabilities for the requested capability.
 * @param array        $args    Additional arguments.
 * @param WP_User      $user    WP_User object.
 */
function learndash_group_leader_has_cap_filter( $allcaps, $cap, $args, $user ) {

	global $pagenow;

	if ( in_array( 'edit_posts', $cap, true ) ) {
		/**
		 * If the Group Leader is attempting to manage a comment we enable that
		 * IF they are viewing the comments for an Assignment or Essay.
		 * At this point we are not concerned about other LD post types.
		 */
		if ( ( 'edit-comments.php' === $pagenow ) && ( isset( $_GET['p'] ) ) ) {
			$comment_post = get_post( absint( $_GET['p'] ) );
			if ( ( $comment_post ) && ( is_a( $comment_post, 'WP_Post' ) ) && ( in_array( $comment_post->post_type, array( learndash_get_post_type_slug( 'assignment' ), learndash_get_post_type_slug( 'essay' ) ), true ) ) ) {
				$course_id = get_post_meta( $comment_post->ID, 'course_id', true );
				$course_id = absint( $course_id );
				if ( ( ! empty( $course_id ) ) && ( learndash_check_group_leader_course_user_intersect( get_current_user_id(), $comment_post->post_author, $course_id ) ) ) {
					foreach ( $cap as $cap_slug ) {
						$allcaps[ $cap_slug ] = true;
					}

					return $allcaps;
				}
			}
		}

		if ( in_array( learndash_get_group_leader_manage_courses(), array( 'basic', 'advanced' ), true ) ) {
			/** This filter is documented in includes/ld-groups.php */
			if ( apply_filters( 'learndash_group_leader_has_cap_filter', true, $cap, $args, $user ) ) {
				if ( ! isset( $args[2] ) ) {
					$post_id = get_the_id();
					if ( $post_id ) {
						if ( ( in_array( get_post_type( $post_id ), learndash_get_post_type_slug( array( 'course', 'lesson', 'topic', 'quiz', 'group' ) ), true ) ) ) {
							$args[2] = $post_id;
						}
					}
				}

				if ( ( isset( $args[2] ) ) && ( ! empty( $args[2] ) ) ) {
					foreach ( $cap as $cap_slug ) {
						$allcaps[ $cap_slug ] = true;
					}
				} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
					// Total KLUDGE. When editing in Gutenberg there is a call to /wp/v2/blocks with 'edit' context.
					$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
					if ( '/wp/v2/blocks' === $route ) {
						foreach ( $cap as $cap_slug ) {
							$allcaps[ $cap_slug ] = true;
						}
					}
				}
			}
		}
	} elseif ( in_array( 'edit_others_groups', $cap, true ) ) { // Check if Group Leader can edit Groups they are Leader of.
		if ( ( ! isset( $allcaps['edit_others_groups'] ) ) || ( true !== $allcaps['edit_others_groups'] ) ) {
			if ( 'basic' === learndash_get_group_leader_manage_groups() ) {
				/**
				 * Filter override for Group Leader edit cap.
				 *
				 * @since 3.2.3
				 *
				 * @param bool     $true Always True if user can edit post.
				 * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name
				 *                          and boolean values represent whether the user has that capability.
				 * @param array    $args {
				 *     @type string    $index_0 Requested capability.
				 *     @type int       $index_1 Concerned user ID.
				 *     @type mixed  ...$other   Optional second and further parameters, typically object ID.
				 * } Arguments that accompany the requested capability check.
				 * @param WP_User  $user    The user object.
				 *
				 * @return bool True if Group Leader is allowed to edit post.
				 */
				if ( apply_filters( 'learndash_group_leader_has_cap_filter', true, $cap, $args, $user ) ) {
					/**
					 * During the save post cycle the args[2] is empty. So we can't check if the GL can edit a specific
					 * Group ID. But if we find the 'action' and 'post_ID' POST vars we can check indirectly.
					 */
					if ( ! isset( $args[2] ) ) {
						if ( ( isset( $_POST['action'] ) ) && ( 'editpost' === $_POST['action'] ) ) {
							if ( isset( $_POST['post_ID'] ) ) {
								$args[2] = absint( $_POST['post_ID'] );
							}
						}
					}

					if ( ( isset( $args[2] ) ) && ( in_array( get_post_type( $args[2] ), array( learndash_get_post_type_slug( 'group' ) ), true ) ) ) {
						if ( ( isset( $args[1] ) ) && ( ! empty( $args[1] ) ) ) {
							$gl_group_ids = learndash_get_administrators_group_ids( absint( $args[1] ) );
							if ( ( ! empty( $gl_group_ids ) ) && ( in_array( absint( $args[2] ), $gl_group_ids, true ) ) ) {
								foreach ( $cap as $cap_slug ) {
									$allcaps[ $cap_slug ] = true;
								}
							}
						}
					}
				}
			}
		}
	} // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace -- Explanatory comment follows
	// Check if Group Leader can edit Course or Steps within their Groups.
	elseif ( ( ( in_array( 'edit_others_courses', $cap, true ) ) ) && ( isset( $allcaps['edit_others_courses'] ) ) && ( true !== $allcaps['edit_others_courses'] ) ) {
		if ( 'basic' === learndash_get_group_leader_manage_courses() ) {

			/** This filter is documented in includes/ld-groups.php */
			if ( apply_filters( 'learndash_group_leader_has_cap_filter', true, $cap, $args, $user ) ) {
				/**
				 * During the save post cycle the args[2] is empty. So we can't check if the GL can edit a specific
				 * Course Post ID. But if we find the 'action' and 'post_ID' POST vars we can check indirectly.
				 */
				if ( ! isset( $args[2] ) ) {
					if ( ( isset( $_POST['action'] ) ) && ( 'editpost' === $_POST['action'] ) ) {
						if ( isset( $_POST['post_ID'] ) ) {
							$args[2] = absint( $_POST['post_ID'] );
						}
					}
				}

				if ( ( isset( $args[2] ) ) && ( in_array( get_post_type( $args[2] ), learndash_get_post_types( 'course' ), true ) ) ) {
					if ( get_post_type( $args[2] ) === learndash_get_post_type_slug( 'course' ) ) {
						$courses = array( $args[2] );
					} else {
						$courses = learndash_get_courses_for_step( $args[2], true );
						$courses = array_keys( $courses );
					}

					$leader_group_ids = array();
					if ( ( isset( $args[1] ) ) && ( ! empty( $args[1] ) ) ) {
						$leader_group_ids = learndash_get_administrators_group_ids( absint( $args[1] ) );
					}

					if ( ! empty( $leader_group_ids ) ) {

						$course_group_ids = array();
						foreach ( $courses as $course_id ) {
							$course_group_ids = array_merge( $course_group_ids, learndash_get_course_groups( absint( $course_id ) ) );
						}

						if ( ( ! empty( $leader_group_ids ) ) && ( ! empty( $course_group_ids ) ) ) {
							$common_course_ids = array_intersect( $leader_group_ids, $course_group_ids );
							if ( ! empty( $common_course_ids ) ) {
								$include_caps = true;
								if ( true === $include_caps ) {
									foreach ( $cap as $cap_slug ) {
										$allcaps[ $cap_slug ] = true;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	return $allcaps;
}
add_action(
	'init',
	function () {
		if ( learndash_is_group_leader_user() ) {
			add_filter( 'user_has_cap', 'learndash_group_leader_has_cap_filter', 10, 4 );
		}
	},
	10
);

/**
 * Check if the Group Leader AND User and Course have common Groups.
 *
 * @since 3.4.0
 *
 * @param int $gl_user_id Group Leader User ID.
 * @param int $user_id    User ID.
 * @param int $course_id  Course ID.
 *
 * @return bool true if a common group intersect is determined.
 */
function learndash_check_group_leader_course_user_intersect( $gl_user_id = 0, $user_id = 0, $course_id = 0 ) {

	if ( ( empty( $gl_user_id ) ) || ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return false;
	}

	if ( ! learndash_is_group_leader_user( $gl_user_id ) ) {
		return false;
	}

	$common_group_ids = array();
	// And that the Course is associated with some Groups.
	$course_group_ids = learndash_get_course_groups( $course_id );
	$course_group_ids = array_map( 'absint', $course_group_ids );
	if ( ! empty( $course_group_ids ) ) {
		/**
		 * If the Group Leader can manage all Users or all Groups then return. Note
		 * we are performing this check AFTER we check if the Course is part of a
		 * Group. This is on purpose.
		 */
		if ( ( 'advanced' === learndash_get_group_leader_manage_users() ) || ( 'advanced' === learndash_get_group_leader_manage_groups() ) ) {
			return true;
		}

		// Now check the Group Leader managed Groups...
		$leader_group_ids = learndash_get_administrators_group_ids( $gl_user_id );
		$leader_group_ids = array_map( 'absint', $leader_group_ids );
		if ( ! empty( $leader_group_ids ) ) {
			// ...and the user (post author) Groups...
			$author_group_ids = learndash_get_users_group_ids( $user_id );
			$author_group_ids = array_map( 'absint', $author_group_ids );

			// ...and the course groups have an intersect.
			$common_group_ids = array_intersect( $leader_group_ids, $course_group_ids, $author_group_ids );
			$common_group_ids = array_map( 'absint', $common_group_ids );
		}
	}

	if ( ! empty( $common_group_ids ) ) {
		return true;
	}

	return false;
}

/**
 * Returns message if groups are not public in the admin dashboard
 *
 * @since 3.4.2
 */
function learndash_groups_get_not_public_message() {
	$groups_setting_link = '<a href="' . esc_url( add_query_arg( array( 'page' => 'groups-options' ), admin_url( 'admin.php' ) ) . '#learndash_settings_groups_cpt_cpt_options' ) . '">' . esc_html__( 'Settings', 'learndash' ) . '</a>';

	// translators: placeholders: Groups, link to Group settings page.
	$message = '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html_x( '%1$s are not public, please visit the %2$s page and set them to Public to enable access on the front end.', 'placeholders: Groups, link to Group settings page', 'learndash' ), esc_html( learndash_get_custom_label( 'groups' ) ), $groups_setting_link ) . '</p></div>';

	/**
	 * Filters groups not set to Public message
	 *
	 * @since 3.4.2
	 *
	 * @param string $message The message when groups are not set to Public
	 * @return string $message The message when groups are not set to Public
	 */
	return apply_filters( 'learndash_groups_get_not_public_message', $message );
}

/**
 * Returns true if it's a group post.
 *
 * @param WP_Post|int|null $post Post or Post ID.
 *
 * @since 4.1.0
 *
 * @return bool
 */
function learndash_is_group_post( $post ): bool {
	if ( empty( $post ) ) {
		return false;
	}

	$post_type = is_a( $post, WP_Post::class ) ? $post->post_type : get_post_type( $post );

	return LDLMS_Post_Types::get_post_type_slug( 'group' ) === $post_type;
}

/**
 * Returns group enrollment url.
 *
 * @param WP_Post|int|null $post Post or Post ID.
 *
 * @since 4.1.0
 *
 * @return string
 */
function learndash_get_group_enrollment_url( $post ): string {
	if ( empty( $post ) ) {
		return '';
	}

	if ( is_int( $post ) ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return '';
		}
	}

	$url = get_permalink( $post );

	$settings = learndash_get_setting( $post );

	if ( 'paynow' === $settings['group_price_type'] && ! empty( $settings['group_price_type_paynow_enrollment_url'] ) ) {
		$url = $settings['group_price_type_paynow_enrollment_url'];
	} elseif ( 'subscribe' === $settings['group_price_type'] && ! empty( $settings['group_price_type_subscribe_enrollment_url'] ) ) {
		$url = $settings['group_price_type_subscribe_enrollment_url'];
	}

	/** This filter is documented in includes/course/ld-course-functions.php */
	return apply_filters( 'learndash_group_join_redirect', $url, $post->ID );
}

/**
 * Deletes group leader metadata when a group leader role is changed to another.
 *
 * @since 4.5.0
 */
add_action(
	'set_user_role',
	function( int $user_id, string $role, array $old_roles ) {
		if (
			in_array( 'group_leader', $old_roles, true )
			&& 'group_leader' !== $role
		) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
					$user_id,
					'learndash_group_leaders_%'
				)
			);
		}
	},
	10,
	3
);
