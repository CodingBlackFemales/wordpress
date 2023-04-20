<?php
/**
 * LearnDash Quizzes (sfwd-quiz) Posts Listing.
 *
 * @since 3.0.0
 * @package LearnDash\Quiz\Listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Quizzes_Listing' ) ) ) {

	/**
	 * Class LearnDash Quizzes (sfwd-quiz) Posts Listing.
	 *
	 * @since 3.0.0
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Quizzes_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'quiz' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}

			$this->selectors = array(
				'course_id'      => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'show_empty_value'         => 'empty',
					'show_empty_label'         => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '-- No %s --', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_value_function'  => array( $this, 'selector_value_for_course' ),
				),
				'lesson_id'      => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'lesson' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( 'All %s', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'show_empty_value'         => 'empty',
					'show_empty_label'         => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( '-- No %s --', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_lesson' ),
					'selector_filters'         => array( 'course_id' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_lesson' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
				),
				'topic_id'       => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'topic' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( 'All %s', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_topic' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_topic' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
					'selector_filters'         => array( 'course_id', 'lesson_id' ),
				),
				'certificate_id' => array(
					'type'                   => 'post_type',
					'post_type'              => learndash_get_post_type_slug( 'certificate' ),
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'All Certificates', 'learndash' ),
					'show_empty_value'       => 'empty',
					'show_empty_label'       => esc_html__( '-- No Certificate --', 'learndash' ),
					'listing_query_function' => array( $this, 'listing_filter_by_certificate' ),
				),

			);

			$this->columns = array(
				'shortcode'    => array(
					'label'   => esc_html__( 'Shortcode', 'learndash' ),
					'after'   => 'title',
					'display' => array( $this, 'show_column_shortcode' ),
				),
				'course'       => array(
					'label'    => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Assigned %s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'shortcode',
					'display'  => array( $this, 'show_column_step_course' ),
					'required' => false,
				),
				'lesson_topic' => array(
					'label'   => sprintf(
						// translators: Placeholders: Lesson, Topic.
						esc_html_x( 'Assigned %1$s / %2$s', 'Placeholders: Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'after'   => 'course',
					'display' => array( $this, 'show_column_step_lesson_or_topic' ),
				),
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				unset( $this->columns['course'] );
				unset( $this->columns['lesson_topic'] );
				unset( $this->selectors['lesson_id']['show_empty_value'] );
				unset( $this->selectors['lesson_id']['show_empty_label'] );
			}

			// If Group Leader remove the selector empty option.
			if ( learndash_is_group_leader_user() ) {
				$gl_manage_courses_capabilities = learndash_get_group_leader_manage_courses();
				if ( 'advanced' !== $gl_manage_courses_capabilities ) {
					unset( $this->selectors['course_id']['show_empty_value'] );
					unset( $this->selectors['course_id']['show_empty_label'] );
				}
			}

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.3
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				add_filter( 'learndash_listing_table_query_vars_filter', array( $this, 'listing_table_query_vars_filter_quizzes' ), 30, 3 );
				add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 20, 2 );

				/**
				 * Convert the Group Post Meta items.
				 *
				 * @since 3.4.1
				 */
				$ld_data_upgrade_quiz_post_meta = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Post_Meta' );
				if ( ( $ld_data_upgrade_quiz_post_meta ) && ( is_a( $ld_data_upgrade_quiz_post_meta, 'Learndash_Admin_Data_Upgrades_Quiz_Post_Meta' ) ) ) {
					$ld_data_upgrade_quiz_post_meta->process_post_meta( false );
				}
			}
		}

		/**
		 * Listing table query vars
		 *
		 * @since 3.4.1
		 *
		 * @param array  $q_vars    Array of query vars.
		 * @param string $post_type Post Type being displayed.
		 * @param array  $query     Main Query.
		 */
		public function listing_table_query_vars_filter_quizzes( $q_vars, $post_type, $query ) {
			return $q_vars;
		}

		/**
		 * Show Course column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id  The Step post ID shown.
		 * @param array $selector Selector array.
		 */
		protected function show_column_shortcode( $post_id = 0, $selector = array() ) {
			if ( ! empty( $post_id ) ) {
				$valid_quiz  = false;
				$quiz_pro_id = learndash_get_setting( $post_id, 'quiz_pro' );
				$quiz_pro_id = absint( $quiz_pro_id );
				if ( ! empty( $quiz_pro_id ) ) {
					$quiz_mapper = new WpProQuiz_Model_QuizMapper();
					$quiz_exists = (bool) $quiz_mapper->exists( $quiz_pro_id );
					if ( true === $quiz_exists ) {
						$valid_quiz = true;
						echo '<strong>[ld_quiz quiz_id="' . absint( $post_id ) . '"]</strong>';
						echo '<br />[LDAdvQuiz ' . absint( $quiz_pro_id ) . ']';
						echo '<br />[LDAdvQuiz_toplist ' . absint( $quiz_pro_id ) . ']';
					}
				}

				if ( false === $valid_quiz ) {
					?>
					<span class="ld-error"><?php esc_html_e( 'Missing ProQuiz Associated Settings.', 'learndash' ); ?></span>
					<?php
				}
			}
		}

		/**
		 * Add Quiz Builder link to Quizzes row action array.
		 *
		 * @since 3.0.0
		 *
		 * @param array   $row_actions Existing Row actions for course.
		 * @param WP_Post $post Course Post object for current row.
		 *
		 * @return array $row_actions
		 */
		public function post_row_actions( $row_actions = array(), $post = null ) {
			if ( $this->post_type_check() ) {
				$row_actions = parent::post_row_actions( $row_actions, $post );

				if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) ) && ( current_user_can( 'edit_post', $post->ID ) ) && ( ! isset( $row_actions['ld-quiz-builder'] ) ) ) {
					/**
					 * Filters whether to show quiz builder row actions or not.
					 *
					 * @since 2.6.4
					 *
					 * @param boolean      $show_row_actions Whether to show row actions.
					 * @param WP_Post|null $course_post      Quiz post object.
					 */
					if ( apply_filters( 'learndash_show_quiz_builder_row_actions', true, $post ) === true ) {
						$label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Use %s Builder', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$link = add_query_arg(
							array(
								'currentTab' => 'learndash_quiz_builder',
							),
							get_edit_post_link( $post->ID )
						);

						$row_actions['ld-quiz-builder'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							esc_url( $link ),
							esc_attr( $label ),
							esc_html__( 'Builder', 'learndash' )
						);
					}
				}

				$pro_quiz_id = learndash_get_setting( $post, 'quiz_pro' );
				if ( ! empty( $pro_quiz_id ) ) {
					if ( ( ! isset( $row_actions['questions'] ) ) || ( empty( $row_actions['questions'] ) ) ) {
						if ( ( true === learndash_is_data_upgrade_quiz_questions_updated() ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) ) ) {
							$link = add_query_arg(
								array(
									'post_type' => learndash_get_post_type_slug( 'question' ),
									'quiz_id'   => $post->ID,
								),
								admin_url( 'edit.php' )
							);
						} else {
							$link = add_query_arg(
								array(
									'page'    => 'ldAdvQuiz',
									'module'  => 'question',
									'quiz_id' => $pro_quiz_id,
									'post_id' => $post->ID,
								),
								admin_url( 'admin.php' )
							);
						}

						$label = sprintf(
							// translators: placeholder: Quiz, Questions.
							esc_html_x( 'Show %1$s %2$s', 'placeholder: Quiz, Questions', 'learndash' ),
							learndash_get_custom_label( 'quiz' ),
							learndash_get_custom_label( 'questions' )
						);

						$row_actions['questions'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							esc_url( $link ),
							esc_attr( $label ),
							esc_html__( 'Questions', 'learndash' )
						);
					}

					if ( ( current_user_can( 'wpProQuiz_show_statistics' ) ) && ( ( ! isset( $row_actions['statistics'] ) ) || ( empty( $row_actions['statistics'] ) ) ) ) {
						if ( learndash_get_setting( $post->ID, 'statisticsOn' ) ) {
							$link = add_query_arg(
								array(
									'page'       => 'ldAdvQuiz',
									'module'     => 'statistics',
									'id'         => $pro_quiz_id,
									'post_id'    => $post->ID,
									'currentTab' => 'statistics',
								),
								admin_url( 'admin.php?' )
							);

							$label = sprintf(
								// translators: placeholder: Quiz.
								esc_html_x( 'Show %s Statistics', 'placeholder: Quiz', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quiz' )
							);

							$row_actions['statistics'] = sprintf(
								'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
								esc_url( $link ),
								esc_attr( $label ),
								esc_html__( 'Statistics', 'learndash' )
							);
						}
					}

					if ( ( current_user_can( 'wpProQuiz_toplist_edit' ) ) && ( ( ! isset( $row_actions['leaderboard'] ) ) || ( empty( $row_actions['leaderboard'] ) ) ) ) {
						if ( learndash_get_setting( $post->ID, 'toplistActivated' ) ) {
							$link = add_query_arg(
								array(
									'page'       => 'ldAdvQuiz',
									'module'     => 'toplist',
									'id'         => $pro_quiz_id,
									'post_id'    => $post->ID,
									'currentTab' => 'leaderboard',
								),
								admin_url( 'admin.php' )
							);

							$label = sprintf(
								// translators: placeholder: Quiz.
								esc_html_x( 'Show %s Leaderboard', 'placeholder: Quiz', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quiz' )
							);

							$row_actions['leaderboard'] = sprintf(
								'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
								esc_url( $link ),
								esc_attr( $label ),
								esc_html__( 'Leaderboard', 'learndash' )
							);
						}
					}

					if ( ( current_user_can( 'wpProQuiz_export' ) ) && ( ( ! isset( $row_actions['export'] ) ) || ( empty( $row_actions['export'] ) ) ) ) {
						$link = add_query_arg(
							array(
								'page'    => 'ldAdvQuiz',
								'quiz_id' => $post->ID,
							),
							admin_url( 'admin.php' )
						);

						$label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Export %s', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['export'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							esc_url( $link ),
							esc_attr( $label ),
							esc_html__( 'Export', 'learndash' )
						);
					}
				}
			}

			return $row_actions;
		}

		// End of functions.
	}
}
new Learndash_Admin_Quizzes_Listing();
