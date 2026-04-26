<?php
/**
 * LearnDash Settings Section for Solid Backups.
 *
 * @since 4.14.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Template\Admin_Views\Settings\Advanced\Backups;
use LearnDash\Core\Modules\AJAX\Notices;

if (
	class_exists( 'LearnDash_Settings_Section' )
	&& ! class_exists( 'LearnDash_Settings_Section_Backups' )
) {
	/**
	 * Class LearnDash Settings Section for Solid Backups.
	 *
	 * @since 4.14.0
	 */
	class LearnDash_Settings_Section_Backups extends LearnDash_Settings_Section {
		/**
		 * Notice for first sale.
		 *
		 * @since 4.14.0
		 *
		 * @var string
		 */
		private const NOTICE_FIRST_SALE = 'notice_first_sale';

		/**
		 * Notice for X students.
		 *
		 * @since 4.14.0
		 *
		 * @var string
		 */
		private const NOTICE_X_STUDENTS = 'notice_x_students';

		/**
		 * Notice for first course.
		 *
		 * @since 4.14.0
		 *
		 * @var string
		 */
		private const NOTICE_FIRST_COURSE = 'notice_first_course';

		/**
		 * Constructor.
		 *
		 * @since 4.14.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_backups';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Backups', 'learndash' );

			parent::__construct();

			// Remove the Save Options metabox.
			add_filter(
				'learndash_admin_settings_advanced_sections_with_hidden_metaboxes',
				function ( array $section_keys ) {
					$section_keys[] = $this->settings_section_key;

					return $section_keys;
				}
			);
		}

		/**
		 * Adds custom classes to postbox wrapper.
		 *
		 * @since 4.14.0
		 *
		 * @param array<string> $classes Array of classes for postbox.
		 *
		 * @return array<string> Array of classes for postbox.
		 */
		public function add_meta_box_classes( $classes ) {
			$classes[] = 'ld-settings-section-backups';

			return parent::add_meta_box_classes( $classes );
		}

		/**
		 * Shows settings content.
		 *
		 * @since 4.14.0
		 *
		 * @return void
		 */
		public function show_meta_box(): void {
			$backups_view = new Backups();

			echo $backups_view->get_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output the HTML.
		}

		/**
		 * Displays admin notices.
		 *
		 * @since 4.14.0
		 * @deprecated 5.0.0
		 *
		 * @return void
		 */
		public function display_admin_notices(): void {
			_deprecated_function( __METHOD__, '5.0.0' );

			// Only show notices to admin users and on LD admin pages.
			if (
				! learndash_is_admin_user()
				|| ! learndash_should_load_admin_assets()
			) {
				return;
			}

			$backups_enabled = is_plugin_active( 'backupbuddy/backupbuddy.php' );

			// First sale notice.
			if (
				! $backups_enabled
				&& ! Notices\Dismisser::is_dismissed( self::NOTICE_FIRST_SALE )
				&& Notices\Dismisser::is_dismissed( self::NOTICE_FIRST_COURSE )
				&& time() > strtotime( '+24 hours', Notices\Dismisser::get_dismissed_time( self::NOTICE_FIRST_COURSE ) )
			) {
				$transactions_count = learndash_get_total_post_count( 'sfwd-transactions' );

				if ( $transactions_count > 0 ) {
					$message = sprintf(
						// translators: %s: Solid Backups link.
						__( 'Congrats on your first sale &#x1F389; We recommend %s to protect your data, website, customers and business.', 'learndash' ),
						'<a href="https://solidwp.com/learndash-backups?utm_source=learndash&utm_medium=in-product&utm_campaign=learndash-in-product-cross-sell" target="_blank">' . esc_html__( 'Solid Backups', 'learndash' ) . '</a>'
					);

					printf(
						'<div class="notice notice-success is-dismissible %1$s" data-nonce="%2$s" data-id="%3$s"><p>%4$s</p></div>',
						esc_attr( Notices\Dismisser::$classname ),
						esc_attr( learndash_create_nonce( Notices\Dismisser::$action ) ),
						esc_attr( self::NOTICE_FIRST_SALE ),
						wp_kses(
							$message,
							[
								'a' => [
									'href'   => [],
									'target' => [],
								],
							]
						)
					);
				}
			}

			// X students notice.
			if (
				! $backups_enabled
				&& ! Notices\Dismisser::is_dismissed( self::NOTICE_X_STUDENTS )
				&& Notices\Dismisser::is_dismissed( self::NOTICE_FIRST_SALE )
				&& time() > strtotime( '+24 hours', Notices\Dismisser::get_dismissed_time( self::NOTICE_FIRST_SALE ) )
			) {
				$has_open_courses = count( learndash_get_open_courses() ) > 0;

				$args = [
					'role__not_in' => 'administrator',
				];

				if ( ! $has_open_courses ) {
					$args['meta_query'] = [
						[
							'key'         => 'learndash_course_[0-9]+_enrolled_at',
							'compare_key' => 'REGEXP',
						],
					];
				}

				$students_enrolled_count = learndash_students_enrolled_count( $args );

				if ( $students_enrolled_count > 0 ) {
					$message = sprintf(
						// Translators: 1: Number of students, 2: Solid Backups link.
						_nx(
							'You now have %1$d student on your site! Don&rsquo;t forget to %2$s your website to preserve your hard work and data.',
							'You now have %1$d students on your site! Don&rsquo;t forget to %2$s your website to preserve your hard work and data.',
							$students_enrolled_count,
							'The number of students on the site. Singular or plural.',
							'learndash'
						),
						$students_enrolled_count,
						'<a href="https://solidwp.com/learndash-backups?utm_source=learndash&utm_medium=in-product&utm_campaign=learndash-in-product-cross-sell" target="_blank">' . esc_html__( 'back up', 'learndash' ) . '</a>'
					);

					printf(
						'<div class="notice notice-success is-dismissible %1$s" data-nonce="%2$s" data-id="%3$s"><p>%4$s</p></div>',
						esc_attr( Notices\Dismisser::$classname ),
						esc_attr( learndash_create_nonce( Notices\Dismisser::$action ) ),
						esc_attr( self::NOTICE_X_STUDENTS ),
						wp_kses(
							$message,
							[
								'a' => [
									'href'   => [],
									'target' => [],
								],
							]
						)
					);
				}
			}

			// First course notice.
			if (
				! $backups_enabled
				&& ! Notices\Dismisser::is_dismissed( self::NOTICE_FIRST_COURSE )
			) {
				$courses_count = learndash_get_total_post_count( 'sfwd-courses' );

				if ( $courses_count > 0 ) {
					$message = sprintf(
						// translators: 1: course label 2: Solid Backups link.
						__( 'Congrats on building your first %1$s &#x1F389; Don&rsquo;t forget to %2$s your website to preserve your hard work and data.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						'<a href="https://solidwp.com/learndash-backups?utm_source=learndash&utm_medium=in-product&utm_campaign=learndash-in-product-cross-sell" target="_blank">' . esc_html__( 'back up', 'learndash' ) . '</a>'
					);

					printf(
						'<div class="notice notice-success is-dismissible %1$s" data-nonce="%2$s" data-id="%3$s"><p>%4$s</p></div>',
						esc_attr( Notices\Dismisser::$classname ),
						esc_attr( learndash_create_nonce( Notices\Dismisser::$action ) ),
						esc_attr( self::NOTICE_FIRST_COURSE ),
						wp_kses(
							$message,
							[
								'a' => [
									'href'   => [],
									'target' => [],
								],
							]
						)
					);
				}
			}
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	[
		LearnDash_Settings_Section_Backups::class,
		'add_section_instance',
	],
	30
);
