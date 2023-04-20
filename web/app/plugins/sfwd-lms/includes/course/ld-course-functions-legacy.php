<?php
/**
 * Legacy Course Functions
 *
 * Functions included here are considered legacy and are no longer used and
 * will soon be deprecated.
 *
 * @since 3.4.0
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore accessable .

/**
 * Gets the lesson list for a course.
 *
 * @global wpdb    $wpdb WordPress database abstraction object.
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $id   The ID of the resource.
 * @param array    $atts An array of lesson arguments.
 *
 * @return array|string Returns Lesson list output or empty array.
 */
function learndash_get_lesson_list( $id = null, $atts = array() ) {
	global $post;

	if ( empty( $id ) ) {
		if ( $post instanceof WP_Post ) {
			$id = $post->ID;
		}
	}

	$course_id = learndash_get_course_id( $id );

	if ( empty( $course_id ) ) {
		return array();
	}

	global $wpdb;

	$lessons             = sfwd_lms_get_post_options( 'sfwd-lessons' );
	$course_lessons_args = learndash_get_course_lessons_order( $course_id );
	$orderby             = ( isset( $course_lessons_args['orderby'] ) ) ? $course_lessons_args['orderby'] : 'title';
	$order               = ( isset( $course_lessons_args['order'] ) ) ? $course_lessons_args['order'] : 'ASC';

	switch ( $orderby ) {
		case 'title':
			$orderby = 'title';
			break;
		case 'date':
			$orderby = 'date';
			break;
	}

	$lessons_args = array(
		'array'      => true,
		'course_id'  => $course_id,
		'post_type'  => 'sfwd-lessons',
		'meta_key'   => 'course_id',
		'meta_value' => $course_id,
		'orderby'    => $orderby,
		'order'      => $order,
	);

	$lessons_args = array_merge( $lessons_args, $atts );

	if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		$ld_course_steps_object->load_steps();
		$course_steps = $ld_course_steps_object->get_steps( 't' );

		if ( ( isset( $course_steps[ $lessons_args['post_type'] ] ) ) && ( ! empty( $course_steps[ $lessons_args['post_type'] ] ) ) ) {
			$lessons_args['post__in'] = $course_steps[ $lessons_args['post_type'] ];
			$lessons_args['orderby']  = 'post__in';

			unset( $lessons_args['order'] );
			unset( $lessons_args['meta_key'] );
			unset( $lessons_args['meta_value'] );
		} else {
			return array();
		}
	}

	/**
	 * Filters query arguments for getting the lesson list.
	 *
	 * @since 2.5.7
	 *
	 * @param array $lesson_args An array of arguments for getting lesson list.
	 * @param int   $id          ID of resource.
	 * @param int   $course_id   Course ID.
	 */
	$lessons_args = apply_filters( 'learndash_get_lesson_list_args', $lessons_args, $id, $course_id );
	if ( ! empty( $lessons_args ) ) {
		return ld_lesson_list( $lessons_args );
	}

	return array();
}

/**
 * LEGACY: Gets the lesson list output for a course.
 *
 * Replaced by `learndash_get_course_lessons_list` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $course       Optional. The `WP_Post` course object or course ID. Default null.
 * @param int|null         $user_id      Optional. User ID. Default null.
 * @param array            $lessons_args Optional. An array of query arguments to get lesson list. Default empty array.
 *
 * @return array The lesson list array.
 */
function learndash_get_course_lessons_list_legacy( $course = null, $user_id = null, $lessons_args = array() ) {
	if ( empty( $course ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( is_numeric( $course ) ) {
		$course_id = $course;
		$course    = get_post( $course_id );
	}

	if ( empty( $course->ID ) ) {
		return array();
	}

	$course_settings = learndash_get_setting( $course );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );

	$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? ( $lessons_options['orderby'] ?? '' ) : $course_settings['course_lesson_orderby'];
	$order   = ( empty( $course_settings['course_lesson_order'] ) ) ? ( $lessons_options['order'] ?? '' ) : $course_settings['course_lesson_order'];

	$lesson_query_pagination = 'true';
	if ( ( isset( $lessons_args['num'] ) ) && ( $lessons_args['num'] !== false ) ) {
		if ( intval( $lessons_args['num'] ) == 0 ) {
			$lesson_query_pagination = '';
			$posts_per_page          = -1;
		} else {
			$posts_per_page = intval( $lessons_args['num'] );
		}
	} else {
		$posts_per_page = learndash_get_course_lessons_per_page( $course->ID );
		if ( empty( $posts_per_page ) ) {
			$posts_per_page          = -1;
			$lesson_query_pagination = '';
		}
	}

	$lesson_paged = 1;
	if ( isset( $lessons_args['paged'] ) ) {
		$lesson_paged = intval( $lessons_args['paged'] );
	} elseif ( isset( $_GET['ld-lesson-page'] ) ) {
		$lesson_paged = intval( $_GET['ld-lesson-page'] );
	}

	if ( empty( $lesson_paged ) ) {
		$lesson_paged = 1;
	}

	$opt = array(
		'post_type'      => 'sfwd-lessons',
		'meta_key'       => 'course_id',
		'meta_value'     => $course->ID,
		'order'          => $order,
		'orderby'        => $orderby,
		'posts_per_page' => $posts_per_page,
		'paged'          => $lesson_paged,
		'pagination'     => $lesson_query_pagination,
		'pager_context'  => 'course_lessons',
		'return'         => 'array',
		'user_id'        => $user_id,
		'course_id'      => $course->ID,
	);
	$opt = wp_parse_args( $lessons_args, $opt );

	if ( learndash_is_course_shared_steps_enabled() ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course->ID );

		$lesson_ids = $ld_course_steps_object->get_children_steps( $course->ID, $opt['post_type'] );
		if ( ! empty( $lesson_ids ) ) {
			$opt['include']   = implode( ',', $lesson_ids );
			$opt['orderby']   = 'post__in';
			$opt['course_id'] = $course->ID;

			unset( $opt['order'] );
			unset( $opt['meta_key'] );
			unset( $opt['meta_value'] );
		} else {
			return array();
		}
	}

	$lessons = SFWD_CPT::loop_shortcode( $opt );
	return $lessons;
}

