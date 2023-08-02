<?php
/**
 * Function that help the Course Steps.
 *
 * @since 3.4.0
 *
 * @package LearnDash\Course_Steps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets all the lessons and topics for a given course ID.
 *
 * For now excludes quizzes at lesson and topic level.
 *
 * @since 2.3.0
 *
 * @param int   $course_id          Optional. The ID of the course. Default 0.
 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
 *
 * @return array An array of all course steps.
 */
function learndash_get_course_steps( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {

	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_course_steps_legacy( $course_id, $include_post_types );
	}

	// The steps array will hold all the individual step counts for each post_type.
	$steps = array();

	// This will hold the combined steps post ids once we have run all queries.
	$steps_all = array();

	if ( ! empty( $course_id ) ) {
		foreach ( $include_post_types as $post_type ) {
			$steps[ $post_type ] = learndash_course_get_steps_by_type( $course_id, $post_type );
			$steps_all           = array_merge( $steps_all, $steps[ $post_type ] );
		}
	}

	return $steps_all;
}

/**
 * Gets the total count of lessons and topics for a given course ID.
 *
 * @since 2.3.0
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The count of the course steps.
 */
function learndash_get_course_steps_count( $course_id = 0 ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_course_steps_count_legacy( $course_id );
	}

	return learndash_course_get_steps_count( $course_id );
}

/**
 * Gets the total completed steps for a given course progress array.
 *
 * @since 2.3.0
 *
 * @param int   $user_id         Optional. The ID of the user. Default 0.
 * @param int   $course_id       Optional. The ID of the course. Default 0.
 * @param array $course_progress Optional. An array of course progress data. Default empty array.
 *
 * @return int The count of completed course steps.
 */
function learndash_course_get_completed_steps( $user_id = 0, $course_id = 0, $course_progress = array() ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_course_get_completed_steps_legacy( $user_id, $course_id, $course_progress );
	}

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	$steps_completed_count = 0;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {

		if ( empty( $course_progress ) ) {
			$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );
		}

		$course_lessons = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
		if ( ! empty( $course_lessons ) ) {
			if ( isset( $course_progress['lessons'] ) ) {
				foreach ( $course_progress['lessons'] as $lesson_id => $lesson_completed ) {
					if ( in_array( $lesson_id, $course_lessons, true ) ) {
						$steps_completed_count += intval( $lesson_completed );
					}
				}
			}
		}

		$course_topics = learndash_course_get_steps_by_type( $course_id, 'sfwd-topic' );
		if ( isset( $course_progress['topics'] ) ) {
			foreach ( $course_progress['topics'] as $lesson_id => $lesson_topics ) {
				if ( in_array( $lesson_id, $course_lessons, true ) ) {
					if ( ( is_array( $lesson_topics ) ) && ( ! empty( $lesson_topics ) ) ) {
						foreach ( $lesson_topics as $topic_id => $topic_completed ) {
							if ( in_array( $topic_id, $course_topics, true ) ) {
								$steps_completed_count += intval( $topic_completed );
							}
						}
					}
				}
			}
		}

		if ( learndash_has_global_quizzes( $course_id ) ) {
			if ( learndash_is_all_global_quizzes_complete( $user_id, $course_id ) ) {
				++$steps_completed_count;
			}
		}
	}

	return $steps_completed_count;
}

/**
 * Get the Course Sections.
 *
 * @since 3.4.0
 *
 * @param integer $course_id Course ID.
 * @param array   $query_args Array of query args to filter the query.
 *
 * @return array of Sections.
 */
function learndash_course_get_sections( $course_id = 0, $query_args = array() ) {
	$sections = array();

	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$ld_course_object = LDLMS_Factory_Post::course( intval( $course_id ) );
		if ( ( $ld_course_object ) && ( is_a( $ld_course_object, 'LDLMS_Model_Course' ) ) ) {
			$query_args_defaults = array(
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$sections = $ld_course_object->get_sections( $query_args );
		}
	}

	return $sections;
}

/**
 * Get the Course Lessons.
 *
 * @since 3.4.0
 *
 * @param integer $course_id Course ID.
 * @param array   $query_args Array of query args to filter the query.
 *
 * @return array of Lessons.
 */
function learndash_course_get_lessons( $course_id = 0, $query_args = array() ) {
	$lessons = array();

	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$ld_course_object = LDLMS_Factory_Post::course( intval( $course_id ) );
		if ( ( $ld_course_object ) && ( is_a( $ld_course_object, 'LDLMS_Model_Course' ) ) ) {
			$query_args_defaults = array(
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$lessons = $ld_course_object->get_lessons( $query_args );
		}
	}

	return $lessons;
}

