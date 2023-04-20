<?php
/**
 * Adds ability to have "Essay / Open Answer" questions in Wp Pro Quiz
 *
 * @since 2.2.0
 *
 * @package LearnDash\Essay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the essay post type.
 *
 * Holds the responses of essay questions submitted by the user.
 * Fires on `init` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_post_type() {

	$labels = array(
		'name'                     => esc_html_x( 'Submitted Essays', 'Post Type General Name', 'learndash' ),
		'singular_name'            => esc_html_x( 'Submitted Essay', 'Post Type Singular Name', 'learndash' ),
		'menu_name'                => esc_html__( 'Submitted Essays', 'learndash' ),
		'name_admin_bar'           => esc_html__( 'Submitted Essays', 'learndash' ),
		'parent_item_colon'        => esc_html__( 'Parent Submitted Essay:', 'learndash' ),
		'all_items'                => esc_html__( 'All Submitted Essays', 'learndash' ),
		'add_new_item'             => esc_html__( 'Add New Submitted Essay', 'learndash' ),
		'add_new'                  => esc_html__( 'Add New', 'learndash' ),
		'new_item'                 => esc_html__( 'New Submitted Essay', 'learndash' ),
		'edit_item'                => esc_html__( 'Edit Submitted Essay', 'learndash' ),
		'update_item'              => esc_html__( 'Update Submitted Essay', 'learndash' ),
		'view_item'                => esc_html__( 'View Submitted Essay', 'learndash' ),
		'view_items'               => esc_html__( 'View Submitted Essays', 'learndash' ),
		'search_items'             => esc_html__( 'Search Submitted Essays', 'learndash' ),
		'not_found'                => esc_html__( 'Submitted Essay Not found', 'learndash' ),
		'not_found_in_trash'       => esc_html__( 'Submitted Essay Not found in Trash', 'learndash' ),
		'item_published'           => esc_html__( 'Submitted Essay Published', 'learndash' ),
		'item_published_privately' => esc_html__( 'Submitted Essay Published Privately', 'learndash' ),
		'item_reverted_to_draft'   => esc_html__( 'Submitted Essay Reverted to Draft', 'learndash' ),
		'item_scheduled'           => esc_html__( 'Submitted Essay Scheduled', 'learndash' ),
		'item_updated'             => esc_html__( 'Submitted Essay Updated', 'learndash' ),

	);

	$capabilities = array(
		'edit_essay'          => 'edit_essay',
		'read_essay'          => 'read_essay',
		'delete_essay'        => 'delete_essay',
		'edit_essays'         => 'edit_essays',
		'edit_others_essays'  => 'edit_others_essays',
		'publish_essays'      => 'publish_essays',
		'read_private_essays' => 'read_private_essays',
	);

	if ( learndash_is_admin_user() ) {
		$show_in_admin_bar = false;
	} elseif ( learndash_is_group_leader_user() ) {
		$show_in_admin_bar = false;
	} else {
		$show_in_admin_bar = false;
	}

	$show_in_rest = LearnDash_REST_API::enabled( learndash_get_post_type_slug( 'essay' ) ) || LearnDash_REST_API::gutenberg_enabled( learndash_get_post_type_slug( 'essay' ) );

	$args = array(
		'label'               => esc_html__( 'sfwd-essays', 'learndash' ),
		// translators: quiz, question.
		'description'         => sprintf( esc_html_x( 'Submitted essays via a %1$s %2$s.', 'placeholder: quiz, question', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'question' ) ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'comments', 'author' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_admin_bar'   => $show_in_admin_bar,
		'query_var'           => true,
		'rewrite'             => array( 'slug' => 'essay' ),
		'menu_position'       => 5,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'show_in_rest'        => $show_in_rest,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'essay',
		'capabilities'        => $capabilities,
		'map_meta_cap'        => true,
	);
	/** This filter is documented in includes/ld-assignment-uploads.php */
	$args = apply_filters( 'learndash-cpt-options', $args, 'sfwd-essays' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Better to keep it this way for now.

	register_post_type( 'sfwd-essays', $args );
}

add_action( 'init', 'learndash_register_essay_post_type' );



/**
 * Adds the essay post type capabilities.
 *
 * Add essay capabilities to administrators and group leaders.
 * Fires on `admin_init` hook.
 *
 * @since 2.2.0
 */