/**
 * LEGACY: Gets the topics list for a lesson.
 *
 * Replaced by `learndash_get_topic_list` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|null $for_lesson_id Optional. The ID of the lesson to get topics.
 * @param int|null $course_id     Optional. Course ID.
 *
 * @return array An array of topics list.
 */
function learndash_get_topic_list_legacy( $for_lesson_id = null, $course_id = null ) {
	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $for_lesson_id );
	}

	if ( ( ! empty( $for_lesson_id ) ) && ( ! empty( $course_id ) ) ) {
		$transient_key = 'learndash_lesson_topics_' . $course_id . '_' . $for_lesson_id;
	} elseif ( ! empty( $for_lesson_id ) ) {
		$transient_key = 'learndash_lesson_topics_' . $for_lesson_id;
	} else {
		$transient_key = 'learndash_lesson_topics_all';
	}

	$topics_array = LDLMS_Transients::get( $transient_key );

	if ( false === $topics_array ) {

		if ( ! empty( $for_lesson_id ) ) {

			$lessons_options = sfwd_lms_get_post_options( 'sfwd-lessons' );
			$orderby         = $lessons_options['orderby'];
			$order           = $lessons_options['order'];

			if ( ! empty( $course_id ) ) {
				$course_lessons_args = learndash_get_course_lessons_order( $course_id );
				$orderby             = isset( $course_lessons_args['orderby'] ) ? $course_lessons_args['orderby'] : 'title';
				$order               = isset( $course_lessons_args['order'] ) ? $course_lessons_args['order'] : 'ASC';
			}
		} else {
			$orderby = 'name';
			$order   = 'ASC';
		}

		$topics_query_args = array(
			'post_type'   => 'sfwd-topic',
			'numberposts' => -1,
			'orderby'     => $orderby,
			'order'       => $order,
		);

		if ( ! empty( $for_lesson_id ) ) {
			$topics_query_args['meta_key']     = 'lesson_id';
			$topics_query_args['meta_value']   = $for_lesson_id;
			$topics_query_args['meta_compare'] = '=';
		}

		if ( learndash_is_course_shared_steps_enabled() ) {
			if ( ! empty( $course_id ) ) {

				$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
				$ld_course_steps_object->load_steps();
				$steps = $ld_course_steps_object->get_steps();

				if ( ( isset( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) && ( ! empty( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) ) {
					$topic_ids                    = array_keys( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] );
					$topics_query_args['include'] = $topic_ids;
					$topics_query_args['orderby'] = 'post__in';

					unset( $topics_query_args['order'] );
					unset( $topics_query_args['meta_key'] );
					unset( $topics_query_args['meta_value'] );
					unset( $topics_query_args['meta_compare'] );
				} else {
					return array();
				}
			}
		}

		$topics = get_posts( $topics_query_args );

		if ( ! empty( $topics ) ) {
			if ( empty( $for_lesson_id ) ) {
				$topics_array = array();

				foreach ( $topics as $topic ) {
					if ( learndash_is_course_shared_steps_enabled() ) {
						$course_id = learndash_get_course_id( $topic->ID );
						$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic->ID );
					} else {
						$lesson_id = learndash_get_setting( $topic, 'lesson' );
					}

					if ( ! empty( $lesson_id ) ) {
						// Need to clear out the post_content before transient storage.
						$topic->post_content          = 'EMPTY';
						$topics_array[ $lesson_id ][] = $topic;
					}
				}
				LDLMS_Transients::set( $transient_key, $topics_array, MINUTE_IN_SECONDS );
				return $topics_array;
			} else {
				LDLMS_Transients::set( $transient_key, $topics, MINUTE_IN_SECONDS );
				return $topics;
			}
		}
	} else {
		return $topics_array;
	}

	return array();
}

/**
 * LEGACY: Gets the quiz list output for a course.
 *
 * Replaced by `learndash_get_course_quiz_list` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $course  Optional. The `WP_Post` course object or course ID. Default null.
 * @param int|null         $user_id Optional. User ID. Default null.
 *
 * @return array|string The quiz list HTML output.
 */
function learndash_get_course_quiz_list_legacy( $course = null, $user_id = null ) {
	if ( empty( $course ) ) {
		$course_id = learndash_get_course_id();
		$course    = get_post( $course_id );
	}

	if ( is_numeric( $course ) ) {
		$course_id = $course;
		$course    = get_post( $course_id );
	}

	if ( empty( $course->ID ) ) {
		return array();
	}

	$course_settings = learndash_get_setting( $course );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	$orderby         = ( empty( $course_settings['course_lesson_orderby'] ) ) ? ( $lessons_options['orderby'] ?? '' ) : $course_settings['course_lesson_orderby'];
	$order           = ( empty( $course_settings['course_lesson_order'] ) ) ? ( $lessons_options['order'] ?? '' ) : $course_settings['course_lesson_order'];
	$opt             = array(
		'post_type'      => 'sfwd-quiz',
		'meta_key'       => 'course_id',
		'meta_value'     => $course->ID,
		'order'          => $order,
		'orderby'        => $orderby,
		'posts_per_page' => -1,
		'user_id'        => $user_id,
		'return'         => 'array',
	);

	if ( learndash_is_course_shared_steps_enabled() ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course->ID );

		$lesson_ids = $ld_course_steps_object->get_children_steps( $course->ID, $opt['post_type'] );

		if ( ! empty( $lesson_ids ) ) {
			$opt['include']   = implode( ',', $lesson_ids );
			$opt['orderby']   = 'post__in';
			$opt['course_id'] = $course->ID;

			unset( $opt['order'] );
			unset( $opt['meta_key'] );
			unset( $opt['meta_value'] );
		} else {
			return array();
		}
	}
	$quizzes = SFWD_CPT::loop_shortcode( $opt );
	return $quizzes;
}

/**
 * LEGACY: Gets the quiz list output for a lesson.
 *
 * Replaced by `learndash_get_lesson_quiz_list` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post $lesson    The `WP_Post` lesson object or lesson ID.
 * @param int|null    $user_id   Optional. User ID. Default null.
 * @param int|null    $course_id Optional. Course ID. Default null.
 *
 * @return array|string The lesson quiz list HTML output.
 */