/**
 * Get the Course Lesson Topics.
 *
 * @since 3.4.0
 *
 * @param integer $course_id Course ID.
 * @param integer $lesson_id Lesson ID.
 * @param array   $query_args Array of query args to filter the query.
 *
 * @return array of Topics.
 */
function learndash_course_get_topics( $course_id = 0, $lesson_id = 0, $query_args = array() ) {
	$topics = array();

	$course_id = absint( $course_id );
	$lesson_id = absint( $lesson_id );
	if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {

		$ld_course_object = LDLMS_Factory_Post::course( $course_id );
		if ( ( $ld_course_object ) && ( is_a( $ld_course_object, 'LDLMS_Model_Course' ) ) ) {
			$query_args_defaults = array(
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$topics = $ld_course_object->get_topics( $lesson_id, $query_args );
		}
	}

	return $topics;
}

/**
 * Get the Course Quizzes.
 *
 * @since 3.4.0
 *
 * @param integer $course_id  Course ID.
 * @param integer $parent_id  Parent Lesson, Topic, or Course ID.
 * @param array   $query_args Array of query args to filter the query.
 *
 * @return array of Quizzes.
 */
function learndash_course_get_quizzes( $course_id = 0, $parent_id = 0, $query_args = array() ) {
	$quizzes = array();

	$course_id = absint( $course_id );
	$parent_id = absint( $parent_id );
	if ( ( ! empty( $course_id ) ) && ( ! empty( $parent_id ) ) ) {

		$ld_course_object = LDLMS_Factory_Post::course( $course_id );
		if ( ( $ld_course_object ) && ( is_a( $ld_course_object, 'LDLMS_Model_Course' ) ) ) {
			$query_args_defaults = array(
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$quizzes = $ld_course_object->get_quizzes( $parent_id, $query_args );
		}
	}

	return $quizzes;
}

/**
 * Get the Course Steps Count.
 *
 * @since 3.4.0
 *
 * @param integer $course_id Course ID.
 *
 * @return integer count of steps.
 */
function learndash_course_get_steps_count( $course_id = 0 ) {
	$steps_count = 0;

	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( ( $ld_course_steps_object ) && ( is_a( $ld_course_steps_object, 'LDLMS_Course_Steps' ) ) ) {
			$steps_count = $ld_course_steps_object->get_steps_count();
		}
	}

	return $steps_count;
}

/**
 * Gets the parent step IDs for a step in a course.
 *
 * @since 2.5.0
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 * @param int $step_id   Optional. The ID of the step to get parent steps. Default 0.
 *
 * @return array An array of step IDs.
 */
function learndash_course_get_all_parent_step_ids( $course_id = 0, $step_id = 0 ) {
	$step_parents = array();

	if ( ( ! empty( $course_id ) ) && ( ! empty( $step_id ) ) ) {
		if ( learndash_is_course_builder_enabled() ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$step_parents = $ld_course_steps_object->get_item_parent_steps( $step_id );
				if ( ! empty( $step_parents ) ) {
					$step_parents_2 = array();
					foreach ( $step_parents as $step_parent ) {
						list( $parent_post_type, $parent_post_id ) = explode( ':', $step_parent );
						$step_parents_2[]                          = intval( $parent_post_id );
					}
					$step_parents = array_reverse( $step_parents_2 );
				}
			}
		} else {
			$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );
			if ( ! empty( $parent_step_id ) ) {
				$step_parents[] = $parent_step_id;
				if ( 'sfwd-topic' === get_post_type( $parent_step_id ) ) {
					$parent_step_id = get_post_meta( $parent_step_id, 'lesson_id', true );
					if ( ! empty( $parent_step_id ) ) {
						$step_parents[] = $parent_step_id;
					}
				}
			}
			if ( ! empty( $step_parents ) ) {
				$step_parents = array_reverse( $step_parents );
			}
		}
	}

	if ( ! empty( $step_parents ) ) {
		$step_parents = array_map( 'intval', $step_parents );
	}

	return $step_parents;
}

/**
 * Gets the single parent step ID for a given step ID in a course.
 *
 * @since 2.5.0
 *
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param int    $step_id   Optional. Step ID. Default 0.
 * @param string $step_type Optional. The type of the step. Default empty.
 *
 * @return int The parent step ID.
 */