function learndash_add_essay_caps() {
	$admin_role = get_role( 'administrator' );
	if ( ( $admin_role ) && ( $admin_role instanceof WP_Role ) ) {

		$cap = $admin_role->has_cap( 'delete_others_essays' );
		if ( empty( $cap ) ) {
			$admin_role->add_cap( 'edit_essays' );
			$admin_role->add_cap( 'edit_others_essays' );
			$admin_role->add_cap( 'publish_essays' );
			$admin_role->add_cap( 'read_essays' );
			$admin_role->add_cap( 'read_private_essays' );
			$admin_role->add_cap( 'delete_essays' );
			$admin_role->add_cap( 'edit_published_essays' );
			$admin_role->add_cap( 'delete_others_essays' );
			$admin_role->add_cap( 'delete_published_essays' );
		}
	}

	$group_leader_role = get_role( 'group_leader' );
	if ( ( $group_leader_role ) && ( $group_leader_role instanceof WP_Role ) ) {
		$group_leader_role->add_cap( 'edit_essays' );
		$group_leader_role->add_cap( 'edit_others_essays' );
		$group_leader_role->add_cap( 'publish_essays' );
		$group_leader_role->add_cap( 'read_essays' );
		$group_leader_role->add_cap( 'read_private_essays' );
		$group_leader_role->add_cap( 'delete_essays' );
		$group_leader_role->add_cap( 'edit_published_essays' );
		$group_leader_role->add_cap( 'delete_others_essays' );
		$group_leader_role->add_cap( 'delete_published_essays' );
	}
}

add_action( 'admin_init', 'learndash_add_essay_caps' );



/**
 * Maps the meta capabilities for essay post type.
 *
 * Fires on `map_meta_cap` hook.
 *
 * @since 2.2.0
 *
 * @param array  $caps    An Array of the user's capabilities.
 * @param string $cap     Capability name.
 * @param int    $user_id The User ID.
 * @param array  $args    Optional. Adds the context to the cap. Typically the object ID. Default empty array.
 *
 * @return array An array of user's capabilities.
 */
function learndash_map_metacap_essays( $caps, $cap, $user_id, $args = array() ) {
	if ( ! is_string( $cap ) ) {
		return $caps;
	}

	/* If editing, deleting, or reading a essays, get the post and post type object. */
	if ( 'edit_essay' == $cap || 'delete_essay' == $cap || 'read_essay' == $cap ) {

		// Ensure $args is valid.
		if ( ( ! is_array( $args ) ) || ( ! isset( $args[0] ) ) ) {
			return $caps;
		}

		$post = get_post( $args[0] );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $caps;
		}

		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	}

	/* If editing a essay, assign the required capability. */
	if ( 'edit_essay' == $cap ) {
		if ( $user_id == $post->post_author ) {
			$caps[] = $post_type->cap->edit_posts;
		} else {
			$caps[] = $post_type->cap->edit_others_posts;
		}
	} elseif ( 'delete_essay' == $cap ) { /* If deleting a essay, assign the required capability. */
		if ( $user_id == $post->post_author ) {
			$caps[] = $post_type->cap->delete_posts;
		} else {
			$caps[] = $post_type->cap->delete_others_posts;
		}
	} elseif ( 'read_essay' == $cap ) { /* If reading a private essay, assign the required capability. */
		if ( 'private' != $post->post_status ) {
			$caps[] = 'read';
		} elseif ( $user_id == $post->post_author ) {
			$caps[] = 'read';
		} else {
			$caps[] = $post_type->cap->read_private_posts;
		}
	}

	/* Return the capabilities required by the user. */

	return $caps;
}

add_filter( 'map_meta_cap', 'learndash_map_metacap_essays', 10, 4 );



/**
 * Registers the 'Graded' and 'Not Graded' post status.
 *
 * Fires on `init` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_post_status() {
	register_post_status(
		'graded',
		array(
			'label'                     => esc_html_x( 'Graded', 'Custom Essay post type status: Graded', 'learndash' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: placeholder: Graded Essay count.
			'label_count'               => _n_noop( 'Graded <span class="count">(%s)</span>', 'Graded <span class="count">(%s)</span>', 'learndash' ),
		)
	);

	register_post_status(
		'not_graded',
		array(
			'label'                     => esc_html_x( 'Not Graded', 'Custom Essay post type status: Not Graded', 'learndash' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: placeholder: Not Graded Essay count.
			'label_count'               => _n_noop( 'Not Graded <span class="count">(%s)</span>', 'Not Graded <span class="count">(%s)</span>', 'learndash' ),
		)
	);
}

add_action( 'init', 'learndash_register_essay_post_status' );


/**
 * Manages the permissions for the essay post type.
 *
 * Only allow admins, group leaders, and essay owners to see the assignment.
 * Fires on `wp` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.2.1
 */