function learndash_get_lesson_quiz_list_legacy( $lesson, $user_id = null, $course_id = null ) {
	if ( is_numeric( $lesson ) ) {
		$lesson_id = $lesson;
		$lesson    = get_post( $lesson_id );
	}

	if ( empty( $lesson->ID ) ) {
		return array();
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson );
	}

	$course_settings = learndash_get_setting( $course_id );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	$orderby         = ( empty( $course_settings['course_lesson_orderby'] ) ) ? ( $lessons_options['orderby'] ?? '' ) : $course_settings['course_lesson_orderby'];
	$order           = ( empty( $course_settings['course_lesson_order'] ) ) ? ( $lessons_options['order'] ?? '' ) : $course_settings['course_lesson_order'];
	$opt             = array(
		'post_type'      => 'sfwd-quiz',
		'meta_key'       => 'lesson_id',
		'meta_value'     => $lesson->ID,
		'order'          => $order,
		'orderby'        => $orderby,
		'posts_per_page' => -1,
		'user_id'        => $user_id,
		'return'         => 'array',
		'course_id'      => $course_id,
	);

	if ( learndash_is_course_shared_steps_enabled() ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		if ( $ld_course_steps_object ) {
			$quiz_ids = $ld_course_steps_object->get_children_steps( $lesson->ID, $opt['post_type'] );
			if ( ! empty( $quiz_ids ) ) {
				$opt['include'] = implode( ',', $quiz_ids );
				$opt['orderby'] = 'post__in';

				unset( $opt['order'] );
				unset( $opt['meta_key'] );
				unset( $opt['meta_value'] );
			} else {
				return array();
			}
		}
	}

	$quizzes = SFWD_CPT::loop_shortcode( $opt );
	return $quizzes;
}

/**
 * LEGACY: Gets the quiz list for a resource.
 *
 * Replaced by `learndash_get_global_quiz_list` in 3.4.0.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $id Optional. An ID of the resource.
 *
 * @return array An array of quizzes.
 */
function learndash_get_global_quiz_list_legacy( $id = null ) {
	global $post;

	if ( empty( $id ) ) {
		if ( ! empty( $post->ID ) ) {
			$id = $post->ID;
		} else {
			return array();
		}
	}

	// COURSE ID CHANGE.
	$course_id = learndash_get_course_id( $id );
	if ( ! empty( $course_id ) ) {
		if ( learndash_is_course_shared_steps_enabled() ) {
			$quiz_ids = learndash_course_get_children_of_step( $course_id, $course_id, 'sfwd-quiz' );
			if ( ! empty( $quiz_ids ) ) {
				return get_posts(
					array(
						'post_type'      => 'sfwd-quiz',
						'posts_per_page' => -1,
						'include'        => $quiz_ids,
						'orderby'        => 'post__in',
						'order'          => 'ASC',
					)
				);

			}
		} else {
			$transient_key = 'learndash_quiz_course_' . $course_id;
			$quizzes_new   = LDLMS_Transients::get( $transient_key );
			if ( false === $quizzes_new ) {

				$course_settings = learndash_get_setting( $course_id );
				$lessons_options = learndash_get_option( 'sfwd-lessons' );
				$orderby         = ( empty( $course_settings['course_lesson_orderby'] ) ) ? ( $lessons_options['orderby'] ?? '' ) : $course_settings['course_lesson_orderby'];
				$order           = ( empty( $course_settings['course_lesson_order'] ) ) ? ( $lessons_options['order'] ?? '' ) : $course_settings['course_lesson_order'];

				$quizzes = get_posts(
					array(
						'post_type'      => 'sfwd-quiz',
						'posts_per_page' => -1,
						'meta_key'       => 'course_id',
						'meta_value'     => $course_id,
						'meta_compare'   => '=',
						'orderby'        => $orderby,
						'order'          => $order,
					)
				);

				$quizzes_new = array();

				foreach ( $quizzes as $k => $quiz ) {
					$quiz_lesson = learndash_get_setting( $quiz, 'lesson' );
					if ( empty( $quiz_lesson ) ) {
						$quizzes_new[] = $quizzes[ $k ];
					}
				}

				LDLMS_Transients::set( $transient_key, $quizzes_new, MINUTE_IN_SECONDS );
			}
			return $quizzes_new;
		}
	}

	return array();
}

/**
 * LEGACY: Gets the course data for the course builder.
 *
 * Replaced by `learndash_get_course_data` in 3.4.0.
 *
 * @since 3.4.0
 *
 * @param array $data The data passed down to the front-end.
 *
 * @return array The data passed down to the front-end.
 */