function learndash_course_get_single_parent_step( $course_id = 0, $step_id = 0, $step_type = '' ) {
	$parent_step_id = 0;

	if ( ( ! empty( $course_id ) ) && ( ! empty( $step_id ) ) ) {
		if ( learndash_is_course_builder_enabled() ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$parent_step_id = $ld_course_steps_object->get_parent_step_id( $step_id, $step_type );
			}
		} else {
			if ( empty( $step_type ) ) {
				$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );
			} else {
				// We only have two nested post types: Topics and quizzes.
				$step_id_post_type = get_post_type( $step_id );

				// A topic only has one parent, a lesson.
				if ( 'sfwd-topic' === $step_id_post_type ) {
					$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );

				} elseif ( 'sfwd-quiz' === $step_id_post_type ) {
					$lesson_id      = 0;
					$topic_id       = 0;
					$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );
					if ( ! empty( $parent_step_id ) ) {
						$parent_step_id_post_type = get_post_type( $parent_step_id );
						if ( 'sfwd-topic' === $parent_step_id_post_type ) {
							$topic_id  = $parent_step_id;
							$lesson_id = get_post_meta( $topic_id, 'lesson_id', true );
						} elseif ( 'sfwd-lessons' === $parent_step_id_post_type ) {
							$lesson_id = $parent_step_id;
						}

						if ( 'sfwd-lessons' === $step_type ) {
							$parent_step_id = $lesson_id;
						} elseif ( 'sfwd-topic' === $step_type ) {
							$parent_step_id = $topic_id;
						} else {
							$parent_step_id = 0;
						}
					}
				}
			}
		}
	}

	return intval( $parent_step_id );
}

/**
 * Gets the course steps by type.
 *
 * @since 2.5.0
 *
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param string $step_type Optional. The type of the step. Default empty.
 *
 * @return array An array of course step IDs.
 */
function learndash_course_get_steps_by_type( $course_id = 0, $step_type = '' ) {
	$course_steps_return = array();

	if ( ( ! empty( $course_id ) ) && ( ! empty( $step_type ) ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			if ( in_array( $step_type, learndash_get_post_types( 'course_steps' ), true ) ) {
				$course_steps_t = $ld_course_steps_object->get_steps( 't' );
				if ( ( isset( $course_steps_t[ $step_type ] ) ) && ( ! empty( $course_steps_t[ $step_type ] ) ) ) {
					$course_steps_return = $course_steps_t[ $step_type ];
				}
			} else {
				$course_steps_return = $ld_course_steps_object->get_steps( $step_type );
			}
		}
	}

	return $course_steps_return;
}

/**
 * Gets the list of children steps for a given step ID.
 *
 * @since 2.5.0
 *
 * @param int    $course_id  Optional.     Course ID. Default 0.
 * @param int    $step_id    Optional.     The ID of step to get child steps. Default 0.
 * @param string $child_type Optional.     The type of the child steps to get. Default empty.
 * @param string $return_type Return type. Default 'ids'. Other values 'objects'.
 * @param bool   $nested                   Wether to traverse substeps. Default false.
 *
 * @return array An array of child step IDs.
 */
function learndash_course_get_children_of_step( $course_id = 0, $step_id = 0, $child_type = '', $return_type = 'ids', $nested = false ) {
	$children_steps = array();

	$course_id = absint( $course_id );
	$step_id   = absint( $step_id );

	if ( ! empty( $course_id ) ) {
		if ( empty( $step_id ) ) {
			$step_id = $course_id;
		}
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			$children_steps = $ld_course_steps_object->get_children_steps( $step_id, $child_type, $return_type, $nested );
		}
	}

	return $children_steps;

}

/**
 * Gets the list of courses associated with a step.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int     $step_id           Optional. The ID of the step to get course list. Default 0.
 * @param boolean $return_flat_array  Optional. Whether to return single dimensional array. Default false.
 *
 * @return array An array of course list for a step. Returns an multidimensional array
 *               of course list sorted in primary and secondary course list if the
 *               `$return_flat_array` parameter is false.
 */
