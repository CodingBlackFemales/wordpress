<?php
/**
 * Presenter Mode settings.
 *
 * @since 4.23.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Presenter_Mode;

use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Presenter Mode settings.
 *
 * @since 4.23.0
 */
class Settings {
	/**
	 * Settings section key.
	 *
	 * @since 4.23.0
	 *
	 * @var string
	 */
	private const SETTINGS_SECTION_KEY = 'settings_theme_ld30';

	/**
	 * Settings option key.
	 *
	 * @since 4.23.0
	 *
	 * @var string
	 */
	private const SETTINGS_OPTION_KEY = 'learndash_settings_theme_ld30';

	/**
	 * Focus mode field key.
	 *
	 * @since 4.23.0
	 *
	 * @var string
	 */
	private const FOCUS_MODE_FIELD_KEY = 'focus_mode_enabled';

	/**
	 * Add settings fields.
	 *
	 * @since 4.23.0
	 *
	 * @param array<string, mixed> $fields               The settings fields.
	 * @param string               $settings_section_key The settings section key.
	 *
	 * @return array<string, mixed>
	 */
	public function add_settings_fields( $fields, string $settings_section_key ) {
		if (
			$settings_section_key !== self::SETTINGS_SECTION_KEY
			|| ! array_key_exists( self::FOCUS_MODE_FIELD_KEY, $fields )
		) {
			return $fields;
		}

		$focus_mode_sub_fields = array_filter(
			$fields,
			function ( $field ) {
				return is_array( $field )
					&& isset( $field['parent_setting'] )
					&& $field['parent_setting'] === self::FOCUS_MODE_FIELD_KEY;
			}
		);

		$insert_after_key = self::FOCUS_MODE_FIELD_KEY; // Default to the focus mode field.

		if ( ! empty( $focus_mode_sub_fields ) ) {
			// Add our fields after the last focus mode sub-field.
			$insert_after_key = array_key_last( $focus_mode_sub_fields );
		}

		// We cannot use LearnDash_Settings_Section::get_section_setting() because it will result in an infinite loop.
		$saved_options = self::get();

		$presenter_mode_fields = [
			'presenter_mode_enabled'       => [
				'child_section_state' => $saved_options['presenter_mode_enabled'] ? 'open' : 'closed',
				'label'               => esc_html__( 'Presenter Mode', 'learndash' ),
				'name'                => 'presenter_mode_enabled',
				'options'             => [
					''    => '',
					'yes' => esc_html__( 'Display a Presenter Mode Icon in Focus Mode. Click the icon to hide top and side bars for a presentation friendly experience. Select icon position below.', 'learndash' ),
				],
				'parent_setting'      => self::FOCUS_MODE_FIELD_KEY,
				'type'                => 'checkbox-switch',
				'validate_callback'   => 'sanitize_text_field',
				'value'               => $saved_options['presenter_mode_enabled'],
			],
			'presenter_mode_icon_position' => [
				'label'             => esc_html__( 'Icon Position', 'learndash' ),
				'name'              => 'presenter_mode_icon_position',
				'options'           => [
					'bottom-left'  => esc_html__( 'Bottom Left', 'learndash' ),
					'bottom-right' => esc_html__( 'Bottom Right', 'learndash' ),
					'top-left'     => esc_html__( 'Top Left', 'learndash' ),
					'top-right'    => esc_html__( 'Top Right', 'learndash' ),
				],
				'parent_setting'    => 'presenter_mode_enabled',
				'type'              => 'select',
				'validate_callback' => 'sanitize_text_field',
				'value'             => $saved_options['presenter_mode_icon_position'],
			],
		];

		$fields = Arr::insert_after_key( $insert_after_key, $fields, $presenter_mode_fields );

		return $fields;
	}

	/**
	 * Gets the presenter mode settings.
	 * We cannot use LearnDash_Settings_Section::get_section_setting() because it is not always initialized.
	 *
	 * @since 4.23.0
	 *
	 * @return array{
	 *     focus_mode_enabled: string,
	 *     focus_mode_sidebar_position: string,
	 *     presenter_mode_enabled: string,
	 *     presenter_mode_icon_position: string,
	 * }
	 */
	public static function get(): array {
		$settings = get_option( self::SETTINGS_OPTION_KEY );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$settings = array_filter(
			$settings,
			function ( $key ) {
				return strpos( $key, 'presenter_mode_' ) === 0
					|| $key === 'focus_mode_enabled'
					|| $key === 'focus_mode_sidebar_position';
			},
			ARRAY_FILTER_USE_KEY
		);

		/**
		 * Ensure we enforce default values.
		 * This is especially important for Select fields, as if Select2 is enabled they could save an empty string.
		 *
		 * @var array{
		 *     focus_mode_enabled: string,
		 *     focus_mode_sidebar_position: string,
		 *     presenter_mode_enabled: string,
		 *     presenter_mode_icon_position: string,
		 * }
		 */
		$settings = array_merge(
			[
				'focus_mode_enabled'           => '',
				'focus_mode_sidebar_position'  => 'default',
				'presenter_mode_enabled'       => '',
				'presenter_mode_icon_position' => 'bottom-right',
			],
			$settings
		);

		return $settings;
	}
}
