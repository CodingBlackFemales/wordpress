<?php
/**
 * SFWD_CPT_Instance
 *
 * @since 2.1.0
 *
 * @package LearnDash\CPT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Template\Views;

if ( ! class_exists( 'SFWD_CPT_Instance' ) ) {

	/**
	 * Extends functionality of SWFD_CPT instance
	 *
	 * @todo  consider whether these methods can just be included in SWFD_CPT
	 *        unclear as to why it's separate
	 */
	class SFWD_CPT_Instance extends SFWD_CPT {

		/**
		 * Instances
		 *
		 * @var array
		 */
		public static $instances = array();

		/**
		 * Filter content
		 *
		 * @var boolean
		 */
		public $filter_content = true;

		/**
		 * Template redirect
		 *
		 * @var boolean
		 */
		public $template_redirect = true;

		/**
		 * Sets up properties for CPT to be used in plugins
		 *
		 * @since 2.1.0
		 *
		 * @param array $args  parameters for setting up the CPT instance.
		 */
		public function __construct( $args ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Bad idea, but better keep it for now.

			if ( empty( $plugin_name ) ) {
				$plugin_name = 'SFWD CPT Instance';
			}

			if ( empty( $post_name ) ) {
				$post_name = $plugin_name;
			}

			if ( empty( $slug_name ) ) {
				$slug_name = sanitize_file_name( strtolower( strtr( $post_name, ' ', '_' ) ) );
			}

			if ( empty( $post_type ) ) {
				$post_type = sanitize_file_name( strtolower( strtr( $slug_name, ' ', '_' ) ) );
			}

			if ( isset( $args['template_redirect'] ) ) {
				$this->template_redirect = $args['template_redirect'];
			}

			self::$instances[ $post_type ] =& $this;

			if ( empty( $name ) ) {
				$name = ! empty( $options_page_title ) ? $options_page_title : $post_name . esc_html__( ' Options', 'learndash' );
			}

			if ( empty( $prefix ) ) {
				$prefix = sanitize_file_name( $post_type ) . '_';
			}

			if ( ! empty( $taxonomies ) ) {
				$this->taxonomies = $taxonomies;
			}

			$this->file        = __FILE__ . "?post_type={$post_type}";
			$this->plugin_name = $plugin_name;
			$this->post_name   = $post_name;
			$this->slug_name   = $slug_name;
			$this->post_type   = $post_type;
			$this->name        = $name;
			$this->prefix      = $prefix;

			$posts_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
			if ( empty( $posts_per_page ) ) {
				$posts_per_page = get_option( 'posts_per_page' );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 5;
				}
			}

			if ( empty( $default_options ) ) {

				$this->default_options = array(
					'orderby'        => array(
						'name'            => esc_html__( 'Sort By', 'learndash' ),
						'type'            => esc_html__( 'select', 'learndash' ),
						'initial_options' => array(
							''           => esc_html__( 'Select a choice...', 'learndash' ),
							'title'      => esc_html__( 'Title', 'learndash' ),
							'date'       => esc_html__( 'Date', 'learndash' ),
							'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
						),
						'default'         => 'date',
						'help_text'       => esc_html__( 'Choose the sort order.', 'learndash' ),
					),
					'order'          => array(
						'name'            => esc_html__( 'Sort Direction', 'learndash' ),
						'type'            => 'select',
						'initial_options' => array(
							''     => esc_html__( 'Select a choice...', 'learndash' ),
							'ASC'  => esc_html__( 'Ascending', 'learndash' ),
							'DESC' => esc_html__( 'Descending', 'learndash' ),
						),
						'default'         => 'DESC',
						'help_text'       => esc_html__( 'Choose the sort order.', 'learndash' ),
					),
					'posts_per_page' => array( // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
						'name'      => esc_html__( 'Posts Per Page', 'learndash' ),
						'type'      => 'text',
						'help_text' => esc_html__( 'Enter the number of posts to display per page.', 'learndash' ),
						'default'   => $posts_per_page,
					),
				);

			} else {
				$this->default_options = $default_options;
			}

			if ( ! empty( $fields ) ) {
				$this->locations = array(
					'default'        => array(
						'name'    => $this->name,
						'prefix'  => $this->prefix,
						'type'    => 'settings',
						'options' => null,
					),
					$this->post_type => array(
						'name'            => $this->plugin_name,
						'type'            => 'metabox',
						'prefix'          => '',
						'options'         => array_keys( $fields ),
						'default_options' => $fields,
						'display'         => array(
							$this->post_type,
						),
					),
				);
			}

			parent::__construct();

			if ( ! empty( $description ) ) {
				$this->post_options['description'] = wp_kses_post( $description );
			}

			if ( ! empty( $menu_icon ) ) {
				$this->post_options['menu_icon'] = esc_url( $menu_icon );
			}

			if ( ! empty( $cpt_options ) ) {
				$this->post_options = wp_parse_args( $cpt_options, $this->post_options );
			}

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			if ( ! in_array( $this->post_type, array( learndash_get_post_type_slug( 'exam' ) ), true ) ) {
				add_shortcode( $this->post_type, array( $this, 'shortcode' ) );
			}
			add_action( 'init', array( $this, 'add_post_type' ), 5 );

			$this->update_options();

			if ( ! is_admin() ) {
				add_action( 'pre_get_posts', array( $this, 'pre_posts' ) );
				if ( true === $this->template_redirect ) {
					add_action( 'template_redirect', array( $this, 'template_redirect_access' ) );
					add_filter( 'the_content', array( $this, 'template_content' ), LEARNDASH_FILTER_PRIORITY_THE_CONTENT );
				}
			}

		} // end __construct()

		/**
		 * Function to dynamically control the 'the_content' filtering for this post_type instance.
		 * This is needed for example when using the 'the_content' filters manually and do not want the
		 * normal filters recursively applied.
		 *
		 * @since 2.5.9
		 *
		 * @param boolean $filter_check True if the_content filter is to be enabled.
		 */
		public function content_filter_control( $filter_check = true ) {
			$this->filter_content = $filter_check;
		}

		/**
		 * Get Archive content
		 *
		 * @todo Consider reworking, function returns content of a post.
		 *       Not archive.
		 *
		 * @since 2.1.0
		 *
		 * @param string $content Content.
		 * @return string $content Content
		 */
		public function get_archive_content( $content ) {
			global $post;
			if ( sfwd_lms_has_access( $post->ID ) ) {
				return $content;
			} else {
				return get_the_excerpt();
			}
		} // end get_archive_content()



		/**
		 * Generate output for courses, lessons, topics, quizzes
		 * Filter callback for 'the_content' (wp core filter)
		 *
		 * Determines what the user is currently looking at, sets up data,
		 * passes to template, and returns output.
		 *
		 * @since 2.1.0
		 *
		 * @param string $content content of post.
		 * @return string $content content of post
		 */
		public function template_content( $content ) {
			global $wp;

			if ( true !== $this->filter_content ) {
				return $content;
			}

			$post            = get_post( get_the_id() );
			$current_user    = wp_get_current_user();
			$post_type       = '';
			$user_wrapper    = true;
			$template_called = array();

			if ( ! $post ) {
				return $content;
			}

			if ( get_query_var( 'post_type' ) ) {
				$post_type = get_query_var( 'post_type' );
			}

			if ( ( ! is_singular() ) || ( $post_type !== $this->post_type ) || ( $post_type !== $post->post_type ) ) {
				return $content;
			}

			if ( ( defined( 'LEARNDASH_DISABLE_TEMPLATE_CONTENT_OUTSIDE_LOOP' ) ) && ( true === LEARNDASH_DISABLE_TEMPLATE_CONTENT_OUTSIDE_LOOP ) && ! in_the_loop() ) {
				return $content;
			}

			if ( post_password_required( $post ) ) {
				return $content;
			}

			/**
			 * Filter called just before template processing. Allows late determination if
			 * LearnDash template logic should be processed.
			 *
			 * @since 3.1.7
			 *
			 * @param boolean $run_filter true.
			 * @param int     $post_id    Current Post ID.
			 * @return boolean True to process template. Anything else to abort.
			 */
			if ( ! apply_filters( 'learndash_template_preprocess_filter', true, get_the_id() ) ) {
				return $content;
			}

			/**
			 * Remove the hook into the WP 'the_content' filter once we are in our handler. This
			 * will allow other templates to call the 'the_content' filter without causing recursion.
			 *
			 * @since 3.1.0
			 *
			 * @param bool $remove_template_content_filter True to remove the filter. Default false.
			 */
			if ( apply_filters( 'learndash_remove_template_content_filter', false ) ) {
				remove_filter( 'the_content', array( $this, 'template_content' ), LEARNDASH_FILTER_PRIORITY_THE_CONTENT );
			}

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = 0;
			}

			$logged_in                  = ! empty( $user_id );
			$course_id                  = learndash_get_course_id();
			$lesson_progression_enabled = false;
			$has_access                 = '';

			if ( ! empty( $course_id ) ) {
				$course                     = get_post( $course_id );
				$course_settings            = learndash_get_setting( $course );
				$lesson_progression_enabled = learndash_lesson_progression_enabled();
				$courses_options            = learndash_get_option( 'sfwd-courses' );
				$lessons_options            = learndash_get_option( 'sfwd-lessons' );
				$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
				$course_status              = learndash_course_status( $course_id, null );
				$course_certficate_link     = learndash_get_course_certificate_link( $course_id, $user_id ); // cspell:disable-line.
				$has_access                 = sfwd_lms_has_access( $course_id, $user_id );

				$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
				if ( ( ! $course_meta ) || ( ! is_array( $course_meta ) ) ) {
					$course_meta = array();
				}
				if ( ! isset( $course_meta['sfwd-courses_course_disable_content_table'] ) ) {
					$course_meta['sfwd-courses_course_disable_content_table'] = false;
				}
			} else {
				$course          = null;
				$course_settings = null;
			}

			$bypass_course_limits_admin_users = learndash_can_user_bypass(
				$user_id,
				'learndash_prerequities_bypass', // cspell:disable-line -- prerequities are prerequisites...
				$course_id,
				$post
			);

			// For logged in users to allow an override filter.
			/** This filter is documented in includes/course/ld-course-progress.php */
			$bypass_course_limits_admin_users = apply_filters(
				'learndash_prerequities_bypass', // cspell:disable-line -- prerequities are prerequisites...
				$bypass_course_limits_admin_users,
				$user_id,
				$course_id,
				$post
			);

			if ( in_array( $post->post_type, learndash_get_post_types( 'course' ), true ) ) {
				if (
					( $logged_in )
					&& ( ! learndash_is_course_prerequities_completed( $course_id ) ) // cspell:disable-line -- prerequities are prerequisites...
					&& ( ! $bypass_course_limits_admin_users )
				) {
					if ( 'sfwd-courses' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'course' );
					} elseif ( 'sfwd-lessons' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'lesson' );
					} elseif ( 'sfwd-quiz' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'quiz' );
					} else {
						$content_type = strtolower( $this->post_name );
					}

					$course_pre = learndash_get_course_prerequisites( $course_id );
					if ( ! empty( $course_pre ) ) {
						foreach ( $course_pre as $c_id => $c_status ) {
							break;
						}

						$level = ob_get_level();
						ob_start();
						SFWD_LMS::get_template(
							'learndash_course_prerequisites_message',
							array(
								'current_post'           => $post,
								// We need to support the 'prerequisite_post' element since modified templates may suse it.
								'prerequisite_post'      => get_post( $c_id ),
								'prerequisite_posts_all' => $course_pre,
								'content_type'           => $content_type,
								'course_settings'        => $course_settings,
							),
							true
						);
						$content = learndash_ob_get_clean( $level );
					}
				} elseif ( ( $logged_in ) && ( ! learndash_check_user_course_points_access( $course_id, $user_id ) ) && ( ! $bypass_course_limits_admin_users ) ) {

					if ( 'sfwd-courses' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'course' );
					} elseif ( 'sfwd-lessons' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'lesson' );
					} elseif ( 'sfwd-quiz' === $this->post_type ) {
						$content_type = learndash_get_custom_label_lower( 'quiz' );
					} else {
						$content_type = strtolower( $this->post_name );
					}

					$course_access_points = learndash_get_course_points_access( $course_id );
					$user_course_points   = learndash_get_user_course_points( $user_id );

					$level = ob_get_level();
					ob_start();
					SFWD_LMS::get_template(
						'learndash_course_points_access_message',
						array(
							'current_post'         => $post,
							'content_type'         => $content_type,
							'course_access_points' => $course_access_points,
							'user_course_points'   => $user_course_points,
							'course_settings'      => $course_settings,
						),
						true
					);
					$content = learndash_ob_get_clean( $level );

				} else {
					if ( 'sfwd-courses' === $this->post_type ) {
						require_once LEARNDASH_LMS_LIBRARY_DIR . '/paypal/enhanced-paypal-shortcodes.php';

						if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views() ) {
							if ( empty( $course ) ) {
								return $content;
							}

							$view = new Views\Course( $course );

							$content = $view->get_html();
						} else {
							$courses_prefix = $this->get_prefix();
							$prefix_len     = strlen( $courses_prefix );

							$materials = '';
							if ( ! isset( $course_settings['course_materials_enabled'] ) ) {
								$course_settings['course_materials_enabled'] = '';
								if ( ( isset( $course_settings['course_materials'] ) ) && ( ! empty( $course_settings['course_materials'] ) ) ) {
									$course_settings['course_materials_enabled'] = 'on';
								}
							}

							if ( ( 'on' === $course_settings['course_materials_enabled'] ) && ( ! empty( $course_settings['course_materials'] ) ) ) {
								$materials = wp_specialchars_decode( strval( $course_settings['course_materials'] ), ENT_QUOTES );
								if ( ! empty( $materials ) ) {
									$materials = do_shortcode( $materials );
									$materials = wpautop( $materials );
								}
							}

							$lessons = learndash_get_course_lessons_list( $course_id );

							// For now no pagination on the course quizzes. Can't think of a scenario where there will be more
							// than the pager count.
							$quizzes = learndash_get_course_quiz_list( $course );

							$has_course_content = ( ! empty( $lessons ) || ! empty( $quizzes ) );

							$lesson_topics = array();

							$has_topics = false;

							if ( ! empty( $lessons ) ) {
								foreach ( $lessons as $lesson ) {
									$lesson_topics[ $lesson['post']->ID ] = learndash_topic_dots( $lesson['post']->ID, false, 'array', null, $course_id );
									if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
										$has_topics = true;

										$topic_pager_args                     = array(
											'course_id' => $course_id,
											'lesson_id' => $lesson['post']->ID,
										);
										$lesson_topics[ $lesson['post']->ID ] = learndash_process_lesson_topics_pager( $lesson_topics[ $lesson['post']->ID ], $topic_pager_args );
									}
								}
							}

							$level = ob_get_level();
							ob_start();
							$template_file = SFWD_LMS::get_template( 'course', null, null, true );
							if ( ! empty( $template_file ) ) {
								include $template_file;
							}
							$content = learndash_ob_get_clean( $level );
						}
					} elseif ( 'sfwd-quiz' === $this->post_type ) {
						$quiz_pro_id = get_post_meta( $post->ID, 'quiz_pro_id', true );
						$quiz_pro_id = absint( $quiz_pro_id );
						if ( empty( $quiz_pro_id ) ) {
							$quiz_settings = learndash_get_setting( $post->ID );
							if ( isset( $quiz_settings['quiz_pro'] ) ) {
								$quiz_settings['quiz_pro'] = absint( $quiz_settings['quiz_pro'] );
								if ( ! empty( $quiz_settings['quiz_pro'] ) ) {
									$quiz_pro_id = $quiz_settings['quiz_pro'];
								}
							}
						}

						$content      = wptexturize(
							learndash_quiz_shortcode(
								array(
									'quiz_id'     => $post->ID,
									'course_id'   => absint( $course_id ),
									'quiz_pro_id' => absint( $quiz_pro_id ),
								),
								$content,
								true
							)
						);
						$user_wrapper = false;

					} elseif ( 'sfwd-lessons' === $this->post_type ) {
						if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views() ) {
							$view = new Views\Lesson( $post );

							$content = $view->get_html();
						} else {
							$show_content = false;

							if ( ! empty( $user_id ) ) {
								if ( learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID ) ) {
									$show_content              = true;
									$previous_lesson_completed = true;
								} elseif ( $lesson_progression_enabled ) {
									if ( learndash_is_sample( $post ) ) {
										$show_content              = true;
										$previous_lesson_completed = false;

										if ( $has_access ) {
											$previous_step_post_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
											if ( ( $previous_step_post_id ) && ( $previous_step_post_id === $post->ID ) ) {
												$previous_lesson_completed = true;
											} else {
												$previous_lesson_completed = false;
											}
										}
									} else {
										if ( $bypass_course_limits_admin_users ) {
											$previous_lesson_completed = true;
											remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );
										} else {
											$previous_step_post_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
											if ( ( $previous_step_post_id ) && ( $previous_step_post_id === $post->ID ) ) {
												$previous_lesson_completed = true;
											} else {
												$previous_lesson_completed = false;
											}

											/**
											 * Filter to override previous step completed.
											 *
											 * @param bool $previous_step_completed True if previous step completed.
											 * @param int  $step_id                 Step Post ID.
											 * @param int  $user_id                 User ID.
											 */
											$previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $post->ID, $user_id );
										}
										$show_content = $previous_lesson_completed;
									}
								} else {
									$show_content              = true;
									$previous_lesson_completed = true;
								}
							} else {
								if ( ( ! learndash_is_sample( $post ) ) && ( ( learndash_get_setting( $post->ID, 'visible_after' ) ) || ( learndash_get_setting( $post->ID, 'visible_after_specific_date' ) ) ) ) {
									$show_content              = false;
									$previous_lesson_completed = false;
								} else {
									$show_content              = true;
									$previous_lesson_completed = true;
								}
							}

							$lesson_settings = learndash_get_setting( $post );
							$quizzes         = learndash_get_lesson_quiz_list( $post, null, $course_id );
							$quizids         = array();

							if ( ! empty( $quizzes ) ) {
								foreach ( $quizzes as $quiz ) {
									$quizids[ $quiz['post']->ID ] = $quiz['post']->ID;
								}
							}

							if ( $lesson_progression_enabled && ! $previous_lesson_completed ) {
								add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 );
							}

							$topics = learndash_topic_dots( $post->ID, false, 'array', null, $course_id );
							if ( ! empty( $topics ) ) {
								$topic_pager_args = array(
									'course_id' => $course_id,
									'lesson_id' => $post->ID,
								);
								$topics           = learndash_process_lesson_topics_pager( $topics, $topic_pager_args );
							}

							if ( ! empty( $quizids ) ) {
								$all_quizzes_completed = ! learndash_is_quiz_notcomplete( null, $quizids, false, $course_id );
							} else {
								$all_quizzes_completed = true;
							}

							if ( $show_content ) {

								$materials = '';
								if ( ! isset( $lesson_settings['lesson_materials_enabled'] ) ) {
									$lesson_settings['lesson_materials_enabled'] = '';
									if ( ( isset( $lesson_settings['lesson_materials'] ) ) && ( ! empty( $lesson_settings['lesson_materials'] ) ) ) {
										$lesson_settings['lesson_materials_enabled'] = 'on';
									}
								}

								if ( ( 'on' === $lesson_settings['lesson_materials_enabled'] ) && ( ! empty( $lesson_settings['lesson_materials'] ) ) ) {
									$materials = wp_specialchars_decode( strval( $lesson_settings['lesson_materials'] ), ENT_QUOTES );
									if ( ! empty( $materials ) ) {
										$materials = do_shortcode( $materials );
										$materials = wpautop( $materials );
									}
								}

								$started_time = time();

								// We insert the Course started record before the Lesson.
								$course_activity = learndash_activity_start_course( $current_user->ID, $course_id, $started_time );
								if ( ( is_a( $course_activity, 'LDLMS_Model_Activity' ) && ( $course_activity->activity_id ) ) ) {
									learndash_activity_update_meta_set(
										$course_activity->activity_id,
										array(
											'steps_completed' => learndash_course_get_completed_steps( $current_user->ID, $course_id ),
											'steps_last_id'   => $post->ID,
										)
									);
								}
								learndash_activity_start_lesson( $current_user->ID, $course_id, $post->ID, $started_time );
							}

							// Added logic for Lesson Videos.
							if ( ( defined( 'LEARNDASH_LESSON_VIDEO' ) ) && ( true === LEARNDASH_LESSON_VIDEO ) ) {
								if ( $show_content ) {
									$ld_course_videos = Learndash_Course_Video::get_instance();
									$content          = $ld_course_videos->add_video_to_content( $content, $post, $lesson_settings );
								}
							}

							$level = ob_get_level();
							ob_start();
							$template_file = SFWD_LMS::get_template( 'lesson', null, null, true );
							if ( ! empty( $template_file ) ) {
								include $template_file;
							}
							$content = learndash_ob_get_clean( $level );
						}
					} elseif ( 'sfwd-topic' === $this->post_type ) {
						if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views() ) {
							$view = new Views\Topic( $post );

							$content = $view->get_html();
						} else {
							$course_id = learndash_get_course_id( $post );
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
							if ( $lesson_id ) {
								$lesson_post = get_post( $lesson_id );
							} else {
								$lesson_post = null;
							}

							$previous_lesson_completed = false;
							$previous_topic_completed  = false;

							if ( ! empty( $user_id ) ) {
								if ( learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID ) ) {
									$show_content              = true;
									$previous_lesson_completed = true;
									$previous_topic_completed  = true;
								} elseif ( $lesson_progression_enabled ) {
									if ( learndash_is_sample( $post ) ) {
										$show_content             = true;
										$previous_topic_completed = false;
									} else {
										if ( $bypass_course_limits_admin_users ) {
											$previous_lesson_completed = true;
											remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );
										} else {
											$previous_step_post_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
											if ( ( $previous_step_post_id ) && ( $previous_step_post_id === $post->ID ) ) {
												$previous_lesson_completed = true;
											} else {
												$previous_lesson_completed = false;
											}

											/** This filter is documented in includes/class-ld-cpt-instance.php */
											$previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $post->ID, $user_id );

										}
										$previous_topic_completed = $previous_lesson_completed;
										$show_content             = $previous_lesson_completed;
									}
								} else {
									$previous_topic_completed  = true;
									$previous_lesson_completed = true;
									$show_content              = true;
								}
							} else {
								if ( ( ! learndash_is_sample( $post ) ) && ( ( learndash_get_setting( $lesson_id, 'visible_after' ) ) || ( learndash_get_setting( $lesson_id, 'visible_after_specific_date' ) ) ) ) {
									$previous_topic_completed  = false;
									$previous_lesson_completed = false;
									$show_content              = false;
								} else {
									$previous_topic_completed  = true;
									$previous_lesson_completed = true;
									$show_content              = true;
								}
							}

							$quizzes = learndash_get_lesson_quiz_list( $post, null, $course_id );
							$quizids = array();

							if ( ! empty( $quizzes ) ) {
								foreach ( $quizzes as $quiz ) {
									$quizids[ $quiz['post']->ID ] = $quiz['post']->ID;
								}
							}

							if ( $lesson_progression_enabled && ( ! $previous_topic_completed || ! $previous_lesson_completed ) ) {
								add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 );
							}

							if ( ! empty( $quizids ) ) {
								$all_quizzes_completed = ! learndash_is_quiz_notcomplete( null, $quizids, false, $course_id );
							} else {
								$all_quizzes_completed = true;
							}

							$topics = learndash_topic_dots( $lesson_id, false, 'array', null, $course_id );

							if ( $show_content ) {
								$topic_settings = learndash_get_setting( $post );
								$materials      = '';
								if ( ! isset( $topic_settings['topic_materials_enabled'] ) ) {
									$topic_settings['topic_materials_enabled'] = '';
									if ( ( isset( $topic_settings['topic_materials'] ) ) && ( ! empty( $topic_settings['topic_materials'] ) ) ) {
										$topic_settings['topic_materials_enabled'] = 'on';
									}
								}

								if ( ( 'on' === $topic_settings['topic_materials_enabled'] ) && ( ! empty( $topic_settings['topic_materials'] ) ) ) {
									$materials = wp_specialchars_decode( strval( $topic_settings['topic_materials'] ), ENT_QUOTES );
									if ( ! empty( $materials ) ) {
										$materials = do_shortcode( $materials );
										$materials = wpautop( $materials );
									}
								}

								$started_time = time();

								// We insert the Course started record before the Topic.
								$course_activity = learndash_activity_start_course( $current_user->ID, $course_id, $started_time );
								if ( ( is_a( $course_activity, 'LDLMS_Model_Activity' ) && ( $course_activity->activity_id ) ) ) {
									learndash_activity_update_meta_set(
										$course_activity->activity_id,
										array(
											'steps_completed' => learndash_course_get_completed_steps( $current_user->ID, $course_id ),
											'steps_last_id'   => $post->ID,
										)
									);
								}
								learndash_activity_start_lesson( $current_user->ID, $course_id, $lesson_id, $started_time );
								learndash_activity_start_topic( $current_user->ID, $course_id, $post->ID, $started_time );
							}

							// Added logic for Lesson Videos.
							if ( ( defined( 'LEARNDASH_LESSON_VIDEO' ) ) && ( true === LEARNDASH_LESSON_VIDEO ) ) {
								if ( $show_content ) {
									$ld_course_videos = Learndash_Course_Video::get_instance();
									$content          = $ld_course_videos->add_video_to_content( $content, $post, $topic_settings );
								}
							}

							$level = ob_get_level();
							ob_start();
							$template_file = SFWD_LMS::get_template( 'topic', null, null, true );
							if ( ! empty( $template_file ) ) {
								include $template_file;
							}
							$content = learndash_ob_get_clean( $level );
						}
					} else {
						// archive.
						$content = $this->get_archive_content( $content );
					}
				}
			} elseif ( learndash_get_post_type_slug( 'group' ) === $post->post_type ) {
				if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views() ) {
					$view = new Views\Group( $post );

					$content = $view->get_html();
				} else {
					$group_id = $post->ID;
					$group    = $post;

					if ( learndash_is_user_in_group( $user_id, $group_id ) ) {
						$has_access   = true;
						$group_status = learndash_get_user_group_status( $group_id, $user_id );
					} else {
						$has_access   = false;
						$group_status = '';
					}

					$user_has_access = $has_access ? 'user_has_access' : 'user_has_no_access';

					$group_certficate_link = ''; // cspell:disable-line.
					if ( $has_access ) {
						$group_certficate_link = learndash_get_group_certificate_link( $group_id, $user_id ); // cspell:disable-line.
					}

					$group_settings = (array) learndash_get_setting( $post );
					if ( ! is_array( $group_settings ) ) {
						$group_settings = array();
					}
					$materials = '';
					if ( ! isset( $group_settings['group_materials_enabled'] ) ) {
						$group_settings['group_materials_enabled'] = '';
						if ( ( isset( $group_settings['group_materials'] ) ) && ( ! empty( $group_settings['group_materials'] ) ) ) {
							$group_settings['group_materials_enabled'] = 'on';
						}
					}

					if ( ( 'on' === $group_settings['group_materials_enabled'] ) && ( ! empty( $group_settings['group_materials'] ) ) ) {
						$materials = wp_specialchars_decode( $group_settings['group_materials'], ENT_QUOTES );
						if ( ! empty( $materials ) ) {
							$materials = do_shortcode( $materials );
							$materials = wpautop( $materials );
						}
					}

					$group_courses     = learndash_get_group_courses_list( $group_id );
					$has_group_content = ( ( is_array( $group_courses ) ) && ( ! empty( $group_courses ) ) );

					$level = ob_get_level();
					ob_start();
					$template_file = SFWD_LMS::get_template( 'group', null, null, true );
					if ( ! empty( $template_file ) ) {
						include $template_file;
					}
					$content = learndash_ob_get_clean( $level );
				}
			} elseif ( learndash_get_post_type_slug( 'exam' ) === $post->post_type ) {
				if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views() ) {
					$view = new Views\Exam( $post );

					$content = $view->get_html();
				}
			}

			// Added this defined wrap in v2.1.8 as it was effecting <pre></pre>, <code></code> and other formatting of the content.
			// See https://www.wrike.com/open.htm?id=77352698 as to why this define exists.
			if ( ( defined( 'LEARNDASH_NEW_LINE_AND_CR_TO_SPACE' ) ) && ( true === LEARNDASH_NEW_LINE_AND_CR_TO_SPACE ) ) {

				// Why is this here?
				$content = str_replace( array( "\n", "\r" ), ' ', $content );

			}

			$user_has_access = $has_access ? 'user_has_access' : 'user_has_no_access';

			/**
			 * Filter content to be return inside div
			 *
			 * @since 2.1.0
			 *
			 * @param string $content Post Content.
			 * @param object $post    WP_Post Post Object.
			 */
			$content = apply_filters( 'learndash_content', $content, $post );
			if ( true === $user_wrapper ) {
				$content = '<div class="learndash learndash_post_' . $this->post_type . ' ' . $user_has_access . '"  id="learndash_post_' . $post->ID . '">' . $content . '</div>';
			}

			return $content;

		} // end template_content()



		/**
		 * Show course completion/quiz completion
		 * Action callback from 'template_redirect' (wp core action)
		 *
		 * @since 2.1.0
		 */
		public function template_redirect_access() {
			global $wp;
			global $post;

			/**
			 * Added check to ensure $post is not empty
			 *
			 * @since 2.3.0.3
			 */
			if ( empty( $post ) ) {
				return;
			}

			if ( ! ( $post instanceof WP_Post ) ) {
				return;
			}

			if ( get_query_var( 'post_type' ) ) {
				$post_type = get_query_var( 'post_type' );
			} elseif ( is_a( $post, 'WP_Post' ) ) {
				$post_type = $post->post_type;
			}

			if ( empty( $post_type ) ) {
				return;
			}

			if ( $post_type === $this->post_type ) {
				if ( is_robots() ) {
					/**
					 * Display the robots.txt file content. (wp core action)
					 *
					 * @since 2.1.0
					 *
					 * @link https://codex.wordpress.org/Function_Reference/do_robots
					 */
					do_action( 'do_robots' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress Core hook
				} elseif ( is_trackback() ) {
					include ABSPATH . 'wp-trackback.php';
				} elseif ( ! empty( $wp->query_vars['name'] ) ) {
					// single.
					if ( in_array( $post_type, learndash_get_post_types( 'course_steps' ), true ) ) {
						global $post;
						sfwd_lms_access_redirect( $post->ID );
						$course_id = learndash_get_course_id( $post->ID );
						if ( ! empty( $course_id ) ) {
							learndash_course_exam_challenge_redirect( $course_id );
						}
					} elseif ( learndash_get_post_type_slug( 'course' ) === $post_type ) {
						learndash_course_exam_challenge_redirect( $post->ID );
					} elseif ( learndash_get_post_type_slug( 'exam' ) === $post_type ) {
						learndash_exam_challenge_view_permission( $post->ID );
					}
				}
			}
		} // end template_redirect_access()

		/**
		 * Amend $wp_query based on what content user is viewing
		 *
		 * If archive for post type of this instance, set order and posts per page
		 * If post archive, don't display certificates
		 *
		 * @since 2.1.0
		 */
		public function pre_posts() {
			global $wp_query;

			if ( is_post_type_archive( $this->post_type ) ) {

				foreach ( array( 'orderby', 'order', 'posts_per_page' ) as $field ) {
					if ( $this->option_isset( $field ) ) {
						$wp_query->set( $field, $this->options[ $this->prefix . $field ] );
					}
				}
			} elseif ( ( 'sfwd-quiz' === $this->post_type ) && ( is_post_type_archive( 'post' ) || is_home() ) && ! empty( $this->options[ "{$this->prefix}certificate_post" ] ) ) {

				$post_not_in = $wp_query->get( 'post__not_in' );

				if ( ! is_array( $post_not_in ) ) {
					$post_not_in = array();
				}

				$post_not_in = array_merge( $post_not_in, array( $this->options[ "{$this->prefix}certificate_post" ] ) );
				$wp_query->set( 'post__not_in', $post_not_in );

			}

		} // end pre_posts()
	}
}
