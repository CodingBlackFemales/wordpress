<?php
/**
 * LearnDash Settings Section for Payments List Metabox.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Payments_List' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Payments List Metabox.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Section_Payments_List extends LearnDash_Settings_Section {

		/**
		 * Section URL Param
		 *
		 * @var string $section_url_param
		 */
		public static $section_url_param = 'section-payment';

		/**
		 * Current Section Shown
		 *
		 * @var string $current_section
		 */
		private $current_sub_section = '';

		/**
		 * Related Sub-Sections
		 *
		 * @var array $sub_sections
		 */
		private $sub_sections = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 3.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_payments';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_payments_list';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_payments_list';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_payments_list';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Payments', 'learndash' );

			add_action( 'learndash_settings_page_init', array( $this, 'learndash_settings_page_init' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Show the Payments List section.
		 *
		 * @since 3.6.0
		 *
		 * @param string $settings_page_id Settings page ID.
		 */
		public function learndash_settings_page_init( $settings_page_id ) {
			if ( $settings_page_id === $this->settings_page_id ) {

				$this->sub_sections = LearnDash_Settings_Section::get_all_sections_by( 'settings_parent_section_key', $this->settings_section_key );
				if ( ! empty( $this->sub_sections ) ) {
					add_filter( 'learndash_show_section', array( $this, 'should_show_settings_section' ), 10, 3 );
				}
			}
		}

		/**
		 * Function to check if section should be shown.
		 *
		 * Called from filter `learndash_show_section` to check if the section should be shown
		 * on page. This is called just before the `add_meta_box()` function.
		 *
		 * @since 3.6.0
		 *
		 * @param bool   $show_section       Default is true.
		 * @param string $section_key        The settings section key to be shown.
		 * @param string $settings_screen_id The settings Screen ID.
		 */
		public function should_show_settings_section( $show_section, $section_key, $settings_screen_id ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( ! empty( $section_key ) ) ) {
				if ( true === $show_section ) {
					$current_sub_section = $this->get_current_sub_section();
					if ( ! empty( $current_sub_section ) ) {
						if ( $section_key !== $current_sub_section ) {
							$show_section = false;
						}
					} elseif ( in_array( $section_key, $this->get_sub_sections_keys(), true ) ) {
						$show_section = false;
					}
				}
			}

			return $show_section;
		}

		/**
		 * Set the current viewed section.
		 *
		 * This is used to control the screen output.
		 *
		 * @since 3.6.0
		 *
		 * @return string Current section slug or empty.
		 */
		private function get_current_sub_section() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( isset( $_GET[ self::$section_url_param ] ) ) && ( ! empty( $_GET[ self::$section_url_param ] ) ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->current_sub_section = sanitize_text_field( wp_unslash( $_GET[ self::$section_url_param ] ) );
				if ( ! $this->is_valid_sub_section( $this->current_sub_section ) ) {
					$this->current_sub_section = '';
				}
			}

			return $this->current_sub_section;
		}

		/**
		 * Customer Show the meta box settings
		 *
		 * @since 3.6.0
		 *
		 * @param string $section Section to be shown.
		 */
		public function show_settings_section( $section = null ) {
			if ( ! empty( $this->current_sub_section ) ) {
				global $wp_settings_sections;
				if ( isset( $wp_settings_sections[ $this->settings_page_id ][ $section ] ) ) {
					parent::show_settings_section( $wp_settings_sections[ $this->settings_page_id ][ $section ] );
				}
				return;
			}

			$this->show_settings_section_nonce_field();
			?>
			<div class="sfwd sfwd_options">
				<table class="learndash-settings-table learndash-settings-table-payments widefat striped" cellspacing="0">
				<thead>
				<tr>
					<th class="col-name-enabled"></th>
					<th class="col-name-label"><?php esc_html_e( 'Payment type', 'learndash' ); ?></th>
					<th class="col-name-manage"></th>
				<tr>
				</thead>
				<tbody>
				<?php
				if ( ! empty( $this->sub_sections ) ) {
					foreach ( (array) $this->get_sub_sections_by_label_order() as $sub_section ) {
						$sub_section_fields = $sub_section->setting_option_fields;
						?>
						<tr>
							<td class="col-name-enabled col-valign-middle">
								<?php
									$sub_section->show_settings_section_nonce_field( false );
								?>
								<div class="sfwd_option_div">
								<?php
								if ( isset( $sub_section->setting_option_fields['enabled'] ) ) {
									call_user_func( $sub_section->setting_option_fields['enabled']['display_callback'], $sub_section->setting_option_fields['enabled'] );
								}
								?>
								</div>
							</td>
							<td class="col-name-label">
								<?php
								$listing_label = '';
								if ( ( isset( $sub_section->settings_section_listing_label ) ) && ( ! empty( $sub_section->settings_section_listing_label ) ) ) {
									$listing_label = $sub_section->settings_section_listing_label;
								} elseif ( ( isset( $sub_section->settings_section_label ) ) && ( ! empty( $sub_section->settings_section_label ) ) ) {
									$listing_label = $sub_section->settings_section_label;
								}

								if ( ! empty( $listing_label ) ) {
									echo '<div class="learndash-listing_label"><strong><a href="' . esc_url( add_query_arg( self::$section_url_param, esc_attr( $sub_section->settings_section_key ) ) ) . '">' . esc_html( $listing_label ) . '</a></strong></div>';
								}
								?>
							</td>
							<td class="col-name-manage col-valign-middle">
								<a class="button alignright" href="<?php echo esc_url( add_query_arg( self::$section_url_param, esc_attr( $sub_section->settings_section_key ) ) ); ?>"><?php esc_html_e( 'Manage', 'learndash' ); ?></a>
							</td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr><td colspan="4"><?php esc_html_e( 'No Payment items found.', 'learndash' ); ?></td></tr>
					<?php
				}
				?>
				</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Utility function to return the sub sections in label (alpha) order.
		 *
		 * @since 3.6.0
		 */
		private function get_sub_sections_by_label_order() {
			$sub_sections = array();
			if ( ! empty( $this->sub_sections ) ) {
				foreach ( (array) $this->sub_sections as $sub_section ) {
					$listing_label = '';
					if ( ( isset( $sub_section->settings_section_listing_label ) ) && ( ! empty( $sub_section->settings_section_listing_label ) ) ) {
						$listing_label = $sub_section->settings_section_listing_label;
					} elseif ( ( isset( $sub_section->settings_section_label ) ) && ( ! empty( $sub_section->settings_section_label ) ) ) {
						$listing_label = $sub_section->settings_section_label;
					}

					if ( ! empty( $listing_label ) ) {
						$sub_sections[ $listing_label ] = $sub_section;
					}
				}
			}

			if ( ! empty( $sub_sections ) ) {
				ksort( $sub_sections );
			}

			return $sub_sections;
		}

		/**
		 * Utility function to return array of sub section keys;
		 *
		 * @since 3.6.0
		 *
		 * @return array
		 */
		private function get_sub_sections_keys() {
			$sub_sections_keys = array();
			if ( ! empty( $this->sub_sections ) ) {
				foreach ( $this->sub_sections as $sub_section ) {
					$sub_sections_keys[] = $sub_section->settings_section_key;
				}
			}

			return $sub_sections_keys;

		}

		/**
		 * Utility function to check if sub section is valid.
		 *
		 * @since 3.6.0
		 *
		 * @param string $sub_section_key Sub-Section key to check.
		 *
		 * @return bool True is valid sub-section.
		 */
		private function is_valid_sub_section( $sub_section_key = '' ) {
			$is_sub_section = false;
			if ( ! empty( $sub_section_key ) ) {
				if ( in_array( $sub_section_key, $this->get_sub_sections_keys(), true ) ) {
					$is_sub_section = true;
				}
			}

			return $is_sub_section;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Payments_List::add_section_instance();
	}
);
