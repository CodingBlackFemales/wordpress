<?php
/**
 * LearnDash Settings Page Courses Shortcodes.
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Courses_Shortcodes' ) ) ) {
	/**
	 * Class LearnDash Settings Page Courses Shortcodes.
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Settings_Page_Courses_Shortcodes extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 2.4.0
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-courses';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'courses-shortcodes';

			// translators: Course Shortcodes Label.
			$this->settings_page_title   = esc_html_x( 'Shortcodes', 'Course Shortcodes Label', 'learndash' );
			$this->settings_columns      = 1;
			$this->show_quick_links_meta = false;

			parent::__construct();
		}

		/**
		 * Show settings page output.
		 *
		 * @since 2.4.0
		 */
		public function show_settings_page() {
			?>
			<div  id='course-shortcodes'  class='wrap'>
				<h1>
				<?php
				printf(
					// translators: placeholder: Course Label.
					esc_html_x( '%s Shortcodes', 'placeholder: Course Label', 'learndash' ),
					esc_attr( LearnDash_Custom_Label::get_label( 'course' ) )
				);
				?>
				</h1>
				<div class='sfwd_options_wrapper sfwd_settings_left'>
					<div class='postbox ' id='sfwd-course_metabox'>
						<div class="inside"  style="padding: 0 12px 12px;">
						<?php
							echo wpautop( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elements escaped within.
								sprintf(
									// translators: placeholder: Course label, URL to online documentation.
									esc_html_x( 'The documentation for %1$s Shortcodes and Blocks has moved online (only available in English). %2$s', 'placeholder: Course label, URL to online documentation', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'course' ),
									'<a href="https://www.learndash.com/support/docs/core/shortcodes-blocks/" target="_blank" rel="noopener noreferrer"  aria-label="' . esc_attr(
										sprintf(
											// translators: placeholder: Course label.
											esc_html_x( 'External link to %s Shortcodes and Blocks online documentation', 'placeholder: Course label.', 'learndash' ),
											LearnDash_Custom_Label::get_label( 'course' )
										)
									) . '">' . esc_html__( 'Click here', 'learndash' ) . sprintf(
										'<span class="screen-reader-text">%s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>',
										/* translators: Accessibility text. */
										esc_html__( '(opens in a new tab)', 'learndash' )
									) . '</a>'
								)
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
		LearnDash_Settings_Page_Courses_Shortcodes::add_page_instance();
	}
);