function learndash_get_course_data_legacy( $data ) {
	global $pagenow, $typenow;

	$output_lessons = array();
	$output_quizzes = array();
	$sections       = array();

	if ( ( 'post.php' === $pagenow ) && ( learndash_get_post_type_slug( 'course' ) === $typenow ) ) {
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : get_the_ID();
		if ( ! empty( $course_id ) ) {
			// Get a list of lessons to loop.
			$lessons        = learndash_get_course_lessons_list( $course_id, null, array( 'num' => 0 ) );
			$output_lessons = array();
			$lesson_topics  = array();

			if ( ( is_array( $lessons ) ) && ( ! empty( $lessons ) ) ) {
				// Loop course's lessons.
				foreach ( $lessons as $lesson ) {
					$post = $lesson['post'];
					// Get lesson's topics.
					$topics        = learndash_topic_dots( $post->ID, false, 'array', null, $course_id );
					$output_topics = array();

					if ( ( is_array( $topics ) ) && ( ! empty( $topics ) ) ) {
						// Loop Topics.
						foreach ( $topics as $topic ) {
							// Get topic's quizzes.
							$topic_quizzes        = learndash_get_lesson_quiz_list( $topic->ID, null, $course_id );
							$output_topic_quizzes = array();

							if ( ( is_array( $topic_quizzes ) ) && ( ! empty( $topic_quizzes ) ) ) {
								// Loop Topic's Quizzes.
								foreach ( $topic_quizzes as $quiz ) {
									$quiz_post = $quiz['post'];

									$output_topic_quizzes[] = array(
										'ID'         => $quiz_post->ID,
										'expanded'   => true,
										'post_title' => $quiz_post->post_title,
										'type'       => $quiz_post->post_type,
										'url'        => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
										'edit_link'  => get_edit_post_link( $quiz_post->ID, '' ),
										'tree'       => array(),
									);
								}
							}

							$output_topics[] = array(
								'ID'         => $topic->ID,
								'expanded'   => true,
								'post_title' => $topic->post_title,
								'type'       => $topic->post_type,
								'url'        => learndash_get_step_permalink( $topic->ID, $course_id ),
								'edit_link'  => get_edit_post_link( $topic->ID, '' ),
								'tree'       => $output_topic_quizzes,
							);
						}
					}

					// Get lesson's quizzes.
					$quizzes        = learndash_get_lesson_quiz_list( $post->ID, null, $course_id );
					$output_quizzes = array();

					if ( ( is_array( $quizzes ) ) && ( ! empty( $quizzes ) ) ) {
						// Loop lesson's quizzes.
						foreach ( $quizzes as $quiz ) {
							$quiz_post = $quiz['post'];

							$output_quizzes[] = array(
								'ID'         => $quiz_post->ID,
								'expanded'   => true,
								'post_title' => $quiz_post->post_title,
								'type'       => $quiz_post->post_type,
								'url'        => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
								'edit_link'  => get_edit_post_link( $quiz_post->ID, '' ),
								'tree'       => array(),
							);
						}
					}

					// Output lesson with child tree.
					$output_lessons[] = array(
						'ID'         => $post->ID,
						'expanded'   => false,
						'post_title' => $post->post_title,
						'type'       => $post->post_type,
						'url'        => $lesson['permalink'],
						'edit_link'  => get_edit_post_link( $post->ID, '' ),
						'tree'       => array_merge( $output_topics, $output_quizzes ),
					);
				}
			}

			// Get a list of quizzes to loop.
			$quizzes        = learndash_get_course_quiz_list( $course_id );
			$output_quizzes = array();

			if ( ( is_array( $quizzes ) ) && ( ! empty( $quizzes ) ) ) {
				// Loop course's quizzes.
				foreach ( $quizzes as $quiz ) {
					$post = $quiz['post'];

					$output_quizzes[] = array(
						'ID'         => $post->ID,
						'expanded'   => true,
						'post_title' => $post->post_title,
						'type'       => $post->post_type,
						'url'        => learndash_get_step_permalink( $post->ID, $course_id ),
						'edit_link'  => get_edit_post_link( $post->ID, '' ),
						'tree'       => array(),
					);
				}
			}

			// Merge sections at Outline.
			$sections_raw = get_post_meta( $course_id, 'course_sections', true );
			$sections     = ! empty( $sections_raw ) ? json_decode( $sections_raw ) : array();

			if ( ( is_array( $sections ) ) && ( ! empty( $sections ) ) ) {
				foreach ( $sections as $section ) {
					array_splice( $output_lessons, (int) $section->order, 0, array( $section ) );
				}
			}
		}
	}

	// Output data.
	$data['outline'] = array(
		'lessons'  => $output_lessons,
		'quizzes'  => $output_quizzes,
		'sections' => $sections,
	);

	return $data;
}

/**
 * LEGACY: Gets the total count of lessons and topics for a given course ID.
 *
 * Replaced by `learndash_get_course_steps_count` in 3.4.0.
 *
 * @since 2.3.0
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The count of the course steps.
 */
function learndash_get_course_steps_count_legacy( $course_id = 0 ) {
	static $courses_steps = array();

	$course_id = absint( $course_id );

	if ( ! isset( $courses_steps[ $course_id ] ) ) {
		$courses_steps[ $course_id ] = 0;

		$course_steps = learndash_get_course_steps( $course_id );
		if ( ! empty( $course_steps ) ) {
			$courses_steps[ $course_id ] = count( $course_steps );
		}

		if ( learndash_has_global_quizzes( $course_id ) ) {
			$courses_steps[ $course_id ] += 1;
		}
	}

	return $courses_steps[ $course_id ];
}

/**
 * LEGACY: Outputs the current status of the course.
 *
 * Replaced by `learndash_course_status` in 3.4.0.
 *
 * @since 2.1.0
 * @since 2.5.8 Added $return_slug parameter.
 *
 * @param int      $id          Course ID to get status.
 * @param int|null $user_id     Optional. User ID. Default null.
 * @param boolean  $return_slug Optional. If false will return translatable string otherwise the status slug. Default false.
 *
 * @return string The current status of the course.
 */