function learndash_essay_permissions() {

	if ( is_singular( learndash_get_post_type_slug( 'essay' ) ) ) {
		$can_view_file = false;

		$post = get_post();
		if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'essay' ) === $post->post_type ) ) {
			$user_id = get_current_user_id();

			if ( ! empty( $user_id ) ) {
				if ( ( learndash_is_admin_user( $user_id ) ) || ( $post->post_author == $user_id ) ) {
					$can_view_file = true;
				} elseif ( learndash_is_group_leader_user( $user_id ) ) {
					/**
					 * For the Group Leader we check for common groups between
					 * Leader + Author + Course
					 */
					$course_id = get_post_meta( $post->ID, 'course_id', true );
					$course_id = absint( $course_id );

					if ( learndash_check_group_leader_course_user_intersect( $user_id, $post->post_author, $course_id ) ) {
						$can_view_file = true;
					}
				}
			}
		}

		if ( true === $can_view_file ) {
			$uploaded_file = get_post_meta( $post->ID, 'upload', true );
			if ( ( ! empty( $uploaded_file ) ) && ( ! strstr( $post->post_content, $uploaded_file ) ) ) {
				$quiz_essay_upload_link = '<p><a target="_blank" href="' . esc_url( $uploaded_file ) . '">' . esc_html__( 'View uploaded file', 'learndash' ) . '</a></p>';

				/**
				 * Filters quiz essay upload link HTML output.
				 *
				 * @deprecated 4.5.0
				 *
				 * @param string $upload_link Essay upload link HTML output.
				 */
				$quiz_essay_upload_link = apply_filters_deprecated(
					'learndash-quiz-essay-upload-link',
					array( $quiz_essay_upload_link ),
					'4.5.0',
					'learndash_quiz_essay_upload_link'
				);

				/**
				 * Filters quiz essay upload link HTML output.
				 *
				 * @since 4.5.0
				 *
				 * @param string $upload_link Essay upload link HTML output.
				 */
				$quiz_essay_upload_link = apply_filters( 'learndash_quiz_essay_upload_link', $quiz_essay_upload_link );

				$post->post_content .= $quiz_essay_upload_link;
			}
			return;
		} else {
			if ( empty( $user_id ) ) {
				$current_url     = remove_query_arg( 'test' );
				$redirect_to_url = wp_login_url( esc_url( $current_url ), true );
			} else {
				$redirect_to_url = get_bloginfo( 'url' );
			}
			/**
			 * Filters the URL to redirect a user if it does not have permission to view the essay.
			 *
			 * @param string $redirect_url Redirect URL.
			 */
			$redirect_to_url = apply_filters( 'learndash_essay_permissions_redirect_url', $redirect_to_url );
			if ( ! empty( $redirect_to_url ) ) {
				learndash_safe_redirect( $redirect_to_url );
			}
		}
	} elseif ( ( is_home() ) || ( is_front_page() ) ) {
		/**
		 * Prevents the user from forcing the query on the home page
		 * with http://www.site.com?post_type=sfwd-essays to access an archive.
		 *
		 * It would be nice if this is controllable via WP register_post_type() settings.
		 *
		 *  See LEARNDASH-6389 for more details.
		 */
		if ( get_query_var( 'post_type', '' ) === learndash_get_post_type_slug( 'essay' ) ) {
			// If this is an attempt we redirect them to the hme URL without the post_type query arg.
			$redirect_to_url = get_bloginfo( 'url' );
			if ( ! empty( $redirect_to_url ) ) {
				learndash_safe_redirect( $redirect_to_url );
			}
		}
	}
}

add_action( 'wp', 'learndash_essay_permissions' );

/**
 * Adds a new essay response.
 *
 * Called from `LD_QuizPro::checkAnswers()` via AJAX.
 *
 * @since 2.2.0
 *
 * @param string                   $response      Essay response.
 * @param WpProQuiz_Model_Question $this_question Pro quiz question object.
 * @param WpProQuiz_Model_Quiz     $quiz          Pro Quiz object.
 * @param array|null               $post_data     Optional. Quiz information and answers. Default null.
 *
 * @return boolean|int|WP_Error Returns essay ID or `WP_Error` if the essay could not be created.
 */