function learndash_get_courses_for_step( $step_id = 0, $return_flat_array = false ) {
	global $wpdb;

	$course_ids              = array();
	$course_ids['primary']   = array();
	$course_ids['secondary'] = array();

	if ( ! empty( $step_id ) ) {
		$post_post_meta = get_post_meta( $step_id );
		foreach ( $post_post_meta as $meta_key => $meta_values ) {
			if ( 'course_id' === $meta_key ) {
				foreach ( $meta_values as $course_id ) {
					$course_id = absint( $course_id );
					if ( ! isset( $course_ids['primary'][ $course_id ] ) ) {
						$course_post = get_post( $course_id );
						if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
							$course_ids['primary'][ $course_id ] = get_the_title( $course_id );
						}
					}
				}
			} elseif ( substr( $meta_key, 0, strlen( 'ld_course_' ) ) === 'ld_course_' ) {
				foreach ( $meta_values as $course_id ) {
					$course_id = absint( $course_id );
					if ( ! isset( $course_ids['secondary'][ $course_id ] ) ) {
						$course_post = get_post( $course_id );
						if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
							$course_ids['secondary'][ $course_id ] = get_the_title( $course_id );
						}
					}
				}
			}
		}

		// LEARNDASH-6567 : If shared steps is not enabled then clear the array node.
		if ( ! learndash_is_course_shared_steps_enabled() ) {
			$course_ids['secondary'] = array();
		}

		// Ensure the primary course is also part of the secondary courses.
		if ( ! empty( $course_ids['primary'] ) ) {
			foreach ( $course_ids['primary'] as $p_course_id => $p_course_title ) {
				if ( ! isset( $course_ids['secondary'][ $p_course_id ] ) ) {
					update_post_meta( $step_id, 'ld_course_' . $p_course_id, $p_course_id );
				}
			}
		} else {
			foreach ( $course_ids['secondary'] as $s_course_id => $s_course_title ) {
				$course_ids['primary'][ $s_course_id ] = $s_course_title;
				learndash_update_setting( $step_id, 'course', $s_course_id );
				break;
			}
		}

		// Now ensure the primary course IDs are not included in the secondary listing.
		foreach ( $course_ids['primary'] as $p_course_id => $p_course_title ) {
			if ( isset( $course_ids['secondary'][ $p_course_id ] ) ) {
				unset( $course_ids['secondary'][ $p_course_id ] );
			}
		}

		if ( true === $return_flat_array ) {
			$course_ids_flat = array();
			foreach ( $course_ids['primary'] as $course_id => $course_title ) {
				if ( ! isset( $course_ids_flat[ $course_id ] ) ) {
					$course_ids_flat[ $course_id ] = $course_title;
				}
			}

			foreach ( $course_ids['secondary'] as $course_id => $course_title ) {
				if ( ! isset( $course_ids_flat[ $course_id ] ) ) {
					$course_ids_flat[ $course_id ] = $course_title;
				}
			}

			$course_ids = $course_ids_flat;
		}

		return $course_ids;
	}

	return array();
}

/**
 * Check the Course Step primary Course ID.
 *
 * @since 3.2.3
 *
 * @param int $step_id Course Step Post ID.
 */
function learndash_check_primary_course_for_step( $step_id = 0 ) {
	$step_id = absint( $step_id );
	if ( ( ! empty( $step_id ) ) && ( learndash_is_course_shared_steps_enabled() ) ) {
		if ( in_array( get_post_type( $step_id ), array( learndash_get_post_type_slug( 'lesson' ), learndash_get_post_type_slug( 'topic' ), learndash_get_post_type_slug( 'quiz' ) ), true ) ) {
			$course_id = learndash_get_primary_course_for_step( $step_id );
			if ( empty( $course_id ) ) {
				$post_courses = learndash_get_courses_for_step( $step_id );
				if ( ( isset( $post_courses['secondary'] ) ) && ( ! empty( $post_courses['secondary'] ) ) ) {
					foreach ( $post_courses['secondary'] as $course_id => $course_title ) {
						learndash_set_primary_course_for_step( $step_id, $course_id );
						break;
					}
				}
			}
		}
	}
}

/**
 * Get primary course_id for course step.
 *
 * @since 3.2.0
 *
 * @param integer $step_id Course step post ID.
 * @return integer $course_id Primary Course ID if found.
 */
function learndash_get_primary_course_for_step( $step_id = 0 ) {
	$course_id = null;
	$step_id   = absint( $step_id );
	if ( ! empty( $step_id ) ) {
		$course_id = get_post_meta( $step_id, 'course_id', true );
		if ( empty( $course_id ) ) {
			$step_courses = learndash_get_courses_for_step( $step_id );
			if ( ! isset( $step_courses['primary'] ) ) {
				$step_courses['primary'] = array();
			}
			$step_courses['primary'] = array_keys( $step_courses['primary'] );
			if ( ! empty( $step_courses['primary'] ) ) {
				$course_id = absint( $step_courses['primary'][0] );
			}
		}
	}

	return $course_id;
}

/**
 * Set primary course_id for course step.
 *
 * @since 3.2.0
 *
 * @param integer $step_id   Course step post ID.
 * @param integer $course_id Course ID.
 */
