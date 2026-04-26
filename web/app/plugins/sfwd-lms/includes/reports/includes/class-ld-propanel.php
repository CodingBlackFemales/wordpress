<?php
/**
 * Set up LearnDash ProPanel
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\App;

/**
 * LearnDash Reporting Class.
 *
 * @since 4.17.0
 */
#[AllowDynamicProperties]
class LearnDash_ProPanel {
	/**
	 * Overview widget.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Overview
	 */
	public $overview_widget;

	/**
	 * Filtering widget.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Filtering
	 */
	public $filtering_widget;

	/**
	 * Reporting widget.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Reporting
	 */
	public $reporting_widget;

	/**
	 * Activity widget.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Activity
	 */
	public $activity_widget;

	/**
	 * Progress chart widget.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Progress_Chart
	 */
	public $progress_chart_widget;

	/**
	 * @var LearnDash_ProPanel The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return LearnDash_ProPanel The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Override class function for 'this'.
	 *
	 * This function handles out Singleton logic in
	 *
	 * @return reference to current instance
	 */
	static function this() {
		return self::$instance;
	}

	/**
	 * LearnDash_ProPanel constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'reporting_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), apply_filters( 'ld_propanel_admin_enqueue_scripts_priority', 5 ) );
		add_action( 'enqueue_scripts', array( $this, 'scripts' ), apply_filters( 'ld_propanel_enqueue_scripts_priority', 5 ) );

		add_action( 'parse_request', array( $this, 'parse_request' ), 1 );

		add_filter( 'learndash_shortcodes_content_args', array( $this, 'add_ld_tinymce_shortcode' ) );
	}

	/**
	 * Adds the reporting page to the admin menu.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	function reporting_page() {
		$menu_user_cap = '';

		if ( learndash_is_admin_user() ) {
			$menu_user_cap = LEARNDASH_ADMIN_CAPABILITY_CHECK;
		} elseif ( learndash_is_group_leader_user() ) {
			$menu_user_cap = LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK;
		}

		if ( ! empty( $menu_user_cap ) ) {
			$r_page = add_submenu_page(
				'',
				esc_html__( 'ProPanel Reporting', 'learndash' ),
				esc_html__( 'ProPanel Reporting', 'learndash' ),
				$menu_user_cap,
				'propanel-reporting',
				array( $this, 'admin_full_page_output' )
			);

			// Found out the following is needed needed mainly for group leaders to be able to see the full page reporting screen. Not really needed for admin users.
			global $_registered_pages;
			$_registered_pages['admin_page_propanel-reporting'] = true;
		}
	}

	function admin_full_page_output() {
		$this->init();

		ob_start();
		$container_type = 'full';
		include ld_propanel_get_template( 'ld-propanel-full-admin.php' );
		echo ob_get_clean();
	}

	function parse_request() {
		// $current_template = get_current_template();
		// error_log('current_template['. $current_template .']');

		// if ( is_page_template('ld-propanel-full-page.php') ) {
		// error_log('ARE using the template');
		// } else {
		// error_log('NOT using the template');
		// }

		// Check if we are doing the full page front-end ld_propanel template
		if ( ( ! is_admin() ) && ( isset( $_GET['ld_propanel'] ) ) ) {
			if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) || ( current_user_can( 'propanel_widgets' ) ) ) {
				$this->scripts( true );

				$template_full_page_css = ld_propanel_get_template( 'ld-propanel-full-page.css' );
				if ( ! empty( $template_full_page_css ) ) {
					$template_full_page_css_url = learndash_template_url_from_path( $template_full_page_css );
					wp_enqueue_style( 'ld-propanel-full-page-style', $template_full_page_css_url, null, LD_PP_VERSION );
				}

				ob_start();
				include ld_propanel_get_template( 'ld-propanel-full-page.php' );
				echo ob_get_clean();
				die();
			}
		}
	}

	function add_ld_tinymce_shortcode( $shortcode_sections = array() ) {
		if ( is_admin() ) {
			$fields_args = array(
				'post_type' => '',
			);

			if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) {
				$fields_args['post_type'] = esc_attr( $_GET['post_type'] );
			}

			if ( $fields_args['post_type'] != 'sfwd-certificates' ) {
				require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-tinymce-courseinfo.php';
				$shortcode_sections['ld_propanel'] = new LearnDash_Shortcodes_Section_ld_propanel( array() );
			}
		}

		return $shortcode_sections;
	}

	public function init() {
		$this->includes();
	}

	/**
	 * Notify user that LearnDash is required.
	 */
	public function notify_user_learndash_required() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'LearnDash is required to be activated before LearnDash ProPanel can work properly.', 'learndash' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Load ProPanel
	 */
	private function includes() {
		if ( is_admin() ) {
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-base-widget.php';

			// ProPanel Overview
			if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
				require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-overview.php';
				$this->overview_widget = new LearnDash_ProPanel_Overview();
			}

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-filtering.php';
			$this->filtering_widget = new LearnDash_ProPanel_Filtering();

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-reporting.php';
			$this->reporting_widget = new LearnDash_ProPanel_Reporting();

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-activity.php';
			$this->activity_widget = new LearnDash_ProPanel_Activity();

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-progress-chart.php';
			$this->progress_chart_widget = new LearnDash_ProPanel_Progress_Chart();

			// require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-trends.php';
			// $this->trends_widget = new LearnDash_ProPanel_Trends();

			require_once LD_PP_PLUGIN_DIR . 'includes/functions.php';

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-shortcodes.php';

			LearnDash_ProPanel_Shortcode::get_instance();

			// LearnDash_ProPanel_Shortcodes_Filtering::get_instance();

			// if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
			// LearnDash_ProPanel_Shortcodes_Overview::get_instance();
			// }

			// LearnDash_ProPanel_Shortcodes_Activity::get_instance();
			// LearnDash_ProPanel_Shortcodes_Reporting::get_instance();
			// LearnDash_ProPanel_Shortcodes_Progress_Chart::get_instance();
		} else {
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-base-widget.php';

			// ProPanel Overview
			if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
				require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-overview.php';
				$this->overview_widget = new LearnDash_ProPanel_Overview();
			}

