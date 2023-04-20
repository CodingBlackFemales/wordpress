<?php
/**
 * Deprecated functions from LD 3.4.1
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_assignment_migration' ) ) {
	/**
	 * Migrates the assignments from post meta to assignments custom post type.
	 *
	 * Fires on `admin_init` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.1
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @since 2.1.0
	 */
	function learndash_assignment_migration() {

		if ( ! learndash_is_admin_user() ) {
			return;
		}

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.1' );
		}

		global $wpdb;
		$old_assignment_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sfwd_lessons-assignment'" );

		if ( ! empty( $old_assignment_ids ) && ! empty( $old_assignment_ids[0] ) ) {

			foreach ( $old_assignment_ids as $post_id ) {
				$assignment_meta_data = get_post_meta( $post_id, 'sfwd_lessons-assignment', true );

				if ( ! empty( $assignment_meta_data ) && ! empty( $assignment_meta_data['assignment'] ) ) {
					$assignment_data      = $assignment_meta_data['assignment'];
					$post                 = get_post( $post_id );
					$assignment_posts_ids = array();

					if ( ! empty( $assignment_data ) ) {
						$error = false;

						foreach ( $assignment_data as $k => $v ) {

							if ( empty( $v['file_name'] ) ) {
								continue;
							}

							$fname     = $v['file_name'];
							$dest      = $v['file_link'];
							$username  = $v['user_name'];
							$dispname  = $v['disp_name'];
							$file_path = $v['file_path'];

							$user_id = 0;
							if ( ! empty( $v['user_name'] ) ) {
								$user = get_user_by( 'login', $v['user_name'] );
								if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
									$user_id = $user->ID;
								}
							}

							$course_id = learndash_get_course_id( $post->ID );

							$assignment_meta = array(
								'file_name'    => $fname,
								'file_link'    => $dest,
								'user_name'    => $username,
								'disp_name'    => $dispname,
								'file_path'    => $file_path,
								'user_id'      => $user_id,
								'lesson_id'    => $post->ID,
								'course_id'    => $course_id,
								'lesson_title' => $post->post_title,
								'lesson_type'  => $post->post_type,
								'migrated'     => '1',
							);

							$assignment = array(
								'post_title'   => $fname,
								'post_type'    => learndash_get_post_type_slug( 'assignment' ),
								'post_status'  => 'publish',
								'post_content' => "<a href='" . $dest . "' target='_blank'>" . $fname . '</a>',
								'post_author'  => $user_id,
							);

							$assignment_post_id = wp_insert_post( $assignment );

							if ( $assignment_post_id ) {
								$assignment_posts_ids[] = $assignment_post_id;

								foreach ( $assignment_meta as $key => $value ) {
									update_post_meta( $assignment_post_id, $key, $value );
								}

								if ( learndash_is_assignment_approved( $assignment_post_id ) === true ) {
									learndash_approve_assignment_by_id( $assignment_post_id );
								}
							} else {
								$error = true;

								foreach ( $assignment_posts_ids as $assignment_posts_id ) {
									wp_delete_post( $assignment_posts_id, true );
								}

								break;
							}
						}

						if ( ! $error ) {
							global $wpdb;
							$wpdb->query(
								$wpdb->prepare(
									"UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d",
									'sfwd_lessons-assignment_migrated',
									'sfwd_lessons-assignment',
									$post_id
								)
							);
						}
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'learndash_get_assignments_list' ) ) {
	/**
	 * Gets the list of all assignments.
	 *
	 * @todo  first argument not used
	 * @since 2.1.0
	 * @deprecated 3.4.1
	 *
	 * @param WP_Post $post WP_Post object( Not used ).
	 *
	 * @return array An array of post objects.
	 */
	function learndash_get_assignments_list( $post ) {

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.1' );
		}

		$posts = get_posts( 'post_type=sfwd-assignment&posts_per_page=-1' );

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $key => $p ) {
				$meta = get_post_meta( $p->ID, '', true );

				foreach ( $meta as $meta_key => $value ) {

					if ( is_string( $value ) || is_numeric( $value ) ) {
						$posts[ $key ]->{$meta_key} = $value;
					} elseif ( is_string( $value[0] ) || is_numeric( $value[0] ) ) {
						$posts[ $key ]->{$meta_key} = $value[0];
					}

					if ( 'file_path' === $meta_key ) {
						$posts[ $key ]->{$meta_key} = rawurldecode( $posts[ $key ]->{$meta_key} );
					}
				}
			}
		}

		return $posts;
	}
}

if ( ! function_exists( 'learndash_all_group_leader_ids' ) ) {
	/**
	 * Gets the list of all group leader user IDs.
	 *
	 * @since 2.1.2
	 * @deprecated 3.4.1
	 *
	 * @return array An array of group leader user IDs.
	 */
	function learndash_all_group_leader_ids() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.1' );
		}

		$group_leader_user_ids = array();
		$group_leader_users    = learndash_all_group_leaders();
		if ( ! empty( $group_leader_users ) ) {
			$group_leader_user_ids = wp_list_pluck( $group_leader_users, 'ID' );
		}
		return $group_leader_user_ids;
	}
}

if ( ! function_exists( 'learndash_all_group_leaders' ) ) {
	/**
	 * Gets the list of all group leader user objects.
	 *
	 * @since 2.1.2
	 * @deprecated 3.4.1
	 *
	 * @return array An array of group leaders user objects.
	 */
	function learndash_all_group_leaders() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.1' );
		}

		$transient_key      = 'learndash_group_leaders';
		$group_user_objects = LDLMS_Transients::get( $transient_key );
		if ( false === $group_user_objects ) {

			$user_query_args = array(
				'role'    => 'group_leader',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			);

			$user_query = new WP_User_Query( $user_query_args );
			if ( isset( $user_query->results ) ) {
				$group_user_objects = $user_query->results;
			} else {
				$group_user_objects = array();
			}

			LDLMS_Transients::set( $transient_key, $group_user_objects, MINUTE_IN_SECONDS );
		}
		return $group_user_objects;
	}
}
