<?php
/**
 * LearnDash Settings Section for Support Database Tables Metabox.
 *
 * @since 3.1.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_Database_Tables' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Support Database Tables Metabox.
	 *
	 * @since 3.1.0
	 */
	class LearnDash_Settings_Section_Support_Database_Tables extends LearnDash_Settings_Section {

		/**
		 * Array of DB tables
		 *
		 * @var array
		 */
		protected $db_tables = array();

		/**
		 * Array of System Info
		 *
		 * @var array
		 */
		protected $system_info = array();

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * Settings tables.
		 *
		 * @var array $admin_notice_tables Array of settings tables.
		 */
		protected $admin_notice_tables = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 3.1.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'ld_database_tables';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_ld_database_tables';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Database Tables', 'learndash' );

			$this->load_options = false;

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			add_action( 'admin_notices', array( $this, 'admin_notice_upgrade_notice' ) );
			parent::__construct();
		}

		/**
		 * Support Sections Init
		 *
		 * @since 3.1.0
		 *
		 * @param array $support_sections Support sections array.
		 */
		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			/************************************************************************************************
			 * Learndash Database Tables
			 */
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {

				$this->settings_set = array();

				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Table Name', 'learndash' ),
						'text'  => 'Table Name',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Present', 'learndash' ),
						'text'  => 'Present',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['desc'] = '<p>' . esc_html__( 'When the LearnDash plugin or related add-ons are activated they will create the following tables. If the tables are not present try reactivating the plugin. If the table still do not show check the DB_USER defined in your wp-config.php and ensure it has the proper permissions to create tables. Check with your host for help.', 'learndash' ) . '</p>';

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['ld_debug'] ) ) {
					$grants = learndash_get_db_user_grants();
					if ( ! is_array( $grants ) ) {
						$grants = array();
					}

					if ( ( array_search( 'ALL PRIVILEGES', $grants, true ) === false ) && ( array_search( 'CREATE', $grants, true ) === false ) ) {
						$this->settings_set['desc'] .= '<p style="color: red">' . esc_html__( 'The DB_USER defined in your wp-config.php does not have CREATE permission.', 'learndash' ) . '</p>';
					}
				}

				$this->settings_set['settings'] = array();

				$this->db_tables = LDLMS_DB::get_tables();
				/**
				 * Filters list of database tables for admin support section.
				 *
				 * @param array $db_tables An array of Database tables.
				 */
				$this->db_tables = apply_filters( 'learndash_support_db_tables', $this->db_tables );
				if ( ! empty( $this->db_tables ) ) {
					$this->db_tables = array_unique( $this->db_tables );

					foreach ( $this->db_tables as $db_key => $db_table ) {
						$this->settings_set['settings'][ $db_table ] = array(
							'label' => $db_table,
						);

						$table_status_info = LDLMS_DB::get_table_status_info( $db_key );
						if ( is_array( $table_status_info ) ) {
							$rows_str      = '';
							$rows_html_str = '';

							if ( isset( $table_status_info['rows_count'] ) ) {
								if ( ! empty( $rows_str ) ) {
									$rows_str .= ' ';
								}
								$rows_str .= 'rows(' . absint( $table_status_info['rows_count'] ) . ')';
							}

							if ( isset( $table_status_info['engine'] ) ) {
								if ( ! empty( $rows_str ) ) {
									$rows_str .= ' ';
								}
								$rows_str .= esc_attr( $table_status_info['engine'] );
							}

							if ( isset( $table_status_info['collation'] ) ) {
								if ( ! empty( $rows_str ) ) {
									$rows_str .= ' ';
								}
								$rows_str .= esc_attr( $table_status_info['collation'] );
							}

							if ( ! empty( $rows_str ) ) {
								$rows_str = ' - ' . $rows_str;
							}

							$this->settings_set['settings'][ $db_table ]['value']      = 'Yes' . $rows_str;
							$this->settings_set['settings'][ $db_table ]['value_html'] = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span>' . $rows_str;

							/**
							 * Check the AUTO_INCREMENT index attribute.
							 *
							 * @since 3.1.8
							 */
							$valid_index = LDLMS_DB::check_table_primary_index( $db_key );
							if ( false === $valid_index ) {
								$this->admin_notice_tables[] = $db_table;

								if ( ! empty( $this->settings_set['settings'][ $db_table ]['value'] ) ) {
									$this->settings_set['settings'][ $db_table ]['value'] .= ' ';
								}
								$this->settings_set['settings'][ $db_table ]['value'] .= 'AUTO_INCREMENT missing - (X)';

								if ( ! empty( $this->settings_set['settings'][ $db_table ]['value_html'] ) ) {
									$this->settings_set['settings'][ $db_table ]['value_html'] .= ' ';
								}
								$this->settings_set['settings'][ $db_table ]['value_html'] .= '<span style="color: red">' . esc_html__( 'AUTO_INCREMENT missing', 'learndash' ) . '</span>';
							}
						} else {
							$this->settings_set['settings'][ $db_table ]['value']      = 'No - (X)';
							$this->settings_set['settings'][ $db_table ]['value_html'] = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';
						}
					}
				}
				/**
				 * Filters LearnDash admin support section settings.
				 *
				 * @param array  $settings An array of support section setting details.
				 * @param string $context  The context where the setting is shown like ld_settings, server_settings, wp_settings, ld_templates,
				 * ld_database_tables, wp_active_theme, wp_active_plugins, etc.
				 */
				$this->system_info['ld_database_tables'] = apply_filters( 'learndash_support_section', $this->settings_set, 'ld_database_tables' );

				/** This filter is documented in includes/settings/settings-sections/class-ld-settings-section-support-database-tables.php */
				$support_sections[ $this->setting_option_key ] = apply_filters( 'learndash_support_section', $this->settings_set, $this->setting_option_key );
			}

			return $support_sections;
		}

		/**
		 * Show support section
		 *
		 * @param string $settings_section_key Section key.
		 * @param string $settings_screen_id   Screen ID.
		 */
		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( $support_page_instance ) {
					$support_page_instance->show_support_section( $this->setting_option_key );
				}
			}
		}

		/**
		 * Support for admin notice header for "Upgrade Notice Admin" header
		 * from readme.txt.
		 *
		 * @since 3.2.0
		 */
		public function admin_notice_upgrade_notice() {
			static $notices_shown = array();

			if ( ( isset( $this->admin_notice_tables ) ) && ( ! empty( $this->admin_notice_tables ) ) ) {
				?><div class="notice notice-error notice-alt is-dismissible ld-support-database-notice">
				<?php
					echo wp_kses_post(
						wpautop(
							'IMPORTANT: The following database tables are missing AUTO_INCREMENT on the primary index. This means data cannot be written to these tables. Please try reactivating LearnDash ASAP.<br />' . implode( ', ', $this->admin_notice_tables )
						)
					);
				?>
				</div>
				<?php
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_Database_Tables::add_section_instance();
	}
);
