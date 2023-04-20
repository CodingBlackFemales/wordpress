<?php
/**
 * Handles assignment uploads and includes helper functions for assignments
 *
 * @since 2.1.0
 *
 * @package LearnDash\Assignments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore hasassignments .

/**
 * Handles the upload, delete, and mark as complete for the assignment.
 *
 * Fires on `parse_request` hook.
 *
 * @since 2.1.0
 */
function learndash_assignment_process_init() {

	if ( ( isset( $_POST['uploadfile'] ) ) && ( ! empty( $_POST['uploadfile'] ) ) && ( isset( $_POST['post'] ) ) && ( ! empty( $_POST['post'] ) ) && ( isset( $_POST['course_id'] ) && ( ! empty( $_POST['course_id'] ) ) ) ) {
		$course_id = intval( $_POST['course_id'] );
		$post_id   = intval( $_POST['post'] );

		// 1. Verify nonce
		if ( ! wp_verify_nonce( $_POST['uploadfile'], 'uploadfile_' . get_current_user_id() . '_' . $post_id ) ) {
			return;
		}

		// 2. Verify lesson/topic is set to accept assignment uploads. The 'lesson_assignment_upload'
		// should return 'on' if assignment uploads are enabled
		if ( 'on' !== learndash_get_setting( $post_id, 'lesson_assignment_upload' ) ) {
			return;
		}

		// 3. Verify the lesson/topic is from the correct course.
		$courses = learndash_get_courses_for_step( $post_id, true );
		if ( ( empty( $courses ) ) || ( ! isset( $courses[ $course_id ] ) ) ) {
			return;
		}

		// 4. Verify the user is logged in or allow external filtering
		if ( ! is_user_logged_in() ) {
			/**
			 * Filters whether to allow assignment for non logged in users.
			 *
			 * @param boolean $allow_upload Whether to allow assignment upload for non logged in users.
			 * @param int     $course_id    Course ID.
			 * @param int     $post_id      Post ID.
			 */
			if ( ! apply_filters( 'learndash_assignment_upload_user_check', false, $course_id, $post_id ) ) {
				return;
			}
		}

		$file = $_FILES['uploadfiles'];

		if ( ( ! empty( $file['name'][0] ) ) && ( learndash_check_upload( $file, $post_id ) ) ) {
			$file_desc = learndash_fileupload_process( $file, $post_id );
			$file_name = $file_desc['filename'];
			$file_link = $file_desc['filelink'];
			$params    = array(
				'filelink' => $file_link,
				'filename' => $file_name,
			);
		}
	}

	if ( ! empty( $_GET['learndash_delete_attachment'] ) ) {
		$assignment_post = get_post( intval( $_GET['learndash_delete_attachment'] ) );
		if ( ( isset( $assignment_post ) ) && ( $assignment_post instanceof WP_Post ) && ( learndash_get_post_type_slug( 'assignment' ) === $assignment_post->post_type ) ) {
			$current_user_id = get_current_user_id();

			if ( ( $assignment_post->post_author == $current_user_id ) || ( learndash_is_admin_user( $current_user_id ) ) || ( learndash_is_group_leader_of_user( $current_user_id, $assignment_post->post_author ) ) ) {

				$course_id = get_post_meta( $assignment_post->ID, 'course_id', true );
				if ( empty( $course_id ) ) {
					$course_id = learndash_get_course_id( $assignment_post->ID );
				}
				$course_step_id = get_post_meta( $assignment_post->ID, 'lesson_id', true );

				learndash_process_mark_incomplete( $current_user_id, $course_id, $course_step_id );

				/**
				 * Filters whether to force delete the assignment or not.
				 *
				 * @param boolean $force_delete    Whether to force delete assignment or not.
				 * @param int     $assignment_id   Assignment ID.
				 * @param WP_POST $assignment_post Assignment post object.
				 */
				wp_delete_post( $assignment_post->ID, apply_filters( 'learndash_assignment_force_delete', true, $assignment_post->ID, $assignment_post ) );

				update_user_meta(
					get_current_user_id(),
					'ld_assignment_message',
					array(
						array(
							'type'    => 'success',
							'message' => esc_html__( 'Assignment successfully deleted.', 'learndash' ),
						),
					)
				);

				$return_url = remove_query_arg( 'learndash_delete_attachment' );
				learndash_safe_redirect( $return_url );
			}
		}
	}

	if ( ! empty( $_POST['attachment_mark_complete'] ) && ! empty( $_POST['userid'] ) ) {
		$lesson_id       = $_POST['attachment_mark_complete'];
		$current_user_id = get_current_user_id();
		$user_id         = $_POST['userid'];

		if ( ( learndash_is_admin_user( $current_user_id ) ) || ( learndash_is_group_leader_of_user( $current_user_id, $user_id ) ) ) {
			learndash_approve_assignment( $user_id, $lesson_id );
		}
	}
}

