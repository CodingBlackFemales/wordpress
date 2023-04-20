<?php
/**
 * LearnDash Quiz Reports.
 *
 * @since 2.3.0
 *
 * @package LearnDash\Quiz\Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Admin_Data_Reports_Quizzes' ) ) && ( class_exists( 'Learndash_Admin_Settings_Data_Reports' ) ) ) {

	/**
	 * Class LearnDash Quiz Reports.
	 *
	 * @since 2.3.0
	 * @uses Learndash_Admin_Settings_Data_Reports
	 */
	class Learndash_Admin_Data_Reports_Quizzes extends Learndash_Admin_Settings_Data_Reports {

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
		private $data_slug = 'user-quizzes';

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
							// translators: Export User Quiz Data Label.
							esc_html_x( 'Export User %s Data', 'Export User Quiz Data Label', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
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
		 * @param  array $data Post data from AJAX call.
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

			$this->csv_parse = new lmsParseCSV();

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( wp_verify_nonce( $data['nonce'], 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() ) ) {
					$this->transient_key = $this->data_slug . '_' . $data['nonce'];

					// On the 'init' (the first call via AJAX we load up the transient with the user_ids).
					if ( ( isset( $data['init'] ) ) && ( 1 == $data['init'] ) ) {
						$_doing_init = true;

						unset( $data['init'] );

						$this->transient_data = array();

						if ( ( isset( $data['filters'] ) ) && ( ! empty( $data['filters'] ) ) ) {

							$this->transient_data = wp_parse_args( $this->transient_data, $data['filters'] );
						} else {

							if ( ( isset( $data['group_id'] ) ) && ( ! empty( $data['group_id'] ) ) ) {
								$this->transient_data['users_ids']  = learndash_get_groups_user_ids( intval( $data['group_id'] ) );
								$this->transient_data['course_ids'] = learndash_group_enrolled_courses( intval( intval( $data['group_id'] ) ) );
								if ( empty( $this->transient_data['course_ids'] ) ) {
									return $data;
								}
							} else {
								$this->transient_data['posts_ids'] = '';
								$this->transient_data['users_ids'] = learndash_get_report_user_ids();
							}
						}

						$this->transient_data['total_users'] = count( $this->transient_data['users_ids'] );

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
						if ( true !== $_doing_init ) {

							$activity_query_args = array(
								'post_types'      => 'sfwd-quiz',
								'activity_types'  => 'quiz',
								'activity_status' => array( 'IN_PROGRESS', 'COMPLETED' ),
								'orderby_order'   => 'users.display_name, posts.post_title ASC',
								'date_format'     => 'F j, Y H:i:s',
								'per_page'        => '',
								'time_start'      => '',
								'time_end'        => '',
							);

							$course_progress_data = array();

							foreach ( $this->transient_data['users_ids'] as $user_id_idx => $user_id ) {

								unset( $this->transient_data['users_ids'][ $user_id_idx ] );
								$this->set_option_cache( $this->transient_key, $this->transient_data );

								$report_user = get_user_by( 'id', $user_id );
								if ( false !== $report_user ) {

									$activity_query_args['user_ids'] = array( $user_id );

									if ( ( isset( $this->transient_data['posts_ids'] ) ) && ( ! empty( $this->transient_data['posts_ids'] ) ) ) {
										$post_ids                        = $this->transient_data['posts_ids'];
										$activity_query_args['post_ids'] = $post_ids;
									}

									if ( ( isset( $this->transient_data['course_ids'] ) ) && ( ! empty( $this->transient_data['course_ids'] ) ) ) {
										$activity_query_args['course_ids'] = $this->transient_data['course_ids'];
									}

									if ( ( isset( $this->transient_data['time_start'] ) ) && ( ! empty( $this->transient_data['time_start'] ) ) ) {
										$activity_query_args['time_start'] = esc_attr( $this->transient_data['time_start'] );
									}

									if ( ( isset( $this->transient_data['time_end'] ) ) && ( ! empty( $this->transient_data['time_end'] ) ) ) {
										$activity_query_args['time_end'] = esc_attr( $this->transient_data['time_end'] );
									}

									$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
									if ( ! empty( $user_courses_reports['results'] ) ) {
										foreach ( $user_courses_reports['results'] as $result ) {

											/**
											 * Added LD 3.2.0 - PP-204
											 * Missing Activity meta data. As a secondary pull from the user quiz meta.
											 */
											if ( ( ! property_exists( $result, 'activity_meta' ) ) || ( empty( $result->activity_meta ) ) ) {
												if ( ! empty( $user_quiz_meta ) ) { // @phpstan-ignore-line -- Not sure where this came from but don't want to remove just yet.
													foreach ( $user_quiz_meta as $user_meta_item ) {
														if ( ( absint( $result->post_id ) === absint( $user_meta_item['quiz'] ) ) && ( absint( $result->activity_updated ) === absint( $user_meta_item['time'] ) ) && ( absint( $result->activity_started ) === absint( $user_meta_item['started'] ) ) ) {
															$result->activity_meta = $user_meta_item;
															break;
														}
													}
												}
											}

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
									}
								}

								if ( $this->out_of_timer() ) {
									break;
								}
							}

							if ( ! empty( $course_progress_data ) ) {
								$this->csv_parse->file            = $this->report_filename;
								$this->csv_parse->output_filename = $this->report_filename;

								// legacy.
								/** This filter is documented in includes/class-ld-lms.php */
								$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, 'quizzes' );

								/** This filter is documented in includes/class-ld-lms.php */
								$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, $this->data_slug );
								/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
								$course_progress_data = apply_filters( 'learndash_csv_data', $course_progress_data, $this->data_slug );

								$this->csv_parse->save( $this->report_filename, $course_progress_data, true, wp_list_pluck( $this->data_headers, 'label' ) );
							}
						}

						$data['result_count']     = $data['total_count'] - count( $this->transient_data['users_ids'] );
						$data['progress_percent'] = ( $data['result_count'] / $data['total_count'] ) * 100;
						$data['progress_label']   = sprintf(
							// translators: placeholders: result count, total count.
							esc_html_x( '%1$d of %2$s Users', 'placeholders: result count, total count', 'learndash' ),
							$data['result_count'],
							$data['total_count']
						);
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

			$this->data_headers['quiz_id']    = array(
				'label'   => esc_html__( 'quiz_id', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['quiz_title'] = array(
				'label'   => esc_html__( 'quiz_title', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_score'] = array(
				'label'   => esc_html__( 'score', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['quiz_total'] = array(
				'label'   => esc_html__( 'total', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);
			$this->data_headers['quiz_date']  = array(
				'label'   => esc_html__( 'date', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_points'] = array(
				'label'   => esc_html__( 'points', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_points_total'] = array(
				'label'   => esc_html__( 'points_total', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_percentage'] = array(
				'label'   => esc_html__( 'percentage', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_time_spent'] = array(
				'label'   => esc_html__( 'time_spent', 'learndash' ),
				'default' => '0',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['quiz_passed'] = array(
				'label'   => esc_html__( 'passed', 'learndash' ),
				'default' => esc_html_x( 'NO', 'Quiz Passed Report label: NO', 'learndash' ),
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['course_id'] = array(
				'label'   => esc_html__( 'course_id', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			$this->data_headers['course_title'] = array(
				'label'   => esc_html__( 'course_title', 'learndash' ),
				'default' => '',
				'display' => array( $this, 'report_column' ),
			);

			/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
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
				/** This filter is documented in includes/class-ld-lms.php */
				$this->csv_parse = apply_filters( 'learndash_csv_object', $this->csv_parse, 'quizzes' );
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

			/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
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

				case 'quiz_id':
					if ( property_exists( $report_item, 'post_id' ) ) {
						$column_value = $report_item->post_id;
					}
					break;

				case 'quiz_title':
					if ( property_exists( $report_item, 'post_title' ) ) {
						$column_value = $report_item->post_title;
						$column_value = str_replace( '’', "'", $column_value );
					}
					break;

				case 'quiz_rank':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['rank'] ) ) && ( ! empty( $report_item->activity_meta['rank'] ) ) ) {
							$column_value = $report_item->activity_meta['rank'];
						}
					}
					break;

				case 'quiz_score':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['score'] ) ) && ( ! empty( $report_item->activity_meta['score'] ) ) ) {
							$column_value = $report_item->activity_meta['score'];
						} else {
							$column_value = '0';
						}
					}
					break;

				case 'quiz_total':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['question_show_count'] ) ) && ( ! empty( $report_item->activity_meta['question_show_count'] ) ) ) {
							$column_value = $report_item->activity_meta['question_show_count'];
						} elseif ( ( isset( $report_item->activity_meta['count'] ) ) && ( ! empty( $report_item->activity_meta['count'] ) ) ) {
							$column_value = $report_item->activity_meta['count'];
						}
					}
					break;

				case 'quiz_date':
					if ( ( property_exists( $report_item, 'activity_completed' ) ) && ( ! empty( $report_item->activity_completed ) ) ) {
						$column_value = learndash_adjust_date_time_display( $report_item->activity_completed, 'Y-m-d' );
					}
					break;

				case 'quiz_points':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['points'] ) ) && ( ! empty( $report_item->activity_meta['points'] ) ) ) {
							$column_value = $report_item->activity_meta['points'];
						}
					}
					break;

				case 'quiz_points_total':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['total_points'] ) ) && ( ! empty( $report_item->activity_meta['total_points'] ) ) ) {
							$column_value = $report_item->activity_meta['total_points'];
						}
					}
					break;

				case 'quiz_percentage':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['percentage'] ) ) && ( ! empty( $report_item->activity_meta['percentage'] ) ) ) {
							$column_value = number_format( round( floatval( $report_item->activity_meta['percentage'] ), 2 ), 2 );
						}
					}
					break;

				case 'quiz_time_spent':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['timespent'] ) ) && ( ! empty( $report_item->activity_meta['timespent'] ) ) ) {

							$timespent    = abs( round( $report_item->activity_meta['timespent'] ) );
							$column_value = '';

							if ( $timespent > 86400 ) {
								if ( ! empty( $column_value ) ) { // @phpstan-ignore-line
									$column_value .= ' ';
								}
								$column_value .= floor( $timespent / 86400 ) . 'd';
								$timespent    %= 86400;
							}

							if ( $timespent > 3600 ) {
								if ( ! empty( $column_value ) ) {
									$column_value .= ' ';
								}
								$column_value .= floor( $timespent / 3600 ) . 'h';
								$timespent    %= 3600;
							}

							if ( $timespent > 60 ) {
								if ( ! empty( $column_value ) ) {
									$column_value .= ' ';
								}
								$column_value .= floor( $timespent / 60 ) . 'm';
								$timespent    %= 60;
							}

							if ( $timespent > 0 ) {
								if ( ! empty( $column_value ) ) {
									$column_value .= ' ';
								}
								$column_value .= $timespent . 's';
							}
						}
					}
					break;

				case 'quiz_passed':
					if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
						if ( ( isset( $report_item->activity_meta['pass'] ) ) && ( 1 == $report_item->activity_meta['pass'] ) ) {
							$column_value = esc_html_x( 'YES', 'Quiz Passed Report label: YES', 'learndash' );
						}
					}
					break;

				case 'course_id':
					if ( property_exists( $report_item, 'activity_course_id' ) ) {
						$course_id = intval( $report_item->activity_course_id );
						if ( ! empty( $course_id ) ) {
							$column_value = $course_id;
						} else {
							$column_value = '';
						}
					}
					break;

				case 'course_title':
					if ( property_exists( $report_item, 'activity_course_id' ) ) {
						$course_id = intval( $report_item->activity_course_id );
						if ( ! empty( $course_id ) ) {
							$column_value = get_the_title( $course_id );
						} else {
							$column_value = '';
						}
					}
					break;

				default:
					break;
			}

			/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
			return apply_filters( 'learndash_report_column_item', $column_value, $column_key, $report_item, $report_user );
		}

		// End of functions.
	}
}


