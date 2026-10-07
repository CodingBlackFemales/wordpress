<?php
/**
 * LearnDash Course Reports.
 *
 * @since 2.3.0
 * @package LearnDash\Course\Reports
 */

use LearnDash\Core\Modules\Reports\Export\User_Enumeration;
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
		$course_label = learndash_get_custom_label( 'course' );

		$report_actions[ $this->data_slug ] = array(
			'class'    => get_class( $this ),
			'instance' => $this,
			'slug'     => $this->data_slug,
			'label'    => $course_label,
			'text'     => sprintf(
				// Translators: placeholders: Custom Course Label.
				__( 'Export User %s Data', 'learndash' ),
				$course_label
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
	 * Handles the AJAX export request.
	 *
	 * Resolves the export's user scope, writes CSV headers, queues the first background chunk via
	 * Action Scheduler, and returns a queued-state snapshot. A scoped export (a group, a filter, or
	 * any non-administrator requester) stores its learner list here, where it is cheap. An
	 * administrator's site-wide export stores none, leaving `process_export_chunk()` to enumerate
	 * the activity table in the background worker — too slow for this request's origin timeout.
	 * Subsequent rounds are no longer driven by the browser: Action Scheduler chains chunks
	 * server-side, and progress (plus the final download link) surfaces through admin notices. A
	 * request received while an export is already running short-circuits to a running-state snapshot.
	 *
	 * @since 2.3.0
	 * @since 4.25.6 The internal processing logic has been updated to be in sync with the Reporting Block results.
	 * @since 5.1.6 Iteration moved to Action Scheduler; this method only queues the export now.
	 * @since 5.1.10 Deferred an administrator's site-wide learner enumeration to the background
	 *            worker, restricted a group export to the groups the requester administers, and
	 *            pinned every other requester to the learners they may report on.
	 *
	 * @param array<string,mixed> $data Post data from AJAX call.
	 * @phpstan-param array{nonce?: string, init?: int, filters?: array<string, mixed>, group_id?: int, time_start?: string, time_end?: string, course_ids?: array<int>, posts_ids?: array<int>, users_ids?: array<int>, slug?: string} $data
	 *
	 * @return array<string, mixed> Response payload describing the queued export.
	 */
	public function process_report_action( $data = [] ) {
		if ( empty( $data['nonce'] ) ) {
			return [];
		}

		$nonce = $data['nonce'];

		if ( ! wp_verify_nonce( $nonce, 'learndash-data-reports-' . $this->data_slug . '-' . get_current_user_id() ) ) {
			return [];
		}

		require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

		$this->csv_parse     = new lmsParseCSV();
		$this->transient_key = $this->data_slug . '_' . $nonce;

		// Do not start a second export while one is already queued or running — the running
		// export's progress notice already covers it.
		$engine = Learndash_Admin_Background_Export::get_instance();

		if (
			$engine instanceof Learndash_Admin_Background_Export
			&& $engine->is_export_in_progress()
		) {
			return [
				'status' => 'running',
				'slug'   => $this->data_slug,
			];
		}

		/*
		 * Only a scoped user list is resolved here — a group's members, or the set a non-admin
		 * requester may see — because each is a bounded lookup. An administrator's site-wide list is
		 * not: this method runs in the init AJAX request, whose origin timeout is too short to
		 * enumerate (or even count) the activity table, so that is deferred to
		 * process_export_chunk() in the background worker.
		 */
		$this->transient_data = [
			'nonce'      => $nonce,
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
				return [];
			}

			/*
			 * A group export is scoped by members as well as by courses. Restricting only the
			 * courses puts every learner on the site who touched one of them into a group leader's
			 * CSV. The member list is one indexed usermeta read bounded by group size, so resolving
			 * it here does not reintroduce the site-wide enumeration cost this method defers.
			 */
			$this->transient_data['users_ids']  = learndash_get_groups_user_ids( $group_id );
			$this->transient_data['course_ids'] = learndash_group_enrolled_courses( $group_id );

			if ( empty( $this->transient_data['course_ids'] ) ) {
				return [];
			}
		}

		/*
		 * Site-wide enumeration is an administrator capability. Every other requester — a group
		 * leader, who holds a valid export nonce and therefore controls this payload — is pinned to
		 * the learners they may report on, so a request carrying no usable user scope cannot widen
		 * the export past its own visibility.
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
			 * touching the activity table; the background worker walks this list. An empty list is
			 * still an explicit scope — it means "no learners", not "fall back to site-wide".
			 */
			$this->transient_data['users_ids']   = array_values( array_map( [ Cast::class, 'to_int' ], $this->transient_data['users_ids'] ) );
			$this->transient_data['total_count'] = count( $this->transient_data['users_ids'] );
		}

		$this->set_report_filenames( $data );
		$this->report_filename = $this->transient_data['report_filename'];

		// Clear any existing report file to start fresh.
		$reports_fp = fopen( $this->report_filename, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions  -- Legacy usage, do not want to change now.
		fclose( $reports_fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions  -- Legacy usage, do not want to change now.

		$this->send_report_headers_to_csv();

		$this->set_option_cache( $this->transient_key, $this->transient_data );

		if ( $engine instanceof Learndash_Admin_Background_Export ) {
			$engine->enqueue_export_chunk_task( $this->transient_key, $this->data_slug );
		}

		return [
			'status'        => 'queued',
			'slug'          => $this->data_slug,
			'transient_key' => $this->transient_key,
		];
	}

	/**
	 * Processes one chunk of users for the in-progress course export.
	 *
	 * Invoked by the Action Scheduler dispatcher in
	 * `Learndash_Admin_Background_Export::handle_export_chunk_task()`. Loads the export state
	 * from the transient, processes a single chunk of users in one batched activity query,
	 * appends the resulting rows to the CSV, and persists the remaining work back into the
	 * transient. Returns whether more chunks remain so the dispatcher knows whether to chain
	 * another scheduled action.
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

		/**
		 * Number of users processed per chunk during the background CSV export.
		 *
		 * Lowering this value keeps the `WHERE user_id IN (...)` clause smaller on
		 * sites with very large user bases (the original 200k-user bug), but each
		 * chunk rewrites the full enumerated `users_ids` list back into the option
		 * cache, so a smaller value multiplies those whole-list rewrites. Raising
		 * it reduces how often that list gets rewritten.
		 *
		 * @since 5.1.6
		 *
		 * @param int $chunk_size Number of users processed per export chunk. Default 100.
		 *
		 * @return int Number of users processed per export chunk.
		 */
		$chunk_size = Cast::to_int( apply_filters( 'learndash_report_user_activity_export_chunk_size', 100 ) );

		if ( $chunk_size < 1 ) {
			$chunk_size = 100;
		}

		$users_ids = $this->transient_data['users_ids'] ?? null;

		if ( ! is_array( $users_ids ) ) {
			/*
			 * Admin-wide export: enumerate the full learner list once, then slice it by offset on
			 * every later chunk. A keyset query per chunk re-scans the activity table and slows as
			 * the export advances (an O(N^2) walk); one upfront enumeration replaces that with a
			 * single query plus O(1) slicing, and yields the total for free.
			 *
			 * The branch is keyed on the list being absent rather than empty: an export scoped to a
			 * group, a filter, or a non-admin requester stores its list at queue time, and an empty
			 * one of those means "no learners" — never "enumerate the whole site".
			 */
			$users_ids                           = ( new User_Enumeration() )->get_all_activity_user_ids( [ 'course' ] );
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

		$this->fetch_and_save_activity_data( $chunk );

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
	 * Fetch and process activity data for the report.
	 *
	 * @since 4.25.6
	 * @since 5.1.6 Added the $user_ids_chunk parameter so the chunked dispatcher can restrict
	 *        the activity query to a slice of users at a time.
	 *
	 * @param array<int>|null $user_ids_chunk Optional slice of user IDs to restrict the
	 *                                        activity query to. When null, the legacy
	 *                                        single-query behavior is preserved.
	 *
	 * @return array<string, mixed> The data array with processed results.
	 */
	private function fetch_and_save_activity_data( ?array $user_ids_chunk = null ): array {
		// Initialize array to store processed course progress data.
		$course_progress_data = [];

		// Build activity query arguments for fetching course progress data.
		$activity_query_args = [
			'post_types'      => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
			'activity_types'  => 'course',
			'activity_status' => '',
			'orderby_order'   => 'users.display_name, posts.post_title',
		];

		// Merge with cached filter data from initialization.
		$activity_query_args = wp_parse_args( $this->transient_data, $activity_query_args );

		// When the dispatcher passes an explicit chunk of user IDs, restrict the activity
		// query to that slice. This is what keeps the WHERE user_id IN (...) clause small
		// enough to stay under max_allowed_packet on sites with very large user bases.
		if (
			is_array( $user_ids_chunk )
			&& ! empty( $user_ids_chunk )
		) {
			$activity_query_args['user_ids'] = $user_ids_chunk;
		}

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

		/*
		 * The requester is passed explicitly because the Action Scheduler worker runs with no
		 * logged-in user, and this query returns nothing at all in that state. It does not scope the
		 * export: the learner list was resolved and authorized when the export was queued.
		 */
		$user_courses_reports = learndash_reports_get_activity(
			$activity_query_args,
			Cast::to_int( $this->transient_data['user_id'] ?? 0 )
		);

		// Process query results and build report rows.
		if ( ! empty( $user_courses_reports['results'] ) ) {
			foreach ( $user_courses_reports['results'] as $result ) {
				$row = $this->build_report_row( $result );
				if ( ! empty( $row ) ) {
					$course_progress_data[] = $row;
				}
			}
		}

		// Skip the CSV write when the chunk produced no rows so an empty chunk does not
		// re-invoke the `learndash_csv_data` filter chain on the in-place file. Mirrors the
		// guard in the quiz exporter's process_export_chunk().
		if ( ! empty( $course_progress_data ) ) {
			$this->save_csv_data( $course_progress_data );
		}

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
	 * Resolve the LearnDash course ID for a report row.
	 *
	 * Prefer `activity_course_id` from the user activity table so CSV columns stay
	 * aligned with course progress even when the joined `post_id` differs.
	 *
	 * @since 5.1.4
	 *
	 * @param object $report_item Activity query result row.
	 *
	 * @return int
	 */
	private function resolve_report_course_id( $report_item ): int {
		if ( ! is_object( $report_item ) ) {
			return 0;
		}

		if (
			property_exists( $report_item, 'activity_course_id' )
			&& Cast::to_string( $report_item->activity_course_id ) !== ''
			&& Cast::to_int( $report_item->activity_course_id ) > 0
		) {
			return absint( $report_item->activity_course_id );
		}

		if (
			property_exists( $report_item, 'post_id' )
			&& ! empty( $report_item->post_id )
		) {
			return absint( $report_item->post_id );
		}

		return 0;
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
		$course_id = $this->resolve_report_course_id( $report_item );

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
				if ( ! empty( $course_id ) ) {
					$column_value = get_the_title( $course_id );
					$column_value = str_replace( '’', "'", $column_value );
				} elseif ( property_exists( $report_item, 'post_title' ) ) {
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
								property_exists( $report_item, 'activity_completed' )
								&& ! empty( $report_item->activity_completed )
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