			// ProPanel Filtering
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-filtering.php';
			$this->filtering_widget = new LearnDash_ProPanel_Filtering();

			// ProPanel Reporting
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-reporting.php';
			$this->reporting_widget = new LearnDash_ProPanel_Reporting();

			// ProPanel Activity
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-activity.php';
			$this->activity_widget = new LearnDash_ProPanel_Activity();

			// ProPanel Charts
			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-progress-chart.php';
			$this->progress_chart_widget = new LearnDash_ProPanel_Progress_Chart();

			require_once LD_PP_PLUGIN_DIR . 'includes/functions.php';

			require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-shortcodes.php';

			LearnDash_ProPanel_Shortcode::get_instance();

			// LearnDash_ProPanel_Shortcodes_Filtering::get_instance();

			// if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
			// LearnDash_ProPanel_Shortcodes_Overview::get_instance();
			// }

			// LearnDash_ProPanel_Shortcodes_Activity::get_instance();
			// LearnDash_ProPanel_Shortcodes_Reporting::get_instance();
			// LearnDash_ProPanel_Shortcodes_Progress_Chart::get_instance();
			// LearnDash_ProPanel_Shortcodes_Link::get_instance();
		}

		require_once LD_PP_PLUGIN_DIR . 'includes/class-ld-propanel-rest.php';

		LearnDash_ProPanel_REST::get_instance();
	}

	/**
	 * Register scripts for any widgets that may need to enqueue them.
	 *
	 * @since 4.17.0
	 *
	 * @param bool $force_load_scripts Whether to force load the scripts. Default false.
	 *
	 * @return void
	 */
	public function scripts( $force_load_scripts = false ) {
		$is_dashboard = false;

		if ( is_admin() ) {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();

				if (
					in_array(
						$screen->id,
						[
							'dashboard',
							'dashboard_page_propanel-reporting',
							'admin_page_' . Cast::to_string( App::getVar( 'learndash_settings_reports_page_id' ) ),
						],
						true
					)
				) {
					$force_load_scripts = true;
					$is_dashboard       = true;
				}
			}

			/**
			 * Filters whether to show the ProPanel widgets on the Dashboard.
			 *
			 * @since 4.17.0
			 *
			 * @param boolean $is_dashboard Whether to show the Dashboard widgets.
			 */
			$is_dashboard = apply_filters( 'ld_propanel_dashboard_show_widgets', $is_dashboard );
			if ( true !== $is_dashboard ) {
				return;
			}
		}

		if ( true === $force_load_scripts ) {
			$ld_script_prerequisite = array( 'jquery' );

			wp_register_script( 'ld-propanel-chart-script', LD_PP_PLUGIN_URL . 'dist/vendor/Chart.js', array( 'jquery' ), LD_PP_VERSION, false );
			$ld_script_prerequisite[] = 'ld-propanel-chart-script';

			wp_register_script( 'ld-propanel-flatpickr-script', LD_PP_PLUGIN_URL . 'dist/vendor/flatpickr/flatpickr.min.js', array(), LD_PP_VERSION, true );
			$ld_script_prerequisite[] = 'ld-propanel-flatpickr-script';
			wp_enqueue_style( 'ld-propanel-flatpickr-style', LD_PP_PLUGIN_URL . 'dist/vendor/flatpickr/flatpickr.min.css' );

			wp_register_script( 'ld-propanel-select2-script', LD_PP_PLUGIN_URL . 'dist/vendor/select2-jquery/js/select2.full.min.js', array( 'jquery' ), '4.0.3', true );
			$ld_script_prerequisite[] = 'ld-propanel-select2-script';
			wp_enqueue_style( 'ld-propanel-select2-style', LD_PP_PLUGIN_URL . 'dist/vendor/select2-jquery/css/select2.min.css' );

			wp_register_script( 'ld-propanel-script', LD_PP_PLUGIN_URL . 'dist/js/ld-propanel.js', $ld_script_prerequisite, LD_PP_VERSION, true );

			$pager_values = ld_propanel_get_pager_values();
			if ( empty( $pager_values ) ) {
				$pager_values = array( get_option( 'posts_per_page' ) );
			}

			$date_format = get_option( 'date_format', 'Y-m-d' );
			if ( empty( $date_format ) ) {
				$date_format = 'Y-m-d';
			}
			// Convert the PHP date params to match the params available in flatpickr JS.
			if ( ! empty( $date_format ) ) {
				$date_format = str_replace(
					array( 'jS', 'dS' ),
					array( 'J', 'J' ),
					$date_format
				);
			}

			$time_format = get_option( 'time_format', 'H:i:s' );
			if ( empty( $time_format ) ) {
				$time_format = 'H:i:s';
			}
			// Convert the PHP time params to match the params available in flatpickr JS.
			if ( ! empty( $time_format ) ) {
				$time_format = str_replace(
					array( 'g', 'a', 'A', 'T' ),
					array( 'G', 'K', 'K', '' ),
					$time_format
				);
			}

			global $wp_locale;

			$flatpickr_locale = array();

			if ( is_admin() ) {
				$flatpickr_locale['months']   = array(
					'longhand'  => array_values( $wp_locale->month ),
					'shorthand' => array_values( $wp_locale->month_abbrev ),
				);
				$flatpickr_locale['weekdays'] = array(
					'longhand'  => array_values( $wp_locale->weekday ),
					'shorthand' => array_values( $wp_locale->weekday_abbrev ),
				);
			}

			if ( ( empty( $wp_locale->meridiem['am'] ) ) && ( empty( $wp_locale->meridiem['AM'] ) ) ) {
				$flatpickr_locale['time_24hr'] = 1;
			} else {
				$flatpickr_locale['time_24hr'] = 0;
			}

			$flatpickr_locale['hourAriaLabel']    = esc_html__( 'Hour', 'learndash' );
			$flatpickr_locale['minuteAriaLabel']  = esc_html__( 'Minute', 'learndash' );
			$flatpickr_locale['monthAriaLabel']   = esc_html__( 'Month', 'learndash' );
			$flatpickr_locale['monthAriaLabel']   = esc_html__( 'Month', 'learndash' );
			$flatpickr_locale['rangeSeparator']   = esc_html__( ' to ', 'learndash' );
			$flatpickr_locale['scrollTitle']      = esc_html__( 'Scroll to increment', 'learndash' );
			$flatpickr_locale['toggleTitle']      = esc_html__( 'Click to toggle', 'learndash' );
			$flatpickr_locale['weekAbbreviation'] = esc_html__( 'Wk', 'learndash' );
			$flatpickr_locale['yearAriaLabel']    = esc_html__( 'Year', 'learndash' );

			wp_localize_script(
				'ld-propanel-script',
				'ld_propanel_settings',
				array(
					'nonce'                      => wp_create_nonce( 'ld-propanel' ),
					'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
					'spinner_admin_img'          => admin_url( '/images/spinner.gif' ),
					'is_dashboard'               => $is_dashboard,
					'is_debug'                   => false,
					'template_load_delay'        => apply_filters( 'ld_propanel_js_template_load_delay', 1000 ),
					'default_per_page'           => $pager_values[0],
					'lang'                       => ( isset( $_GET['lang'] ) ) ? esc_attr( $_GET['lang'] ) : '',
					'flatpickr_locale'           => $flatpickr_locale,
					'flatpickr_date_time_format' => $date_format . ' ' . $time_format,
				)
			);
			wp_enqueue_script( 'ld-propanel-script' );

			wp_enqueue_style( 'dashicons' );

			wp_register_style( 'ld-propanel-style', LD_PP_PLUGIN_URL . 'dist/css/ld-propanel.css', null, LD_PP_VERSION );
			wp_enqueue_style( 'ld-propanel-style' );
			wp_style_add_data( 'ld-propanel-style', 'rtl', 'replace' );

			global $learndash_assets_loaded;
			if ( ! isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) {
				$filepath = SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );
				if ( ! empty( $filepath ) ) {
					wp_enqueue_script( 'learndash_template_script_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
					$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;

					$data            = array();
					$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
					$data            = array( 'json' => json_encode( $data ) );
					wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $data );
				}
			}

			LD_QuizPro::showModalWindow();
		}
	}
}