function learndash_set_primary_course_for_step( $step_id = 0, $course_id = 0 ) {
	$step_id   = absint( $step_id );
	$course_id = absint( $course_id );

	if ( ( ! empty( $step_id ) ) && ( ! empty( $course_id ) ) ) {
		$step_courses = learndash_get_courses_for_step( $step_id );

		if ( ( ! isset( $step_courses['primary'][ $course_id ] ) ) && ( isset( $step_courses['secondary'][ $course_id ] ) ) ) {
			learndash_update_setting( $step_id, 'course', $course_id );
		}
	}
}

/**
 * Validates the URL requests when nested URL permalinks are used.
 *
 * @since 2.5.0
 *
 * @global WP_Post  $post     Global post object.
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param WP $wp The `WP` instance.
 */
function learndash_check_course_step( $wp ) {
	if ( is_single() ) {
		global $post;
		if ( ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) === true ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) ) ) {
			$course_slug = get_query_var( 'sfwd-courses' );

			// Check first if there is an existing course part of the URL. Maybe the student is trying to user a lesson URL part for a different course.
			if ( ! empty( $course_slug ) ) {
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( ! empty( $course_post ) ) && ( is_a( $course_post, 'WP_Post' ) ) && ( 'sfwd-courses' === $course_post->post_type ) ) {
					$step_courses = learndash_get_courses_for_step( $post->ID, true );
					if ( ( ! empty( $step_courses ) ) && ( isset( $step_courses[ $course_post->ID ] ) ) ) {

						if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) === true ) {

							$parent_steps = learndash_course_get_all_parent_step_ids( $course_post->ID, $post->ID );

							if ( 'sfwd-quiz' === $post->post_type ) {
								$topic_slug = get_query_var( 'sfwd-topic' );
								if ( ! empty( $topic_slug ) ) {
									$topic_post = learndash_get_page_by_path( $topic_slug, 'sfwd-topic' );
									if ( ( ! empty( $topic_post ) ) && ( is_a( $topic_post, 'WP_Post' ) ) && ( 'sfwd-topic' === $topic_post->post_type ) ) {
										if ( ! in_array( $topic_post->ID, $parent_steps, true ) ) {
											$course_link = get_permalink( $course_post->ID );
											if ( ! empty( $course_link ) ) {
												learndash_safe_redirect( $course_link );
											}
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										if ( ! empty( $course_link ) ) {
											learndash_safe_redirect( $course_link );
										}
									}
								}
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps, true ) ) {
											$course_link = get_permalink( $course_post->ID );
											if ( ! empty( $course_link ) ) {
												learndash_safe_redirect( $course_link );
											}
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										if ( ! empty( $course_link ) ) {
											learndash_safe_redirect( $course_link );
										}
									}
								}
							} elseif ( 'sfwd-topic' === $post->post_type ) {
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps, true ) ) {
											$course_link = get_permalink( $course_post->ID );
											if ( ! empty( $course_link ) ) {
												learndash_safe_redirect( $course_link );
											}
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										if ( ! empty( $course_link ) ) {
											learndash_safe_redirect( $course_link );
										}
									}
								}
							}
						}

						// All is ok to return.
						return;
					} else {
						$course_link = get_permalink( $course_post->ID );
						if ( ! empty( $course_link ) ) {
							learndash_safe_redirect( $course_link );
						}
					}
				} else {
					// If we don't have a valid Course post.
					global $wp_query;
					$wp_query->set_404();

					require get_404_template();
					exit;
				}
			} else {
				if ( learndash_is_admin_user() ) {
					return;
				} else {
					// If we don't have a course part of the URL then we check if the step has a primary (legacy) course.
					$step_courses = learndash_get_courses_for_step( $post->ID, false );

					// If we do have a primary (legacy) then we redirect the user there.
					if ( ! empty( $step_courses['primary'] ) ) {
						$primary_courses = array_keys( $step_courses['primary'] );
						$step_permalink  = learndash_get_step_permalink( $post->ID, $primary_courses[0] );
						if ( ! empty( $step_permalink ) ) {
							learndash_safe_redirect( $step_permalink );
						} else {
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							if ( ! empty( $courses_archive_link ) ) {
								learndash_safe_redirect( $step_permalink );
							}
						}
					} else {
						if ( learndash_is_admin_user() ) {
							// Alow the admin to view the lesson/topic before it is added to a course.
							return;
						} elseif ( ( 'sfwd-quiz' === $post->post_type ) && ( empty( $step_courses['secondary'] ) ) ) {
							// If here we have a quiz with no primary or secondary courses. So it is standalone and allowed.
							return;
						} else {
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							if ( ! empty( $courses_archive_link ) ) {
								learndash_safe_redirect( $courses_archive_link );
							}
						}
					}
				}
			}
		}
	}
}
add_action( 'wp', 'learndash_check_course_step' );

