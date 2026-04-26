<?php
/**
 * Deprecated functions from LD 5.0.0.
 * The functions will be removed in a later version.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_30_focus_mode_can_complete' ) ) {
	/**
	 * Checks whether a post can be marked as complete or not in focus mode.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0 Use `learndash_can_complete_step()` instead. It was marked as deprecated in 4.0.3, but was not actually deprecated.
	 *
	 * @param int|WP_Post|null $post      `WP_Post` object or post ID. Default to global $post.
	 * @param int|null         $course_id Course ID.
	 *
	 * @return boolean Whether a post can be marked as complete.
	 */
	function learndash_30_focus_mode_can_complete( $post = null, $course_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_can_complete_step' );

		if ( null === $post ) {
			global $post;
		}

		if ( is_int( $post ) ) {
			$post = get_post( $post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- I suppose it's what they wanted.
		}

		if ( ! $course_id ) {
			$course_id = learndash_get_course_id( $course_id );
		}

		// Shouldn't appear regardless if this is a quiz.
		if ( get_post_type( $post ) == 'sfwd-quiz' ) {
			return false;
		}

		$complete_button = learndash_mark_complete( $post );

		// If the complete button returns empty, also just return false.
		if ( empty( $complete_button ) ) {
			return false;
		}

		// Check if has any outstanding quizzes.
		$quizzes = learndash_get_lesson_quiz_list( $post->ID, get_current_user_id(), $course_id );

		// If there is a quiz then the quiz is the mark complete.
		if ( $quizzes ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'learndash_30_responsive_videos' ) ) {
	/**
	 * Deprecated.
	 *
	 * @deprecated 5.0.0
	 *
	 * @param string $html    Html.
	 * @param string $url     Url.
	 * @param string $attr    Attr.
	 * @param int    $post_id Post ID.
	 *
	 * @return false|mixed|string
	 */
	function learndash_30_responsive_videos( $html, $url, $attr, $post_id ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		/** This filter is documented in themes/ld30/includes/helpers.php */
		$responsive_video = apply_filters( 'learndash_30_responsive_video', LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'responsive_video_enabled' ) );

		if ( ! isset( $responsive_video ) || 'yes' !== $responsive_video ) {
			return false;
		}

		if ( has_filter( 'learndash_responsive_video_post_types' ) ) {
			/**
			 * Filters Responsive video supported post types.
			 *
			 * @deprecated 5.0.0
			 *
			 * @param array $post_types Array of supported post type.
			 */
			$post_types = apply_filters_deprecated(
				'learndash_responsive_video_post_types',
				[
					[
						'sfwd-courses',
						'sfwd-lessons',
						'sfwd-topic',
						'sfwd-quiz',
						'sfwd-assignments',
					],
				],
				'5.0.0'
			);
		}

		if ( ! in_array( get_post_type( $post_id ), $post_types, true ) ) {
			return $html;
		}

		if ( has_filter( 'learndash_responsive_video_domains' ) ) {
			/**
			 * Filters responsive video domains. Used to modify the supported domains for the responsive video.
			 *
			 * @since 3.0.0
			 * @deprecated 5.0.0
			 *
			 * @param array $video_domains Array of video domains to support responsive video.
			 */
			$matches = apply_filters_deprecated(
				'learndash_responsive_video_domains',
				[
					[
						'youtube.com',
						'vimeo.com',
					],
				],
				'5.0.0'
			);
		}

		foreach ( $matches as $match ) {
			if ( strpos( $url, $match ) !== false ) {
				return '<div class="ld-resp-video">' . $html . '</div>';
			}
		}

		return $html;
	}
}

if ( ! function_exists( 'ls_propanel_set_report_filenames' ) ) {
	/**
	 * Set ProPanel Report Filename
	 *
	 * @deprecated 5.0.0
	 *
	 * @param string $filename_part The base filename to be used
	 *
	 * @return array containing two keys
	 *          'report_filename' as the server filename and path
	 *          'report_url'  as the URL to download the file
	 */
	function ls_propanel_set_report_filenames( $file_part = '' ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$files_info = array();

		$path_part = 'ld_propanel';

		if ( ! empty( $file_part ) ) {
			$wp_upload_dir            = wp_upload_dir();
			$wp_upload_dir['basedir'] = str_replace( '\\', '/', $wp_upload_dir['basedir'] );
			$wp_upload_dir['basedir'] = trailingslashit( $wp_upload_dir['basedir'] );
			$wp_upload_dir['baseurl'] = trailingslashit( $wp_upload_dir['baseurl'] );

			if ( wp_mkdir_p( $wp_upload_dir['basedir'] . $path_part ) !== false ) {
				// Just to ensure the directory is not readable
				file_put_contents( $wp_upload_dir['basedir'] . $path_part . '/index.php', '// nothing to see here' );

				$files_info['report_file'] = $wp_upload_dir['basedir'] . $path_part . '/' . $file_part;
				$files_info['report_url']  = $wp_upload_dir['baseurl'] . $path_part . '/' . $file_part;
			}
		}

		return $files_info;
	}
}

if ( ! function_exists( 'learndash_the_currency_symbol' ) ) {
	/**
	 * Outputs the LearnDash global currency symbol.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0 Use `learndash_get_currency_symbol()` instead.
	 *
	 * @return void
	 */
	function learndash_the_currency_symbol(): void {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_currency_symbol' );

		echo wp_kses_post( learndash_get_currency_symbol() );
	}
}

if ( ! function_exists( 'learndash_test_admin_icon' ) ) {
	/**
	 * Looks like it was never used.
	 *
	 * @deprecated 5.0.0
	 */
	function learndash_test_admin_icon() {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		?>
		<style type="text/css">
			#adminmenu #toplevel_page_learndash-lms div.wp-menu-image:before {
				background: url('<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . '/themes/ld30/assets/iconfont/admin-icons/browser-checkmark.svg' ); ?>') center center no-repeat;
				content: '';
				opacity: 0.7;
			}
		</style>
		<?php
	}
}