function learndash_add_new_essay_response( $response, $this_question, $quiz, $post_data = null ) {
	if ( ! is_a( $this_question, 'WpProQuiz_Model_Question' ) || ! is_a( $quiz, 'WpProQuiz_Model_Quiz' ) ) {
		return false;
	}

	$user = wp_get_current_user();

	// essay args defaults.
	$essay_args = array(
		'post_title'  => $this_question->getTitle(),
		'post_status' => 'draft',
		'post_type'   => 'sfwd-essays',
		'post_author' => $user->ID,
	);

	$essay_data = $this_question->getAnswerData();
	$essay_data = array_shift( $essay_data );

	// switch on grading progression in order to set post status.
	switch ( $essay_data->getGradingProgression() ) {
		case '':
		case 'not-graded-none':
			$essay_args['post_status'] = 'not_graded';
			break;
		case 'not-graded-full':
			$essay_args['post_status'] = 'not_graded';
			break;
		case 'graded-full':
			$essay_args['post_status'] = 'graded';
			break;
	}

	$essay_args['post_status'] = 'draft';

	// switch on graded type to handle the response
	// used a switch in case we add more types.
	switch ( $essay_data->getGradedType() ) {
		case 'text':
			$essay_args['post_content'] = wp_kses(
				$response,
				/**
				 * Filters list of allowed html tags in essay content.
				 *
				 * Used in allowed_html parameter of `wp_kses` function.
				 *
				 * @param array $allowed_tags An array of allowed HTML tags in essay content.
				 */
				apply_filters( 'learndash_essay_new_allowed_html', wp_kses_allowed_html( 'post' ) )
			);
			break;
		case 'upload':
			$essay_args['post_content'] = esc_html__( 'See upload below.', 'learndash' );
	}

	/**
	 * Filters new essay submission `wp_insert_post` arguments.
	 *
	 * @param array $essay_args An array of essay arguments.
	 */
	$essay_args = apply_filters( 'learndash_new_essay_submission_args', $essay_args );
	$essay_id   = wp_insert_post( $essay_args );

	if ( ! empty( $essay_id ) ) {
		if ( ( isset( $post_data['quiz_id'] ) ) && ( ! empty( $post_data['quiz_id'] ) ) ) {
			$quiz_id = absint( $post_data['quiz_id'] );
		} else {
			$quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $this_question->getQuizId() );
		}

		if ( isset( $post_data['course_id'] ) ) {
			$course_id = intval( $post_data['course_id'] );
			if ( ! empty( $course_id ) ) {
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $quiz_id );
			} else {
				$lesson_id = 0;
			}
		} else {
			$course_id = learndash_get_course_id( $quiz_id );
			$lesson_id = learndash_get_lesson_id( $quiz_id );
		}

		update_post_meta( $essay_id, 'question_id', $this_question->getId() );
		update_post_meta( $essay_id, 'quiz_pro_id', $this_question->getQuizId() );
		update_post_meta( $essay_id, 'quiz_id', $this_question->getQuizId() );

		update_post_meta( $essay_id, 'course_id', $course_id );
		update_post_meta( $essay_id, 'lesson_id', $lesson_id );

		update_post_meta( $essay_id, 'quiz_post_id', $quiz->getPostId() );
		update_post_meta( $essay_id, 'question_post_id', $this_question->getQuestionPostId() );

		if ( 'upload' == $essay_data->getGradedType() ) {
			update_post_meta( $essay_id, 'upload', esc_url( $response ) );
		}
	}

	/**
	 * Fires after a new essay is submitted.
	 *
	 * @param int   $essay_id  The new Essay ID created after essay submission.
	 * @param array $essay_arg An array of essay arguments.
	 */
	do_action( 'learndash_new_essay_submitted', $essay_id, $essay_args );

	return $essay_id;
}

/**
 * Gets the essay data for this particular submission
 *
 * Loop through all the quizzes and return the quiz that matches as soon as it's found
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id     Quiz ID.
 * @param int     $question_id Question ID.
 * @param WP_Post $essay       The `WP_Post` essay object.
 *
 * @return mixed The submitted essay data.
 */
function learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay ) {
	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );
	if ( ( ! empty( $users_quiz_data ) ) && ( is_array( $users_quiz_data ) ) ) {
		if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
			$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
		} else {
			$essay_quiz_time = null;
		}

		foreach ( $users_quiz_data as $quiz_data ) {
			// We check for a match on the quiz time from the essay postmeta first.
			// If the essay_quiz_time is not empty and does NOT match then continue.
			if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
				continue;
			}
			if ( empty( $quiz_data['pro_quizid'] ) || $quiz_id != $quiz_data['pro_quizid'] || ! isset( $quiz_data['has_graded'] ) || false == $quiz_data['has_graded'] ) {
				continue;
			}

			if ( ( isset( $quiz_data['graded'] ) ) && ( ! empty( $quiz_data['graded'] ) ) ) {
				foreach ( $quiz_data['graded'] as $key => $graded_question ) {
					if ( ( $key == $question_id ) && ( $essay->ID == $graded_question['post_id'] ) ) {
						return $quiz_data['graded'][ $key ];
					}
				}
			}
		}
	}
}

/**
 * Updates the user's submitted essay data.
 *
 * Finds the essay in this particular quiz attempt in the user's meta and updates its data.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id         Quiz ID.
 * @param int     $question_id     Question ID.
 * @param WP_Post $essay           The `WP_Post` essay object.
 * @param array   $submitted_essay Submitted essay data.
 */
