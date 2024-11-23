<?php
/**
 * LearnDash Helper Functions
 *
 */

namespace BuddyBossTheme;

use LearnDash_Settings_Section;

if ( ! class_exists( '\BuddyBossTheme\LearndashHelper' ) ) {

	class LearndashHelper implements BBLMSHelper {
		const LMS_WIDGET_NAME_COURSES = 'ld-courses';
		const LMS_WIDGET_NAME_ACTIVITY = 'ld-activity';
		const LMS_CLASS = 'SFWD_LMS';
		const LMS_NAME = 'LearnDash';
		const LMS_SHORT_NAME = 'ld';
		const LMS_POST_TYPE = 'sfwd-courses';
		const LMS_VIEW_OPTION = 'bb_theme_learndash_grid_list';
		const LMS_CATEGORY_SLUG = 'ld_course_category';
		const LMS_TAG_SLUG = 'ld_course_tag';

		protected $_is_active = false;

		protected $_my_course_progress = [];

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'learndash_init', [ $this, 'set_active' ] );

			add_action( 'admin_init', [ $this, 'course_cover_photo' ] );

			add_action( 'wp_ajax_buddyboss_lms_toggle_theme_color', [ $this, 'toggle_theme_color' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_lms_toggle_theme_color', [ $this, 'toggle_theme_color' ] );
			add_filter( 'body_class', [ $this, 'body_class' ] );

			add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 30 );
			add_action( 'save_post', [ $this, 'course_save_price_box_data' ] );
			//add_action( 'save_post', array( $this, 'course_cover_photo_save' ) );

			add_filter( 'learndash_profile_shortcode_atts', [ $this, 'user_courses_atts' ] );
			add_filter( 'bp_learndash_user_courses_atts', [ $this, 'user_courses_atts' ] );

			add_action( 'wp_ajax_buddyboss_lms_get_courses', [ $this, 'ajax_get_courses' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_lms_get_courses', [ $this, 'ajax_get_courses' ] );

			add_action( 'parse_query', [ $this, 'prepare_course_archive_page_query' ] );
			add_action( 'pre_get_posts', [ $this, 'course_archive_page_query' ], 999 );

			add_filter( 'bp_learndash_courses_page_title', [ $this, 'remove_course_title' ] );

			add_filter( 'buddyboss-theme-main-js-data', [ $this, 'js_l10n' ] );

			add_action( 'wp_head', [ $this, 'boss_theme_find_last_known_learndash_page' ] );

			// Convert Social Learner Video Metabox to LD Video Progression.
			add_action( 'init', [ $this, 'boss_theme_convert_social_learner_video_to_ld_video_progression' ] );
			add_action( 'template_redirect', [
				$this,
				'boss_theme_convert_social_learner_video_to_ld_video_progression',
			] );

			// Remove the <div class="ld-video"> div from the content if video enabled.
			//add_filter( 'learndash_content', array( $this, 'buddyboss_theme_ld_remove_video_from_content' ), 999, 2 );

			// Remove the Take course button if price not added.
			add_filter( 'learndash_payment_button', [ $this, 'buddyboss_theme_ld_payment_buttons' ], 999, 2 );

			if ( function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {

				add_action( 'template_redirect', [ $this, 'ld_30_template_override' ] );

				add_filter( 'learndash_30_get_template_part', [ $this, 'ld_30_get_template_part' ], 10, 2 );

				// Filter for set always No to focus_mode_enabled.
				//add_action('update_option_learndash_settings_theme_ld30', array( $this, 'ld_30_focus_mode_set_disable' ), 9999, 3);

				add_action( 'wp_enqueue_scripts', [ $this, 'bb_learndash_30_custom_colors' ], 999, 2 );

				// Set the default template for the lessons, topic, assignment and quiz single pages.
				add_filter( 'template_include', [ $this, 'ld_30_focus_mode_template' ], 999, 1 );

				// Add custom class if focus mode enabled.
				add_filter( 'body_class', [ $this, 'ld_30_custom_body_classes' ], 999, 1 );

				// Add filter for [ld_course_list] shortcode
				add_filter( 'ld_course_list', [ $this, 'add_grid_list_filter_on_shortcode' ], 9999, 3 );

			}

			if ( buddyboss_theme_get_option( 'learndash_course_participants', null, true ) ) {
				add_action( 'learndash_update_course_access', [
					$this,
					'buddyboss_theme_refresh_ld_course_enrolled_users_total',
				], 9999, 4 );

				add_action( 'save_post_groups', [
					$this,
					'buddyboss_theme_refresh_ld_group_enrolled_users_total',
				], 9999, 1 );

				add_action( 'ld_removed_group_access', [
					$this,
					'buddyboss_theme_refresh_ld_group_access_users_total',
				], 9999, 2 );

				add_action( 'ld_added_group_access', [
					$this,
					'buddyboss_theme_refresh_ld_group_access_users_total',
				], 9999, 2 );

				add_action( 'wp_ajax_buddyboss_lms_get_course_participants', [
					$this,
					'buddyboss_lms_get_course_participants',
				] );
				add_action( 'wp_ajax_nopriv_buddyboss_lms_get_course_participants', [
					$this,
					'buddyboss_lms_get_course_participants',
				] );
			}

			add_action( 'wp_ajax_buddyboss_lms_save_view', [ $this, 'buddyboss_lms_save_view' ] );
			add_action( 'wp_ajax_nopriv_buddyboss_lms_save_view', [ $this, 'buddyboss_lms_save_view' ] );

			// Hook into the user enrolled course or remove from course
			add_action( 'learndash_update_course_access', [ $this, 'bb_flush_ld_mycourse_ids_cache_user_id' ], 9999, 1 );
			add_action( 'save_post_groups' , [ $this, 'bb_flush_ld_mycourse_ids_cache_group' ], 9999, 1 );
			add_action( 'ld_added_group_access', [ $this, 'bb_flush_ld_mycourse_ids_cache_user_id' ], 9999, 1 );
			add_action( 'ld_removed_group_access', [ $this, 'bb_flush_ld_mycourse_ids_cache_user_id' ], 9999, 1 );

			// Hook for open course type.
			add_action( 'pre_post_update', [ $this, 'bb_flush_ld_mycourse_ids_update_course_helper' ], 9999, 1 );
			add_action( 'save_post_sfwd-courses', [ $this, 'bb_flush_ld_mycourse_ids_save_course_helper' ], 9999, 3 );

			add_action( 'learndash_update_user_activity', [ $this, 'bb_flush_ld_courses_progress_cache' ], 9999, 1 );
			add_action( 'update_option', [ $this, 'bb_onupdate_learndash_settings_admin_user' ], 9999, 1 );


			add_action( 'learndash-content-tabs-after', array( $this, 'buddyboss_lms_support_page_break_block' ), 20 );

			// Filter the tabs for Learndash Elementor addon.
			add_filter( 'learndash_elementor_use_content_tabs', array( $this, 'buddyboss_learndash_elementor_tabs' ), 10, 1 );
		}

		public function bb_learndash_30_custom_colors() {

			$colors = apply_filters(
				'bb_learndash_30_custom_colors',
				[
					'primary'   => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_primary' ),
					'secondary' => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_secondary' ),
					'tertiary'  => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_tertiary' ),
				]
			);

			ob_start();
			?>

            <style id="bb_learndash_30_custom_colors">

                <?php
				if ( ( isset( $colors['primary'] ) ) && ( ! empty( $colors['primary'] ) ) && ( LD_30_COLOR_PRIMARY != $colors['primary'] ) ) { ?>

                .learndash-wrapper .bb-single-course-sidebar .ld-status.ld-primary-background {
                    background-color: #e2e7ed !important;
                    color: inherit !important;
                }

                .learndash-wrapper .ld-course-status .ld-status.ld-status-progress.ld-primary-background {
                    background-color: #ebe9e6 !important;
                    color: inherit !important;
                }

                .learndash-wrapper .learndash_content_wrap .wpProQuiz_content .wpProQuiz_button_reShowQuestion:hover {
                    background-color: #fff !important;
                }

                .learndash-wrapper .learndash_content_wrap .wpProQuiz_content .wpProQuiz_toplistTable th {
                    background-color: transparent !important;
                }

                .learndash-wrapper .wpProQuiz_content .wpProQuiz_button:not(.wpProQuiz_button_reShowQuestion):not(.wpProQuiz_button_restartQuiz) {
                    color: #fff !important;
                }

                .learndash-wrapper .wpProQuiz_content .wpProQuiz_button.wpProQuiz_button_restartQuiz {
                    color: #fff !important;
                }

                .wpProQuiz_content .wpProQuiz_results > div > .wpProQuiz_button,
                .learndash-wrapper .bb-learndash-content-wrap .ld-item-list .ld-item-list-item a.ld-item-name:hover,
                .learndash-wrapper .bb-learndash-content-wrap .ld-item-list .ld-item-list-item .ld-item-list-item-preview:hover a.ld-item-name .ld-item-title,
                .learndash-wrapper .bb-learndash-content-wrap .ld-item-list .ld-item-list-item .ld-item-list-item-preview:hover .ld-expand-button .ld-icon-arrow-down,
                .lms-topic-sidebar-wrapper .lms-lessions-list > ol li a.bb-lesson-head:hover,
                .learndash-wrapper .bb-learndash-content-wrap .ld-primary-color-hover:hover,
                .learndash-wrapper .learndash_content_wrap .ld-table-list-item-quiz .ld-primary-color-hover:hover .ld-item-title,
                .learndash-wrapper .ld-item-list-item-expanded .ld-table-list-items .ld-table-list-item .ld-table-list-item-quiz .ld-primary-color-hover:hover .ld-item-title,
                .learndash-wrapper .ld-table-list .ld-table-list-items div.ld-table-list-item a.ld-table-list-item-preview:hover .ld-topic-title,
                .lms-lesson-content .bb-type-list li a:hover,
                .lms-lesson-content .lms-quiz-list li a:hover,
                .learndash-wrapper .ld-expand-button.ld-button-alternate:hover .ld-icon-arrow-down,
                .learndash-wrapper .ld-table-list .ld-table-list-items div.ld-table-list-item a.ld-table-list-item-preview:hover .ld-topic-title:before,
                .bb-lessons-list .lms-toggle-lesson i:hover,
                .lms-topic-sidebar-wrapper .lms-course-quizzes-list > ul li a:hover,
                .lms-topic-sidebar-wrapper .lms-course-members-list .course-members-list a:hover,
                .lms-topic-sidebar-wrapper .lms-course-members-list .bb-course-member-wrap > .list-members-extra,
                .lms-topic-sidebar-wrapper .lms-course-members-list .bb-course-member-wrap > .list-members-extra:hover,
                .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-list-item-preview .ld-item-name .ld-item-title .ld-item-components span,
                .bb-about-instructor h5 a:hover,
                .learndash_content_wrap .comment-respond .comment-author:hover,
                .single-sfwd-courses .comment-respond .comment-author:hover {
                    color: <?php echo $colors['primary']; ?> !important;
                }

                .learndash-wrapper .learndash_content_wrap #quiz_continue_link,
                .learndash-wrapper .learndash_content_wrap .learndash_mark_complete_button,
                .learndash-wrapper .learndash_content_wrap #learndash_mark_complete_button,
                .learndash-wrapper .learndash_content_wrap .ld-status-complete,
                .learndash-wrapper .learndash_content_wrap .ld-alert-success .ld-button,
                .learndash-wrapper .learndash_content_wrap .ld-alert-success .ld-alert-icon,
                .wpProQuiz_questionList[data-type="assessment_answer"] .wpProQuiz_questionListItem label.is-selected:before,
                .wpProQuiz_questionList[data-type="single"] .wpProQuiz_questionListItem label.is-selected:before,
                .wpProQuiz_questionList[data-type="multiple"] .wpProQuiz_questionListItem label.is-selected:before {
                    background-color: <?php echo $colors['primary']; ?> !important;
                }

                .wpProQuiz_content .wpProQuiz_results > div > .wpProQuiz_button,
                .wpProQuiz_questionList[data-type="multiple"] .wpProQuiz_questionListItem label.is-selected:before {
                    border-color: <?php echo $colors['primary']; ?> !important;
                }

                .learndash-wrapper .wpProQuiz_content .wpProQuiz_button.wpProQuiz_button_restartQuiz,
                .learndash-wrapper .wpProQuiz_content .wpProQuiz_button.wpProQuiz_button_restartQuiz:hover,
                #learndash-page-content .sfwd-course-nav .learndash_next_prev_link a:hover,
                .bb-cover-list-item .ld-primary-background {
                    background-color: <?php echo $colors['primary']; ?> !important;
                }

                <?php } ?>

                <?php
				if ( ( isset( $colors['secondary'] ) ) && ( ! empty( $colors['secondary'] ) ) && ( LD_30_COLOR_SECONDARY != $colors['secondary'] ) ) { ?>

                .lms-topic-sidebar-wrapper .ld-secondary-background,
                .i-progress.i-progress-completed,
                .bb-cover-list-item .ld-secondary-background,
                .learndash-wrapper .ld-status-icon.ld-status-complete.ld-secondary-background,
                .learndash-wrapper .ld-status-icon.ld-quiz-complete,
                .ld-progress-bar .ld-progress-bar-percentage.ld-secondary-background {
                    background-color: <?php echo $colors['secondary']; ?> !important;
                }

                .bb-progress .bb-progress-circle {
                    border-color: <?php echo $colors['secondary']; ?> !important;
                }

                .learndash-wrapper .ld-alert-success {
                    border-color: #DCDFE3 !important;
                }

                .learndash-wrapper .ld-secondary-in-progress-icon {
                    color: <?php echo $colors['secondary']; ?> !important;
                }

                .learndash-wrapper .bb-learndash-content-wrap .ld-secondary-in-progress-icon {
                    border-left-color: #DEDFE2 !important;
                    border-top-color: #DEDFE2 !important;
                }

                <?php } ?>

                <?php
				if ( ( isset( $colors['tertiary'] ) ) && ( ! empty( $colors['tertiary'] ) ) && ( LD_30_COLOR_SECONDARY != $colors['tertiary'] ) ) { ?>

                .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-name .ld-item-title .ld-item-components span.ld-status-waiting,
                .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-name .ld-item-title .ld-item-components span.ld-status-waiting span.ld-icon,
                .learndash-wrapper .ld-status-waiting {
                    background-color: <?php echo $colors['tertiary']; ?> !important;
                }

                <?php } ?>

            </style>

			<?php
			$custom_css = ob_get_clean();
			echo $custom_css;

		}

		public function buddyboss_lms_save_view() {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'list-grid-settings') ) {
				wp_send_json_error( array(
					'message' => __( 'Invalid request.', 'buddyboss-theme' ),
				) );
			}

			$object = bb_theme_filter_input_string( INPUT_POST, 'object' );
			if ( empty( $object ) || 'ld-course' !== $object ) {
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

		public function buddyboss_lms_get_course_participants() {

			check_ajax_referer( 'buddyboss_lms_get_courses' );

			$course_id   = isset( $_GET['course'] ) ? (int) $_GET['course'] : 0;
			$total_users = isset( $_GET['total'] ) ? (int) $_GET['total'] : 0;
			$page        = isset( $_GET['page'] ) ? (int) $_GET['page'] : 1;

			// how many users to show per page
			$users_per_page = apply_filters( 'buddyboss_lms_get_course_participants_per_page', 5 );

			// calculate the total number of pages.
			$total_pages = 1;
			$offset      = $users_per_page * ( $page - 1 );
			$total_pages = ceil( $total_users / $users_per_page );

			$members_arr = learndash_get_users_for_course( $course_id,
				[ 'number' => $users_per_page, 'offset' => $offset ],
				false );

			$show_more = 'false';
			if ( $page < $total_pages ) {
				$show_more = 'true';
			}

			$page = $page + 1;
			$html = '';

			ob_start();

			if ( ( $members_arr instanceof \WP_User_Query ) && ( property_exists( $members_arr, 'results' ) ) && ( ! empty( $members_arr->results ) ) ) {
				$course_members = $members_arr->get_results();

				foreach ( $course_members as $k => $course_member ) {
					?>
                    <li>
						<?php if ( class_exists( 'BuddyPress' ) ) { ?>
                        <a href="<?php echo bp_core_get_user_domain( (int) $course_member ); ?>">
							<?php } ?>
                            <img class="round" src="<?php echo get_avatar_url( (int) $course_member,
								[ 'size' => 96 ] ); ?>" alt=""/>
							<?php if ( class_exists( 'BuddyPress' ) ) { ?>
                                <span><?php echo bp_core_get_user_displayname( (int) $course_member ); ?></span>
							<?php } else { ?><?php $course_member = get_userdata( (int) $course_member ); ?>
                                <span><?php echo $course_member->display_name; ?></span>
							<?php } ?>
							<?php if ( class_exists( 'BuddyPress' ) ) { ?>
                        </a>
					<?php } ?>
                    </li>
					<?php
				}
			}

			$html = ob_get_contents();
			ob_end_clean();

			wp_send_json_success( [
				'html'      => $html,
				'show_more' => $show_more,
				'page'      => $page,
			] );

			die();
		}

		/**
		 * Add filter for [ld_course_list] shortcode
		 *
		 */
		public function add_grid_list_filter_on_shortcode( $content, $atts, $filter ) {

			$html = '';
			if ( isset( $_POST ) && isset( $_POST['action'] ) && 'ld_course_list_shortcode_pager' === $_POST['action'] ) {
				return $content;
			}
			if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'learndash-course-grid/learndash_course_grid.php' ) ) {

				$view = bb_theme_get_directory_layout_preference( 'ld-course' );

				$html    .= '<div class="bb-courses-directory">
							<div id="bb-course-list-grid-filters" class="bb-secondary-list-tabs flex align-items-center">
								<div class="grid-filters push-right" data-view="ld-course">
								    <a href="#" class="layout-view layout-grid-view ' . ( ! empty( $view ) && $view === 'grid' ? 'active' : '' ) . ' bp-tooltip" data-view="grid" data-bp-tooltip-pos="up"
								       data-bp-tooltip="' . esc_html__( 'Grid View', 'buddyboss-theme' ) . '">
								        <i class="bb-icon-l bb-icon-grid-large" aria-hidden="true"></i>
								    </a>

								    <a href="#" class="layout-view layout-list-view ' . ( ! empty( $view ) && $view === 'list' ? 'active' : '' ) . ' bp-tooltip" data-view="list" data-bp-tooltip-pos="up"
								       data-bp-tooltip=" ' . esc_html__( 'List View', 'buddyboss-theme' ) . '">
								        <i class="bb-icon-l bb-icon-bars" aria-hidden="true"></i>
								    </a>
								</div>
							</div>
						</div>';
				$content = $html . $content;
			}

			return $content;

		}

		/**
		 * Convert Social Learner Video Metabox to LD Video Progression.
		 *
		 */
		public function boss_theme_convert_social_learner_video_to_ld_video_progression() {
			if ( is_singular( [ 'sfwd-lessons', 'sfwd-topic' ] ) ) {
				global $post;
				$value            = get_post_meta( $post->ID, '_boss_edu_post_video', true );
				$is_video_migrate = get_post_meta( $post->ID, 'is_boss_edu_post_video_migrate', true );
				$lesson_settings  = learndash_get_setting( $post->ID );
				if ( $value && false === $is_video_migrate ) {
					if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( 'on' === $lesson_settings['lesson_video_enabled'] ) ) {
					} else {
						learndash_update_setting( $post->ID, 'lesson_video_enabled', 'on' );
						learndash_update_setting( $post->ID, 'lesson_video_url', $value );
						learndash_update_setting( $post->ID, 'lesson_video_shown', 'BEFORE' );
						learndash_update_setting( $post->ID, 'lesson_video_auto_start', 'on' );
						learndash_update_setting( $post->ID, 'lesson_video_show_controls', 'on' );
						update_post_meta( $post->ID, 'is_boss_edu_post_video_migrate', true );
					}
				}
			}

			if ( is_singular( [ 'sfwd-courses' ] ) ) {
				global $post;
				$value            = get_post_meta( $post->ID, '_boss_edu_post_video', true );
				$is_video_migrate = get_post_meta( $post->ID, 'is_boss_edu_post_video_migrate', true );
				if ( $value && false === $is_video_migrate ) {
					update_post_meta( $post->ID, '_buddyboss_lms_course_video', $value );
					update_post_meta( $post->ID, 'is_boss_edu_post_video_migrate', true );
				}
			}
		}

		public function ld_30_template_override() {
			remove_filter( 'learndash_template', 'learndash_30_template_routes', 1000, 5 );
			add_filter( 'learndash_template', [ $this, 'ld_30_template_routes' ], 1000, 5 );
		}

		public function set_active() {
			$this->_is_active = true;
		}

		public function is_active() {
			return $this->_is_active;
		}

		public function body_class( $classes ) {
			if ( isset( $_COOKIE['bbtheme'] ) && 'dark' == $_COOKIE['bbtheme'] && function_exists( 'buddyboss_is_learndash_inner' ) && buddyboss_is_learndash_inner() && is_user_logged_in() ) {
				$classes[] = 'bb-dark-theme';
			}

			return $classes;
		}

		public function toggle_theme_color() {
			$cookie_name  = "bbtheme";
			$cookie_value = ! empty( $_POST['color'] ) ? $_POST['color'] : '';
			setcookie( $cookie_name, $cookie_value, time() + ( 86400 * 30 ), "/" ); // 86400 = 1 day
			die();
		}

		public function learndash_get_lesson_progress( $lesson_id, $course_id = false ) {
			$user_id = get_current_user_id();

			if ( ! $course_id ) {
				$course_id = learndash_get_course_id( $lesson_id );
			}

			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			$topics  = learndash_get_topic_list( $lesson_id ) ?: [];
			$quizzes = learndash_get_lesson_quiz_list( $lesson_id ) ?: [];

			$total     = sizeof( $topics ) + sizeof( $quizzes );
			$completed = 0;

			if ( ! empty( $course_progress[ $course_id ]['lessons'][ $lesson_id ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson_id ] ) {
				$completed += 1;
			}

			foreach ( $topics as $topic ) {
				if ( ( ! empty( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic->ID ] ) ) {
					$completed ++;
				}
			}

			foreach ( $quizzes as $quiz ) {
				if ( learndash_is_quiz_complete( $user_id, $quiz['post']->ID, $course_id ) ) {
					$completed ++;
				}
			}

			$percentage = 0;
			if ( $total != 0 ) {
				$percentage = intVal( $completed * 100 / $total );
				$percentage = ( $percentage > 100 ) ? 100 : $percentage;
			} elseif ( $total == 0 && ! empty( $course_progress[ $course_id ]['lessons'][ $lesson_id ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson_id ] ) {
				$percentage = 100;
			}

			return [
				'total'      => $total,
				'completed'  => $completed,
				'percentage' => $percentage,
			];
		}

		public function get_icon_by_file_extension( $file_ext ) {

			// Get a list of allowed mime types.
			$mimes = get_allowed_mime_types();

			// Loop through and find the file extension icon.
			foreach ( $mimes as $type => $mime ) {
				if ( false !== strpos( $type, $file_ext ) ) {
					return wp_mime_type_icon( $mime );
				}
			}
		}

		public function add_meta_boxes() {

			add_meta_box( 'postexcerpt', __( 'Course Short Description', 'buddyboss-theme' ), [
				$this,
				'course_short_description_output',
			], 'sfwd-courses', 'normal', 'high' );

			add_meta_box( 'post_price_box', __( 'Course Video Preview', 'buddyboss-theme' ), [
				$this,
				'course_price_box_output',
			], 'sfwd-courses', 'normal', 'low' );
		}

		public function course_cover_photo() {
			if ( class_exists( '\BuddyBossTheme\BuddyBossMultiPostThumbnails' ) ) {
				new \BuddyBossTheme\BuddyBossMultiPostThumbnails(
					[
						'label'     => __( 'Cover Photo', 'buddyboss-theme' ),
						'id'        => 'course-cover-image',
						'post_type' => 'sfwd-courses',
					]
				);
			}
		}

		/**
		 * Outputs the content of cover photo meta box
		 */
		public function course_cover_media_output( $post ) {
			wp_nonce_field( 'buddyboss_lms_course_cover_photo_box', 'buddyboss_lms_course_cover_photo_box_nonce' );
			$course_cover_photo_meta = get_post_meta( $post->ID );
			?>
            <div class="editor-post-featured-image"><?php
				if ( isset ( $course_cover_photo_meta['course-cover-image'] ) ) { ?>
                    <img src="<?php echo $course_cover_photo_meta['course-cover-image'][0]; ?>" id="preview-cover-photo"
                         alt="" style="width: 100%; max-width: 640px;" /><?php
				} ?>
            </div>
            <input type="hidden" name="course-cover-image" id="course-cover-image"
                   value="<?php if ( isset ( $course_cover_photo_meta['course-cover-image'] ) ) {
				       echo $course_cover_photo_meta['course-cover-image'][0];
			       } ?>"/>
            <p><a id="remove-cover-photo" href="#"
                  class="components-button is-link is-destructive"><?php _e( 'Remove cover photo', 'buddyboss-theme' ) ?></a>
            </p>
            <div><input type="button" id="meta-image-button"
                        class="components-button editor-post-featured-image__toggle"
                        value="<?php _e( 'Set cover photo', 'buddyboss-theme' ) ?>"/></div>
			<?php
		}

		/**
		 * Saves cover photo custom meta input
		 */
		public function course_cover_photo_save( $post_id ) {

			// Checks save status
			$is_autosave    = wp_is_post_autosave( $post_id );
			$is_revision    = wp_is_post_revision( $post_id );
			$is_valid_nonce = ( isset( $_POST['buddyboss_lms_course_cover_photo_box_nonce'] ) && wp_verify_nonce( $_POST['buddyboss_lms_course_cover_photo_box_nonce'], 'buddyboss_lms_course_cover_photo_box' ) ) ? 'true' : 'false';

			// Exits script depending on save status
			if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
				return;
			}

			// Checks for input and saves if needed
			if ( isset( $_POST['course-cover-image'] ) ) {
				update_post_meta( $post_id, 'course-cover-image', $_POST['course-cover-image'] );
			}

		}

		public function course_short_description_output( $post ) {
			$settings = [
				'textarea_name' => 'excerpt',
				'quicktags'     => [ 'buttons' => 'em,strong,link' ],
				'tinymce'       => [
					'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
					'theme_advanced_buttons2' => '',
				],
				'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
			];

			wp_editor( htmlspecialchars_decode( $post->post_excerpt, ENT_QUOTES ), 'excerpt', $settings );
		}

		public function course_price_box_output( $post ) { ?>
            <div class="sfwd sfwd_options sfwd-courses_settings">
                <div class="sfwd_input">
                    <span class="sfwd_option_label">
                        <a class="sfwd_help_text_link" style="cursor:pointer;"
                           title="<?php _e( 'Click for Help!', 'buddyboss-theme' ) ?>"
                           onclick="toggleVisibility('sfwd-courses_course_video_url_tip');">
                            <img alt="" src="<?php echo get_template_directory_uri(); ?>/assets/images/question.png"/>
        				    <label for="buddyboss_lms_course_video"
                                   class="sfwd_label buddyboss_lms_course_video_label"><?php echo __( 'Preview Video URL', 'buddyboss-theme' ); ?></label>
                        </a>
        			</span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <?php
                            // Add a nonce field so we can check for it later.
                            wp_nonce_field( 'buddyboss_lms_course_video_meta_box', 'buddyboss_lms_course_video_meta_box_nonce' );

                            /*
							 * Use get_post_meta() to retrieve an existing value
							 * from the database and use the value for the form.
							 */
                            $value = get_post_meta( $post->ID, '_buddyboss_lms_course_video', true );
                            echo '<textarea id="buddyboss_lms_course_video" name="buddyboss_lms_course_video" rows="2" style="width:100%;">' . esc_attr( $value ) . '</textarea>';
                            ?>
                        </div>
                        <div class="sfwd_help_text_div" style="display:none"
                             id="sfwd-courses_course_video_url_tip"><label
                                    class="sfwd_help_text"><?php echo __( 'Enter preview video URL for the course. The video will be added on single course price box.', 'buddyboss-theme' ); ?></label></div>
                    </span>
                    <p style="clear:left"></p>
                </div>
            </div>
			<?php
		}

		/**
		 * When the post is saved, saves our custom data.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function course_save_price_box_data( $post_id ) {

			/*
			 * We need to verify this came from our screen and with proper authorization,
			 * because the save_post action can be triggered at other times.
			 */

			// Check if our nonce is set.
			if ( ! isset( $_POST['buddyboss_lms_course_video_meta_box_nonce'] ) ) {
				return;
			}

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['buddyboss_lms_course_video_meta_box_nonce'], 'buddyboss_lms_course_video_meta_box' ) ) {
				return;
			}

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check the user's permissions.
			if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}

			} else {

				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			/* OK, it's safe for us to save the data now. */

			// Make sure that it is set.
			if ( ! isset( $_POST['buddyboss_lms_course_video'] ) ) {
				return;
			}

			// Sanitize user input.
			$data = sanitize_text_field( $_POST['buddyboss_lms_course_video'] );

			// Update the meta field in the database.
			update_post_meta( $post_id, '_buddyboss_lms_course_video', $data );
		}

		public function user_courses_atts( $atts ) {

			if ( ! empty( $_GET['orderby'] ) ) {
				$atts['orderby'] = $_GET['orderby'];
			}

			if ( ! empty( $_GET['order'] ) && is_string( $_GET['order'] ) ) {
				$atts['order'] = strtoupper( $_GET['order'] );
			}

			if ( ! empty( $_GET['orderby'] ) && 'price' == $_GET['orderby'] ) {
				$atts['orderby']  = 'meta_value_num';
				$atts['meta_key'] = 'bb_course_price';
			}

			return $atts;
		}

		public function ajax_get_courses() {
			check_ajax_referer( 'buddyboss_lms_get_courses' );

			$order_by_current = isset ( $_GET['orderby'] ) ? $_GET['orderby'] : '';

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
				$pagination_url = get_post_type_archive_link( 'sfwd-courses' );
			}

			if ( 'my-progress' === $order_by_current ) {
				$this->_my_course_progress = $this->get_courses_progress( get_current_user_id() );
			}

			add_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );

			$posts_per_page = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );

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

			$args = [
				'post_status'    => 'publish',
				'posts_per_page' => $posts_per_page,
				'post_type'      => 'sfwd-courses',
				'paged'          => isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1,
			];

			if ( current_user_can( 'manage_options' ) ) {
				$args['post_status'] = [ 'publish', 'private' ];
			}

			$args = apply_filters( THEME_HOOK_PREFIX . 'lms_ajax_get_courses_args', $args );

			$c_q = new \WP_Query( $args );

			do_action( 'the_posts', $c_q->posts, $c_q );

			$if_theme_ld30 = ( function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) ? 'learndash/ld30/template-course-item' : 'learndash/template-course-item';

			$view = bb_theme_get_directory_layout_preference( 'ld-course' );

			if ( $c_q->have_posts() ) {
				$courses_list = [ 'list-view' => [], 'grid-view' => [] ];

				while ( $c_q->have_posts() ) {
					$c_q->the_post();

					ob_start();
					get_template_part( $if_theme_ld30 );
					$courses_list['list-view'][] = ob_get_clean();

					ob_start();
					get_template_part( $if_theme_ld30 );
					$courses_list['grid-view'][] = ob_get_clean();
				}

				$html = '<ul class="bb-course-list bb-course-items bb-grid list-view ' . ( 'list' != $view ? 'hide' : '' ) . '" aria-live="assertive" aria-relevant="all">'
				        . implode( '', $courses_list['list-view'] )
				        . '</ul>'
				        . '<ul class="bb-card-list bb-course-items grid-view bb-grid ' . ( 'grid' != $view ? 'hide' : '' ) . '" aria-live="assertive" aria-relevant="all">'
				        . implode( '', $courses_list['grid-view'] )
				        . '</ul>';

				$html .= '<div class="bb-lms-pagination">';

				$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

				$html .= paginate_links( [
					'base'               => trailingslashit( $pagination_url ) . 'page/%#%/',
					'format'             => '?paged=%#%',
					'current'            => ( isset( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1 ),
					'total'              => $c_q->max_num_pages,
					'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
				] );

				$html .= "</div><!-- .bb-lms-pagination -->";
			} else {
				$html = '<aside class="bp-feedback bp-template-notice ld-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>';
				$html .= __( 'Sorry, no courses were found.', 'buddyboss-theme' );
				$html .= '</p></aside>';
			}

			wp_reset_postdata();

			$total = $c_q->found_posts;
			wp_send_json_success( [
				'html'   => $html,
				'count'  => $total,
				'scopes' => $this->get_course_query_scope( $c_q->query_vars ),
				'layout' => $view,
			] );
			die();
		}

		public function filter_query_ajax_get_courses( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_get_courses' ], 999 );
			$query = $this->_course_archive_query_params( $query );
		}

		/**
		 * Prefetch user's course progress, if required.
		 * We can't do that on the fly as it involves its own wp_query and hence it'll mess up the global wp query
		 * leading to unexpected results.
		 *
		 * @param type $query
		 *
		 * @return type
		 */
		public function prepare_course_archive_page_query( $query ) {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$order_by_current = isset ( $_GET['orderby'] ) ? $_GET['orderby'] : '';

			if ( 'my-progress' === $order_by_current ) {
				if ( $query->is_post_type_archive && 'sfwd-courses' == $query->query_vars['post_type'] ) {
					$this->_my_course_progress = $this->get_courses_progress( get_current_user_id() );
				}
			}
		}

		public function course_archive_page_query( $query ) {

			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			if ( ! is_post_type_archive( 'sfwd-courses' ) ) {
				return;
			}

			remove_action( 'pre_get_posts', [ $this, 'course_archive_page_query' ], 999 );

			$query = $this->_course_archive_query_params( $query );
		}

		protected function _course_archive_query_params( $query ) {
			// search
			if ( ! empty( $_GET['search'] ) ) {
				$query->set( 's', sanitize_text_field( wp_unslash( $_GET['search'] ) ) );
			}

			// my courses
			$query = $this->_archive_only_my_courses( $query );

			// ordering
			$query = $this->_set_archive_orderby( $query );

			// categories
			$query = $this->_archive_filterby_tax( $query );

			// instructors
			$query = $this->_archive_filterby_instructors( $query );

			return apply_filters( 'BuddyBossTheme/Learndash/Archive/Filterby_Instructors', $query );
		}

		protected function get_course_query_scope( $query_vars ) {
			$return = [
				'all'        => 0,
				'my-courses' => 0,
			];

			$return['all'] = $this->get_all_courses_count();

			if ( is_user_logged_in() ) {
				$return['my-courses'] = $this->get_my_courses_count();
			}

			return $return;
		}

		public function filter_query_ajax_do_all_courses_counts( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_do_all_courses_counts' ], 9999 );
			$query->set( 'posts_per_page', 1 );
			$query->set( 'paged', 1 );
			$query->set( 'fields', 'ids' );
			$query->set( 'post__in', [] );
		}

		public function filter_query_ajax_do_personal_courses_counts( $query ) {
			remove_action( 'pre_get_posts', [ $this, 'filter_query_ajax_do_personal_courses_counts' ], 9999 );
			$query->set( 'posts_per_page', 1 );
			$query->set( 'paged', 1 );
			$query->set( 'fields', 'ids' );

			// fake it
			$_temp_GET    = $_GET;
			$_GET['type'] = 'my-courses';
			$this->_archive_only_my_courses( $query );
			$_GET = $_temp_GET;
		}

		public function get_more_courses( $cats = [], $tags = [], $exclude = [] ) {
			$args      = [
				'post_type' => 'sfwd-courses',
			];
			$tax_query = [
				'relation' => 'OR',
			];
			if ( ! empty( $cats ) ) {
				$tax_query[] = [
					'taxonomy'         => 'ld_course_category',
					'terms'            => $cats,
					'field'            => 'term_id',
					'include_children' => true,
				];
			}
			if ( ! empty( $tags ) ) {
				$tax_query[] = [
					'taxonomy'         => 'ld_course_tag',
					'terms'            => $tags,
					'field'            => 'term_id',
					'include_children' => true,
				];
			}
			$args['tax_query'] = $tax_query;
			if ( ! empty( $exclude ) ) {
				$args['post__not_in'] = $exclude;
			}
			$post_query = new \WP_Query( $args );

			return $post_query;
		}

		public function remove_course_title() {
			return '';
		}

		/**
		 * Get the total number of courses available.
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 */
		public function get_all_courses_count() {

			$args = [
				'post_type'   => 'sfwd-courses',
				'post_status' => 'publish',
			];

			if ( current_user_can( 'manage_options' ) ) {
				$args['post_status'] = [ 'publish', 'private' ];
			}

			$tax_query = $this->bb_learndash_get_learndash_taxonomy();
			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query;
			}

			if ( ! empty( $_GET["filter-instructors"] ) && 'all' != $_GET['filter-instructors'] ) {
				$author_by = $this->bb_learndash_get_filterby_instructors();
				if ( ! empty( $author_by ) ) {
					$args['author'] = $author_by;
				}
			}

			$courses = new \WP_Query( $args );

			return ! empty( $courses->found_posts ) ? $courses->found_posts : 0;
		}

		/**
		 * Get the number of courses a given user has access to.
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 */
		public function get_my_courses_count( $user_id = false, $tax_query = array() ) {
			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			if ( empty( $user_id ) ) {
				return 0;
			}

			$course_args = array();
			if ( ! empty( $tax_query ) ) {
				$course_args['tax_query'] = $tax_query;
			} else {
				$tax_query = $this->bb_learndash_get_learndash_taxonomy();
				if ( ! empty( $tax_query ) ) {
					$course_args['tax_query'] = $tax_query;
				}
			}

			if ( ! empty( $_GET["filter-instructors"] ) && 'all' != $_GET['filter-instructors'] ) {
				$author_by = $this->bb_learndash_get_filterby_instructors();
				if ( ! empty( $author_by ) ) {
					$course_args['author'] = $author_by;
				}
			}

			/**
			 * Fetch course id direct from LD insted of cache
			 * Removed below code
			 * if ( ! $course_ids = wp_cache_get ( $user_id, 'ld_mycourse_ids' ) ) {
			 *    $course_ids = ld_get_mycourses ( $user_id, array() );
			 *    wp_cache_set( $user_id, $course_ids, 'ld_mycourse_ids' );
			 *    }
			 */

			$course_ids = ld_get_mycourses( $user_id, $course_args );

			return empty( $course_ids ) ? 0 : count( $course_ids );
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
				$args['selected'] = isset ( $_GET['filter-categories'] ) && ! empty ( $_GET['filter-categories'] ) ? $_GET['filter-categories'] : '';
			}

			$all_cate_val = 'all';

			$archive_category_taxonomy = buddyboss_theme_get_option( 'learndash_course_index_categories_filter_taxonomy' );
			if ( empty( $archive_category_taxonomy ) ) {
				$archive_category_taxonomy = 'ld_course_category';
			}

			$categories = get_terms(
				array(
					'taxonomy' => $archive_category_taxonomy,
					'orderby'  => $args['orderby'],
					'order'    => $args['order'],
					'include'  => $args['include'],
				)
			);

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

		public function get_course_category( $category_taxonomy = '' ) {

			$archive_category_taxonomy = buddyboss_theme_get_option( 'learndash_course_index_categories_filter_taxonomy' );
			if ( empty( $archive_category_taxonomy ) ) {
				$archive_category_taxonomy = 'ld_course_category';
			}

			if ( ! empty( $category_taxonomy ) ) {
				$archive_category_taxonomy = $category_taxonomy;
            }

			$categories = get_terms( [
				'taxonomy' => $archive_category_taxonomy,
			] );

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				return $categories;
			} else {
				return '';
			}

		}

		protected function _archive_filterby_tax( $query ) {
			$tax_query = $query->get( 'tax_query' );

			if ( empty( $tax_query ) ) {
				$tax_query = [];
			}

			$tax_query = $this->bb_learndash_get_learndash_taxonomy( $tax_query );

			if ( ! empty( $tax_query ) ) {
				$query->set('tax_query' , $tax_query );
            }

			return $query;
		}

		/**
		 * Print the options for instructors dropdown.
		 *
		 * @param array|string $args       {
		 *                                 Array of parameters. All items are optional.
		 *
		 * @type string|array $selected    Selected items
		 * @type string $option_all        Text to display for 'all' option
		 * }
		 * @since BuddyBossTheme 1.0.0
		 *
		 */
		public function print_instructors_options( $args = '' ) {
			$defaults = [
				'selected'   => false,
				'option_all' => __( 'All Instructors', 'buddyboss-theme' ),
			];

			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['selected'] ) ) {
				$args['selected'] = isset ( $_GET['filter-instructors'] ) && ! empty ( $_GET['filter-instructors'] ) ? $_GET['filter-instructors'] : '';
			}

			echo "<option value='all'>{$args['option_all']}</option>";

			global $wpdb;
			$author_ids = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' AND post_status = 'publish'" );

			// if db error out, we stop
			if ( is_wp_error( $author_ids ) ) {
				return;
			}

			$author_ids = apply_filters( THEME_HOOK_PREFIX . 'learndash_instructors_options', $author_ids, $args );

			if ( ! empty( $author_ids ) ) {
				$authors = [];
				foreach ( $author_ids as $author_id ) {
					$authors[ $author_id ] = get_the_author_meta( 'display_name', $author_id );
				}

				//sort
				asort( $authors );

				foreach ( $authors as $uid => $name ) {
					printf( "<option value='%s' %s>%s</option>", $uid, selected( $args['selected'], $uid, false ), $name );
				}
			}
		}

		protected function _archive_filterby_instructors( $query ) {
			if ( ! empty( $_GET["filter-instructors"] ) && 'all' != $_GET['filter-instructors'] ) {
				$authors = $this->bb_learndash_get_filterby_instructors();
				$query->set( 'author', $authors );
			} elseif ( isset( $query->query_vars['author__in'] ) ) {
				// unset author if it was set by instructor plugin.
				unset( $query->query_vars['author__in'] );
			}

			return $query;
		}

		protected function _archive_only_my_courses( $query ) {
			if ( ! isset( $_GET['type'] ) || 'my-courses' != $_GET['type'] ) {
				return $query;
			}

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();

				if ( ! $course_ids = wp_cache_get( $user_id, 'ld_mycourse_ids' ) ) {
					// Filter to remove author__in argument which is set by instructor role plugin.
					add_filter( 'ir_filter_instructor_query', array( $this, 'remove_author_set_by_instructor' ), 9999 );

					if ( class_exists( 'LDLMS_Transients' ) ) {

						// Remove Learndash Transient/Object cache.
						\LDLMS_Transients::delete( 'learndash_open_courses' );
						\LDLMS_Transients::delete( 'learndash_user_courses_' . absint( $user_id ) );
					}
					$course_ids = ld_get_mycourses( $user_id, [] );
					wp_cache_set( $user_id, $course_ids, 'ld_mycourse_ids' );
					remove_filter( 'ir_filter_instructor_query', array( $this, 'remove_author_set_by_instructor' ), 9999 );
				}

				if ( empty( $course_ids ) ) {
					$course_ids = [ - 1 ];//an unlikely post id, to ensure that query doesn't return any courses.
				}

				$query->set( 'post__in', $course_ids );
			}

			return $query;
		}

		/**
		 * Remove author argument which is set by instructor role plugin
		 *
		 * @param  mixed $query The WP_Query instance.
		 *
		 * @return mixed
		 */
		public function remove_author_set_by_instructor( $query ) {

			if ( isset( $query->query_vars['author__in'] ) ) {
				unset( $query->query_vars['author__in'] );
			}

			return $query;
		}

		protected function _get_orderby_options() {
			$order_by_options = [
				'alphabetical' => __( 'Alphabetical', 'buddyboss-theme' ),
				'recent'       => __( 'Newly Created', 'buddyboss-theme' ),
			];

			if ( is_user_logged_in() ) {
				$order_by_options['my-progress'] = __( 'My Progress', 'buddyboss-theme' );
			}

			return apply_filters( 'BuddyBossTheme/Learndash/Archive/OrderByOptions', $order_by_options );
		}

		/**
		 * Print the options for sorting/orderby dropdown.
		 *
		 * @param array|string $args     {
		 *                               Array of parameters. All items are optional.
		 *
		 * @type string|array $selected  Selected items
		 * }
		 *
		 * @return int
		 * @since BuddyBossTheme 1.0.0
		 *
		 */
		public function print_sorting_options( $args = '' ) {
			$defaults = [
				'selected' => false,
			];

			$args = wp_parse_args( $args, $defaults );

			$order_by_options = $this->_get_orderby_options();

			if ( empty( $args['selected'] ) ) {
				$default = apply_filters( 'BuddyBossTheme/Learndash/Archive/DefaultOrderBy', 'alphabetical' );
				if ( ! isset( $order_by_options[ $default ] ) ) {
					foreach ( $order_by_options as $k => $v ) {
						$default = $k;//first one
						break;
					}
				}

				$order_by_current = isset ( $_GET['orderby'] ) ? $_GET['orderby'] : $default;
				$order_by_current = isset( $order_by_options[ $order_by_current ] ) ? $order_by_current : $default;
				$args['selected'] = $order_by_current;
			}

			foreach ( $order_by_options as $opt => $label ) {
				printf( "<option value='%s' %s>%s</option>", $opt, selected( $args['selected'], $opt, false ), $label );
			}
		}

		protected function _set_archive_orderby( $query ) {
			$order_by_options = $this->_get_orderby_options();
			$default = apply_filters( 'BuddyBossTheme/Learndash/Archive/DefaultOrderBy', 'alphabetical' );
			if ( ! isset( $order_by_options[ $default ] ) ) {
				foreach ( $order_by_options as $k => $v ) {
					$default = $k;//first one
					break;
				}
			}

			$order_by_current = isset( $_GET['orderby'] ) ? $_GET['orderby'] : $default;
			switch ( $order_by_current ) {
				case 'alphabetical':
					$query_order_by = 'title';
					$query_order    = 'asc';
					break;
				case 'my-progress':
					$query_order_by = 'date';
					$query_order    = 'desc';//doesn't matter

					add_filter( 'posts_clauses', [ $this, 'alter_query_parts' ], 10, 2 );
					break;
				default:
					$query_order_by = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'date';
					$query_order    = isset( $_GET['order'] ) ? $_GET['order'] : 'desc';
					break;
			}

            $query->set( 'orderby', $query_order_by );
			$query->set( 'order', $query_order );

			return $query;
		}

		public function get_courses_progress( $user_id, $sort_order = 'desc' ) {
			if ( ! $course_completion_percentage = wp_cache_get( $user_id, 'ld_courses_progress' ) ) {
				$course_completion_percentage = ! empty( $course_completion_percentage ) ? $course_completion_percentage : array();

				$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

				if ( ! empty( $course_progress ) && is_array( $course_progress ) ) {

					foreach ( $course_progress as $course_id => $coursep ) {
						// We take default progress value as 1 % rather than 0%
						$course_completion_percentage[ $course_id ] = 1;//

						if ( $coursep['total'] == 0 ) {
							continue;
						}

						$course_steps_count     = learndash_get_course_steps_count( $course_id );
						$course_steps_completed = learndash_course_get_completed_steps( $user_id, $course_id, $coursep );

						$completed_on = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
						if ( ! empty( $completed_on ) ) {

							$coursep['completed'] = $course_steps_count;
							$coursep['total']     = $course_steps_count;

						} else {
							$coursep['total']     = $course_steps_count;
							$coursep['completed'] = $course_steps_completed;

							if ( $coursep['completed'] > $coursep['total'] ) {
								$coursep['completed'] = $coursep['total'];
							}
						}

						// cannot divide by 0
						if ( $coursep['total'] == 0 ) {
							$course_completion_percentage[ $course_id ] = 0;
						} else {
							$course_completion_percentage[ $course_id ] = ceil( ( $coursep['completed'] * 100 ) / $coursep['total'] );
						}
					}
				}

				//Avoid running the queries multiple times if user's course progress is empty
				$course_completion_percentage = ! empty( $course_completion_percentage ) ? $course_completion_percentage : 'empty';

				wp_cache_set( $user_id, $course_completion_percentage, 'ld_courses_progress' );
			}

			$course_completion_percentage = 'empty' !== $course_completion_percentage ? $course_completion_percentage : [];

			if ( ! empty( $course_completion_percentage ) ) {
				// Sort.
				if ( 'asc' == $sort_order ) {
					asort( $course_completion_percentage );
				} else {
					arsort( $course_completion_percentage );
				}
			}

			return $course_completion_percentage;
		}

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

		public function js_l10n( $data ) {
			$data['learndash'] = [
				'nonce_get_courses'  => wp_create_nonce( 'buddyboss_lms_get_courses' ),
				'course_archive_url' => trailingslashit( get_post_type_archive_link( 'sfwd-courses' ) ),
			];

			return $data;
		}


		public function ld_get_progress_course_percentage( $user_id, $course_id ) {

			if ( empty( $user_id ) ) {
				// $current_user = wp_get_current_user();
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
				} else {
					$user_id = 0;
				}
			}

			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id();
			}

			if ( empty( $course_id ) ) {
				return '';
			}

			$completed = 0;
			$total     = false;

			if ( ! empty( $user_id ) ) {

				$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

				$percentage = 0;
				$message    = '';

				if ( ( ! empty( $course_progress ) ) && ( isset( $course_progress[ $course_id ] ) ) && ( ! empty( $course_progress[ $course_id ] ) ) ) {
					if ( isset( $course_progress[ $course_id ]['completed'] ) ) {
						$completed = absint( $course_progress[ $course_id ]['completed'] );
					}

					if ( isset( $course_progress[ $course_id ]['total'] ) ) {
						$total = absint( $course_progress[ $course_id ]['total'] );
					}
				} else {
					$total = 0;
				}
			}

			// If $total is still false we calculate the total from course steps.
			if ( false === $total ) {
				$total = learndash_get_course_steps_count( $course_id );
			}

			if ( $total > 0 ) {
				$percentage = intval( $completed * 100 / $total );
				$percentage = ( $percentage > 100 ) ? 100 : $percentage;
			} else {
				$percentage = 0;
			}

			return $percentage;

		}


		public function boss_theme_course_resume( $course_id ) {

			if ( is_user_logged_in() ) {
				if ( ! empty( $course_id ) ) {
					$user           = wp_get_current_user();
					$step_course_id = $course_id;
					$course         = get_post( $step_course_id );

					$lession_list = learndash_get_course_lessons_list( $course_id, $user->ID, array( 'num' => - 1 ) );
					$lession_list = array_column( $lession_list, 'post' );
					$url          = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_continue_url_arr( $course_id, $lession_list );

					if ( isset( $course ) && 'sfwd-courses' === $course->post_type ) {
						//$last_know_step = get_user_meta( $user->ID, 'learndash_last_known_course_' . $step_course_id, true );
						$last_know_step = '';

						// User has not hit a LD module yet
						if ( empty( $last_know_step ) ) {

							if ( isset( $url ) && '' !== $url ) {
								return $url;
							} else {
								return '';
							}
						}

						//$step_course_id = 0;
						// Sanity Check
						if ( absint( $last_know_step ) ) {
							$step_id = $last_know_step;
						} else {
							if ( isset( $url ) && '' !== $url ) {
								return $url;
							} else {
								return '';
							}
						}

						$last_know_post_object = get_post( $step_id );

						// Make sure the post exists and that the user hit a page that was a post
						// if $last_know_page_id returns '' then get post will return current pages post object
						// so we need to make sure first that the $last_know_page_id is returning something and
						// that the something is a valid post
						if ( null !== $last_know_post_object ) {

							$post_type        = $last_know_post_object->post_type; // getting post_type of last page.
							$label            = get_post_type_object( $post_type ); // getting Labels of the post type.
							$title            = $last_know_post_object->post_title;
							$resume_link_text = __( 'RESUME', 'buddyboss-theme' );

							if ( function_exists( 'learndash_get_step_permalink' ) ) {
								$permalink = learndash_get_step_permalink( $step_id, $step_course_id );
							} else {
								$permalink = get_permalink( $step_id );
							}

							return $permalink;
						}
					}
				}
			} else {
				$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
				if ( $course_price_type == 'open' ) {

					$lession_list = learndash_get_course_lessons_list( $course_id );
					$lession_list = array_column( $lession_list, 'post' );
					$url          = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_continue_url_arr( $course_id, $lession_list );

					return $url;
				}
			}

			return '';
		}

		public function boss_theme_find_last_known_learndash_page() {
			$user = wp_get_current_user();

			if ( is_user_logged_in() ) {

				/* declare $post as global so we get the post->ID of the current page / post */
				global $post;

				// Sanity check page doesn't exist
				if ( ! is_object( $post ) ) {
					return;
				}

				/* Limit the plugin to LearnDash specific post types */
				$learn_dash_post_types = apply_filters(
					' boss_theme_find_last_known_learndash_post_types',
					[
						'sfwd-courses',
						'sfwd-lessons',
						'sfwd-topic',
						'sfwd-quiz',
						'sfwd-certificates',
						'sfwd-assignment',
					]
				);

				$step_id        = $post->ID;
				$step_course_id = learndash_get_course_id( $step_id );

				if ( empty( $step_course_id ) ) {
					$step_course_id = 0;
				}

				if ( is_singular( $learn_dash_post_types ) ) {
					update_user_meta( $user->ID, 'learndash_last_known_page', $step_id . ',' . $step_course_id );
					if ( 'sfwd-courses' !== $post->post_type ) {
						update_user_meta( $user->ID, 'learndash_last_known_course_' . $step_course_id, $step_id );
					}
				}

			}
		}

		/**
		 * Remove the <div class="ld-video"> div from the content if video enabled.
		 *
		 * @param $content
		 * @param $post
		 *
		 * @return string|string[]|null
		 */
		public function buddyboss_theme_ld_remove_video_from_content( $content, $post ) {

			$lesson_settings = learndash_get_setting( $post );

			if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( $lesson_settings['lesson_video_enabled'] == 'on' ) ) {
				if ( ( isset( $lesson_settings['lesson_video_url'] ) ) && ( ! empty( $lesson_settings['lesson_video_url'] ) ) ) {
					$content = preg_replace( '#<div class="ld-video" (.*?)>(.*?)</div>#', '', $content );
				}
			}

			return $content;
		}

		/**
		 * Get all the URLs of current course ( lesson, topic, quiz )
		 *
		 * @param        $course_id
		 * @param        $lession_list
		 * @param string $course_quizzes_list
		 *
		 * @return array
		 */
		public function buddyboss_theme_ld_custom_pagination( $course_id, $lession_list, $course_quizzes_list = '' ) {
			global $post;

			$navigation_urls = [];
			if ( ! empty( $lession_list ) ) :

				foreach ( $lession_list as $lesson ) {

					$lesson_topics = learndash_get_topic_list( $lesson->ID );

					$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson->ID ) ) );

					if ( ! empty( $lesson_topics ) ) :
						foreach ( $lesson_topics as $lesson_topic ) {
							$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson_topic->ID ) ) );

							$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

							if ( ! empty( $topic_quizzes ) ) :
								foreach ( $topic_quizzes as $topic_quiz ) {
									$navigation_urls[] = urldecode( trailingslashit( get_permalink( $topic_quiz['post']->ID ) ) );
								}
							endif;

						}
					endif;

					$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

					if ( ! empty( $lesson_quizzes ) ) :
						foreach ( $lesson_quizzes as $lesson_quiz ) {
							$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson_quiz['post']->ID ) ) );
						}
					endif;
				}

			endif;

			$course_quizzes = learndash_get_course_quiz_list( $course_id );
			if ( ! empty( $course_quizzes ) ) :
				foreach ( $course_quizzes as $course_quiz ) {
					$navigation_urls[] = urldecode( trailingslashit( get_permalink( $course_quiz['post']->ID ) ) );
				}
			endif;


			return $navigation_urls;
		}

		/**
		 * Get all the URLs of current course ( lesson, topic, quiz )
		 *
		 * @param        $course_id
		 * @param        $lession_list
		 * @param string $course_quizzes_list
		 *
		 * @return array | string
		 */
		public function buddyboss_theme_ld_custom_continue_url_arr( $course_id, $lession_list, $course_quizzes_list = '' ) {
			global $post;

			$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
			if ( $course_price_type == 'closed' ) {
				$courses_progress = buddyboss_theme()->learndash_helper()->get_courses_progress( get_current_user_id() );
				$user_courses     = learndash_user_get_enrolled_courses( get_current_user_id() );
				$course_progress  = isset( $courses_progress[ $course_id ] ) ? $courses_progress[ $course_id ] : null;
				if ( $course_progress <= 0 && ! in_array( $course_id, $user_courses ) ) {
					return get_the_permalink( $course_id );
				}
			}

			$navigation_urls = [];
			if ( ! empty( $lession_list ) ) :

				foreach ( $lession_list as $lesson ) {

					$lesson_topics = learndash_get_topic_list( $lesson->ID );

					$course_progress = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
					$completed       = ! empty( $course_progress[ $course_id ]['lessons'][ $lesson->ID ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson->ID ];

					$navigation_urls[] = [
						'url'      => learndash_get_step_permalink( $lesson->ID, $course_id ),
						'complete' => $completed ? 'yes' : 'no',
					];

					if ( ! empty( $lesson_topics ) ) :
						foreach ( $lesson_topics as $lesson_topic ) {

							$completed = ! empty( $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ];

							$navigation_urls[] = [
								'url'      => learndash_get_step_permalink( $lesson_topic->ID, $course_id ),
								'complete' => $completed ? 'yes' : 'no',
							];

							$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

							if ( ! empty( $topic_quizzes ) ) :
								foreach ( $topic_quizzes as $topic_quiz ) {
									$navigation_urls[] = [
										'url'      => learndash_get_step_permalink( $topic_quiz['post']->ID, $course_id ),
										'complete' => learndash_is_quiz_complete( get_current_user_id(), $topic_quiz['post']->ID, $course_id ) ? 'yes' : 'no',
									];
								}
							endif;

						}
					endif;

					$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

					if ( ! empty( $lesson_quizzes ) ) :
						foreach ( $lesson_quizzes as $lesson_quiz ) {
							$navigation_urls[] = [
								'url'      => learndash_get_step_permalink( $lesson_quiz['post']->ID, $course_id ),
								'complete' => learndash_is_quiz_complete( get_current_user_id(), $lesson_quiz['post']->ID, $course_id ) ? 'yes' : 'no',
							];
						}
					endif;
				}

			endif;

			$course_quizzes = learndash_get_course_quiz_list( $course_id );
			if ( ! empty( $course_quizzes ) ) :
				foreach ( $course_quizzes as $course_quiz ) {
					$navigation_urls[] = [
						'url'      => learndash_get_step_permalink( $course_quiz['post']->ID, $course_id ),
						'complete' => learndash_is_quiz_complete( get_current_user_id(), $course_quiz['post']->ID, $course_id ) ? 'yes' : 'no',
					];
				}
			endif;

			$key = array_search( 'no', array_column( $navigation_urls, 'complete' ) );
			if ( '' !== $key && isset( $navigation_urls[ $key ] ) ) {
				return $navigation_urls[ $key ]['url'];
			}

			return '';
		}

		/**
		 * return the next and previous URL based on the course current URL.
		 *
		 * @param array $url_arr
		 * @param string $current_url
		 *
		 * @return array|string
		 */
		public function buddyboss_theme_custom_next_prev_url( $url_arr = [], $current_url = '' ) {

			if ( empty( $url_arr ) ) {
				return;
			}

			// Protocol
			$url = ( is_ssl() ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

			// Get current URL
			$current_url = trailingslashit( $url );
			if ( ! $query = parse_url( $current_url, PHP_URL_QUERY ) ) {
				$current_url = trailingslashit( $current_url );
			}

			$key = array_search( urldecode( $current_url ), $url_arr );


			$url = [];

			$next = current( array_slice( $url_arr, array_search( $key, array_keys( $url_arr ) ) + 1, 1 ) );
			$prev = current( array_slice( $url_arr, array_search( $key, array_keys( $url_arr ) ) - 1, 1 ) );

			$last_element = array_values( array_slice( $url_arr, - 1 ) )[0];

			$url['next'] = ( isset( $next ) && $last_element != $current_url ) ? '<a href="' . $next . '" class="next-link" rel="next">' . esc_html__( 'Next', 'buddyboss-theme' ) . '<span class="meta-nav" data-balloon-pos="up" data-balloon="' . esc_attr__( 'Next', 'buddyboss-theme' ) . '">&rarr;</span></a>' : '';
			$url['prev'] = ( isset( $prev ) && $last_element != $prev ) ? '<a href="' . $prev . '" class="prev-link" rel="prev"><span class="meta-nav" data-balloon-pos="up" data-balloon="' . esc_attr__( 'Previous', 'buddyboss-theme' ) . '">&larr;</span> ' . esc_html__( 'Previous', 'buddyboss-theme' ) . '</a>' : '';


			return $url;
		}

		/**
		 * Get all the URLs of current course ( quiz )
		 *
		 * @param        $course_id
		 * @param        $lession_list
		 * @param string $course_quizzes_list
		 *
		 * @return array
		 */
		public function buddyboss_theme_ld_custom_quiz_count( $course_id, $lession_list, $course_quizzes_list = '' ) {
			global $post;

			$quiz_urls = [];
			if ( ! empty( $lession_list ) ) :

				foreach ( $lession_list as $lesson ) {

					$lesson_topics = learndash_get_topic_list( $lesson->ID );

					if ( ! empty( $lesson_topics ) ) :
						foreach ( $lesson_topics as $lesson_topic ) {

							$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

							if ( ! empty( $topic_quizzes ) ) :
								foreach ( $topic_quizzes as $topic_quiz ) {
									$quiz_urls[] = get_permalink( $topic_quiz['post']->ID );
								}
							endif;

						}
					endif;

					$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

					if ( ! empty( $lesson_quizzes ) ) :
						foreach ( $lesson_quizzes as $lesson_quiz ) {
							$quiz_urls[] = get_permalink( $lesson_quiz['post']->ID );
						}
					endif;
				}

			endif;

			$course_quizzes = learndash_get_course_quiz_list( $course_id );
			if ( ! empty( $course_quizzes ) ) :
				foreach ( $course_quizzes as $course_quiz ) {
					$quiz_urls[] = get_permalink( $course_quiz['post']->ID );
				}
			endif;


			return $quiz_urls;
		}

		/**
		 * Return the current quiz no.
		 *
		 * @param array $url_arr
		 * @param string $current_url
		 *
		 * @return false|int|string
		 */
		public function buddyboss_theme_ld_custom_quiz_key( $url_arr = [], $current_url = '' ) {

			// Protocol
			$url = ( is_ssl() ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

			// Get current URL
			$current_url = trailingslashit( $url );

			$key = array_search( $current_url, $url_arr );

			return $key + 1;
		}

		/**
		 * Remove the Take course button if price not added.
		 *
		 * @param $join_button
		 * @param $payment_params
		 *
		 * @return string
		 */
		public function buddyboss_theme_ld_payment_buttons( $join_button, $payment_params ) {
			if (
				'0' === $payment_params['price']
				&& (
					isset( $payment_params['course_price_type'] )
					&& $payment_params['course_price_type'] != 'free'
				)
			) {
				return '';
			}

			return $join_button;
		}

		public function buddyboss_theme_video_progression_video( $object, $content, $post, $lesson_settings ) {
			//remove_filter( 'learndash_content', array( $this, 'buddyboss_theme_ld_remove_video_from_content',999 ) );
			$content = $object->add_video_to_content( $content, $post, $lesson_settings );

			//add_filter( 'learndash_content', array( $this, 'buddyboss_theme_ld_remove_video_from_content' ), 999, 2 );
			return $content;
		}

		public function ld_30_template_routes( $filepath, $name, $args, $echo, $return_file_path ) {

			$LD_30_TEMPLATE_DIR        = get_stylesheet_directory() . '/learndash/ld30/';
			$LD_30_TEMPLATE_DIR_PARENT = get_template_directory() . '/learndash/ld30/';
			$over_ride_template        = '';

			$routes = apply_filters( 'learndash_30_template_routes', [
				'core'       => [
					'course',
					'lesson',
					'topic',
					'quiz',
				],
				'shortcodes' => [
					'profile',
					'ld_course_list',
					'course_list_template',
					'ld_topic_list',
					'user_groups_shortcode',
					'course_content_shortcode',
				],
				'widgets'    => [
					'course_navigation_widget' => 'course-navigation',
					'course_progress_widget'   => 'course-progress',
				],
				'messages'   => [
					'learndash_course_prerequisites_message' => 'prerequisites',
					'learndash_course_points_access_message' => 'course-points',
					'learndash_course_lesson_not_available'  => 'lesson-not-available',
				],
				'modules'    => [
					'learndash_lesson_video' => 'lesson-video',
				],
			] );

			if ( in_array( $name, $routes['core'] ) ) {
				$over_ride_template = $LD_30_TEMPLATE_DIR . $name . '.php';
				if ( file_exists( $over_ride_template ) ) {
					return $over_ride_template;
				} else {
					$over_ride_template = $LD_30_TEMPLATE_DIR_PARENT . $name . '.php';
					if ( file_exists( $over_ride_template ) ) {
						return $over_ride_template;
					} else {
						return LD_30_TEMPLATE_DIR . $name . '.php';
					}
				}
			}

			if ( in_array( $name, $routes['shortcodes'] ) ) {
				$over_ride_template = $LD_30_TEMPLATE_DIR . 'shortcodes/' . $name . '.php';
				if ( file_exists( $over_ride_template ) ) {
					return $over_ride_template;
				} else {
					$over_ride_template = $LD_30_TEMPLATE_DIR_PARENT . 'shortcodes/' . $name . '.php';
					if ( file_exists( $over_ride_template ) ) {
						return $over_ride_template;
					} else {
						return LD_30_TEMPLATE_DIR . 'shortcodes/' . $name . '.php';
					}
				}
			}

			foreach ( $routes['modules'] as $slug => $path ) {

				if ( $name !== $slug ) {
					continue;
				}

				$over_ride_template = $LD_30_TEMPLATE_DIR . 'modules/' . $path . '.php';
				if ( file_exists( $over_ride_template ) ) {
					return $over_ride_template;
				} else {
					$over_ride_template = $LD_30_TEMPLATE_DIR_PARENT . 'modules/' . $path . '.php';
					if ( file_exists( $over_ride_template ) ) {
						return $over_ride_template;
					} else {
						return LD_30_TEMPLATE_DIR . 'modules/' . $path . '.php';
					}
				}
			}

			foreach ( $routes['widgets'] as $slug => $path ) {

				if ( $name !== $slug ) {
					continue;
				}

				$over_ride_template = $LD_30_TEMPLATE_DIR . 'widgets/' . $path . '.php';
				if ( file_exists( $over_ride_template ) ) {
					return $over_ride_template;
				} else {
					$over_ride_template = $LD_30_TEMPLATE_DIR_PARENT . 'widgets/' . $path . '.php';
					if ( file_exists( $over_ride_template ) ) {
						return $over_ride_template;
					} else {
						return LD_30_TEMPLATE_DIR . 'widgets/' . $path . '.php';
					}
				}
			}

			foreach ( $routes['messages'] as $slug => $path ) {

				if ( $name !== $slug ) {
					continue;
				}

				$over_ride_template = $LD_30_TEMPLATE_DIR . 'modules/messages/' . $path . '.php';
				if ( file_exists( $over_ride_template ) ) {
					return $over_ride_template;
				} else {
					$over_ride_template = $LD_30_TEMPLATE_DIR_PARENT . 'modules/messages/' . $path . '.php';
					if ( file_exists( $over_ride_template ) ) {
						return $over_ride_template;
					} else {
						return LD_30_TEMPLATE_DIR . 'modules/messages/' . $path . '.php';
					}

				}
			}

			return $filepath;
		}

		public function ld_30_get_template_part( $filepath, $slug ) {
			$LD_30_TEMPLATE_DIR        = get_stylesheet_directory() . '/learndash/ld30/';
			$LD_30_TEMPLATE_DIR_PARENT = get_template_directory() . '/learndash/ld30/';

			if ( $slug == 'modules/infobar/course' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'modules/infobar/course.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'modules/infobar/course.php';
				}
			}

			if ( $slug == 'template-banner' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'template-banner.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'template-banner.php';
				}
			}

			if ( $slug == 'template-single-course-sidebar' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'template-single-course-sidebar.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'template-single-course-sidebar.php';
				}
			}

			if ( $slug == 'modules/infobar' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'modules/infobar.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'modules/infobar.php';
				}
			}

			if ( $slug == 'modules/progress' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'modules/progress.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'modules/progress.php';
				}
			}

			if ( $slug == 'modules/course-steps' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'modules/course-steps.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'modules/course-steps.php';
				}
			}

			if ( $slug == 'template-course-author-details' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'template-course-author-details.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'template-course-author-details.php';
				}
			}

			if ( $slug == 'lesson/partials/row' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'lesson/partials/row.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'lesson/partials/row.php';
				}
			}

			if ( $slug == 'quiz/partials/row' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'quiz/partials/row.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'quiz/partials/row.php';
				}
			}

			if ( $slug == 'assignment/partials/row' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'assignment/partials/row.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'assignment/partials/row.php';
				}
			}

			if ( $slug == 'shortcodes/profile/assignment-row' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'shortcodes/profile/assignment-row.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'shortcodes/profile/assignment-row.php';
				}
			}

			if ( $slug == 'shortcodes/profile/course-row' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'shortcodes/profile/course-row.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'shortcodes/profile/course-row.php';
				}
			}

			if ( $slug == 'shortcodes/course_list_template' ) {
				$filepath = $LD_30_TEMPLATE_DIR . 'shortcodes/course_list_template.php';
				if ( ! file_exists( $filepath ) ) {
					$filepath = $LD_30_TEMPLATE_DIR_PARENT . 'shortcodes/course_list_template.php';
				}
			}

			return $filepath;
		}

		/**
		 * Filter for set always No to focus_mode_enabled.
		 *
		 * @param $old_value
		 * @param $value
		 */
		public function ld_30_focus_mode_set_disable( $old_value, $value ) {

			if ( isset( $value['focus_mode_enabled'] ) && '' !== $value['focus_mode_enabled'] ) {
				unset( $value['focus_mode_enabled'] );
			}
			update_option( 'learndash_settings_theme_ld30', $value );

		}

		/**
		 * Set the default template for the lessons, topic, assignment and quiz single pages.
		 *
		 * @param $template
		 *
		 * @return string
		 */
		public function ld_30_focus_mode_template( $template ) {

			$focus_mode = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

			$post_types = [
				'sfwd-lessons',
				'sfwd-topic',
				'sfwd-assignment',
				'sfwd-quiz',
			];

			if ( in_array( get_post_type(), $post_types ) ) {
				if ( 'yes' === $focus_mode && ! is_search() ) {
					$template = get_single_template();

					return $template;
				}
			}

			return $template;

		}

		/**
		 * Add custom class if focus mode enabled.
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		public function ld_30_custom_body_classes( $classes ) {

			$focus_mode = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

			$post_types = [
				'sfwd-lessons',
				'sfwd-topic',
				'sfwd-quiz',
				'sfwd-assignment',
			];

			if ( 'yes' === $focus_mode && in_array( get_post_type(), $post_types ) ) {
				$classes[] = 'bb-custom-ld-focus-mode-enabled';
			}

			if ( function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
				$classes[] = 'learndash-theme';
			}

			return $classes;

		}

		public function ld_30_get_course_id( $id ) {

			global $wpdb;

			$sql_str   = $wpdb->prepare( "SELECT meta_value as post_id FROM " . $wpdb->postmeta . " WHERE meta_key LIKE %s AND post_id = %d", '%ld_course_%', $id );
			$course_id = $wpdb->get_col( $sql_str );
			$course_id = (int) isset( $course_id[0] ) ? $course_id[0] : 0;

			return $course_id;

		}

		public function buddyboss_theme_refresh_ld_course_enrolled_users_total( $user_id, $course_id, $course_access_list, $remove ) {

			$this->buddyboss_theme_ld_course_enrolled_users_list( $course_id, $force_refresh = true );
		}

		/**
		 * Update user course enrolled count.
		 *
		 * @param int $group_id Post ID of the group.
		 *
		 * @since 1.7.3
		 */
		public function buddyboss_theme_refresh_ld_group_enrolled_users_total( $group_id ) {

			$group_course_ids = learndash_group_enrolled_courses( $group_id );
			if ( ! empty( $group_course_ids ) ) {

				foreach ( $group_course_ids as $course_id ) {

					$this->buddyboss_theme_ld_course_enrolled_users_list( $course_id, $force_refresh = true );

					$this->buddyboss_theme_ld_course_enrolled_users_list( $course_id, true );
				}
			}
		}

		/**
		 * Update user course enrolled count.
		 *
		 * @param int $user_id  User ID.
		 * @param int $group_id Post ID of the group.
		 *
		 * @since 1.7.3
		 */
		public function buddyboss_theme_refresh_ld_group_access_users_total( $user_id, $group_id ) {

			$group_course_ids = learndash_group_enrolled_courses( $group_id );
			if ( ! empty( $group_course_ids ) ) {

				// Clear LD cache for updated user list.
				$transient_key = 'learndash_group_users_' . $group_id;
				delete_transient( $transient_key );

				foreach ( $group_course_ids as $course_id ) {
					$this->buddyboss_theme_ld_course_enrolled_users_list( $course_id, true );
				}
			}
		}

		public function buddyboss_theme_ld_course_enrolled_users_list( $course_id, $force_refresh = false ) {

			$course_enrolled_users_list = get_transient( 'buddyboss_theme_ld_course_enrolled_users_count_' . $course_id );

			// If nothing is found, build the object.
			if ( true === $force_refresh || false === $course_enrolled_users_list ) {

				$members_arr = learndash_get_users_for_course( $course_id, [], false );

				if ( ( $members_arr instanceof \WP_User_Query ) && ( property_exists( $members_arr, 'results' ) ) && ( ! empty( $members_arr->results ) ) ) {
					$course_enrolled_users_list = $members_arr->get_results();
				} else {
					$course_enrolled_users_list = [];
				}

				$course_enrolled_users_list = count( $course_enrolled_users_list );

				set_transient( 'buddyboss_theme_ld_course_enrolled_users_count_' . $course_id, $course_enrolled_users_list );

			}

			return (int) $course_enrolled_users_list;
		}


		/**
		 * Gets courses where was any actions last days
		 *
		 * @param int $limit
		 *
		 * @return array
		 */
		public function last_courses_actions( $limit = 5 ) {
			global $wpdb;

			$activity_table = \LDLMS_DB::get_table_name( 'user_activity' );

			return $wpdb->get_results( $wpdb->prepare( "SELECT ua.* FROM {$activity_table} ua INNER JOIN {$wpdb->posts} p ON ua.course_id = p.ID WHERE ua.user_id = %d AND ua.activity_type = %s AND ua.activity_completed = %d AND p.post_status = 'publish' ORDER BY ua.activity_updated DESC LIMIT %d", get_current_user_id(), 'course', 0, $limit ) );
		}

		/**
		 * Get first lesson to take next
		 *
		 * @param $course
		 *
		 * @return false|mixed
		 */
		public function active_lesson( $course ) {

			if (
				function_exists( 'learndash_get_course_steps' ) &&
				function_exists( 'learndash_user_progress_is_step_complete' )
			) {

				// Get all course steps.
				$course_steps = learndash_get_course_steps( $course, array( 'sfwd-lessons' ) );
				foreach( $course_steps as $step_id ) {
					if ( ! learndash_user_progress_is_step_complete( get_current_user_id(), $course, $step_id ) ) {
						return $step_id;
					}
				}
			}

			return false;
		}

		/**
		 * Reset object cache for accessible learndash course accessible by user.
		 *
		 * @since 2.3.40
		 *
		 * @param int $user_id User Id.
		 */
		public function bb_flush_ld_mycourse_ids_cache_user_id( $user_id ) {
			// Remove the cached course IDs.
			wp_cache_delete( $user_id, 'ld_mycourse_ids' );
		}

		/**
		 * Reset object cache for ld_mycourse_ids group.
		 *
		 * @since 2.3.40
		 *
		 * @param int $group_id Learndash group id.
		 */
		public function bb_flush_ld_mycourse_ids_cache_group( $group_id ) {
			if ( ! empty( $_POST ) && isset( $_POST['learndash_group_users'] ) ) {
				if (
					function_exists( 'wp_cache_flush_group' ) &&
					function_exists( 'wp_cache_supports' ) &&
					wp_cache_supports( 'flush_group' )
				) {
					wp_cache_flush_group( 'ld_mycourse_ids' );
				} elseif ( isset( $_POST['learndash_group_users'][ $group_id ] ) ) {
					$group_user_ids = (array) json_decode( stripslashes( $_POST['learndash_group_users'][ $group_id ] ) );
					foreach ( $group_user_ids as $user_id ) {
						wp_cache_delete( absint( $user_id ), 'ld_mycourse_ids' );
					}
				}
			}
		}

		/**
		 * Reset object cache for ld_courses_progress.
		 *
		 * @since 2.3.40
		 *
		 * @param array $args Details of the learndash activity.
		 */
		public function bb_flush_ld_courses_progress_cache( $args ) {
			if ( ! empty( $args['user_id'] ) ) {
				wp_cache_delete( absint( $args['user_id'] ), 'ld_courses_progress' );
			}
		}

		/**
		 * Helper function to reset object cache for ld_mycourse_ids on course update.
		 *
		 * @since 2.3.40
		 *
		 * @param int $post_id Post id.
		 */
		public function bb_flush_ld_mycourse_ids_update_course_helper( $post_id ) {
			$post_type = get_post_type( $post_id );
			if ( 'sfwd-courses' === $post_type && ! empty( $_POST ) ) {
				$course_price_type     = isset( $_POST['learndash-course-access-settings']['course_price_type'] ) ? sanitize_text_field( $_POST['learndash-course-access-settings']['course_price_type'] ) : '';
				$old_course_price_type = learndash_get_course_price( $post_id );
				$old_course_price_type = isset( $old_course_price_type['type'] ) ? $old_course_price_type['type'] : '';

				// Check if open price type before update or if the price type is updated.
				if (
					! empty( $old_course_price_type ) &&
					$course_price_type !== $old_course_price_type &&
					( 'open' === $course_price_type || 'open' === $old_course_price_type )
				) {

					// Change in the course type.
					$this->bb_flush_ld_mycourse_ids_cache_for_all_users();
				}
			}
		}

		/**
		 * Helper function to reset object cache for ld_mycourse_ids on new open course.
		 *
		 * @since 2.3.40
		 *
		 * @param int     $post_id    Post ID.
		 * @param WP_Post $saved_post WP Post object.
		 * @param bool    $update     Whether this is an existing post.
		 */
		public function bb_flush_ld_mycourse_ids_save_course_helper( $post_id, $saved_post, $update = null ) {
			if (
				! $update &&
				! empty( $_POST ) &&
				isset( $_POST['learndash-course-access-settings'] )
			) {
				$course_price_type = isset( $_POST['learndash-course-access-settings']['course_price_type'] ) ? sanitize_text_field( $_POST['learndash-course-access-settings']['course_price_type'] ) : '';
				if ( 'open' === $course_price_type ) {
					$this->bb_flush_ld_mycourse_ids_cache_for_all_users();
				}
			}
		}

		/**
		 * Reset object cache for ld_mycourse_ids for all users on course save or update.
		 *
		 * @since 2.3.40
		 */
		public function bb_flush_ld_mycourse_ids_cache_for_all_users() {

			// Flush cache for the open courses.
			if (
				function_exists( 'wp_cache_flush_group' ) &&
				function_exists( 'wp_cache_supports' ) &&
				wp_cache_supports( 'flush_group' )
			) {
				wp_cache_flush_group( 'ld_mycourse_ids' );
			} else {

				// Get wp users.
				$paged          = 1;
				$users_per_page = 1000;

				$args = array(
					'fields' => 'ids',
					'number' => $users_per_page,
					'paged'  => $paged,
				);

				$users_query = new \WP_User_Query( $args );
				$user_ids    = $users_query->get_results();

				while ( ! empty( $user_ids ) ) {
					foreach ( $user_ids as $user_id ) {
						wp_cache_delete( absint( $user_id ), 'ld_mycourse_ids' );
					}
					$paged++;
					$args['paged'] = $paged;

					$users_query = new \WP_User_Query( $args );
					$user_ids    = $users_query->get_results();
				}
			}
		}

		/**
		 * Reset object cache for ld_mycourse_ids for update in autoenrollment settings for admin.
		 *
		 * @since 2.3.40
		 *
		 * @param string $option_name Option name( i.e - learndash_settings_admin_user ).
		 */
		public function bb_onupdate_learndash_settings_admin_user( $option_name ) {
			if ( 'learndash_settings_admin_user' === $option_name ) {
				$this->bb_flush_ld_mycourse_ids_cache_for_all_users();
			}
		}

		/**
		 * Add pagination to support page break block.
		 *
		 * @since BuddyBoss 2.3.50
		 *
		 * @return void
		 */
		public function buddyboss_lms_support_page_break_block() {
			wp_link_pages(
				array(
					'before' => '<div class="page-links bp-pagination-links">' . __( 'Pages:', 'buddyboss-theme' ),
					'after'  => '</div>',
				)
			);
		}

		/**
		 * Fixes the issue of duplicate tabs on single lesson and topic pages caused by the Learndash Elementor addon.
		 *
		 * @since BuddyBoss 2.4.20
		 *
		 * @param string $step_material_select The step material selector.
		 *
		 * @return mixed
		 */
		public function buddyboss_learndash_elementor_tabs( $step_material_select ) {

			if (
				is_singular( learndash_get_post_type_slug( 'lesson' ) ) ||
				is_singular( learndash_get_post_type_slug( 'topic' ) )
			) {
				return '';
			}

			return $step_material_select;
		}

		/**
		 * Retrieves the selected instructors for filtering in LearnDash.
		 * This function checks the 'filter-instructors' query parameter in the URL
		 * and returns a string of comma-separated instructor IDs or an empty string.
		 * If no instructors are selected or if 'all' is selected, an empty string is returned.
		 *
		 * @since 2.5.40
		 *
		 * @return string Comma-separated string of selected instructor IDs,
		 *                or an empty string if no instructors are selected.
		 */
		public function bb_learndash_get_filterby_instructors() {
			$authors = $_GET["filter-instructors"];
			if ( is_array( $authors ) ) {
				$authors = implode( ',', $authors );
			}

			return $authors;
		}

		/**
		 * Retrieves the LearnDash taxonomy parameters for filtering.
		 * This function checks the 'filter-categories', 'filter-block-categories', and 'filter-block-tags'
		 * query parameters in the URL and constructs a tax_query array accordingly.
		 * The resulting tax_query is suitable for filtering LearnDash content based on categories and tags.
		 *
		 * @since 2.5.40
		 *
		 * @param array $tax_query Optional. Existing tax_query array to extend.
		 *
		 * @return array The extended tax_query array for LearnDash content filtering.
		 */
		public function bb_learndash_get_learndash_taxonomy( $tax_query = array() ) {
			// Query Depend on theme setting
			if ( ! empty( $_GET[ "filter-categories" ] ) && 'all' != $_GET['filter-categories'] ) {
				$archive_category_taxonomy = buddyboss_theme_get_option( 'learndash_course_index_categories_filter_taxonomy' );
				if ( empty( $archive_category_taxonomy ) ) {
					$archive_category_taxonomy = 'ld_course_category';
				}

				$tax_query[] = array(
					'taxonomy'         => $archive_category_taxonomy,
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

			return $tax_query;
		}
	}
}
