<?php
/**
 * Function that help the user navigate through the course
 *
 * @since 2.1.0
 *
 * @package LearnDash\Navigation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the previous post link for lesson or topic.
 *
 * @since 2.1.0
 *
 * @param  string       $prev_link Default previous post link.
 * @param  boolean      $url       Whether to return URL instead of HTML output.
 * @param  WP_Post|null $post      The `WP_Post` object. Defaults to global post object.
 *
 * @return string Previous post link URL or HTML output.
 */
function learndash_previous_post_link( $prev_link = '', $url = false, $post = null ) {
	if ( empty( $post ) ) {
		global $post;
	}

	if ( empty( $post ) ) {
		return $prev_link;
	}

	if ( 'sfwd-lessons' === $post->post_type ) {
		$link_name = learndash_get_label_course_step_previous( learndash_get_post_type_slug( 'lesson' ) );

		$course_id = learndash_get_course_id( $post );
		$posts     = learndash_course_get_lessons( $course_id, array( 'per_page' => 0 ) );

	} elseif ( 'sfwd-topic' === $post->post_type ) {
		$link_name = learndash_get_label_course_step_previous( learndash_get_post_type_slug( 'topic' ) );

		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_id = learndash_get_course_id( $post );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
			$course_id = learndash_get_setting( $post, 'course' );
		}
		$posts = learndash_course_get_topics( $course_id, $lesson_id, array( 'per_page' => 0 ) );
	} else {
		return $prev_link;
	}

	$prev_post = null;
	foreach ( $posts as $k => $p ) {
		if ( is_a( $p, 'WP_Post' ) ) {
			if ( $p->ID == $post->ID ) {
				break;
			}
			$prev_post = $p;
		}
	}

	if ( ( $prev_post ) && ( is_a( $prev_post, 'WP_Post' ) ) ) {
		if ( 'id' === $url ) {
			return $prev_post->ID;
		} elseif ( $url ) {
			return get_permalink( $prev_post->ID );
		} else {
			$permalink = get_permalink( $prev_post->ID );
			if ( is_rtl() ) {
				$link_name_with_arrow = $link_name;
			} else {
				$link_name_with_arrow = '<span class="meta-nav">&larr;</span> ' . $link_name;
			}

			$link = '<a href="' . $permalink . '" class="prev-link" rel="prev">' . $link_name_with_arrow . '</a>';

			/**
			 * Filters course navigation previous post link output.
			 *
			 * @since 2.1.0
			 *
			 * @param string  $link      Previous post link output.
			 * @param string  $permalink Previous post permalink.
			 * @param string  $link_name Text shown inside previous post link.
			 * @param WP_Post $post      Post object.
			 */
			return apply_filters( 'learndash_previous_post_link', $link, $permalink, $link_name, $post );
		}
	} else {
		return $prev_link;
	}
}

/**
 * Generates the next post link for lesson or topic.
 *
 * @since 2.1.0
 *
 * @param  string       $prev_link Default next post link.
 * @param  boolean      $url       Whether to return URL instead of HTML output.
 * @param  WP_Post|null $post      The `WP_Post` object. Defaults to global post object.
 *
 * @return string Next post link URL or HTML output.
 */
