<?php
/**
 * LearnDash Settings Metabox for Course Dashboard.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Settings\Metaboxes
 */

use LearnDash\Core\Template\Admin_Views\Dashboards;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Settings_Metabox' ) && ! class_exists( 'LearnDash_Settings_Metabox_Course_Dashboard' ) ) {
	/**
	 * Class LearnDash Settings Metabox for Course Groups Settings.
	 *
	 * @since 4.9.0
	 */
	class LearnDash_Settings_Metabox_Course_Dashboard extends LearnDash_Settings_Metabox {
		/**
		 * Public constructor for class
		 *
		 * @since 4.9.0
		 */
		public function __construct() {
			$this->settings_screen_id     = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE );
			$this->settings_metabox_key   = sprintf( 'learndash-%s-dashboard', LDLMS_Post_Types::COURSE );
			$this->settings_section_label = __( 'Dashboard', 'learndash' );

			parent::__construct();
		}

		/**
		 * Shows the dashboard.
		 *
		 * @since 4.9.0
		 *
		 * @param LearnDash_Settings_Metabox|null $metabox Metabox.
		 *
		 * @return void
		 */
		protected function show_settings_metabox_fields( $metabox = null ) {
			$course = get_post( (int) get_the_ID() );

			if ( ! $course instanceof WP_Post ) {
				return;
			}

			$dashboard = new Dashboards\Course( $course );

			echo $dashboard->get_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output the HTML.
		}
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if (
				! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Dashboard'] )
				&& class_exists( 'LearnDash_Settings_Metabox_Course_Dashboard' )
			) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Dashboard'] = LearnDash_Settings_Metabox_Course_Dashboard::add_metabox_instance();
			}

			return $metaboxes;
		},
		50
	);
}
