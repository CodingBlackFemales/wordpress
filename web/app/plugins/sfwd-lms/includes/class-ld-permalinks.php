<?php
/**
 * LearnDash Permalink functions
 *
 * @since 2.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Permalinks' ) ) {
	/**
	 * Class for LearnDash Permalinks
	 */
	class LearnDash_Permalinks {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			add_action( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );
			add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 4 );
			add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 10, 3 );
			add_filter( 'get_sample_permalink', array( $this, 'get_sample_permalink' ), 99, 5 );

			add_filter( 'attachment_link', array( $this, 'attachment_link' ), 10, 2 );
			add_action( 'comment_form_top', array( $this, 'comment_form_top' ) );
			add_action( 'comment_post', array( $this, 'comment_post' ), 30, 3 );
		}

		/**
		 * Setup custom rewrite URLs.
		 *
		 * Important note: This is very much dependant on the order of the registered post types. This is import when WP goes to parse the request. See
		 * the logic in wp-includes/class-wp.php starting in the loop at line 289 where it loops the registered CPTs. Within this loop at line 311 it
		 * set the queried post_type with the last matched post_type per the parse/marched request. So if the Quiz CPT is registered before Topic then
		 * when we try to match the /courses/course-slug/lessons/lesson-slug/topics/topic-slug/quizzes/quiz-slug/ the queried 'post_type' will be set to
		 * topic not quiz. As a result in LD v2.5 in includes/class-ld-lms.php where we build the $post_args array we ensure the order of the to-be
		 * CPTs.
		 *
		 * @param array $wp_rewrite Global WP rewrite array.
		 */
		public function generate_rewrite_rules( $wp_rewrite ) {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) {
				$courses_cpt = get_post_type_object( learndash_get_post_type_slug( 'course' ) );
				$lessons_cpt = get_post_type_object( learndash_get_post_type_slug( 'lesson' ) );
				$topics_cpt  = get_post_type_object( learndash_get_post_type_slug( 'topic' ) );
				$quizzes_cpt = get_post_type_object( learndash_get_post_type_slug( 'quiz' ) );

				$ld_rewrite_values = array(
					learndash_get_post_type_slug( 'course' ) => array(
						'name' => array(
							'placeholder' => '{{courses_cpt_name}}',
							'value'       => $courses_cpt->name,
						),
						'slug' => array(
							'placeholder' => '{{courses_cpt_slug}}',
							'value'       => $courses_cpt->rewrite['slug'],
						),
					),
					learndash_get_post_type_slug( 'lesson' ) => array(
						'name' => array(
							'placeholder' => '{{lessons_cpt_name}}',
							'value'       => $lessons_cpt->name,
						),
						'slug' => array(
							'placeholder' => '{{lessons_cpt_slug}}',
							'value'       => $lessons_cpt->rewrite['slug'],
						),
					),
					learndash_get_post_type_slug( 'topic' ) => array(
						'name' => array(
							'placeholder' => '{{topics_cpt_name}}',
							'value'       => $topics_cpt->name,
						),
						'slug' => array(
							'placeholder' => '{{topics_cpt_slug}}',
							'value'       => $topics_cpt->rewrite['slug'],
						),
					),
					learndash_get_post_type_slug( 'quiz' ) => array(
						'name' => array(
							'placeholder' => '{{quizzes_cpt_name}}',
							'value'       => $quizzes_cpt->name,
						),
						'slug' => array(
							'placeholder' => '{{quizzes_cpt_slug}}',
							'value'       => $quizzes_cpt->rewrite['slug'],
						),
					),
				);

				$ld_rewrite_patterns = array(
					// Course > Quiz.
					'{{courses_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{quizzes_cpt_name}}=$matches[2]&cpage=$matches[3]',
					'{{courses_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{quizzes_cpt_name}}=$matches[2]&page=$matches[3]',

					// Course > Lesson.
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&cpage=$matches[3]',
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&page=$matches[3]',

					// Course > Lesson > Quiz.
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{quizzes_cpt_name}}=$matches[3]&cpage=$matches[4]',
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{quizzes_cpt_name}}=$matches[3]&page=$matches[4]',

					// Course > Lesson > Topic.
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&cpage=$matches[4]',
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&page=$matches[4]',

					// Course > Lesson > Topic > Quiz.
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&{{quizzes_cpt_name}}=$matches[4]&cpage=$matches[5]',
					'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&{{quizzes_cpt_name}}=$matches[4]&page=$matches[5]',
				);

				$ld_rewrite_rules = array();
				if ( ( ! empty( $ld_rewrite_patterns ) ) && ( ! empty( $ld_rewrite_values ) ) ) {
					foreach ( $ld_rewrite_patterns as $rewrite_pattern_key => $rewrite_pattern_rule ) {
						foreach ( $ld_rewrite_values as $post_type_name => $ld_rewrite_values_sets ) {
							if ( ! empty( $ld_rewrite_values_sets ) ) {
								foreach ( $ld_rewrite_values_sets as $ld_rewrite_values_set_key => $ld_rewrite_values_set ) {
									if ( ! empty( $ld_rewrite_values_set ) ) {
										if ( ( ! isset( $ld_rewrite_values_set['placeholder'] ) ) || ( empty( $ld_rewrite_values_set['placeholder'] ) ) ) {
											continue;
										}
										if ( ( ! isset( $ld_rewrite_values_set['value'] ) ) || ( empty( $ld_rewrite_values_set['value'] ) ) ) {
											continue;
										}

										$rewrite_pattern_key  = str_replace( $ld_rewrite_values_set['placeholder'], $ld_rewrite_values_set['value'], $rewrite_pattern_key );
										$rewrite_pattern_rule = str_replace( $ld_rewrite_values_set['placeholder'], $ld_rewrite_values_set['value'], $rewrite_pattern_rule );
									}
								}
							}
						}
						$ld_rewrite_rules[ $rewrite_pattern_key ] = $rewrite_pattern_rule;
					}
				}

				/**
				 * Filters list of permalinks structure rules.
				 *
				 * @param array $permalink_structure An array of permalink structure rules. @since 2.5.0
				 * @param array $ld_rewrite_patterns An array of rewrite patterns. @since 3.1.4
				 * @param array $ld_rewrite_values   An array of rewrite placeholder/value pairs. @since 3.1.4
				 */
				$ld_rewrite_rules = apply_filters( 'learndash_permalinks_nested_urls', $ld_rewrite_rules, $ld_rewrite_patterns, $ld_rewrite_values );

				if ( ! empty( $ld_rewrite_rules ) ) {
					$wp_rewrite->rules = array_merge( $ld_rewrite_rules, $wp_rewrite->rules );
				}
			}
		}

		/**
		 * This second filter will correct calls to the WordPress get_permalink() function to use the new structure
		 *
		 * @param string $post_link  The post's permalink.
		 * @param Object $post       The WP_Post post in question.
		 * @param bool   $leave_name Whether to keep the post name.
		 * @param bool   $sample     Is it a sample permalink.
		 */
		public function post_type_link( $post_link = '', $post = null, $leave_name = false, $sample = false ) {
			global $pagenow, $wp_rewrite;

			$url_part_old = '';
			$url_part_new = '';

			if ( ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) && ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {

				// If we are viewing one of the list tables we only effect the link if the course_id URL param is set.
				if ( ( is_admin() ) && ( 'edit.php' == $pagenow ) ) {
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$course_id = 0;

						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$course_id = absint( $_GET['course_id'] );
						}
						$course_id = apply_filters( 'learndash_post_link_course_id', $course_id, $post_link, $post );
						if ( empty( $course_id ) ) {
							return $post_link;
						}
					}
				}

				$courses_cpt = get_post_type_object( learndash_get_post_type_slug( 'course' ) );
				$lessons_cpt = get_post_type_object( learndash_get_post_type_slug( 'lesson' ) );
				$topics_cpt  = get_post_type_object( learndash_get_post_type_slug( 'topic' ) );
				$quizzes_cpt = get_post_type_object( learndash_get_post_type_slug( 'quiz' ) );

				/**
				 * Filters the rewrite slug for a post type.
				 *
				 * @param string $rewrite_slug   Rewrite slug.
				 * @param string $post_type_slug Post type slug.
				 */
				$courses_cpt->rewrite['slug_alt'] = apply_filters( 'learndash_post_type_rewrite_slug', $courses_cpt->rewrite['slug'], learndash_get_post_type_slug( 'course' ) );
				/** This filter is documented in includes/class-ld-permalinks.php */
				$lessons_cpt->rewrite['slug_alt'] = apply_filters( 'learndash_post_type_rewrite_slug', $lessons_cpt->rewrite['slug'], learndash_get_post_type_slug( 'lesson' ) );
				/** This filter is documented in includes/class-ld-permalinks.php */
				$topics_cpt->rewrite['slug_alt'] = apply_filters( 'learndash_post_type_rewrite_slug', $topics_cpt->rewrite['slug'], learndash_get_post_type_slug( 'topic' ) );
				/** This filter is documented in includes/class-ld-permalinks.php */
				$quizzes_cpt->rewrite['slug_alt'] = apply_filters( 'learndash_post_type_rewrite_slug', $quizzes_cpt->rewrite['slug'], learndash_get_post_type_slug( 'quiz' ) );

				$draft_or_pending = in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ), true );

				if ( $lessons_cpt->name == $post->post_type ) {

					$lesson = $post;

					/**
					 * Filters course link post ID.
					 *
					 * @param int     $course_id Course ID.
					 * @param string  $post_link Post Link.
					 * @param WP_Post $post      Post Object.
					 */
					$course_id = apply_filters(
						'learndash_post_link_course_id',
						learndash_get_course_id( $lesson->ID ),
						$post_link,
						$post
					);

					if ( ! empty( $course_id ) ) {
						$course = get_post( $course_id );
						if ( $course instanceof WP_Post ) {
							$url_part_old = '';
							$url_part_new = '';

							$course_post_name = $course->post_name;
							if ( false === $sample ) {
								$lesson_post_name = $lesson->post_name;
							} else {
								$lesson_post_name = '%' . $lessons_cpt->name . '%';
							}

							if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
								// Old URL part.
								if ( strstr( $post_link, $lessons_cpt->rewrite['slug'] . '/' . $lesson_post_name ) ) {
									$url_part_old = '/' . $lessons_cpt->rewrite['slug'] . '/' . $lesson_post_name;
								} elseif ( strstr( $post_link, $lessons_cpt->rewrite['slug_alt'] . '/' . $lesson_post_name ) ) {
									$url_part_old = '/' . $lessons_cpt->rewrite['slug_alt'] . '/' . $lesson_post_name;
								}

								// New URL part.
								if ( ! strstr( $post_link, $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name ) ) {
									$url_part_new .= '/' . $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name;
								}
								$url_part_new .= '/' . $lessons_cpt->rewrite['slug_alt'] . '/' . $lesson_post_name;
							} else {
								// Old URL part.
								$url_part_old = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_old );

								// New URL part.
								$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
								$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
							}
						}
					}
				} elseif ( $topics_cpt->name == $post->post_type ) {

					$topic = $post;

					/** This filter is documented in includes/class-ld-permalinks.php */
					$course_id = apply_filters( 'learndash_post_link_course_id', learndash_get_course_id( $topic->ID ), $post_link, $post );
					if ( ! empty( $course_id ) ) {
						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic->ID );
						} else {
							$lesson_id = learndash_get_lesson_id( $topic->ID );
						}

						if ( ! empty( $lesson_id ) ) {
							$course = get_post( $course_id );
							$lesson = get_post( $lesson_id );

							if ( ( $course instanceof WP_Post ) && ( $lesson instanceof WP_Post ) ) {

								$course_post_name = $course->post_name;
								$lesson_post_name = $lesson->post_name;
								if ( false === $sample ) {
									$topic_post_name = $topic->post_name;
								} else {
									$topic_post_name = '%' . $topics_cpt->name . '%';
								}

								if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
									if ( strstr( $post_link, $topics_cpt->rewrite['slug'] . '/' . $topic_post_name ) ) {
										$url_part_old = '/' . $topics_cpt->rewrite['slug'] . '/' . $topic_post_name;
									} elseif ( strstr( $post_link, $topics_cpt->rewrite['slug_alt'] . '/' . $topic_post_name ) ) {
										$url_part_old = '/' . $topics_cpt->rewrite['slug_alt'] . '/' . $topic_post_name;
									}

									if ( ! strstr( $post_link, $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name ) ) {
										$url_part_new .= '/' . $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name;
									}
									if ( ! strstr( $post_link, $lessons_cpt->rewrite['slug_alt'] . '/' . $lesson_post_name ) ) {
										$url_part_new .= '/' . $lessons_cpt->rewrite['slug_alt'] . '/' . $lesson_post_name;
									}
									$url_part_new .= '/' . $topics_cpt->rewrite['slug_alt'] . '/' . $topic_post_name;

								} else {
									$url_part_old = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_old );

									$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
									$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
									$url_part_new = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_new );
								}
							}
						}
					}
				} elseif ( $quizzes_cpt->name == $post->post_type ) {
					$quiz = $post;

					/** This filter is documented in includes/class-ld-permalinks.php */
					$course_id = apply_filters( 'learndash_post_link_course_id', learndash_get_course_id( $quiz->ID ), $post_link, $post );

					if ( ! empty( $course_id ) ) {
						$course = get_post( $course_id );
						if ( $course instanceof WP_Post ) {

							$course_post_name = $course->post_name;
							if ( false === $sample ) {
								$quiz_post_name = $quiz->post_name;
							} else {
								$quiz_post_name = '%' . $quizzes_cpt->name . '%';
							}

							if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
								if ( strstr( $post_link, $quizzes_cpt->rewrite['slug'] . '/' . $quiz_post_name ) ) {
									$url_part_old = '/' . $quizzes_cpt->rewrite['slug'] . '/' . $quiz_post_name;
								} elseif ( strstr( $post_link, $quizzes_cpt->rewrite['slug_alt'] . '/' . $quiz_post_name ) ) {
									$url_part_old = '/' . $quizzes_cpt->rewrite['slug_alt'] . '/' . $quiz_post_name;
								}
							} else {
								$url_part_old = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_old );
							}

							$quiz_parents = array();

							if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
								$quiz_parents = learndash_course_get_all_parent_step_ids( $course_id, $quiz->ID );
							} else {
								$lesson_id = learndash_get_lesson_id( $quiz->ID );
								if ( ! empty( $lesson_id ) ) {
									if ( get_post_type( $lesson_id ) == $topics_cpt->name ) {
										$topic_id  = $lesson_id;
										$lesson_id = learndash_get_lesson_id( $topic_id );
										if ( ! empty( $lesson_id ) ) {
											if ( get_post_type( $lesson_id ) == $lessons_cpt->name ) {
												$quiz_parents[] = $lesson_id;
												$quiz_parents[] = $topic_id;
											}
										}
									} else {
										$quiz_parents[] = $lesson_id;
									}
								}
							}

							if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
								if ( ! strstr( $post_link, $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name ) ) {
									$url_part_new .= '/' . $courses_cpt->rewrite['slug_alt'] . '/' . $course_post_name;
								}
							} else {
								$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
							}

							if ( ! empty( $quiz_parents ) ) {
								foreach ( $quiz_parents as $quiz_parent_id ) {
									$quiz_parent_post = get_post( $quiz_parent_id );
									if ( $quiz_parent_post->post_type == $lessons_cpt->name ) {
										if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
											$parent_slug = $lessons_cpt->rewrite['slug_alt'];
										} else {
											$parent_slug = $lessons_cpt->name;
										}
									} elseif ( $quiz_parent_post->post_type == $topics_cpt->name ) {
										if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
											$parent_slug = $topics_cpt->rewrite['slug_alt'];
										} else {
											$parent_slug = $topics_cpt->name;
										}
									}

									if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
										if ( ! strstr( $post_link, $parent_slug . '/' . $quiz_parent_post->post_name ) ) {
											$url_part_new .= '/' . $parent_slug . '/' . $quiz_parent_post->post_name;
										}
									} else {
										$url_part_new = add_query_arg( $parent_slug, $quiz_parent_post->post_name, $url_part_new );
									}
								}
							}

							if ( ( $wp_rewrite->using_permalinks() ) && ( ! $draft_or_pending ) ) {
								$url_part_new .= '/' . $quizzes_cpt->rewrite['slug_alt'] . '/' . $quiz_post_name;
							} else {
								$url_part_new = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_new );
							}
						}
					}
				}
			}

			if ( ( ! empty( $url_part_new ) ) && ( ! empty( $url_part_old ) ) && ( $url_part_old !== $url_part_new ) ) {
				if ( ( ! $wp_rewrite->using_permalinks() ) || ( $draft_or_pending ) ) {
					$url_part_old = str_replace( '?', '', $url_part_old );
					$url_part_new = str_replace( '?', '', $url_part_new );

					/**
					 * We could normally just append the new args to the end of the URL. But
					 * we want to control the ordering for readability.
					 */
					$args = wp_parse_args( $url_part_new, array() );
					if ( ! empty( $args ) ) {
						foreach ( $args as $arg_key => $arg_val ) {
							$post_link = remove_query_arg( $arg_key, $post_link );
						}

						$post_link_parts_old = wp_parse_url( $post_link );
						if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {
							$post_link = str_replace( $post_link_parts_old['query'], '', $post_link );
						}

						$post_link           = add_query_arg( $args, $post_link );
						$post_link_parts_new = wp_parse_url( $post_link );

						/**
						 * Here we have removed the original LD post type elements and any non-LD elements from the
						 * original URL. Now we want to add the non-LD elements back.
						 */
						if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {
							if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {
								$post_link .= '&' . $post_link_parts_old['query'];
							} else {
								$post_link .= '?' . $post_link_parts_old['query'];
							}
						}
					}
				} else {
					$post_link = str_replace( $url_part_old, $url_part_new, $post_link );
				}
			}

			return $post_link;
		}


		/**
		 * Called via the WordPress List Table instance. This function will adjust the course
		 * step permalinks to be nested if enabled.
		 *
		 * @since 2.5.0
		 * @param array  $actions Array of row actions to be shown.
		 * @param object $post    WP_Post object being displayed for the row.
		 */
		public function row_actions( $actions = array(), $post = '' ) {
			global $pagenow, $typenow;

			if ( ( is_admin() ) && ( 'edit.php' == $pagenow ) ) {
				if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) && ( in_array( $typenow, learndash_get_post_types( 'course_steps' ), true ) ) ) {
					$course_id = 0;

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['course_id'] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$course_id = absint( $_GET['course_id'] );
					}

					if ( ( empty( $course_id ) ) && ( isset( $actions['view'] ) ) ) {
						unset( $actions['view'] );
					}
				}
			}

			return $actions;
		}

		/**
		 * Override WordPress Get Edit Post Link to include nested URL.
		 *
		 * @since 2.5.0
		 * @param string  $link    The edit link.
		 * @param integer $post_id Post ID to edit.
		 * @param string  $context The link context. If set to 'display' then ampersands are encoded.
		 */
		public function get_edit_post_link( $link = '', $post_id = 0, $context = '' ) {
			global $pagenow;

			if ( ( ! empty( $post_id ) ) && ( ! is_admin() ) || ( ( is_admin() && ( in_array( $pagenow, array( 'post.php', 'edit.php' ), true ) ) ) ) ) {
				$post_type_name = get_post_type( $post_id );
				if ( ( ! empty( $post_type_name ) ) && ( in_array( $post_type_name, learndash_get_post_types( 'course_steps' ), true ) ) ) {
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {

						$course_id = 0;
						$course_id = learndash_get_course_id( $post_id );
						if ( ! empty( $course_id ) ) {
							$link = add_query_arg( 'course_id', $course_id, $link );
						}
					}
				}

				if ( ( ! empty( $post_type_name ) ) && ( in_array( $post_type_name, LDLMS_Post_Types::get_post_types( 'quiz_questions' ), true ) ) ) {
					$quiz_id = 0;
					$quiz_id = learndash_get_quiz_id( $post_id );
					if ( ! empty( $quiz_id ) ) {
						$link = add_query_arg( 'quiz_id', $quiz_id, $link );
					}
				}
			}

			return $link;
		}

		/**
		 * Hook into the admin post editor Permalink display. We override the LD post
		 * items so they include the full nested URL
		 *
		 * @since 2.5.0
		 * @param array  $permalink Array containing the sample permalink with placeholder for the post name, and the post name.
		 * @param int    $post_id   Post ID.
		 * @param string $title    Post title.
		 * @param string $name     Post name (slug).
		 * @param object $post     (WP_Post) Post object.
		 */
		public function get_sample_permalink( $permalink = '', $post_id = 0, $title = '', $name = '', $post = '' ) {
			global $pagenow;

			if ( ( is_admin() ) && ( 'post.php' === $pagenow ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {
				$permalink_new = $this->post_type_link( $permalink[0], $post, false, true );
				if ( ( ! empty( $permalink_new ) ) && ( $permalink_new !== $permalink[0] ) ) {
					$permalink[0] = $permalink_new;
				}
			}

			return $permalink;
		}

		/**
		 * Filter the attachment link when using nested URLs.
		 *
		 * @since 3.4.1
		 *
		 * @param string  $attachment_link Attachment link.
		 * @param integer $attachment_id   Attachment post ID.
		 *
		 * @return string $attachment_link
		 */
		public function attachment_link( $attachment_link = '', $attachment_id = 0 ) {
			global $wp_rewrite;

			if ( ( ! empty( $attachment_link ) ) && ( ! empty( $attachment_id ) ) ) {
				if ( ( $wp_rewrite->using_permalinks() ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) ) ) {
					$attachment_post = get_post( $attachment_id );
					if ( ( $attachment_post ) && ( is_a( $attachment_post, 'WP_Post' ) ) ) {
						if ( ( property_exists( $attachment_post, 'post_parent' ) ) && ( ! empty( $attachment_post->post_parent ) ) ) {
							if ( in_array( get_post_type( $attachment_post->post_parent ), learndash_get_post_types( 'course' ), true ) ) {
								$attachment_link = str_replace( '/' . $attachment_post->post_name . '/', '/attachment/' . $attachment_post->post_name . '/', $attachment_link );
							}
						}
					}
				}
			}

			return $attachment_link;
		}


		/**
		 * Action for comment form when nested URLs are enabled. This way the user is returned to this course step URL
		 *
		 * @since 2.5.5
		 */
		public function comment_form_top() {
			$queried_object = get_queried_object();

			if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
				if ( in_array( $queried_object->post_type, learndash_get_post_types( 'course_steps' ), true ) ) {
					echo '<input type="hidden" name="step_id" value="' . absint( $queried_object->ID ) . '" />';

					$course_id = learndash_get_course_id( $queried_object->ID );
					if ( ! empty( $course_id ) ) {
						echo '<input type="hidden" name="course_id" value="' . absint( $course_id ) . '" />';

						$redirect_to = learndash_get_step_permalink( $queried_object->ID, $course_id );
						if ( ! empty( $redirect_to ) ) {
							// This 'redirect_to' is used by WP in wp-comments-post.php to redirect back to a specific URL.
							// This is the important part.
							echo '<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '" />';
						}
					}
				}
			}
		}

		/**
		 * Add the course_id to comment meta
		 *
		 * @since 2.5.5
		 *
		 * @param int        $comment_id       The comment ID.
		 * @param int|string $comment_approved Comment Approve Status, 1 if the comment is approved, 0 if not, 'spam' if spam.
		 * @param array      $comment_data     Comment data.
		 */
		public function comment_post( $comment_id, $comment_approved, $comment_data ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ( isset( $_POST['course_id'] ) ) && ( ! empty( $_POST['course_id'] ) ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				update_comment_meta( $comment_id, 'course_id', absint( $_POST['course_id'] ) );
			}
		}

		// End of function.
	}
}

