<?php
/**
 * LearnDash Settings Page for Certificate Shortcodes.
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Certificates_Shortcodes' ) ) ) {
	/**
	 * Class LearnDash Settings Page for Certificate Shortcodes.
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Settings_Page_Certificates_Shortcodes extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 2.4.0
		 */
		public function __construct() {
			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-certificates';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'learndash-lms-certificate_shortcodes';
			$this->settings_page_title  = esc_html__( 'Shortcodes', 'learndash' );
			$this->settings_columns     = 1;

			parent::__construct();
		}

		/**
		 * Custom function to show settings page output
		 *
		 * @since 2.4.0
		 */
		public function show_settings_page() {
			?>
			<div  id="certificate-shortcodes"  class="wrap">
				<h1><?php esc_html_e( 'Certificate Shortcodes', 'learndash' ); ?></h1>
				<div class='sfwd_options_wrapper sfwd_settings_left'>
					<div class='postbox ' id='sfwd-certificates_metabox'>
						<div class='inside'  style='padding: 0 12px 12px;'>
						<?php
							echo wpautop( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elements escaped within.
								sprintf(
									// translators: placeholder: URL to online documentation.
									esc_html_x( 'The documentation for Certificate Shortcodes has moved online (only available in English). %s', 'placeholder: URL to online documentation', 'learndash' ),
									'<a href="https://www.learndash.com/support/docs/core/certificates/certificate-shortcodes/" target="_blank" rel="noopener noreferrer" aria-label="' . esc_html__( 'External link to Certificate online documentation', 'learndash' )
								) . '">' . esc_html__( 'Click here', 'learndash' ) . sprintf(
									'<span class="screen-reader-text">%s</span><span aria-hidden="true" style="text-decoration: none !important;" class="dashicons dashicons-external"></span>',
									/* translators: Accessibility text. */
									esc_html__( '(opens in a new tab)', 'learndash' )
								) . '</a>'
							);
						?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Certificates_Shortcodes::add_page_instance();
	}
);