if ( ! function_exists( 'learndash_on_iis' ) ) {
	/**
	 * Checks if the server is on Microsoft IIS.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0
	 *
	 * @return boolean Returns true if the server is on Microsoft IIS otherwise false.
	 */
	function learndash_on_iis() {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$s_software = strtolower( $_SERVER['SERVER_SOFTWARE'] );
		if ( strpos( $s_software, 'microsoft-iis' ) !== false ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'learndash_get_paynow_courses' ) ) {
	/**
	 * Gets all the courses with the price type paynow.
	 *
	 * Logic for this query was taken from the `sfwd_lms_has_access_fn()` function.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0
	 *
	 * @param boolean $bypass_transient Optional. Whether to bypass the transient cache. Default false.
	 *
	 * @return array An array of course IDs.
	 */
	function learndash_get_paynow_courses( $bypass_transient = false ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_posts_by_price_type' );

		return learndash_get_posts_by_price_type( learndash_get_post_type_slug( 'course' ), 'paynow', $bypass_transient );
	}
}

if ( ! function_exists( 'learndash_get_current_tabs_set' ) ) {
	/**
	 * Get current admin tabs set.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @return array
	 */
	function learndash_get_current_tabs_set() {
		_deprecated_function( __FUNCTION__, '5.0.0', 'Learndash_Admin_Menus_Tabs::get_instance()->learndash_admin_tabs()' );

		return Learndash_Admin_Menus_Tabs::get_instance()->learndash_admin_tabs();
	}
}

if ( ! function_exists( 'learndash_get_course_url' ) ) {
	/**
	 * Gets the course permalink.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `get_permalink` instead.
	 *
	 * @param int|null $id Optional. The ID of the resource like course, topic, lesson, quiz, etc. Default null.
	 *
	 * @return string The course permalink.
	 */
	function learndash_get_course_url( $id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'get_permalink' );

		if ( empty( $id ) ) {
			$id = learndash_get_course_id();
		}

		return get_permalink( $id );
	}
}

if ( ! function_exists( 'ld_course_check_user_access' ) ) {
	/**
	 * Checks if the user has access to a course.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `Product::user_has_access` instead.
	 *
	 * @param int      $course_id Course ID.
	 * @param int|null $user_id   Optional. User ID. Default null.
	 *
	 * @return boolean Returns true if the user has access otherwise false.
	 */
	function ld_course_check_user_access( $course_id, $user_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'Product::user_has_access' );

		return sfwd_lms_has_access( $course_id, $user_id );
	}
}

if ( ! function_exists( 'learndash_get_content_label' ) ) {
	/**
	 * Gets a label for the content type by post type.
	 *
	 * Universal function for simpler template logic and reusable templates
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @param string $post_type The post type slug to check.
	 * @param array  $args      An array of arguments used to get the content label.
	 *
	 * @return string The label for the content type based on user settings
	 */
	function learndash_get_content_label( $post_type = null, $args = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		if ( $args ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Bad idea, but better keep it for now.
		}

		$post_type = ( null === $post_type ? get_post_type() : $post_type );
		$label     = '';

		switch ( $post_type ) {
			case ( 'sfwd-courses' ):
				$label = LearnDash_Custom_Label::get_label( 'course' );
				break;
			case ( 'sfwd-lessons' ):
				if ( isset( $parent ) ) {
					$label = LearnDash_Custom_Label::get_label( 'course' );
				} else {
					$label = LearnDash_Custom_Label::get_label( 'lesson' );
				}
				break;
			case ( 'sfwd-topic' ):
				if ( isset( $parent ) ) {
					$label = LearnDash_Custom_Label::get_label( 'lesson' );
				} else {
					$label = LearnDash_Custom_Label::get_label( 'topic' );
				}
				break;
		}

		if ( has_filter( 'learndash_get_content_label' ) ) {
			/**
			 * Filters label for the content type by post type. Used to override label settings set by the user.
			 *
			 * @since 3.0.0
			 * @deprecated 5.0.0
			 *
			 * @param string $label     Label for the content type.
			 * @param string $post_type Post type.
			 *
			 * @return string Label for the content type.
			 */
			$label = apply_filters_deprecated( 'learndash_get_content_label', [ $label, $post_type ], '5.0.0' );
		}

		return $label;
	}
}