add_action( 'parse_request', 'learndash_assignment_process_init', 1 );

/**
 * Gets a list of user's assignments.
 *
 * @since 2.1.0
 * @since 4.5.0 Added optional param $fields.
 *
 * @param int    $post_id   Lesson ID.
 * @param int    $user_id   User ID.
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param string $fields    Optional. Return array of assignment post IDs if set to 'ids'. Default 'all'.
 *
 * @return array Array of post objects or post IDs.
 */
function learndash_get_user_assignments( $post_id, $user_id, $course_id = 0, string $fields = 'all' ) {
	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
	}

	$opt = array(
		'post_type'      => 'sfwd-assignment',
		'posts_per_page' => - 1,
		'author'         => $user_id,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => 'lesson_id',
				'value'   => $post_id,
				'compare' => '=',
			),
			array(
				'key'     => 'course_id',
				'value'   => $course_id,
				'compare' => '=',
			),
		),
		'fields' => $fields,
	);
	return get_posts( $opt );
}

/**
 * Handles assignment uploads.
 *
 * Takes post ID, filename as arguments(We don't want to store BLOB data there).
 *
 * How is this different from learndash_assignment_process_init() ?
 *
 * @since 2.1.0
 *
 * @param int $post_id Post ID.
 * @param int $fname   Assignment file name.
 */
function learndash_upload_assignment_init( $post_id, $fname ) {
	// Initialize an empty array.
	global $wp;

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include ABSPATH . 'wp-includes/pluggable.php';
	}

	$new_assignment_meta = array();
	$current_user        = wp_get_current_user();
	$username            = $current_user->user_login;
	$display_name        = $current_user->display_name;
	$userid              = $current_user->ID;
	$url_link_arr        = wp_upload_dir();
	$url_link            = $url_link_arr['baseurl'];
	$dir_link            = $url_link_arr['basedir'];
	$file_path           = $dir_link . '/assignments/';
	$url_path            = $url_link . '/assignments/' . $fname;

	if ( file_exists( $file_path . $fname ) ) {
		$dest = $url_path;
	} else {
		return;
	}

	update_post_meta( $post_id, 'sfwd_lessons-assignment', $new_assignment_meta );
	$post      = get_post( $post_id );
	$course_id = learndash_get_course_id( $post->ID );

	$assignment_meta = array(
		'file_name'    => $fname,
		'file_link'    => $dest,
		'user_name'    => $username,
		'disp_name'    => $display_name, // cspell:disable-line.
		'file_path'    => rawurlencode( $file_path . $fname ),
		'user_id'      => $current_user->ID,
		'lesson_id'    => $post->ID,
		'course_id'    => $course_id,
		'lesson_title' => $post->post_title,
		'lesson_type'  => $post->post_type,
	);

	$points_enabled = learndash_get_setting( $post, 'lesson_assignment_points_enabled' );

	if ( 'on' === $points_enabled ) {
		$assignment_meta['points'] = 'pending';
	}

	$assignment = array(
		'post_title'   => $fname,
		'post_type'    => learndash_get_post_type_slug( 'assignment' ),
		'post_status'  => 'publish',
		'post_content' => "<a href='" . $dest . "' target='_blank'>" . $fname . '</a>',
		'post_author'  => $current_user->ID,
	);

	$assignment_post_id = wp_insert_post( $assignment );
	$auto_approve       = learndash_get_setting( $post, 'auto_approve_assignment' );

	if ( $assignment_post_id ) {
		foreach ( $assignment_meta as $key => $value ) {
			update_post_meta( $assignment_post_id, $key, $value );
		}

		/**
		 * Fires after the assignment is uploaded.
		 *
		 * @since 2.2.0
		 *
		 * @param int   $assignment_post_id The assignment post id created after the assignment upload.
		 * @param array $assignment_meta    Assignment meta data.
		 */
		do_action( 'learndash_assignment_uploaded', $assignment_post_id, $assignment_meta );

		if ( empty( $auto_approve ) ) {

			update_user_meta(
				get_current_user_id(),
				'ld_assignment_message',
				array(
					array(
						'type'    => 'success',
						'message' => esc_html__( 'Assignment successfully uploaded.', 'learndash' ),
					),
				)
			);

			learndash_safe_redirect( get_permalink( $post->ID ), 303 );
		}
	}

	if ( ! empty( $auto_approve ) ) {
		learndash_approve_assignment( $current_user->ID, $post_id, $assignment_post_id );

		// assign full points if auto approve & points are enabled.
		if ( 'on' === $points_enabled ) {
			$points = learndash_get_setting( $post, 'lesson_assignment_points_amount' );
			update_post_meta( $assignment_post_id, 'points', intval( $points ) );
		}

		learndash_get_next_lesson_redirect( $post );
	}
}