function learndash_next_post_link( $prev_link = '', $url = false, $post = null ) {
	if ( empty( $post ) ) {
		global $post;
	}

	if ( empty( $post ) ) {
		return $prev_link;
	}

	if ( 'sfwd-lessons' === $post->post_type ) {
		$link_name = learndash_get_label_course_step_next( learndash_get_post_type_slug( 'lesson' ) );
		$course_id = learndash_get_course_id( $post );
		$posts     = learndash_course_get_lessons( $course_id, array( 'per_page' => 0 ) );
	} elseif ( 'sfwd-topic' === $post->post_type ) {
		$link_name = learndash_get_label_course_step_next( learndash_get_post_type_slug( 'topic' ) );

		if ( learndash_is_course_builder_enabled() ) {
			$course_id = learndash_get_course_id( $post->ID );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
			$course_id = learndash_get_setting( $post, 'course' );
		}
		$posts = learndash_course_get_topics( $course_id, $lesson_id, array( 'per_page' => 0 ) );
	} else {
		return $prev_link;
	}

	$found_at  = null;
	$next_post = null;
	foreach ( $posts as $k => $p ) {

		if ( is_a( $p, 'WP_Post' ) ) {
			if ( $p->ID == $post->ID ) {
				$found_at = $k;
			} elseif ( $found_at ) {
				$next_post = $p;
				break;
			}
		}
	}

	if ( ( $next_post ) && ( is_a( $next_post, 'WP_Post' ) ) ) {
		if ( 'id' === $url ) {
			return $next_post->ID;
		} elseif ( $url ) {
			return get_permalink( $next_post->ID );
		} else {
			$permalink = get_permalink( $next_post->ID );
			if ( is_rtl() ) {
				$link_name_with_arrow = $link_name;
			} else {
				$link_name_with_arrow = $link_name . ' <span class="meta-nav">&rarr;</span>';
			}

			$link = '<a href="' . $permalink . '" class="next-link" rel="next">' . $link_name_with_arrow . '</a>';

			/**
			 * Filters course navigation next post link output.
			 *
			 * @since 2.1.0
			 *
			 * @param string  $link      Next post link output.
			 * @param string  $permalink Next post permalink.
			 * @param string  $link_name Text shown inside next post link.
			 * @param WP_Post $post      Post object.
			 */
			return apply_filters( 'learndash_next_post_link', $link, $permalink, $link_name, $next_post );
		}
	} else {
		return $prev_link;
	}
}

/**
 * Hides the next/previous post links in certain situations.
 *
 * Fires on `previous_post_link` and `next_post_link` hook.
 *
 * @since 2.1.0
 *
 * @param string $prev_link The next/previous post link.
 *
 * @return string The next/previous post link.
 */
function learndash_clear_prev_next_links( $prev_link = '' ) {
	global $post;

	if ( ! is_singular() || empty( $post->post_type ) || ! in_array( $post->post_type, array( 'sfwd-quiz', 'sfwd-courses', 'sfwd-topic', 'sfwd-assignment' ), true ) ) {
		return $prev_link;
	} else {
		return '';
	}
}
add_filter( 'previous_post_link', 'learndash_clear_prev_next_links', 1 );
add_filter( 'next_post_link', 'learndash_clear_prev_next_links', 1 );

/**
 * Outputs the quiz continue link.
 *
 * @param int $id Quiz ID.
 *
 * @return string The quiz continue link output.
 */
