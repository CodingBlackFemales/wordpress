<?php
/**
 * LearnDash Course Reports.
 *
 * @since 2.3.0
 * @package LearnDash\Course\Reports
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Data_Reports_Courses' )
	|| ! class_exists( 'Learndash_Admin_Settings_Data_Reports' )
) {
	return;
}

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
	private $data_headers = [];

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
	private $transient_data = [];

	/**
	 * CSV Parse instance
	 *
	 * @var lmsParseCSV $csv_parse
	 */
	private $csv_parse;

	/**
	 * Public constructor for class
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		self::$instance =& $this;

		add_filter( 'learndash_admin_report_register_actions', [ $this, 'register_report_action' ] );
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
	public function register_report_action( $report_actions = [] ) {
		// Add ourselves to the upgrade actions.
		$report_actions[ $this->data_slug ] = array(
			'class'    => get_class( $this ),
			'instance' => $this,
			'slug'     => $this->data_slug,
			'text'     => sprintf(
				// Translators: placeholders: Custom Course Label.
				__( 'Export User %s Data', 'learndash' ),
				learndash_get_custom_label( 'course' )
			),
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
	 * @since 4.25.6 The internal processing logic has been updated to be in sync with the Reporting Block results.
	 *
	 * @param array<string,mixed> $data Post data from AJAX call.
	 * @phpstan-param array{nonce?: string, init?: int, filters?: array<string, mixed>, group_id?: int, time_start?: string, time_end?: string, course_ids?: array<int>, posts_ids?: array<int>, users_ids?: array<int>, slug?: string, error_message?: string} $data
	 *
	 * @return array<string, mixed> Post data from AJAX call.
	 */
	public function process_report_action( $data = [] ) {
		global $wpdb;

		// Initialize default values for progress tracking.
		$result = [];

		// Load the CSV parsing library for report generation.
		require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

		// Verify nonce for security and process the request.
		if ( empty( $data['nonce'] ) ) {
			return $result;
		}

		$nonce = $data['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() ) ) {
			return $result;
		}

		// Generate unique transient key for this report session.
		$this->transient_key = $this->data_slug . '_' . $nonce;

		// Initialize CSV parser instance.
		$this->csv_parse = new lmsParseCSV();

		// Handle initialization phase - first AJAX call sets up the report.
		if (
			isset( $data['init'] )
			&& 1 === intval( (string) $data['init'] )
		) {
			$result = array_merge(
				$data,
				$this->initialize_report_data( $data )
			);

			// Remove init flag from result.
			unset( $result['init'] );
		} else {
			// Subsequent calls: retrieve cached data from previous initialization.
			$this->transient_data  = $this->get_transient( $this->transient_key );
			$this->report_filename = $this->transient_data['report_filename'];

			$result = array_merge(
				$data,
				$this->fetch_and_save_activity_data()
			);
		}

		// Calculate progress percentage for UI display.
		$result['progress_percent'] = $result['total_count'] > 0
			? ( $result['result_count'] / $result['total_count'] ) * 100
			: 100;

		// Generate human-readable progress label.
		$result_count             = $result['result_count'];
		$total_count              = $result['total_count'];
		$result['progress_label'] = sprintf(
			// translators: placeholders: result count, total count.
			esc_html_x( '%1$d of %2$s results', 'placeholders: result count, total count', 'learndash' ),
			is_numeric( $result_count ) ? (int) $result_count : 0,
			is_numeric( $total_count ) ? (string) $total_count : '0'
		);

		return $result;
	}

	/**
	 * Initialize report data and set up the report file.
	 *
	 * @since 4.25.6
	 *
	 * @param array<string,mixed> $data The input data array.
	 * @phpstan-param array{nonce?: string, init?: int, filters?: array<string, mixed>, group_id?: int, time_start?: string, time_end?: string, course_ids?: array<int>, posts_ids?: array<int>, users_ids?: array<int>, slug?: string, error_message?: string} $data
	 *
	 * @return array<string, mixed> The data array with initialization results.
	 */
	private function initialize_report_data( array $data ): array {
		// Initialize transient data storage.
		$this->transient_data = [
			'nonce' => $data['nonce'] ?? '',
		];

		// When exporting from a group context, restrict to that group's users and courses.
		if ( ! empty( $data['group_id'] ) ) {
			$group_id = Cast::to_int( $data['group_id'] );

			$this->transient_data['user_ids']   = learndash_get_groups_user_ids( $group_id );
			$this->transient_data['course_ids'] = learndash_group_enrolled_courses( $group_id );
		}

		// Use custom filters when they are provided in the request.
		$this->transient_data = wp_parse_args(
			$this->transient_data,
			ld_propanel_load_post_data( $data )
		);

		// Generate report filename and download URL.
		$this->set_report_filenames( $data );
		$this->report_filename = $this->transient_data['report_filename'];

		// Clear any existing report file to start fresh.
		// phpcs:ignore WordPress.WP.AlternativeFunctions  -- Legacy usage, do not want to change now.
		$reports_fp = fopen( $this->report_filename, 'w' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions  -- Legacy usage, do not want to change now.
		fclose( $reports_fp );

		// Cache the transient data for subsequent requests.
		$this->set_option_cache( $this->transient_key, $this->transient_data );

		// Write CSV headers to the file.
		$this->send_report_headers_to_csv();

		/**
		 * Return progress data for initialization phase.
		 *
		 * We are setting result count and total count explicitly here in order to force
		 * another request. This is in order to circumvent the old pagination system that is no longer used.
		 */
		return [
			'result_count'         => 1,
			'total_count'          => 2,
			'report_download_link' => $this->transient_data['report_url'],
		];
	}

	/**
	 * Fetch and process activity data for the report.
	 *
	 * @since 4.25.6
	 *
	 * @return array<string, mixed> The data array with processed results.
	 */
	private function fetch_and_save_activity_data(): array {
		// Initialize array to store processed course progress data.
		$course_progress_data = [];

		// Build activity query arguments for fetching course progress data.
		$activity_query_args = [
			'post_types'      => 'sfwd-courses',
			'activity_types'  => 'course',
			'activity_status' => '',
			'orderby_order'   => 'users.display_name, posts.post_title',
		];

		// Merge with cached filter data from initialization.
		$activity_query_args = wp_parse_args( $this->transient_data, $activity_query_args );

		// Be sure these expected fields are set.
		$post_data_args = $this->transient_data;

		if (
			! isset( $post_data_args['filters'] )
			|| ! is_array( $post_data_args['filters'] )
		) {
			$post_data_args['filters'] = [];
		}

		if (
			! isset( $post_data_args['filters']['reporting_pager'] )
			|| ! is_array( $post_data_args['filters']['reporting_pager'] )
		) {
			$post_data_args['filters']['reporting_pager'] = [
				'per_page'     => 0,
				'current_page' => 1,
			];
		}

		$activity_query_args = ld_propanel_load_activity_query_args( $activity_query_args, $post_data_args );

		// Remove pagination from query (not paginated display).
		$activity_query_args['per_page'] = 0;
		$activity_query_args['paged']    = 1;

		// Apply course ID filters if available.
		if (
			isset( $this->transient_data['course_ids'] )
			&& ! empty( $this->transient_data['course_ids'] )
		) {
			$activity_query_args['post_ids'] = $this->transient_data['course_ids'];
		} elseif (
			isset( $this->transient_data['posts_ids'] )
			&& ! empty( $this->transient_data['posts_ids'] )
		) {
			$activity_query_args['post_ids'] = $this->transient_data['posts_ids'];
		}

		// Apply time-based filters if specified.
		if (
			isset( $this->transient_data['time_start'] )
			&& ! empty( $this->transient_data['time_start'] )
		) {
			$activity_query_args['time_start'] = esc_attr( $this->transient_data['time_start'] );
		}

		if (
			isset( $this->transient_data['time_end'] )
			&& ! empty( $this->transient_data['time_end'] )
		) {
			$activity_query_args['time_end'] = esc_attr( $this->transient_data['time_end'] );
		}

		// Apply admin user restrictions and user count optimizations.
		$activity_query_args = ld_propanel_adjust_admin_users( $activity_query_args );
		$activity_query_args = ld_propanel_convert_fewer_users( $activity_query_args );

		// Execute the activity query to get course progress data.
		$user_courses_reports = learndash_reports_get_activity( $activity_query_args );

		// Process query results and build report rows.
		if ( ! empty( $user_courses_reports['results'] ) ) {
			foreach ( $user_courses_reports['results'] as $result ) {
				$row = $this->build_report_row( $result );
				if ( ! empty( $row ) ) {
					$course_progress_data[] = $row;
				}
			}
		}

		// Save results to file.
		$this->save_csv_data( $course_progress_data );

		// Update cached data with any changes.
		$this->set_option_cache( $this->transient_key, $this->transient_data );

		return [
			'result_count' => count( $course_progress_data ),
			'total_count'  => count( $course_progress_data ),
		];
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

		// Create a unique suffix from the $data array. We only use the first 7 characters to avoid the filename being too long.
		$unique_suffix = substr( md5( Cast::to_string( wp_json_encode( $data ) ) ), 0, 7 );

		$ld_file_part = '/learndash/reports/learndash_reports_' . str_replace( array( 'ld_data_reports_', '-' ), array( '', '_' ), $this->transient_key ) . '_' . $unique_suffix . '.csv';

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
	 * Build a report row from a result object.
	 *
	 * @since 4.25.6
	 *
	 * @param object $result The result object from the activity query.
	 *
	 * @return array<string, mixed> The formatted row data.
	 */
	private function build_report_row( $result ): array {
		$row = [];

		foreach ( $this->data_headers as $header_key => $header_data ) {
			if (
				isset( $header_data['display'] )
				&& ! empty( $header_data['display'] )
				&& is_callable( $header_data['display'] )
			) {
				$user_id            = property_exists( $result, 'user_id' ) ? $result->user_id : get_current_user_id();
				$row[ $header_key ] = call_user_func_array(
					$header_data['display'],
					array(
						$header_data['default'],
						$header_key,
						$result,
						get_user_by( 'id', $user_id ),
					)
				);
			} elseif (
				isset( $header_data['default'] )
				&& ! empty( $header_data['default'] )
			) {
				$row[ $header_key ] = $header_data['default'];
			} else {
				$row[ $header_key ] = '';
			}
		}

		return $row;
	}

	/**
	 * Save CSV data to file.
	 *
	 * @since 4.25.6
	 *
	 * @param array<int, array<string, mixed>> $course_progress_data The data to save.
	 *
	 * @return void
	 */
	private function save_csv_data( array $course_progress_data ): void {
		$this->csv_parse->file            = $this->report_filename;
		$this->csv_parse->output_filename = $this->report_filename;

		// Apply filters for CSV object.
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

		$this->csv_parse->save( $this->report_filename, $course_progress_data, true, wp_list_pluck( $this->data_headers, 'label' ) );
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
		if (
			property_exists( $report_item, 'post_id' )
			&& ! empty( $report_item->post_id )
		) {
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
					} elseif (
						property_exists( $report_item, 'activity_status' )
						&& true === (bool) $report_item->activity_status
					) {
						$column_value = esc_html_x( 'YES', 'Course Complete Report label: YES', 'learndash' );
					}
				}
				break;

			case 'course_completed_on':
				if ( ! empty( $course_id ) ) {
					$completed_on = get_user_meta( $report_item->user_id, 'course_completed_' . $course_id, true );
					if ( ! empty( $completed_on ) ) {
						return learndash_adjust_date_time_display( $completed_on, 'Y-m-d' );
					} elseif (
						property_exists( $report_item, 'activity_status' )
					) {
						if ( true === (bool) $report_item->activity_status ) {
							if (
								( property_exists( $report_item, 'activity_completed' ) )
								&& ( ! empty( $report_item->activity_completed ) )
							) {
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
}
