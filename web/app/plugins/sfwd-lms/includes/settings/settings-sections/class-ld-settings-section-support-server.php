<?php
/**
 * LearnDash Settings Section for Support Server Metabox.
 *
 * @since 3.1.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_Server' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Support Server Metabox.
	 *
	 * @since 3.1.0
	 */
	class LearnDash_Settings_Section_Support_Server extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * PHP ini settings array.
		 *
		 * @var array $php_ini_settings Array of PHP settings to check.
		 */
		private $php_ini_settings = array( 'max_execution_time', 'max_input_time', 'max_input_vars', 'post_max_size', 'max_file_uploads', 'upload_max_filesize' );

		/**
		 * PHP extensions array.
		 *
		 * @var array $php_extensions Array of PHP extensions to check.
		 */
		private $php_extensions = array( 'mbstring' );

		/**
		 * Protected constructor for class
		 *
		 * @since 3.1.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'server_settings';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_server_settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Server Settings', 'learndash' );

			$this->load_options = false;

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

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
			 * Server Settings.
			 */
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->settings_set = array();

				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Setting', 'learndash' ),
						'text'  => 'Setting',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Value', 'learndash' ),
						'text'  => 'Value',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$php_version                                  = phpversion();
				$this->settings_set['settings']['phpversion'] = array(
					'label'      => 'PHP Version',
					'label_html' => esc_html__( 'PHP Version', 'learndash' ),
					'value'      => $php_version,
				);

				$version_compare = version_compare( LEARNDASH_MIN_PHP_VERSION, $php_version, '>' );
				$color           = 'green';
				if ( -1 == $version_compare ) {
					$color = 'red';
				}
				$this->settings_set['settings']['phpversion']['value_html'] = '<span style="color: ' . $color . '">' . $php_version . '</span>';
				if ( -1 == $version_compare ) {
					$this->settings_set['settings']['phpversion']['value_html'] .= ' - <a href="https://www.learndash.com/support/docs/getting-started/requirements/" target="_blank">' . esc_html__( 'LearnDash Minimum Requirements', 'learndash' ) . '</a>';
					$this->settings_set['settings']['phpversion']['value']      .= ' - (X)';
				}

				if ( defined( 'PHP_OS' ) ) {
					$this->settings_set['settings']['PHP_OS'] = array(
						'label'      => 'PHP OS',
						'label_html' => esc_html__( 'PHP OS', 'learndash' ),
						'value'      => PHP_OS,
					);
				}

				if ( defined( 'PHP_OS_FAMILY' ) ) {
					$this->settings_set['settings']['PHP_OS_FAMILY'] = array(
						'label'      => 'PHP OS Family',
						'label_html' => esc_html__( 'PHP OS Family', 'learndash' ),
						'value'      => PHP_OS_FAMILY, // phpcs:ignore PHPCompatibility.Constants.NewConstants.php_os_familyFound -- Only executed if available
					);
				}

				$db_server_info = LDLMS_DB::get_db_server_info();

				if ( true == $db_server_info['mysqldb_active'] ) {
					$db_version = $db_server_info['db_version_found'];

					$this->settings_set['settings']['db_version'] = array(
						'label'      => 'MySQL version',
						'label_html' => esc_html__( 'MySQL version', 'learndash' ),
						'value'      => $db_version,
					);

					$version_compare = version_compare( LEARNDASH_MIN_MYSQL_VERSION, $db_version, '>' );
					$color           = 'green';
					if ( -1 == $version_compare ) {
						$color = 'red';
					}

					$this->settings_set['settings']['db_version']['value_html'] = '<span style="color: ' . $color . '">' . $db_version . '</span>';
					if ( -1 == $version_compare ) {
						$this->settings_set['settings']['db_version']['value_html'] .= ' - <a href="https://www.learndash.com/support/docs/getting-started/requirements/" target="_blank">' . esc_html__( 'LearnDash Minimum Requirements', 'learndash' ) . '</a>';
						$this->settings_set['settings']['db_version']['value']      .= ' - (X)';
					}
				} elseif ( true == $db_server_info['mariadb_active'] ) {
					$db_version = $db_server_info['db_version_found'];

					$this->settings_set['settings']['db_version'] = array(
						'label'      => 'MariaDB version',
						'label_html' => esc_html__( 'MariaDB version', 'learndash' ),
						'value'      => $db_version,
					);

					$version_compare = version_compare( LEARNDASH_MIN_MARIA_VERSION, $db_version, '>' );
					$color           = 'green';
					if ( -1 == $version_compare ) {
						$color = 'red';
					}

					$this->settings_set['settings']['db_version']['value_html'] = '<span style="color: ' . $color . '">' . $db_version . '</span>';
					if ( -1 == $version_compare ) {
						$this->settings_set['settings']['db_version']['value_html'] .= ' - <a href="https://www.learndash.com/support/docs/getting-started/requirements/" target="_blank">' . esc_html__( 'LearnDash Minimum Requirements', 'learndash' ) . '</a>';
						$this->settings_set['settings']['db_version']['value']      .= ' - (X)';
					}
				}

				/**
				 * Filters admin support section PHP ini settings.
				 *
				 * @param array $php_ini_settings An array of php ini settings.
				 */
				$this->php_ini_settings = apply_filters( 'learndash_support_php_ini_settings', $this->php_ini_settings );
				if ( ! empty( $this->php_ini_settings ) ) {
					sort( $this->php_ini_settings );
					$this->php_ini_settings = array_unique( $this->php_ini_settings );

					foreach ( $this->php_ini_settings as $ini_key ) {
						$this->settings_set['settings'][ $ini_key ] = array(
							'label' => $ini_key,
							'value' => ini_get( $ini_key ),
						);
					}

					$this->settings_set['settings']['curl'] = array(
						'label' => 'curl',
					);

					if ( ! extension_loaded( 'curl' ) ) {
						$this->settings_set['settings']['curl']['value']      = 'No';
						$this->settings_set['settings']['curl']['value_html'] = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';

					} else {
						$this->settings_set['settings']['curl']['value']      = 'Yes<br />';
						$this->settings_set['settings']['curl']['value_html'] = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span><br />';

						$version = curl_version();

						$this->settings_set['settings']['curl']['value']      .= 'Version: ' . $version['version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Version', 'learndash' ) . ': ' . $version['version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'SSL Version: ' . $version['ssl_version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'SSL Version', 'learndash' ) . ': ' . $version['ssl_version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'Libz Version: ' . $version['libz_version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Libz Version', 'learndash' ) . ': ' . $version['libz_version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'Protocols: ' . join( ', ', $version['protocols'] ) . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Protocols', 'learndash' ) . ': ' . join( ', ', $version['protocols'] ) . '<br />';

						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $_GET['ld_debug'] ) ) {
							$paypal_email         = get_option( 'learndash_settings_paypal' );
							$ca_certificates_path = ini_get( 'curl.cainfo' );

							if ( ! $ca_certificates_path ) {
								if ( isset( $paypal_email['paypal_email'] ) && ! empty( $paypal_email['paypal_email'] ) ) {
									$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates not set. Please add it to curl.cainfo in the php.ini file. Otherwise, PayPal may not work. (X)<br />';
									$this->settings_set['settings']['curl']['value_html'] .= '<span style="color: red">' . esc_html__( 'Path to the CA certificates not set. Please add it to curl.cainfo in the php.ini file. Otherwise, PayPal may not work.', 'learndash' ) . '</span><br />';
								}

								if ( isset( $paypal_email['paypal_email'] ) && empty( $paypal_email['paypal_email'] ) ) {
									$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates not set. (X)<br />';
									$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Path to the CA certificates not set.', 'learndash' ) . '</span><br />';
								}
							} else {
								$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates: ' . $ca_certificates_path . '<br />';
								$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Path to the CA certificates', 'learndash' ) . ': ' . $ca_certificates_path . '</span><br />';
							}
						}
					}
				}

				/**
				 * Filters admin support section PHP extensions.
				 *
				 * @param array $php_extensions An array of PHP extensions.
				 */
				$this->php_extensions = apply_filters( 'learndash_support_php_extensions', $this->php_extensions );
				if ( ! empty( $this->php_extensions ) ) {
					sort( $this->php_extensions );
					$this->php_extensions = array_unique( $this->php_extensions );

					foreach ( $this->php_extensions as $ini_key ) {
						$this->settings_set['settings'][ $ini_key ] = array(
							'label'      => $ini_key,
							'value'      => extension_loaded( $ini_key ) ? 'Yes' : 'No (X)',
							'value_html' => extension_loaded( $ini_key ) ? esc_html__( 'Yes', 'learndash' ) : '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>',
						);
					}
				}

				/** This filter is documented in includes/settings/settings-sections/class-ld-settings-section-support-database-tables.php */
				$support_sections[ $this->setting_option_key ] = apply_filters( 'learndash_support_section', $this->settings_set, $this->setting_option_key );
			}

			return $support_sections;
		}

		/**
		 * Show Support Section
		 *
		 * @since 3.1.0
		 *
		 * @param string $settings_section_key Section Key.
		 * @param string $settings_screen_id   Screen ID.
		 */
		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( is_a( $support_page_instance, 'LearnDash_Settings_Page_Support' ) ) {
					$support_page_instance->show_support_section( $this->setting_option_key );
				}
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_Server::add_section_instance();
	}
);