function learndash_quiz_continue_link( $id ) {
	$course_id = learndash_get_course_id( $id );
	if ( ( ! empty( $course_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) ) {
		$lesson_id = learndash_course_get_single_parent_step( $course_id, $id );
		if ( empty( $lesson_id ) ) {
			$url = get_permalink( $course_id );
			$url = add_query_arg(
				array(
					'quiz_type'     => 'global',
					'quiz_redirect' => 1,
					'course_id'     => $course_id,
					'quiz_id'       => $id,
					'next_step'     => '1',
				),
				$url
			);

		} else {
			$url = get_permalink( $lesson_id );
			$url = add_query_arg(
				array(
					'quiz_type'     => 'lesson',
					'quiz_redirect' => 1,
					'lesson_id'     => $lesson_id,
					'quiz_id'       => $id,
					'next_step'     => '1',
				),
				$url
			);
		}

		if ( ( isset( $url ) ) && ( ! empty( $url ) ) ) {
			$return_link = '<a id="quiz_continue_link" href="' . esc_url( $url ) . '">' . LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		}
	} else {
		$quizmeta = get_post_meta( $id, '_sfwd-quiz', true );

		if ( ! empty( $quizmeta['sfwd-quiz_lesson'] ) ) {
			$return_id   = (int) $quizmeta['sfwd-quiz_lesson'];
			$quiz_lesson = (int) $quizmeta['sfwd-quiz_lesson'];
		}

		if ( empty( $quiz_lesson ) ) {
			$url         = add_query_arg(
				array(
					'quiz_type'     => 'global',
					'quiz_redirect' => 1,
					'course_id'     => $course_id,
					'quiz_id'       => $id,
					'next_step'     => '1',
				),
				get_permalink( $course_id )
			);
			$return_link = '<a id="quiz_continue_link" href="' . esc_url( $url ) . '">' . LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		} else {
			$url         = add_query_arg(
				array(
					'quiz_type'     => 'lesson',
					'quiz_redirect' => 1,
					'course_id'     => $course_id,
					'lesson_id'     => $return_id,
					'quiz_id'       => $id,
					'next_step'     => '1',
				),
				get_permalink( $return_id )
			);
			$return_link = '<a id="quiz_continue_link" href="' . esc_url( $url ) . '">' . LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		}
	}

	// Why are we checking the WordPress version? Shouldn't this be checking the LD version??
	$version = get_bloginfo( 'version' );

	if ( $version >= '1.5.1' ) {

		/**
		 * Filters output of quiz continue link.
		 *
		 * @since 2.1.0
		 *
		 * @param string $return_link   Continue link output.
		 * @param string $url           Continue link url.
		 */
		return apply_filters( 'learndash_quiz_continue_link', $return_link, $url );

	} else {

		/** This filter is documented in includes/course/ld-course-navigation.php */
		return apply_filters( 'learndash_quiz_continue_link', $return_link );

	}
}

/**
 * Outputs the LearnDash topic dots.
 *
 * Indicates the name of the topic and whether it's been completed
 *
 * @since 2.1.0
 *
 * @param int      $lesson_id Lesson ID.
 * @param boolean  $show_text Whether to show text.
 * @param string   $type      The type of dots. Value can be 'dots', 'list' or 'array'.
 * @param int|null $user_id   User ID.
 * @param int|null $course_id Course ID.
 *
 * @return string|array The topic dots output or an array of topics.
 */
function learndash_topic_dots( $lesson_id, $show_text = false, $type = 'dots', $user_id = null, $course_id = null ) {
	if ( empty( $lesson_id ) ) {
		return '';
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}

	$topics = learndash_get_topic_list( $lesson_id, $course_id );
	if ( empty( $topics[0]->ID ) ) {
		return '';
	}

	$topics_progress = learndash_get_course_progress( $user_id, $topics[0]->ID, $course_id );

	if ( ! empty( $topics_progress['posts'][0] ) ) {
		$topics = $topics_progress['posts'];
	}

	if ( 'array' == $type ) {
		return $topics;
	}

	$html = "<div id='learndash_topic_dots-" . esc_attr( $lesson_id ) . "' class='learndash_topic_dots type-" . esc_attr( $type ) . "'>";

	if ( ! empty( $show_text ) ) {
		$html .= '<strong>' . esc_html( $show_text ) . '</strong>';
	}

	switch ( $type ) {
		case 'list':
			$html .= '<ul>';
			$sn    = 0;

			foreach ( $topics as $topic ) {
				$sn++;

				if ( $topic->completed ) {
					$completed = 'topic-completed';
				} else {
					$completed = 'topic-notcompleted';
				}

				/**
				 * Filters output of topic list dots.
				 *
				 * @since 2.1.0
				 *
				 * @param string  $topic_dots  Topic dots output.
				 * @param WP_Post $topic       Topic WP_Post object to be checked.
				 * @param bool    $completed   A flag if a topic is completed or not.
				 * @param string  $type        Type of dots. Value can dots or list.
				 * @param mixed   $topic_count Count of topics.
				 */
				$html .= apply_filters(
					'learndash_topic_dots_item',
					"<li><a class='" . esc_attr( $completed ) . "' href='" . esc_url( get_permalink( $topic->ID ) ) . "'  title='" . esc_html( apply_filters( 'the_title', $topic->post_title, $topic->ID ) ) . "'><span>" . wp_kses_post( apply_filters( 'the_title', $topic->post_title, $topic->ID ) ) . '</span></a></li>', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.
					$topic,
					(bool) $topic->completed,
					$type,
					$sn
				);
			}

			$html .= '</ul>';
			break;

		case 'dots':
		default:
			$sn = 0;

			foreach ( $topics as $topic ) {
				$sn++;

				if ( $topic->completed ) {
					$completed = 'topic-completed';
				} else {
					$completed = 'topic-notcompleted';
				}

				/** This filter is documented in includes/course/ld-course-navigation.php */
				$html .= apply_filters( 'learndash_topic_dots_item', '<a class="' . esc_attr( $completed ) . '" href="' . esc_url( get_permalink( $topic->ID ) ) . '"><span title="' . esc_html( apply_filters( 'the_title', $topic->post_title, $topic->ID ) ) . '"></span></a>', $topic, $completed, $type, $sn ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
			}

			break;
	}

	$html .= '</div>';

	return $html;
}

/**
 * Gets the topics list for a lesson.
 *
 * @since 2.1.0
 *
 * @param int|null $lesson_id The ID of the lesson to get topics.
 * @param int|null $course_id     Course ID.
 *
 * @return array An array of topics list.
 */
function learndash_get_topic_list( $lesson_id = null, $course_id = null ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_topic_list_legacy( $lesson_id, $course_id );
	}

	$course_topics = array();

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}

	$lesson_id = absint( $lesson_id );
	$course_id = absint( $course_id );

	if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {
		$course_topics = learndash_course_get_topics( $course_id, $lesson_id );
		/**
		 * See the part from learndash_topic_dots() where this function is called.
		 * The logic expects the returned array to be indexed starting at zero.
		 */
		if ( ! empty( $course_topics ) ) {
			$course_topics = array_values( $course_topics );
		}
	}

	return $course_topics;
}