function learndash_course_status_legacy( $id, $user_id = null, $return_slug = false ) {
	$course_status_str = '';

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $course_status_str;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = intval( $user_id );
	}

	$completed_on = get_user_meta( $user_id, 'course_completed_' . $id, true );
	if ( ! empty( $completed_on ) ) {
		if ( true === $return_slug ) {
			$course_status_str = 'completed';
		} else {
			$course_status_str = esc_html__( 'Completed', 'learndash' );
		}
	} else {
		$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
		/**
		 * We need a better solution for this. A central class to ensure
		 * correct data compliance and required elements are present. But
		 * for now adding this here.
		 * LEARNDASH-4868
		 */
		if ( ! is_array( $course_progress ) ) {
			$course_progress = array();
		}
		if ( ! isset( $course_progress[ $id ] ) ) {
			$course_progress[ $id ] = array();
		}
		if ( ! isset( $course_progress[ $id ]['completed'] ) ) {
			$course_progress[ $id ]['completed'] = 0;
		}
		if ( ! isset( $course_progress[ $id ]['total'] ) ) {
			$course_progress[ $id ]['total'] = 0;
		}

		/**
		 * Filters the recalculation of the course steps.
		 *
		 * @since 3.3.0
		 *
		 * @param bool  $recalculate_course_total_steps Recalculate course total steps. Default true.
		 * @param array $course_progress                Array of course progress.
		 * @param int   $user_id                        User ID.
		 * @param int   $course_id                      Course ID.
		 */
		if ( apply_filters( 'learndash_course_status_recalc_total_steps', true, $course_progress[ $id ], $user_id, $id ) ) {
			$course_steps_count = learndash_get_course_steps_count( $id );
			if ( ( ! empty( $course_steps_count ) ) && ( $course_steps_count < absint( $course_progress[ $id ]['total'] ) ) ) {
				$course_progress[ $id ]['total'] = $course_steps_count;

				// We also need to update the user meta since other functions will retrieve this data.
				update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );
			}
		}

		$has_completed_topic = false;

		if ( ! empty( $course_progress[ $id ] ) && ! empty( $course_progress[ $id ]['topics'] ) && is_array( $course_progress[ $id ]['topics'] ) ) {
			foreach ( $course_progress[ $id ]['topics'] as $lesson_topics ) {
				if ( ! empty( $lesson_topics ) && is_array( $lesson_topics ) ) {
					foreach ( $lesson_topics as $topic ) {
						if ( ! empty( $topic ) ) {
							$has_completed_topic = true;
							break;
						}
					}
				}

				if ( $has_completed_topic ) {
					break;
				}
			}
		}

		$quizzes = learndash_get_global_quiz_list( $id );
		if ( ! empty( $quizzes ) ) {
			$quizzes_incomplete = array();
			foreach ( $quizzes as $quiz ) {
				if ( learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) ) {
					$quizzes_incomplete[] = $quiz->ID;
				}
			}

			if ( ! empty( $quizzes_incomplete ) ) {
				$quiz_notstarted = true;
			} else {
				if ( has_filter( 'learndash_post_args_groups' ) ) {
					/**
					 * Filters whether to autocomplete courses with final quizzes after the first final quiz is completed.
					 *
					 * @since 3.2.0
					 *
					 * @param bool false   Action to auto complete course step.
					 * @param int $id      Course ID
					 * @param int $user_id User ID
					 */
					apply_filters_deprecated( 'learndash_prevent_course_autocompletion_multiple_final_quizzes', array( false, $id, $user_id ), '3.2.3', 'learndash_course_autocompletion_multiple_final_quizzes_step' );
				}

				/**
				 * Filters to autocomplete course with multiple final (global) quizzes when not all are complete.
				 *
				 * @since 3.2.3
				 *
				 * @param bool  $autocomplete_course_step Autocomplete course step. Default false.
				 * @param int   $id                       Course ID
				 * @param int   $user_id                  User ID
				 * @param array $quizzes                  Course Global Quiz Posts.
				 * @param array $quizzes_incomplete       Array of incomplete Quizzes IDs.
				 *
				 * @return bool True auto complete step, false do not auto complete step.
				 */
				$quiz_notstarted = apply_filters(
					'learndash_course_autocompletion_multiple_final_quizzes_step',
					false,
					$id,
					$user_id,
					$quizzes,
					$quizzes_incomplete
				);
			}

			if ( true !== $quiz_notstarted ) {
				$course_progress[ $id ]['completed'] += 1;
			}
		} else {
			$quiz_notstarted = true;
		}

		if ( ( empty( $course_progress[ $id ] ) || empty( $course_progress[ $id ]['lessons'] ) && ! $has_completed_topic ) && $quiz_notstarted ) {
			if ( true === $return_slug ) {
				$course_status_str = 'not-started';
			} else {
				$course_status_str = esc_html__( 'Not Started', 'learndash' );
			}
		} elseif (
			empty( $course_progress[ $id ] )
			|| (
				isset( $course_progress[ $id ]['completed'] )
				&& isset( $course_progress[ $id ]['total'] )
				&& $course_progress[ $id ]['completed'] < $course_progress[ $id ]['total']
			)
		) {
			if ( true === $return_slug ) {
				$course_status_str = 'in-progress';
			} else {
				$course_status_str = esc_html__( 'In Progress', 'learndash' );
			}
		} elseif (
			isset( $course_progress[ $id ]['completed'] )
			&& isset( $course_progress[ $id ]['total'] )
			&& absint( $course_progress[ $id ]['completed'] ) === absint( $course_progress[ $id ]['total'] )
		) {
			if ( true === $return_slug ) {
				$course_status_str = 'completed';
			} else {
				$course_status_str = esc_html__( 'Completed', 'learndash' );
			}

			/**
			 * We call the standard mark complete function so it triggers the notifications etc.
			 */
			learndash_process_mark_complete( $user_id, $id, false, $id );
		}
	}

	if ( true === $return_slug ) {
		return $course_status_str;
	} else {
		/**
		 * Filters the current status of the course.
		 *
		 * @param string $course_status_str The translatable current course status string.
		 * @param int    $course_id         Course ID.
		 * @param int    $user_id           User ID.
		 * @param array  $name              Current course progress.
		 */
		return apply_filters(
			'learndash_course_status',
			$course_status_str,
			$id,
			$user_id,
			isset( $course_progress[ $id ] ) ? $course_progress[ $id ] : array()
		);
	}
}

/**
 * LEGACY: Checks if a lesson is not complete.
 *
 * Replaced by `learndash_is_lesson_notcomplete` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param array    $lessons   Optional. An array of lesson IDs.
 * @param int      $course_id Optional. Course ID. Default 0.
 *
 * @return boolean Returns true if the lesson is not complete otherwise false.
 */
