<?php
/**
 * LearnDash Data Upgrades for Quiz Questions.
 *
 * @since 2.6.0
 * @package LearnDash\Data_Upgrades
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' ) ) ) {

	/**
	 * Class LearnDash Data Upgrades for Quiz Questions.
	 *
	 * @since 2.6.0
	 * @uses Learndash_Admin_Data_Upgrades
	 */
	class Learndash_Admin_Data_Upgrades_Quiz_Questions extends Learndash_Admin_Data_Upgrades {

		/**
		 * Protected constructor for class
		 *
		 * @since 2.6.0
		 */
		protected function __construct() {
			$this->data_slug = 'pro-quiz-questions';
			parent::__construct();
			parent::register_upgrade_action();
		}

		/**
		 * Show data upgrade row for this instance.
		 *
		 * @since 2.6.0
		 */
		public function show_upgrade_action() {
			?>
			<tr id="learndash-data-upgrades-container-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-container">
				<td class="learndash-data-upgrades-button-container">
					<button class="learndash-data-upgrades-button button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ); ?>" data-slug="<?php echo esc_attr( $this->data_slug ); ?>">
					<?php
						esc_html_e( 'Upgrade', 'learndash' );
					?>
					</button>
				</td>
				<td class="learndash-data-upgrades-status-container">
					<span class="learndash-data-upgrades-name">
					<?php
					printf(
						// translators: placeholders: Quiz, Questions.
						esc_html_x( 'Upgrade %1$s %2$s', 'placeholders: Quiz, Questions', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</span>
					<p>
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: placeholders: Quiz questions, Quiz.
							_x( 'This upgrade will convert the %1$s %2$s to WordPress custom post type. <strong>This is required before enabling %3$s Builder.</strong> (Optional)', 'placeholders: Quiz questions, Quiz', 'learndash' ),
							esc_html( learndash_get_custom_label_lower( 'Quiz' ) ),
							esc_html( learndash_get_custom_label_lower( 'questions' ) ),
							esc_html( learndash_get_custom_label( 'Quiz' ) )
						)
					);
					?>
					</p>
					<p class="description"><?php echo esc_html( $this->get_last_run_info() ); ?></p>

					<?php
						$show_progress        = false;
						$data_settings        = $this->get_data_settings( $this->data_slug );
						$this->transient_key  = $this->data_slug;
						$this->transient_data = $this->get_transient( $this->transient_key );
					if ( ! empty( $this->transient_data ) ) {
						if ( isset( $this->transient_data['result_count'] ) ) {
							$this->transient_data['result_count'] = intval( $this->transient_data['result_count'] );
						} else {
							$this->transient_data['result_count'] = 0;
						}

						if ( isset( $this->transient_data['total_count'] ) ) {
							$this->transient_data['total_count'] = intval( $this->transient_data['total_count'] );
						} else {
							$this->transient_data['total_count'] = 0;
						}

						if ( ( ! empty( $this->transient_data['result_count'] ) ) && ( ! empty( $this->transient_data['total_count'] ) ) && ( $this->transient_data['result_count'] != $this->transient_data['total_count'] ) ) {
							$show_progress = true;
						}

						if ( isset( $this->transient_data['skipped'] ) ) {
							$this->transient_data['skipped'] = array();
						}
					}

					$progress_style       = 'display:none;';
					$progress_meter_style = '';
					$progress_label       = '';
					$progress_slug        = '';

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ( true === $show_progress ) && ( ! isset( $_GET['quiz_id'] ) ) ) {
						?>
						<p id="learndash-data-upgrades-continue-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-continue"><input type="checkbox" name="learndash-data-upgrades-continue" value="1" /> <?php esc_html_e( 'Continue previous upgrade processing?', 'learndash' ); ?></p>
							<?php

							$progress_style = '';
							$data           = $this->transient_data;
							$data           = $this->build_progress_output( $data );
							if ( ( isset( $data['progress_percent'] ) ) && ( ! empty( $data['progress_percent'] ) ) ) {
								$progress_meter_style = 'width: ' . $data['progress_percent'] . '%';
							}

							if ( ( isset( $data['progress_label'] ) ) && ( ! empty( $data['progress_label'] ) ) ) {
								$progress_label = $data['progress_label'];
							}

							if ( ( isset( $data['progress_slug'] ) ) && ( ! empty( $data['progress_slug'] ) ) ) {
								$progress_slug = 'progress-label-' . $data['progress_slug'];
							}
					} else {
						if ( ( isset( $data_settings['last_run'] ) ) && ( ! empty( $data_settings['last_run'] ) ) ) {
							$process_quiz_id = 0;
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
								// phpcs:ignore WordPress.Security.NonceVerification.Recommended
								if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( absint( $_GET['quiz_id'] ) ) ) {
									// phpcs:ignore WordPress.Security.NonceVerification.Recommended
									$process_quiz_id = absint( $_GET['quiz_id'] );
								} else {
									?>
									<p>
									<?php
										printf(
											// translators: placeholder: Quiz.
											esc_html_x( 'Invalid %s ID', 'placeholders: Quiz', 'learndash' ),
											LearnDash_Custom_Label::get_label( 'Quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
										)
									?>
										</p>
										<?php
								}
							}

							if ( empty( $process_quiz_id ) ) {
								if ( ( isset( $this->transient_data['quiz'] ) ) && ( ! empty( $this->transient_data['quiz'] ) ) ) {
									$process_quiz_id = absint( $this->transient_data['quiz'] );
								}
							}

							if ( ! empty( $process_quiz_id ) ) {
								?>
								<p id="learndash-data-upgrades-quiz-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-quiz">
								<?php
								printf(
									// translators: placeholders: Questions, Quiz, Quiz Title.
									esc_html_x( 'Reprocess %1$s for %2$s: "%3$s"', 'placeholders: Questions, Quiz, Quiz Title', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'Questions' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									LearnDash_Custom_Label::get_label( 'Quiz' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									wp_kses_post( get_the_title( absint( $_GET['quiz_id'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								)
								?>
									<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
									<input type="hidden" name="learndash-data-upgrades-quiz" value="<?php echo absint( $_GET['quiz_id'] ); ?>" />
									</p>
									<?php
							} else {
								?>
								<p id="learndash-data-upgrades-mismatched-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-mismatched"><input type="checkbox" name="learndash-data-upgrades-mismatched" value="1" checked="checked" />
									<?php
									printf(
										// translators: placeholders: Questions.
										esc_html_x( 'Process Mismatched %s only?', 'placeholders: Questions', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'Questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									);
									?>
									</p>
									<?php
							}
						}
					}
					?>
					<div style="<?php echo esc_attr( $progress_style ); ?>" class="meter learndash-data-upgrades-status">
						<div class="progress-meter">
							<span class="progress-meter-image" style="<?php echo esc_attr( $progress_meter_style ); ?>"></span>
						</div>
						<div class="progress-label <?php echo esc_attr( $progress_slug ); ?>"><?php echo esc_attr( $progress_label ); ?></div>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Class method for the AJAX update logic
		 * This function will determine what users need to be converted. Then the course and quiz functions
		 * will be called to convert each individual user data set.
		 *
		 * @since 2.6.0
		 *
		 * @param  array $data Post data from AJAX call.
		 *
		 * @return array $data Post data from AJAX call
		 */
		public function process_upgrade_action( $data = array() ) {
			global $wpdb;

			$this->init_process_times();

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( ( wp_verify_nonce( $data['nonce'], 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ) && ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) ) {
					$this->transient_key = $this->data_slug;

					if ( ( isset( $data['init'] ) ) && ( '1' === $data['init'] ) ) {
						unset( $data['init'] );

						/**
						 * Transient_data is used to store the local server state information and will
						 * saved in a transient type options variable.
						 */
						$this->transient_data = array();

						if ( ( isset( $data['continue'] ) ) && ( ! empty( $data['continue'] ) ) && ( 'true' === $data['continue'] ) ) {
							$this->transient_data['continue'] = true;
						} else {
							$this->transient_data['continue'] = false;
						}

						if ( ( isset( $data['quiz'] ) ) && ( ! empty( $data['quiz'] ) ) ) {
							$this->transient_data['quiz_id']  = absint( $data['quiz'] );
							$this->transient_data['continue'] = false;
						} else {
							$this->transient_data['quiz_id'] = 0;
						}

						if ( ( isset( $data['mismatched'] ) ) && ( ! empty( $data['mismatched'] ) ) && ( 'true' === $data['mismatched'] ) ) {
							$this->transient_data['mismatched'] = true;
							$this->transient_data['continue']   = false;
						} else {
							$this->transient_data['mismatched'] = false;
						}

						if ( 'true' !== $data['continue'] ) {
							// Hold the number of completed/processed items.
							$this->transient_data['result_count']     = 0;
							$this->transient_data['current_user']     = array();
							$this->transient_data['progress_started'] = time();
							$this->transient_data['progress_user']    = get_current_user_id();
							$this->transient_data['skipped']          = array();

							$quiz_builder_option                           = get_option( 'learndash_settings_quizzes_builder' );
							$quiz_builder_option['force_quiz_builder']     = '';
							$quiz_builder_option['force_shared_questions'] = '';
							update_option( 'learndash_settings_quizzes_builder', $quiz_builder_option );

							$this->query_items();
						} else {
							$this->transient_data = $this->get_transient( $this->transient_key );
						}

						$this->set_option_cache( $this->transient_key, $this->transient_data );

					} else {
						$this->transient_data = $this->get_transient( $this->transient_key );
						if ( ( ! isset( $this->transient_data['process_users'] ) ) || ( empty( $this->transient_data['process_users'] ) ) ) {
							$this->query_items();
						}

						if ( ( isset( $this->transient_data['process_users'] ) ) && ( ! empty( $this->transient_data['process_users'] ) ) ) {
							foreach ( $this->transient_data['process_users'] as $user_idx => $user_id ) {
								$user_id = intval( $user_id );
								if ( ( ! isset( $this->transient_data['current_user']['user_id'] ) ) || ( $this->transient_data['current_user']['user_id'] !== $user_id ) ) {
									$this->transient_data['current_user'] = array(
										'user_id'  => $user_id,
										'item_idx' => 0,
									);
								}

								$question_convert_complete = $this->convert_proquiz_question( intval( $user_id ) );
								if ( true === $question_convert_complete ) {
									$this->transient_data['current_user'] = array();
									unset( $this->transient_data['process_users'][ $user_idx ] );

									if ( ! isset( $this->transient_data['result_count'] ) ) {
										$this->transient_data['result_count'] = 0;
									}
									$this->transient_data['result_count'] = (int) $this->transient_data['result_count'] + 1;
								}

								$this->set_option_cache( $this->transient_key, $this->transient_data );
								if ( $this->out_of_timer() ) {
									break;
								}
							}
						}
					}
				}
			}

			$data = $this->build_progress_output( $data );

			// If we are at 100% then we update the internal data settings so other parts of LD know the upgrade has been run.
			if ( ( isset( $data['progress_percent'] ) ) && ( 100 == $data['progress_percent'] ) ) {

				// We enable Quiz Builder running the data upgrade.
				$quiz_builder_option            = get_option( 'learndash_settings_quizzes_builder', array() );
				$quiz_builder_option['enabled'] = 'yes';
				update_option( 'learndash_settings_quizzes_builder', $quiz_builder_option );

				if ( ( true !== $this->transient_data['mismatched'] ) && ( empty( $this->transient_data['quiz_id'] ) ) ) {
					$this->set_last_run_info( $data );
					$data['last_run_info'] = $this->get_last_run_info();
				}
				$this->remove_transient( $this->transient_key );
			}

			return $data;
		}

		/**
		 * Common function to query needed items.
		 *
		 * @since 2.6.0
		 *
		 * @param boolean $increment_paged default true to increment paged.
		 */
		protected function query_items( $increment_paged = true ) {
			global $wpdb;

			if ( ! isset( $this->transient_data['process_users'] ) ) {
				$this->transient_data['process_users'] = array();
			}

			if ( ! isset( $this->transient_data['mismatched'] ) ) {
				$this->transient_data['mismatched'] = false;
			}

			// Get total rows.
			if ( ( isset( $this->transient_data['quiz_id'] ) ) && ( ! empty( $this->transient_data['quiz_id'] ) ) ) {
				$pro_quiz_id = get_post_meta( $this->transient_data['quiz_id'], 'quiz_pro_id', true );
				if ( empty( $pro_quiz_id ) ) {
					$pro_quiz_id = learndash_get_setting( $this->transient_data['quiz_id'], 'quiz_pro' );
					if ( ! empty( $pro_quiz_id ) ) {
						update_post_meta( $this->transient_data['quiz_id'], 'quiz_pro_id', $pro_quiz_id );
					}
				}
				if ( ! empty( $pro_quiz_id ) ) {
					$quiz_questions = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							// phpcs:ignore
							'SELECT id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' WHERE online = %d AND quiz_id = %d',
							'1',
							$pro_quiz_id
						)
					);
					if ( ! empty( $quiz_questions ) ) {
						$this->transient_data['process_users'] = array_merge( $this->transient_data['process_users'], $quiz_questions );
						$this->transient_data['total_count']   = count( $this->transient_data['process_users'] );
					}
				}
			} elseif ( true === $this->transient_data['mismatched'] ) {
				$mismatched_questions = $this->get_mismatched_questions();
				if ( ! empty( $mismatched_questions ) ) {
					$this->transient_data['process_users'] = array_merge( $this->transient_data['process_users'], $mismatched_questions );
					$this->transient_data['total_count']   = count( $this->transient_data['process_users'] );
				}
			} else {

				if ( ! isset( $this->transient_data['total_count'] ) ) {
					$total_rows_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							// phpcs:ignore
							'SELECT COUNT(*) FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' WHERE online = %d',
							1
						)
					);
					if ( ! is_null( $total_rows_count ) ) {
						$this->transient_data['total_count'] = intval( $total_rows_count );
					}
				}

				if ( ( isset( $this->transient_data['total_count'] ) ) && ( ! empty( $this->transient_data['total_count'] ) ) ) {
					// Initialize or increment the current paged or items.
					if ( ! isset( $this->transient_data['paged'] ) ) {
						$this->transient_data['paged'] = 0;
					} else {
						if ( true === $increment_paged ) {
							$this->transient_data['paged'] = (int) $this->transient_data['paged'] + 1;
						}
					}

					$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							// phpcs:ignore
							'SELECT id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' WHERE online = %d ORDER BY quiz_id ASC, id ASC LIMIT %d OFFSET %d',
							'1',
							LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE,
							$this->transient_data['paged'] * LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE
						)
					);
					if ( ! empty( $rows ) ) {
						$this->transient_data['process_users'] = array_merge( $this->transient_data['process_users'], $rows );
					}
				}
			}
		}

		/**
		 * Determine if there are mismatched ProQuiz Questions not found as WP Posts (sfwd-question).
		 *
		 * @since 2.6.4
		 */
		public function get_mismatched_questions() {
			global $wpdb;

			$mismatched_pro_ids = array();

			$question_pro_ids = array();
			$pro_ids_paged    = 0;
			while ( true ) {
				$pro_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						// phpcs:ignore
						'SELECT id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' WHERE online = %d ORDER BY quiz_id, sort ASC LIMIT %d OFFSET %d',
						'1',
						LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE,
						$pro_ids_paged * LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE
					)
				);
				if ( ! empty( $pro_ids ) ) {
					$pro_ids          = array_map( 'intval', $pro_ids );
					$question_pro_ids = array_merge( $question_pro_ids, $pro_ids );
					$pro_ids_paged++;
				} else {
					break;
				}
			}

			$question_post_ids = array();
			if ( ! empty( $question_pro_ids ) ) {
				$post_ids_paged = 0;
				while ( true ) {
					$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT %d OFFSET %d",
							'question_pro_id',
							LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE,
							$post_ids_paged * LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE
						)
					);
					if ( ! empty( $post_ids ) ) {
						$post_ids          = array_map( 'intval', $post_ids );
						$question_post_ids = array_merge( $question_post_ids, $post_ids );
						$post_ids_paged++;
					} else {
						break;
					}
				}

				if ( ! empty( $question_post_ids ) ) {
					$mismatched_pro_ids = array_diff( $question_pro_ids, $question_post_ids );
					if ( ! empty( $mismatched_pro_ids ) ) {
						$mismatched_pro_ids = array_values( $mismatched_pro_ids );
					}
				}
			}

			return $mismatched_pro_ids;
		}


		/**
		 * Common function to build the returned data progress output.
		 *
		 * @since 2.6.0
		 *
		 * @param array $data Array of existing data elements.
		 *
		 * @return array or data.
		 */
		protected function build_progress_output( $data = array() ) {
			if ( isset( $this->transient_data['result_count'] ) ) {
				$data['result_count'] = intval( $this->transient_data['result_count'] );
			} else {
				$data['result_count'] = 0;
			}

			if ( isset( $this->transient_data['total_count'] ) ) {
				$data['total_count'] = intval( $this->transient_data['total_count'] );
			} else {
				$data['total_count'] = 0;
			}

			if ( ! empty( $data['total_count'] ) ) {
				$data['progress_percent'] = ceil( ( intval( $data['result_count'] ) / intval( $data['total_count'] ) ) * 100 );
			} else {
				$data['progress_percent'] = 100;
			}

			if ( 100 == $data['progress_percent'] ) {
					$progress_status       = __( 'Complete', 'learndash' );
					$data['progress_slug'] = 'complete';
			} else {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$progress_status       = __( 'In Progress', 'learndash' );
					$data['progress_slug'] = 'in-progress';
				} else {
					$progress_status       = __( 'Incomplete', 'learndash' );
					$data['progress_slug'] = 'in-complete';
				}
			}

			$data['progress_label'] = sprintf(
				// translators: placeholders: result count, total count, Questions label.
				esc_html_x( '%1$s: %2$d of %3$d %4$s', 'placeholders: progress status, result count, total count, Questions label', 'learndash' ),
				esc_html( $progress_status ),
				esc_html( $data['result_count'] ),
				esc_html( $data['total_count'] ),
				( $data['total_count'] > 1 ) ? LearnDash_Custom_Label::get_label( 'questions' ) : LearnDash_Custom_Label::get_label( 'question' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);

			if ( ( isset( $data['progress_percent'] ) ) && ( 100 == $data['progress_percent'] ) ) {
				if ( ! empty( $this->transient_data['skipped'] ) ) {
					$skipped_out  = '<div class="learndash-skipped">';
					$skipped_out .= sprintf(
						// translators: placeholder: Number of skipped Questions.
						esc_html_x( 'ProQuiz %1$s Skipped %2$d', 'placeholder: Number of skipped Questions', 'learndash' ),
						learndash_get_custom_label( 'questions' ),
						count( $this->transient_data['skipped'] )
					);
					$skipped_out .= '<ol>';
					foreach ( $this->transient_data['skipped'] as $skip_msg ) {
						$skipped_out .= '<li>' . $skip_msg . '</li>';
					}
					$skipped_out .= '</ol>';
					$skipped_out .= '</div>';

					$data['progress_label'] .= '<br /><br />' . $skipped_out;
				}
			}

			return $data;
		}

		/**
		 * Convert single user quiz attempts to Activity DB entries.
		 *
		 * @since 2.6.0
		 *
		 * @param int $question_pro_id ProQuiz Question ID to convert.
		 *
		 * @return boolean true if complete, false if not.
		 */
		protected function convert_proquiz_question( $question_pro_id = 0 ) {
			global $wpdb;

			$quiz_builder_option = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_Builder' );
			$question_pro_id     = absint( $question_pro_id );

			if ( ( empty( $question_pro_id ) ) || ( ! isset( $this->transient_data['current_user']['user_id'] ) ) || ( $question_pro_id !== $this->transient_data['current_user']['user_id'] ) ) {
				$this->transient_data['skipped'][ $question_pro_id ] = sprintf(
					// translators: placeholder: ProQuiz Question ID, question.
					esc_html_x( '[%1$d] Empty %2$s ProID given.', 'placeholder: ProQuiz Question ID, question', 'learndash' ),
					$question_pro_id,
					learndash_get_custom_label_lower( 'question' )
				);
				return false;
			}

			$question_pro_mapper = new WpProQuiz_Model_QuestionMapper();
			$question_pro        = $question_pro_mapper->fetch( $question_pro_id );
			if ( ( ! $question_pro ) || ( ! is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) ) {
				$this->transient_data['skipped'][ $question_pro_id ] = sprintf(
					// translators: placeholder: ProQuiz Question ID.
					esc_html_x( 'ProQuestion ID [%d] Model not found.', 'placeholder: ProQuiz Question ID', 'learndash' ),
					$question_pro_id
				);
				return true;
			}

			$quiz_pro_id = $question_pro->getQuizId();
			$quiz_pro_id = absint( $quiz_pro_id );
			if ( ( isset( $quiz_builder_option['shared_questions'] ) ) && ( 'yes' !== $quiz_builder_option['shared_questions'] ) ) {
				if ( empty( $quiz_pro_id ) ) {
					$this->transient_data['skipped'][ $question_pro_id ] = sprintf(
						// translators: placeholder: ProQuiz Question ID, ProQuiz Quiz ID.
						esc_html_x( 'ProQuestion ID [%1$d] ProQuiz ID empty [%2$d].', 'placeholder: ProQuiz Question ID, ProQuiz Quiz ID', 'learndash' ),
						$question_pro_id,
						$quiz_pro_id
					);
					return true;
				}

				$quiz_pro_mapper = new WpProQuiz_Model_QuizMapper();
				$quiz_pro        = $quiz_pro_mapper->fetch( $quiz_pro_id );
				if ( ( ! $quiz_pro ) || ( ! is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) ) {
					$this->transient_data['skipped'][ $question_pro_id ] = sprintf(
						// translators: placeholder: ProQuiz Question ID, ProQuiz Quiz ID.
						esc_html_x( 'ProQuestion ID [%1$d] ProQuiz ID Model not found [%2$d].', 'placeholder: ProQuiz Question ID, ProQuiz Quiz ID', 'learndash' ),
						$question_pro_id,
						$quiz_pro_id
					);
					return true;
				}
			}

			if ( ! empty( $quiz_pro_id ) ) {
				$quiz_post_ids = learndash_get_quiz_post_ids( $quiz_pro_id );
			} else {
				$quiz_post_ids = array();
			}

			$question_insert_post_id = learndash_get_question_post_by_pro_id( $question_pro_id );
			if ( empty( $question_insert_post_id ) ) {
				$question_insert_post                 = array();
				$question_insert_post['post_type']    = learndash_get_post_type_slug( 'question' );
				$question_insert_post['post_status']  = 'publish';
				$question_insert_post['post_title']   = $question_pro->getTitle();
				$question_insert_post['post_content'] = $question_pro->getQuestion();
				$question_insert_post['menu_order']   = absint( $question_pro->getSort() );

				/**
				 * We are getting the Quiz post to use the same post author and date since WPProQuiz
				 * does not track that information. This will be used when inserting a new Question.
				 */
				if ( ! empty( $quiz_post_ids ) ) {
					$quiz_post = get_post( $quiz_post_ids[0] );
					if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
						$question_insert_post['post_author'] = $quiz_post->post_author;
						$question_insert_post['post_date']   = $quiz_post->post_date;
					}
				}
				$question_insert_post    = wp_slash( $question_insert_post );
				$question_insert_post_id = wp_insert_post( $question_insert_post );
			} else {
				$update_post = array(
					'ID'           => $question_insert_post_id,
					'post_title'   => $question_pro->getTitle(),
					'post_content' => $question_pro->getQuestion(),
					'menu_order'   => absint( $question_pro->getSort() ),
				);
				$update_post = wp_slash( $update_post );
				wp_update_post( $update_post );
			}

			if ( ! empty( $question_insert_post_id ) ) {
				learndash_proquiz_sync_question_fields( $question_insert_post_id, $question_pro );

				if ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) {
					// Create the association between the question post and the quiz post(s).
					if ( ( ! empty( $quiz_pro_id ) ) && ( ! empty( $quiz_post_ids ) ) ) {
						foreach ( $quiz_post_ids as $idx => $quiz_post_id ) {
							learndash_set_quiz_questions_dirty( $quiz_post_id );

							if ( 0 === $idx ) {
								learndash_update_setting( $question_insert_post_id, 'quiz', absint( $quiz_post_id ) );
								$quiz_primary_post_id = learndash_get_quiz_primary_shared( $quiz_pro_id, false );
								if ( empty( $quiz_primary_post_id ) ) {
									update_post_meta( $quiz_post_id, 'quiz_pro_primary_' . $quiz_pro_id, $quiz_pro_id );
								}
							}
							add_post_meta( $question_insert_post_id, 'ld_quiz_' . absint( $quiz_post_id ), absint( $quiz_post_id ), true );
						}
					}
				}
			}

			return true;
		}

		// End of functions.
	}
}

add_action(
	'learndash_data_upgrades_init',
	function() {
		Learndash_Admin_Data_Upgrades_Quiz_Questions::add_instance();
	}
);