/**
 * Gets the quiz list for a resource.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $id An ID of the resource.
 *
 * @return array An array of quizzes.
 */
function learndash_get_global_quiz_list( $id = null ) {
	$quizzes = array();

	$course_id = learndash_get_course_id( $id );
	if ( ! empty( $course_id ) ) {
		if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
			return learndash_get_global_quiz_list_legacy( $id );
		}

		$quizzes = learndash_course_get_quizzes( $course_id, $course_id );
	}
	return $quizzes;
}

/**
 * Gets the lesson list output for a course.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $course_id  Optional. The course ID or `WP_Post` object. Default null.
 * @param int|null         $user_id    Optional. User ID. Default null.
 * @param array            $query_args Optional. An array of query arguments to get lesson list. Default empty array.
 *
 * @return array The lesson list array.
 */
function learndash_get_course_lessons_list( $course_id = null, $user_id = null, $query_args = array() ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_course_lessons_list_legacy( $course_id, $user_id, $query_args );
	}

	$lessons = array();

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	$course_post = get_post( $course_id );
	if ( ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
		$course_id = $course_post->ID;
	}

	if ( ! empty( $course_id ) ) {
		// Convert some legacy query parameters.
		if ( isset( $query_args['num'] ) ) {
			if ( ( ! isset( $query_args['per_page'] ) ) || ( empty( $query_args['per_page'] ) ) ) {
				$query_args['per_page'] = intval( $query_args['num'] );
			}
			unset( $query_args['num'] );
		}

		if ( isset( $query_args['posts_per_page'] ) ) {
			if ( ( ! isset( $query_args['per_page'] ) ) || ( empty( $query_args['per_page'] ) ) ) {
				$query_args['per_page'] = intval( $query_args['posts_per_page'] );
			}
			unset( $query_args['posts_per_page'] );
		}

		if ( isset( $query_args['pagination'] ) ) {
			if ( ! isset( $query_args['nopaging'] ) ) {
				if ( ( 'true' === $query_args['pagination'] ) || ( true === $query_args['pagination'] ) ) {
					$query_args['nopaging'] = false;
				} elseif ( ( 'false' === $query_args['pagination'] ) || ( false === $query_args['pagination'] ) ) {
					$query_args['nopaging'] = true;
				}
			}
			unset( $query_args['pagination'] );
		}

		if ( ! isset( $query_args['paged'] ) ) {
			if ( isset( $_GET['ld-lesson-page'] ) ) {
				$query_args['paged'] = intval( $_GET['ld-lesson-page'] );
			}
		}

		$query_args['return_type'] = 'WP_Post';

		$ld_course_object = LDLMS_Factory_Post::course( intval( $course_id ) );
		if ( ( $ld_course_object ) && ( is_a( $ld_course_object, 'LDLMS_Model_Course' ) ) ) {
			$course_lessons = $ld_course_object->get_lessons( $query_args );
			if ( ! empty( $course_lessons ) ) {
				$lessons_pager = $ld_course_object->get_pager( $course_id, learndash_get_post_type_slug( 'lesson' ) );
				$sno           = 1;
				if ( ( isset( $lessons_pager['per_page'] ) ) && ( isset( $lessons_pager['paged'] ) ) ) {
					$sno = 1 + ( absint( $lessons_pager['per_page'] ) * ( absint( $lessons_pager['paged'] ) - 1 ) );
				}
				foreach ( $course_lessons as $lesson_post ) {
					$lesson_item = array(
						'sno'                => $sno,
						'id'                 => $lesson_post->ID,
						'post'               => $lesson_post,
						'permalink'          => '',
						'class'              => '',
						'status'             => '',
						'sample'             => '',
						'sub_title'          => '',
						'lesson_access_from' => '',
					);

					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) {
						$lesson_item['permalink'] = learndash_get_step_permalink( $lesson_post->ID, $course_id );
					} else {
						$lesson_item['permalink'] = get_permalink( $lesson_post->ID );
					}

					$lesson_item['sample'] = ( learndash_is_sample( $lesson_post->ID ) ) ? 'is_sample' : 'is_not_sample';

					if ( ! learndash_is_lesson_notcomplete( $user_id, array( $lesson_post->ID => 1 ), $course_id ) ) {
						$lesson_item['status'] = 'completed';
					} else {
						$lesson_item['lesson_access_from'] = ld_lesson_access_from( $lesson_post->ID, $user_id, $course_id );

						if ( empty( $lesson_item['lesson_access_from'] ) ) {
							$lesson_item['status'] = 'notcompleted';
						} else {
							$lesson_item['status']    = 'notavailable';
							$lesson_item['sub_title'] = SFWD_LMS::get_template(
								'learndash_course_lesson_not_available',
								array(
									'user_id'   => $user_id,
									'course_id' => $course_id,
									'lesson_id' => $lesson_post->ID,
									'lesson_access_from_int' => $lesson_item['lesson_access_from'],
									'lesson_access_from_date' => learndash_adjust_date_time_display( $lesson_item['lesson_access_from'] ),
									'context'   => 'loop_content_shortcode',
								),
								false
							);
						}
					}

					$lessons[ $sno ] = $lesson_item;

					$sno++;
				}
			}

			/**
			 * Support the legacy pagination data structure and hook.
			 */
			$query_posts   = new WP_Query();
			$lessons_pager = $ld_course_object->get_pager( $course_id, learndash_get_post_type_slug( 'lesson' ) );
			if ( is_array( $lessons_pager ) ) {
				if ( isset( $lessons_pager['paged'] ) ) {
					$query_posts->query_vars['paged'] = intval( $lessons_pager['paged'] );
				}
				if ( isset( $lessons_pager['total_items'] ) ) {
					$query_posts->found_posts = absint( $lessons_pager['total_items'] );
				}
				if ( isset( $lessons_pager['total_pages'] ) ) {
					$query_posts->max_num_pages = $lessons_pager['total_pages'];
				}
			}

			/**
			 * Fires after the course lesson list pagination.
			 *
			 * @param WP_Query $query_posts   Course lesson list WP_Query object.
			 * @param string   $pager_context The context where pagination is shown.
			 */
			do_action(
				'learndash_course_lessons_list_pager',
				$query_posts,
				isset( $query_args['pager_context'] ) ? esc_attr( $query_args['pager_context'] ) : ''
			);
		}
	}

	return $lessons;
}