add_action(
	'plugins_loaded',
	function() {
		new LearnDash_Permalinks();
	}
);


/**
 * Utility function to get the nested permalink of the course step within in the course.
 *
 * @since 2.5.0
 * @param int $step_id        Course Step Post ID.
 * @param int $step_course_id Course ID.
 */
function learndash_get_step_permalink( $step_id = 0, $step_course_id = null ) {

	if ( ! empty( $step_id ) ) {
		if ( ! is_null( $step_course_id ) ) {
			$GLOBALS['step_course_id'] = $step_course_id; //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			add_filter(
				'learndash_post_link_course_id',
				function( $course_id ) {
					if ( ( isset( $GLOBALS['step_course_id'] ) ) && ( ! is_null( $GLOBALS['step_course_id'] ) ) ) { // @phpstan-ignore-line -- filter is processed later.
						$course_id = $GLOBALS['step_course_id'];
					}
					return $course_id;
				}
			);
		}
		$step_permalink = get_permalink( $step_id );

		if ( isset( $GLOBALS['step_course_id'] ) ) {
			unset( $GLOBALS['step_course_id'] );
		}

		return $step_permalink;
	}
}


/**
 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add
 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
 *
 * @since 2.5.0
 * @param string $location The destination URL.
 * @param int    $post_id  The post ID.
 */
