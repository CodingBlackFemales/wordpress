<?php

/**
 * Lifter LMS Helper Functions
 *
 */

namespace BuddyBossTheme;

use WP_Query;
use LLMS_Student;
use LLMS_Lesson;
use LLMS_Course;
use LLMS_Student_Dashboard;

if ( ! class_exists( '\BuddyBossTheme\LifterLMSHelper' ) ) {

	class LifterLMSHelper implements BBLMSHelper {
		const LMS_WIDGET_NAME_COURSES = 'bb-llms-courses';
		const LMS_WIDGET_NAME_ACTIVITY = 'ld-activity';
		const LMS_CLASS = 'LifterLMS';
		const LMS_NAME = 'LifterLMS';
		const LMS_SHORT_NAME = 'llms';
		const LMS_POST_TYPE = 'course';
		const LMS_VIEW_OPTION = 'bb_theme_lifter_grid_list';
		const LMS_CATEGORY_SLUG = 'course_cat';
		const LMS_TAG_SLUG = 'course_tag';

		protected $_my_course_progress = [];

		/**
		 * Constructor
		 */
		public function __construct() {
			remove_action( 'lifterlms_single_lesson_before_summary', 'lifterlms_template_single_parent_course', 10 );
			remove_action( 'lifterlms_single_quiz_before_summary', 'lifterlms_template_quiz_return_link', 10 );
			remove_action( 'llms_single_assignment_before_summary', 'llms_assignments_template_return_to_lesson_link', 10 );
			remove_action( 'lifterlms_after_loop_item_title', 'lifterlms_template_loop_author', 10 );

			add_filter( 'body_class', [ $this, 'body_class' ] );
			add_filter( 'buddyboss-theme-main-js-data', [ $this, 'js_l10n' ] );

			/*****************
			 * Course Index
			 ******************/
			add_action( 'wp_ajax_buddyboss_lms_get_courses', [ $this, 'ajax_get_courses' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_lms_get_courses', [ $this, 'ajax_get_courses' ] );

			add_action( 'admin_init', [ $this, 'cover_course_photo' ] );
			add_action( 'after_setup_theme', [ $this, 'my_llms_theme_support' ] );
			add_filter( 'llms_get_theme_default_sidebar', [ $this, 'my_llms_sidebar_function' ] );

			// Course loop
			remove_action( 'lifterlms_archive_description', 'lifterlms_archive_description', 10 );
			remove_action( 'lifterlms_loop', 'lifterlms_loop', 10 );
			add_action( 'lifterlms_loop', [ $this, 'boss_lifterlms_loop' ], 10 );

			$lifterlms_course_author = buddyboss_theme_get_option( 'lifterlms_course_author' );
			if ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) ) {
				add_action( 'lifterlms_after_loop_item_title', 'lifterlms_template_loop_author', 10 );
			}
			remove_action( 'lifterlms_before_loop_item_title', 'lifterlms_template_loop_progress', 15 );
			remove_action( 'lifterlms_after_loop_item_title', 'lifterlms_template_loop_length', 15 );

			// Course review
			remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_reviews', 100 );
			add_action( 'lifterlms_single_course_after_summary', [
				$this,
				'lifterlms_template_single_reviews_course',
			], 100 );

			// Course page main title.
			add_filter( 'lifterlms_show_page_title', '__return_false' );

			// Single Course
			/* Remove duplicated featured video on single course page */
			remove_action( 'lifterlms_single_course_before_summary', 'lifterlms_template_single_video', 20 );

			// Lifter LMS courses sidebar
			remove_action( 'lifterlms_sidebar', 'lifterlms_get_sidebar', 10 );
			add_action( 'lifterlms_sidebar', [ $this, 'theme_lifterlms_get_sidebar' ], 10 );

			add_filter( 'llms_certificates_loop_columns', [ $this, 'bb_llms_certificate_loop_cols' ] );
			add_filter( 'llms_achievement_loop_columns', [ $this, 'bb_llms_achievements_loop_cols' ] );

			// Hide dashboard page title.
			add_filter( 'body_class', [ $this, 'bb_llms_is_dashboard_page_class' ] );

			add_action( 'lifterlms_single_membership_before_summary', [
				$this,
				'bb_llms_single_membership_before_summary',
			], 10 );

			remove_action( 'lifterlms_after_student_dashboard', 'lifterlms_template_student_dashboard_wrapper_close', 10 );
			add_action( 'lifterlms_after_student_dashboard', [
				$this,
				'bb_lifterlms_template_student_dashboard_wrapper_close',
			], 12 );

			// Pricing Tables
			remove_action( 'llms_before_access_plan', 'llms_template_access_plan_feature', 10 );
			add_action( 'llms_acces_plan_footer', 'llms_template_access_plan_feature', 5 );

			remove_action( 'llms_acces_plan_content', 'llms_template_access_plan_restrictions', 30 );
			add_action( 'llms_acces_plan_footer', 'llms_template_access_plan_restrictions', 15 );

			remove_action( 'llms_acces_plan_content', 'llms_template_access_plan_pricing', 20 );
			add_action( 'llms_acces_plan_footer', 'llms_template_access_plan_pricing', 5 );

			remove_action( 'llms_acces_plan_footer', 'llms_template_access_plan_button', 20 );
			add_action( 'llms_acces_plan_content', 'llms_template_access_plan_button', 50 );

			add_action( 'wp_ajax_buddyboss_llms_get_course_participants', [
				$this,
				'buddyboss_llms_get_course_participants',
			] );
			add_action( 'wp_ajax_nopriv_buddyboss_llms_get_course_participants', [
				$this,
				'buddyboss_llms_get_course_participants',
			] );

			add_action( 'wp_ajax_buddyboss_llms_save_view', [ $this, 'buddyboss_llms_save_view' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_llms_save_view', [ $this, 'buddyboss_llms_save_view' ] );


			add_action( 'wp_ajax_buddyboss_lms_get_memberships', [ $this, 'ajax_get_memberships' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_lms_get_memberships', [ $this, 'ajax_get_memberships' ] );

			add_filter( 'llms_get_product_schedule_details', [
				$this,
				'buddyboss_llms_add_space_before_schedule_details',
			], 9999, 2 );

			add_action( 'parse_query', array( $this, 'buddyboss_llms_prepare_course_archive_page_query' ) );
			add_action( 'pre_get_posts', array( $this, 'buddyboss_llms_course_archive_page_query' ), 999 );
			add_action( 'bb_llms_display_certificate', [ $this, 'bb_llms_certificate_content' ], 50 );
			add_action( 'bb_llms_display_certificate', [ $this, 'bb_llms_certificate_actions' ], 60 );

			add_action( 'llms_user_enrolled_in_course', [ $this, 'bb_flush_llms_mycourse_ids_cache_user_id' ], 9999, 1 );
			add_action( 'llms_user_removed_from_course', [ $this, 'bb_flush_llms_mycourse_ids_cache_user_id' ], 9999, 1 );
			add_action( 'llms_user_enrollment_deleted', [ $this, 'bb_flush_llms_mycourse_ids_cache_user_id' ], 9999, 1 );

			add_filter( 'llms_course_meta_info_title_size', [ $this, 'bb_llms_course_meta_info_title_size' ], 9999, 1 );
		}

		/**
		 * Get parent course based on lesson object as compatible with lifterlms version.
		 *
		 * @since 2.0.1
		 *
		 * @param object $lesson Object of the lesson.
		 *
		 * @return integer $course_id
		 */
		public function bb_lifterlms_get_parent_course( $lesson ) {
			if ( defined( 'LLMS_VERSION' ) && version_compare( LLMS_VERSION, '5.7.0', '<' ) ) {
				$course_id = $lesson->get_parent_course(); // Its deprecated since version 5.7.0.
			} else {
				$course_id = $lesson->get( 'parent_course' );
			}
			return $course_id;
		}

		/**
		 * Get lesson order based on lesson object as compatible with lifterlms version.
		 *
		 * @since 2.0.1
		 *
		 * @param object $lesson Object of the lesson.
		 *
		 * @return integer $lesson_order
		 */
		public function bb_lifterlms_get_lesson_order( $lesson ) {
			if ( defined( 'LLMS_VERSION' ) && version_compare( LLMS_VERSION, '5.7.0', '<' ) ) {
				$lesson_order = $lesson->get_order(); // Its deprecated since version 5.7.0.
			} else {
				$lesson_order = $lesson->get( 'order' );
			}
			return $lesson_order;
		}

		/**
		 * Get results of attempt quiz as compatible with lifterlms version.
		 *
		 * @since 2.0.1
		 *
		 * @param object $query Object of the quiz attempt.
		 *
		 * @return array $results
		 */
		public function bb_lifterlms_get_quiz_result( $query ) {
			if ( defined( 'LLMS_VERSION' ) && version_compare( LLMS_VERSION, '6.0.0', '<' ) ) {
				$results = $query->results; // Its deprecated since version 6.0.0.
			} else {
				$results = $query->get_results();
			}
			return $results;
		}

		public function buddyboss_llms_add_space_before_schedule_details( $period, $data ) {

			$period = '&nbsp;' . $period;

			return apply_filters( 'buddyboss_llms_add_space_before_schedule_details', $period, $data );

		}

		public function buddyboss_llms_save_view() {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'list-grid-settings') ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'buddyboss-theme' ) ) );
			}

			$object = bb_theme_filter_input_string( INPUT_POST, 'object' );
			if ( empty( $object ) || 'llms-course' !== $object ) {
				wp_send_json_error( array(
					'message' => __( 'Not a valid object', 'buddyboss-theme' ),
				) );
				wp_die();
			}

			$option_name = bb_theme_filter_input_string( INPUT_POST, 'option' );
			if ( empty( $option_name ) || 'bb_layout_view' !== $option_name ) {
				wp_send_json_error( array(
					'message' => __( 'Not a valid option', 'buddyboss-theme' ),
				) );
				wp_die();
			}

			// phpcs:ignore WordPress.Security.NonceVerification
			$option_value = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : '';

			if ( ! in_array( $option_value, array( 'grid', 'list' ), true )) {
				wp_send_json_error( array(
					'message' => __( 'Not a valid value', 'buddyboss-theme' ),
				) );
				wp_die();
			}

			if ( is_user_logged_in() ) {
				$existing_layout = get_user_meta( get_current_user_id(), $option_name, true );
				$existing_layout = ! empty( $existing_layout ) ? $existing_layout : array();
				// Store layout option in the db.
				$existing_layout[ $object ] = $option_value;
				update_user_meta( get_current_user_id(), $option_name, $existing_layout );
			} else {
				$existing_layout = ! empty( $_COOKIE[ $option_name ] ) ? json_decode( rawurldecode( $_COOKIE[ $option_name ] ), true ) : array();
				// Store layout option in the cookie.
				$existing_layout[ $object ] = $option_value;
				setcookie( $option_name, rawurlencode( wp_json_encode( $existing_layout ) ), time() + 31556926, '/', COOKIE_DOMAIN, false, false );
			}

			wp_send_json_success( array( 'html' => 'success' ) );
			wp_die();
		}

		public function buddyboss_llms_get_course_participants() {

			check_ajax_referer( 'buddyboss_llms_get_courses' );

			$course_id   = isset( $_GET['course'] ) ? (int) $_GET['course'] : 0;
			$total_users = isset( $_GET['total'] ) ? (int) $_GET['total'] : 0;
			$page        = isset( $_GET['page'] ) ? (int) $_GET['page'] : 1;

			// how many users to show per page
			$users_per_page = apply_filters( 'buddyboss_llms_get_course_participants', 5 );

			$data = buddyboss_theme()->lifterlms_helper()->bb_theme_llms_get_users_for_course( $course_id, $page, $users_per_page );

			$show_more = 'false';
			if ( true === $data['has_more'] ) {
				$show_more = 'true';
			}

			$page = $page + 1;
			$html = '';

			ob_start();

			if ( isset( $data['data'] ) && '' !== $data['data'] ) {
				foreach ( $data['data'] as $k => $course_member ) {
					?>
                    <li>
						<?php
						if ( class_exists( 'BuddyPress' ) ) { ?>
                        <a href="<?php echo bp_core_get_user_domain( (int) $course_member->user_id ); ?>">
							<?php
							} ?>
                            <img class="round"
                                 src="<?php echo get_avatar_url( (int) $course_member->user_id, [ 'size' => 96 ] ); ?>"
                                 alt=""/>
							<?php
							if ( class_exists( 'BuddyPress' ) ) { ?>
                                <span><?php echo bp_core_get_user_displayname( (int) $course_member->user_id ); ?></span>
								<?php
							} else {
								$course_member = get_userdata( (int) $course_member->user_id );
								?>
                                <span><?php echo $course_member->display_name; ?></span>
								<?php
							}
							if ( class_exists( 'BuddyPress' ) ) { ?>
                        </a>
					<?php
					} ?>
                    </li>
					<?php
				}
			}

			$html = ob_get_contents();
			ob_end_clean();
			wp_send_json_success(
				[
					'html'      => $html,
					'show_more' => $show_more,
					'page'      => $page,
				]
			);
			die();
		}

		/**
		 * Get course participants.
		 *
		 * @param integer $course_id Course ID.
		 * @param integer $page      Page number.
		 * @param integer $per_page  Number of participants per page.
		 *
		 * @return array
		 */
		public function bb_theme_llms_get_users_for_course( $course_id, $page = 1, $per_page = 6 ) {

			$query_args = array(
				'page'     => $page,
				'post_id'  => $course_id,
				'per_page' => $per_page,
				'sort'     => array(
					'date'       => 'ASC',
					'last_name'  => 'ASC',
					'first_name' => 'ASC',
					'id'         => 'ASC',
				),
				'statuses' => array( 'enrolled' ),
			);

			$query          = new \LLMS_Student_Query( $query_args );
			$data           = $query->get_results();
			$modified_array = array();

			if ( ! empty( $data ) ) {
				$modified_array = array_map(
					function ( $object ) use ( $course_id ) {
						$object->user_id       = $object->id;
						$object->post_id       = $course_id;
						$object->enrolled_date = $object->date;

						unset( $object->id );
						unset( $object->date );
						unset( $object->last_name );
						unset( $object->first_name );

						return $object;
					},
					$data
				);
			}

			$response                = array();
			$response['total']       = (int) $query->get_found_results();
			$response['data']        = $modified_array;
			$response['total_pages'] = (int) $query->get_max_pages();
			$response['has_more']    = ( (int) $page >= (int) $query->get_max_pages() ) ? false : true;

			return $response;
		}

		public function bb_theme_llms_get_author( $args ) {

			$args = wp_parse_args(
				$args,
				[
					'avatar'      => true,
					'avatar_size' => 96,
					'bio'         => false,
					'label'       => '',
					'user_id'     => get_the_author_meta( 'ID' ),
				]
			);

			$name = get_the_author_meta( 'display_name', $args['user_id'] );

			if ( $args['avatar'] ) {
				$img = get_avatar( $args['user_id'], $args['avatar_size'], apply_filters( 'bb_theme_lifterlms_author_avatar_placeholder', '' ), $name );
			} else {
				$img = '';
			}

			$img = apply_filters( 'bb_theme_llms_get_author_image', $img );

			$desc = '';
			if ( $args['bio'] ) {
				$desc = get_the_author_meta( 'description', $args['user_id'] );
			}

			$user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( $args['user_id'] );

			ob_start();
			?>
            <div class="llms-author flex">
                <a href="<?php echo esc_url( $user_link ); ?>">
					<?php echo $img; ?>
                </a>
                <div class="llms-author__verbose">
                    <a href="<?php echo esc_url( $user_link ); ?>">
                        <span class="llms-author-info name"><?php echo $name; ?></span>
                    </a>
					<?php if ( $args['label'] ) : ?>
                        <span class="llms-author-info label"><?php echo $args['label']; ?></span>
					<?php endif; ?>
					<?php if ( $desc ) : ?>
                        <p class="llms-author-info bio"><?php echo $desc; ?></p>
					<?php endif; ?>
                </div>
            </div>
			<?php
			$html = ob_get_clean();

			return apply_filters( 'bb_theme_llms_get_author', $html, $args );

		}

		/**
		 * This function will add the "is_dashboard" class to page body and then will hide the title.
		 *
		 * @param $classes
		 *
		 * @return array
		 * @since BuddyBossTheme 1.0.0
		 *
		 */
		public function bb_llms_is_dashboard_page_class( $classes ) {
			global $post;

			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'lifterlms_my_account' ) ) {
				$classes[] = 'is_dashboard';

				return $classes;
			}

			return $classes;

		}

		/**
		 * Get the total number of courses available.
		 *
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 */
		public function get_all_courses_count() {

			$terms = wp_list_pluck(
				get_terms(
					[
						'taxonomy'   => 'llms_product_visibility',
						'hide_empty' => false,
					]
				),
				'term_taxonomy_id',
				'name'
			);

			$not_in = [ $terms['hidden'], $terms['search'] ];

			$category = get_queried_object();
			if ( isset( $category->term_id ) && $category->term_id ) {

				$courses = new WP_Query(
					[
						'post_type'   => 'course',
						'post_status' => 'publish',
						'tax_query'   => [
							'relation' => 'AND',
							[
								'taxonomy' => "$category->taxonomy",
								'field'    => 'id',
								'terms'    => [ $category->term_id ],
							],
							[
								'field'    => 'term_taxonomy_id',
								'operator' => 'NOT IN',
								'taxonomy' => 'llms_product_visibility',
								'terms'    => $not_in,
							],
						],
					]
				);
			} else {
				$courses = new WP_Query(
					[
						'post_type'   => 'course',
						'post_status' => 'publish',
						'tax_query'   => [
							'relation' => 'AND',
							[
								'field'    => 'term_taxonomy_id',
								'operator' => 'NOT IN',
								'taxonomy' => 'llms_product_visibility',
								'terms'    => $not_in,
							],
						],
					]
				);
			}

			return ! empty( $courses->found_posts ) ? $courses->found_posts : 0;
		}

		/**
		 * Get the number of courses a given user has access to.
		 *
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 */
		public function get_my_courses_count( $user_id = false, $tax_query = array() ) {

			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			if ( empty( $user_id ) ) {
				return 0;
			}

			$student = llms_get_student( $user_id );
			if ( ! $student ) {
				return 0;
			}

			$courses = $student->get_courses(
				apply_filters( 'llms_my_courses_loop_courses_query_args', array(
					'limit' => 1
				), $student )
			);

			return $courses['found'];
		}

		/**
		 * Print the options for sorting/orderby dropdown.
		 *
		 * @param array|string $args    {
		 *                              Array of parameters. All items are optional.
		 *
		 * @type string|array $selected Selected items
		 * }
		 *
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 */
		public function print_sorting_options( $args = '' ) {

			$defaults = [
				'selected' => false,
			];

			$args = wp_parse_args( $args, $defaults );

			$order_by_options = $this->_get_orderby_options();

			if ( empty( $args['selected'] ) ) {

				$default = apply_filters( 'BuddyBossTheme/LifterLMS/Archive/DefaultOrderBy', 'alphabetical' );

				if ( ! isset( $order_by_options[ $default ] ) ) {
					foreach ( $order_by_options as $k => $v ) {
						$default = $k;// first one
						break;
					}
				}

				$order_by_current = isset( $_GET['orderby'] ) ? $_GET['orderby'] : $default;
				$order_by_current = isset( $order_by_options[ $order_by_current ] ) ? $order_by_current : $default;
				$args['selected'] = $order_by_current;
			}

			foreach ( $order_by_options as $opt => $label ) {
				printf( "<option value='%s' %s>%s</option>", $opt, selected( $args['selected'], $opt, false ), $label );
			}
		}

		/**
		 * Function to the orderby option
		 *
		 * @return mixed|void
		 */
		protected function _get_orderby_options() {

			$order_by_options = [
				'alphabetical' => __( 'Alphabetical', 'buddyboss-theme' ),
				'recent'       => __( 'Newly Created', 'buddyboss-theme' ),
			];

			if ( is_user_logged_in() ) {
				$order_by_options['my-progress'] = __( 'My Progress', 'buddyboss-theme' );
			}

			return apply_filters( 'BuddyBossTheme/LifterLMS/Archive/OrderByOptions', $order_by_options );
		}

		/**
		 * Print the options for instructors dropdown.
		 *
		 * @param array|string $args    {
		 *                              Array of parameters. All items are optional.
		 *
		 * @type string|array $selected Selected items
		 * @type string $option_all     Text to display for 'all' option
		 * }
		 * @since BuddyBossTheme 1.0.0
		 */
		public function print_instructors_options( $args = '' ) {

			$defaults = [
				'selected'   => false,
				'option_all' => __( 'All Instructors', 'buddyboss-theme' ),
			];

			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['selected'] ) ) {
				$args['selected'] = isset( $_GET['filter-instructors'] ) && ! empty( $_GET['filter-instructors'] ) ? $_GET['filter-instructors'] : '';
			}

			echo "<option value='all'>{$args['option_all']}</option>";

			global $wpdb;
			$query = $wpdb->get_col( "SELECT pm.meta_value FROM {$wpdb->posts} AS p JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID WHERE p.post_type = 'course' AND p.post_status = 'publish' AND pm.meta_key = '_llms_instructors';" );

			$instructor_ids = array();

			if ( ! is_wp_error( $query ) && ! empty( $query ) ) {
				foreach ( $query as $ids ) {
					$ids = maybe_unserialize( $ids );
					$ids = wp_list_pluck( $ids, 'id' );

					if ( ! empty( $ids ) ) {
						$instructor_ids = array_unique( array_merge( $instructor_ids, $ids ) );
					}
				}
			}

			$query = get_users(
				array(
					'fields'   => array( 'ID', 'display_name' ),
					'meta_key' => 'last_name',
					'orderby'  => 'meta_value',
					'include'  => $instructor_ids,
				)
			);

			$author_ids = wp_list_pluck( $query, 'display_name', 'ID' );

			$author_ids = apply_filters( THEME_HOOK_PREFIX . 'lifterlms_instructors_options', $author_ids, $args );

			if ( ! empty( $author_ids ) ) {
				$authors = [];
				foreach ( $author_ids as $k => $value ) {
					$authors[ $k ] = $value;
				}

				// sort
				asort( $authors );

				foreach ( $authors as $uid => $name ) {
					printf(
						"<option value='%s' %s>%s</option>",
						$uid,
						selected( $args['selected'], $uid, false ),
						$name
					);
				}
			}
		}

		/**
		 * Function to the course progress for the student
		 *
		 * @param $course_id int course id to fetch the progress.
		 *
		 * @return float
		 */
		public function boss_theme_progress_course( $course_id ) {

			$progress = 0;

			if ( is_user_logged_in() && llms_is_user_enrolled( get_current_user_id(), $course_id ) ) {

				$course   = new LLMS_Course( $course_id );
				$progress = $course->get_percent_complete();

			}

			return $progress;
		}

		/**
		 * Function the add the class in body tag when the lifter lms is active.
		 *
		 * @param $classes array class array
		 *
		 * @return array
		 */
		public function body_class( $classes ) {

			if ( ( isset( $_COOKIE['bbtheme'] ) && 'dark' == $_COOKIE['bbtheme'] && is_user_logged_in() ) && ( is_singular( 'lesson' ) || is_singular( 'llms_assignment' ) || is_singular( 'llms_quiz' ) ) ) {
				$classes[] = 'bb-dark-theme';
			}

			if ( class_exists( 'LifterLMS' ) ) {
				$classes[] = 'llms-theme';
			}

			return $classes;
		}

		/**
		 * Function to add localize data in to js file for lifter.
		 *
		 * @param $data array localize data
		 *
		 * @return mixed
		 */
		public function js_l10n( $data ) {

			$category = get_queried_object();

			$data['lifterlms'] = [
				'nonce_get_courses'     => wp_create_nonce( 'buddyboss_llms_get_courses' ),
				'nonce_get_memberships' => wp_create_nonce( 'buddyboss_llms_get_memberships' ),
				'course_archive_url'    => trailingslashit( get_post_type_archive_link( 'course' ) ),
				'course_membership_url' => trailingslashit( get_post_type_archive_link( 'llms_membership' ) ),
				'course_category_url'   => ( isset( $category->term_id ) && $category->term_id ) ? trailingslashit( get_category_link( $category->term_id ) ) : '',
				'is_course_category'    => ( isset( $category->term_id ) && $category->term_id ) ? 1 : 0,
				'course_category_id'    => ( isset( $category->term_id ) && $category->term_id ) ? $category->term_id : 0,
				'course_category_name'  => ( isset( $category->taxonomy ) && $category->taxonomy ) ? $category->taxonomy : '',
			];

			return $data;
		}

		/**
		 * Function to prepare course html for AJAX request.
		 */
		public function ajax_get_courses() {

			check_ajax_referer( 'buddyboss_llms_get_courses' );

			$order_by_current = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'alphabetical';

			if ( 'my-progress' === $order_by_current ) {
				$this->_my_course_progress = $this->get_courses_progress( get_current_user_id() );
			}

			add_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );

			$posts_per_page = 0;

			if ( class_exists( 'LifterLMS' ) ) {
				$posts_per_page = get_option( 'lifterlms_shop_courses_per_page' );
			}

			if ( empty( $posts_per_page ) ) {
				$posts_per_page = get_option( 'posts_per_page' );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 5;
				}
			}

			// Set post per page if set through GET.
			if ( isset( $_GET['posts_per_page'] ) && ! empty( $_GET['posts_per_page'] ) ) {
				$posts_per_page = absint( $_GET['posts_per_page'] );
			}

			$category = isset( $_GET['course_category_id'] ) ? (int) $_GET['course_category_id'] : 0;
			$taxonomy = isset( $_GET['course_category_name'] ) ? $_GET['course_category_name'] : '';
			if ( $category ) {
				$args = [
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_page,
					'post_type'      => 'course',
					'paged'          => isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1,
					'tax_query'      => [
						[
							'taxonomy' => "$taxonomy",
							'field'    => 'id',
							'terms'    => [ $category ],
						],
					],
				];
			} else {
				$args = [
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_page,
					'post_type'      => 'course',
					'paged'          => isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1,
				];
			}

			$args = apply_filters( THEME_HOOK_PREFIX . 'llms_ajax_get_courses_args', $args );
			$pagination_url = '';
			if ( isset( $_GET['request_url'] ) && ! empty( $_GET['request_url'] ) ) {
				// Decode the requested URL.
				$pagination_url = urldecode_deep( $_GET['request_url'] );

				// Validate the requested URL.
				if ( false === strpos( $pagination_url, get_site_url() ) ) {
					$pagination_url = '';
				}
			}

			if ( empty( $pagination_url ) ) {
				$pagination_url = $category ? get_category_link( $category ) : get_post_type_archive_link( 'course' );
			}

			$view = bb_theme_get_directory_layout_preference( 'llms-course' );

			$c_q = new WP_Query( $args );

			if ( $c_q->have_posts() ) {

				$courses_list = [
					'list-view' => [],
					'grid-view' => [],
				];

				while ( $c_q->have_posts() ) {
					$c_q->the_post();

					ob_start();
					get_template_part( 'lifterlms/course/course-index-loop' );
					$courses_list['grid-view'][] = ob_get_clean();

					ob_start();
					get_template_part( 'lifterlms/course/course-index-loop' );
					$courses_list['list-view'][] = ob_get_clean();
				}

				$html = '<ul class="bb-course-list bb-course-items bb-grid list-view ' . ( 'list' != $view ? 'hide' : '' ) . '" aria-live="assertive" aria-relevant="all">' . implode(
						'',
						$courses_list['list-view']
					) . '</ul>' . '<ul class="bb-card-list bb-course-items grid-view bb-grid ' . ( 'grid' != $view ? 'hide' : '' ) . '" aria-live="assertive" aria-relevant="all">' . implode(
					        '',
					        $courses_list['grid-view']
				        ) . '</ul>';

				$html .= '<div class="bb-lms-pagination">';

				$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

				if ( $category ) {
					$html .= paginate_links(
						[
							'base'               => trailingslashit( $pagination_url ) . 'page/%#%/',
							'format'             => '?paged=%#%',
							'current'            => ( isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1 ),
							'total'              => $c_q->max_num_pages,
							'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
						]
					);
				} else {
					$html .= paginate_links(
						[
							'base'               => trailingslashit( $pagination_url ) . 'page/%#%/',
							'format'             => '?paged=%#%',
							'current'            => ( isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1 ),
							'total'              => $c_q->max_num_pages,
							'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
						]
					);
				}

				$html .= '</div><!-- .bb-lms-pagination -->';
			} else {
				$html = '<aside class="bp-feedback bp-template-notice ld-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>';
				$html .= __( 'Sorry, no courses were found.', 'buddyboss-theme' );
				$html .= '</p></aside>';
			}

			wp_reset_postdata();

			$total = $c_q->found_posts;

			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );

			wp_send_json_success(
				[
					'html'   => $html,
					'count'  => $total,
					'scopes' => $this->get_course_query_scope( $c_q->query_vars ),
					'layout' => $view,
				]
			);
			die();
		}

		public function ajax_get_memberships() {

			check_ajax_referer( 'buddyboss_llms_get_memberships' );

			add_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_memberships' ], 999 );

			$posts_per_page = 0;

			if ( class_exists( 'LifterLMS' ) ) {
				$posts_per_page = get_option( 'lifterlms_shop_courses_per_page' );
			}

			if ( empty( $posts_per_page ) ) {
				$posts_per_page = get_option( 'posts_per_page' );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 5;
				}
			}

			$args = [
				'post_status'    => 'publish',
				'posts_per_page' => $posts_per_page,
				'post_type'      => 'llms_membership',
				'paged'          => isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1,
			];

			$view            = get_option( 'bb_theme_lifter_membership_grid_list', 'grid' );
			$class_grid_show = ( 'grid' === $view ) ? 'grid-view bb-grid' : '';
			$class_list_show = ( 'list' === $view ) ? 'list-view bb-list' : '';

			$args = apply_filters( THEME_HOOK_PREFIX . 'llms_ajax_get_memberships', $args );

			$c_q = new WP_Query( $args );

			if ( $c_q->have_posts() ) {

				$courses_list = [
					'list-view' => [],
					'grid-view' => [],
				];

				while ( $c_q->have_posts() ) {
					$c_q->the_post();

					ob_start();
					get_template_part( 'lifterlms/membership/membership-index-loop' );
					$courses_list['grid-view'][] = ob_get_clean();
				}

				$html = '<ul class="bb-course-list bb-course-items ' . esc_attr( $class_grid_show . $class_list_show ) . ' " aria-live="assertive" aria-relevant="all">' . implode(
						'',
						$courses_list['grid-view']
					) . '</ul>';

				$html .= '<div class="bb-lms-pagination">';

				$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

				$html .= paginate_links(
					[
						'base'               => trailingslashit( get_post_type_archive_link( 'llms_membership' ) ) . 'page/%#%/',
						'format'             => '?paged=%#%',
						'current'            => ( isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1 ),
						'total'              => $c_q->max_num_pages,
						'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
					]
				);

				$html .= '</div><!-- .bb-lms-pagination -->';
			} else {
				$html = '<aside class="bp-feedback bp-template-notice ld-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>';
				$html .= __( 'Sorry, no memberships were found.', 'buddyboss-theme' );
				$html .= '</p></aside>';
			}

			wp_reset_postdata();

			$total = $c_q->found_posts;

			wp_send_json_success(
				[
					'html'  => $html,
					'count' => $total,
				]
			);
			die();
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		public function filter_query_ajax_get_courses( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );
			$query = $this->course_archive_query_params( $query );
		}

		public function filter_query_ajax_get_memberships( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_memberships' ], 999 );
			$query = $this->membership_archive_query_params( $query );
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		protected function course_archive_query_params( $query ) {
			// search
			if ( ! empty( $_GET['search'] ) ) {
				$query->set( 's', sanitize_text_field( wp_unslash( $_GET['search'] ) ) );
			}

			// my courses
			$query = $this->_archive_only_my_courses( $query );

			// ordering
			$query = $this->_set_archive_orderby( $query );

			// visibility
			$query = $this->search_visiblility_tax( $query );

			// categories
			$query = $this->_archive_filterby_tax( $query );

			// instructors
			$query = $this->_archive_filterby_instructors( $query );

			return apply_filters( 'BuddyBossTheme/LifterLMS/Archive/Filterby_Instructors', $query );
		}

		protected function membership_archive_query_params( $query ) {
			// search
			if ( ! empty( $_GET['search'] ) ) {
				$query->set( 's', sanitize_text_field( wp_unslash( $_GET['search'] ) ) );
			}

			// visibility
			$query = $this->search_visiblility_tax( $query );

			return $query;
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		protected function _set_archive_orderby( $query ) {
			$order_by_options = $this->_get_orderby_options();

			$default = apply_filters( 'BuddyBossTheme/LifterLMS/Archive/DefaultOrderBy', 'alphabetical' );
			if ( ! isset( $order_by_options[ $default ] ) ) {
				foreach ( $order_by_options as $k => $v ) {
					$default = $k;// first one
					break;
				}
			}

			$order_by_current = isset( $_GET['orderby'] ) ? $_GET['orderby'] : $default;
			$order_by_current = isset( $order_by_options[ $order_by_current ] ) ? $order_by_current : $default;

			switch ( $order_by_current ) {
				case 'alphabetical':
					$query_order_by = 'title';
					$query_order    = 'asc';
					break;
				case 'my-progress':
					$query_order_by = 'date';
					$query_order    = 'desc';// doesn't matter

					add_filter( 'posts_clauses', [ $this, 'alter_query_parts' ], 10, 2 );
					break;
				case 'recent':
					$query_order_by = 'date';
					$query_order    = 'desc';
					break;
				default:
					$query_order_by = 'title';
					$query_order    = 'asc';
					break;
			}

			$query->set( 'orderby', $query_order_by );
			$query->set( 'order', $query_order );

			return $query;
		}

		/**
		 * Function to alter the query according to the progress.
		 *
		 * @param $clauses string query clause
		 * @param $query   object query
		 *
		 * @return mixed
		 */
		public function alter_query_parts( $clauses, $query ) {

			remove_filter( 'posts_clauses', [ $this, 'alter_query_parts' ], 10, 2 );

			$my_course_progress = $this->_my_course_progress;

			if ( ! empty( $my_course_progress ) ) {

				$clauses['fields'] .= ', CASE ';

				global $wpdb;
				$id_colum_name = $wpdb->posts . '.ID';

				foreach ( $my_course_progress as $course_id => $progress ) {
					$clauses['fields'] .= ' WHEN ' . $id_colum_name . ' = ' . $course_id . ' THEN ' . $progress . ' ';
				}

				$clauses['fields']  .= ' ELSE 0 END AS my_progress ';
				$clauses['orderby'] = 'my_progress DESC, ' . $clauses['orderby'];
			}

			return $clauses;
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		protected function _archive_filterby_tax( $query ) {

			$tax_query = $query->get( 'tax_query' );

			if ( empty( $tax_query ) ) {
				$tax_query = [];
			}

			// Query Depend on theme setting
			if ( ! empty( $_GET[ "filter-categories" ] ) && 'all' != $_GET['filter-categories'] ) {

				$tax_query[] = array(
					'taxonomy'         => self::LMS_CATEGORY_SLUG,
					'field'            => 'slug',
					'terms'            => explode( ',', $_GET["filter-categories"] ),
					'include_children' => false,
				);
			}

			if ( ! empty( $_GET["filter-block-categories"] ) || ! empty( $_GET["filter-block-tags"] ) ) {
				$tax_blog_query = array(
					'relation' => 'AND',
				);

				/**
				 * Without interact with theme setting. Filter course by course categories
				 * Used by Elementor widgets like Course grid
				 */
				if ( ! empty( $_GET["filter-block-categories"] ) ) {
					$tax_blog_query[] = array(
						'taxonomy'         => self::LMS_CATEGORY_SLUG,
						'field'            => 'id',
						'terms'            => wp_parse_id_list( $_GET["filter-block-categories"] ),
						'include_children' => false,
					);

				}

				/**
				 * Without interact with theme setting. Filter course by course tags
				 * Used by Elementor widgets like Course grid
				 */
				if ( ! empty( $_GET["filter-block-tags"] ) ) {
					$tax_blog_query[] = array(
						'taxonomy'         => self::LMS_TAG_SLUG,
						'field'            => 'id',
						'terms'            => wp_parse_id_list( $_GET["filter-block-tags"] ),
						'include_children' => false,
					);

				}

				$tax_query[] = $tax_blog_query;
			}

			if ( ! empty( $tax_query ) ) {
				$query->set('tax_query' , $tax_query );
			}

			return $query;
		}

		protected function search_visiblility_tax( $query ) {

			$tax_query = $query->get( 'tax_query' );

			if ( ! is_array( $tax_query ) ) {
				$tax_query = [
					'relation' => 'AND',
				];
			}

			$terms = wp_list_pluck(
				get_terms(
					[
						'taxonomy'   => 'llms_product_visibility',
						'hide_empty' => false,
					]
				),
				'term_taxonomy_id',
				'name'
			);

			if ( ! empty( $_GET['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$not_in = [ $terms['hidden'], $terms['catalog'] ];
			} else {
				$not_in = [ $terms['hidden'], $terms['search'] ];
			}


			$visibility_tax_query = array(
				'field'    => 'term_taxonomy_id',
				'operator' => 'NOT IN',
				'taxonomy' => 'llms_product_visibility',
				'terms'    => $not_in,
			);

			if ( ! in_array( $visibility_tax_query, $tax_query ) ) {
				$tax_query[] = $visibility_tax_query;
			}

			$query->set( 'tax_query', $tax_query );

			return $query;
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		protected function _archive_only_my_courses( $query ) {

			// phpcs:ignore WordPress.Security.NonceVerification
			if ( ! isset( $_GET['type'] ) || 'my-courses' !== $_GET['type'] ) {
				return $query;
			}

			if ( is_user_logged_in() ) {

				$user_id    = get_current_user_id();
				$course_ids = wp_cache_get( $user_id, 'llms_mycourse_ids' );
				if ( ! $course_ids ) {
					$student_data    = new LLMS_Student();
					$student_courses = $student_data->get_courses();
					$course_ids      = ( ! empty( $student_courses['results'] ) ) ? $student_courses['results'] : [ - 1 ];
					wp_cache_set( $user_id, $course_ids, 'llms_mycourse_ids' );
				}

				// phpcs:ignore WordPress.Security.NonceVerification
				$category = isset( $_GET['course_category_id'] ) ? (int) $_GET['course_category_id'] : 0;
				// phpcs:ignore WordPress.Security.NonceVerification
				$taxonomy = isset( $_GET['course_category_name'] ) ? $_GET['course_category_name'] : '';
				if ( $category > 0 ) {
					$in = [];
					foreach ( $course_ids as $course ) {
						$cats     = wp_get_object_terms( $course, $taxonomy );
						$term_ids = wp_list_pluck( $cats, 'term_id' );
						if ( in_array( (int) $category, $term_ids, true ) ) {
							$in[] = $course;
						}
					}
					$course_ids = $in;
				}

				$query->set( 'post__in', $course_ids );
			}

			return $query;
		}

		/**
		 * Function alter the query.
		 *
		 * @param $query object query object
		 */
		protected function _archive_filterby_instructors( $query ) {
			if ( ! empty( $_GET['filter-instructors'] ) && 'all' != $_GET['filter-instructors'] ) {
				$authors = $_GET['filter-instructors'];
				$authors = wp_parse_id_list( $authors );

				if ( ! empty( $authors ) ) {
					$meta_query['relation'] = 'OR';
					foreach ( $authors as $author ) {
						$meta_query[] = [
							'compare' => 'LIKE',
							'key'     => '_llms_instructors',
							'value'   => 's:2:"id";i:' . $author . ';',
						];
					}
				}

				$query->set( 'meta_query', $meta_query );

				$query->set( 'llms_instructor_query', true );

				$query->set( 'author', '' );
			}

			return $query;
		}

		/**
		 * Print the options for categories dropdown.
		 *
		 * @since BuddyBossTheme 1.0.0
		 *
		 * @param array|string $args       {
		 *                                 Array of parameters. All items are optional.
		 *
		 * @type string|array  $selected   Selected items
		 * @type string        $orderby    Orderby. Default name
		 * @type string        $order      Default 'ASC'
		 * @type string        $option_all Text to display for 'all' option
		 *                                 }
		 *
		 * @return mixed
		 */
		public function print_categories_options( $args = '' ) {

			$defaults = array(
				'selected'   => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'include'    => array(),
				'option_all' => __( 'All Categories', 'buddyboss-theme' ),
			);

			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['selected'] ) ) {
				$args['selected'] = isset( $_GET['filter-categories'] ) && ! empty( $_GET['filter-categories'] ) ? $_GET['filter-categories'] : '';
			}

			$all_cate_val = 'all';

			$taxonomy = $this->get_theme_category();
			$category = get_queried_object();

			if ( isset( $category->term_id ) && $category->term_id ) {
				$categories = get_terms(
					array(
						'taxonomy' => "$category->taxonomy",
						'orderby'  => $args['orderby'],
						'order'    => $args['order'],
						'include'  => $args['include'],
					)
				);
			} else {
				$categories = get_terms(
					array(
						'taxonomy' => "$taxonomy",
						'orderby'  => $args['orderby'],
						'order'    => $args['order'],
						'include'  => $args['include'],
					)
				);
			}

			$html = '';
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {

				$category_slugs = array();
				foreach ( $categories as $term ) {
					$html             .= sprintf( "<option value='%s' %s>%s</option>", $term->slug, selected( $args['selected'], $term->slug, false ), $term->name );
					$category_slugs[] = $term->slug;
				}

				if ( ! empty( $args['include'] ) ) {
					$all_cate_val = implode( ',', $category_slugs );
				}
			}

			if ( '' !== $html ) {
				return "<option value='{$all_cate_val}'>{$args['option_all']}</option>" . $html;
			}
		}

		public function get_course_category() {
			$taxonomy   = $this->get_theme_category();
			$categories = get_terms(
				array(
					'taxonomy' => "$taxonomy",
				)
			);

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				return $categories;
			}

			return '';
		}

		/**
		 * Function to get the course progress for Lifter LMS.
		 *
		 * @param $user_id    int user id
		 * @param $sort_order string sorting order
		 *
		 * @return array|bool|mixed|string
		 */
		public function get_courses_progress( $user_id, $sort_order = 'desc' ) {

			$course_completion_percentage = wp_cache_get( $user_id, 'llms_courses_progress' );

			if ( ! $course_completion_percentage ) {

				$student                      = new LLMS_Student();
				$courses                      = $student->get_courses();
				$course_completion_percentage = [];

				if ( ! empty( $courses['results'] ) && is_array( $courses['results'] ) ) {
					foreach ( $courses['results'] as $course_id ) {
						$course_completion_percentage[ $course_id ] = $student->get_progress( $course_id, 'course' );
					}
				}

				// Avoid running the queries multiple times if user's course progress is empty
				$course_completion_percentage = ! empty( $course_completion_percentage ) ? $course_completion_percentage : 'empty';

				wp_cache_set( $user_id, $course_completion_percentage, 'llms_courses_progress' );

			}

			$course_completion_percentage = ( 'empty' !== $course_completion_percentage ) ? $course_completion_percentage : [];

			if ( ! empty( $course_completion_percentage ) ) {
				// Sort.
				if ( 'asc' === $sort_order ) {
					asort( $course_completion_percentage );
				} else {
					arsort( $course_completion_percentage );
				}
			}

			return $course_completion_percentage;
		}

		public function filter_query_ajax_do_all_courses_counts( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_do_all_courses_counts' ], 9999 );
			$query->set( 'posts_per_page', 1 );
			$query->set( 'paged', 1 );
			$query->set( 'fields', 'ids' );
			$query->set( 'post__in', [] );
		}

		/**
		 * Function to get the scope of query.
		 *
		 * @param $query_vars array query parameters
		 *
		 * @return array
		 */
		protected function get_course_query_scope( $query_vars ) {

			$return = [
				'all'        => 0,
				'my-courses' => 0,
			];

			add_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );
			add_action( 'pre_get_posts', [ $this, 'filter_query_ajax_do_all_courses_counts' ], 9999 );

			$terms = wp_list_pluck(
				get_terms(
					[
						'taxonomy'   => 'llms_product_visibility',
						'hide_empty' => false,
					]
				),
				'term_taxonomy_id',
				'name'
			);

			if ( ! empty( $_GET['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$not_in = [ $terms['hidden'], $terms['catalog'] ];
			} else {
				$not_in = [ $terms['hidden'], $terms['search'] ];
			}

			$category = isset( $_GET['course_category_id'] ) ? (int) $_GET['course_category_id'] : 0;
			$taxonomy = isset( $_GET['course_category_name'] ) ? $_GET['course_category_name'] : '';
			$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
			if ( $category && $category > 0 ) {
				$all_query = new WP_Query(
					[
						's'           => $search,
						'post_type'   => 'course',
						'post_status' => 'publish',
						'tax_query'   => [
							'relation' => 'AND',
							[
								'taxonomy' => "$taxonomy",
								'field'    => 'id',
								'terms'    => [ $category ],
							],
							[
								'field'    => 'term_taxonomy_id',
								'operator' => 'NOT IN',
								'taxonomy' => 'llms_product_visibility',
								'terms'    => $not_in,
							],
						],
					]
				);
			} else {
				$all_query = new WP_Query(
					[
						's'           => $search,
						'post_type'   => 'course',
						'post_status' => 'publish',
						'tax_query'   => [
							'relation' => 'AND',
							[
								'field'    => 'term_taxonomy_id',
								'operator' => 'NOT IN',
								'taxonomy' => 'llms_product_visibility',
								'terms'    => $not_in,
							],
						],
					]
				);
			}

			$return['all'] = (int) $all_query->found_posts;

			if ( is_user_logged_in() ) {

				$user_id = get_current_user_id();

				if ( ! $course_ids = wp_cache_get( $user_id, 'llms_mycourse_ids' ) ) {
					$student_data    = new LLMS_Student();
					$student_courses = $student_data->get_courses();
					$course_ids      = ( ! empty( $student_courses['results'] ) ) ? $student_courses['results'] : [ - 1 ];
					wp_cache_set( $user_id, $course_ids, 'llms_mycourse_ids' );
				}

				// phpcs:ignore WordPress.Security.NonceVerification
				$category = isset( $_GET['course_category_id'] ) ? (int) $_GET['course_category_id'] : 0;
				// phpcs:ignore WordPress.Security.NonceVerification
				$taxonomy = isset( $_GET['course_category_name'] ) ? $_GET['course_category_name'] : '';
				if ( $category && isset( $category ) && $category > 0 ) {
					$in = [];
					foreach ( $course_ids as $course ) {
						$cats     = wp_get_object_terms( $course, $taxonomy );
						$term_ids = wp_list_pluck( $cats, 'term_id' );
						if ( in_array( (int) $category, $term_ids, true ) ) {
							$in[] = $course;
						}
					}
					$all_query = new WP_Query(
						[
							's'              => $search,
							'post_type'      => 'course',
							'post_status'    => 'publish',
							'posts_per_page' => - 1,
							'post__in'       => $in,
							'tax_query'      => [
								'relation' => 'AND',
								[
									'taxonomy' => "$taxonomy",
									'field'    => 'id',
									'terms'    => [ $category ],
								],
								[
									'field'    => 'term_taxonomy_id',
									'operator' => 'NOT IN',
									'taxonomy' => 'llms_product_visibility',
									'terms'    => $not_in,
								],
							],
						]
					);
				} else {
					$all_query = new WP_Query(
						[
							's'              => $search,
							'post_type'      => 'course',
							'post_status'    => 'publish',
							'post__in'       => $course_ids,
							'posts_per_page' => - 1,
							'tax_query'      => [
								'relation' => 'AND',
								[
									'field'    => 'term_taxonomy_id',
									'operator' => 'NOT IN',
									'taxonomy' => 'llms_product_visibility',
									'terms'    => $not_in,
								],
							],
						]
					);
				}

				$count = (int) $all_query->found_posts;

				$return['my-courses'] = $count;
			}

			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );

			return $return;
		}

		/**
		 * Function to add the cover photo image data.
		 */
		public function cover_course_photo() {
			if ( class_exists( '\BuddyBossTheme\BuddyBossMultiPostThumbnails' ) ) {
				new \BuddyBossTheme\BuddyBossMultiPostThumbnails(
					[
						'label'     => __( 'Cover Photo', 'buddyboss-theme' ),
						'id'        => 'cover-course-image',
						'post_type' => 'course',
					]
				);
			}
		}

		public function lifterlms_course_progress_bar( $progress, $link = false, $button = true, $echo = true, $lessons = array() ) {

			$progress = round( $progress, 2 );

			$tag  = ( $link ) ? 'a' : 'span';
			$href = ( $link ) ? ' href=" ' . $link . ' "' : '';

			if ( is_singular( 'course' ) || is_singular( 'lesson' ) || is_post_type_archive( 'course' ) || is_archive() || is_singular( 'llms_quiz' ) || is_singular( 'llms_assignment' ) ) {
				if ( is_singular( 'lesson' ) ) {
					$type = 'lesson';
				} else {
					if ( is_singular( 'llms_quiz' ) || is_singular( 'llms_assignment' ) ) {
						$type = 'quiz';
					} else {
						$type = 'course';
					}
				}
			} else {
				$type = 'course';
			}

			$html = $this->llms_get_progress_bar_html_course_single( $progress, $type, $lessons );

			if ( $button ) {
				$html .= '<' . $tag . ' class="llms-button-primary llms-purchase-button"' . $href . '>' . __( 'Continue', 'buddyboss-theme' ) . '(' . $progress . '%)</' . $tag . '>';
			}

			if ( $echo ) {
				echo $html;
			} else {
				return $html;
			}
		}

		public function llms_get_progress_bar_html_course_single( $percentage, $post_type, $lessons = array() ) {

			global $post, $course;

			$percentage     = round( $percentage );
			$completed_text = ( $percentage != 100 ) ? __( 'Complete', 'buddyboss-theme' ) : __( 'Completed', 'buddyboss-theme' );
			$last_activity  = '';
			$student        = '';
			$course_id      = get_the_ID();
			$status_class   = ' ';

			if ( 'lesson' === $post_type ) {
				$lesson    = new LLMS_Lesson( $post );
			}

			if ( 'quiz' === $post_type ) {
				$quiz           = llms_get_post( $post );
				$quiz_lesson_id = $quiz->get( 'lesson_id' );
				$post_object    = get_post( $quiz_lesson_id );
				$lesson         = new LLMS_Lesson( $post_object );
			}

			if ( ! empty( $lesson ) ) {
				$course_id = $this->bb_lifterlms_get_parent_course( $lesson );
			}

			if ( is_user_logged_in() ) {
				$student       = new LLMS_Student( get_current_user_id() );
				$last_activity = $student->get_events(
					[
						'per_page' => 1,
						'post_id'  => $course_id,
					]
				);
			}

			if ( ( ! is_user_logged_in() ) || empty( $last_activity ) ) {

				if ( empty( $course ) ) {
					$course = new LLMS_Course( $course_id );
				}

				$completed_lesson_count = 0;
				$all_lesson_count       = 0;
				$lessons                = ( ! empty( $lessons ) ? $lessons : $course->get_lessons( 'ids' ) );

				if ( ! empty( $lessons ) ) {
					$all_lesson_count = count( $lessons );
					foreach ( $lessons as $lesson ) {
						$is_lesson_complete = ! empty( $student ) ? $student->is_complete( $lesson, 'lesson' ) : false;
						if ( $is_lesson_complete ) {
							$completed_lesson_count ++;
						}
					}
				} // End if().
				$last_activity_time = $completed_lesson_count . '/' . $all_lesson_count . ' ' . __( 'Steps', 'buddyboss-theme' );
			} else {
				$last_activity_time = __( 'Last activity on', 'buddyboss-theme' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $last_activity[0]->get( 'updated_date' ) ) );
			}

			$temp_percentage = $percentage;
			if ( 0 === $percentage ) {
				$temp_percentage = '0%';
			} else {
				$temp_percentage = $temp_percentage . '%';
			}

			$html = '';
			$html .= '<div class="llms-progress">
				<div class="progress__indicator"><div class="ld-progress-percentage">' . $temp_percentage . ' ' . $completed_text . '</div><div class="ld-progress-steps">' . $last_activity_time . '</div></div>
				<div class="llms-progress-bar">
					<div class="progress-bar-complete" data-progress="' . $temp_percentage . '"  style="width:' . $temp_percentage . '"></div>
				</div>';

			if ( is_singular( 'course' ) && is_user_logged_in() ) :

				if ( 100 === $percentage ) {
					$status       = __( 'Complete', 'buddyboss-theme' );
					$status_class = ' status-complete';
				} else {
					$status       = __( 'In Progress', 'buddyboss-theme' );
					$status_class = ' status-in-progress';
				}

				if ( ! empty( $status_class ) ) :
					$html .= '<div class="' . $status_class . '">' . $status . '</div>';
				endif;
			endif;
			$html .= '</div>';

			return $html;
		}

		/**
		 * Declare explicit theme support for LifterLMS course and lesson sidebars
		 *
		 * @return   void
		 */
		public function my_llms_theme_support() {
			add_theme_support( 'lifterlms-sidebars' );
		}

		/**
		 * Display LifterLMS Course and Lesson sidebars
		 * on courses and lessons in place of the sidebar returned by
		 * this function
		 *
		 * @param $id string sidebar id.
		 *
		 * @return string
		 */
		public function my_llms_sidebar_function( $id ) {
			$my_sidebar_id = 'secondary';

			return $my_sidebar_id;
		}

		/**
		 * Function include our course/membership loop file.
		 */
		public function boss_lifterlms_loop() {
			if ( is_memberships() || is_membership_category() || is_membership_tag() || is_membership_taxonomy() ) {
				llms_get_template( 'membership/membership-index.php' );
			} else {
				llms_get_template( 'course/course-index.php' );
			}
		}

		/**
		 * Function add div wrapper;
		 */
		public function boss_lifterlms_before_loop_item_title() {
			?>
            <div class="llms-loop-item-after-image">
			<?php
		}

		/**
		 * Function end div wrapper;
		 */
		public function boss_lifterlms_after_loop_item() {
			?>
            </div>
			<?php
		}

		public function lifterlms_template_single_reviews_course() {

			/**
			 * Check to see if we are supposed to output the code at all
			 */
			if ( get_post_meta( get_the_ID(), '_llms_display_reviews', true ) ) {

				$args        = [
					'posts_per_page'   => get_post_meta( get_the_ID(), '_llms_num_reviews', true ),
					'post_type'        => 'llms_review',
					'post_status'      => 'publish',
					'post_parent'      => get_the_ID(),
					'suppress_filters' => true,
				];
				$posts_array = get_posts( $args );

				$styles = [
					'background-color' => '#EFEFEF',
					'title-color'      => 'inherit',
					'text-color'       => 'inherit',
					'custom-css'       => '',
				];

				if ( has_filter( 'llms_review_custom_styles' ) ) {
					$styles = apply_filters( 'llms_review_custom_styles', $styles );
				}

				if ( count( $posts_array ) > 0 ) :
					?>
                    <div id="old_reviews" class='old_reviews--revoke'><h3>
					<?php
					echo apply_filters(
						'lifterlms_reviews_section_title',
						__( 'What Others Have Said', 'buddyboss-theme' )
					);
					?>
                </h3>

				<?php
				endif;

				foreach ( $posts_array as $post ) {
					echo $styles['custom-css'];

					?>

                    <div class="llms_review"
                         style="margin:20px 0px; background-color:<?php echo $styles['background-color']; ?>; padding:10px">
						<?php
						$user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( get_post_field( 'post_author', $post->ID ) );
						?>
                        <div class="review_avatar_image">
                            <a href="<?php echo $user_link; ?>">
								<?php echo get_avatar( get_post_field( 'post_author', $post->ID ), 52 ); ?>
                            </a>
                        </div>
                        <div class="review_content">
                            <a href="<?php echo $user_link; ?>">
                                <div class="review_author">
									<?php
									echo sprintf(
										__( '%s', 'buddyboss-theme' ),
										get_the_author_meta(
											'display_name',
											get_post_field( 'post_author', $post->ID )
										)
									);
									?>
                                </div>
                            </a>

                            <div class="review_date">
								<?php echo date( 'M j, Y', strtotime( $post->post_date ) ); ?>
                            </div>

                            <h5 class="review_content__title"><?php echo get_the_title( $post->ID ); ?></h5>
                            <p class="review_content__description"><?php echo get_post_field( 'post_content', $post->ID ); ?></p>
                        </div>
                    </div>
					<?php
				}

				if ( count( $posts_array ) > 0 ) :
					?>
                    </div>
				<?php
				endif;
			}// End if().

			/**
			 * Check to see if reviews are open
			 */
			if ( get_post_meta( get_the_ID(), '_llms_reviews_enabled', true ) && is_user_logged_in() ) {
				/**
				 * Look for previous reviews that we have written on this course.
				 *
				 * @var array
				 */
				$args        = [
					'posts_per_page'   => 1,
					'post_type'        => 'llms_review',
					'post_status'      => 'publish',
					'post_parent'      => get_the_ID(),
					'author'           => get_current_user_id(),
					'suppress_filters' => true,
				];
				$posts_array = get_posts( $args );

				/**
				 * Check to see if we are allowed to write more than one review.
				 * If we are not, check to see if we have written a review already.
				 */
				if ( get_post_meta( get_the_ID(), '_llms_multiple_reviews_disabled', true ) && $posts_array ) {
					?>
                    <div id="thank_you_box">
                        <h2>
							<?php
							echo apply_filters(
								'llms_review_thank_you_text',
								__( 'Thank you for your review!', 'buddyboss-theme' )
							);
							?>
                        </h2>
                    </div>
					<?php
				} else {
					?>

                    <h3 class="review_title"><?php _e( 'Write a Review', 'buddyboss-theme' ); ?></h3>
                    <div class="review_box" id="review_box">
                        <!--<form method="post" name="review_form" id="review_form">-->
						<?php
						$current_user_link = $user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( get_current_user_id() );
						?>
                        <div class="current_user_avatar">
                            <a href="<?php echo $current_user_link; ?>">
								<?php echo get_avatar( get_current_user_id(), 52 ); ?>
                                <span class="current_user_avatar_name">
								<?php
								echo sprintf(
									__(
										'%s',
										'buddyboss-theme'
									),
									get_the_author_meta( 'display_name', get_current_user_id() )
								);
								?>
								</span>
                            </a>
                        </div>
                        <input style="margin:10px 0px" type="text" name="review_title"
                               placeholder="<?php _e( 'Review Title', 'buddyboss-theme' ); ?>" id="review_title">
                        <h5 style="color:red; display:none" id="review_title_error">
							<?php _e( 'Review Title is required.', 'buddyboss-theme' ); ?>
                        </h5>
                        <textarea name="review_text" placeholder="<?php _e( 'Review Text', 'buddyboss-theme' ); ?>"
                                  id="review_text"></textarea>
                        <h5 style="color:red; display:none" id="review_text_error">
							<?php _e( 'Review Text is required.', 'buddyboss-theme' ); ?>
                        </h5>
						<?php wp_nonce_field( 'submit_review', 'submit_review_nonce_code' ); ?>
                        <input name="action" value="submit_review" type="hidden">
                        <input name="post_ID" value="<?php echo get_the_ID(); ?>" type="hidden" id="post_ID">
                        <span class="review_leave">
					   	<input type="submit" class="button" value="<?php _e( 'Leave Review', 'buddyboss-theme' ); ?>"
                               id="llms_review_submit_button">
					</span>
                        <!--</form>	-->
                    </div>
                    <div id="thank_you_box" style="display:none;">
                        <h2>
							<?php
							echo apply_filters(
								'llms_review_thank_you_text',
								__( 'Thank you for your review!', 'buddyboss-theme' )
							);
							?>
                        </h2>
                    </div>
					<?php
				}
			}// End if().
		}

		/**
		 * Show theme sidebar for course listing page.
		 */
		public function theme_lifterlms_get_sidebar() {

			if ( is_active_sidebar( 'lifter_sidebar' ) ) {
				?>
                <div id="secondary" class="widget-area sm-grid-1-1">
					<?php dynamic_sidebar( 'lifter_sidebar' ); ?>
                </div>
				<?php
			}

		}

		/**
		 * Customize the number of columns displayed on LifterLMS Certificate Loop
		 * Note that LifterLMS has native support for 1 - 6 columns
		 * If you require 7 or more columns you will need to write custom CSS to accommodate
		 *
		 * This version displays a different number of columns on Certificate Loops
		 *
		 * @param int $cols default number of columns (4)
		 *
		 * @return   int
		 */
		public function bb_llms_certificate_loop_cols( $cols ) {

			return 3;

		}

		/**
		 * Customize the number of columns displayed on LifterLMS achievements Loop
		 * Note that LifterLMS has native support for 1 - 6 columns
		 * If you require 7 or more columns you will need to write custom CSS to accommodate
		 *
		 * This version displays a different number of columns on achievements Loops
		 *
		 * @param int $cols default number of columns (4)
		 *
		 * @return   int
		 */
		public function bb_llms_achievements_loop_cols( $cols ) {

			return 3;

		}

		public function bb_llms_get_user_link( $author_id ) {

			if ( class_exists( 'LifterLMS_Social_Learning' ) && function_exists( 'llms_sl_get_student_profile_url' ) ) {
				$user_link = llms_sl_get_student_profile_url( $author_id );
			} elseif ( class_exists( 'BuddyPress' ) ) {
				$user_link = bp_core_get_user_domain( $author_id );
			} else {
				$user_link = get_author_posts_url( $author_id );
			}

			return $user_link;

		}

		public function bb_llms_single_membership_before_summary() {
			echo '<h1 class="entry-title entry-title--llmsMembership">';
			the_title();
			echo '</h1>';
		}

		public function bb_lifterlms_template_student_dashboard_wrapper_close() {
			echo '</div><!-- .llms-student-dashboard__frame -->';
			echo '</div><!-- .llms-student-dashboard -->';
		}


		/**
		 * Gets courses where was any actions last days
		 *
		 * @param int $limit
		 *
		 * @return array
		 */
		public function last_courses_actions( $limit = 5 ) {
			$courses = [];

			if ( is_user_logged_in() ) {
				$args = [
					'orderby' => 'date',
					'order'   => 'DESC',
					'status'  => 'enrolled',
					'limit'   => $limit,
				];

				$student_data = new LLMS_Student();
				$courses      = $student_data->get_courses( $args );
			}

			return ( ! empty( $courses ) && ! empty( $courses['found'] ) ) ? $courses : [];
		}

		/**
		 * Get first lesson to take next
		 *
		 * @param $course
		 *
		 * @return false|mixed
		 */
		public function active_lesson( $course ) {
			$student = new LLMS_Student();
			$course  = new LLMS_Course( $course );

			if ( ! $course || ! is_a( $course, 'LLMS_Post_Model' ) ) {
				return false;
			}

			if ( in_array( $course->get( 'type' ), array( 'lesson', 'quiz' ) ) ) {
				$course = llms_get_post_parent_course( $course->get( 'id' ) );
				if ( ! $course ) {
					return false;
				}
			}

			if ( ! $student || ! llms_is_user_enrolled( $student->get_id(), $course->get( 'id' ) ) ) {
				return false;
			}

			$progress = (int) $student->get_progress( $course->get( 'id' ), 'course' );
			$lesson   = false;
			if ( 100 !== $progress ) {
				$lesson = $student->get_next_lesson( $course->get( 'id' ) );
			}

			return ! empty( $lesson ) ? $lesson : false;
		}

		/** Return the lessons ids of given course id.
         *
		 * @param int $course_id Course ID.
		 *
		 * @return array
		 */
		public function get_course_lessons( $course_id ) {

			$lessons_ids = array();
			$sections    = new WP_Query(
				array(
					'meta_key'       => '_llms_order',
					'meta_query'     => array(
						array(
							'key'   => '_llms_parent_course',
							'value' => $course_id,
						),
					),
					'order'          => 'ASC',
					'orderby'        => 'meta_value_num',
					'post_type'      => 'section',
					'posts_per_page' => - 1,
				)
			);

			if ( $sections->have_posts() ) {

				$section_ids = wp_list_pluck( $sections->posts, 'ID' );
				$lessons     = new WP_Query(
					array(
						'meta_key'       => '_llms_order',
						'meta_query'     => array(
							array(
								'key'     => '_llms_parent_section',
								'value'   => $section_ids,
								'compare' => 'IN'
							),
						),
						'order'          => 'ASC',
						'orderby'        => 'meta_value_num',
						'post_type'      => 'lesson',
						'posts_per_page' => - 1,
					)
				);
				$lessons_ids = wp_list_pluck( $lessons->posts, 'ID' );
			}

			return $lessons_ids;


		}

		/**
		 * Loads the certificate content template.
		 *
		 * @since 2.0.3
		 *
		 * @param LLMS_User_Certificate $certificate Certificate object.
		 * @return void
		 */
		public function bb_llms_certificate_content( $certificate ) {
			$template = 1 === $certificate->get_template_version() ? 'content-legacy' : 'content';
			llms_get_template(
				"certificates/{$template}.php",
				compact( 'certificate' )
			);
		}

		/**
		 * Loads the certificate actions template.
		 *
		 * @since 2.0.3
		 *
		 * @param LLMS_User_Certificate $certificate Certificate object.
		 * @return void
		 */
		public function bb_llms_certificate_actions( $certificate ) {

			if ( ! $certificate->can_user_manage() ) {
				return;
			}

			$dashboard_url   = get_permalink( llms_get_page_id( 'myaccount' ) );
			$cert_ep_enabled = LLMS_Student_Dashboard::is_endpoint_enabled( 'view-certificates' );

			$back_link = $cert_ep_enabled ? llms_get_endpoint_url( 'view-certificates', '', $dashboard_url ) : $dashboard_url;
			$back_text = $cert_ep_enabled ? __( 'All certificates', 'buddyboss-theme' ) : __( 'Dashboard', 'buddyboss-theme' );

			$is_template        = 'llms_certificate' === $certificate->get( 'type' );
			$is_sharing_enabled = $certificate->is_sharing_enabled();
			llms_get_template(
				'certificates/actions.php',
				compact( 'certificate', 'back_link', 'back_text', 'is_sharing_enabled', 'is_template' )
			);
 
		}

		/**
		 * Prefetch user's course progress, if required.
		 * We can't do that on the fly as it involves its own wp_query and hence it'll mess up the global wp query
		 * leading to unexpected results.
		 *
		 * @since 2.3.2
		 *
		 * @param object $query The WP_Query instance (passed by reference).
		 */
		public function buddyboss_llms_prepare_course_archive_page_query( $query ) {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$order_by_current = isset ( $_GET['orderby'] ) ? $_GET['orderby'] : '';
			if ( 'my-progress' === $order_by_current && $query->is_post_type_archive && 'course' === $query->query_vars['post_type'] ) {
				$this->_my_course_progress = $this->get_courses_progress( get_current_user_id() );
			}
		}

		/**
		 * Modify lifter lms course archive page query.
		 *
		 * @since 2.3.2
		 *
		 * @param object $query The WP_Query instance (passed by reference).
		 */
		public function buddyboss_llms_course_archive_page_query( $query ) {
			if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive && 'course' === $query->query_vars['post_type'] ) {
				$query = $this->course_archive_query_params( $query );
			}
		}

		/**
		 * Reset object cache for accessible lifter course accessible by user.
		 *
		 * @since 2.3.60
		 * 
		 * @param int $user_id User Id.
		 */
		public function bb_flush_llms_mycourse_ids_cache_user_id( $user_id ) {
			// Remove the cached course IDs.
			wp_cache_delete( $user_id, 'llms_mycourse_ids' );
		}

		/**
		 * Get selected category.
		 *
		 * @since 2.4.10
		 *
		 * @return string
		 */
		public function get_theme_category() {
			$llms_taxonomy = buddyboss_theme_get_option( 'lifterlms_course_index_categories_filter_taxonomy' );

			if ( 'llms_course_category' === $llms_taxonomy ) {
				$taxonomy = 'course_cat';
			} elseif ( 'llms_course_difficulty' === $llms_taxonomy ) {
				$taxonomy = 'course_difficulty';
			} elseif ( 'llms_course_tag' === $llms_taxonomy ) {
				$taxonomy = 'course_tag';
			} else {
				$taxonomy = 'course_track';
			}

			return $taxonomy;
		}

		/**
		 * Function to change tag to follow heading hierarchy for SEO compatibility.
		 *
		 * @since 2.4.30
		 *
		 * @param string $title_tag Title tag.
		 *
		 * @return string
		 */
		public function bb_llms_course_meta_info_title_size( $title_tag ) {
			if ( 'h2' !== $title_tag ) {
				$title_tag = 'h2';
			}

			return $title_tag;
		}
	}
}
