<?php
/**
 * LearnDash Quiz Reports.
 *
 * @since 2.3.0
 *
 * @package LearnDash\Quiz\Reports
 */

use LearnDash\Core\Modules\Reports\Export\User_Enumeration;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;

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
			$quiz_label = learndash_get_custom_label( 'quiz' );

			$report_actions[ $this->data_slug ] = array(
				'class'    => get_class( $this ),
				'instance' => $this,
				'slug'     => $this->data_slug,
				'label'    => $quiz_label,
				'text'     => sprintf(
					// Translators: placeholders: Custom Quiz Label.
					__( 'Export User %s Data', 'learndash' ),
					$quiz_label
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
		 * Handles the AJAX export request.
		 *
		 * Resolves the export's user scope, writes CSV headers, queues the first background chunk
		 * via Action Scheduler, and returns a queued-state snapshot. A scoped export (a group, a
		 * filter, or any non-administrator requester) stores its learner list here, where it is
		 * cheap. An administrator's site-wide export stores none, leaving `process_export_chunk()`
		 * to enumerate the activity table in the background worker — too slow for this request's
		 * origin timeout. Subsequent rounds are no longer driven by the browser: Action Scheduler
		 * chains chunks server-side, and progress (plus the final download link) surfaces through
		 * admin notices. A request received while an export is already running short-circuits to a
		 * running-state snapshot.
		 *
		 * @since 2.3.0
		 * @since 5.1.6 Iteration moved to Action Scheduler; this method only queues the export now.
		 * @since 5.1.10 Deferred an administrator's site-wide learner enumeration to the background
		 *            worker, restricted a group export to the groups the requester administers, and
		 *            pinned every other requester to the learners they may report on.
		 *
		 * @param array $data Post data from AJAX call.
		 *
		 * @return array Response payload describing the queued export.
		 */
		public function process_report_action( $data = array() ) {
			if (
				empty( $data['nonce'] )
				|| ! wp_verify_nonce( $data['nonce'], 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() )
			) {
				return $data;
			}

			require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

			$this->csv_parse     = new lmsParseCSV();
			$this->transient_key = $this->data_slug . '_' . $data['nonce'];

			// Do not start a second export while one is already queued or running — the running
			// export's progress notice already covers it.
			$engine = Learndash_Admin_Background_Export::get_instance();

			if (
				$engine instanceof Learndash_Admin_Background_Export
				&& $engine->is_export_in_progress()
			) {
				return array(
					'status' => 'running',
					'slug'   => $this->data_slug,
				);
			}

			/*
			 * Only a scoped user list is resolved here — a group's members, or the set a non-admin
			 * requester may see — because each is a bounded lookup. An administrator's site-wide
			 * list is not: this method runs in the init AJAX request, whose origin timeout is too
			 * short to enumerate (or even count) the activity table, so that is deferred to
			 * process_export_chunk() in the background worker.
			 */
			$this->transient_data = [
				'nonce'      => $data['nonce'],
				'user_id'    => get_current_user_id(),
				'offset'     => 0,
				'started_at' => time(),
				'updated_at' => time(),
			];

			if ( ! empty( $data['filters'] ) ) {
				$this->transient_data = wp_parse_args( $this->transient_data, $data['filters'] );
			} elseif ( ! empty( $data['group_id'] ) ) {
				$group_id = Cast::to_int( $data['group_id'] );

				/*
				 * The group export is also reachable from the group list table, whose handler admits
				 * group leaders and forwards the payload verbatim, so the requested group is attacker
				 * controlled and has to be authorized rather than trusted. An administrator gets every
				 * group back from this helper and a group leader only the ones they lead, so both a
				 * forged ID and a leader who leads nothing resolve to no access.
				 */
				$permitted_group_ids = array_map( [ Cast::class, 'to_int' ], learndash_get_administrators_group_ids( get_current_user_id() ) );

				if ( ! in_array( $group_id, $permitted_group_ids, true ) ) {
					return $data;
				}

				/*
				 * A group export is scoped by members as well as by courses. Restricting only the
				 * courses puts every learner on the site who touched one of them into a group
				 * leader's CSV. The member list is one indexed usermeta read bounded by group size,
				 * so resolving it here does not reintroduce the site-wide enumeration cost this
				 * method defers.
				 */
				$this->transient_data['users_ids']  = learndash_get_groups_user_ids( $group_id );
				$this->transient_data['course_ids'] = learndash_group_enrolled_courses( $group_id );

				if ( empty( $this->transient_data['course_ids'] ) ) {
					return $data;
				}
			}

			/*
			 * Site-wide enumeration is an administrator capability. Every other requester — a group
			 * leader, who holds a valid export nonce and therefore controls this payload — is pinned
			 * to the learners they may report on, so a request carrying no usable user scope cannot
			 * widen the export past its own visibility.
			 */
			if (
				! learndash_is_admin_user()
				&& empty( $this->transient_data['users_ids'] )
			) {
				// learndash_get_report_user_ids() returns null, not an array, when no user is resolved.
				$permitted_user_ids = learndash_get_report_user_ids();

				$this->transient_data['users_ids'] = empty( $permitted_user_ids ) ? [] : $permitted_user_ids;
			}

			if (
				isset( $this->transient_data['users_ids'] )
				&& is_array( $this->transient_data['users_ids'] )
			) {
				/*
				 * An explicit user list is exported as given, so the total is known here without
				 * touching the activity table; the background worker walks this list. An empty list
				 * is still an explicit scope — it means "no learners", not "fall back to site-wide".
				 */
				$this->transient_data['users_ids']   = array_values( array_map( [ Cast::class, 'to_int' ], $this->transient_data['users_ids'] ) );
				$this->transient_data['total_count'] = count( $this->transient_data['users_ids'] );
			}

			$this->set_report_filenames( $data );
			$this->report_filename = $this->transient_data['report_filename'];

			// Clear out any previous file.
			$reports_fp = fopen( $this->report_filename, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			if ( $reports_fp ) {
				fclose( $reports_fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}

			$this->send_report_headers_to_csv();

			$this->set_option_cache( $this->transient_key, $this->transient_data );

			if ( $engine instanceof Learndash_Admin_Background_Export ) {
				$engine->enqueue_export_chunk_task( $this->transient_key, $this->data_slug );
			}

			return array(
				'status'        => 'queued',
				'slug'          => $this->data_slug,
				'transient_key' => $this->transient_key,
			);
		}

		/**
		 * Processes one chunk of users for the in-progress quiz export.
		 *
		 * Invoked by the Action Scheduler dispatcher in `Learndash_Admin_Background_Export::handle_export_chunk_task()`.
		 * Loads the export state from the transient, processes a single chunk of users in
		 * one batched activity query, appends the resulting rows to the CSV, and persists
		 * the remaining work back into the transient. Returns whether more chunks remain
		 * so the dispatcher knows whether to chain another scheduled action.
		 *
		 * @since 5.1.6
		 * @since 5.1.10 Enumerated an administrator's site-wide learner list here, on the first chunk,
		 *            when the queueing request stored no scoped list of its own.
		 *
		 * @param string $transient_key Transient key holding the export state.
		 *
		 * @return bool True when more users remain to process; false when the export is done.
		 */
		public function process_export_chunk( string $transient_key ): bool {
			if ( empty( $transient_key ) ) {
				return false;
			}

			$this->transient_key  = $transient_key;
			$this->transient_data = $this->get_transient( $transient_key );

			if (
				! is_array( $this->transient_data )
				|| empty( $this->transient_data['report_filename'] )
			) {
				return false;
			}

			if ( empty( $this->data_headers ) ) {
				$this->set_report_headers();
			}

			$this->report_filename = $this->transient_data['report_filename'];

			require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

			$this->csv_parse = new lmsParseCSV();

			/** This filter is documented in includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php */
			$chunk_size = Cast::to_int( apply_filters( 'learndash_report_user_activity_export_chunk_size', 100 ) );

			if ( $chunk_size < 1 ) {
				$chunk_size = 100;
			}

			$users_ids = $this->transient_data['users_ids'] ?? null;

			if ( ! is_array( $users_ids ) ) {
				/*
				 * Admin-wide export: enumerate the full learner list once, then slice it by offset
				 * on every later chunk. A keyset query per chunk re-scans the activity table and
				 * slows as the export advances (an O(N^2) walk); one upfront enumeration replaces
				 * that with a single query plus O(1) slicing, and yields the total for free.
				 *
				 * The branch is keyed on the list being absent rather than empty: an export scoped
				 * to a group, a filter, or a non-admin requester stores its list at queue time, and
				 * an empty one of those means "no learners" — never "enumerate the whole site".
				 */
				$users_ids                           = ( new User_Enumeration() )->get_all_activity_user_ids( [ 'quiz' ] );
				$this->transient_data['users_ids']   = $users_ids;
				$this->transient_data['total_count'] = count( $users_ids );

				$this->set_option_cache( $this->transient_key, $this->transient_data );
			}

			$offset = Cast::to_int( $this->transient_data['offset'] ?? 0 );
			$chunk  = array_slice( $users_ids, $offset, $chunk_size );

			if ( empty( $chunk ) ) {
				// The learner list is fully processed.
				return false;
			}

			$activity_query_args = array(
				'post_types'      => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
				'activity_types'  => 'quiz',
				'activity_status' => array( 'IN_PROGRESS', 'COMPLETED' ),
				'orderby_order'   => 'users.display_name, posts.post_title ASC',
				'date_format'     => 'F j, Y H:i:s',
				'per_page'        => '',
				'time_start'      => '',
				'time_end'        => '',
			);

			if ( ! empty( $this->transient_data['posts_ids'] ) ) {
				$activity_query_args['post_ids'] = $this->transient_data['posts_ids'];
			}

			if ( ! empty( $this->transient_data['course_ids'] ) ) {
				$activity_query_args['course_ids'] = $this->transient_data['course_ids'];
			}

			if ( ! empty( $this->transient_data['time_start'] ) ) {
				$activity_query_args['time_start'] = esc_attr( $this->transient_data['time_start'] );
			}

			if ( ! empty( $this->transient_data['time_end'] ) ) {
				$activity_query_args['time_end'] = esc_attr( $this->transient_data['time_end'] );
			}

			global $wpdb;

			$report_users = array();

			/*
			 * Load only the columns the report emits (ID, name, email). get_users( 'fields' => 'all' )
			 * primes every user's full meta — on a large batch that is hundreds of thousands of
			 * usermeta rows read for nothing. A WP_User built from the row is still a real WP_User, so
			 * the report's `instanceof WP_User` checks pass, and it lazily loads meta only if a meta
			 * field is accessed, which the report does not do.
			 */
			$user_query = DB::table( DB::raw( $wpdb->users ) )
				->select( 'ID', 'display_name', 'user_email' )
				->whereIn( 'ID', array_map( [ Cast::class, 'to_int' ], $chunk ) );

			$user_rows = DB::get_results( $user_query->getSQL() );

			if ( is_array( $user_rows ) ) {
				foreach ( $user_rows as $u ) {
					$report_users[ Cast::to_int( $u->ID ) ] = new WP_User( $u );
				}
			}

			$activity_query_args['user_ids'] = $chunk;

			/*
			 * The requester is passed explicitly because the Action Scheduler worker runs with no
			 * logged-in user, and this query returns nothing at all in that state. It does not scope
			 * the export: the learner list was resolved and authorized when the export was queued.
			 */
			$user_courses_reports = learndash_reports_get_activity(
				$activity_query_args,
				Cast::to_int( $this->transient_data['user_id'] ?? 0 )
			);

			$course_progress_data = array();

			if ( ! empty( $user_courses_reports['results'] ) ) {
				foreach ( $user_courses_reports['results'] as $result ) {
					$report_user = $report_users[ (int) $result->user_id ] ?? null;

					if ( ! $report_user ) {
						continue;
					}

					$row = array();

					foreach ( $this->data_headers as $header_key => $header_data ) {
						if (
							isset( $header_data['display'] )
							&& ! empty( $header_data['display'] )
							&& is_callable( $header_data['display'] )
						) {
							$row[ $header_key ] = call_user_func_array(
								$header_data['display'],
								array(
									$header_data['default'],
									$header_key,
									$result,
									$report_user,
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

					if ( ! empty( $row ) ) {
						$course_progress_data[] = $row;
					}
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

			/*
			 * Persist the running offset — the empty-batch check above is the done signal, not a
			 * comparison against a total.
			 */
			$this->transient_data['offset']     = $offset + count( $chunk );
			$this->transient_data['updated_at'] = time();

			$this->set_option_cache( $this->transient_key, $this->transient_data );

			return count( $chunk ) === $chunk_size;
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


