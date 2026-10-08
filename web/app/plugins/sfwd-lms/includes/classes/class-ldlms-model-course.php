<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Course.
 *
 * @since 2.5.0
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Course' ) ) && ( class_exists( 'LDLMS_Model_Post' ) ) ) {
	/**
	 * Class for LearnDash Model Course.
	 *
	 * @since 2.5.0
	 * @uses LDLMS_Model_Post
	 */
	class LDLMS_Model_Course extends LDLMS_Model_Post {

		/**
		 * Steps Page Results.
		 *
		 * @since 3.4.0
		 *
		 * @var array pager Array of pagination results for steps;
		 */
		protected $pager = array();

		/**
		 * Class constructor.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Course Post ID to load.
		 *
		 * @throws LDLMS_Exception_NotFound When post not loaded.
		 *
		 * @return mixed instance of class or exception.
		 */
		public function __construct( $post_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'course' );

			if ( ! $this->init( $post_id ) ) {
				throw new LDLMS_Exception_NotFound();
			} else {
				return $this;
			}
		}

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Course Post ID to load.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		private function init( $post_id = 0 ) {
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$post = get_post( $post_id );
				if ( ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type === $this->post_type ) ) {
					$this->post_id = $post_id;
					$this->post    = $post;

					$this->load_settings();

					return true;
				}
			}
			return false;
		}

		/**
		 * Load the Post Settings.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $force Force reload of settings.
		 *
		 * @return bool settings loaded.
		 */
		public function load_settings( $force = false ) {
			parent::load_settings( $force );

			return $this->settings_loaded;
		}

		/**
		 * Get Lessons order/orderby setting
		 */
		public function get_settings_order() {
			$course_lessons_args = array(
				'order'   => '',
				'orderby' => '',
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$course_lessons_args['orderby'] = 'post__in';
				return $course_lessons_args;
			} else {
				$lessons_options = learndash_get_option( 'sfwd-lessons' );
				if ( ( isset( $lessons_options['order'] ) ) && ( ! empty( $lessons_options['order'] ) ) ) {
					$course_lessons_args['order'] = $lessons_options['order'];
				}

				if ( ( isset( $lessons_options['orderby'] ) ) && ( ! empty( $lessons_options['orderby'] ) ) ) {
					$course_lessons_args['orderby'] = $lessons_options['orderby'];
				}
			}

			if ( ! empty( $this->post_id ) ) {
				if ( ( isset( $this->settings['course_lesson_order'] ) ) && ( ! empty( $this->settings['course_lesson_order'] ) ) ) {
					$course_lessons_args['order'] = $this->settings['course_lesson_order'];
				}

				if ( ( isset( $course_settings['course_lesson_orderby'] ) ) && ( ! empty( $course_settings['course_lesson_orderby'] ) ) ) {
					$course_lessons_args['orderby'] = $this->settings['course_lesson_orderby'];
				}
			}

			/**
			 * Filters course lessons order query arguments.
			 *
			 * @param array $course_lesson_args An array of course lesson order arguments.
			 * @param int   $course_id          Course ID.
			 */
			return apply_filters( 'learndash_course_lessons_order', $course_lessons_args, $this->post_id );
		}

		/**
		 * Get Lessons per page setting
		 */
		public function get_settings_lessons_per_page() {
			$course_lessons_per_page = learndash_get_course_lessons_per_page( $this->post_id );
			return $course_lessons_per_page;
		}

		/**
		 * Get Topics per page setting
		 */
		public function get_settings_topics_per_page() {
			$course_topics_per_page = learndash_get_course_topics_per_page( $this->post_id );
			return $course_topics_per_page;
		}

		/**
		 * Get Post Setting.
		 *
		 * @since 2.5.0
		 *
		 * @param string $setting_key           Setting key to return.
		 * @param string $setting_default_value Setting default value.
		 * @param bool   $force                 Control reloading of setting.
		 */
		public function get_setting( $setting_key = '', $setting_default_value = null, $force = false ) {
			if ( $this->load_settings() ) {
				if ( ! empty( $setting_key ) ) {
					switch ( $setting_key ) {
						case 'course_lesson_orderby':
						case 'orderby':
							$setting_ordering = $this->get_settings_order();
							if ( isset( $setting_ordering['orderby'] ) ) {
								return $setting_ordering['orderby'];
							} else {
								return null;
							}
							break;

						case 'course_lesson_order':
						case 'order':
							$setting_ordering = $this->get_settings_order();
							if ( isset( $setting_ordering['order'] ) ) {
								return $setting_ordering['order'];
							} else {
								return null;
							}
							break;

						case 'course_lesson_per_page':
						case 'per_page':
							return $this->get_settings_lessons_per_page();

						case 'course_topic_per_page':
							return $this->get_settings_topics_per_page();

						default:
							return parent::get_setting( $setting_key, $setting_default_value = null, $force = false );
					}
				} else {
					return $this->settings;
				}
			}
		}

		/**
		 * Get Course Steps.
		 *
		 * @since 2.5.0
		 *
		 * @param string $steps_type Steps type.
		 */
		public function get_steps( $steps_type = 'h' ) {
			if ( ! empty( $this->post_id ) ) {
				return learndash_course_get_steps_by_type( $this->post_id, $steps_type );
			}
		}

		/**
		 * Get Course Steps Count.
		 */
		public function get_steps_count() {
			if ( ! empty( $this->post_id ) ) {
				learndash_course_get_steps_count( $this->post_id );
			}
		}

		/**
		 * General function to check if user can view/read step post.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $step_post_id Step Post ID.
		 *
		 * @return boolean True if user can view/read post.
		 */
		protected function can_user_read_step( $step_post_id = 0 ) {
			$user_can_read_post = true;

			if ( ( defined( 'LEARNDASH_COURSE_STEP_READ_CHECK' ) ) && ( true === LEARNDASH_COURSE_STEP_READ_CHECK ) ) {
				if ( ! empty( $step_post_id ) ) {
					if ( is_user_logged_in() ) {
						if ( ! current_user_can( 'read_post', $step_post_id ) ) {
							$step_post = get_post( $step_post_id );
							if ( ( $step_post ) && ( is_a( $step_post, 'WP_Post' ) ) ) {
								if ( ! in_array( $step_post->post_status, array( 'publish' ), true ) ) {
									$user_can_read_post = false;
								}
							}
						}
					} else {
						$step_post = get_post( $step_post_id );
						if ( ( $step_post ) && ( is_a( $step_post, 'WP_Post' ) ) ) {
							if ( ! in_array( $step_post->post_status, array( 'publish' ), true ) ) {
								$user_can_read_post = false;
							}
						}
					}
				}
			}

			/**
			 * Filters user can view/read course step.
			 *
			 * @since 3.4.0
			 *
			 * @param boolean  $user_can_read_post True if user can read step post.
			 * @param integer  $step_post_id       Step Post ID.
			 * @param integer  $course_id          Course ID.
			 */
			return apply_filters( 'learndash_can_user_read_step', $user_can_read_post, $step_post_id, $this->post_id );
		}

		/**
		 * Get Course Sections
		 *
		 * @since 3.4.0
		 *
		 * @param array $query_args Array of query args to filter sections.
		 */
		public function get_sections( $query_args = array() ) {
			$query_args_defaults = array(
				'paged'       => 1,
				'per_page'    => -1,
				'return_type' => 'ID',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$course_sections = $this->get_steps( 'sections' );

			$pager_key = $this->post_id . ':sections';

			$this->pager[ $pager_key ]['per_page']    = $query_args['per_page'];
			$this->pager[ $pager_key ]['paged']       = $query_args['paged'];
			$this->pager[ $pager_key ]['total_items'] = count( $course_sections );
			$this->pager[ $pager_key ]['total_pages'] = 1;

			if ( ! empty( $course_sections ) ) {
				$this->pager[ $pager_key ]['total_items'] = count( $course_sections );
				if ( $query_args['per_page'] > 0 ) {
					$this->pager[ $pager_key ]['total_pages'] = ceil( $this->pager[ $pager_key ]['total_items'] / $query_args['per_page'] );

					$course_sections = array_slice( $course_sections, ( $query_args['paged'] - 1 ) * $query_args['per_page'], $query_args['per_page'] );
				}

				foreach ( $course_sections as $section_idx => &$section ) {
					if ( ( property_exists( $section, 'steps' ) ) && ( ! empty( $section->steps ) ) ) {
						foreach ( $section->steps as $step_idx => $step_id ) {
							if ( ! $this->can_user_read_step( $step_id ) ) {
								unset( $section->steps[ $step_idx ] );
							}
						}
						$section->steps = array_values( $section->steps );
					}
				}
			}

			return $course_sections;
		}

		/**
		 * Get Course Lessons
		 *
		 * @since 3.4.0
		 *
		 * @param array $query_args Array of query args to filter lessons.
		 */
		public function get_lessons( $query_args = array() ) {
			$query_args_defaults = array(
				'paged'       => 1,
				'per_page'    => $this->get_setting( 'course_lesson_per_page' ),
				'return_type' => 'WP_Post',
				'nopaging'    => false,
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$lessons = learndash_course_get_children_of_step( $this->post_id, 0, learndash_get_post_type_slug( 'lesson' ), $query_args['return_type'] );
			if ( ! empty( $lessons ) ) {
				foreach ( $lessons as $lesson_id => $lesson_post ) {
					if ( ! $this->can_user_read_step( $lesson_post->ID ) ) {
						unset( $lessons[ $lesson_id ] );
					}
				}
			}

			$pager_key = $this->post_id . ':' . learndash_get_post_type_slug( 'lesson' );

			$this->pager[ $pager_key ]['per_page']    = $query_args['per_page'];
			$this->pager[ $pager_key ]['paged']       = $query_args['paged'];
			$this->pager[ $pager_key ]['total_items'] = count( $lessons );
			$this->pager[ $pager_key ]['total_pages'] = 1;

			if ( ! empty( $lessons ) ) {
				$this->pager[ $pager_key ]['total_items'] = count( $lessons );
				if ( ( $query_args['per_page'] > 0 ) && ( false === $query_args['nopaging'] ) ) {
					$this->pager[ $pager_key ]['total_pages'] = ceil( $this->pager[ $pager_key ]['total_items'] / $query_args['per_page'] );

					$lessons = array_slice( $lessons, ( $query_args['paged'] - 1 ) * $query_args['per_page'], $query_args['per_page'] );
				}

				if ( 'ids' === $query_args['return_type'] ) {
					$lessons = wp_list_pluck( $lessons, 'ID' );
				}
			}

			return $lessons;
		}

		/**
		 * Get Course Lesson Topics
		 *
		 * @since 3.4.0
		 *
		 * @param integer $lesson_id  Lesson ID parent.
		 * @param array   $query_args Array of query args to filter lessons.
		 */
		public function get_topics( $lesson_id = 0, $query_args = array() ) {
			$topics = array();

			$lesson_id = absint( $lesson_id );
			if ( empty( $lesson_id ) ) {
				return $topics;
			}

			// check the user can read/view the parent lesson.
			if ( ! $this->can_user_read_step( $lesson_id ) ) {
				return $topics;
			}

			$query_args_defaults = array(
				'paged'       => 1,
				'per_page'    => -1,
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$topics = learndash_course_get_children_of_step( $this->post_id, $lesson_id, learndash_get_post_type_slug( 'topic' ), $query_args['return_type'] );
			if ( ! empty( $topics ) ) {
				foreach ( $topics as $topic_id => $topic_post ) {
					if ( ! $this->can_user_read_step( $topic_post->ID ) ) {
						unset( $topics[ $topic_id ] );
					}
				}
			}

			$pager_key = $lesson_id . ':' . learndash_get_post_type_slug( 'topic' );

			$this->pager[ $pager_key ]['paged']       = $query_args['paged'];
			$this->pager[ $pager_key ]['total_items'] = count( $topics );
			$this->pager[ $pager_key ]['total_pages'] = 1;

			if ( ! empty( $topics ) ) {
				if ( $query_args['per_page'] > 0 ) {
					$this->pager[ $pager_key ]['total_pages'] = ceil( $this->pager[ $pager_key ]['total_items'] / $query_args['per_page'] );

					$topics = array_slice( $topics, ( $query_args['paged'] - 1 ) * $query_args['per_page'], $query_args['per_page'] );
				}

				if ( 'ids' === $query_args['return_type'] ) {
					$topics = wp_list_pluck( $topics, 'ID' );
				}
			}

			return $topics;
		}

		/**
		 * Get Quizzes
		 *
		 * @since 3.4.0
		 *
		 * @param integer $parent_id  Course/Lesson/Topic ID parent.
		 * @param array   $query_args Array of query args to filter quizzes.
		 */
		public function get_quizzes( $parent_id = 0, $query_args = array() ) {
			$quizzes = array();

			$parent_id = absint( $parent_id );
			if ( empty( $parent_id ) ) {
				return $quizzes;
			}

			/**
			 * Check if the user can access the parent steps.
			 */
			$parent_steps = learndash_course_get_all_parent_step_ids( $this->post_id, $parent_id );
			if ( ! empty( $parent_steps ) ) {
				foreach ( $parent_steps as $parent_step_post_id ) {
					$parent_step_post_id = absint( $parent_step_post_id );
					if ( ! empty( $parent_step_post_id ) ) {
						if ( ! $this->can_user_read_step( $parent_step_post_id ) ) {
							return $quizzes;
						}
					}
				}
			}

			$query_args_defaults = array(
				'paged'       => 1,
				'per_page'    => -1,
				'return_type' => 'WP_Post',
			);

			$query_args = wp_parse_args( $query_args, $query_args_defaults );

			$quizzes = learndash_course_get_children_of_step( $this->post_id, $parent_id, learndash_get_post_type_slug( 'quiz' ), $query_args['return_type'] );
			if ( ! empty( $quizzes ) ) {
				foreach ( $quizzes as $quiz_id => $quiz_post ) {
					if ( ! $this->can_user_read_step( $quiz_post->ID ) ) {
						unset( $quizzes[ $quiz_id ] );
					}
				}
			}

			$pager_key = $parent_id . ':' . get_post_type( $parent_id );

			$this->pager[ $pager_key ]['paged']       = $query_args['paged'];
			$this->pager[ $pager_key ]['total_items'] = count( $quizzes );
			$this->pager[ $pager_key ]['total_pages'] = 1;

			if ( ! empty( $quizzes ) ) {
				if ( $query_args['per_page'] > 0 ) {
					$this->pager[ $pager_key ]['total_pages'] = ceil( $this->pager[ $pager_key ]['total_items'] / $query_args['per_page'] );

					$quizzes = array_slice( $quizzes, ( $query_args['paged'] - 1 ) * $query_args['per_page'], $query_args['per_page'] );
				}

				if ( 'ids' === $query_args['return_type'] ) {
					$quizzes = wp_list_pluck( $quizzes, 'ID' );
				}
			}

			return $quizzes;
		}

		/**
		 * Get Course Pager.
		 *
		 * @since 3.4.0
		 *
		 * @param int    $parent_id Parent ID. This may be the Course, Lesson, Topic or Quiz.
		 * @param string $post_type Post Type of Parent ID.
		 */
		public function get_pager( $parent_id = 0, $post_type = '' ) {
			$pager = array();

			$parent_id = absint( $parent_id );
			$post_type = esc_attr( $post_type );
			if ( ( ! empty( $parent_id ) ) && ( ! empty( $post_type ) ) ) {
				$pager_key = $parent_id . ':' . $post_type;
				if ( isset( $this->pager[ $pager_key ] ) ) {
					$pager = $this->pager[ $pager_key ];
				}
			}

			return $pager;
		}

		// End of functions.
	}
}