if ( ! function_exists( 'learndash_get_step_post_status_label' ) ) {
	/**
	 * Get single course step post status label.
	 *
	 * @since 4.0.0
	 * @deprecated 5.0.0
	 *
	 * @param object $post WP_Post object.
	 *
	 * @return string Post status label.
	 */
	function learndash_get_step_post_status_label( $post ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

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
}

if ( ! function_exists( 'learndash_check_query_post_type' ) ) {
	/**
	 * Gets the posts count from the `WP_Query` post_type argument.
	 *
	 * @deprecated 5.0.0
	 *
	 * @param array $query_args Optional. The `WP_Query` query arguments array. Default empty array.
	 *
	 * @return int Number of posts for a post type.
	 */
	function learndash_check_query_post_type( $query_args = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$total_post_count = 0;
		if ( ( isset( $query_args['post_type'] ) ) && ( ! empty( $query_args['post_type'] ) ) ) {
			if ( is_string( $query_args['post_type'] ) ) {
				$total_post_count += learndash_get_total_post_count( $query_args['post_type'] );
			} elseif ( is_array( $query_args['post_type'] ) ) {
				foreach ( $query_args['post_type'] as $post_type ) {
					$total_post_count += learndash_get_total_post_count( $query_args['post_type'] );
				}
			}
		}

		return $total_post_count;
	}
}

if ( ! function_exists( 'learndash_activity_complete_quiz' ) ) {
	/**
	 * Set the quiz activity completed record.
	 *
	 * @since 3.5.0
	 * @deprecated 5.0.0 Use `learndash_activity_complete_step` instead.
	 *
	 * @param int $user_id       User ID.
	 * @param int $course_id     Course ID.
	 * @param int $quiz_id       Quiz ID.
	 * @param int $complete_time Activity complete timestamp (GMT).
	 *
	 * @return object Instance of LDLMS_Model_Activity or null;
	 */
	function learndash_activity_complete_quiz( $user_id = 0, $course_id = 0, $quiz_id = 0, $complete_time = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_activity_complete_step' );

		return learndash_activity_complete_step( $user_id, $course_id, $quiz_id, 'quiz', $complete_time );
	}
}

if ( ! function_exists( 'learndash_activity_complete_course' ) ) {
	/**
	 * Set the course activity completed record.
	 *
	 * @since 3.5.0
	 * @deprecated 5.0.0 Use `learndash_activity_complete_step` instead.
	 *
	 * @param int $user_id       User ID.
	 * @param int $course_id     Course ID.
	 * @param int $complete_time Activity complete timestamp (GMT).
	 *
	 * @return object Instance of LDLMS_Model_Activity or null;
	 */
	function learndash_activity_complete_course( $user_id = 0, $course_id = 0, $complete_time = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_activity_complete_step' );

		return learndash_activity_complete_step( $user_id, $course_id, $course_id, 'course', $complete_time );
	}
}

if ( ! function_exists( 'learndash_get_essays_by_quiz_attempt' ) ) {
	/**
	 * Gets the essays from a specific quiz attempt - DEPRECATED
	 *
	 * Look up all the essay responses from a particular quiz attempt
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0 It was marked as deprecated many years ago, but now it's actually deprecated.
	 *
	 * @param int|null $attempt_id Post ID.
	 * @param int|null $user_id    User ID.
	 *
	 * @return array|boolean An array of essay post IDs.
	 */
	function learndash_get_essays_by_quiz_attempt( $attempt_id = null, $user_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		// Fail gracefully.
		if ( null === $attempt_id ) {
			return false;
		}

		if ( null === $user_id ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		$quiz_attempts = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$essays        = array();

		if ( ! $quiz_attempts || empty( $quiz_attempts ) ) {
			return false;
		}

		foreach ( $quiz_attempts as $attempt ) {
			if ( $attempt['quiz'] != $attempt_id || ! isset( $attempt['graded'] ) ) {
				continue;
			}

			foreach ( $attempt['graded'] as $essay ) {
				$essays[] = $essay['post_id'];
			}
		}

		return $essays;
	}
}

if ( ! function_exists( 'learndash_get_lesson_attributes' ) ) {
	/**
	 * Gets the Lesson attributes.
	 *
	 * Populates an array of attributes about a lesson, if it's a sample or if it isn't currently available
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0 Use `learndash_get_course_step_attributes` instead.
	 *
	 * @param array $lesson Lesson details array.
	 *
	 * @return array Attributes including label, icon and class name.
	 */
	function learndash_get_lesson_attributes( $lesson = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_step_attributes' );

		$attributes = array();

		if ( ( isset( $lesson['post'] ) ) && ( is_a( $lesson['post'], 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $lesson['post']->post_type ) ) {
			$attributes = learndash_get_course_step_attributes( $lesson['post']->ID );

			if ( has_filter( 'learndash_lesson_attributes' ) ) {
				/**
				 * Filters attributes of a lesson. Used to modify details about a lesson like label, icon and class name.
				 *
				 * @since 3.0.0
				 * @deprecated 5.0.0
				 *
				 * @param array   $attributes Array of lesson attributes.
				 * @param WP_Post $lesson     The lesson post object.
				 *
				 * @return array Attributes including label, icon and class name.
				 */
				return apply_filters_deprecated( 'learndash_lesson_attributes', [ $attributes, $lesson['post'] ], '5.0.0' );
			}
		}

		return $attributes;
	}
}

if ( ! function_exists( 'learndash_get_lesson_progress' ) ) {
	/**
	 * Gets the current lesson progress.
	 *
	 * Returns stats about a user's current progress within a lesson.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @param array|null $topics An array of the topic of the lessons, contextualized for the user's progress.
	 *
	 * @return array An array of stats including percentage, completed and total
	 */
	function learndash_get_lesson_progress( $topics = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$progress = [
			'percentage' => 0,
			'completed'  => 0,
			'total'      => 0,
		];

		if ( has_filter( 'learndash_get_lesson_progress_defaults' ) ) {
			/**
			 * Filters default values for lesson progress.
			 *
			 * @since 3.0.0
			 * @deprecated 5.0.0
			 *
			 * @param array $lesson_progress_defaults Default values for lesson progress.
			 */
			$progress = apply_filters_deprecated(
				'learndash_get_lesson_progress_defaults',
				[ $progress ],
				'5.0.0'
			);
		}

		// Fail gracefully, return zero's.
		if ( null === $topics || empty( $topics ) ) {
			return $progress;
		}

		foreach ( $topics as $key => $topic ) {
			++$progress['total'];

			if ( ! empty( $topic->completed ) ) {
				++$progress['completed'];
			}
		}

		if ( 0 === ! $progress['completed'] ) {
			$progress['percentage'] = floor( $progress['completed'] / $progress['total'] * 100 );
		}

		if ( has_filter( 'learndash_get_lesson_progress' ) ) {
			/**
			 * Filters LearnDash lesson progress.
			 *
			 * @since 3.0.0
			 * @deprecated 5.0.0
			 *
			 * @param array $progress An Associative array of lesson progress with keys total, completed and percentage.
			 * @param array $topics   An array of the topics of the lessons.
			 *
			 * @return array An Associative array of lesson progress with keys total, completed and percentage.
			 */
			$progress = apply_filters_deprecated( 'learndash_get_lesson_progress', [ $progress, $topics ], '5.0.0' );
		}

		return $progress;
	}
}

if ( ! function_exists( 'learndash_get_quiz_pro_fields' ) ) {
	/**
	 * Gets the `WPProQuiz` Quiz row column fields.
	 *
	 * @since 2.6.0
	 * @since 3.3.0 Corrected function name
	 * @deprecated 5.0.0
	 *
	 * @param int          $quiz_pro_id Optional. The `WPProQuiz` Question ID. Default 0.
	 * @param string|array $fields       Optional. An array or comma delimited string of fields to return. Default null.
	 *
	 * @return array An array of `WPProQuiz` quiz field values.
	 */
	function learndash_get_quiz_pro_fields( $quiz_pro_id = 0, $fields = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$values = array();

		if ( ( ! empty( $quiz_pro_id ) ) && ( ! empty( $fields ) ) ) {
			if ( is_string( $fields ) ) {
				$fields = explode( ',', $fields );
			}
			if ( is_array( $fields ) ) {
				$fields = array_map( 'trim', $fields );
			}

			$quiz_mapper = new WpProQuiz_Model_QuizMapper();
			$quiz_pro    = $quiz_mapper->fetch( $quiz_pro_id );

			foreach ( $fields as $field ) {
				$function = 'get' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
				if ( method_exists( $quiz_pro, $function ) ) {
					$values[ $field ] = $quiz_pro->$function();
				} else {
					$values[ $field ] = null;
				}
			}

			return $values;
		}

		return $values;
	}
}

if ( ! function_exists( 'learndash_lms_reports_page' ) ) {
	/**
	 * Outputs the Reports Page.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0
	 */
	function learndash_lms_reports_page() {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		?>
			<div  id="learndash-reports"  class="wrap">
				<h1><?php esc_html_e( 'User Reports', 'learndash' ); ?></h1>
				<br>
				<div class="sfwd_settings_left">
					<div class=" " id="sfwd-learndash-reports_metabox">
						<div class="inside">
							<a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . esc_attr( wp_create_nonce( 'sfwd-nonce' ) ) . '&page_options=sfp_home_description&courses_export_submit=Export' ) ); ?>">
							<?php
							// translators: Export User Course Data Label.
							printf( esc_html_x( 'Export User %s Data', 'Export User Course Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							?>
							</a>
							<a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . esc_attr( wp_create_nonce( 'sfwd-nonce' ) ) . '&page_options=sfp_home_description&quiz_export_submit=Export' ) ); ?>">
							<?php
							printf(
							// translators: Export Quiz Data Label.
								esc_html_x( 'Export %s Data', 'Export Quiz Data Label', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							);
							?>
							</a>
							<?php
								if ( has_action( 'learndash_report_page_buttons' ) ) {
									/**
									 * Fires after report page buttons.
									 *
									 * @since 2.1.0
									 * @deprecated 5.0.0
									 */
									do_action_deprecated( 'learndash_report_page_buttons', [], '5.0.0' );
								}
								?>
						</div>
					</div>
				</div>
			</div>
		<?php
	}
}

if ( ! function_exists( 'learndash_report_user_courses_progress' ) ) {
	/**
	 * Gets the users course progress for the report.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0
	 *
	 * @param int   $user_id             Optional. User ID to get course list. Default 0.
	 * @param array $course_query_args   Optional. The query arguments to get the list of user enrolled courses. Default empty array.
	 * @param array $activity_query_args Optional. The query arguments to get the the user activities. Default empty array.
	 *
	 * @return array If course query and activity query is successful this should be a multi-dimensional array showing 'results', 'pager', 'query_args', 'query_str'
	 */
	function learndash_report_user_courses_progress( $user_id = 0, $course_query_args = array(), $activity_query_args = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$user_courses_progress_data = array();

		if ( empty( $user_id ) ) {
			if ( ! is_user_logged_in() ) {
				return $user_courses_progress_data;
			}
			$user_id = get_current_user_id();
		}

		// If the post_ids (Course ids) was not passed from the caller then we need to do that work.
		if ( ( ! isset( $activity_query_args['post_ids'] ) ) || ( empty( $activity_query_args['post_ids'] ) ) ) {
			$activity_query_args['post_ids'] = learndash_user_get_enrolled_courses( intval( $user_id ), $course_query_args );
		}

		if ( ! empty( $activity_query_args['post_ids'] ) ) {
			$activity_query_defaults = array(
				'user_ids'        => intval( $user_id ),
				'post_types'      => 'sfwd-courses',
				'activity_types'  => 'course',
				'activity_status' => '',
				'orderby_order'   => 'users.display_name, posts.post_title',
				'date_format'     => 'F j, Y H:i:s',
				'paged'           => 1,
				'per_page'        => 10,
			);

			$activity_query_args = wp_parse_args( $activity_query_args, $activity_query_defaults );

			$report_user = get_user_by( 'id', $user_id );

			$activity = learndash_reports_get_activity( $activity_query_args );
			if ( ! empty( $activity['results'] ) ) {
				$user_courses_progress_data = $activity;
			}
		}

		return $user_courses_progress_data;
	}
}

if ( ! function_exists( 'learndash_set_course_prerequisite_enabled' ) ) {
	/**
	 * Sets the status of whether the course prerequisite is enabled or disabled.
	 *
	 * @since 2.4.4
	 * @deprecated 5.0.0 Use `learndash_update_setting` instead.
	 *
	 * @param int     $course_id The ID of the course.
	 * @param boolean $enabled   Optional. The value is true to enable course prerequisites. Any other
	 *                           value will disable course prerequisites. Default true.
	 *
	 * @return boolean Returns true if the status was updated successfully otherwise false.
	 */
	function learndash_set_course_prerequisite_enabled( $course_id, $enabled = true ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_update_setting' );

		if ( true === $enabled ) {
			$enabled = 'on';
		}

		if ( 'on' !== $enabled ) {
			$enabled = '';
		}

		return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
	}
}

if ( ! function_exists( 'learndash_set_course_prerequisite' ) ) {
	/**
	 * Sets new prerequisites for a course.
	 *
	 * @since 2.4.4
	 * @deprecated 5.0.0 Use `learndash_update_setting` instead.
	 *
	 * @param int   $course_id  Optional. ID of the course. Default 0.
	 * @param array $course_pre Optional. An array of course prerequisites. Default empty array.
	 *
	 * @return boolean Returns true if update was successful otherwise false.
	 */
	function learndash_set_course_prerequisite( $course_id = 0, $course_pre = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_update_setting' );

		if ( ! empty( $course_id ) ) {
			if ( ( ! empty( $course_pre ) ) && ( is_array( $course_pre ) ) ) {
				$course_pre = array_unique( $course_pre );
			}

			$transient_key        = 'learndash_course_pre_' . $course_id;
			$course_pre_transient = LDLMS_Transients::delete( $transient_key );

			return learndash_update_setting( $course_id, 'course_prerequisite', $course_pre );
		}

		return false;
	}
}

if ( ! function_exists( 'learndash_users_can_register' ) ) {
	/**
	 * Utility function to check if users can register for the site.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 */
	function learndash_users_can_register() {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		if ( is_multisite() ) {
			$users_can_register = (bool) users_can_register_signup_filter();
		} else {
			$users_can_register = (bool) get_option( 'users_can_register' );
		}

		if ( has_filter( 'learndash_users_can_register' ) ) {
			/**
			 * Filter for users can register.
			 *
			 * @since 3.6.0
			 * @deprecated 5.0.0
			 *
			 * @param bool $users_can_register True if users can register.
			 *
			 * @return bool True if users can register.
			 */
			$users_can_register = (bool) apply_filters_deprecated( 'learndash_users_can_register', [ $users_can_register ], '5.0.0' );
		}

		return $users_can_register;
	}
}

if ( ! function_exists( 'learndash_user_course_last_step' ) ) {
	/**
	 * Gets the user's last active step for a course.
	 *
	 * @since 3.1.4
	 * @deprecated 5.0.0
	 *
	 * @param int $user_id   Optional. User ID. Default 0.
	 * @param int $course_id Optional. Course ID. Default 0.
	 *
	 * @return int The last active course step ID.
	 */
	function learndash_user_course_last_step( $user_id = 0, $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		global $wpdb;

		$last_course_step_id = 0;

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! empty( $user_id ) ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_last_active_course( $user_id );
			}
			if ( ! empty( $course_id ) ) {
				$query_result        = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT user_activity_meta.activity_meta_value FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' as user_activity INNER JOIN ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) . " as user_activity_meta ON user_activity.activity_id = user_activity_meta.activity_id WHERE user_activity.user_id=%d AND user_activity.post_id=%d AND user_activity.activity_type='course' AND user_activity_meta.activity_meta_key= 'steps_last_id' ORDER BY activity_updated DESC",
						$user_id,
						$course_id
					)
				);
				$last_course_step_id = absint( $query_result );
			}
		}

		return $last_course_step_id;
	}
}