/**
 * Handles whether the comments should be open for assignments.
 *
 * Fires on `comments_open` hook.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param boolean $open    Whether the current post is open for comments.
 * @param int     $post_id The post ID.
 *
 * @return boolean True if the comments should be open otherwise false.
 */
function learndash_assignments_comments_open( $open, $post_id ) {
	if ( learndash_get_post_type_slug( 'assignment' ) === get_post_type( $post_id ) ) {
		$comment_status = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'comment_status' );
		if ( 'yes' === $comment_status ) {

			if ( empty( $open ) ) {
				if ( is_numeric( $post_id ) ) {
					global $wpdb;
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->posts SET comment_status = %s WHERE ID = %d",
							'open',
							$post_id
						)
					);
					$open = true;
				}
			}
		} else {
			$open = false;
		}
	}

	return $open;
}

add_filter( 'comments_open', 'learndash_assignments_comments_open', 10, 2 );

/**
 * Enables comments when adding a new assignment.
 *
 * Fires on `wp_insert_post_data` hook.
 *
 * @since 2.1.0
 *
 * @param array $data An array of slashed post data.
 *
 * @return array $data post data
 */
function learndash_assignments_comments_on( $data ) {
	if ( learndash_get_post_type_slug( 'assignment' ) === $data['post_type'] ) {
		$data['comment_status'] = 'open';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'learndash_assignments_comments_on' );

/**
 * Cleans file name on upload.
 *
 * @since 2.1.0
 *
 * @param string $string Name of the file.
 *
 * @return string Returns filename after cleaning.
 */
function learndash_clean_filename( $string ) {
	$string = htmlentities( $string, ENT_QUOTES, 'UTF-8' );
	$string = preg_replace( '~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $string );
	$string = html_entity_decode( $string, ENT_QUOTES, 'UTF-8' );
	$string = preg_replace( array( '~[^0-9a-z.]~i', '~[ -]+~' ), ' ', $string );
	$string = str_replace( ' ', '_', $string );
	return trim( $string, ' -' );
}

/**
 * Handles file upload process.
 *
 * @since 2.1.0
 *
 * @param array $uploadfiles An array of uploaded files data.
 * @param int   $post_id    The assignment ID.
 *
 * @return array Returns file data after upload such as file name and file URL.
 */
function learndash_fileupload_process( $uploadfiles, $post_id ) {

	if ( is_array( $uploadfiles ) ) {

		foreach ( $uploadfiles['name'] as $key => $value ) {
			// look only for uploaded files.
			if ( 0 == $uploadfiles['error'][ $key ] ) {

				$file_tmp = $uploadfiles['tmp_name'][ $key ];

				// clean filename.
				$filename = learndash_clean_filename( $uploadfiles['name'][ $key ] );

				// extract extension.
				if ( ! function_exists( 'wp_get_current_user' ) ) {
					include ABSPATH . 'wp-includes/pluggable.php';
				}

				// Before this function we have already validated the file extension/type via the function learndash_check_upload
				// @2.5.4.
				$file_title = pathinfo( basename( $filename ), PATHINFO_FILENAME );
				$file_ext   = pathinfo( basename( $filename ), PATHINFO_EXTENSION );

				$upload_dir      = wp_upload_dir();
				$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
				$upload_url_base = $upload_dir['baseurl'];
				$upload_dir_path = $upload_dir_base . '/assignments';
				$upload_url_path = $upload_url_base . '/assignments/';

				if ( ! file_exists( $upload_dir_path ) ) {
					if ( is_writable( dirname( $upload_dir_path ) ) ) {
						wp_mkdir_p( $upload_dir_path );
					} else {
						die( esc_html__( 'Unable to write to UPLOADS directory. Is this directory writable by the server?', 'learndash' ) );
					}
				}

				// Add an index.php file to prevent directory browsing.
				$_index = trailingslashit( $upload_dir_path ) . 'index.php';
				if ( ! file_exists( $_index ) ) {
					learndash_put_directory_index_file( $_index );
				}

				$file_time = microtime( true ) * 100;
				$filename  = sprintf( 'assignment_%d_%d_%s.%s', $post_id, $file_time, $file_title, $file_ext );

				/**
				 * Filters the assignment upload filename.
				 *
				 * @since 3.2.0
				 *
				 * @param string $filename   File name.
				 * @param int    $post_id    Post ID.
				 * @param float  $file_time  Unix timestamp.
				 * @param string $file_title Title of the file.
				 * @param string $file_ext   File extension.
				 */
				$filename = apply_filters(
					'learndash_assignment_upload_filename',
					$filename,
					$post_id,
					$file_time,
					$file_title,
					$file_ext
				);

				/**
				 * Check if the filename already exist in the directory and rename the
				 * file if necessary
				 */
				$i = 0;

				$file_title = pathinfo( basename( $filename ), PATHINFO_FILENAME );
				$file_ext   = pathinfo( basename( $filename ), PATHINFO_EXTENSION );

				while ( file_exists( $upload_dir_path . '/' . $filename ) ) {
					$i++;
					$filename = $file_title . '_' . $i . '.' . $file_ext;
				}

				$file_dest   = $upload_dir_path . '/' . $filename;
				$destination = $upload_url_path . $filename;

				/**
				 * Check write permissions
				 */
				if ( ! is_writeable( $upload_dir_path ) ) {
					die( esc_html__( 'Unable to write to directory. Is this directory writable by the server?', 'learndash' ) );
				}

				/**
				 * Save temporary file to uploads dir
				 */
				if ( ! @move_uploaded_file( $file_tmp, $file_dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Better not to touch it for now.
					echo sprintf(
						// translators: placeholder: temporary file, file destination.
						esc_html__( 'Error, the file %1$s could not be moved to: %2$s', 'learndash' ),
						esc_html( $file_tmp ),
						esc_html( $file_dest )
					);
					continue;
				}

				/**
				 * Add upload meta to database
				 */
				learndash_upload_assignment_init( $post_id, $filename, $file_dest );
				$file_desc             = array();
				$file_desc['filename'] = $filename;
				$file_desc['filelink'] = $destination;
				return $file_desc;
			}
		}
	}

	return array();
}

/**
 * Utility function to check whether a lesson has an assignment.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post The assignment `WP_Post` object.
 *
 * @return boolean
 */
function learndash_lesson_hasassignments( $post ) {
	$post_id     = $post->ID;
	$assign_meta = get_post_meta( $post_id, '_' . $post->post_type, true );

	if ( ! empty( $assign_meta[ $post->post_type . '_lesson_assignment_upload' ] ) ) {
		$val = $assign_meta[ $post->post_type . '_lesson_assignment_upload' ];

		if ( 'on' === $val ) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Marks assignment approved by assignment ID.
 *
 * @since 2.1.0
 *
 * @param int $assignment_id Assignment ID.
 *
 * @return boolean Returns true if the assignment is approved otherwise false.
 */
function learndash_approve_assignment_by_id( $assignment_id ) {
	$assignment_post = get_post( $assignment_id );
	$user_id         = $assignment_post->post_author;
	$lesson_id       = get_post_meta( $assignment_post->ID, 'lesson_id', true );
	return learndash_approve_assignment( $user_id, $lesson_id, $assignment_id );
}



/**
 * Marks assignment approved by user ID and lesson ID.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param int $user_id            User ID.
 * @param int $lesson_id          Lesson ID.
 * @param int $assignment_post_id Optional. Assignment post ID. Default 0.
 *
 * @return boolean Returns true if the assignment is approved otherwise false.
 */
function learndash_approve_assignment( $user_id, $lesson_id, $assignment_post_id = 0 ) {

	/**
	 * Filters whether an assignment should be approved or not.
	 *
	 * @since 2.1.0
	 *
	 * @param boolean $approve            Whether assignment should be approved or not.
	 * @param int     $user_id            User ID.
	 * @param int     $lesson_id          Lesson ID.
	 * @param int     $assignment_post_id Assignment ID. @since 2.5.5
	 */
	$learndash_approve_assignment = apply_filters( 'learndash_approve_assignment', true, $user_id, $lesson_id, $assignment_post_id );

	if ( $learndash_approve_assignment ) {
		$assignment_course_id            = get_post_meta( $assignment_post_id, 'course_id', true );
		$learndash_process_mark_complete = learndash_process_mark_complete( $user_id, $lesson_id, null, $assignment_course_id );
		if ( $learndash_process_mark_complete ) {
			// This query needs to be reworked to NOT query all posts with that meta_key. Better off using WP_Query.
			global $wpdb;
			$assignment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", 'lesson_id', $lesson_id ) );

			foreach ( $assignment_ids as $assignment_id ) {
				if ( ( intval( $assignment_post_id ) != 0 ) && ( intval( $assignment_post_id ) != intval( $assignment_id ) ) ) {
					continue;
				}

				$assignment = get_post( $assignment_id );
				if ( $assignment->post_author == $user_id ) {
					learndash_assignment_mark_approved( $assignment_id );

					/**
					 * Fires after assignment is approved
					 *
					 * @since 2.2.0
					 *
					 * @param int $assignment_id Assignment ID.
					 */
					do_action( 'learndash_assignment_approved', $assignment_id );
				}
			}
		}

		return $learndash_process_mark_complete;
	}

	return false;
}



/**
 * Updates assignments post meta with approval status.
 *
 * @since 2.1.0
 *
 * @param int $assignment_id Assignment ID.
 */
function learndash_assignment_mark_approved( $assignment_id ) {
	update_post_meta( $assignment_id, 'approval_status', 1 );
}

/**
 * Gets assignments approval status.
 *
 * @since 2.1.0
 *
 * @param int $assignment_id Assignment ID.
 *
 * @return int|false Status of assignment approval. Returns 1 if the assignment is approved.
 */
function learndash_is_assignment_approved_by_meta( $assignment_id ) {
	return get_post_meta( $assignment_id, 'approval_status', true );
}

/**
 * Checks if the assignment is approved or not.
 *
 * @since 2.1.0
 *
 * @param int $assignment_id Assignment ID.
 *
 * @return boolean|string Returns true if assignment approved otherwise false.
 */
function learndash_is_assignment_approved( $assignment_id ) {
	$assignment = get_post( $assignment_id );

	if ( empty( $assignment->ID ) ) {
		return '';
	}

	$lesson_id = learndash_get_lesson_id( $assignment->ID );

	if ( empty( $lesson_id ) ) {
		return '';
	}

	$lesson_completed = learndash_is_lesson_notcomplete( $assignment->post_author, array( $lesson_id => 1 ) );

	if ( empty( $lesson_completed ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Manages the permissions to view the assignment.
 *
 * Only allow admins, group leaders, and assignment owners to see the assignment.
 * Fires on `wp` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 */
function learndash_assignment_permissions() {
	if ( is_singular( learndash_get_post_type_slug( 'assignment' ) ) ) {

		$user_id = get_current_user_id();
		$post    = get_post();

		if ( learndash_is_admin_user( $user_id ) ) {
			return;
		} elseif ( learndash_is_group_leader_user( $user_id ) ) {
			/**
			 * For the Group Leader we check for common groups between
			 * Leader + Author + Course
			 */
			$course_id = get_post_meta( $post->ID, 'course_id', true );
			$course_id = absint( $course_id );

			if ( learndash_check_group_leader_course_user_intersect( $user_id, $post->post_author, $course_id ) ) {
				return;
			}
		} elseif ( absint( $user_id ) === absint( $post->post_author ) ) {
			return;
		}

		/**
		 * Filters Assignment permission redirect URL.
		 *
		 * @param string $redirect_url Redirect URL.
		 */
		learndash_safe_redirect( apply_filters( 'learndash_assignment_permissions_redirect_url', get_bloginfo( 'url' ) ) );
	} elseif ( ( is_home() ) || ( is_front_page() ) ) {
		/**
		 * Prevents the user from forcing the query on the home page
		 * with http://www.site.com?post_type=sfwd-assignment to access an archive.
		 *
		 * It would be nice if this is controllable via WP register_post_type() settings.
		 *
		 * See LEARNDASH-6390 for more details.
		 */
		if ( get_query_var( 'post_type', '' ) === learndash_get_post_type_slug( 'assignment' ) ) {
			// If this is an attempt we redirect them to the hme URL without the post_type query arg.
			$redirect_to_url = get_bloginfo( 'url' );
			if ( ! empty( $redirect_to_url ) ) {
				learndash_safe_redirect( $redirect_to_url );
			}
		}
	}
}

add_action( 'wp', 'learndash_assignment_permissions' );

/**
 * Registers assignments custom post type.
 *
 * Fires on `init` hook.
 *
 * @since 2.1.0
 */
function learndash_register_assignment_upload_type() {

	$exclude_from_search = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'exclude_from_search' );
	if ( 'yes' === $exclude_from_search ) {
		$exclude_from_search = true;
	} else {
		$exclude_from_search = false;
	}
	$publicly_queryable = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'publicly_queryable' );
	if ( 'yes' === $publicly_queryable ) {
		$publicly_queryable = true;
	} else {
		$publicly_queryable = false;
	}
	$comment_status = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'comment_status' );
	if ( 'yes' === $comment_status ) {
		$comment_status = true;
	} else {
		$comment_status = false;
	}

	$show_in_rest = LearnDash_REST_API::enabled( learndash_get_post_type_slug( 'assignment' ) ) || LearnDash_REST_API::gutenberg_enabled( learndash_get_post_type_slug( 'assignment' ) );

	$labels = array(
		'name'               => esc_html__( 'Assignments', 'learndash' ),
		'singular_name'      => esc_html__( 'Assignment', 'learndash' ),
		'edit_item'          => esc_html__( 'Edit Assignment', 'learndash' ),
		'view_item'          => esc_html__( 'View Assignment', 'learndash' ),
		'view_items'         => esc_html__( 'View Assignments', 'learndash' ),
		'search_items'       => esc_html__( 'Search Assignments', 'learndash' ),
		'not_found'          => esc_html__( 'No assignment found', 'learndash' ),
		'not_found_in_trash' => esc_html__( 'No assignment found in Trash', 'learndash' ),
		'parent_item_colon'  => esc_html__( 'Parent:', 'learndash' ),
		'menu_name'          => esc_html__( 'Assignments', 'learndash' ),
	);

	if ( learndash_is_admin_user() ) {
		$show_in_admin_bar = false;
	} elseif ( learndash_is_group_leader_user() ) {
		$show_in_admin_bar = false;
	} else {
		$show_in_admin_bar = false;
	}

	$supports = array( 'title', 'comments', 'author' );
	if ( true !== $comment_status ) {
		$supports = array_diff( $supports, array( 'comments' ) );
	}

	$rewrite = array( 'slug' => 'assignment' );
	if ( true !== $publicly_queryable ) {
		$rewrite = false;
	}

	$args = array(
		'labels'              => $labels,
		'hierarchical'        => false,
		'supports'            => $supports,
		'public'              => $publicly_queryable,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => $show_in_admin_bar,
		'publicly_queryable'  => $publicly_queryable,
		'exclude_from_search' => $exclude_from_search,
		'has_archive'         => false,
		'show_in_rest'        => $show_in_rest,
		'query_var'           => $publicly_queryable,
		'rewrite'             => $rewrite,
		'capability_type'     => 'assignment',
		'capabilities'        => array(
			'read_post'              => 'read_assignment',
			'publish_posts'          => 'publish_assignments',
			'edit_posts'             => 'edit_assignments',
			'edit_others_posts'      => 'edit_others_assignments',
			'delete_posts'           => 'delete_assignments',
			'delete_others_posts'    => 'delete_others_assignments',
			'read_private_posts'     => 'read_private_assignments',
			'edit_post'              => 'edit_assignment',
			'delete_post'            => 'delete_assignment',
			'edit_published_posts'   => 'edit_published_assignments',
			'delete_published_posts' => 'delete_published_assignments',
		),
		'map_meta_cap'        => true,
	);

	/**
	 * Filters the custom post type arguments.
	 *
	 * @param array  $cpt_args  Custom post type arguments.
	 * @param string $post_type Post type.
	 */
	$args = apply_filters( 'learndash-cpt-options', $args, 'sfwd-assignment' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Better to keep it this way for now.

	register_post_type( 'sfwd-assignment', $args );
}

add_action( 'init', 'learndash_register_assignment_upload_type' );

/**
 * Setups capabilities for assignments custom post type.
 *
 * Fires on `admin_init` hook.
 *
 * @since 2.1.0
 *
 * @return void
 */
function learndash_init_assignments_capabilities(): void {
	$admin_role = get_role( 'administrator' );

	if ( $admin_role instanceof WP_Role ) {
		$admin_role_capabilities = array(
			'edit_assignment',
			'edit_assignments',
			'edit_others_assignments',
			'publish_assignments',
			'read_assignment',
			'read_private_assignments',
			'delete_assignment',
			'edit_published_assignments',
			'delete_others_assignments',
			'delete_published_assignments',
		);

		foreach ( $admin_role_capabilities as $capability ) {
			if ( ! $admin_role->has_cap( $capability ) ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	$group_leader_role = get_role( 'group_leader' );

	if ( $group_leader_role instanceof WP_Role ) {
		$group_leader_role_capabilities = array(
			'read_assignment',
			'edit_assignments',
			'edit_others_assignments',
			'edit_published_assignments',
			'delete_others_assignments',
			'delete_published_assignments',
		);

		foreach ( $group_leader_role_capabilities as $capability ) {
			if ( ! $group_leader_role->has_cap( $capability ) ) {
				$group_leader_role->add_cap( $capability );
			}
		}
	}
}

add_action( 'admin_init', 'learndash_init_assignments_capabilities' );

/**
 * Deletes assignment file when assignment post is deleted.
 *
 * Fires on `before_delete_post` hook.
 *
 * @since 2.1.0
 *
 * @param int $post_id Assignment post ID.
 */
function learndash_before_delete_assignment( $post_id ) {

	if ( ( ! empty( $post_id ) ) && ( learndash_get_post_type_slug( 'assignment' ) === get_post_type( $post_id ) ) ) {
		$file_path = get_post_meta( $post_id, 'file_path', true );
		if ( ! empty( $file_path ) ) {
			$file_path = rawurldecode( $file_path );

			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
		}
	}
}

add_action( 'before_delete_post', 'learndash_before_delete_assignment' );

/**
 * Returns the number of points awarded for an assignment.
 *
 * Displayed on single lessons under the submitted assignment
 *
 * @param int $assignment_id ID of the assignment.
 *
 * @return string Returns the number of points awarded string.
 */
function learndash_assignment_points_awarded( $assignment_id ) {
	$points_enabled = learndash_assignment_is_points_enabled( $assignment_id );

	if ( $points_enabled ) {
		$current = learndash_get_assignment_points_awarded( $assignment_id );

		/**
		 * Filters the output of the awarded points of an assignment.
		 *
		 * @param string $output  Output of the awarded points.
		 * @param string $current Points awarded values or translatable string.
		 */
		return apply_filters(
			'learndash_points_awarded_output',
			sprintf(
				// translators: placeholder: points awarded values (30/100) 30%.
				esc_html_x( 'Points Awarded: %s', 'placeholder: points awarded values (30/100) 30%', 'learndash' ),
				$current
			),
			$current
		);
	}

	return '';
}

/**
 * Gets the value of the awarded assignment points.
 *
 * If the assignment hasn't been approved or graded, the translatable string 'Pending' is returned.
 * Otherwise, the awarded points and percentage achieved are returned.
 *
 * @since 2.6.4
 *
 * @param int $assignment_id ID of the assignment.
 *
 * @return string Returns points awarded.
 */
function learndash_get_assignment_points_awarded( $assignment_id ) {
	$current = get_post_meta( $assignment_id, 'points', true );

	// We can't compare against the actual post meta value because it was a translatable string until 2.6.4.
	if ( ( ! empty( $current ) ) && ( ! is_numeric( $current ) ) ) {
		return esc_html__( 'Pending', 'learndash' );
	}

	if ( is_numeric( $current ) ) {
		$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
		$max_points             = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
		$max_points             = intval( $max_points );
		if ( ! empty( $max_points ) ) {
			$percentage = ( intval( $current ) / intval( $max_points ) ) * 100;
			$percentage = round( $percentage, 2 );
		} else {
			$percentage = 0.00;
		}

		/**
		 * Filters the output format of the awarded points of an assignment.
		 *
		 * @param string $output_format Output Format of awarded points.
		 * @param string $current       Achieved points.
		 * @param int    $max_points    Maximum points.
		 * @param float  $percentage    Percentage of achieved points/maximum points.
		 */
		return apply_filters(
			'learndash_points_awarded_output_format',
			sprintf(
				'(%1$d/%2$d) %3$d&#37; ',
				$current,
				$max_points,
				$percentage
			),
			$current,
			$max_points,
			$percentage
		);
	}

	return '';
}

/**
 * Checks if the points are enabled for the assignment.
 *
 * @param int|WP_Post $assignment The assignment `WP_Post` object or ID.
 *
 * @return boolean Returns true if the points are enabled otherwise false.
 */
function learndash_assignment_is_points_enabled( $assignment ) {
	if ( is_a( $assignment, 'WP_Post' ) ) {
		$assignment_id = $assignment->ID;
	} else {
		$assignment_id = intval( $assignment );
	}

	$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
	$points_enabled         = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_enabled' );

	if ( 'on' === $points_enabled ) {
		return true;
	}

	return false;
}

/**
 * Converts the file size shorthand to bytes.
 *
 * @param int|string $val Optional. Shorthand notation for file size like 1024M. Default 0.
 *
 * @return int Returns the bytes after converting from shorthand.
 */
function learndash_return_bytes_from_shorthand( $val = 0 ) {

	$units = array(
		'KB' => 1,
		'MB' => 2,
		'GB' => 3,
		'K'  => 1,
		'M'  => 2,
		'G'  => 3,
		'B'  => 0,
	);

	if ( ! empty( $val ) ) {
		$val = trim( $val );

		foreach ( $units as $unit_notation => $unit_multiplier ) {
			$val_unit = substr( $val, -( strlen( $unit_notation ) ) );
			if ( strtoupper( $val_unit ) == $unit_notation ) {
				$val_number = substr( $val, 0, strlen( $val ) - strlen( $unit_notation ) );

				$val_bytes = $val_number * pow( 1024, $unit_multiplier );

				return $val_bytes;
			}
		}
	}

	return $val;
}

/**
 * Checks whether the assignment upload is successful or not.
 *
 * @param array $uploadfiles An array of uploaded files data.
 * @param int   $post_id    Optional. The Assignment ID. Default 0.
 *
 * @return boolean
 */
function learndash_check_upload( $uploadfiles = array(), $post_id = 0 ) {
	$post_settings = array();

	if ( ( is_array( $uploadfiles ) ) && ( ! empty( $post_id ) ) ) {
		$limit_file_exts = array();
		$limit_file_size = 0;

		$post_settings = learndash_get_setting( $post_id );
		if ( ( isset( $post_settings['assignment_upload_limit_size'] ) ) && ( ! empty( $post_settings['assignment_upload_limit_size'] ) ) ) {
			$limit_file_size = $post_settings['assignment_upload_limit_size'];
			$limit_file_size = learndash_return_bytes_from_shorthand( $limit_file_size );

		} else {
			$limit_file_size = wp_max_upload_size();
		}

		if ( ( empty( $limit_file_size ) ) || ( intval( $uploadfiles['size'][0] ) > $limit_file_size ) ) {
			update_user_meta(
				get_current_user_id(),
				'ld_assignment_message',
				array(
					array(
						'type'    => 'error',
						'message' => esc_html__( 'Uploaded file size exceeds allowed limit.', 'learndash' ),
					),
				)
			);
			return false;
		}

		$limit_file_exts = learndash_get_allowed_upload_mime_extensions_for_post( $post_id );
		$filetype_mime   = wp_check_filetype( $uploadfiles['name'][0], $limit_file_exts );

		if ( ( empty( $filetype_mime ) ) || ( empty( $filetype_mime['ext'] ) ) || ( empty( $filetype_mime['type'] ) ) || ( ! $limit_file_exts[ strtolower( $filetype_mime['ext'] ) ] ) ) {
			update_user_meta(
				get_current_user_id(),
				'ld_assignment_message',
				array(
					array(
						'type'    => 'error',
						'message' => esc_html__( 'The uploaded file type is not allowed.', 'learndash' ),
					),
				)
			);
			return false;
		}

		if ( isset( $post_settings['assignment_upload_limit_count'] ) ) {
			$assignment_upload_limit_count = intval( $post_settings['assignment_upload_limit_count'] );
			if ( $assignment_upload_limit_count > 0 ) {
				$assignments = learndash_get_user_assignments( $post_id, get_current_user_id() );
				if ( ( ! empty( $assignments ) ) && ( count( $assignments ) >= $assignment_upload_limit_count ) ) {
					update_user_meta(
						get_current_user_id(),
						'ld_assignment_message',
						array(
							array(
								'type'    => 'error',
								'message' => esc_html__( 'Number of allowed assignment uploads reached.', 'learndash' ),
							),
						)
					);
					return false;
				}
			}
		}
	}

	/**
	 * Filter to allow external approval of Assignment upload.
	 *
	 * @since 3.4.0
	 *
	 * @param bool  $assignment_approved If Assignment has been approved. Default true.
	 * @param array $uploadfiles         Array of uploaded files.
	 * @param int   $post_id             Assignment Post ID.
	 * @param array $post_settings       Array of Assignment Post Settings.
	 *
	 * @return bool true if upload is approved. false if not.
	 *
	 * The external processor should set a usermeta entry 'ld_assignment_message' with the
	 * error message. This will be shown to the user.
	 *
	 * Example:
	 * update_user_meta(
	 *    get_current_user_id(),
	 *    'ld_assignment_message',
	 *    array(
	 *       array(
	 *          'type'    => 'error',
	 *          'message' => esc_html__( 'Number of allowed assignment uploads reached.', 'learndash' ),
	 *       ),
	 *    )
	 * );
	 */
	return (bool) apply_filters( 'learndash_assignment_check_upload', true, $uploadfiles, $post_id, $post_settings );
}

/**
 * Checks whether all assignments for specified step and user are approved.
 *
 * @since 4.5.0
 *
 * @param array<int> $assignment_ids An array of assignment IDs.
 * @param int        $step_id        The ID of the lesson or topic to get user assignments from.
 * @param int        $user_id        Optional. The user ID, gets current user if not specified.
 *
 * @return boolean True if all user assignments for step are approved, otherwise false.
 */
function learndash_assignment_list_approved( array $assignment_ids, int $step_id, int $user_id ): bool {
	if ( ! is_array( $assignment_ids ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( ! empty( $step_id ) ) {
		$course_id      = absint( learndash_get_course_id( $step_id ) );
		$assignment_ids = learndash_get_user_assignments( $step_id, $user_id, $course_id, 'ids' );
	}

	foreach ( $assignment_ids as $assignment ) {
		$approval_status = learndash_is_assignment_approved_by_meta( $assignment );

		if ( ! $approval_status ) {
			return false;
		}
	}

	return true;
}