function learndash_is_lesson_notcomplete_legacy( $user_id = null, $lessons = array(), $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		$use_lesson_course = true;
	} else {
		$use_lesson_course = false;
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ! empty( $lessons ) ) {
		foreach ( $lessons as $lesson => $v ) {
			if ( true === $use_lesson_course ) {
				$course_id = learndash_get_course_id( $lesson );
			}

			if ( ! empty( $course_progress[ $course_id ] ) && ! empty( $course_progress[ $course_id ]['lessons'] ) && ! empty( $course_progress[ $course_id ]['lessons'][ $lesson ] ) ) {
				unset( $lessons[ $lesson ] );
			}
		}
	}

	if ( empty( $lessons ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * LEGACY: Checks if a topic is not complete.
 *
 * Replaced by `learndash_is_topic_notcomplete` in 3.4.0.
 *
 * @since 2.3.1
 * @since 3.2.0 Added `$course_id` parameter
 *
 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param array    $topics    Optional. An array of topic IDs.
 * @param int      $course_id Optional. Course ID.
 *
 * @return boolean Returns true if the topic is not completed otherwise false.
 */
function learndash_is_topic_notcomplete_legacy( $user_id = null, $topics = array(), $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		$use_topic_course = true;
	} else {
		$use_topic_course = false;
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ! empty( $topics ) ) {
		foreach ( $topics as $topic_id => $v ) {
			if ( true === $use_topic_course ) {
				$course_id = learndash_get_course_id( $topic_id );
			}
			$lesson_id = learndash_get_lesson_id( $topic_id );

			if ( ( isset( $course_progress[ $course_id ] ) )
				&& ( ! empty( $course_progress[ $course_id ] ) )
				&& ( isset( $course_progress[ $course_id ]['topics'] ) )
				&& ( ! empty( $course_progress[ $course_id ]['topics'] ) )
				&& ( isset( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) )
				&& ( ! empty( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) ) ) {
				unset( $topics[ $topic_id ] );
			}
		}
	}

	if ( empty( $topics ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Checks if the quiz is accessible to the user (legacy).
 *
 * Replaced by `learndash_is_quiz_accessable` in 3.4.0.
 *
 * @since 2.4.0
 *
 * @param int|null     $user_id   Optional. User ID. Default null.
 * @param WP_Post|null $post      Optional. The `WP_Post` quiz object. Default null.
 * @param int          $course_id Optional. Course ID. Default 0.
 *
 * @return int Returns 1 if the quiz is accessible by the user otherwise 0.
 */
function learndash_is_quiz_accessable_legacy( $user_id = null, $post = null, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return 1;
		}

		$user_id = $current_user->ID;
	}

	if ( ( ! empty( $post ) ) && ( $post instanceof WP_Post ) ) {
		if ( empty( $course_id ) ) {
			$course_id = learndash_get_course_id( $post );
		}
		$course_id = absint( $course_id );

		if ( learndash_is_course_shared_steps_enabled() ) {
			$quiz_lesson = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$quiz_lesson = learndash_get_setting( $post, 'lesson' );
		}

		if ( ! empty( $quiz_lesson ) ) {
			$quiz_lesson_post = get_post( $quiz_lesson );
			if ( ( $quiz_lesson_post instanceof WP_Post ) && ( 'sfwd-topic' === $quiz_lesson_post->post_type ) ) {
				return 1;
			} elseif ( learndash_lesson_topics_completed( $quiz_lesson ) ) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			if ( ! empty( $course_progress ) && ! empty( $course_progress[ $course_id ] ) && ! empty( $course_progress[ $course_id ]['total'] ) ) {
				$completed = intVal( $course_progress[ $course_id ]['completed'] );
				$total     = intVal( $course_progress[ $course_id ]['total'] );

				if ( $completed >= $total - 1 ) {
					return 1;
				}
			}

			$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

			if ( empty( $lessons ) ) {
				return 1;
			}
		}
	}
	return 0;
}

/**
 * LEGACY: Gets the user's current course progress.
 *
 * Replaced by `learndash_get_course_progress` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Default null.
 * @param int|null $postid    Optional. Post ID. Default null.
 * @param int|null $course_id Optional. Course ID. Default null.
 *
 * @return array An array of user's current course progress.
 */
function learndash_get_course_progress_legacy( $user_id = null, $postid = null, $course_id = null ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return null;
		}

		$user_id = $current_user->ID;
	}

	$posts = array();

	$posts = array();

	if ( is_null( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
	$this_post       = get_post( $postid );

	if ( empty( $course_progress ) ) {
		$course_progress = array();
	}

	if ( 'sfwd-lessons' === $this_post->post_type ) {
		$posts = learndash_get_lesson_list( $postid, array( 'num' => 0 ) );

		if ( empty( $course_progress ) || empty( $course_progress[ $course_id ]['lessons'] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress[ $course_id ]['lessons'];
		}
	} elseif ( 'sfwd-topic' === $this_post->post_type ) {
		if ( learndash_is_course_shared_steps_enabled() ) {
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $this_post->ID );
		} else {
			$lesson_id = learndash_get_setting( $this_post, 'lesson' );
		}
		$posts = learndash_get_topic_list( $lesson_id, $course_id );

		if ( empty( $course_progress ) || empty( $course_progress[ $course_id ]['topics'][ $lesson_id ] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress[ $course_id ]['topics'][ $lesson_id ];
		}
	}

	$temp   = '';
	$prev_p = '';
	$next_p = '';
	$this_p = '';

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $k => $post ) {

			if ( $post instanceof WP_Post ) {

				if ( ! empty( $completed_posts[ $post->ID ] ) ) {
					$posts[ $k ]->completed = 1;
				} else {
					$posts[ $k ]->completed = 0;
				}

				if ( $post->ID == $postid ) {
					$this_p = $post;
					$prev_p = $temp;
				}

				if ( ! empty( $temp->ID ) && $temp->ID == $postid ) {
					$next_p = $post;
				}

				$temp = $post;
			}
		}
	} else {
		$posts = array();
	}

	return array(
		'posts' => $posts,
		'this'  => $this_p,
		'prev'  => $prev_p,
		'next'  => $next_p,
	);
}

/**
 * LEGACY: Gets all the lessons and topics for a given course ID.
 *
 * For now excludes quizzes at lesson and topic level.
 *
 * Replaced by `learndash_get_course_steps` in 3.4.0.
 *
 * @since 2.3.0
 *
 * @param int   $course_id          Optional. The ID of the course. Default 0.
 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
 *
 * @return array An array of all course steps.
 */
function learndash_get_course_steps_legacy( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {

	// The steps array will hold all the individual step counts for each post_type.
	$steps = array();

	// This will hold the combined steps post ids once we have run all queries.
	$steps_all = array();

	if ( ! empty( $course_id ) ) {
		if ( learndash_is_course_builder_enabled() ) {
			foreach ( $include_post_types as $post_type ) {
				$steps[ $post_type ] = learndash_course_get_steps_by_type( $course_id, $post_type );
			}
		} else {
			if ( ( in_array( 'sfwd-lessons', $include_post_types, true ) ) || ( in_array( 'sfwd-topic', $include_post_types, true ) ) ) {
				$lesson_steps_query_args = array(
					'post_type'      => 'sfwd-lessons',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => 'course_id',
							'value'   => intval( $course_id ),
							'compare' => '=',
							'type'    => 'NUMERIC',
						),
					),
				);

				$lesson_steps_query = new WP_Query( $lesson_steps_query_args );
				if ( $lesson_steps_query->have_posts() ) {
					$steps['sfwd-lessons'] = $lesson_steps_query->posts;
				}
			}

			// For Topics we still require the parent lessons items.
			if ( in_array( 'sfwd-topic', $include_post_types, true ) ) {

				if ( ! empty( $steps['sfwd-lessons'] ) ) {
					$topic_steps_query_args = array(
						'post_type'      => 'sfwd-topic',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'fields'         => 'ids',
						'meta_query'     => array(
							array(
								'key'     => 'course_id',
								'value'   => intval( $course_id ),
								'compare' => '=',
								'type'    => 'NUMERIC',
							),
						),
					);

					if ( ( isset( $steps['sfwd-lessons'] ) ) && ( ! empty( $steps['sfwd-lessons'] ) ) ) {
						$topic_steps_query_args['meta_query'][] = array(
							'key'     => 'lesson_id',
							'value'   => $steps['sfwd-lessons'],
							'compare' => 'IN',
							'type'    => 'NUMERIC',
						);
					}

					$topic_steps_query = new WP_Query( $topic_steps_query_args );
					if ( $topic_steps_query->have_posts() ) {
						$steps['sfwd-topic'] = $topic_steps_query->posts;
					}
				} else {
					$steps['sfwd-topic'] = array();
				}
			}
		}
	}

	foreach ( $include_post_types as $post_type ) {
		if ( ( isset( $steps[ $post_type ] ) ) && ( ! empty( $steps[ $post_type ] ) ) ) {
			$steps_all = array_merge( $steps_all, $steps[ $post_type ] );
		}
	}

	return $steps_all;
}

