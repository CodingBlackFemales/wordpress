<?php
/**
 * Tutor LMS helper
 *
 * @since   2.4.90
 *
 * @package BuddyBoss_Theme
 */

namespace BuddyBossTheme;

if ( ! class_exists( '\BuddyBossTheme\TutorLMSHelper' ) ) {

	class TutorLMSHelper {

		/**
		 * Constructor
		 *
		 * @since 2.4.90
		 */
		public function __construct() {
			add_filter( 'template_redirect', array( $this, 'bb_tutorlms_template_redirect' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_tutorlms_helper_script' ) );
			add_filter( 'tutor_dashboard/nav_items/settings/nav_items', array( $this, 'bb_tutorlms_filter_profile_tabs' ) );
			add_filter( 'tutor_dashboard/nav_ui_items', array( $this, 'bb_tutorlms_filter_dashboard_nav_items' ) );
			add_filter( 'body_class', array( $this, 'bb_tutorlms_custom_class' ) );
		}

		/**
		 * Remove some TutorLMS links.
		 *
		 * @since 2.4.90
		 */
		public function bb_tutorlms_template_redirect() {
			global $wp;

			if ( function_exists( 'bp_core_get_user_domain' ) ) {
				$profile_url = bp_core_get_user_domain( bp_loggedin_user_id() );

				if ( ! empty( $wp->query_vars['tutor_dashboard_page'] ) ) {
					if ( 'my-profile' === $wp->query_vars['tutor_dashboard_page'] ) {
						wp_redirect( $profile_url, 301 );
						exit;
					} elseif (
						'settings' === $wp->query_vars['tutor_dashboard_page'] &&
						! empty( $wp->query_vars['tutor_dashboard_sub_page'] ) &&
						in_array(
							$wp->query_vars['tutor_dashboard_sub_page'],
							array( 'reset-password', 'social-profile' )
						)
					) {
						status_header( 404 );
						load_template( get_404_template() );
						exit;
					}
					// Redirect to the courses tab in profile page.
				} elseif ( ! empty( $wp->query_vars['tutor_profile_username'] ) ) {
					$username = sanitize_text_field( $wp->query_vars['tutor_profile_username'] );
					$user     = tutor_utils()->get_user_by_login( $username );

					$is_instructor         = tutor_utils()->is_instructor( $user->ID, true );
					$student_course_url    = bp_core_get_user_domain( $user->ID ) . bb_tutorlms_profile_courses_slug();
					$instructor_course_url = $student_course_url . '/' . bb_tutorlms_profile_instructor_courses_slug();

					if ( empty( $_GET['view'] ) && $is_instructor ) {
						$profile_course_url = $instructor_course_url;
					} elseif ( ! empty( $_GET['view'] ) && 'instructor' === $_GET['view'] && $is_instructor ) {
						$profile_course_url = $instructor_course_url;
					} else {
						$profile_course_url = $student_course_url;
					}

					wp_redirect( $profile_course_url, 301 );
					exit;
				}
			}
		}

		/**
		 * Update the notification label.
		 *
		 * @since 2.4.90
		 */
		public function bb_tutorlms_helper_script() {
			$tutor_dashboard_page_id = (int) tutor_utils()->get_option( 'tutor_dashboard_page_id' );
			if ( get_the_ID() === $tutor_dashboard_page_id ) {
				$course_update_text = esc_html__( 'Course Updates', 'buddyboss-theme' );
				wp_register_script( 'bb-tutorlms-custom-js', '', array( "jquery" ), '', true );
				wp_enqueue_script( 'bb-tutorlms-custom-js' );
				wp_add_inline_script(
					'bb-tutorlms-custom-js',
					"var courseUpdateText = '" . $course_update_text . "';
					jQuery(document).ready(function($) {
						jQuery('#tutor-notifications-wrapper .tutor-offcanvas-header > div:first-child').html( courseUpdateText );
					});"
				);
			}
		}

		/**
		 * TutorLMS avatars related body class.
		 *
		 * @since 2.4.90
		 *
		 * @param array $classes array of body classes.
		 *
		 * @return array
		 */
		public function bb_tutorlms_custom_class( $classes ) {

			$classes[] = 'bb-tutorlms-avatars';

			return $classes;
		}

		/**
		 * Filter the tutor lms dashboard profile navigation.
		 *
		 * @since 2.4.90
		 *
		 * @param array $setting_menus array of profile navigation tabs.
		 *
		 * @return array
		 */
		public function bb_tutorlms_filter_profile_tabs( $setting_menus ) {
			if ( is_array( $setting_menus ) ) {
				if ( array_key_exists( 'reset_password', $setting_menus ) ) {
					unset( $setting_menus['reset_password'] );
				}
				if ( array_key_exists( 'social-profile', $setting_menus ) ) {
					unset( $setting_menus['social-profile'] );
				}
			}

			return $setting_menus;
		}

		/**
		 * Filter the tutor lms dashboard navigation.
		 *
		 * @since 2.4.90
		 *
		 * @param array $dashboard_menus array of profile navigations.
		 *
		 * @return array
		 */
		public function bb_tutorlms_filter_dashboard_nav_items( $dashboard_menus ) {

			if ( is_array( $dashboard_menus ) && bb_theme_enable_tutorlms_override() ) {
				if ( array_key_exists( 'logout', $dashboard_menus ) ) {
					unset( $dashboard_menus['logout'] );
				}
				if ( array_key_exists( 'my-profile', $dashboard_menus ) ) {
					unset( $dashboard_menus['my-profile'] );
				}
			}

			return $dashboard_menus;
		}

	}
}
