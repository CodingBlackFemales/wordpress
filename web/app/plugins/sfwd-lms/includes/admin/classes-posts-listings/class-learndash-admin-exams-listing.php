<?php
/**
 * LearnDash Exams (ld-exam) Posts Listing Class.
 *
 * @package LearnDash\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Exams_Listing' ) ) ) {
	/**
	 * Class for LearnDash Exams Listing Pages.
	 */
	class Learndash_Admin_Exams_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'exam' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}

			$this->selectors = array(
				'exam_challenge_course_show'   => array(
					'type'                   => 'post_type',
					'post_type'              => learndash_get_post_type_slug( 'course' ),
					'show_all_value'         => '',
					'show_all_label'         => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s Show', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'show_empty_value'       => 'empty',
					'show_empty_label'       => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '-- No %s --', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'listing_query_function' => array( $this, 'listing_filter_by_course_show' ),
				),

				'exam_challenge_course_passed' => array(
					'type'                   => 'post_type',
					'post_type'              => learndash_get_post_type_slug( 'course' ),
					'show_all_value'         => '',
					'show_all_label'         => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s Pass', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'show_empty_value'       => 'empty',
					'show_empty_label'       => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '-- No %s --', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'listing_query_function' => array( $this, 'listing_filter_by_course_passed' ),
				),
			);

			$this->columns = array(
				'exam_challenge_course_show'   => array(
					'label'    => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Show', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'title',
					'display'  => array( $this, 'show_column_step_course_show' ),
					'required' => false,
				),
				'exam_challenge_course_passed' => array(
					'label'    => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Passed', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'exam_challenge_course_show',
					'display'  => array( $this, 'show_column_step_course_passed' ),
					'required' => false,
				),
			);

			add_action( 'admin_notices', array( $this, 'learndash_exam_legacy_theme_warning' ) );

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Show Exam Course "show" column.
		 *
		 * @since 4.0.0
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_course_show( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				$course_id = (int) learndash_get_setting( $post_id, 'exam_challenge_course_show' );
				if ( ! empty( $course_id ) ) {
					$course_post = get_post( $course_id );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) ) {

						$course_title = learndash_format_step_post_title_with_status_label( $course_post );

						$row_actions = array();

						$filter_url = add_query_arg( 'exam_challenge_course_show', $course_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . wp_kses_post( $course_title ) . '</a>';

						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						if ( current_user_can( 'edit_post', $course_id ) ) {
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( get_edit_post_link( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $course_id ) ) ) {
							$row_actions['ld-post-view'] = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				}
			}
		}

		/**
		 * Show Exam Course "passed" column.
		 *
		 * @since 4.0.0
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_course_passed( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				$course_id = (int) learndash_get_setting( $post_id, 'exam_challenge_course_passed' );
				if ( ! empty( $course_id ) ) {
					$course_post = get_post( $course_id );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) ) {
						$course_title = learndash_format_step_post_title_with_status_label( $course_post );

						$row_actions = array();

						$filter_url = add_query_arg( 'exam_challenge_course_passed', $course_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . wp_kses_post( $course_title ) . '</a>';

						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						if ( current_user_can( 'edit_post', $course_id ) ) {
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( get_edit_post_link( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $course_id ) ) ) {
							$row_actions['ld-post-view'] = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				}
			}
		}

		/**
		 * Filter Exam listing by Course Show
		 *
		 * @since 4.0.0
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_course_show( $q_vars, $selector = array() ) {

			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						array(
							'key'     => 'exam_challenge_course_show',
							'compare' => 'NOT EXISTS',
						),
					);
				} else {

					if ( ! empty( $selector['selected'] ) ) {

						if ( ! isset( $q_vars['meta_query'] ) ) {
							$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						}

						$q_vars['meta_query'][] = array(
							'key'   => 'exam_challenge_course_show',
							'value' => absint( $selector['selected'] ),
						);
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Filter Exam listing by Course Pass
		 *
		 * @since 4.0.0
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_course_passed( $q_vars, $selector = array() ) {

			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						array(
							'key'     => 'exam_challenge_course_passed',
							'compare' => 'NOT EXISTS',
						),
					);
				} else {

					if ( ! empty( $selector['selected'] ) ) {

						if ( ! isset( $q_vars['meta_query'] ) ) {
							$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						}

						$q_vars['meta_query'][] = array(
							'key'   => 'exam_challenge_course_passed',
							'value' => absint( $selector['selected'] ),
						);
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Returns message if current active theme is set to Legacy
		 *
		 * @since 4.0.0
		 */
		public function learndash_exam_legacy_theme_warning() {
			if ( learndash_is_active_theme( 'legacy' ) ) {
				$message = learndash_exam_legacy_theme_warning_message();
				if ( ! empty( $message ) ) {
					echo wp_kses_post( $message );
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Exams_Listing();
