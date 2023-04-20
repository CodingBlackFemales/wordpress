<?php
/**
 * LearnDash Data Upgrades Base.
 *
 * This class handles the data upgrade from the user meta arrays into a DB structure to
 * allow on the fly reporting. Plus to not bloat the user meta table.
 *
 * @since 2.6.0
 * @package LearnDash\Data_Upgrades
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Data_Upgrades' ) ) {

	/**
	 * Class LearnDash Data Upgrades Base.
	 *
	 * @since 2.6.0
	 */
	class Learndash_Admin_Data_Upgrades {

		/**
		 * Static instance of class.
		 *
		 * @var object $instance.
		 */
		protected static $instance;

		/**
		 * Static array of section instances.
		 *
		 * @var array $_instances
		 */
		protected static $_instances = array(); //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Upgrade Actions array
		 *
		 * @var array $upgrade_actions
		 */
		protected static $upgrade_actions = array();

		/**
		 * Private flag for when admin notices have been
		 * show. This prevent multiple admin notices.
		 *
		 * @var boolean $admin_notice_shown
		 */
		private static $admin_notice_shown = false;

		/**
		 * Variable to contain the current processing times.
		 *
		 * @var array $process_times
		 */
		protected $process_times = array();

		/**
		 * Data Slug used to identify each instance.
		 *
		 * @var string $data_slug
		 */
		protected $data_slug;

		/**
		 * Meta Key used to identify each instance.
		 *
		 * @var string $meta_key
		 */
		protected $meta_key;

		/**
		 * Transient Prefix
		 *
		 * @var string $transient_prefix
		 */
		protected $transient_prefix = 'ld-upgraded-';

		/**
		 * Transient Key
		 *
		 * @var string $transient_key
		 */
		protected $transient_key = '';

		/**
		 * Transient Data
		 *
		 * @var array $transient_data
		 */
		protected $transient_data = array();

		/**
		 * Data Settings Loaded
		 *
		 * @var boolean $data_settings_loaded
		 */
		protected $data_settings_loaded = false;

		/**
		 * Data Settings array
		 *
		 * @var array $data_settings
		 */
		protected $data_settings = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		protected function __construct() {
			$this->meta_key = $this->transient_prefix . $this->data_slug;

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			/**
			 * Filters value of process time percentage.
			 *
			 * @since 2.3.0
			 *
			 * @param int $process_time_percent Process time percentage.
			 */
			$process_time_percent = apply_filters( 'learndash_process_time_percent', 80 );
			if ( ! defined( 'LEARNDASH_PROCESS_TIME_PERCENT' ) ) {
				/**
				 * Define LearnDash LMS - Set the processing time (percent) for Data
				 * Upgrade and Report Export processing.
				 *
				 * During the Data Upgrade or Reporting processing there is a series of AJAX
				 * calls made. This define controls how long the AJAX call can run before
				 * returning and starting a new AJAX process.
				 *
				 * @since 2.3.0
				 *
				 * @var int $process_time_percent Default is 80 percent.
				 */
				define( 'LEARNDASH_PROCESS_TIME_PERCENT', $process_time_percent );
			}

			/**
			 * Filters value of process time seconds.
			 *
			 * @since 2.3.0
			 *
			 * @param int $process_time_seconds Process time seconds.
			 */
			$process_time_seconds = apply_filters( 'learndash_process_time_seconds', 10 );
			if ( ! defined( 'LEARNDASH_PROCESS_TIME_SECONDS' ) ) {
				/**
				 * Define LearnDash LMS - Set the processing time (seconds) for Data
				 * Upgrade and Report Export processing.
				 *
				 * During the Data Upgrade or Reporting processing there is a series of AJAX
				 * calls made. This define controls how long the AJAX call can run before
				 * returning and starting a new AJAX process.
				 *
				 * @since 2.3.0
				 *
				 * @var int $process_time_seconds Default is 10 seconds.
				 */
				define( 'LEARNDASH_PROCESS_TIME_SECONDS', $process_time_seconds );
			}
		}

		/**
		 * Get the current instance of this class or new.
		 *
		 * @since 2.6.0
		 *
		 * @param string $instance_key Unique identifier for instance.
		 *
		 * @return object|null instance of class or null.
		 */
		final public static function get_instance( $instance_key = '' ) {
			if ( ! empty( $instance_key ) ) {
				if ( isset( self::$_instances[ $instance_key ] ) ) {
					return self::$_instances[ $instance_key ];
				}
			} else {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}
			return null;
		}

		/**
		 * Add instance to static tracking array
		 *
		 * @since 2.6.0
		 */
		final public static function add_instance() {
			$section = get_called_class();

			if ( ! isset( self::$_instances[ $section ] ) ) {
				self::$_instances[ $section ] = new $section();
			}
		}

		/**
		 * Register the data upgrade action.
		 *
		 * @since 2.6.0
		 */
		public function register_upgrade_action() {
			// Add ourselves to the upgrade actions.
			if ( ! isset( self::$upgrade_actions[ $this->data_slug ] ) ) {
				self::$upgrade_actions[ $this->data_slug ] = array(
					'class'    => get_called_class(),
					'instance' => $this,
					'slug'     => $this->data_slug,
				);
			}
		}

		/**
		 * Initialize the LearnDash Settings array
		 *
		 * @since 2.6.0
		 *
		 * @param bool $force_reload optional to force reload from database.
		 *
		 * @return void.
		 */
		private function init_data_settings( $force_reload = false ) {

			if ( ( true !== $this->data_settings_loaded ) || ( true === $force_reload ) ) {
				$this->data_settings_loaded = true;

				$this->data_settings = get_option( 'learndash_data_settings', array() );

				$data_settings_changed = false;

				if ( ! isset( $this->data_settings['db_version'] ) ) {
					$this->data_settings['db_version'] = 0;
				}

				if ( ! isset( $this->data_settings['version_history'] ) ) {
					$this->data_settings['version_history'] = array();
				}

				if ( ! isset( $this->data_settings['prior_version'] ) ) {
					$this->data_settings['prior_version'] = '';
				}

				if ( empty( $this->data_settings['prior_version'] ) ) {
					if ( get_option( 'learndash_quiz_migration_completed' ) ) {
						// If we have a prior version of LD.
						$this->data_settings['prior_version'] = '0.0.0.0';
					} else {
						// Else we have a new install.
						$this->data_settings['prior_version'] = 'new';
					}
					$this->data_settings['version_history'][0] = $this->data_settings['prior_version'];
					$data_settings_changed                     = true;
				}

				if ( ! isset( $this->data_settings['current_version'] ) ) {
					$this->data_settings['current_version'] = 0;
				}

				if ( version_compare( LEARNDASH_VERSION, $this->data_settings['current_version'], 'ne' ) ) {
					if ( ! empty( $this->data_settings['current_version'] ) ) {
						$this->data_settings['prior_version'] = $this->data_settings['current_version'];
						if ( ! isset( $this->data_settings['version_history'][0] ) ) {
							$this->data_settings['version_history'][0] = $this->data_settings['prior_version'];
						}
					}
					// Set the upgrade flag to trigger 'activate' logic.
					$this->data_settings['is_upgrade'] = true;

					$this->data_settings['current_version']           = LEARNDASH_VERSION;
					$this->data_settings['version_history'][ time() ] = LEARNDASH_VERSION;
					$data_settings_changed                            = true;
				}

				if ( empty( $this->data_settings['version_history'] ) ) {
					$this->data_settings['version_history'][ time() ] = $this->data_settings['current_version'];
					$this->data_settings['version_history'][0]        = $this->data_settings['prior_version'];
					$data_settings_changed                            = true;
				}

				if ( true === $data_settings_changed ) {
					krsort( $this->data_settings['version_history'] );
					$this->data_settings['version_history'] = array_slice( $this->data_settings['version_history'], 0, 25, true );

					update_option( 'learndash_data_settings', $this->data_settings );
				}
			}
		}

		/**
		 * Get the LearnDash Settings array
		 *
		 * @since 2.6.0
		 *
		 * @param string $key optional to return only specific key value.
		 *
		 * @return mixed.
		 */
		public function get_data_settings( $key = '' ) {
			$this->init_data_settings( true );

			if ( ! empty( $key ) ) {
				if ( isset( $this->data_settings[ $key ] ) ) {
					return $this->data_settings[ $key ];
				}
			} else {
				return $this->data_settings;
			}
		}

		/**
		 * Set data upgrade option for instance.
		 *
		 * @since 2.6.0
		 *
		 * @param string $key   Key to data upgrade instance.
		 * @param mixed  $value Value for key instance.
		 */
		public function set_data_settings( $key = '', $value = '' ) {
			if ( empty( $key ) ) {
				return;
			}

			$this->init_data_settings( true );
			$this->data_settings[ $key ] = $value;

			return update_option( 'learndash_data_settings', $this->data_settings );
		}

		/**
		 * General admin_init hook function to check admin notices.
		 *
		 * @since 2.6.0
		 */
		public function admin_init() {

			$this->init_data_settings();

			if ( true === $this->check_upgrade_admin_notice() ) {
				add_action( 'admin_notices', array( $this, 'show_upgrade_admin_notice' ) );
			}
		}

		/**
		 * Shows Data Upgrade admin notice.
		 *
		 * @version 2.6.0
		 */
		public function show_upgrade_admin_notice() {
			if ( true !== self::$admin_notice_shown ) {
				self::$admin_notice_shown = true;

				$admin_notice_message = sprintf(
					// translators: placeholder: link to LearnDash Data Upgrade admin page.
					esc_html_x( 'LearnDash Notice: Please perform a %s. This is a required step to ensure accurate reporting.', 'placeholder: link to LearnDash Data Upgrade admin page', 'learndash' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=learndash_data_upgrades' ) ) . '">' . esc_html__( 'LearnDash Data Upgrade', 'learndash' ) . '</a>'
				);
				?>
				<div id="ld-data-upgrade-notice-error" class="notice notice-info is-dismissible">
					<p><?php echo $admin_notice_message; ?></p> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when defined ?>
				</div>
				<?php
			}
		}

		/**
		 * Trigger admin notice if Data Upgrades need to be performed.
		 *
		 * @since 2.6.0
		 */
		public function check_upgrade_admin_notice() {
			$show_admin_notice = false;

			if ( ( isset( $this->data_settings['user-meta-courses']['version'] ) ) && ( $this->data_settings['user-meta-courses']['version'] < LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION ) ) {
				$show_admin_notice = true;
			}

			if ( ( isset( $this->data_settings['user-meta-quizzes']['version'] ) ) && ( $this->data_settings['user-meta-quizzes']['version'] < LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION ) ) {
				$show_admin_notice = true;
			}

			return $show_admin_notice;
		}

		/**
		 * Show the admin page content.
		 *
		 * @since 2.6.0
		 */
		public function admin_page() {
			$banner_message = esc_html__( 'The Data Upgrades should only be run if prompted or advised by LearnDash Support. There is no need to re-run the Data Upgrades every time you update LearnDash core or one of the add-ons. Re-running the data upgrades when not needed can result in data corruption.', 'learndash' );

			$banner_content = '<div class="ld-settings-info-banner ld-settings-info-banner-alert">' . wpautop( wptexturize( $banner_message ) ) . '</div>';
			echo wp_kses_post( $banner_content );

			?>
			<table id="learndash-data-upgrades" class="wc_status_table widefat" cellspacing="0">
			<?php
			wp_nonce_field( 'learndash-data-upgrades-nonce-' . get_current_user_id(), 'learndash-data-upgrades-nonce' );
			foreach ( self::$upgrade_actions as $upgrade_action_slug => $upgrade_action ) {
				$upgrade_action['instance']->show_upgrade_action();
			}
			?>
			</table>
			<?php
		}

		/**
		 * Placeholder function. This function is called when displaying the admin page.
		 *
		 * @since 2.6.0
		 */
		public function show_upgrade_action() {
			// Does nothing.
		}

		/**
		 * Placeholder function. This function is called when processing the upgrade action.
		 *
		 * @since 2.6.0
		 */
		public function process_upgrade_action() {
			// Does nothing.
		}

		/**
		 * Set the last run completed data upgrade for instance.
		 *
		 * @since 2.6.0
		 *
		 * @param array $data Last run data array.
		 */
		public function set_last_run_info( $data = array() ) {
			$data_settings = array_merge(
				array(
					'last_run' => time(),
					'user_id'  => get_current_user_id(),
					'version'  => LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION,
				),
				$data
			);

			$data_settings = array_diff_key(
				$data_settings,
				array(
					'nonce'            => '',
					'slug'             => '',
					'continue'         => '',
					'progress_label'   => '',
					'result_count'     => '',
					'progress_percent' => '',
				)
			);

			$this->set_data_settings( $this->data_slug, $data_settings );
		}

		/**
		 * Return the last run details for the last completed data upgrade for the instance.
		 *
		 * @since 2.6.0
		 */
		public function get_last_run_info() {
			$last_run_info = '';

			$data_settings = $this->get_data_settings( $this->data_slug );

			$last_run_info = esc_html__( 'Last run: none', 'learndash' );
			if ( ! empty( $data_settings ) ) {
				if ( isset( $data_settings['user_id'] ) ) {
					$user = get_user_by( 'id', $data_settings['user_id'] );
					if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
						$last_run_info = sprintf(
							// translators: placeholders: date/time, user name.
							_x( 'Last run: %1$s by %2$s', 'placeholders: date/time, user name', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings['last_run'] ),
							$user->display_name
						);
					}
				}
			}

			return $last_run_info;
		}

		/**
		 * Entry point to perform data upgrade for instance.
		 *
		 * @since 2.6.0
		 *
		 * @param array $post_data Array of post data sent via AJAX.
		 * @param array $reply_data Array of return data returned to browser.
		 *
		 * @return array $reply_data.
		 */
		public function do_data_upgrades( $post_data = array(), $reply_data = array() ) {

			if ( ( isset( $post_data['slug'] ) ) && ( ! empty( $post_data['slug'] ) ) ) {
				$post_data_slug = esc_attr( $post_data['slug'] );

				if ( isset( self::$upgrade_actions[ $post_data_slug ] ) ) {
					if ( isset( $post_data['data'] ) ) {
						$data = $post_data['data'];
					} else {
						$data = array();
					}

					$reply_data = self::$upgrade_actions[ $post_data_slug ]['instance']->process_upgrade_action( $post_data );
				}
			}
			return $reply_data;
		}

		/**
		 * Initialize the processing timer.
		 *
		 * @since 2.6.0
		 */
		protected function init_process_times() {
			$this->process_times['started'] = time();
			$this->process_times['limit']   = intval( ini_get( 'max_execution_time' ) );
			if ( empty( $this->process_times['limit'] ) ) {
				$this->process_times['limit'] = 60;
			}
		}

		/**
		 * Check if the process timer is out of time.
		 *
		 * @since 2.6.0
		 */
		protected function out_of_timer() {
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
		 * Remove the processing transient for instance.
		 *
		 * @since 2.6.0
		 *
		 * @param string $transient_key Transient key to identify transient.
		 */
		protected function remove_transient( $transient_key = '' ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );
				return delete_option( $options_key );
			}
		}

		/**
		 * Get the processing transient for instance.
		 *
		 * @since 2.6.0
		 *
		 * @param string $transient_key Transient key to identify transient.
		 * @return mixed transient data.
		 */
		protected function get_transient( $transient_key = '' ) {
			$transient_data = array();
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );

				if ( ( defined( 'LEARNDASH_TRANSIENT_CACHE_STORAGE' ) ) && ( 'file' === LEARNDASH_TRANSIENT_CACHE_STORAGE ) ) { // @phpstan-ignore-line
					$wp_upload_dir = wp_upload_dir();

					$ld_file_part = '/learndash/cache/learndash_data_upgrade_' . $options_key . '.txt';

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
								$transient_data .= fread( $transient_fp, 4096 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
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
		 * Set the processing transient for instance.
		 *
		 * @since 3.1.0
		 *
		 * @param string $transient_key Transient key to identify transient.
		 * @param array  $transient_data Array for transient data.
		 *
		 * @return void
		 */
		protected function set_option_cache( $transient_key = '', $transient_data = array() ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );

				if ( ! empty( $transient_data ) ) {
					if ( ( defined( 'LEARNDASH_TRANSIENT_CACHE_STORAGE' ) ) && ( 'file' === LEARNDASH_TRANSIENT_CACHE_STORAGE ) ) { // @phpstan-ignore-line
						$wp_upload_dir = wp_upload_dir();

						$ld_file_part = '/learndash/cache/learndash_data_upgrade_' . $options_key . '.txt';

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
							fwrite( $transient_fp, serialize( $transient_data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
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
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-translations.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-group-leader-role.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-course-post-meta.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-group-post-meta.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-quiz-post-meta.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-user-activity-db-table.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-user-meta-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-user-meta-quizzes.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-quiz-questions.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-course-access-list-convert.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-rename_wpproquiz-tables.php';

/**
 * Fires on admin data upgrades init
 *
 * @since 2.6.0
 */
do_action( 'learndash_data_upgrades_init' );

/**
 * AJAX function to handle calls from browser on Data Upgrade cycles.
 *
 * @since 2.3.0
 *
 * @return void
 */
function learndash_data_upgrades_ajax() {

	$reply_data = array( 'status' => false );

	if ( ( is_user_logged_in() ) && ( learndash_is_admin_user() ) ) {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'learndash-data-upgrades-nonce-' . get_current_user_id() ) ) ) {

			if ( ( isset( $_POST['data'] ) ) && ( ! empty( $_POST['data'] ) ) ) {
				$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
				$reply_data['data']     = $ld_admin_data_upgrades->do_data_upgrades( $_POST['data'], $reply_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				echo wp_json_encode( $reply_data );
			}
		}
	}
	wp_die();
}

add_action( 'wp_ajax_learndash-data-upgrades', 'learndash_data_upgrades_ajax' );


/**
 * Utility function to check if the data upgrade for Quiz Questions has been run.
 *
 * @since 2.6.0
 * @since 3.3.0 Renamed to learndash_is_data_upgrade_quiz_questions_updated
 * @return boolean true if has been run.
 */
function learndash_is_data_upgrade_quiz_questions_updated() {
	$data_settings_quiz_questions = learndash_data_upgrades_setting( 'pro-quiz-questions' );
	if ( ( isset( $data_settings_quiz_questions['last_run'] ) ) && ( ! empty( $data_settings_quiz_questions['last_run'] ) ) ) {
		return true;
	}
	return false;
}

/**
 * Utility function to get the data upgrade settings.
 *
 * @since 3.1.3
 *
 * @param string $settings_key Settings key.
 */
function learndash_data_upgrades_setting( $settings_key = '' ) {
	$element = Learndash_Admin_Data_Upgrades::get_instance();
	if ( ( $element ) && ( is_a( $element, 'Learndash_Admin_Data_Upgrades' ) ) ) {
		return $element->get_data_settings( $settings_key );
	}
}