if ( ! function_exists( 'learndash_update_posts_comment_status' ) ) {
	/**
	 * Updates the comment_status field for all the post of given post type.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @global array $learndash_question_types
	 *
	 * @param string         $post_type      Optional. The post type slug. Default empty.
	 * @param string|boolean $comment_status Optional. New comment status. Allowed values 'open' or 'closed'. Default false.
	 */
	function learndash_update_posts_comment_status( $post_type = '', $comment_status = false ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		global $learndash_question_types;

		if ( ! empty( $post_type ) ) {
			$ld_post_types = learndash_get_post_types();
			if ( in_array( $post_type, $ld_post_types, true ) ) {
				if ( in_array( $comment_status, array( 'open', 'closed' ), true ) ) {
					$update_comment_status = true;

					if ( has_filter( 'learndash_update_posts_comment_status' ) ) {
						/**
						 * Filters whether to update comment status for any post type or not.
						 *
						 * @deprecated 5.0.0
						 *
						 * @param boolean $update_comment_status Whether to Update comment status or not.
						 * @param string  $post_type             Post type slug.
						 * @param string  $comment_status        Status of comments.
						 *
						 * @return boolean Whether to update comment status or not.
						 */
						$update_comment_status = apply_filters_deprecated(
							'learndash_update_posts_comment_status',
							[ $update_comment_status, $post_type, $comment_status ],
							'5.0.0'
						);
					}

					if ( $update_comment_status ) {
						global $wpdb;
						$wpdb->query(
							$wpdb->prepare(
								'UPDATE wp_posts SET comment_status = %s WHERE post_type = %s',
								$comment_status,
								$post_type
							)
						);
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'learndash_get_exam_challenge_available_courses' ) ) {
	/**
	 * Gets the list of available courses for a Challenge Exam.
	 *
	 * This is a list of Courses not associated with a Challenge Exam.
	 *
	 * @since 4.0.0
	 * @deprecated 5.0.0
	 *
	 * @return array An array of course IDs.
	 */
	function learndash_get_exam_challenge_available_courses() {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$query_args = array(
			'post_type'      => learndash_get_post_type_slug( 'course' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => LEARNDASH_EXAM_CHALLENGE_POST_META_KEY,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$query = new WP_Query( $query_args );

		return $query->posts;
	}
}

if ( ! function_exists( 'learndash_get_user_course_attempts_time_spent' ) ) {
	/**
	 * Gets the time spent by user in the course.
	 *
	 * Total of each started/complete time set.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0
	 *
	 * @param int $user_id   Optional. The ID of the user to get course time spent. Default 0.
	 * @param int $course_id Optional. The ID of the course to get time spent. Default 0.
	 *
	 * @return int Total number of seconds spent.
	 */
	function learndash_get_user_course_attempts_time_spent( $user_id = 0, $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$total_time_spent = 0;

		$attempts = learndash_get_user_course_attempts( $user_id, $course_id );

		// We should only ever have one entry for a user+course_id. But still we are returned an array of objects.
		if ( ( ! empty( $attempts ) ) && ( is_array( $attempts ) ) ) {
			foreach ( $attempts as $attempt ) {
				if ( ! empty( $attempt->activity_completed ) ) {
					// If the Course is complete then we take the time as the completed - started times.
					$total_time_spent += ( $attempt->activity_completed - $attempt->activity_started );
				} else {
					// But if the Course is not complete we calculate the time based on the updated timestamp
					// This is updated on the course for each lesson, topic, quiz.
					$total_time_spent += ( $attempt->activity_updated - $attempt->activity_started );
				}
			}
		}

		return $total_time_spent;
	}
}

if ( ! function_exists( 'learndash_get_user_quiz_attempts_count' ) ) {
	/**
	 * Gets the count of user quiz attempts.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0 Use `learndash_get_user_quiz_attempts` instead.
	 *
	 * @param int $user_id The ID of the user to get quiz attempts.
	 * @param int $quiz_id The ID of the quiz to get attempts.
	 *
	 * @return int|void The count of quiz attempts.
	 */
	function learndash_get_user_quiz_attempts_count( $user_id, $quiz_id ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_user_quiz_attempts' );

		$quiz_attempts = learndash_get_user_quiz_attempts( $user_id, $quiz_id );
		if ( ( ! empty( $quiz_attempts ) ) && ( is_array( $quiz_attempts ) ) ) {
			return count( $quiz_attempts );
		}
	}
}

if ( ! function_exists( 'learndash_get_user_quiz_attempts_time_spent' ) ) {
	/**
	 * Gets the time spent by user on the quiz.
	 *
	 * Total of each started/complete time set.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0
	 *
	 * @param int $user_id The ID of the user to get quiz time spent.
	 * @param int $quiz_id The ID of the quiz to get time spent.
	 *
	 * @return int The total number of seconds spent on a quiz.
	 */
	function learndash_get_user_quiz_attempts_time_spent( $user_id, $quiz_id ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_user_quiz_attempts' );

		$total_time_spent = 0;

		$attempts = learndash_get_user_quiz_attempts( $user_id, $quiz_id );
		if ( ( ! empty( $attempts ) ) && ( is_array( $attempts ) ) ) {
			foreach ( $attempts as $attempt ) {
				$total_time_spent += ( $attempt->activity_completed - $attempt->activity_started );
			}
		}

		return $total_time_spent;
	}
}

if ( ! function_exists( 'learndash_set_exam_challenge_courses' ) ) {
	/**
	 * Sets the list of enrolled courses for an exam.
	 *
	 * @since 4.0.0
	 * @deprecated 5.0.0
	 *
	 * @param int   $exam_id          Optional. Exam ID. Default 0.
	 * @param array $exam_courses_new Optional. An array of courses to enroll an exam. Default empty array.
	 */
	function learndash_set_exam_challenge_courses( $exam_id = 0, $exam_courses_new = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$exam_id = absint( $exam_id );
		if ( ! empty( $exam_id ) ) {
			$exam_courses_old = learndash_get_exam_challenge_courses( $exam_id, true );

			$exam_courses_intersect = array_intersect( $exam_courses_new, $exam_courses_old );

			$exam_courses_add = array_diff( $exam_courses_new, $exam_courses_intersect );
			if ( ! empty( $exam_courses_add ) ) {
				foreach ( $exam_courses_add as $course_id ) {
					learndash_update_course_exam_challenge( $course_id, $exam_id, false );
				}
			}

			$exam_courses_remove = array_diff( $exam_courses_old, $exam_courses_intersect );
			if ( ! empty( $exam_courses_remove ) ) {
				foreach ( $exam_courses_remove as $course_id ) {
					learndash_update_course_exam_challenge( $course_id, $exam_id, true );
				}
			}
		}
	}
}

if ( ! function_exists( 'learndash_get_user_course_attempts' ) ) {
	/**
	 * Gets the user course attempts activity.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0
	 *
	 * @param int $user_id   Optional. The ID of the user to get course attempts. Default 0.
	 * @param int $course_id Optional. The ID of the course to get attempts. Default 0.
	 *
	 * @return array|void An array of activity IDs and timestamps or quizzes found.
	 */
	function learndash_get_user_course_attempts( $user_id = 0, $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		global $wpdb;

		if ( ( ! empty( $user_id ) ) || ( ! empty( $course_id ) ) ) {
			return $wpdb->get_results(
				$wpdb->prepare( 'SELECT activity_id, activity_started, activity_completed, activity_updated FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND post_id=%d and activity_type=%s ORDER BY activity_id, activity_started ASC', $user_id, $course_id, 'course' )
			);
		}
	}
}

if ( ! function_exists( 'learndash_get_exam_challenge_courses' ) ) {
	/**
	 * Gets the list of enrolled courses for a Challenge Exam.
	 *
	 * @since 4.0.0
	 * @deprecated 5.0.0
	 *
	 * @param int $exam_id Optional. Exam ID. Default 0.
	 *
	 * @return array An array of course IDs.
	 */
	function learndash_get_exam_challenge_courses( $exam_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0' );

		$course_ids = array();

		$exam_id = absint( $exam_id );
		if ( ! empty( $exam_id ) ) {
			$query_args = array(
				'post_type'      => learndash_get_post_type_slug( 'course' ),
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => LEARNDASH_EXAM_CHALLENGE_POST_META_KEY,
						'value'   => $exam_id,
						'compare' => '=',
					),
				),
			);

			$query = new WP_Query( $query_args );
			if ( ( is_a( $query, 'WP_Query' ) ) && ( property_exists( $query, 'posts' ) ) ) {
				$course_ids = $query->posts;
			}
		}

		return $course_ids;
	}
}

if ( ! function_exists( 'learndash_get_course_lessons_list_legacy' ) ) {
	/**
	 * LEGACY: Gets the lesson list output for a course.
	 *
	 * Replaced by `learndash_get_course_lessons_list` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0
	 *
	 * @param int|WP_Post|null $course       Optional. The `WP_Post` course object or course ID. Default null.
	 * @param int|null         $user_id      Optional. User ID. Default null.
	 * @param array            $lessons_args Optional. An array of query arguments to get lesson list. Default empty array.
	 *
	 * @return array The lesson list array.
	 */
	function learndash_get_course_lessons_list_legacy( $course = null, $user_id = null, $lessons_args = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_lessons_list' );

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
}

if ( ! function_exists( 'learndash_get_topic_list_legacy' ) ) {
	/**
	 * LEGACY: Gets the topics list for a lesson.
	 *
	 * Replaced by `learndash_get_topic_list` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_get_topic_list` instead.
	 *
	 * @param int|null $for_lesson_id Optional. The ID of the lesson to get topics.
	 * @param int|null $course_id     Optional. Course ID.
	 *
	 * @return array An array of topics list.
	 */
	function learndash_get_topic_list_legacy( $for_lesson_id = null, $course_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_topic_list' );

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
}

if ( ! function_exists( 'learndash_get_course_quiz_list_legacy' ) ) {
	/**
	 * LEGACY: Gets the quiz list output for a course.
	 *
	 * Replaced by `learndash_get_course_quiz_list` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_get_course_quiz_list` instead.
	 *
	 * @param int|WP_Post|null $course  Optional. The `WP_Post` course object or course ID. Default null.
	 * @param int|null         $user_id Optional. User ID. Default null.
	 *
	 * @return array|string The quiz list HTML output.
	 */
	function learndash_get_course_quiz_list_legacy( $course = null, $user_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_quiz_list' );

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
}

if ( ! function_exists( 'learndash_get_lesson_quiz_list_legacy' ) ) {
	/**
	 * LEGACY: Gets the quiz list output for a lesson.
	 *
	 * Replaced by `learndash_get_lesson_quiz_list` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_get_lesson_quiz_list` instead.
	 *
	 * @param int|WP_Post $lesson    The `WP_Post` lesson object or lesson ID.
	 * @param int|null    $user_id   Optional. User ID. Default null.
	 * @param int|null    $course_id Optional. Course ID. Default null.
	 *
	 * @return array|string The lesson quiz list HTML output.
	 */
	function learndash_get_lesson_quiz_list_legacy( $lesson, $user_id = null, $course_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_lesson_quiz_list' );

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
}

if ( ! function_exists( 'learndash_get_global_quiz_list_legacy' ) ) {
	/**
	 * LEGACY: Gets the quiz list for a resource.
	 *
	 * Replaced by `learndash_get_global_quiz_list` in 3.4.0.
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_get_global_quiz_list` instead.
	 *
	 * @param int|null $id Optional. An ID of the resource.
	 *
	 * @return array An array of quizzes.
	 */
	function learndash_get_global_quiz_list_legacy( $id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_global_quiz_list' );

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
}

if ( ! function_exists( 'learndash_get_course_data_legacy' ) ) {
	/**
	 * LEGACY: Gets the course data for the course builder.
	 *
	 * Replaced by `learndash_get_course_data` in 3.4.0.
	 *
	 * @since 3.4.0
	 * @deprecated 5.0.0 Use `learndash_get_course_data` instead.
	 *
	 * @param array $data The data passed down to the front-end.
	 *
	 * @return array The data passed down to the front-end.
	 */
	function learndash_get_course_data_legacy( $data ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_data' );

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
}

if ( ! function_exists( 'learndash_get_course_steps_count_legacy' ) ) {
	/**
	 * LEGACY: Gets the total count of lessons and topics for a given course ID.
	 *
	 * Replaced by `learndash_get_course_steps_count` in 3.4.0.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0 Use `learndash_get_course_steps_count` instead.
	 *
	 * @param int $course_id Optional. The ID of the course. Default 0.
	 *
	 * @return int The count of the course steps.
	 */
	function learndash_get_course_steps_count_legacy( $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_steps_count' );

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
}

if ( ! function_exists( 'learndash_course_status_legacy' ) ) {
	/**
	 * LEGACY: Outputs the current status of the course.
	 *
	 * Replaced by `learndash_course_status` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @since 2.5.8 Added $return_slug parameter.
	 * @deprecated 5.0.0 Use `learndash_course_status` instead.
	 *
	 * @param int      $id          Course ID to get status.
	 * @param int|null $user_id     Optional. User ID. Default null.
	 * @param boolean  $return_slug Optional. If false will return translatable string otherwise the status slug. Default false.
	 *
	 * @return string The current status of the course.
	 */
	function learndash_course_status_legacy( $id, $user_id = null, $return_slug = false ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_course_status' );

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

			$recalculate_course_total_steps = true;

			if ( has_filter( 'learndash_course_status_recalc_total_steps' ) ) {
				/**
				 * Filters the recalculation of the course steps.
				 *
				 * @since 3.3.0
				 * @deprecated 5.0.0 Use `learndash_course_status_recalc_total_steps` instead.
				 *
				 * @param bool  $recalculate_course_total_steps Recalculate course total steps. Default true.
				 * @param array $course_progress                Array of course progress.
				 * @param int   $user_id                        User ID.
				 * @param int   $course_id                      Course ID.
				 */
				$recalculate_course_total_steps = apply_filters_deprecated(
					'learndash_course_status_recalc_total_steps',
					[ true, $course_progress[ $id ], $user_id, $id ],
					'5.0.0'
				);
			}

			if ( $recalculate_course_total_steps ) {
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

					$quiz_notstarted = false;

					if ( has_filter( 'learndash_course_autocompletion_multiple_final_quizzes_step' ) ) {
						/**
						 * Filters to autocomplete course with multiple final (global) quizzes when not all are complete.
						 *
						 * @since 3.2.3
						 * @deprecated 5.0.0 Use `learndash_course_autocompletion_multiple_final_quizzes_step` instead.
						 *
						 * @param bool  $autocomplete_course_step Autocomplete course step. Default false.
						 * @param int   $id                       Course ID
						 * @param int   $user_id                  User ID
						 * @param array $quizzes                  Course Global Quiz Posts.
						 * @param array $quizzes_incomplete       Array of incomplete Quizzes IDs.
						 *
						 * @return bool True auto complete step, false do not auto complete step.
						 */
						$quiz_notstarted = apply_filters_deprecated(
							'learndash_course_autocompletion_multiple_final_quizzes_step',
							[ false, $id, $user_id, $quizzes, $quizzes_incomplete ],
							'5.0.0'
						);
					}
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
}

if ( ! function_exists( 'learndash_is_lesson_notcomplete_legacy' ) ) {
	/**
	 * LEGACY: Checks if a lesson is not complete.
	 *
	 * Replaced by `learndash_is_lesson_notcomplete` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_is_lesson_notcomplete` instead.
	 *
	 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
	 * @param array    $lessons   Optional. An array of lesson IDs.
	 * @param int      $course_id Optional. Course ID. Default 0.
	 *
	 * @return boolean Returns true if the lesson is not complete otherwise false.
	 */
	function learndash_is_lesson_notcomplete_legacy( $user_id = null, $lessons = array(), $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_is_lesson_notcomplete' );

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
}

if ( ! function_exists( 'learndash_is_topic_notcomplete_legacy' ) ) {
	/**
	 * LEGACY: Checks if a topic is not complete.
	 *
	 * Replaced by `learndash_is_topic_notcomplete` in 3.4.0.
	 *
	 * @since 2.3.1
	 * @since 3.2.0 Added `$course_id` parameter
	 * @deprecated 5.0.0 Use `learndash_is_topic_notcomplete` instead.
	 *
	 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
	 * @param array    $topics    Optional. An array of topic IDs.
	 * @param int      $course_id Optional. Course ID.
	 *
	 * @return boolean Returns true if the topic is not completed otherwise false.
	 */
	function learndash_is_topic_notcomplete_legacy( $user_id = null, $topics = array(), $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_is_topic_notcomplete' );

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
}

if ( ! function_exists( 'learndash_is_quiz_accessable_legacy' ) ) {
	/**
	 * Checks if the quiz is accessible to the user (legacy).
	 *
	 * Replaced by `learndash_is_quiz_accessable` in 3.4.0.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0 Use `learndash_is_quiz_accessable` instead.
	 *
	 * @param int|null     $user_id   Optional. User ID. Default null.
	 * @param WP_Post|null $post      Optional. The `WP_Post` quiz object. Default null.
	 * @param int          $course_id Optional. Course ID. Default 0.
	 *
	 * @return int Returns 1 if the quiz is accessible by the user otherwise 0.
	 */
	function learndash_is_quiz_accessable_legacy( $user_id = null, $post = null, $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_is_quiz_accessable' );

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
}

if ( ! function_exists( 'learndash_get_course_progress_legacy' ) ) {
	/**
	 * LEGACY: Gets the user's current course progress.
	 *
	 * Replaced by `learndash_get_course_progress` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_get_course_progress` instead.
	 *
	 * @param int|null $user_id   Optional. User ID. Default null.
	 * @param int|null $postid    Optional. Post ID. Default null.
	 * @param int|null $course_id Optional. Course ID. Default null.
	 *
	 * @return array An array of user's current course progress.
	 */
	function learndash_get_course_progress_legacy( $user_id = null, $postid = null, $course_id = null ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_progress' );

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
}

if ( ! function_exists( 'learndash_get_course_steps_legacy' ) ) {
	/**
	 * LEGACY: Gets all the lessons and topics for a given course ID.
	 *
	 * For now excludes quizzes at lesson and topic level.
	 *
	 * Replaced by `learndash_get_course_steps` in 3.4.0.
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0 Use `learndash_get_course_steps` instead.
	 *
	 * @param int   $course_id          Optional. The ID of the course. Default 0.
	 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
	 *
	 * @return array An array of all course steps.
	 */
	function learndash_get_course_steps_legacy( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_get_course_steps' );

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
}

if ( ! function_exists( 'learndash_process_mark_complete_legacy' ) ) {
	/**
	 * LEGACY: Updates the user meta with completion status for any resource.
	 *
	 * Replaced by `learndash_process_mark_complete` in 3.4.0.
	 *
	 * @since 2.1.0
	 * @deprecated 5.0.0 Use `learndash_process_mark_complete` instead.
	 *
	 * @param int|null $user_id       Optional. User ID. Default null.
	 * @param int|null $postid        Optional. The ID of the resource like course, lesson, topic, etc. Default null.
	 * @param boolean  $onlycalculate Optional. Whether to mark the resource as complete. Default false.
	 * @param int      $course_id     Optional. Course ID. Default 0.
	 *
	 * @return boolean Returns true if the meta is updated successfully otherwise false.
	 */
	function learndash_process_mark_complete_legacy( $user_id = null, $postid = null, $onlycalculate = false, $course_id = 0 ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_process_mark_complete' );

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
}

if ( ! function_exists( 'learndash_course_get_completed_steps_legacy' ) ) {
	/**
	 * LEGACY: Gets the total completed steps for a given course progress array.
	 *
	 * Replaced by `learndash_course_get_completed_steps` in 3.4.0
	 *
	 * @since 2.3.0
	 * @deprecated 5.0.0 Use `learndash_course_get_completed_steps` instead.
	 *
	 * @param int   $user_id         Optional. The ID of the user. Default 0.
	 * @param int   $course_id       Optional. The ID of the course. Default 0.
	 * @param array $course_progress Optional. An array of course progress data. Default empty array.
	 *
	 * @return int The count of completed course steps.
	 */
	function learndash_course_get_completed_steps_legacy( $user_id = 0, $course_id = 0, $course_progress = array() ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'learndash_course_get_completed_steps' );

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
}