/**
 * Updates the course step post status when a post is trashed or untrashed.
 *
 * Fires on `transition_post_status` hook.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.5.0
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       The `WP_Post` object.
 */
function learndash_transition_course_step_post_status( $new_status, $old_status, $post ) {
	global $wpdb;

	if ( $new_status !== $old_status ) {
		if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$course_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND (meta_key = %s OR meta_key LIKE %s)",
					$post->ID,
					'course_id',
					'ld_course_%'
				)
			);
			if ( ! empty( $course_ids ) ) {
				$course_ids = array_unique( $course_ids );
				foreach ( $course_ids as $course_id ) {
					learndash_course_set_steps_dirty( $course_id );
				}
			}
		}
	}
}
add_action( 'transition_post_status', 'learndash_transition_course_step_post_status', 10, 3 );

/**
 * Course set steps dirty.
 *
 * @since 3.4.0.2
 *
 * @param integer $course_id Course ID.
 */
function learndash_course_set_steps_dirty( $course_id = 0 ) {
	$course_id = absint( $course_id );
	if ( ! empty( $course_id ) ) {
		$course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		if ( ( is_object( $course_steps_object ) ) && ( is_a( $course_steps_object, 'LDLMS_Course_Steps' ) ) ) {
			return $course_steps_object->set_steps_dirty();
		}
	}
}

/**
 * Get the array of 'post_stats' keys used for Course Steps queries.
 *
 * @since 3.4.1
 *
 * @return array Array of post_status keys.
 */
function learndash_get_step_post_statuses() {
	$ld_post_statuses = array();
	$wp_post_statuses = get_post_stati( array( 'show_in_admin_status_list' => true ), 'object' );
	if ( ! empty( $wp_post_statuses ) ) {
		foreach ( $wp_post_statuses as $status_key => $status_object ) {
			$ld_post_statuses[ $status_key ] = $status_object->label;
		}
	}
	$ld_post_statuses['password'] = esc_html_x( 'Password', 'Password Protected post_status label', 'learndash' );

	/**
	 * Filters the post_statuses use for Course Steps Queries.
	 *
	 * @since 3.4.0.3
	 *
	 * @param array $ld_post_statuses Array of post_status key/label pairs.
	 */
	return apply_filters( 'learndash_course_steps_post_statuses', $ld_post_statuses );
}

/**
 * Get single course step post status slug.
 *
 * @since 3.4.1
 *
 * @param object $post WP_Post object.
 *
 * @return string
 */
function learndash_get_step_post_status_slug( $post ) {
	if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
		if ( ( 'publish' === $post->post_status ) && ( ! empty( $post->post_password ) ) ) {
			return 'password';
		}
		return $post->post_status;
	}

	return '';
}

/**
 * Get single course step post status label.
 *
 * @since 4.0.0
 *
 * @param object $post WP_Post object.
 *
 * @return string
 */
function learndash_get_step_post_status_label( $post ) {
	$post_status_label = '';
	if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
		$post_status_slug = learndash_get_step_post_status_slug( $post );
		$post_statuses    = learndash_get_step_post_statuses();
		if ( isset( $post_statuses[ $post_status_slug ] ) ) {
			$post_status_label = $post_statuses[ $post_status_slug ];
		}
	}

	return $post_status_label;
}

/**
 * Get the post title formatted with post status label.
 *
 * @since 4.0.0
 *
 * @param object $post          WP_Post object.
 * @param array  $skip_statuses Optional. Array of post_stati to skip.
 * @param string $before_label  Optional. String to prepend to the status label.
 * @param string $after_label   Optional. String to append to the status label.
 *
 * @return string Formatted post title.
 */
