<?php
/**
 * LearnDash Settings Page Support.
 *
 * @since 3.1.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Support' ) ) ) {
	/**
	 * Class LearnDash Settings Page Support.
	 *
	 * @since 3.1.0
	 */
	class LearnDash_Settings_Page_Support extends LearnDash_Settings_Page {

		/**
		 * Systems Info array.
		 *
		 * @var array $system_info Array of System Info items to check.
		 */
		private $system_info = array();

		/**
		 * Public constructor for class
		 *
		 * @since 3.1.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_support';
			$this->settings_page_title   = esc_html__( 'Support', 'learndash' );
			$this->settings_tab_title    = $this->settings_page_title;
			$this->settings_tab_priority = 60;
			$this->settings_form_wrap    = false;
			$this->show_submit_meta      = false;
			$this->show_quick_links_meta = true;

			add_action( 'learndash_settings_page_load', array( $this, 'learndash_settings_page_load' ) );

			parent::__construct();
		}

		/**
		 * Setting Page Load
		 *
		 * @since 3.1.0
		 *
		 * @param string $settings_screen_id Screen ID.
		 */
		public function learndash_settings_page_load( $settings_screen_id = '' ) {
			global $sfwd_lms;

			if ( $settings_screen_id === $this->settings_screen_id ) {

				$this->gather_system_details();

				// download-system-info.
				if ( ( isset( $_GET['ld_download_system_info_nonce'] ) ) && ( ! empty( $_GET['ld_download_system_info_nonce'] ) ) && ( wp_verify_nonce( $_GET['ld_download_system_info_nonce'], 'ld_download_system_info_' . get_current_user_id() ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					header( 'Content-type: text/plain' );
					header( 'Content-Disposition: attachment; filename=ld_system_info-' . gmdate( 'Ymd' ) . '.txt' );
					$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
					if ( $support_page_instance ) {
						foreach ( $support_page_instance->get_support_sections() as $_key => $_section ) {
							$support_page_instance->show_support_section( $_key, 'text' );
						}
					}
					die();
				}

				// Load JS/CSS as needed for page.
				wp_enqueue_style(
					'learndash-admin-support-page',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-support-page' . learndash_min_asset() . '.css',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN
				);
				wp_style_add_data( 'learndash-admin-support-page', 'rtl', 'replace' );
				$learndash_assets_loaded['styles']['learndash-admin-support-page'] = __FUNCTION__;
			}
		}

		/**
		 * Used to collect all needed display elements. Many filters by section as well as a final filter
		 *
		 * @since 3.1.0
		 */
		public function gather_system_details() {

			/**
			 * Filters list of initial sections for admin settings support tab.
			 *
			 * @param array $system_info An array of support sections.
			 */
			$this->system_info = apply_filters( 'learndash_support_sections_init', $this->system_info );

			// Finally a filter for all sections. This is where some external process will add new sections etc.
			/**
			 * Filters list of sections for admin settings support tab.
			 *
			 * @param array $system_info An array of support sections.
			 */
			$this->system_info = apply_filters( 'learndash_support_sections', $this->system_info );

		}

		/**
		 * Get Support Sections
		 *
		 * @since 3.1.0
		 */
		public function get_support_sections() {
			return $this->system_info;
		}

		/**
		 * Show System Info section
		 *
		 * @since 3.1.0
		 *
		 * @param string $section_key Section Key.
		 * @param string $output_type Controls formatting. 'html' or 'text'.
		 */
		public function show_support_section( $section_key = '', $output_type = 'html' ) {
			if ( isset( $this->system_info[ $section_key ] ) ) {
				$_set = $this->system_info[ $section_key ];
				$_key = $section_key;

				switch ( $output_type ) {
					case 'text':
						if ( ( isset( $_set['header']['text'] ) ) && ( ! empty( $_set['header']['text'] ) ) ) {
							echo esc_html( strtoupper( $_set['header']['text'] ) ) . "\r\n";
						}

						if ( ( isset( $_set['columns'] ) ) && ( ! empty( $_set['columns'] ) ) && ( isset( $_set['settings'] ) ) && ( ! empty( $_set['settings'] ) ) ) {
							foreach ( $_set['settings'] as $setting_key => $setting_set ) {
								if ( 'settings-sub-section-' === substr( $setting_key, 0, strlen( 'settings-sub-section-' ) ) ) {
									if ( isset( $setting_set['text'] ) ) {
										echo "\r\n";
										echo esc_html( $setting_set['text'] );
										echo "\r\n";
									}
								} else {

									foreach ( $_set['columns'] as $column_key => $column_set ) {
										$value = wp_strip_all_tags( str_replace( array( '<br />', '<br>', '<br >' ), "\r\n", $setting_set[ $column_key ] ) );

										// Add some format spacing to make the raw txt version easier to read.
										$spaces_needed = 50 - strlen( $value );
										if ( $spaces_needed > 0 ) {
											$value .= str_repeat( ' ', $spaces_needed );
										}
										echo esc_html( $value );
									}
									echo "\r\n";
								}
							}
						}
						echo "\r\n";
						break;

					case 'html':
					default:
						if ( ( isset( $_set['desc'] ) ) & ( ! empty( $_set['desc'] ) ) ) {
							?>
							<div class="learndash-support-settings-desc"><?php echo wp_kses_post( wptexturize( $_set['desc'] ) ); ?></div>
							<?php
						}

						if ( ( isset( $_set['columns'] ) ) && ( ! empty( $_set['columns'] ) ) && ( isset( $_set['settings'] ) ) && ( ! empty( $_set['settings'] ) ) ) {
							?>
							<table cellspacing="0" class="learndash-support-settings">
								<thead>
									<tr>
									<?php
									foreach ( $_set['columns'] as $column_key => $column_set ) {
										$column_class = '';
										if ( isset( $column_set['class'] ) ) {
											$column_class = $column_set['class'];
										}
										/**
										 * Filters admin settings support column CSS class.
										 *
										 * @param string $column_class     Column CSS class.
										 * @param string $column_key       Column Key.
										 * @param string $system_info_item Name fo system info item.
										 */
										$column_class = apply_filters( 'learndash_support_column_class', $column_class, $column_key, $_key );
										?>
											<th scope="col" class="<?php echo esc_attr( $column_class ); ?>">
											<?php
											if ( isset( $column_set['html'] ) ) {
												echo wp_kses_post( $column_set['html'] );
											} elseif ( isset( $column_set['text'] ) ) {
												echo esc_html( $column_set['text'] );
											}
											?>
											</th>
											<?php
									}
									?>
									</tr>
								</thead>
								<body>
									<?php
									foreach ( $_set['settings'] as $setting_key => $setting_set ) {
										if ( 'settings-sub-section-' === substr( $setting_key, 0, strlen( 'settings-sub-section-' ) ) ) {
											?>
											<tr class="settings-sub-section">
											<th scope="row" class="settings-sub-section" colspan="<?php echo count( $_set['columns'] ); ?>">
											<?php
											if ( isset( $setting_set['html'] ) ) {
												echo wp_kses_post( $setting_set['html'] );
											}
											?>
											</th>
											</tr>
											<?php
										} else {
											?>
											<tr>
											<?php
											foreach ( $_set['columns'] as $column_key => $column_set ) {
												?>
												<td scope="col" class="
												<?php
													/** This filter is documented in includes/settings/settings-pages/class-ld-settings-page-support.php */
													apply_filters( 'learndash_support_column_class', '', $column_key, $_key );
												?>
												">
												<?php
												if ( isset( $setting_set[ $column_key . '_html' ] ) ) {
														echo wp_kses_post( $setting_set[ $column_key . '_html' ] );
												} elseif ( isset( $setting_set[ $column_key ] ) ) {
													echo wp_kses_post( $setting_set[ $column_key ] );
												}
												?>
												</td>
												<?php
											}
											?>
											</tr>
											<?php
										}
									}
									?>
								</body>
							</table>
							<?php
						}
				}
			}
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Support::add_page_instance();
	}
);