/**
 * Gets the quiz list output for a course.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $course  Optional. The `WP_Post` course object or course ID. Default null.
 * @param int|null         $user_id Optional. User ID. Default null.
 *
 * @return array|string The quiz list HTML output.
 */
function learndash_get_course_quiz_list( $course = null, $user_id = null ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_course_quiz_list_legacy( $course, $user_id );
	}

	$quizzes = array();

	if ( is_a( $course, 'WP_Post' ) ) {
		$course_id = $course->ID;
	} else {
		$course_post = get_post( absint( $course ) );
		if ( ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
			$course_id = $course_post->ID;
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( ! empty( $course_id ) ) {
		$course_quizzes = learndash_course_get_quizzes( $course_id, $course_id );
		if ( ! empty( $course_quizzes ) ) {
			$sno = 1;
			foreach ( $course_quizzes as $quiz_post ) {
				$quiz_item = array(
					'sno'                   => $sno,
					'id'                    => $quiz_post->ID,
					'post'                  => $quiz_post,
					'permalink'             => '',
					'class'                 => '',
					'status'                => '',
					'sample'                => '',
					'sub_title'             => '',
					'ld_lesson_access_from' => '',
				);

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) {
					$quiz_item['permalink'] = learndash_get_step_permalink( $quiz_post->ID, $course_id );
				} else {
					$quiz_item['permalink'] = get_permalink( $quiz_post->ID );
				}

				$quiz_item['sample'] = ( learndash_is_sample( $quiz_post->ID ) ) ? 'is_sample' : 'is_not_sample';
				$quiz_item['status'] = ( learndash_is_quiz_notcomplete( $user_id, array( $quiz_post->ID => 1 ), false, $course_id ) ) ? 'notcompleted' : 'completed';

				$quizzes[] = $quiz_item;
				$sno++;
			}
		}
	}

	return $quizzes;
}

