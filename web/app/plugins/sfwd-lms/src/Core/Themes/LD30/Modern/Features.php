<?php
/**
 * LearnDash LD30 Modern Features.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern;

use LDLMS_Post_Types;
use LearnDash\Core\Version_Tracker;
use LearnDash_Settings_Section_General_Appearance;
use LearnDash_Settings_Section_Registration_Pages;
use LearnDash_Theme_Register_LD30;
use LearnDash_Theme_Register;
use LearnDash\Core\Template\Template;
use WP_Post;

/**
 * LD30 Modern Features.
 *
 * @since 4.21.0
 */
class Features {
	/**
	 * Whether a given view is enabled based on the enabled Features.
	 *
	 * @since 4.21.0
	 *
	 * @param string $view_slug View slug.
	 *
	 * @return bool
	 */
	public static function enabled_for_view( string $view_slug ): bool {
		if (
			$view_slug === LDLMS_Post_Types::COURSE
			|| $view_slug === LDLMS_Post_Types::LESSON
			|| $view_slug === LDLMS_Post_Types::TOPIC
		) {
			return LearnDash_Settings_Section_General_Appearance::get_setting( 'course_enabled' ) === 'yes';
		}

		if ( $view_slug === LDLMS_Post_Types::GROUP ) {
			return LearnDash_Settings_Section_General_Appearance::get_setting( 'group_enabled' ) === 'yes';
		}

		return false;
	}

	/**
	 * Enables View support for the LD30 theme for template files supported by the Feature.
	 *
	 * @since 4.21.0
	 *
	 * @param bool                     $supports_views Whether the Theme supports views.
	 * @param string                   $view_slug      View slug to use as context when checking if the Theme supports views.
	 * @param LearnDash_Theme_Register $theme_instance Instance of the Theme.
	 *
	 * @return bool
	 */
	public function enable_view_support( $supports_views, $view_slug, $theme_instance ) {
		if (
			! $theme_instance instanceof LearnDash_Theme_Register_LD30
			|| ! self::enabled_for_view( $view_slug )
		) {
			return $supports_views;
		}

		return true;
	}

	/**
	 * Ensures that the the_content filter can be safely ran for the Modern LD30 theme without causing recursion.
	 *
	 * @since 4.21.0
	 *
	 * @param bool $remove Whether to temporarily remove the the_content filter at SFWD_CPT_Instance::template_content(). Default false.
	 *
	 * @return bool
	 */
	public function remove_template_content_filter( $remove = false ) {
		if (
			self::enabled_for_view( LDLMS_Post_Types::COURSE )
			&& is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) )
		) {
			return true;
		}

		if (
			self::enabled_for_view( LDLMS_Post_Types::LESSON )
			&& is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ) )
		) {
			return true;
		}

		if (
			self::enabled_for_view( LDLMS_Post_Types::TOPIC )
			&& is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ) )
		) {
			return true;
		}

		if (
			self::enabled_for_view( LDLMS_Post_Types::GROUP )
			&& is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) )
		) {
			return true;
		}

		return $remove;
	}

	/**
	 * Filters the template filename to load a View-based version for the Modern LD30 theme.
	 *
	 * @since 4.21.0
	 *
	 * @param string              $template_filename Relative template file name.
	 * @param string              $name              Template name.
	 * @param array<string,mixed> $args              Template data.
	 * @param bool                $should_echo       Whether to echo the template output or not.
	 * @param bool                $return_file_path  Whether to return the template file path or not.
	 * @param Template|null       $instance          Current Instance of template engine rendering this template or null if not available (legacy).
	 *
	 * @return string
	 */
	public function load_modern_templates( $template_filename, $name, $args, $should_echo, $return_file_path, $instance ) {
		if (
			$instance === null
			|| ! self::enabled_for_view( $name )
		) {
			return $template_filename;
		}

		Assets::enqueue_scripts();

		return 'modern/' . ltrim( $template_filename, DIRECTORY_SEPARATOR );
	}

	/**
	 * Updates the wrapper class for Views loaded using the Modern LD30 theme.
	 *
	 * @since 4.21.0
	 *
	 * @param string  $wrapper_class CSS classes for the wrapper element.
	 * @param WP_Post $post          Current Post object.
	 *
	 * @return string
	 */
	public function update_wrapper_class( $wrapper_class, $post ) {
		if (
			! $post instanceof WP_Post
			|| ! self::enabled_for_view( LDLMS_Post_Types::get_post_type_key( $post->post_type ) )
		) {
			return $wrapper_class;
		}

		$wrapper_class .= ' learndash-wrapper--modern';

		return $wrapper_class;
	}

	/**
	 * Handles migrating the LearnDash_Settings_Section_Registration_Pages Appearance field to the new shape.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function migrate_updated_appearance_field(): void {
		if ( Version_Tracker::has_upgraded( '4.21.0' ) ) {
			return;
		}

		/**
		 * Using our old legacy field for compatibility to this new setting.
		 *
		 * @var array{ registration_appearance: string } $legacy_options
		 */
		$legacy_options = get_option( 'learndash_settings_registration_pages', [] );

		// Convert to new data shape.
		$default_registration_value = '';
		if ( ! empty( $legacy_options[ LearnDash_Settings_Section_Registration_Pages::$setting_registration_appearance ] )
			&& $legacy_options[ LearnDash_Settings_Section_Registration_Pages::$setting_registration_appearance ] === 'modern' ) {
			$default_registration_value = 'yes';
		}

		// Default and update our registration value.
		$options = get_option(
			'learndash_settings_appearance',
			[
				'registration_enabled' => $default_registration_value,
			]
		);
		update_option( 'learndash_settings_appearance', $options );
	}

	/**
	 * Sets the new install appearance.
	 *
	 * This is hooked to the `learndash_initialization_new_install` action and overrides the default
	 * appearance of the registration pages from classic to modern if this is a new install.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function action_set_new_install_appearance(): void {
		LearnDash_Settings_Section_General_Appearance::set_setting(
			'registration_enabled',
			'yes',
		);
		LearnDash_Settings_Section_General_Appearance::set_setting(
			'group_enabled',
			'yes',
		);
	}
}