function learndash_format_step_post_title_with_status_label( $post, $skip_statuses = array( 'publish' ), $before_label = '(', $after_label = ')' ) {
	$post_title = '';

	if ( is_a( $post, 'WP_Post' ) ) {
		$post_title = get_the_title( $post->ID );

		if ( ! empty( $skip_statuses ) ) {
			if ( ! is_array( $skip_statuses ) ) {
				$skip_statuses = array( $skip_statuses );
			}
		}

		$post_status_slug = learndash_get_step_post_status_slug( $post );
		if ( ! in_array( $post_status_slug, $skip_statuses, true ) ) {
			$post_statuses = learndash_get_step_post_statuses();

			if ( isset( $post_statuses[ $post_status_slug ] ) ) {
				$post_status_label = esc_html( $before_label ) . esc_html( $post_statuses[ $post->post_status ] ) . esc_html( $after_label );
			} else {
				$post_status_label = esc_html( $before_label ) . esc_html__( 'Unknown', 'learndash' ) . esc_html( $after_label );
			}

			if ( ! empty( $post_status_label ) ) {
				$post_title = sprintf(
					// translators: placeholder: post title, post status.
					esc_html_x( '%1$s %2$s', 'placeholder: post title, post status', 'learndash' ),
					$post_title,
					esc_html( $post_status_label )
				);
			}
		}
	}

	return $post_title;
}

/**
 * Handler function when a new course step is inserted.
 *
 * This function exists to handle inserted post steps external to
 * LearnDash. But this function will also be triggered when adding
 * a new step post within LearnDash.
 *
 * @since 3.4.0.3
 *
 * @param int    $post_id Post ID.
 * @param object $post    WP_Post object.
 * @param bool   $update  Whether this is an existing post being updated.
 */
function learndash_new_step_insert( $post_id, $post, $update ) {
	$post_id = absint( $post_id );
	if ( ( ! empty( $post_id ) ) && ( ! $update ) && ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {
		$learndash_course_dirty = get_option( 'learndash_course_dirty', array() );
		if ( ! is_array( $learndash_course_dirty ) ) {
			$learndash_course_dirty = array();
		}
		$learndash_course_dirty[] = $post_id;
		update_option( 'learndash_course_dirty', $learndash_course_dirty );
	}
}
add_action( 'wp_insert_post', 'learndash_new_step_insert', 30, 3 );

/**
 * Handler function to post-process new inserted steps from learndash_insert_new_step.
 *
 * @since 3.4.0.3
 */
function learndash_new_step_init() {
	$learndash_course_dirty = get_option( 'learndash_course_dirty', array() );
	if ( ! is_array( $learndash_course_dirty ) ) {
		$learndash_course_dirty = array();
	}

	while ( ! empty( $learndash_course_dirty ) ) {
		foreach ( $learndash_course_dirty as $post_id ) {
			if ( in_array( get_post_type( $post_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$course_id = get_post_meta( $post_id, 'course_id', true );
				$course_id = absint( $course_id );
				if ( ! empty( $course_id ) ) {
					learndash_course_set_steps_dirty( $course_id );
				}
			}
			$learndash_course_dirty = array_diff( $learndash_course_dirty, array( $post_id ) );
			break;
		}
	}
	update_option( 'learndash_course_dirty', $learndash_course_dirty );
}
add_action( 'learndash_init', 'learndash_new_step_init', 30 );

/**
 * Updates the metadata settings array when updating single setting.
 *
 * Used when saving a single setting. This will then trigger an update to the array setting.
 * Fires on `update_post_meta` hook.
 *
 * @since 3.4.0.3
 *
 * @param int        $meta_id    Optional. ID of the metadata entry to update. Default 0.
 * @param int|string $object_id  Optional. Object ID. Default empty.
 * @param string     $meta_key   Optional. Meta key. Default empty.
 * @param mixed      $meta_value Optional. Meta value. Default empty.
 */
function learndash_course_steps_update_post_meta( $meta_id = 0, $object_id = '', $meta_key = '', $meta_value = '' ) {
	// Simple static var to prevent recursive calls.
	static $in_process = false;
	if ( true === $in_process ) {
		return;
	}

	if ( ( ! empty( $object_id ) ) && ( ! empty( $meta_key ) ) ) {
		$object_id = absint( $object_id );
		$meta_key  = esc_attr( $meta_key );

		if ( 'course_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = true;

				$course_id_new = absint( $meta_value );

				$course_id_current = get_post_meta( $object_id, $meta_key, true );
				$course_id_current = absint( $course_id_current );
				if ( $course_id_current !== $course_id_new ) {
					if ( ! empty( $course_id_current ) ) {
						learndash_course_set_steps_dirty( $course_id_current );
					}

					if ( ! empty( $course_id_new ) ) {
						learndash_course_set_steps_dirty( $course_id_new );
					}
				}

				$in_process = false;
			}
		} elseif ( 'lesson_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = true;

				$lesson_id_new = absint( $meta_value );

				$lesson_id_current = get_post_meta( $object_id, $meta_key, true );
				$lesson_id_current = absint( $lesson_id_current );
				if ( $lesson_id_current !== $lesson_id_new ) {
					$course_id_current = 0;
					$course_id_new     = 0;

					if ( ! empty( $lesson_id_current ) ) {
						$course_id_current = get_post_meta( $lesson_id_current, 'course_id', true );
						$course_id_current = absint( $course_id_current );
						if ( ! empty( $course_id_current ) ) {
							learndash_course_set_steps_dirty( $course_id_current );
						}
					}
					if ( ! empty( $lesson_id_new ) ) {
						$course_id_new = get_post_meta( $lesson_id_new, 'course_id', true );
						$course_id_new = absint( $course_id_new );
						if ( ! empty( $course_id_new ) ) {
							learndash_course_set_steps_dirty( $course_id_new );
						}
					}
				}

				$in_process = false;
			}
		}
	}
}
add_action( 'update_post_meta', 'learndash_course_steps_update_post_meta', 20, 4 );

/**
 * Deletes the metadata settings array when updating single setting.
 *
 * Used when deleting a single setting. This will then trigger an update to the array setting.
 * Fires on `delete_post_meta` hook.
 *
 * @since 3.4.0.3
 *
 * @param int        $meta_id    Optional. ID of the metadata entry to update. Default 0.
 * @param int|string $object_id  Optional. Object ID. Default empty.
 * @param string     $meta_key   Optional. Meta key. Default empty.
 * @param mixed      $meta_value Optional. Meta value. Default empty.
 */
function learndash_course_steps_delete_post_meta( $meta_id = 0, $object_id = '', $meta_key = '', $meta_value = '' ) {
	// Simple static var to prevent recursive calls.
	static $in_process = false;
	if ( true === $in_process ) {
		return;
	}

	if ( ( ! empty( $object_id ) ) && ( ! empty( $meta_key ) ) ) {
		$object_id = absint( $object_id );
		if ( 'course_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = true;

				// Get the current 'course_id' if set.
				$course_id_current = get_post_meta( $object_id, $meta_key, true );
				$course_id_current = absint( $course_id_current );
				if ( ! empty( $course_id_current ) ) {
					learndash_course_set_steps_dirty( $course_id_current );
				}

				$in_process = false;
			}
		} elseif ( 'lesson_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = false;

				$lesson_id_new     = absint( $meta_value );
				$lesson_id_current = get_post_meta( $object_id, $meta_key, true );
				$lesson_id_current = absint( $lesson_id_current );

				if ( $lesson_id_current !== $lesson_id_new ) {
					$course_id = get_post_meta( $lesson_id_current, 'course_id', true );
					$course_id = absint( $course_id );
					if ( ! empty( $course_id ) ) {
						learndash_course_set_steps_dirty( $course_id );
					}
				}

				$in_process = false;
			}
		}
	}
}
add_action( 'delete_post_meta', 'learndash_course_steps_delete_post_meta', 20, 4 );