function learndash_redirect_post_location( $location = '', $post_id = 0 ) {
	if ( ( is_admin() ) && ( ! empty( $location ) ) ) {

		global $typenow;

		check_admin_referer( 'update-post_' . $post_id );

		if ( in_array( $typenow, learndash_get_post_types( 'course_steps' ), true ) ) {
			if ( ( isset( $_POST['ld-course-switcher'] ) ) && ( ! empty( $_POST['ld-course-switcher'] ) ) ) {
				$post_args = wp_parse_args( $_POST['ld-course-switcher'], array() );
				if ( ( isset( $post_args['course_id'] ) ) && ( ! empty( $post_args['course_id'] ) ) ) {
					$location = add_query_arg( 'course_id', intval( $post_args['course_id'] ), $location );
				}
			}
		} elseif ( learndash_get_post_type_slug( 'question' ) === $typenow ) {
			if ( ( isset( $_POST['ld-quiz-switcher'] ) ) && ( ! empty( $_POST['ld-quiz-switcher'] ) ) ) {
				$post_args = wp_parse_args( $_POST['ld-quiz-switcher'], array() );
				if ( ( isset( $post_args['quiz_id'] ) ) && ( ! empty( $post_args['quiz_id'] ) ) ) {
					$location = add_query_arg( 'quiz_id', absint( $post_args['quiz_id'] ), $location );
				}
			}
		}
	}

	return $location;
}
add_filter( 'redirect_post_location', 'learndash_redirect_post_location', 10, 2 );


/**
 * Utility function to set the option to trigger flush of rewrite rules.
 * This is checked during the 'shutdown' action where the rewrites will
 * then be flushed.
 */
function learndash_setup_rewrite_flush() {
	update_option( 'sfwd_lms_rewrite_flush', true, false );
}
