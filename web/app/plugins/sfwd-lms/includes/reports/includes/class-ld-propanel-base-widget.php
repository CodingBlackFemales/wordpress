<?php
/**
 * LearnDash ProPanel Widget Base
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnDash_ProPanel_Widget' ) ) {
	/**
	 * Base class for ProPanel dashboard widgets.
	 *
	 * @since 4.17.0
	 */
	class LearnDash_ProPanel_Widget {
		/**
		 * Singleton instance reference.
		 *
		 * @since 4.17.0
		 *
		 * @var static|null
		 */
		private static $instance;

		/**
		 * Posted filter data.
		 *
		 * @since 4.17.0
		 *
		 * @var array<string, mixed>
		 */
		protected $post_data = array();

		/**
		 * Activity query arguments from filters.
		 *
		 * @since 4.17.0
		 *
		 * @var array<string, mixed>
		 */
		protected $activity_query_args = array();

		/**
		 * Registered filter widgets.
		 *
		 * @since 4.17.0
		 *
		 * @var array<string, mixed>
		 */
		protected $registered_filters = array();

		/**
		 * Filter key identifier.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $filter_key;

		/**
		 * Search placeholder for filter UI.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $filter_search_placeholder;

		/**
		 * Filter table column headers.
		 *
		 * @since 4.17.0
		 *
		 * @var array<int|string, string>
		 */
		protected $filter_headers = array();

		/**
		 * Path to filter table template.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $filter_template_table;

		/**
		 * Path to filter row template.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $filter_template_row;

		/**
		 * The name key which is used to identify this widget.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $name = '';

		/**
		 * The label used as the widget title.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $label = '';

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return LearnDash_ProPanel_Widget The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === static::$instance ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Registers WordPress hooks for this widget.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
			add_action( 'wp_ajax_learndash_propanel_template', array( $this, 'load_template' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 1000 );
			add_action( 'enqueue_scripts', array( $this, 'scripts' ), 1000 );
		}

		/**
		 * Enqueues ProPanel scripts and styles where applicable.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function scripts() {
			if ( is_admin() ) {
				$screen = get_current_screen();

				if ( in_array( $screen->id, array( 'dashboard', 'dashboard_page_propanel-reporting' ), true ) ) {
					$menu_user_cap = '';

					if ( learndash_is_admin_user() ) {
						$menu_user_cap = LEARNDASH_ADMIN_CAPABILITY_CHECK;
					} elseif ( learndash_is_group_leader_user() ) {
						$menu_user_cap = LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK;
					} elseif ( current_user_can( 'propanel_widgets' ) ) {
						$menu_user_cap = 'propanel_widgets';
					}

					if ( ! empty( $menu_user_cap ) ) {
						// Specific code to deregister the BadgeOS version of select JS libs. This seems to
						// cause a conflict with the version needed for PP on the Dashboard.
						wp_deregister_script( 'badgeos-select2' );
						wp_deregister_style( 'badgeos-select2-css' );

						wp_enqueue_script( 'ld-propanel-select2-script' );
						wp_enqueue_script( 'ld-propanel-chart-script' );

						wp_enqueue_style( 'ld-propanel-select2-style' );

						wp_localize_script(
							'ld-propanel-script',
							'ld_propanel_reporting',
							array(
								/**
								 * Filter CSV Export File Name
								 */
								// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy ProPanel filter.
								'filename'         => apply_filters( 'ld_propanel_export_filename', 'learndash-report-' . current_time( 'Y-m-d' ) ) . '.csv',
								'ajax_email_error' => esc_html__( 'ProPanel Email: AJAX submission could not complete, please try again.', 'learndash' ),
							)
						);
					} else {
						wp_deregister_script( 'ld-propanel-select2-script' );
						wp_deregister_script( 'ld-propanel-chart-script' );
						wp_deregister_style( 'ld-propanel-select2-style' );
					}
				}
			} else {
				wp_enqueue_style( 'ld-propanel-select2-style' );
			}
		}

		/**
		 * Registers the dashboard widget when the user may access ProPanel.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function register_widget() {
			// Only show the ProPanel widgets for admin and group leaders, or users with ProPanel access.
			if (
				learndash_is_group_leader_user()
				|| learndash_is_admin_user()
				|| current_user_can( 'propanel_widgets' )
			) {
				$is_dashboard = true;
			} else {
				$is_dashboard = false;
			}

			/** This filter is documented in includes/class-ld-propanel.php */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy ProPanel filter.
			$is_dashboard = apply_filters( 'ld_propanel_dashboard_show_widgets', $is_dashboard );
			if ( true === $is_dashboard ) {
				wp_add_dashboard_widget( 'learndash-propanel-' . $this->name, $this->label, array( $this, 'initial_template' ) );
			}
		}

		/**
		 * Outputs the widget container markup (override in subclasses).
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function initial_template() {}

		/**
		 * AJAX handler: renders a ProPanel fragment by template name.
		 *
		 * @since 4.17.0
		 *
		 * @return void
		 */
		public function load_template() {
			check_ajax_referer( 'ld-propanel', 'nonce' );

			if (
				learndash_is_admin_user()
				|| learndash_is_group_leader_user()
				|| current_user_can( 'propanel_widgets' )
			) {
				$template = isset( $_GET['template'] ) ? sanitize_text_field( wp_unslash( $_GET['template'] ) ) : '';
				if ( '' !== $template ) {
					$output = apply_filters( 'learndash_propanel_template_ajax', '', $template );
					wp_send_json_success( array( 'output' => $output ) );
				} else {
					die();
				}
			} else {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'learndash' ) ), 403 );
			}
		}

		/**
		 * Returns the name key which is used to identify this widget.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function get_name(): string {
			return $this->name;
		}

		/**
		 * Returns the label used as the widget title.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function get_label(): string {
			return $this->label;
		}
	}
}