function learndash_update_submitted_essay_data( $quiz_id, $question_id, $essay, $submitted_essay ) {
	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

	if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
		$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
	} else {
		$essay_quiz_time = null;
	}

	$quizdata_changed = array();

	foreach ( $users_quiz_data as $quiz_key => $quiz_data ) {
		// We check for a match on the quiz time from the essay postmeta first.
		// If the essay_quiz_time is not empty and does NOT match then continue.
		if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
			continue;
		}

		if ( $quiz_id != $quiz_data['pro_quizid'] || ! isset( $quiz_data['has_graded'] ) || false == $quiz_data['has_graded'] ) {
			continue;
		}

		foreach ( $quiz_data['graded'] as $question_key => $graded_question ) {
			if ( ( $question_key == $question_id ) && ( $essay->ID == $graded_question['post_id'] ) ) {
				$users_quiz_data[ $quiz_key ]['graded'][ $question_key ] = $submitted_essay;
				if ( ( isset( $submitted_essay['status'] ) ) && ( 'graded' === $submitted_essay['status'] ) ) {
					$quizdata_changed[] = $users_quiz_data[ $quiz_key ];
				}
			}
		}
	}

	update_user_meta( $essay->post_author, '_sfwd-quizzes', $users_quiz_data );

	/**
	 * Fires after the essay response data is updated.
	 *
	 * @param int     $quiz_id         Quiz ID.
	 * @param int     $question_id     Question ID.
	 * @param WP_Post $essay           WP_Post object for essay.
	 * @param array   $submitted_essay An array of submitted essay data.
	 */
	do_action( 'learndash_essay_response_data_updated', $quiz_id, $question_id, $essay, $submitted_essay );
}

/**
 * Updates the user's quiz data.
 *
 * Finds this particular quiz attempt in the user's meta and updates its data.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id         Quiz ID.
 * @param int     $question_id     Question ID.
 * @param array   $updated_scoring An array of updated quiz scoring data.
 * @param WP_Post $essay           The `WP_Post` essay object.
 */
function learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring, $essay ) {
	$affected_quiz_keys = array();

	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

	if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
		$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
	} else {
		$essay_quiz_time = null;
	}

	// We need to find the user meta quiz to matches the essay being scored.
	foreach ( $users_quiz_data as $quiz_key => $quiz_data ) {

		// We check for a match on the quiz time from the essay postmeta first.
		// If the essay_quiz_time is not empty and does NOT match then continue.
		if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
			continue;
		}

		if ( ( $quiz_id != $quiz_data['pro_quizid'] ) || ( ! isset( $quiz_data['has_graded'] ) ) || ( false == $quiz_data['has_graded'] ) ) {
			continue;
		}

		if ( ( ! isset( $quiz_data['graded'][ $question_id ]['post_id'] ) ) || ( $quiz_data['graded'][ $question_id ]['post_id'] != $essay->ID ) ) {
			continue;
		}

		$affected_quiz_keys[] = $quiz_key;

		// update total score.
		$users_quiz_data[ $quiz_key ]['score'] = $users_quiz_data[ $quiz_key ]['score'] + $updated_scoring['score_difference'];

		// update total points.
		$users_quiz_data[ $quiz_key ]['points'] = $users_quiz_data[ $quiz_key ]['points'] + $updated_scoring['points_awarded_difference'];

		// update total score percentage.
		$updated_percentage                         = ( $users_quiz_data[ $quiz_key ]['points'] / $users_quiz_data[ $quiz_key ]['total_points'] ) * 100;
		$users_quiz_data[ $quiz_key ]['percentage'] = round( $updated_percentage, 2 );

		// update passing score.
		$quizmeta                             = get_post_meta( $quiz_data['quiz'], '_sfwd-quiz', true );
		$passingpercentage                    = intVal( $quizmeta['sfwd-quiz_passingpercentage'] );
		$users_quiz_data[ $quiz_key ]['pass'] = ( $users_quiz_data[ $quiz_key ]['percentage'] >= $passingpercentage ) ? 1 : 0;

		learndash_update_quiz_statistics( $quiz_id, $question_id, $updated_scoring, $essay, $users_quiz_data[ $quiz_key ] );
		learndash_update_quiz_activity( $essay->post_author, $users_quiz_data[ $quiz_key ] );
	}

	update_user_meta( $essay->post_author, '_sfwd-quizzes', $users_quiz_data );

	if ( ! empty( $affected_quiz_keys ) ) {
		foreach ( $affected_quiz_keys as $quiz_key ) {
			if ( isset( $users_quiz_data[ $quiz_key ] ) ) {
				$send_quiz_completed = true;

				if ( ( isset( $users_quiz_data[ $quiz_key ]['has_graded'] ) ) && ( true === $users_quiz_data[ $quiz_key ]['has_graded'] ) ) {
					if ( ( isset( $users_quiz_data[ $quiz_key ]['graded'] ) ) && ( ! empty( $users_quiz_data[ $quiz_key ]['graded'] ) ) ) {
						foreach ( $users_quiz_data[ $quiz_key ]['graded'] as $grade_item ) {
							if ( ( isset( $grade_item['status'] ) ) && ( 'graded' !== $grade_item['status'] ) ) {
								$send_quiz_completed = false;
							}
						}
					}
				}
				if ( true === $send_quiz_completed ) {
					if ( isset( $users_quiz_data[ $quiz_key ]['course'] ) ) {
						$course_id = intval( $users_quiz_data[ $quiz_key ]['course'] );
					} else {
						$course_id = learndash_get_course_id( $essay->ID );
					}

					learndash_process_mark_complete( $essay->post_author, $users_quiz_data[ $quiz_key ]['quiz'], false, $course_id );

					/** This action is documented in includes/ld-users.php */
					do_action( 'learndash_quiz_completed', $users_quiz_data[ $quiz_key ], get_user_by( 'ID', $essay->post_author ) );
				}
			}
		}
	}

	/**
	 * Fires after the essay quiz data is updated.
	 *
	 * @param int     $quiz_id         Quiz ID.
	 * @param int     $question_id     Question ID.
	 * @param array   $updated_scoring An array of updated essay scoring data.
	 * @param WP_Post $essay           WP_Post object for essay.
	 */
	do_action( 'learndash_essay_quiz_data_updated', $quiz_id, $question_id, $updated_scoring, $essay );
}

