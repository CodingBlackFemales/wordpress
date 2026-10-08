<?php
/**
 * LearnDash Reports Base Class.
 *
 * @since 2.3.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\App;
use LearnDash\Core\Modules\Reports\Legacy\Settings\Page;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Sanitize;

if ( ! class_exists( 'Learndash_Admin_Settings_Data_Reports' ) ) {
	/**
	 * LearnDash Reports Base Class.
	 *
	 * @since 2.3.0
	 *
	 * @phpstan-import-type Header_Data from LearnDash_Settings_Page
	 */
	class Learndash_Admin_Settings_Data_Reports {

		/**
		 * Process times
		 *
		 * @var array $process_times
		 */
		protected $process_times = array();

		/**
		 * Parent menu page URL
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var string
		 */
		protected $parent_menu_page_url;

		/**
		 * Capability for menu page
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var string
		 */
		protected $menu_page_capability;

		/**
		 * Settings page ID
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var string
		 */
		protected $settings_page_id;

		/**
		 * Settings page title
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var string
		 */
		protected $settings_page_title;

		/**
		 * Settings tab title
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var string
		 */
		protected $settings_tab_title;

		/**
		 * Settings tab priority
		 *
		 * @since 3.5.0
		 * @deprecated 4.17.0
		 *
		 * @var integer
		 */
		protected $settings_tab_priority = 0;

		/**
		 * Report actions
		 *
		 * @var array $report_actions
		 */
		private $report_actions = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.3.0
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init_check_for_download_request' ) );

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_PERCENT' ) ) {
				/** This filter is documented in includes/admin/class-learndash-admin-data-upgrades.php */
				define( 'LEARNDASH_PROCESS_TIME_PERCENT', apply_filters( 'learndash_process_time_percent', 80 ) );
			}

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_SECONDS' ) ) {
				/** This filter is documented in includes/admin/class-learndash-admin-data-upgrades.php */
				define( 'LEARNDASH_PROCESS_TIME_SECONDS', apply_filters( 'learndash_process_time_seconds', 10 ) );
			}

		}

		/**
		 * Init check for download request.
		 *
		 * @since 2.3.0
		 */
		public function init_check_for_download_request() {
			if ( isset( $_GET['ld-report-download'] ) ) {
				if ( ( isset( $_GET['data-nonce'] ) ) && ( ! empty( $_GET['data-nonce'] ) ) && ( isset( $_GET['data-slug'] ) ) && ( ! empty( $_GET['data-slug'] ) ) ) {
					$data_slug   = sanitize_text_field( wp_unslash( $_GET['data-slug'] ) );
					$data_nonce  = sanitize_text_field( wp_unslash( $_GET['data-nonce'] ) );
					$nonce_valid = wp_verify_nonce( $data_nonce, 'learndash-data-reports-' . $data_slug . '-' . get_current_user_id() );

					$transient_key  = $data_slug . '_' . $data_nonce;
					$transient_data = $this->get_transient( $transient_key );

					/*
					 * The per-session nonce rolls on WordPress' ~12-hour tick, so a long-running or
					 * left-open export could no longer be downloaded once its nonce expires. The URL's
					 * nonce is still the unguessable key of the export's own transient, so fall back
					 * to the user who started this export when only the nonce's freshness has lapsed,
					 * not its secrecy. Group leaders start exports from the group report list table,
					 * so the fallback covers them too — an administrator capability here would strand
					 * them on a report they are allowed to run.
					 */
					$is_owner = is_array( $transient_data )
						&& isset( $transient_data['user_id'] )
						&& Cast::to_int( $transient_data['user_id'] ) === get_current_user_id()
						&& (
							learndash_is_admin_user()
							|| learndash_is_group_leader_user()
						);

					if (
						$nonce_valid
						|| $is_owner
					) {
						if ( ( isset( $transient_data['report_filename'] ) ) && ( ! empty( $transient_data['report_filename'] ) ) ) {
							$report_filename = $transient_data['report_filename'];
							if ( ( file_exists( $report_filename ) ) && ( is_readable( $report_filename ) ) ) {
								$http_headers = array(
									'Content-type: text/csv; charset=' . DB_CHARSET,
									'Content-Disposition: attachment; filename=' . basename( $report_filename ),
									'Pragma: no-cache',
									'Expires: 0',
								);
								/**
								 * Filters http headers for CSV download request.
								 *
								 * @since 2.4.7
								 *
								 * @param array  $http_headers  An array of http headers.
								 * @param array  $transient_data An array of transient data for csv download.
								 * @param string $data_slug     The slug of the data to be downloaded.
								 */
								$http_headers = apply_filters( 'learndash_csv_download_headers', $http_headers, $transient_data, sanitize_text_field( wp_unslash( $_GET['data-slug'] ) ) );
								if ( ! empty( $http_headers ) ) {
									foreach ( $http_headers as $http_header ) {
										header( $http_header );
									}
								}
								/**
								 * Fires after setting CSV download headers.
								 *
								 * @since 2.4.7
								 */
								do_action( 'learndash_csv_download_after_headers' );

								set_time_limit( 0 );
								$report_fp = @fopen( $report_filename, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
								while ( ! feof( $report_fp ) ) {
									print( @fread( $report_fp, 1024 * 8 ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_system_read_fread
									if ( ob_get_level() > 0 ) {
										ob_flush();
									}
									flush();
								}
							}
						}
					}
				}
				die();
			}
		}

		/**
		 * Register settings page
		 *
		 * @since 2.3.0
		 * @deprecated 4.17.0
		 *
		 * @return void
		 */
		public function admin_menu() {
			_deprecated_function( __METHOD__, '4.17.0' );

			$page_instance = App::get( Page::class );

			if ( ! $page_instance instanceof Page ) {
				return;
			}

			$page_instance->admin_menu();
		}

		/**
		 * Admin tabs
		 *
		 * @since 2.4.0
		 * @since 4.17.0 Corrected type of $admin_menu_section
		 * @deprecated 4.17.0
		 *
		 * @param string $admin_menu_section Settings Section instance.
		 * @param object $ld_admin_tabs      LearnDash Admin Tabs instance.
		 *
		 * return void
		 */
		public function admin_tabs( $admin_menu_section, $ld_admin_tabs ) {
			_deprecated_function( __METHOD__, '4.17.0' );

			$page_instance = App::get( Page::class );

			if ( ! $page_instance instanceof Page ) {
				return;
			}

			$page_instance->admin_tabs( $admin_menu_section );
		}

		/**
		 * On load panel
		 *
		 * @since 2.3.0
		 */
		public function on_load_panel() {

			wp_enqueue_style(
				'learndash_style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash_style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_style'] = __FUNCTION__;

			wp_enqueue_style(
				'sfwd-module-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash-admin-settings-data-reports-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-data-reports' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			wp_localize_script(
				'learndash-admin-settings-data-reports-script',
				'learndashDataReports',
				array(
					'messages' => self::get_export_notice_messages(),
				)
			);
			$learndash_assets_loaded['scripts']['learndash-admin-settings-data-reports-script'] = __FUNCTION__;

			$this->init_report_actions();

		}

		/**
		 * Returns the export notice strings shared by the Reports settings page and the
		 * ProPanel widget, so the wording lives in one place instead of being repeated
		 * in each script.
		 *
		 * @since 5.1.6
		 *
		 * @return array{ export_queued: string, dismiss_notice: string, export_failed_start: string } Notice keys mapped to translated, HTML-safe strings.
		 */
		public static function get_export_notice_messages(): array {
			return array(
				'export_queued'       => esc_html__( 'Your report is being processed in the background. You can safely leave or close this page; the download link will appear here as a notice once the export finishes.', 'learndash' ),
				'dismiss_notice'      => esc_html__( 'Dismiss this notice.', 'learndash' ),
				'export_failed_start' => esc_html__( 'The export could not be started. Please try again.', 'learndash' ),
			);
		}

		/**
		 * Init Report Action
		 *
		 * @since 2.3.0
		 */
		public function init_report_actions() {

			/**
			 * Filters admin report register actions.
			 *
			 * @since 2.3.0
			 *
			 * @param array $report_actions An array of report actions.
			 */
			$this->report_actions = apply_filters( 'learndash_admin_report_register_actions', $this->report_actions );
		}

		/**
		 * Returns registered Report Actions.
		 *
		 * @since 4.17.0
		 *
		 * @return array{class: string, instance: Learndash_Admin_Settings_Data_Reports, slug: string, label?: string, text?: string}[]
		 */
		public function get_report_actions(): array {
			return $this->report_actions;
		}

		/**
		 * Admin page
		 *
		 * @since 2.3.0
		 * @deprecated 4.17.0
		 *
		 * @return void
		 */
		public function admin_page() {
			_deprecated_function( __METHOD__, '4.17.0' );

			$page_instance = App::get( Page::class );

			if ( ! $page_instance instanceof Page ) {
				return;
			}

			$page_instance->show_settings_page();
		}

		/**
		 * Do data reports
		 *
		 * @since 2.3.0
		 *
		 * @param array $post_data  Array of post data to process.
		 * @param array $reply_data Array of reply data to return.
		 *
		 * @return array
		 */
		public function do_data_reports( $post_data = array(), $reply_data = array() ) {
			$this->init_report_actions();
			if ( ( isset( $post_data['slug'] ) ) && ( ! empty( $post_data['slug'] ) ) ) {
				$post_data_slug = esc_attr( $post_data['slug'] );

				if ( isset( $this->report_actions[ $post_data_slug ] ) ) {
					$reply_data = $this->report_actions[ $post_data_slug ]['instance']->process_report_action( $post_data );
				}
			}
			return $reply_data;
		}

		/**
		 * Init process times
		 *
		 * @since 2.3.0
		 */
		public function init_process_times() {
			$this->process_times['started'] = time();
			$this->process_times['limit']   = ini_get( 'max_execution_time' );
			$this->process_times['limit']   = intval( $this->process_times['limit'] );
			if ( empty( $this->process_times['limit'] ) ) {
				$this->process_times['limit'] = 30;
			}
		}

		/**
		 * Out of time check
		 *
		 * @since 2.3.0
		 */
		public function out_of_timer() {
			$this->process_times['current_time'] = time();

			$this->process_times['ticks']   = $this->process_times['current_time'] - $this->process_times['started'];
			$this->process_times['percent'] = ( $this->process_times['ticks'] / $this->process_times['limit'] ) * 100;

			// If we are over 80% of the allowed processing time or over 10 seconds then finish up and return.
			if ( ( $this->process_times['percent'] >= LEARNDASH_PROCESS_TIME_PERCENT ) || ( $this->process_times['ticks'] > LEARNDASH_PROCESS_TIME_SECONDS ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get process transient data.
		 *
		 * @since 2.4.0
		 *
		 * @param string $transient_key Unique transient key.
		 */
		public function get_transient( $transient_key = '' ) {
			$transient_data = array();

			if ( ! empty( $transient_key ) ) {
				$transient_key = str_replace( '-', '_', $transient_key );
				$options_key   = 'learndash_reports_' . $transient_key;

				if ( ( defined( 'LEARNDASH_TRANSIENT_CACHE_STORAGE' ) ) && ( 'file' === LEARNDASH_TRANSIENT_CACHE_STORAGE ) ) { // @phpstan-ignore-line
					$wp_upload_dir = wp_upload_dir();

					$ld_file_part = '/learndash/cache/learndash_reports_data_' . $transient_key . '.txt';

					$ld_transient_filename = $wp_upload_dir['basedir'] . $ld_file_part;

					if ( ! file_exists( dirname( $ld_transient_filename ) ) ) {
						if ( wp_mkdir_p( dirname( $ld_transient_filename ) ) === false ) {
							$data['error_message'] = esc_html__( 'ERROR: Cannot create working folder. Check that the parent folder is writable', 'learndash' ) . ' ' . dirname( $ld_transient_filename );
							return;
						}
					}

					learndash_put_directory_index_file( trailingslashit( dirname( $ld_transient_filename ) ) . 'index.php' );

					Learndash_Admin_File_Download_Handler::register_file_path(
						'learndash-cache',
						dirname( $ld_transient_filename )
					);

					Learndash_Admin_File_Download_Handler::try_to_protect_file_path(
						dirname( $ld_transient_filename )
					);

					if ( file_exists( $ld_transient_filename ) ) {
						$transient_fp = fopen( $ld_transient_filename, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
						if ( $transient_fp ) {
							$transient_data = '';
							while ( ! feof( $transient_fp ) ) {
								$transient_data .= fread( $transient_fp, 4096 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_system_read_fread
							}
							fclose( $transient_fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

							$transient_data = maybe_unserialize( $transient_data );
						}
					}
				} else {
					$transient_data = get_option( $options_key );
				}

				return $transient_data;
			}
		}

		/**
		 * Set process Option cache
		 *
		 * @since 3.1.0
		 *
		 * @param string $transient_key  Unique transient key.
		 * @param array  $transient_data Array of data to store.
		 */
		public function set_option_cache( $transient_key = '', $transient_data = array() ) {

			if ( ! empty( $transient_key ) ) {
				$transient_key = str_replace( '-', '_', $transient_key );
				$options_key   = 'learndash_reports_' . $transient_key;

				if ( ! empty( $transient_data ) ) {
					if ( ( defined( 'LEARNDASH_TRANSIENT_CACHE_STORAGE' ) ) && ( 'file' === LEARNDASH_TRANSIENT_CACHE_STORAGE ) ) { // @phpstan-ignore-line
						$wp_upload_dir = wp_upload_dir();

						$ld_file_part = '/learndash/cache/learndash_reports_data_' . $transient_key . '.txt';

						$ld_transient_filename = $wp_upload_dir['basedir'] . $ld_file_part;

						if ( ! file_exists( dirname( $ld_transient_filename ) ) ) {
							if ( wp_mkdir_p( dirname( $ld_transient_filename ) ) === false ) {
								$data['error_message'] = esc_html__( 'ERROR: Cannot create working folder. Check that the parent folder is writable', 'learndash' ) . ' ' . dirname( $ld_transient_filename );
								return;
							}
						}

						learndash_put_directory_index_file( trailingslashit( dirname( $ld_transient_filename ) ) . 'index.php' );

						Learndash_Admin_File_Download_Handler::register_file_path(
							'learndash-cache',
							dirname( $ld_transient_filename )
						);

						Learndash_Admin_File_Download_Handler::try_to_protect_file_path(
							dirname( $ld_transient_filename )
						);

						// Atomic write: stream to a unique temp file then rename into place.
						// Writing directly to $ld_transient_filename truncates the live file before
						// the payload is fully written, leaving a window where concurrent readers see
						// an empty file and the export status endpoint reports "Export not found"
						// mid-run. WP_Filesystem::move() wraps a POSIX rename, which is atomic on the
						// file systems WordPress supports, so readers either see the previous full file
						// or the new one — never a half-written intermediate.
						global $wp_filesystem;

						if ( ! $wp_filesystem ) {
							require_once ABSPATH . 'wp-admin/includes/file.php';
							WP_Filesystem();
						}

						if ( $wp_filesystem ) {
							$tmp_filename  = $ld_transient_filename . '.tmp.' . uniqid( '', true );
							$serialized    = serialize( $transient_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- maybe_unserialize() on the read side requires PHP serialize format; JSON would not round-trip stored objects.
							$wrote_tmp     = $wp_filesystem->put_contents( $tmp_filename, $serialized, FS_CHMOD_FILE );

							if ( $wrote_tmp ) {
								if ( ! $wp_filesystem->move( $tmp_filename, $ld_transient_filename, true ) ) {
									$wp_filesystem->delete( $tmp_filename );
								}
							}
						}
					} else {
						update_option( $options_key, $transient_data );
					}
				} else {
					delete_option( $options_key );
				}
			}
		}
	}
}

// Go ahead and include out User Meta Courses upgrade class.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-quizzes.php';

add_action(
	'plugins_loaded',
	function() {
		new Learndash_Admin_Data_Reports_Courses();
		new Learndash_Admin_Data_Reports_Quizzes();
	}
);

/**
 * Data Reports AJAX function.
 * Handles AJAX requests for Reports.
 *
 * @since 2.3.0
 *
 * @return void
 */
function learndash_data_reports_ajax() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- TODO: Move this function.
	if (
		! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK )
		|| empty( $_POST['data'] )
		|| empty( $_POST['data']['nonce'] )
	) {
		wp_die();
	}

	$data = Sanitize::array( wp_unslash( $_POST['data'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in Sanitize::array().

	if (
		! wp_verify_nonce(
			Cast::to_string( $data['nonce'] ),
			'learndash-data-reports-' . esc_attr( Cast::to_string( $data['slug'] ) ) . '-' . get_current_user_id()
		)
	) {
		wp_die();
	}

	$reply_data = array( 'status' => false );

	$ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();
	$reply_data['data']             = $ld_admin_settings_data_reports->do_data_reports( $data, $reply_data );

	echo wp_json_encode( $reply_data );

	wp_die(); // this is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_learndash-data-reports', 'learndash_data_reports_ajax' );

/**
 * Data Reports export status AJAX handler.
 *
 * Reports background-export progress for the Reports settings page poller: the completion
 * percentage, whether the export has finished, and the CSV download URL. The poller redirects
 * to that URL to download the file automatically once the export is done.
 *
 * Administrators and group leaders may poll. The Reports settings page is administrator-only, but
 * the same export runs from the group report list table and the dashboard reporting widgets, which
 * group leaders can reach, so an administrator-only gate would leave them polling forever for an
 * export they were allowed to start. Which export a caller may read is decided by the nonce or by
 * ownership of the transient, not by the capability.
 *
 * @since 5.1.10
 *
 * @return void
 */
function learndash_report_export_status_ajax() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- Mirrors learndash_data_reports_ajax() above.
	if (
		(
			! learndash_is_admin_user()
			&& ! learndash_is_group_leader_user()
		)
		|| empty( $_POST['slug'] )
		|| empty( $_POST['nonce'] )
	) {
		wp_send_json_error();
	}

	$slug  = sanitize_text_field( wp_unslash( $_POST['slug'] ) );
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

	$nonce_valid = wp_verify_nonce( $nonce, 'learndash-data-reports-' . $slug . '-' . get_current_user_id() );

	$reports        = new Learndash_Admin_Settings_Data_Reports();
	$transient_data = $reports->get_transient( $slug . '_' . $nonce );

	/*
	 * The per-session nonce rolls on WordPress' ~12-hour tick, so a long-running export would
	 * stop reporting progress once its nonce expires even though the background chunks keep
	 * running. The polled nonce is still the unguessable key of the export's own transient, so
	 * fall back to the export owner — already admitted by the capability check above — when only
	 * the nonce's freshness has lapsed.
	 */
	$is_owner = is_array( $transient_data )
		&& isset( $transient_data['user_id'] )
		&& Cast::to_int( $transient_data['user_id'] ) === get_current_user_id();

	if (
		! $nonce_valid
		&& ! $is_owner
	) {
		wp_send_json_error();
	}

	if (
		! is_array( $transient_data )
		|| empty( $transient_data['report_filename'] )
	) {
		wp_send_json_error();
	}

	$report_filename = Cast::to_string( $transient_data['report_filename'] );
	$file_ready      =
		file_exists( $report_filename )
		&& is_readable( $report_filename );

	$total  = isset( $transient_data['total_count'] ) ? Cast::to_int( $transient_data['total_count'] ) : 0;
	$offset = isset( $transient_data['offset'] ) ? Cast::to_int( $transient_data['offset'] ) : 0;
	$status = isset( $transient_data['status'] ) ? Cast::to_string( $transient_data['status'] ) : '';

	/*
	 * A failed export is reported as an error so the page stops polling. The partial CSV is still
	 * on disk and would otherwise keep answering with a plausible percentage forever; the engine's
	 * failure admin notice carries the reason.
	 */
	if ( 'export_failed' === $status ) {
		wp_send_json_error();
	}

	/*
	 * "Done" means the engine actually finished the chunk chain, not merely that the Action
	 * Scheduler queue is currently empty (an interrupted chain looks idle too, and reporting
	 * that as done would auto-download an incomplete CSV). The explicit status flag is the
	 * primary signal; the offset/total comparison is a fallback for transients written before
	 * that flag existed.
	 */
	$done = $file_ready
		&& (
			'export_complete' === $status
			|| ( $total > 0 && $offset >= $total )
		);

	// Cap in-progress progress at 99% so the notice only reads 100% once the file is ready to download.
	$percent = $done
		? 100
		: ( $total > 0 ? min( 99, Cast::to_int( floor( ( $offset / $total ) * 100 ) ) ) : 0 );

	wp_send_json_success(
		[
			'percent'     => $percent,
			'done'        => $done,
			'offset'      => $offset,
			'total_count' => $total,
			'report_url'  => isset( $transient_data['report_url'] ) ? Cast::to_string( $transient_data['report_url'] ) : '',
		]
	);
}

add_action( 'wp_ajax_learndash_report_export_status', 'learndash_report_export_status_ajax' );