/**
 * Gets the quiz list output for a lesson/topic.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post $lesson    The `WP_Post` lesson/topic object or ID.
 * @param int|null    $user_id   Optional. User ID. Default null.
 * @param int|null    $course_id Optional. Course ID. Default null.
 *
 * @return array|string The lesson quiz list HTML output.
 */
function learndash_get_lesson_quiz_list( $lesson, $user_id = null, $course_id = null ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_lesson_quiz_list_legacy( $lesson, $user_id, $course_id );
	}

	$quizzes   = array();
	$lesson_id = 0;

	if ( is_a( $lesson, 'WP_Post' ) ) {
		$lesson_id = $lesson->ID;
	} else {
		$lesson_post = get_post( absint( $lesson ) );
		if ( ( is_a( $lesson_post, 'WP_Post' ) ) && ( in_array( $lesson_post->post_type, learndash_get_post_type_slug( array( 'lesson', 'topic' ) ), true ) ) ) {
			$lesson_id = $lesson_post->ID;
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}

	if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {
		$course_quizzes = learndash_course_get_quizzes( $course_id, $lesson_id );
		if ( ! empty( $course_quizzes ) ) {

			$sno = 1;
			foreach ( $course_quizzes as $quiz_post ) {
				$quiz_item = array(
					'sno'                   => $sno,
					'id'                    => $quiz_post->ID,
					'post'                  => $quiz_post,
					'permalink'             => '',
					'class'                 => '',
					'status'                => '',
					'sample'                => '',
					'sub_title'             => '',
					'ld_lesson_access_from' => '',
				);

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) {
					$quiz_item['permalink'] = learndash_get_step_permalink( $quiz_post->ID, $course_id );
				} else {
					$quiz_item['permalink'] = get_permalink( $quiz_post->ID );
				}

				$quiz_item['sample'] = ( learndash_is_sample( $quiz_post->ID ) ) ? 'is_sample' : 'is_not_sample';
				$quiz_item['status'] = ( learndash_is_quiz_notcomplete( $user_id, array( $quiz_post->ID => 1 ), false, $course_id ) ) ? 'notcompleted' : 'completed';

				$quizzes[] = $quiz_item;

				$sno++;
			}
		}
	}

	return $quizzes;
}