/**
 * Add the metadata settings array when adding single setting.
 *
 * Used when adding a single setting.
 * Fires on `delete_post_meta` hook.
 *
 * @since 3.4.0.3
 *
 * @param int|string $object_id  Optional. Object ID. Default empty.
 * @param string     $meta_key   Optional. Meta key. Default empty.
 * @param mixed      $meta_value Optional. Meta value. Default empty.
 */
function learndash_course_steps_add_post_meta( $object_id, $meta_key, $meta_value ) {
	// Simple static var to prevent recursive calls.
	static $in_process = false;
	if ( true === $in_process ) {
		return;
	}

	if ( ( ! empty( $object_id ) ) && ( ! empty( $meta_key ) ) ) {
		$object_id = absint( $object_id );
		if ( 'course_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = true;

				$course_id_new = absint( $meta_value );
				if ( ! empty( $course_id_new ) ) {
					learndash_course_set_steps_dirty( $course_id_new );
				}

				$in_process = false;
			}
		} elseif ( 'lesson_id' === $meta_key ) {
			if ( in_array( get_post_type( $object_id ), learndash_get_post_types( 'course_steps' ), true ) ) {
				$in_process = false;

				$lesson_id = absint( $meta_value );
				$course_id = get_post_meta( $lesson_id, 'course_id', true );
				if ( ! empty( $course_id ) ) {
					learndash_course_set_steps_dirty( $course_id );
				}

				$in_process = false;
			}
		}
	}
}
add_action( 'add_post_meta', 'learndash_course_steps_add_post_meta', 20, 3 );
