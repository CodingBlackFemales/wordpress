<?php
/**
 * LearnDash Course Reports.
 *
 * @since 2.3.0
 * @package LearnDash\Course\Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Admin_Data_Reports_Courses' ) ) && ( class_exists( 'Learndash_Admin_Settings_Data_Reports' ) ) ) {

	/**
	 * Class LearnDash Course Reports.
	 *
	 * @since 2.3.0
	 * @uses Learndash_Admin_Settings_Data_Reports
	 */
	class Learndash_Admin_Data_Reports_Courses extends Learndash_Admin_Settings_Data_Reports {

		/**
		 * Instance
		 *
		 * @var object $instance Object instance of class.
		 */
		public static $instance = null;

		/**
		 * Data slug
		 *
		 * @var string $data_slug
		 */
		private $data_slug = 'user-courses';

		/**
		 * Data headers
		 *
		 * @var array $data_headers
		 */
		private $data_headers = array();

		/**
		 * Report filename
		 *
		 * @var string $report_filename
		 */
		private $report_filename = '';

		/**
		 * Transient key
		 *
		 * @var string $transient_key
		 */
		private $transient_key = '';

		/**
		 * Transient data
		 *
		 * @var array $transient_data
		 */
		private $transient_data = array();

		/**
		 * CSV Parse instance
		 *
		 * @var object $csv_parse
		 */
		private $csv_parse;

		/**
		 * Public constructor for class
		 *
		 * @since 2.3.0
		 */
		public function __construct() {
			self::$instance =& $this;

			add_filter( 'learndash_admin_report_register_actions', array( $this, 'register_report_action' ) );
		}

		/**
		 * Get the single instance of the class
		 *
		 * @since 2.3.0
		 */
		public static function getInstance() {
			if ( ! is_object( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Register Report Action
		 *
		 * @since 2.3.0
		 *
		 * @param array $report_actions Array of existing report actions.
		 *
		 * @return array
		 */
		public function register_report_action( $report_actions = array() ) {
			// Add ourselves to the upgrade actions.
			$report_actions[ $this->data_slug ] = array(
				'class'    => get_class( $this ),
				'instance' => $this,
				'slug'     => $this->data_slug,
			);

			$this->set_report_headers();

			return $report_actions;
		}

		/**
		 * Show Report Action
		 *
		 * @since 2.3.0
		 */
		public function show_report_action() {
			?>
			<tr id="learndash-data-reports-container-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-reports-container">
				<td class="learndash-data-reports-button-container" style="width: 20%">
					<button class="learndash-data-reports-button button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() ) ); ?>" data-slug="<?php echo esc_attr( $this->data_slug ); ?>">
					<?php
					printf(
					// translators: Export User Course Data Label.
						esc_html_x( 'Export User %s Data', 'Export User Course Data Label', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</button></td>
				<td class="learndash-data-reports-status-container" style="width: 80%">
					<div style="display:none;" class="meter learndash-data-reports-status">
						<div class="progress-meter">
							<span class="progress-meter-image"></span>
						</div>
						<div class="progress-label"></div>
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
		 * @since 2.3.0
		 *
		 * @param array $data Post data from AJAX call.
		 *
		 * @return array $data Post data from AJAX call
		 */
		public function process_report_action( $data = array() ) {
			global $wpdb;

			$this->init_process_times();

			if ( ! isset( $data['total_count'] ) ) {
				$data['total_count'] = 0;
			}

			if ( ! isset( $data['result_count'] ) ) {
				$data['result_count'] = 0;
			}

			if ( ! isset( $data['progress_percent'] ) ) {
				$data['progress_percent'] = 0;
			}

			if ( ! isset( $data['progress_label'] ) ) {
				$data['progress_label'] = '';
			}

			$_doing_init = false;

			require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( wp_verify_nonce( $data['nonce'], 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() ) ) {
					$this->transient_key = $this->data_slug . '_' . $data['nonce'];

					$this->csv_parse = new lmsParseCSV();

					// On the 'init' (the first call via AJAX we load up the transient with the user_ids).
					if ( ( isset( $data['init'] ) ) && ( 1 == $data['init'] ) ) {
						$_doing_init = true;

						unset( $data['init'] );

						$this->transient_data = array();

						if ( ( isset( $data['filters'] ) ) && ( ! empty( $data['filters'] ) ) ) {
							$this->transient_data = wp_parse_args( $this->transient_data, $data['filters'] );
						} else {

							$this->transient_data['activity_status'] = array( 'NOT_STARTED', 'IN_PROGRESS', 'COMPLETED' );

							if ( ( isset( $data['group_id'] ) ) && ( ! empty( $data['group_id'] ) ) ) {
								$this->transient_data['users_ids'] = learndash_get_groups_user_ids( intval( $data['group_id'] ) );
								$this->transient_data['posts_ids'] = learndash_group_enrolled_courses( intval( $data['group_id'] ) );
							} else {
								$this->transient_data['posts_ids'] = '';
								$this->transient_data['users_ids'] = learndash_get_report_user_ids();
							}

							$this->transient_data['activity_status'] = array( 'NOT_STARTED', 'IN_PROGRESS', 'COMPLETED' );
						}

						$this->transient_data['total_users'] = count( $this->transient_data['users_ids'] );

						$this->transient_data['course_step_totals'] = array();

						$this->set_report_filenames( $data );
						$this->report_filename = $this->transient_data['report_filename'];

						$data['report_download_link'] = $this->transient_data['report_url'];
						$data['total_count']          = $this->transient_data['total_users'];

						// Clear out any previous file.
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
						$reports_fp = fopen( $this->report_filename, 'w' );
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
						fclose( $reports_fp );

						$this->set_option_cache( $this->transient_key, $this->transient_data );

						$this->send_report_headers_to_csv();

					} else {
						$this->transient_data  = $this->get_transient( $this->transient_key );
						$this->report_filename = $this->transient_data['report_filename'];
					}

					if ( ! empty( $this->transient_data['users_ids'] ) ) {

						// If we are doing the initial 'init' then we return so we can show the progress meter.
						if ( true !== (bool) $_doing_init ) {

							$course_query_args = array(
								'orderby'  => 'title',
								'order'    => 'ASC',
								'nopaging' => true,
							);

							$activity_query_args = array(
								'post_types'      => 'sfwd-courses',
								'activity_types'  => 'course',
								'activity_status' => $this->transient_data['activity_status'],
								'orderby_order'   => 'users.ID, posts.post_title',
								'date_format'     => 'F j, Y H:i:s',
								'per_page'        => '',
								'time_start'      => '',
								'time_end'        => '',
							);

							$course_progress_data = array();

							foreach ( $this->transient_data['users_ids'] as $user_id_idx => $user_id ) {

								unset( $this->transient_data['users_ids'][ $user_id_idx ] );

								$report_user = get_user_by( 'id', $user_id );
								if ( false !== $report_user ) {
									if ( ( isset( $this->transient_data['course_ids'] ) ) && ( ! empty( $this->transient_data['course_ids'] ) ) ) {
										$post_ids = $this->transient_data['course_ids'];

									} elseif ( ( isset( $this->transient_data['posts_ids'] ) ) && ( ! empty( $this->transient_data['posts_ids'] ) ) ) {
										$post_ids = $this->transient_data['posts_ids'];

									} else {
										$post_ids = learndash_user_get_enrolled_courses( intval( $user_id ), $course_query_args, true );
									}

									if ( ! empty( $post_ids ) ) {

										$activity_query_args['user_ids'] = array( $user_id );
										$activity_query_args['post_ids'] = $post_ids;

										if ( ( isset( $this->transient_data['time_start'] ) ) && ( ! empty( $this->transient_data['time_start'] ) ) ) {
											$activity_query_args['time_start'] = esc_attr( $this->transient_data['time_start'] );
										}

										if ( ( isset( $this->transient_data['time_end'] ) ) && ( ! empty( $this->transient_data['time_end'] ) ) ) {
											$activity_query_args['time_end'] = esc_attr( $this->transient_data['time_end'] );
										}

										$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
										if ( ! empty( $user_courses_reports['results'] ) ) {
											foreach ( $user_courses_reports['results'] as $result ) {
												$row = array();

												foreach ( $this->data_headers as $header_key => $header_data ) {

													if ( ( isset( $header_data['display'] ) ) && ( ! empty( $header_data['display'] ) ) && ( is_callable( $header_data['display'] ) ) ) {
														$row[ $header_key ] = call_user_func_array(
															$header_data['display'],
															array(
																$header_data['default'],
																$header_key,
																$result,
																$report_user,
															)
														);
													} elseif ( ( isset( $header_data['default'] ) ) && ( ! empty( $header_data['default'] ) ) ) {
														$row[ $header_key ] = $header_data['default'];
													} else {
														$row[ $header_key ] = '';
													}
												}

												if ( ! empty( $row ) ) {
													$course_progress_data[] = $row;
												}
											}
										} else {
											if ( ( is_array( $this->transient_data['activity_status'] ) ) && ( count( $this->transient_data['activity_status'] ) > 1 ) && ( in_array( 'NOT_STARTED', $this->transient_data['activity_status'], true ) ) ) {
												$row = array();

												foreach ( $this->data_headers as $header_key => $header_data ) {

													if ( ( isset( $header_data['display'] ) ) && ( ! empty( $header_data['display'] ) ) && ( is_callable( $header_data['display'] ) ) ) {
														$row[ $header_key ] = call_user_func_array(
															$header_data['display'],
															array(
																$header_data['default'],
																$header_key,
																new stdClass(),
																$report_user,
															)
														);
													} elseif ( ( isset( $header_data['default'] ) ) && ( ! empty( $header_data['default'] ) ) ) {
														$row[ $header_key ] = $header_data['default'];
													} else {
														$row[ $header_key ] = '';
													}
												}

												if ( ! empty( $row ) ) {
													$course_progress_data[] = $row;
												}
											}
										}
									}
								}

								if ( $this->out_of_timer() ) {
									break;
								}
							}

							$this->set_option_cache( $this->transient_key, $this->transient_data );

							if ( ! empty( $course_progress_data ) ) {
								$this->csv_parse->file            = $this->report_filename;
								$this->csv_parse->output_filename = $this->report_filename;

								// legacy.
								/** This filter is documented in includes/class-ld-lms.php */
								$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, 'courses' );

								/** This filter is documented in includes/class-ld-lms.php */
								$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, $this->data_slug );

								/**
								 * Filters CSV data.
								 *
								 * @since 2.4.7
								 *
								 * @param array  $csv_data  An array of CSV data.
								 * @param string $data_slug The slug of the data in the CSV.
								 */
								$course_progress_data = apply_filters( 'learndash_csv_data', $course_progress_data, $this->data_slug );

								$save_ret = $this->csv_parse->save( $this->report_filename, $course_progress_data, true, wp_list_pluck( $this->data_headers, 'label' ) );

							}
						}

						$data['result_count']     = $data['total_count'] - count( $this->transient_data['users_ids'] );
						$data['progress_percent'] = ( $data['result_count'] / $data['total_count'] ) * 100;
						// translators: placeholders: result count, total count.
						$data['progress_label'] = sprintf( esc_html_x( '%1$d of %2$s Users', 'placeholders: result count, total count', 'learndash' ), $data['result_count'], $data['total_count'] );

					}
				}
			}

			return $data;
		}

		/**
		 * Set Report Headers
		 *
		 * @since 2.3.0
		 */
		public function set_report_headers() {
			$this->data_headers              = array();
			$this->data_headers['user_id']   = array(
				'label'   => esc_html__( 'user_id', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['user_name'] = array(
				'label'   => esc_html__( 'name', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['user_email'] = array(
				'label'   => esc_html__( 'email', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['course_id']    = array(
				'label'   => esc_html__( 'course_id', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['course_title'] = array(
				'label'   => esc_html__( 'course_title', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['course_steps_completed'] = array(
				'label'   => esc_html__( 'steps_completed', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['course_steps_total']     = array(
				'label'   => esc_html__( 'steps_total', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['course_completed']       = array(
				'label'   => esc_html__( 'course_completed', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['course_completed_on']    = array(
				'label'   => esc_html__( 'course_completed_on', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			/**
			 * Filters data reports headers.
			 *
			 * @since 2.3.0
			 *
			 * @param array  $data_headers An array of data report header details.
			 * @param string $data_slug    The slug of the data in the CSV.
			 */
			$this->data_headers = apply_filters( 'learndash_data_reports_headers', $this->data_headers, $this->data_slug );
		}

		/**
		 * Send Report Headers to CSV
		 *
		 * @since 2.3.0
		 */
		public function send_report_headers_to_csv() {
			if ( ! empty( $this->data_headers ) ) {
				$this->csv_parse->file            = $this->report_filename;
				$this->csv_parse->output_filename = $this->report_filename;

				// legacy.
				/** This filter is documented in includes/class-ld-lms.php */
				$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, 'courses' );

				/** This filter is documented in includes/class-ld-lms.php */
				$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, $this->data_slug );

				/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
				$this->data_headers = apply_filters( 'learndash_csv_data', $this->data_headers, $this->data_slug );

				$this->csv_parse->save( $this->report_filename, array(), false, wp_list_pluck( $this->data_headers, 'label' ) );
			}
		}

		/**
		 * Set Report Filenames
		 *
		 * @since 2.3.0
		 *
		 * @param array $data Report data.
		 */
		public function set_report_filenames( $data ) {
			$wp_upload_dir = wp_upload_dir();

			$ld_file_part = '/learndash/reports/learndash_reports_' . str_replace( array( 'ld_data_reports_', '-' ), array( '', '_' ), $this->transient_key ) . '.csv';

			$ld_wp_upload_filename = $wp_upload_dir['basedir'] . $ld_file_part;

			if ( ! file_exists( dirname( $ld_wp_upload_filename ) ) ) {
				if ( wp_mkdir_p( dirname( $ld_wp_upload_filename ) ) === false ) {
					$data['error_message'] = esc_html__( 'ERROR: Cannot create working folder. Check that the parent folder is writable', 'learndash' ) . ' ' . $ld_wp_upload_filename;
					return $data;
				}
			}

			learndash_put_directory_index_file( trailingslashit( dirname( $ld_wp_upload_filename ) ) . 'index.php' );

			Learndash_Admin_File_Download_Handler::register_file_path(
				'learndash-reports',
				dirname( $ld_wp_upload_filename )
			);

			Learndash_Admin_File_Download_Handler::try_to_protect_file_path(
				dirname( $ld_wp_upload_filename )
			);

			/**
			 * Filters data report file path.
			 *
			 * @since 2.4.7
			 *
			 * @param string $report_file_name The name of the report file path.
			 * @param string $data_slug       The slug of the data in the CSV.
			 */
			$this->transient_data['report_filename'] = apply_filters( 'learndash_report_filename', $ld_wp_upload_filename, $this->data_slug );

			$this->transient_data['report_url'] = add_query_arg(
				array(
					'data-slug'          => $this->data_slug,
					'data-nonce'         => $data['nonce'],
					'ld-report-download' => 1,
				),
				admin_url()
			);
		}

		/**
		 * Handles display formatting of report column value.
		 *
		 * @since 2.3.0
		 *
		 * @param int|string $column_value Report column value.
		 * @param string     $column_key   Column key.
		 * @param object     $report_item  Report Item.
		 * @param WP_User    $report_user  WP_User object.
		 *
		 * @return mixed $column_value;
		 */
		public function report_column( $column_value, $column_key, $report_item, $report_user ) {

			if ( ( property_exists( $report_item, 'post_id' ) ) && ( ! empty( $report_item->post_id ) ) ) {
				$course_id = absint( $report_item->post_id );
			} else {
				$course_id = 0;
			}

			switch ( $column_key ) {
				case 'user_id':
					if ( $report_user instanceof WP_User ) {
						$column_value = $report_user->ID;
					}
					break;

				case 'user_name':
					if ( $report_user instanceof WP_User ) {
						$column_value = $report_user->display_name;
						$column_value = str_replace( '’', "'", $column_value );

					}
					break;

				case 'user_email':
					if ( $report_user instanceof WP_User ) {
						$column_value = $report_user->user_email;
					}
					break;

				case 'course_id':
					$column_value = $course_id;
					break;

				case 'course_title':
					if ( property_exists( $report_item, 'post_title' ) ) {
						$column_value = $report_item->post_title;
						$column_value = str_replace( '’', "'", $column_value );
					}
					break;

				case 'course_steps_total':
					$column_value = '0';

					if ( ! empty( $course_id ) ) {
						if ( isset( $this->transient_data['course_step_totals'][ $course_id ] ) ) {
							$column_value = $this->transient_data['course_step_totals'][ $course_id ];
						} else {
							$column_value = learndash_get_course_steps_count( $course_id );
							$this->transient_data['course_step_totals'][ $course_id ] = absint( $column_value );
						}
					}
					break;

				case 'course_steps_completed':
					$column_value = '0';

					if ( ! empty( $course_id ) ) {
						// First check if the user previously completed the course.
						$user_completed_course = false;
						$completed_on          = get_user_meta( $report_item->user_id, 'course_completed_' . $course_id, true );
						if ( ! empty( $completed_on ) ) {
							$user_completed_course = true;
						} elseif ( property_exists( $report_item, 'activity_status' ) ) {
							if ( true === $report_item->activity_status ) {
								$user_completed_course = true;
							}
						}

						if ( true === $user_completed_course ) {
							// IF the user completed the course we set the user's completed steps to the number of steps in the course.
							if ( isset( $this->transient_data['course_step_totals'][ $course_id ] ) ) {
								$column_value = $this->transient_data['course_step_totals'][ $course_id ];
							} else {
								$column_value = learndash_get_course_steps_count( $course_id );
								$this->transient_data['course_step_totals'][ $course_id ] = absint( $column_value );
							}
						} else {
							$column_value = learndash_course_get_completed_steps( $report_item->user_id, $course_id );
							$column_value = absint( $column_value );
						}
					}
					break;

				case 'course_completed':
					$column_value = esc_html_x( 'NO', 'Course Complete Report label: NO', 'learndash' );

					if ( ! empty( $course_id ) ) {
						$completed_on = get_user_meta( $report_item->user_id, 'course_completed_' . $course_id, true );
						if ( ! empty( $completed_on ) ) {
							$column_value = esc_html_x( 'YES', 'Course Complete Report label: YES', 'learndash' );
						} elseif ( property_exists( $report_item, 'activity_status' ) ) {
							if ( true === (bool) $report_item->activity_status ) {
								$column_value = esc_html_x( 'YES', 'Course Complete Report label: YES', 'learndash' );
							}
						}
					}
					break;

				case 'course_completed_on':
					if ( ! empty( $course_id ) ) {
						$completed_on = get_user_meta( $report_item->user_id, 'course_completed_' . $course_id, true );
						if ( ! empty( $completed_on ) ) {
							return learndash_adjust_date_time_display( $completed_on, 'Y-m-d' );
						} elseif ( property_exists( $report_item, 'activity_status' ) ) {
							if ( true === (bool) $report_item->activity_status ) {
								if ( ( property_exists( $report_item, 'activity_completed' ) ) && ( ! empty( $report_item->activity_completed ) ) ) {
									return learndash_adjust_date_time_display( $report_item->activity_completed, 'Y-m-d' );
								}
							}
						}
					}
					break;

				default:
					break;
			}
			/**
			 * Filters report column data.
			 *
			 * @since 2.4.7
			 *
			 * @param int|string $column_value Report column value.
			 * @param string     $column_key   Column key.
			 * @param object     $report_item  Report Item.
			 * @param WP_User    $report_user  WP_User object.
			 * @param string     $data_slug    The slug of the data in the CSV.
			 */
			return apply_filters( 'learndash_report_column_item', $column_value, $column_key, $report_item, $report_user, $this->data_slug );
		}


		// End of functions.
	}
}