/**
 * LEGACY: Updates the user meta with completion status for any resource.
 *
 * Replaced by `learndash_process_mark_complete` in 3.4.0.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id       Optional. User ID. Default null.
 * @param int|null $postid        Optional. The ID of the resource like course, lesson, topic, etc. Default null.
 * @param boolean  $onlycalculate Optional. Whether to mark the resource as complete. Default false.
 * @param int      $course_id     Optional. Course ID. Default 0.
 *
 * @return boolean Returns true if the meta is updated successfully otherwise false.
 */
function learndash_process_mark_complete_legacy( $user_id = null, $postid = null, $onlycalculate = false, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		} else {
			return false;
		}
	} else {
		$current_user = get_user_by( 'id', $user_id );
	}

	$post = get_post( $postid );
	if ( ! ( $post instanceof WP_Post ) ) {
		return false;
	}

	if ( ! $onlycalculate ) {

		/**
		 * Filters whether to mark a process complete.
		 *
		 * @since 2.1.0
		 *
		 * @param boolean $mark_complete Whether to mark a process complete.
		 * @param WP_Post $post          WP_Post object to be checked.
		 * @param WP_User $current_user  Current logged in WP_User object.
		 */
		$process_completion = apply_filters( 'learndash_process_mark_complete', true, $post, $current_user );

		if ( ! $process_completion ) {
			return false;
		}
	}

	if ( 'sfwd-topic' === $post->post_type ) {
		if ( learndash_is_course_builder_enabled() ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post->ID );
			}
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	if ( empty( $course_id ) ) {
		return false;
	}

	$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ( empty( $course_progress ) ) || ( ! is_array( $course_progress ) ) ) {
		$course_progress = array();
	}

	if ( ( ! isset( $course_progress[ $course_id ] ) ) || ( empty( $course_progress[ $course_id ] ) ) ) {
		$course_progress[ $course_id ] = array(
			'lessons' => array(),
			'topics'  => array(),
		);
	}

	if ( ( ! isset( $course_progress[ $course_id ]['lessons'] ) ) || ( empty( $course_progress[ $course_id ]['lessons'] ) ) ) {
		$course_progress[ $course_id ]['lessons'] = array();
	}

	if ( ( ! isset( $course_progress[ $course_id ]['topics'] ) ) || ( empty( $course_progress[ $course_id ]['topics'] ) ) ) {
		$course_progress[ $course_id ]['topics'] = array();
	}

	if ( 'sfwd-topic' === $post->post_type && empty( $course_progress[ $course_id ]['topics'][ $lesson_id ] ) ) {
		$course_progress[ $course_id ]['topics'][ $lesson_id ] = array();
	}

	$lesson_completed = false;
	$topic_completed  = false;

	if ( ! $onlycalculate && 'sfwd-lessons' === $post->post_type && empty( $course_progress[ $course_id ]['lessons'][ $postid ] ) ) {
		$course_progress[ $course_id ]['lessons'][ $postid ] = 1;
		$lesson_completed                                    = true;
	}

	if ( ! $onlycalculate && 'sfwd-topic' === $post->post_type && empty( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $postid ] ) ) {
		$course_progress[ $course_id ]['topics'][ $lesson_id ][ $postid ] = 1;
		$topic_completed = true;
	}

	$completed_old = isset( $course_progress[ $course_id ]['completed'] ) ? $course_progress[ $course_id ]['completed'] : 0;

	$completed = learndash_course_get_completed_steps( $user_id, $course_id, $course_progress[ $course_id ] );

	$course_progress[ $course_id ]['completed'] = $completed;

	// New logic includes lessons and topics.
	$course_progress[ $course_id ]['total'] = learndash_get_course_steps_count( $course_id );

	/**
	 * Track the last post_id (Lesson, Topic, Quiz) seen by user.
	 *
	 * @since 2.1.0
	 */
	$course_progress[ $course_id ]['last_id'] = $post->ID;

	$course_completed_time = time();
	// If course is completed.
	if ( ( $course_progress[ $course_id ]['completed'] >= $completed_old ) && ( $course_progress[ $course_id ]['completed'] >= $course_progress[ $course_id ]['total'] ) ) {

		/**
		 * Fires before the course is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $course_data An array of course complete data.
		 */
		do_action(
			'learndash_before_course_completed',
			array(
				'user'           => $current_user,
				'course'         => get_post( $course_id ),
				'progress'       => $course_progress,
				'completed_time' => $course_completed_time,
			)
		);
		add_user_meta( $current_user->ID, 'course_completed_' . $course_id, $course_completed_time, true );
	} else {
		delete_user_meta( $current_user->ID, 'course_completed_' . $course_id );
	}

	update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );

	if ( ! empty( $topic_completed ) ) {

		/**
		 * Fires after the topic is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $topic_data An array of topic complete data.
		 */
		do_action(
			'learndash_topic_completed',
			array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => get_post( $lesson_id ),
				'topic'    => $post,
				'progress' => $course_progress,
			)
		);

		learndash_update_user_activity(
			array(
				'course_id'          => $course_id,
				'user_id'            => $current_user->ID,
				'post_id'            => $post->ID,
				'activity_type'      => 'topic',
				'activity_status'    => true,
				'activity_completed' => time(),
				'activity_meta'      => array(
					'steps_total'     => $course_progress[ $course_id ]['total'],
					'steps_completed' => $course_progress[ $course_id ]['completed'],
				),

			)
		);

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! $course_activity ) {
			learndash_update_user_activity(
				array(
					'course_id'       => $course_id,
					'user_id'         => $current_user->ID,
					'post_id'         => $course_id,
					'activity_type'   => 'course',
					'activity_status' => false,
					'activity_meta'   => array(
						'steps_total'     => $course_progress[ $course_id ]['total'],
						'steps_completed' => $course_progress[ $course_id ]['completed'],
						'steps_last_id'   => $post->ID,
					),
				)
			);
		} else {
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_total', $course_progress[ $course_id ]['total'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_completed', $course_progress[ $course_id ]['completed'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_last_id', $post->ID );
		}
	}

	if ( ! empty( $lesson_completed ) ) {

		/**
		 * Fires after the lesson is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $lesson_data An array of lesson complete data.
		 */
		do_action(
			'learndash_lesson_completed',
			array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => $post,
				'progress' => $course_progress,
			)
		);

		learndash_update_user_activity(
			array(
				'course_id'          => $course_id,
				'user_id'            => $current_user->ID,
				'post_id'            => $post->ID,
				'activity_type'      => 'lesson',
				'activity_status'    => true,
				'activity_completed' => time(),
				'activity_meta'      => array(
					'steps_total'     => $course_progress[ $course_id ]['total'],
					'steps_completed' => $course_progress[ $course_id ]['completed'],
				),

			)
		);

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! $course_activity ) {

			learndash_update_user_activity(
				array(
					'course_id'       => $course_id,
					'user_id'         => $current_user->ID,
					'post_id'         => $course_id,
					'activity_type'   => 'course',
					'activity_status' => false,
					'activity_meta'   => array(
						'steps_total'     => $course_progress[ $course_id ]['total'],
						'steps_completed' => $course_progress[ $course_id ]['completed'],
						'steps_last_id'   => $post->ID,
					),
				)
			);
		} else {
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_total', $course_progress[ $course_id ]['total'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_completed', $course_progress[ $course_id ]['completed'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_last_id', $post->ID );
		}
	}

	if ( $course_progress[ $course_id ]['completed'] >= $completed_old && $course_progress[ $course_id ]['total'] == $course_progress[ $course_id ]['completed'] ) {
		$do_course_complete_action = false;

		$course_args = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);

		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! empty( $course_activity ) ) {
			$course_args = json_decode( wp_json_encode( $course_activity ), true );

			if ( true != $course_activity->activity_status ) {
				$course_args['activity_status']    = true;
				$course_args['activity_completed'] = time();
				$course_args['activity_updated']   = time();

				$do_course_complete_action = true;
			}
		} else {
			// If no activity record found.
			$course_args['activity_status']    = true;
			$course_args['activity_started']   = time();
			$course_args['activity_completed'] = time();
			$course_args['activity_updated']   = time();

			$do_course_complete_action = true;
		}

		$course_args['activity_meta'] = array(
			'steps_total'     => $course_progress[ $course_id ]['total'],
			'steps_completed' => $course_progress[ $course_id ]['completed'],
			'steps_last_id'   => $post->ID,
		);

		learndash_update_user_activity( $course_args );

		if ( true == $do_course_complete_action ) {

			/**
			 * Fires after the course is marked completed.
			 *
			 * @since 2.1.0
			 *
			 * @param array $course_data An array of course complete data.
			 */
			do_action(
				'learndash_course_completed',
				array(
					'user'             => $current_user,
					'course'           => get_post( $course_id ),
					'progress'         => $course_progress,
					'course_completed' => $course_completed_time,
				)
			);
		}
	} else {

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( $course_activity ) {
			$course_args['activity_completed'] = 0;
			$course_args['activity_status']    = false;

			if ( empty( $course_progress[ $course_id ]['completed'] ) ) {
				$course_args['activity_updated'] = 0;
			}
			$course_args['activity_meta'] = array(
				'steps_total'     => $course_progress[ $course_id ]['total'],
				'steps_completed' => $course_progress[ $course_id ]['completed'],
				'steps_last_id'   => $post->ID,
			);
			learndash_update_user_activity( $course_args );
		}
	}

	return true;

}

/**
 * LEGACY: Gets the total completed steps for a given course progress array.
 *
 * Replaced by `learndash_course_get_completed_steps` in 3.4.0
 *
 * @since 2.3.0
 *
 * @param int   $user_id         Optional. The ID of the user. Default 0.
 * @param int   $course_id       Optional. The ID of the course. Default 0.
 * @param array $course_progress Optional. An array of course progress data. Default empty array.
 *
 * @return int The count of completed course steps.
 */
function learndash_course_get_completed_steps_legacy( $user_id = 0, $course_id = 0, $course_progress = array() ) {
	$steps_completed_count = 0;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {

		if ( empty( $course_progress ) ) {
			$course_progress_all = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( isset( $course_progress_all[ $course_id ] ) ) {
				$course_progress = $course_progress_all[ $course_id ];
			}
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