/**
 * Updates the quiz activity for a user.
 *
 * @since 2.3.0
 *
 * @param int   $user_id   User ID.
 * @param array $quiz_data An array of quiz activity data to be updated.
 */
function learndash_update_quiz_activity( $user_id = 0, $quiz_data = array() ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_data ) ) ) {

		$quiz_data_meta = $quiz_data;

		// Remove many fields that we either don't need or are duplicate of the main table columns.
		unset( $quiz_data_meta['quiz'] );
		unset( $quiz_data_meta['pro_quizid'] );
		unset( $quiz_data_meta['time'] );
		unset( $quiz_data_meta['completed'] );
		unset( $quiz_data_meta['started'] );

		if ( '-' == $quiz_data_meta['rank'] ) {
			unset( $quiz_data_meta['rank'] );
		}

		if ( true == $quiz_data['pass'] ) {
			$quiz_data_pass = true;
		} else {
			$quiz_data_pass = false;
		}

		learndash_update_user_activity(
			array(
				'course_id'          => ( isset( $quiz_data['course'] ) ) ? intval( $quiz_data['course'] ) : 0,
				'post_id'            => $quiz_data['quiz'],
				'user_id'            => $user_id,
				'activity_type'      => 'quiz',
				'activity_status'    => $quiz_data_pass,
				'activity_started'   => $quiz_data['started'],
				'activity_completed' => $quiz_data['completed'],
				'activity_meta'      => $quiz_data_meta,
			)
		);
	}
}

/**
 * Updates the quiz statistics for the given quiz attempt.
 *
 * Updates the score when the essay grading is adjusted, I ran this through manual SQL queries
 * because WpProQuiz doesn't offer an elegant way to grab a particular question and update it.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id           Quiz ID.
 * @param int     $question_id       Question ID.
 * @param array   $updated_quiz_data Updated quiz statistics data.
 * @param WP_Post $essay             The `WP_Post` essay object.
 * @param array   $users_quiz_data   User quiz data.
 */
function learndash_update_quiz_statistics( $quiz_id, $question_id, $updated_quiz_data, $essay, $users_quiz_data ) {
	global $wpdb;

	if ( ( isset( $users_quiz_data['statistic_ref_id'] ) ) && ( ! empty( $users_quiz_data['statistic_ref_id'] ) ) ) {
		$ref_id = absint( $users_quiz_data['statistic_ref_id'] );
	} else {
		$ref_id = $wpdb->get_var(
			$wpdb->prepare(
				'
						SELECT statistic_ref_id
						FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) . ' WHERE quiz_id = %d AND user_id = %d
					',
				$quiz_id,
				$essay->post_author
			)
		);

		$ref_id = absint( $ref_id );
	}

	$row = $wpdb->get_results(
		$wpdb->prepare(
			'
					SELECT *
					FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic' ) . ' WHERE statistic_ref_id = %d AND question_id = %d
				',
			$ref_id,
			$question_id
		)
	);

	if ( empty( $row ) ) {
		return;
	}

	if ( $updated_quiz_data['updated_question_score'] > 0 ) {
		$correct_count   = 1;
		$incorrect_count = 0;
	} else {
		$correct_count   = 0;
		$incorrect_count = 1;
	}

	$update = $wpdb->update(
		LDLMS_DB::get_table_name( 'quiz_statistic' ),
		array(
			'correct_count'   => $correct_count,
			'incorrect_count' => $incorrect_count,
			'points'          => $updated_quiz_data['updated_question_score'],
		),
		array(
			'statistic_ref_id' => $ref_id,
			'question_id'      => $question_id,
		),
		array( '%d', '%d', '%d' ),
		array( '%d', '%d' )
	);

	/**
	 * Fires after the essay question stats are updated.
	 */
	do_action( 'learndash_essay_question_stats_updated' );
}

/**
 * Handles the AJAX file upload for an essay question.
 *
 * Fires on `learndash_upload_essay` AJAX action.
 *
 * @since 2.2.0
 *
 * Runs checks for needing information, or will die and send an error back to browser
 */
