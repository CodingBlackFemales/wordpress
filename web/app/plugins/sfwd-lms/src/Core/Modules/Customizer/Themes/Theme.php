<?php
/**
 * Theme base class.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\Themes;

use Learndash_DTO_Validation_Exception;
use LearnDash_Theme_Register;
use LearnDash\Core\Modules\Customizer\DTO\Panel;
use LearnDash\Core\Modules\Customizer\DTO\Section;
use LearnDash\Core\Modules\Customizer\DTO\Control;
use LearnDash\Core\Modules\Customizer\DTO\Setting;
use LearnDash\Core\Utilities\Cast;
use InvalidArgumentException;
use LearnDash\Core\Utilities\Color;
use WP_Customize_Color_Control;
use WP_Customize_Control;

/**
 * Theme base class.
 *
 * @since 4.15.0
 *
 * @phpstan-type Config array{
 *     panels: Panel_Config[],
 * }
 *
 * @phpstan-type Panel_Config array{
 *     id: string,
 *     priority?: int,
 *     capability?: string,
 *     theme_supports?: string[],
 *     title?: string,
 *     description?: string,
 *     type?: string,
 *     active_callback?: callable,
 *     sections?: Section_Config[],
 * }
 *
 * @phpstan-type Section_Config array{
 *     id: string,
 *     priority?: int,
 *     capability?: string,
 *     theme_supports?: string[],
 *     title?: string,
 *     description?: string,
 *     type?: string,
 *     active_callback?: callable,
 *     description_hidden?: bool,
 *     controls?: Control_Config[],
 * }
 *
 * @phpstan-type Control_Config array{
 *     id: string,
 *     capability?: string,
 *     priority?: int,
 *     label?: string,
 *     description?: string,
 *     choices?: array<string|int, string>,
 *     input_attrs?: array<string, mixed>,
 *     allow_addition?: bool,
 *     type?: string,
 *     active_callback?: callable,
 *     settings?: Setting_Config[],
 * }
 *
 * @phpstan-type Setting_Config array{
 *     id: string,
 *     type?: string,
 *     capability?: string,
 *     theme_supports?: string[],
 *     default?: mixed,
 *     transport?: string,
 *     validate_callback?: callable,
 *     sanitize_callback?: callable,
 *     sanitize_js_callback?: callable,
 *     dirty?: bool,
 *     selector: string,
 *     property: string,
 *     unit?: string,
 *     important?: bool
 * }
 */
abstract class Theme {
	/**
	 * JS handle for live refreshing within the Customizer.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	private const JS_HANDLE = 'learndash-customizer-live-refresh';

	/**
	 * Returns the "Theme ID" for the Customizer theme. Used in Filters, Actions, and for checking if the current Theme is active within LearnDash via is_active(). Example: 'ld30'.
	 *
	 * @since 4.15.0
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Returns the configuration used for our Customizer Panels, Sections, Controls, and Settings that gets used throughout the plugin.
	 * See WP_Customize_Manager::add_panel(), WP_Customize_Manager::add_section(), WP_Customize_Manager::add_control(), and WP_Customize_Manager::add_setting() to see what parameters are accepted.
	 * However, keep in mind the additional keys added to the array for Settings that are used when generating the needed CSS and JS for the frontend and the Customizer Preview respectively.
	 * Additionally, setting a panel, section, or setting key manually here for Sections and Controls will not work, as you're expected to follow the hierarchy provided instead.
	 *
	 * @since 4.15.0
	 *
	 * @return Config
	 */
	abstract protected function get_config(): array;

	/**
	 * Returns whether the required Theme is active within LearnDash for the Customizer theme.
	 *
	 * @since 4.15.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return LearnDash_Theme_Register::get_active_theme_key() === $this->get_id();
	}

	/**
	 * Returns the CSS Handle for Enqueue'd CSS that we want to append our styles to. See wp_add_inline_style(). Example: 'learndash-ld30'.
	 *
	 * @since 4.15.0
	 *
	 * @return string
	 */
	public function get_css_handle(): string {
		return sprintf( 'learndash-%s', esc_attr( $this->get_id() ) );
	}

