<?php
/**
 * LearnDash Settings Section Emails Placeholders Metabox.
 *
 * @since 2.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Emails_Placeholders' ) ) ) {
	/**
	 * Class LearnDash Settings Section Emails Placeholders Metabox.
	 *
	 * @since 2.6.0
	 */
	class LearnDash_Settings_Section_Emails_Placeholders extends LearnDash_Settings_Section {

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 *
		 * @param array $args Array of class args.
		 */
		public function __construct( $args = array() ) {

			$this->settings_page_id = 'learndash_lms_emails';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_emails_placeholders';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_emails_placeholders';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Email Placeholders', 'learndash' );

			$this->metabox_context  = 'side';
			$this->metabox_priority = 'default';

			parent::__construct();
		}

		/**
		 * Show custom metabox output for Quick Links.
		 *
		 * @since 2.6.0
		 */
		public function show_meta_box() {
			global $wp_meta_boxes;
			?>
			<div id="ld-emails-placeholders" class="submitbox">
				<p>
				<?php
				// translators: Describes email placeholders available for use.
				echo esc_html__( 'Placeholders available for use in the different email notifications', 'learndash' );
				?>
				</p>
				<p><strong>New User Registration</strong></p>
				<ul>
					<li>{username}</li>
					<li>{first_name}</li>
					<li>{last_name}</li>
					<li>{email}</li>
				</ul>
				<p><strong>
				<?php
				// translators: placeholder: Course/Group Purchase Success.
				echo sprintf( esc_html_x( ' %1$s/%2$s Purchase Success', 'placeholder: Course/Group Purchase Success.', 'learndash' ), esc_html( learndash_get_custom_label( 'course' ) ), esc_html( learndash_get_custom_label( 'group' ) ) );
				?>
				</strong></p>
				<ul>
					<li>{username}</li>
					<li>{first_name}</li>
					<li>{last_name}</li>
					<li>{transaction_id}</li>
				</ul>
				<p><strong>
				<?php
				// translators: placeholder: Course/Group Purchase Failed.
				echo sprintf( esc_html_x( ' %1$s/%2$s Purchase Failed', 'placeholder: Course/Group Purchase Failed.', 'learndash' ), esc_html( learndash_get_custom_label( 'course' ) ), esc_html( learndash_get_custom_label( 'group' ) ) );
				?>
				</strong></p>
				<ul>
					<li>{username}</li>
					<li>{first_name}</li>
					<li>{last_name}</li>
					<li>{transaction_id}</li>
				</ul>
			</div>
			<?php
		}

		/**
		 * This is a requires function.
		 */
		public function load_settings_fields() {
			// Nothing to do here.
		}
	}
}