function learndash_upload_essay() {

	if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['question_id'] ) || ! isset( $_FILES['essayUpload'] ) ) {
		wp_send_json_error();
		die();
	}

	$nonce       = $_POST['nonce'];
	$question_id = intval( $_POST['question_id'] );
	if ( empty( $question_id ) ) {
		wp_send_json_error();
		die();
	}

	/**
	 * Changes in v2.5.4 to include the question_id as part of the nonce
	 */
	if ( ! wp_verify_nonce( $nonce, 'learndash-upload-essay-' . $question_id ) ) {
		wp_send_json_error();
		die( 'Security check' );
	} else {

		if ( ! is_user_logged_in() ) {
			/**
			 * Filters whether to allow essay upload or not if the user is not logged in.
			 *
			 * @param boolean $allow_upload Whether to allow upload.
			 * @param int     $question_id  ID of the essay question.
			 */
			if ( ! apply_filters( 'learndash_essay_upload_user_check', false, $question_id ) ) {
				wp_send_json_error();
				die();
			}
		}

		$file_desc = learndash_essay_fileupload_process( $_FILES['essayUpload'], $question_id );

		if ( ! empty( $file_desc ) ) {
			wp_send_json_success( $file_desc );
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unknown error.', 'learndash' ),
				)
			);
		}
		die();
	}
}

add_action( 'wp_ajax_learndash_upload_essay', 'learndash_upload_essay' );
add_action( 'wp_ajax_nopriv_learndash_upload_essay', 'learndash_upload_essay' );


/**
 * Handles the file uploads for the essays.
 *
 * @since 2.2.0
 *
 * @param array $uploadfiles  An array of uploaded files data.
 * @param int   $question_id Question ID.
 *
 * @return array An array of file data like file name and link.
 */
function learndash_essay_fileupload_process( $uploadfiles, $question_id ) {
	if ( is_array( $uploadfiles ) ) {

		// look only for uploaded files.
		if ( 0 == $uploadfiles['error'] ) {

			$file_tmp = $uploadfiles['tmp_name'];

			// clean filename.
			$filename = learndash_clean_filename( $uploadfiles['name'] );

			// extract extension.
			if ( ! function_exists( 'wp_get_current_user' ) ) {
				include ABSPATH . 'wp-includes/pluggable.php';
			}

			// current user.
			$user = get_current_user_id();

			$limit_file_exts = learndash_get_allowed_upload_mime_extensions_for_post( $question_id );

			// get file info.
			// @fixme: wp checks the file extension....
			$filetype = wp_check_filetype( basename( $filename ), $limit_file_exts );
			if ( ( empty( $filetype ) ) || ( empty( $filetype['ext'] ) ) || ( empty( $filetype['type'] ) ) || ( ! $limit_file_exts[ strtolower( $filetype['ext'] ) ] ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid essay uploaded file type.', 'learndash' ),
					)
				);
				die();
			}

			$filetype['ext'] = strtolower( $filetype['ext'] );

			$file_title = pathinfo( $filename, PATHINFO_FILENAME );
			$file_time  = microtime( true ) * 100;

			$filename = sprintf( 'question_%d_%d_%s.%s', $question_id, $file_time, $file_title, $filetype['ext'] );
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$filename        = apply_filters( 'learndash_essay_upload_filename', $filename, $question_id, $file_title, $filetype['ext'] );
			$upload_dir      = wp_upload_dir();
			$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
			$upload_url_base = $upload_dir['baseurl'];
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$upload_dir_path = $upload_dir_base . apply_filters( 'learndash_essay_upload_dirbase', '/essays', $filename, $upload_dir );
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$upload_url_path = $upload_url_base . apply_filters( 'learndash_essay_upload_urlbase', '/essays/', $filename, $upload_dir );

			if ( ! file_exists( $upload_dir_path ) ) {
				if ( is_writable( dirname( $upload_dir_path ) ) ) {
					wp_mkdir_p( $upload_dir_path );
				} else {
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Unable to write to UPLOADS directory. Is this directory writable by the server?', 'learndash' ),
						)
					);
					die();
				}
			}

			// Add an index.php file to prevent directory browsing.
			$_index = trailingslashit( $upload_dir_path ) . 'index.php';
			if ( ! file_exists( $_index ) ) {
				file_put_contents( $_index, '//LearnDash is THE Best LMS' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- It's okay here.
			}

			$file_title = pathinfo( basename( $filename ), PATHINFO_FILENAME );
			$file_ext   = pathinfo( basename( $filename ), PATHINFO_EXTENSION );

			/**
			 * Check if the filename already exist in the directory and rename the
			 * file if necessary
			 */
			$i = 0;

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
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Unable to write to directory. Is this directory writable by the server?', 'learndash' ),
					)
				);
				die();
			}

			/**
			 * Save temporary file to uploads dir
			 */
			if ( ! @move_uploaded_file( $file_tmp, $file_dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Better not to touch it for now.
				wp_send_json_error(
					array(
						'message' => esc_html__( 'The uploaded file could not be move to the destination directory.', 'learndash' ),
					)
				);
				die();
			}

			$file_desc             = array();
			$file_desc['filename'] = $filename;
			$file_desc['filelink'] = $destination;
			$file_desc['message']  = esc_html__( 'Essay upload success.', 'learndash' );

			return $file_desc;
		}
	}
	return array();
}