	/**
	 * Initializes the theme.
	 * If the theme is not active, it does nothing.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		/**
		 * Fires before the theme is initialized.
		 *
		 * @since 4.15.0
		 *
		 * @param Theme $theme Theme instance.
		 */
		do_action( 'learndash_customizer_theme_init_before', $this );

		$this->setup_hooks();

		/**
		 * Fires after the theme is initialized.
		 *
		 * @since 4.15.0
		 *
		 * @param Theme $theme Theme instance.
		 */
		do_action( 'learndash_customizer_theme_init_after', $this );
	}

	/**
	 * Registers our Panels, Sections, Controls, and Settings to the Customizer.
	 *
	 * @since 4.15.0
	 *
	 * @throws InvalidArgumentException           On missing Section object within our Control object.
	 * @throws Learndash_DTO_Validation_Exception If a section cannot be created.
	 *
	 * @return void
	 */
	public function register_customizer_settings(): void {
		$settings = $this->parse_config( $this->get_config() );

		foreach ( $settings as $setting ) {
			$this->maybe_add_panel( $setting->control->section->panel );

			$this->maybe_add_section( $setting->control->section );

			$this->maybe_add_setting( $setting );

			$this->maybe_add_control( $setting->control );
		}
	}

	/**
	 * Returns whether the required Theme is active within LearnDash for the Customizer theme.
	 * Outputs the CSS for each of our Settings. When hooking this to wp_enqueue_scripts,
	 * make sure you use a later priority than what the original CSS handle is being enqueue'd at.
	 *
	 * @since 4.15.0
	 *
	 * @throws Learndash_DTO_Validation_Exception If a config cannot be parsed.
	 *
	 * @return void
	 */
	public function output_css(): void {
		$settings = $this->parse_config( $this->get_config() );

		$css = '';
		foreach ( $settings as $setting ) {
			$css .= esc_html( $this->get_css_rule( $setting ) );
		}

		if ( empty( $css ) ) {
			return;
		}

		wp_add_inline_style( $this->get_css_handle(), $css );
	}

	/**
	 * Enqueues JS to live-refresh the CSS in the Customizer Preview.
	 *
	 * @since 4.15.0
	 *
	 * @throws Learndash_DTO_Validation_Exception If a config cannot be parsed.
	 *
	 * @return void
	 */
	public function enqueue_live_reload_js(): void {
		$settings = $this->parse_config( $this->get_config() );

		$settings = array_filter(
			$settings,
			fn( Setting $setting ): bool => $setting->transport === 'postMessage'
		);

		if ( empty( $settings ) ) {
			return;
		}

		wp_register_script(
			self::JS_HANDLE,
			LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/admin/modules/customizer/live-refresh.js',
			[ 'jquery' ],
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);

		wp_localize_script(
			self::JS_HANDLE,
			'learnDashCustomizer',
			[
				'l10n' => [
					'settings' => array_map(
						fn( Setting $setting ): array => $setting->to_array(),
						$settings
					),
				],
			]
		);

		wp_enqueue_script( self::JS_HANDLE );
	}

	/**
	 * Sets up the hooks to be ran when the theme is initialized.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action(
			'customize_register',
			[ $this, 'register_customizer_settings' ]
		);

		add_action(
			'customize_preview_init',
			[ $this, 'enqueue_live_reload_js' ]
		);

		add_action(
			'wp_enqueue_scripts',
			[ $this, 'output_css' ],
			/**
			 * Filters the priority on the output CSS. This needs to be later than the priority of when the stylesheet
			 * defined by get_css_handle() is hooked. Example: If learndash-breezy is loaded at wp_enqueue_scripts:10, then this needs to be at least 11.
			 *
			 * @since 4.15.0
			 *
			 * @param int   $priority Hook priority. Default 20.
			 * @param Theme $theme    Theme instance.
			 *
			 * @return int
			 */
			apply_filters(
				'learndash_customizer_css_priority',
				20,
				$this
			)
		);
	}

	/**
	 * Generates a CSS Rule based on the provided Setting object.
	 *
	 * @since 4.15.0
	 *
	 * @param Setting $setting Setting DTO object.
	 *
	 * @return string CSS Rule on success, empty String if a Selector and/or Property is not defined for the Setting
	 * or if no value was retrieved for the Setting and no Default was provided.
	 */
	protected function get_css_rule( Setting $setting ): string {
		if (
			empty( $setting->selector )
			|| empty( $setting->property )
		) {
			return '';
		}

		// WP_Customize_Setting supports saving to an option, so we support reading from one.
		// See WP_Customize_Setting::set_root_value().
		if ( $setting->type === 'option' ) {
			$value = get_option( $setting->id, $setting->default );
		} else {
			$value = get_theme_mod( $setting->id, $setting->default );
		}

		$value = Cast::to_string( $value );

		if (
			strlen( $value ) === 0
			|| $value === $setting->default // Ignore default values to prevent styling overriding.
		) {
			return '';
		}

		$rule = sprintf(
			'%s {
				%s: %s%s%s;
			}',
			$setting->selector,
			$setting->property,
			$value,
			$setting->unit,
			$setting->important ? ' !important' : ''
		);

		if ( empty( $setting->supports ) ) {
			return $rule;
		}

		// In case we have multiple selectors, we need to apply the same transformations to each.
		$selectors = explode( ',', $setting->selector );

		foreach ( $selectors as $selector ) {
			foreach ( $setting->supports as $support ) {
				switch ( $support ) {
					// Replicates the hover/focus states pre-4.21.3.
					case 'button-hover':
						$rule .= sprintf(
							'%s:hover {
								%s: %s%s%s;
							}',
							$selector,
							'opacity',
							0.85,
							'',
							$setting->important ? ' !important' : ''
						);
						break;
					case 'button-focus':
						$rule .= sprintf(
							'%s:focus {
								%s: %s%s%s;
							}',
							$selector,
							'opacity',
							0.75,
							'',
							$setting->important ? ' !important' : ''
						);
						break;
					default:
						break;
				}
			}
		}

		return $rule;
	}

	/**
	 * Maps our config to Settings objects. Each Setting has a reference to its parent, so you can traverse back up to the Panel if necessary.
	 *
	 * @since 4.15.0
	 *
	 * @param Config $config Configuration array.
	 *
	 * @throws Learndash_DTO_Validation_Exception If a section/panel/control/setting DTO cannot be created.
	 *
	 * @return Setting[]
	 */
	protected function parse_config( array $config ): array {
		$settings = [];

		if ( empty( $config['panels'] ) ) {
			return $settings;
		}

		foreach ( $config['panels'] as $panel_config ) {
			$panel = new Panel( $panel_config );

			if ( empty( $panel_config['sections'] ) ) {
				continue;
			}

			foreach ( $panel_config['sections'] as $section_config ) {
				if ( empty( $section_config['controls'] ) ) {
					continue;
				}

				$section = new Section(
					array_merge(
						$section_config,
						[
							'panel' => $panel,
						]
					)
				);

				foreach ( $section_config['controls'] as $control_config ) {
					if ( empty( $control_config['settings'] ) ) {
						continue;
					}

					$control = new Control(
						array_merge(
							$control_config,
							[
								'section' => $section,
							]
						)
					);

					foreach ( $control_config['settings'] as $setting_config ) {
						$settings[] = new Setting(
							array_merge(
								$setting_config,
								[
									'control' => $control,
								]
							)
						);
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Adds a Panel to the Customizer if it doesn't already exist.
	 *
	 * @since 4.15.0
	 *
	 * @param Panel $panel Panel Object.
	 *
	 * @return void
	 */
	protected function maybe_add_panel( Panel $panel ): void {
		global $wp_customize;

		if ( $wp_customize->get_panel( $panel->id ) ) {
			return;
		}

		// While passing this to the Customizer shouldn't matter, it is safer to exclude them in case WordPress Core starts checking this key in the future.
		$args = $panel->except( 'id' )->to_array();

		/**
		 * Fires before the panel is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Panel $panel Panel object.
		 * @param Theme $theme Theme instance.
		 */
		do_action( 'learndash_customizer_panel_add_before', $panel, $this );

		$wp_customize->add_panel(
			$panel->id,
			// @phpstan-ignore-next-line -- The output Array's Array Shape for our DTO is correct.
			$args
		);

		/**
		 * Fires after the panel is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Panel $panel Panel object.
		 * @param Theme $theme Theme instance.
		 */
		do_action( 'learndash_customizer_panel_add_after', $panel, $this );
	}

	/**
	 * Adds a Section to the Customizer if it doesn't already exist.
	 *
	 * @since 4.15.0
	 *
	 * @param Section $section Section Object.
	 *
	 * @return void
	 */
	protected function maybe_add_section( Section $section ): void {
		global $wp_customize;

		if ( $wp_customize->get_section( $section->id ) ) {
			return;
		}

		// While passing this to the Customizer shouldn't matter, it is safer to exclude them in case WordPress Core starts checking this key in the future.
		$args = $section->except( 'id' )->to_array();

		// Panels are technically optional for Sections.
		if ( ! empty( $section->panel->id ) ) {
			$args['panel'] = $section->panel->id;
		}

		/**
		 * Fires before the section is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Section $section Section object.
		 * @param Theme $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_section_add_before', $section, $this );

		$wp_customize->add_section(
			$section->id,
			// @phpstan-ignore-next-line -- The output Array's Array Shape for our DTO is correct.
			$args
		);

		/**
		 * Fires after the section is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Section $section Section object.
		 * @param Theme $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_section_add_after', $section, $this );
	}

	/**
	 * Adds a Control to the Customizer if it doesn't already exist.
	 *
	 * @since 4.15.0
	 *
	 * @param Control $control Control Object.
	 *
	 * @throws InvalidArgumentException           On missing Section object within our Control object.
	 * @throws Learndash_DTO_Validation_Exception If a section cannot be created.
	 *
	 * @return void
	 */
	protected function maybe_add_control( Control $control ): void {
		global $wp_customize;

		if ( $wp_customize->get_control( $control->id ) ) {
			return;
		}

		if ( empty( $control->section->id ) ) {
			throw new InvalidArgumentException(
				esc_html(
					__( 'All Customizer Controls must be attached to a Section.', 'learndash' )
				)
			);
		}

		// While passing this to the Customizer shouldn't matter, it is safer to exclude them in case WordPress Core starts checking this key in the future.
		$args = $control->except( 'id' )->to_array();

		$args['section'] = $control->section->id;

		// Ensure Settings are provided to the Customizer in the correct format.

		$args['settings'] = [];

		foreach ( $control->settings as $setting_args ) {
			// Leave the passed args alone. This may have been intentionally passed as a String or a WP_Customize_Setting object.
			if ( ! is_array( $setting_args ) ) {
				$args['settings'][] = $setting_args;
				continue;
			}

			// Settings are not Cast within the Control DTO to prevent an infinite loop while mapping the Config, so we will manually create the object now.
			$setting = new Setting( $setting_args );
			if ( empty( $setting->id ) ) {
				continue;
			}

			$args['settings'][] = $setting->id;
		}

		// Remove duplicate Settings, which may have happened as part of our Config structure.
		$args['settings'] = array_unique( $args['settings'] );

		/**
		 * If there's only one Settings item set and it is an ID, pass it through as a String.
		 *
		 * See WP_Customize_Control::_construct() to see this odd logic.
		 * We do this so that WP_Customize_Control::$setting gets populated with the matching WP_Customize_Setting object.
		 * This is important for some more complex Controls, such as WP_Customize_Color_Control.
		 */
		if (
			count( $args['settings'] ) === 1
			&& is_string( $args['settings'][0] )
		) {
			$args['settings'] = $args['settings'][0];
		}

		/**
		 * Fires before the control is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Control $control Control object.
		 * @param Theme   $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_control_add_before', $control, $this );

		// @phpstan-ignore-next-line -- $args Array Shape is correct.
		$this->add_control( $control, $args );

		/**
		 * Fires after the control is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Control $control Control object.
		 * @param Theme   $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_control_add_after', $control, $this );
	}

	/**
	 * While just setting "Type" for a Control will often still result in loading the Control,
	 * all features and styling may not be loaded correctly.
	 *
	 * This is particularly apparent with some more complex controls such as WP_Customize_Color_Control,
	 * where the styling will not correctly load and a "Default" button will not appear.
	 *
	 * This will directly load the appropriate Control Class into the Customizer Manager object to account for this.
	 *
	 * @since 4.15.0
	 *
	 * @param Control $control Control object.
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamName -- Multiline hint.
	 * @param array{
	 *     capability?: string,
	 *     priority?: int,
	 *     label?: string,
	 *     description?: string,
	 *     choices?: array<string|int, string>,
	 *     input_attrs?: array<string, mixed>,
	 *     allow_addition?: bool,
	 *     type?: string,
	 *     active_callback?: callable,
	 *     settings?: string[]|WP_Customize_Setting[],
	 * } $args Control args.
	 *
	 * @see WP_Customize_Manager::add_control()
	 *
	 * @return void
	 */
	private function add_control( Control $control, array $args ): void {
		global $wp_customize;

		$customize_control_object = new WP_Customize_Control(
			$wp_customize,
			$control->id,
			$args
		);

		switch ( $control->type ) {
			case 'color':
				$customize_control_object = new WP_Customize_Color_Control(
					$wp_customize,
					$control->id,
					$args
				);
				break;
			// TODO: Add more Control accommodations as necessary.
			default:
				break;
		}

		/**
		 * Filters the created Customizer Control object that is about to be added to the Customizer.
		 *
		 * @since 4.15.0
		 *
		 * @param WP_Customize_Control $customize_control_object Control object.
		 * @param array{
		 *     capability?: string,
		 *     priority?: int,
		 *     label?: string,
		 *     description?: string,
		 *     choices?: array<string|int, string>,
		 *     input_attrs?: array<string, mixed>,
		 *     allow_addition?: bool,
		 *     type?: string,
		 *     active_callback?: callable,
		 *     settings?: string[]|WP_Customize_Setting[],
		 * } $args Control args.
		 * @param Theme                $theme                    Theme instance.
		 */
		$customize_control_object = apply_filters( 'learndash_customizer_control', $customize_control_object, $args, $this );

		$wp_customize->add_control( $customize_control_object );
	}

	/**
	 * Adds a Setting to the Customizer if it doesn't already exist.
	 *
	 * @since 4.15.0
	 *
	 * @param Setting $setting Setting Object.object.
	 *
	 * @return void
	 */
	protected function maybe_add_setting( Setting $setting ): void {
		global $wp_customize;

		if ( $wp_customize->get_setting( $setting->id ) ) {
			return;
		}

		// In the case of Settings, as we'd be otherwise excluding so many keys, we pass through only the keys specifically used by the Customizer.
		$args = $setting->only(
			'type',
			'capability',
			'edit_theme_options',
			'theme_supports',
			'default',
			'transport',
			'validate_callback',
			'sanitize_callback',
			'sanitize_js_callback',
			'dirty'
		)->to_array();

		/**
		 * Fires before the setting is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Setting $setting Setting object.
		 * @param Theme   $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_setting_add_before', $setting, $this );

		$wp_customize->add_setting(
			$setting->id,
			// @phpstan-ignore-next-line -- The output Array's Array Shape for our DTO is correct.
			$args
		);

		/**
		 * Fires after the setting is created.
		 *
		 * @since 4.15.0
		 *
		 * @param Setting $setting Setting object.
		 * @param Theme   $theme   Theme instance.
		 */
		do_action( 'learndash_customizer_setting_add_after', $setting, $this );
	}
}
