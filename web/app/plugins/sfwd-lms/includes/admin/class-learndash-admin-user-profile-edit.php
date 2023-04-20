<?php
/**
 * LearnDash Admin WP User Profile Edit.
 *
 * @since 2.2.1
 * @package LearnDash\User\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_User_Profile_Edit' ) ) {

	/**
	 * Class LearnDash Admin WP User Profile Edit.
	 *
	 * @since 2.2.1
	 */
	class Learndash_Admin_User_Profile_Edit {
		/**
		 * Public constructor for class.
		 *
		 * @since 2.2.1
		 */
		public function __construct() {
			// Hook into the on-load action for our post_type editor.
			add_action( 'load-profile.php', array( $this, 'on_load_user_profile' ) );
			add_action( 'load-user-edit.php', array( $this, 'on_load_user_profile' ) );

			add_action( 'show_user_profile', array( $this, 'show_user_profile' ) );
			add_action( 'edit_user_profile', array( $this, 'show_user_profile' ) );

			add_action( 'personal_options_update', array( $this, 'save_user_profile' ), 1 );
			add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ), 1 );

			add_action( 'wp_ajax_learndash_remove_quiz', array( $this, 'remove_quiz_ajax' ) );
		}

		/**
		 * Function called when WP load the page.
		 * Fires on action 'load-profile.php'
		 * Fires on action 'load-user-edit.php'
		 *
		 * @since 2.2.1
		 */
		public function on_load_user_profile() {
			global $learndash_assets_loaded;

			wp_enqueue_style(
				'learndash_style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash_style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_style'] = __FUNCTION__;

			$filepath = SFWD_LMS::get_template( 'learndash_template_style.css', null, null, true );
			if ( ! empty( $filepath ) ) {
				wp_enqueue_style( 'learndash_template_style_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
				wp_style_add_data( 'learndash_template_style_css', 'rtl', 'replace' );
				$learndash_assets_loaded['styles']['learndash_template_style_css'] = __FUNCTION__;
			}

			wp_enqueue_style(
				'learndash-admin-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-style' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-admin-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash-admin-style'] = __FUNCTION__;

			wp_enqueue_style(
				'sfwd-module-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash-admin-binary-selector-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-binary-selector' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['learndash-admin-binary-selector-script'] = __FUNCTION__;

			wp_enqueue_script(
				'sfwd-module-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/sfwd_module' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['sfwd-module-script'] = __FUNCTION__;

			$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
			if ( ! empty( $filepath ) ) {
				wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
				wp_style_add_data( 'learndash_pager_css', 'rtl', 'replace' );
				$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
			}

			$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
			if ( ! empty( $filepath ) ) {
				wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
				$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
			}

			$data = array();

			if ( ! empty( $this->script_data ) ) {
				$data = $this->script_data;
			}

			if ( ! isset( $data['ajaxurl'] ) ) {
				$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
			}

			$data = array( 'json' => wp_json_encode( $data ) );
			wp_localize_script( 'sfwd-module-script', 'sfwd_data', $data );

			wp_enqueue_style(
				'learndash-admin-binary-selector-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-binary-selector' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-admin-binary-selector-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash-admin-binary-selector-style'] = __FUNCTION__;
		}

		/**
		 * Function called to show / edit WP user profile.
		 * Fires on action 'show_user_profile'
		 * Fires on action 'edit_user_profile'
		 *
		 * @since 2.2.1
		 *
		 * @param WP_User $user User object instance.
		 */
		public function show_user_profile( WP_User $user ) {

			$this->show_user_courses( $user );
			$this->show_user_groups( $user );
			$this->show_leader_groups( $user );

			$this->show_user_course_info( $user );
			$this->show_user_delete_data_link( $user );
		}

		/**
		 * Displays users course information at bottom of profile
		 * called by show_user_profile().
		 *
		 * @since 2.5.0
		 *
		 * @param WP_User $user wp user object.
		 */
		private function show_user_course_info( WP_User $user ) {
			$user_id = $user->ID;
			echo '<h3>' . sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Info', 'Course Info Label', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			) . '</h3>';

			$atts = array(
				'progress_num'     => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'progress_num' ),
				'progress_orderby' => 'title',
				'progress_order'   => 'ASC',
				'quiz_num'         => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'quiz_num' ),
				'quiz_orderby'     => 'taken',
				'quiz_order'       => 'DESC',
			);
			/**
			 * Filters profile course info attributes.
			 *
			 * @since 2.5.5
			 *
			 * @param array   $attributes An array of course info attributes.
			 * @param WP_User $user       WP_User object to be checked.
			 */
			$atts = apply_filters( 'learndash_profile_course_info_atts', $atts, $user );

			echo SFWD_LMS::get_course_info( $user_id, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Output link to delete course data for user
		 *
		 * @since 2.5.0
		 *
		 * @param WP_User $user WP_User object.
		 */
		private function show_user_delete_data_link( WP_User $user ) {
			if ( ! current_user_can( 'edit_users' ) ) {
				return '';
			}

			?>
			<div id="learndash_delete_user_data">
				<h2>
				<?php
				printf(
					// translators: placeholder: Course.
					esc_html_x( 'Permanently Delete %s Data', 'Permanently Delete Course Data Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'Course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				);
				?>
				</h2>
				<p><input type="checkbox" id="learndash_delete_user_data" name="learndash_delete_user_data" value="<?php echo (int) $user->ID; ?>"> <label for="learndash_delete_user_data">
				<?php
				echo wp_kses_post(
					sprintf(
						// translators: placeholder: course.
						_x( 'Check and click update profile to permanently delete users LearnDash %s data. <strong>This cannot be undone.</strong>', 'placeholder: course', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'course' ) )
					)
				)
				?>
				</label></p>
				<?php
					global $wpdb;
					$proquiz_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							'SELECT quiz_id as proquiz_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_lock' ) ) . ' WHERE user_id = %d',
							$user->ID
						)
					);
				if ( ! empty( $proquiz_ids ) ) {
					$quiz_ids = array();

					foreach ( $proquiz_ids as $proquiz_id ) {
						$quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $proquiz_id );
						if ( ! empty( $quiz_id ) ) {
							$quiz_ids[] = $quiz_id;
						}
					}

					if ( ! empty( $quiz_ids ) ) {
						$quiz_query_args = array(
							'post_type'   => 'sfwd-quiz',
							'post_status' => array( 'publish' ),
							'post__in'    => $quiz_ids,
							'nopaging'    => true,
							'orderby'     => 'title',
							'order'       => 'ASC',
						);
						$quiz_query      = new WP_Query( $quiz_query_args );
						if ( ! empty( $quiz_query->posts ) ) {
							?>
								<p><label for="">
								<?php
								wp_kses_post(
									sprintf(
										// translators: placeholder: quiz.
										esc_html_x( 'Remove the %s lock(s) for this user.', 'placeholder: quiz', 'learndash' ),
										esc_html( learndash_get_custom_label_lower( 'quiz' ) )
									)
								)
								?>
								</label> <select
									id="learndash_delete_quiz_user_lock_data" name="learndash_delete_quiz_user_lock_data">
									<option value=""></option>
								<?php
								foreach ( $quiz_query->posts as $quiz_post ) {
									?>
											<option value="<?php echo absint( $quiz_post->ID ); ?>"><?php echo wp_kses_post( $quiz_post->post_title ); ?></option>
											<?php
								}
								?>
								</select>
								<input type="hidden" name="learndash_delete_quiz_user_lock_data-nonce" value="<?php echo esc_attr( wp_create_nonce( 'learndash_delete_quiz_user_lock_data-' . intval( $user->ID ) ) ); ?>">
								<?php
						}
					}
				}
				?>
			</div>
			<?php
		}

		/**
		 * Save WP User Profile hook.
		 *
		 * @since 2.2.1
		 *
		 * @param integer $user_id ID of user being saved.
		 */
		public function save_user_profile( $user_id ) {
			if ( ! current_user_can( 'edit_users' ) ) {
				return;
			}

			if ( empty( $user_id ) ) {
				return;
			}

			if ( ( isset( $_POST['learndash_user_courses'] ) ) && ( isset( $_POST['learndash_user_courses'][ $user_id ] ) ) && ( ! empty( $_POST['learndash_user_courses'][ $user_id ] ) ) ) {
				if ( ( isset( $_POST[ 'learndash_user_courses-' . $user_id . '-changed' ] ) ) && ( '1' === $_POST[ 'learndash_user_courses-' . $user_id . '-changed' ] ) ) {
					if ( ( isset( $_POST[ 'learndash_user_courses-' . $user_id . '-nonce' ] ) ) && ( ! empty( $_POST[ 'learndash_user_courses-' . $user_id . '-nonce' ] ) ) ) {
						if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'learndash_user_courses-' . $user_id . '-nonce' ] ) ), 'learndash_user_courses-' . $user_id ) ) {
							$user_courses = (array) json_decode( wp_unslash( $_POST['learndash_user_courses'][ $user_id ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							learndash_user_set_enrolled_courses( $user_id, $user_courses );
						}
					}
				}
			}

			if ( ( isset( $_POST['learndash_user_groups'] ) ) && ( isset( $_POST['learndash_user_groups'][ $user_id ] ) ) && ( ! empty( $_POST['learndash_user_groups'][ $user_id ] ) ) ) {
				if ( ( isset( $_POST[ 'learndash_user_groups-' . $user_id . '-changed' ] ) ) && ( ! empty( $_POST[ 'learndash_user_groups-' . $user_id . '-changed' ] ) ) ) {
					if ( ( isset( $_POST[ 'learndash_user_groups-' . $user_id . '-nonce' ] ) ) && ( ! empty( $_POST[ 'learndash_user_groups-' . $user_id . '-nonce' ] ) ) ) {
						if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'learndash_user_groups-' . $user_id . '-nonce' ] ) ), 'learndash_user_groups-' . $user_id ) ) {
							$user_groups = (array) json_decode( wp_unslash( $_POST['learndash_user_groups'][ $user_id ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							learndash_set_users_group_ids( $user_id, $user_groups );
						}
					}
				}
			}

			if ( ( isset( $_POST['learndash_leader_groups'] ) ) && ( isset( $_POST['learndash_leader_groups'][ $user_id ] ) ) && ( ! empty( $_POST['learndash_leader_groups'][ $user_id ] ) ) ) {
				if ( ( isset( $_POST[ 'learndash_leader_groups-' . $user_id . '-changed' ] ) ) && ( ! empty( $_POST[ 'learndash_leader_groups-' . $user_id . '-changed' ] ) ) ) {
					if ( ( isset( $_POST[ 'learndash_leader_groups-' . $user_id . '-nonce' ] ) ) && ( ! empty( $_POST[ 'learndash_leader_groups-' . $user_id . '-nonce' ] ) ) ) {
						if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'learndash_leader_groups-' . $user_id . '-nonce' ] ) ), 'learndash_leader_groups-' . $user_id ) ) {
							$user_groups = (array) json_decode( wp_unslash( $_POST['learndash_leader_groups'][ $user_id ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							learndash_set_administrators_group_ids( $user_id, $user_groups );
						}
					}
				}
			}

			/**
			 * Process course access date changes
			 *
			 * @since 2.6.0
			 */
			if ( ( isset( $_POST['learndash-user-courses-access-changed'][ $user_id ] ) ) && ( ! empty( $_POST['learndash-user-courses-access-changed'][ $user_id ] ) ) && ( is_array( $_POST['learndash-user-courses-access-changed'][ $user_id ] ) ) ) {
				foreach ( wp_unslash( $_POST['learndash-user-courses-access-changed'][ $user_id ] ) as $course_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( ( isset( $_POST['learndash-user-courses-access'][ $user_id ][ $course_id ] ) ) && ( ! empty( $_POST['learndash-user-courses-access'][ $user_id ][ $course_id ] ) ) ) {
						$course_date_set = (array) wp_unslash( $_POST['learndash-user-courses-access'][ $user_id ][ $course_id ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						if ( isset( $course_date_set['aa'] ) ) { // @phpstan-ignore-line
							$course_date_set['aa'] = intval( $course_date_set['aa'] );
						} else {
							$date['aa'] = 0;
						}

						if ( isset( $course_date_set['mm'] ) ) { // @phpstan-ignore-line
							$course_date_set['mm'] = intval( $course_date_set['mm'] );
						} else {
							$date['mm'] = 0;
						}

						if ( isset( $course_date_set['jj'] ) ) { // @phpstan-ignore-line
							$course_date_set['jj'] = intval( $course_date_set['jj'] );
						} else {
							$course_date_set['jj'] = 0;
						}

						if ( isset( $course_date_set['hh'] ) ) { // @phpstan-ignore-line
							$course_date_set['hh'] = intval( $course_date_set['hh'] );
						} else {
							$course_date_set['hh'] = 0;
						}

						if ( isset( $course_date_set['mn'] ) ) { // @phpstan-ignore-line
							$course_date_set['mn'] = intval( $course_date_set['mn'] );
						} else {
							$course_date_set['mn'] = 0;
						}

						if ( ( ! empty( $course_date_set['aa'] ) ) && ( ! empty( $course_date_set['mm'] ) ) && ( ! empty( $course_date_set['jj'] ) ) ) { // @phpstan-ignore-line
							$date_string = sprintf( '%04d-%02d-%02d %02d:%02d:00', $course_date_set['aa'], $course_date_set['mm'], $course_date_set['jj'], $course_date_set['hh'], $course_date_set['mn'] );
							$ret         = ld_course_access_from_update( (int) $course_id, (int) $user_id, $date_string, false );
						}
					}
				}
			}

			if ( ( isset( $_POST['learndash_delete_quiz_user_lock_data'] ) ) && ( ! empty( $_POST['learndash_delete_quiz_user_lock_data'] ) ) ) {
				if ( ( isset( $_POST['learndash_delete_quiz_user_lock_data-nonce'] ) ) && ( ! empty( $_POST['learndash_delete_quiz_user_lock_data-nonce'] ) ) ) {
					if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash_delete_quiz_user_lock_data-nonce'] ) ), 'learndash_delete_quiz_user_lock_data-' . $user_id ) ) {
						learndash_remove_user_quiz_locks( $user_id, $_POST['learndash_delete_quiz_user_lock_data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					}
				}
			}

			if ( isset( $_POST['learndash_course_points'] ) ) {
				update_user_meta( $user_id, 'course_points', learndash_format_course_points( $_POST['learndash_course_points'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}

			if ( ( isset( $_POST['learndash_delete_user_data'] ) ) && ( ! empty( $_POST['learndash_delete_user_data'] ) ) && ( intval( $_POST['learndash_delete_user_data'] ) === intval( $user_id ) ) ) {
				learndash_delete_user_data( $user_id );
			}

			learndash_save_user_course_complete( $user_id );
		}

		/**
		 * Show User Enrolled Courses Binary Selector.
		 * called by show_user_profile().
		 *
		 * @since 2.2.1
		 *
		 * @param WP_User $user wp_user object.
		 */
		private function show_user_courses( WP_User $user ) {
			// First check is the user viewing the screen is admin...
			if ( current_user_can( 'edit_users' ) ) {
				// Then is the user profile being viewed is not admin.
				if ( learndash_can_user_autoenroll_courses( $user->ID ) ) {
					?>
					<h3>
					<?php
					printf(
						// translators: placeholder: Courses.
						esc_html_x( 'User Enrolled %s', 'User Enrolled Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</h3>
					<p><?php esc_html_e( 'User is automatically enrolled in all Courses.', 'learndash' ); ?></p>
					<?php
					return;
				}

				$ld_binary_selector_user_courses = new Learndash_Binary_Selector_User_Courses(
					array(
						'user_id'      => $user->ID,
						'selected_ids' => learndash_user_get_enrolled_courses( $user->ID, array(), true ),
					)
				);
				$ld_binary_selector_user_courses->show();
			}
		}

		/**
		 * Show User Enrolled Groups Binary Selector.
		 * called by show_user_profile().
		 *
		 * @since 2.2.1
		 *
		 * @param WP_User $user wp_user object.
		 */
		private function show_user_groups( WP_User $user ) {
			if ( current_user_can( 'edit_users' ) ) {

				$ld_binary_selector_user_groups = new Learndash_Binary_Selector_User_Groups(
					array(
						'user_id'      => $user->ID,
						'selected_ids' => learndash_get_users_group_ids( $user->ID, true ),
					)
				);
				$ld_binary_selector_user_groups->show();
			}
		}

		/**
		 * Show User Leader of Groups Binary Selector.
		 * called by show_user_profile().
		 *
		 * @since 2.2.1
		 *
		 * @param WP_User $user A user whose profile we are editing.
		 */
		private function show_leader_groups( WP_User $user ): void {
			if (
				! current_user_can( 'edit_users' )
				|| ! learndash_is_group_leader_user( $user->ID )
			) {
				return;
			}

			if ( learndash_is_admin_user() ) {
				$current_user_can_edit = true;
			} else {
				/**
				 * Filters an ability of a group leader to edit the groups list they lead or other group leaders lead.
				 *
				 * @since 4.5.0
				 *
				 * @param bool    $can_edit     A flag indicating if a user can edit group leaders. Default false.
				 * @param WP_User $group_leader A group leader user object.
				 *
				 * @return bool True if a group leader is allowed to edit group leaders.
				 */
				$current_user_can_edit = apply_filters(
					'learndash_group_leader_can_edit_group_leaders',
					false,
					$user
				);
			}

			if ( ! $current_user_can_edit ) {
				return;
			}

			$ld_binary_selector_leader_groups = new Learndash_Binary_Selector_Leader_Groups(
				array(
					'user_id'      => $user->ID,
					'selected_ids' => learndash_get_administrators_group_ids( $user->ID, true ),
				)
			);

			$ld_binary_selector_leader_groups->show();
		}

		/**
		 * Remove Quiz AJAX handler.
		 *
		 * @since 2.5.0
		 */
		public function remove_quiz_ajax() {
			$data = array();

			$quiz_time = 0;
			if ( isset( $_POST['quiz_time'] ) ) {
				$quiz_time = sanitize_text_field( wp_unslash( $_POST['quiz_time'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_nonce is called below
			}

			$quiz_nonce = 0;
			if ( isset( $_POST['quiz_nonce'] ) ) {
				$quiz_nonce = sanitize_text_field( wp_unslash( $_POST['quiz_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_nonce is called below
			}

			$user_id = 0;
			if ( isset( $_POST['user_id'] ) ) {
				$user_id = intval( $_POST['user_id'] );
			}

			if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_time ) ) && ( ! empty( $quiz_nonce ) ) ) {
				$user_quizzes = learndash_get_user_quiz_attempt( $user_id, array( 'time' => $quiz_time ) );
				if ( ! empty( $user_quizzes ) ) {
					foreach ( $user_quizzes as $q_idx => $q_item ) {
						if ( wp_verify_nonce( $quiz_nonce, 'remove_quiz_' . $user_id . '_' . $q_item['quiz'] . '_' . $q_item['time'] ) ) {
							learndash_remove_user_quiz_attempt( $user_id, array( 'time' => $q_item['time'] ) );
						}
					}
				}
			}

			echo wp_json_encode( $data );
			die();
		}

		// End of functions.
	}
}