/**
 * Deletes the uploaded file when essay post is deleted.
 *
 * Fires on `before_delete_post` hook.
 *
 * @since 2.5.0
 *
 * @param int $post_id Post ID.
 */
function learndash_before_delete_essay( $post_id ) {

	if ( ( ! empty( $post_id ) ) && ( 'sfwd-essays' == get_post_type( $post_id ) ) ) {
		$file_path = get_post_meta( $post_id, 'upload', true );
		if ( ! empty( $file_path ) ) {
			$file_path = basename( $file_path );

			$url_link_arr = wp_upload_dir();
			$file_path    = trailingslashit( str_replace( '\\', '/', $url_link_arr['basedir'] ) ) . 'essays/' . basename( $file_path );
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
		}
	}
}

add_action( 'before_delete_post', 'learndash_before_delete_essay' );


/**
 * Updates the essays post meta with a reference to the quiz attempt user meta.
 *
 * Fires on `learndash_quiz_submitted` hook.
 *
 * @since 3.1.0
 *
 * @param array   $quizdata Optional. An array of quiz attempt data. Default empty array.
 * @param WP_User $user     The `WP_User` instance.
 */
function learndash_quiz_submitted_update_essay( $quizdata, $user ) {
	if ( is_array( $quizdata ) ) {
		if ( ( isset( $quizdata['time'] ) ) && ( ! empty( $quizdata['time'] ) ) ) {
			if ( ( isset( $quizdata['has_graded'] ) ) && ( true === $quizdata['has_graded'] ) ) {
				if ( ( isset( $quizdata['graded'] ) ) && ( ! empty( $quizdata['graded'] ) ) ) {
					foreach ( $quizdata['graded'] as $question_id => $graded_data ) {
						if ( isset( $graded_data['post_id'] ) ) {
							$essay_post_id = absint( $graded_data['post_id'] );
							if ( ! empty( $essay_post_id ) ) {

								// Update the Essay post_status.
								if ( isset( $graded_data['status'] ) ) {
									$essay_post = array(
										'ID'          => $essay_post_id,
										'post_status' => esc_attr( $graded_data['status'] ),
									);
									wp_update_post( $essay_post );
								}

								$quiz_time = get_post_meta( $essay_post_id, 'quiz_time', true );
								if ( ! $quiz_time ) {
									update_post_meta( $essay_post_id, 'quiz_time', $quizdata['time'] );
								}
							}
						}
					}
				}
			}
		}
	}
}
add_action( 'learndash_quiz_submitted', 'learndash_quiz_submitted_update_essay', 1, 2 );

/**
 * Return the Usermeta Quiz array for the Essay Post ID.
 *
 * @since 3.2.3
 * @param int $essay_post_id Essay Post ID.
 * @param int $user_id       User ID.
 */
function learndash_get_user_quiz_entry_for_essay( $essay_post_id = 0, $user_id = 0 ) {
	$essay_post_id = absint( $essay_post_id );
	if ( ! empty( $essay_post_id ) ) {
		$essay_post = get_post( $essay_post_id );
		if ( ( $essay_post ) && ( is_a( $essay_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'essay' ) === $essay_post->post_type ) ) {
			if ( empty( $user_id ) ) {
				$user_id = absint( $essay_post->post_author );
			}
			$quiz_pro_id = get_post_meta( $essay_post->ID, 'quiz_pro_id', true );
			$quiz_time   = get_post_meta( $essay_post->ID, 'quiz_time', true );
			if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_pro_id ) ) && ( ! empty( $quiz_time ) ) ) {
				$user_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );
				if ( ! empty( $user_quizzes ) ) {
					foreach ( $user_quizzes as $q_idx => $user_quiz ) {
						if ( ( isset( $user_quiz['pro_quizid'] ) ) && ( absint( $quiz_pro_id ) === absint( $user_quiz['pro_quizid'] ) ) ) {
							if ( ( isset( $user_quiz['time'] ) ) && ( absint( $quiz_time ) === absint( $user_quiz['time'] ) ) ) {
								if ( ( isset( $user_quiz['graded'] ) ) && ( ! empty( $user_quiz['graded'] ) ) ) {
									foreach ( $user_quiz['graded'] as $key => $graded_question ) {
										if ( absint( $essay_post_id ) === absint( $graded_question['post_id'] ) ) {
											$user_quiz['usermeta_idx'] = absint( $q_idx );
											return $user_quiz;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
