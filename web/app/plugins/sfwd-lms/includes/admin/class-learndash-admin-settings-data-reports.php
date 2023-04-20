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

if ( ! class_exists( 'Learndash_Admin_Settings_Data_Reports' ) ) {
	/**
	 * LearnDash Reports Base Class.
	 *
	 * @since 2.3.0
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
		 * @var string
		 */
		protected $parent_menu_page_url;

		/**
		 * Capability for menu page
		 *
		 * @var string
		 */
		protected $menu_page_capability;

		/**
		 * Settings page ID
		 *
		 * @var string
		 */
		protected $settings_page_id;

		/**
		 * Settings page title
		 *
		 * @var string
		 */
		protected $settings_page_title;

		/**
		 * Settings tab title
		 *
		 * @var string
		 */
		protected $settings_tab_title;

		/**
		 * Settings tab priority
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

			$this->parent_menu_page_url  = 'admin.php?page=learndash-lms-reports';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash-lms-reports';
			$this->settings_page_title   = esc_html_x( 'Reports', 'Learndash Report Menu Label', 'learndash' );
			$this->settings_tab_title    = $this->settings_page_title;
			$this->settings_tab_priority = 0;

			add_action( 'init', array( $this, 'init_check_for_download_request' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

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

					if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['data-nonce'] ) ), 'learndash-data-reports-' . sanitize_text_field( wp_unslash( $_GET['data-slug'] ) ) . '-' . get_current_user_id() ) ) {
						$transient_key = sanitize_text_field( wp_unslash( $_GET['data-slug'] ) ) . '_' . sanitize_text_field( wp_unslash( $_GET['data-nonce'] ) );

						$transient_data = $this->get_transient( $transient_key );
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
		 */
		public function admin_menu() {

			$data_settings_courses = learndash_data_upgrades_setting( 'user-meta-courses' );
			$data_settings_quizzes = learndash_data_upgrades_setting( 'user-meta-quizzes' );

			if ( ( ! empty( $data_settings_courses ) ) && ( ! empty( $data_settings_quizzes ) ) ) {
				$this->settings_page_id = add_submenu_page(
					'learndash-lms',
					$this->settings_page_title,
					$this->settings_page_title,
					$this->menu_page_capability,
					$this->settings_page_id,
					array( $this, 'admin_page' )
				);
				add_action( 'load-' . $this->settings_page_id, array( $this, 'on_load_panel' ) );

			} else {
				// If the data upgrades have not been performed then we call the old Reports page output in ld-admin.php.
				$this->settings_page_id = add_submenu_page(
					'learndash-lms',
					$this->settings_page_title,
					$this->settings_page_title,
					LEARNDASH_ADMIN_CAPABILITY_CHECK,
					'learndash-lms-reports',
					'learndash_lms_reports_page'
				);
			}
		}

		/**
		 * Admin tabs
		 *
		 * @since 2.4.0
		 *
		 * @param object $admin_menu_section Settings Section instance.
		 * @param object $ld_admin_tabs      LearnDash Admin Tabs instance.
		 */
		public function admin_tabs( $admin_menu_section, $ld_admin_tabs ) {
			if ( $admin_menu_section == $this->parent_menu_page_url ) {

				$ld_admin_tabs->add_admin_tab_item(
					$admin_menu_section,
					array(
						'id'   => $this->settings_page_id,
						'link' => add_query_arg( array( 'page' => $this->settings_page_id ), 'admin.php' ),
						'name' => $this->settings_tab_title,
					),
					$this->settings_tab_priority
				);
			}
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
			$learndash_assets_loaded['scripts']['learndash-admin-settings-data-reports-script'] = __FUNCTION__;

			$this->init_report_actions();

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
		 * Admin page
		 *
		 * @since 2.3.0
		 */
		public function admin_page() {

			/**
			 * Fires before settings page content.
			 *
			 * @since 3.0.0
			 */
			do_action( 'learndash_settings_page_before_content' );
			?>
			<div id="learndash-settings" class="wrap learndash-settings-page-wrap">
				<h1 class="learndash-empty-page-title"></h1>
				<form method="post" action="options.php">
					<div id="poststuff">
						<div id="advanced-sortables" class="meta-box-sortables">
							<div id="sfwd-courses_metabox" class="postbox ld_settings_postbox learndash-settings-postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php esc_html_e( 'User Reports', 'learndash' ); ?></h2>
								</div>
								<div class="inside">
									<div class="sfwd sfwd_options sfwd-courses_settings">
										<table id="learndash-data-reports" class="wc_status_table widefat" cellspacing="0">
										<?php
										wp_nonce_field( 'learndash-data-reports-nonce-' . get_current_user_id(), 'learndash-data-reports-nonce' );
										foreach ( $this->report_actions as $report_action_slug => $report_action ) {
											$report_action['instance']->show_report_action();
										}
										?>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
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

						$transient_fp = fopen( $ld_transient_filename, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
						if ( $transient_fp ) {
							fwrite( $transient_fp, serialize( $transient_data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize, WordPress.WP.AlternativeFunctions.file_system_read_fwrite
							fclose( $transient_fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
						}
					} else {
						update_option( $options_key, $transient_data );
					}
				} else {
					delete_option( $options_key );
				}
			}
		}

		// End of functions.
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
 */
function learndash_data_reports_ajax() {
	$reply_data = array( 'status' => false );

	if ( current_user_can( 'read' ) ) {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'learndash-data-reports-nonce-' . get_current_user_id() ) ) ) {
			if ( ( isset( $_POST['data'] ) ) && ( ! empty( $_POST['data'] ) ) ) {

				$ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();
				$reply_data['data']             = $ld_admin_settings_data_reports->do_data_reports( $_POST['data'], $reply_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				echo wp_json_encode( $reply_data );
			}
		}
	}
	wp_die(); // this is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_learndash-data-reports', 'learndash_data_reports_ajax' );
