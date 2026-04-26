<?php
/**
 * LD30 Customizer Registration Class.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\Themes;

use LearnDash_Settings_Section;

/**
 * LD30 Customizer Registration Class.
 *
 * @since 4.15.0
 *
 * @phpstan-import-type Config from Theme
 * @phpstan-import-type Section_Config from Theme
 */
class LD30 extends Theme {
	/**
	 * Colors.
	 *
	 * @since 4.15.0
	 *
	 * @var array{primary: string, secondary: string, tertiary: string}
	 */
	private array $colors;

	/**
	 * Constructor.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	public function __construct() {
		$colors = wp_parse_args(
			array_filter(
				[
					'primary'   => sanitize_hex_color( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_primary' ) ),
					'secondary' => sanitize_hex_color( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_secondary' ) ),
					'tertiary'  => sanitize_hex_color( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'color_tertiary' ) ),
				]
			),
			// If the values are empty or invalid, we want to keep them in this case.
			[
				'primary'   => sanitize_hex_color( constant( 'LD_30_COLOR_PRIMARY' ) ),
				'secondary' => sanitize_hex_color( constant( 'LD_30_COLOR_SECONDARY' ) ),
				'tertiary'  => sanitize_hex_color( constant( 'LD_30_COLOR_TERTIARY' ) ),
			]
		);

		/** This filter is documented in themes/ld30/includes/helpers.php */
		$this->colors = apply_filters(
			'learndash_30_custom_colors',
			$colors
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 4.15.0
	 */
	public function get_id(): string {
		return 'ld30';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 4.15.0
	 */
	public function get_css_handle(): string {
		return 'learndash-front';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 4.15.0
	 */
	protected function get_config(): array {
		/**
		 * Filters the Customizer Settings shown for the LearnDash 3.0 Theme.
		 *
		 * @since 4.15.0
		 *
		 * @param Config $args  Config for the Theme.
		 * @param Theme  $theme Theme instance.
		 *
		 * @return Config
		 */
		return apply_filters(
			'learndash_customizer_config',
			[
				'panels' => [
					[
						'id'          => 'learndash_ld30_styles',
						'title'       => __( 'LearnDash Styles', 'learndash' ),
						'description' => __( 'Customize LearnDash Styles', 'learndash' ),
						'sections'    => [
							$this->get_global_section_config(),
							$this->get_course_section_config(),
							$this->get_lesson_section_config(),
							$this->get_topic_section_config(),
							$this->get_quiz_section_config(),
							$this->get_navigation_panel_section_config(),
						],
					],
				],
			],
			$this
		);
	}

	/**
	 * Returns the Global Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Global Section Config.
	 */
	private function get_global_section_config(): array {
		return [
			'id'       => 'learndash_ld30_global_styles',
			'title'    => __( 'Global Styles', 'learndash' ),
			'controls' => [
				[
					'id'       => 'learndash_ld30_global_status_complete_background_color',
					'type'     => 'color',
					'label'    => __( 'Complete Status Background Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_status_complete_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-course-status .ld-status.ld-status-complete, .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'background',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_status_complete_text_color',
					'type'     => 'color',
					'label'    => __( 'Complete Status Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_status_complete_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-course-status .ld-status.ld-status-complete, .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_status_in_progress_background_color',
					'type'     => 'color',
					'label'    => __( 'In Progress Status Background Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_status_in_progress_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-status.ld-status-progress, .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-progress',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_status_in_progress_text_color',
					'type'     => 'color',
					'label'    => __( 'In Progress Status Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_status_in_progress_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-status.ld-status-progress',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_global_expand_button_radius',
					'type'        => 'number',
					'label'       => sprintf(
						// translators: placeholders: Course label.
						__( '%s Expand/Collapse Button Border Radius', 'learndash' ),
						learndash_get_custom_label( 'course' ),
					),
					'input_attrs' => [
						'min'         => 0,
						'max'         => 100,
						'step'        => 1,
						'placeholder' => __( 'Default', 'learndash' ),
					],
					'settings'    => [
						[
							'id'                => 'learndash_ld30_global_button_radius',
							'sanitize_callback' => 'sanitize_text_field', // Ensures a 0 isn't saved if the field is blank.
							'selector'          => '.learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon, .learndash-wrapper .ld-expand-button.ld-primary-background, .learndash-wrapper .ld-content-actions .ld-content-action a',
							'property'          => 'border-radius',
							'unit'              => 'px',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_content_header_background_color',
					'type'     => 'color',
					'label'    => __( 'Content Header Background Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_content_header_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_content_header_text_color',
					'type'     => 'color',
					'label'    => __( 'Content Header Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_content_header_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_next_button_background_color',
					'type'     => 'color',
					'label'    => __( 'Next Button Background Color', 'learndash' ),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_global_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_global_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:last-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_next_button_text_color',
					'type'     => 'color',
					'label'    => __( 'Next Button Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_next_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_previous_button_background_color',
					'type'     => 'color',
					'label'    => __( 'Previous Button Background Color', 'learndash' ),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_global_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_global_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:first-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_global_previous_button_text_color',
					'type'     => 'color',
					'label'    => __( 'Previous Button Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_global_previous_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the Course Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Course Section Config.
	 */
	private function get_course_section_config(): array {
		return [
			'id'       => 'learndash_ld30_course_styles',
			'title'    => sprintf(
				// translators: placeholders: Course label.
				__( '%s Pages', 'learndash' ),
				learndash_get_custom_label( 'course' )
			),
			'controls' => [
				[
					'id'       => 'learndash_ld30_course_progress_bar_background_color',
					'type'     => 'color',
					'label'    => __( 'Progress Bar Filled Background Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_progress_bar_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-progress .ld-progress-bar .ld-progress-bar-percentage',
							'property'          => 'background-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_progress_bar_text_color',
					'type'     => 'color',
					'label'    => __( 'Progress Bar Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_progress_bar_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-progress .ld-progress-heading .ld-progress-stats .ld-progress-percentage',
							'property'          => 'color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_status_complete_background_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_status_complete_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status.ld-status-complete',
							'property'          => 'background-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_status_complete_text_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_status_complete_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status.ld-status-complete',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_status_in_progress_background_color',
					'type'        => 'color',
					'label'       => __( 'In Progress Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_status_in_progress_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status.ld-status-progress',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_status_in_progress_text_color',
					'type'     => 'color',
					'label'    => __( 'In Progress Status Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_status_in_progress_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status.ld-status-progress',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_expand_button_background_color',
					'type'     => 'color',
					'label'    => __( 'Expand/Collapse Button Background Color', 'learndash' ),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_course_expand_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-button-alternate:not([disabled]) .ld-icon, .single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-primary-background:not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_course_expand_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-button-alternate:focus .ld-icon, .single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-primary-background:focus',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_expand_button_text_color',
					'type'     => 'color',
					'label'    => __( 'Expand/Collapse Button Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_expand_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon, .single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-primary-background',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_expand_link_text_color',
					'type'     => 'color',
					'label'    => __( 'Expand/Collapse Link Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_expand_link_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-button-alternate .ld-text',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_expand_button_radius',
					'type'        => 'number',
					'label'       => sprintf(
						// translators: placeholders: Course label.
						__( '%s Expand/Collapse Button Border Radius', 'learndash' ),
						learndash_get_custom_label( 'course' ),
					),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'input_attrs' => [
						'min'         => 0,
						'max'         => 100,
						'step'        => 1,
						'placeholder' => __( 'Default', 'learndash' ),
					],
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_expand_button_radius',
							'sanitize_callback' => 'sanitize_text_field',
							// Ensures a 0 isn't saved if the field is blank.
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon, .single-sfwd-courses .learndash-wrapper .ld-expand-button.ld-primary-background',
							'property'          => 'border-radius',
							'unit'              => 'px',
							'important'         => true,
							'default'           => '',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_content_header_background_color',
					'type'        => 'color',
					'label'       => __( 'Content Header Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_content_header_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_course_content_header_text_color',
					'type'        => 'color',
					'label'       => __( 'Content Header Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_course_content_header_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_lesson_complete_checkbox_background_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s Complete Checkbox Background Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_lesson_complete_checkbox_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-icon.ld-status-complete',
							'property'          => 'background-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_lesson_complete_checkbox_text_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s Complete Checkbox Text Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_lesson_complete_checkbox_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-icon.ld-status-complete',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_lesson_progress_circle_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s In Progress Circle Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_course_lesson_progress_circle_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-in-progress',
							'property'          => 'border-left-color',
							'default'           => $this->colors['secondary'],
						],
						[
							'id'                => 'learndash_ld30_course_lesson_progress_circle_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-in-progress',
							'property'          => 'border-top-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_quiz_complete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Complete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_quiz_complete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-icon.ld-quiz-complete',
							'property'          => 'color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_course_quiz_incomplete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Incomplete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_course_quiz_incomplete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-courses .learndash-wrapper .ld-status-icon.ld-quiz-incomplete',
							'property'          => 'color',
							'default'           => '#333333',
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the Lesson Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Lesson Section Config.
	 */
	private function get_lesson_section_config(): array {
		return [
			'id'       => 'learndash_ld30_lesson_styles',
			'title'    => sprintf(
				// translators: placeholders: Lesson label.
				__( '%s Pages', 'learndash' ),
				learndash_get_custom_label( 'lesson' )
			),
			'controls' => [
				[
					'id'          => 'learndash_ld30_lesson_status_complete_background_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_status_complete_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'background-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_status_complete_text_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_status_complete_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_status_in_progress_background_color',
					'type'        => 'color',
					'label'       => __( 'In Progress Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_status_in_progress_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-progress',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_status_in_progress_text_color',
					'type'        => 'color',
					'label'       => __( 'In Progress Status Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_status_in_progress_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-progress',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_content_header_background_color',
					'type'        => 'color',
					'label'       => __( 'Content Header Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_content_header_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_content_header_text_color',
					'type'        => 'color',
					'label'       => __( 'Content Header Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_content_header_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-table-list-header.ld-primary-background',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_lesson_quiz_complete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Complete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_lesson_quiz_complete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-lessons .learndash-wrapper .ld-status-icon.ld-quiz-complete',
							'property'          => 'color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_lesson_quiz_incomplete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Incomplete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_lesson_quiz_incomplete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-lessons .learndash-wrapper .ld-status-icon.ld-quiz-incomplete',
							'property'          => 'color',
							'default'           => '#333333',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_next_button_background_color',
					'type'        => 'color',
					'label'       => __( 'Next Button Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_lesson_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_lesson_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:last-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_next_button_text_color',
					'type'        => 'color',
					'label'       => __( 'Next Button Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_next_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_previous_button_background_color',
					'type'        => 'color',
					'label'       => __( 'Previous Button Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_lesson_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_lesson_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:first-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_previous_button_text_color',
					'type'        => 'color',
					'label'       => __( 'Previous Button Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_previous_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_lesson_content_action_button_radius',
					'type'        => 'number',
					'label'       => __( 'Content Action Button Border Radius', 'learndash' ),
					'input_attrs' => [
						'min'         => 0,
						'max'         => 100,
						'step'        => 1,
						'placeholder' => __( 'Default', 'learndash' ),
					],
					'settings'    => [
						[
							'id'                => 'learndash_ld30_lesson_content_action_button_radius',
							'sanitize_callback' => 'sanitize_text_field', // Ensures a 0 isn't saved if the field is blank.
							'selector'          => '.learndash_post_sfwd-lessons .learndash-wrapper .ld-content-actions .ld-content-action a, .learndash_post_sfwd-lessons .learndash-wrapper .ld-content-actions .learndash_mark_complete_button',
							'property'          => 'border-radius',
							'unit'              => 'px',
							'important'         => true,
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the Topic Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Topic Section Config.
	 */
	private function get_topic_section_config(): array {
		return [
			'id'       => 'learndash_ld30_topic_styles',
			'title'    => sprintf(
				// translators: placeholders: Topic label.
				__( '%s Pages', 'learndash' ),
				learndash_get_custom_label( 'topic' )
			),
			'controls' => [
				[
					'id'          => 'learndash_ld30_topic_status_complete_background_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_status_complete_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'background-color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_status_complete_text_color',
					'type'        => 'color',
					'label'       => __( 'Complete Status Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_status_complete_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-complete',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_status_in_progress_background_color',
					'type'        => 'color',
					'label'       => __( 'In Progress Status Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_status_in_progress_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-progress',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_status_in_progress_text_color',
					'type'        => 'color',
					'label'       => __( 'In Progress Status Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_status_in_progress_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-breadcrumbs .ld-status.ld-status-progress',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_topic_quiz_complete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Complete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_topic_quiz_complete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-topic .learndash-wrapper .ld-status-icon.ld-quiz-complete',
							'property'          => 'color',
							'default'           => $this->colors['secondary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_topic_quiz_incomplete_icon_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( '%s Incomplete Icon Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_topic_quiz_incomplete_icon_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-topic .learndash-wrapper .ld-status-icon.ld-quiz-incomplete',
							'property'          => 'color',
							'default'           => '#333333',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_next_button_background_color',
					'type'        => 'color',
					'label'       => __( 'Next Button Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_topic_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_topic_next_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:last-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_next_button_text_color',
					'type'        => 'color',
					'label'       => __( 'Next Button Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_next_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:last-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_previous_button_background_color',
					'type'        => 'color',
					'label'       => __( 'Previous Button Background Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_topic_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent):not([disabled])',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_topic_previous_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:first-child .ld-button:focus:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_previous_button_text_color',
					'type'        => 'color',
					'label'       => __( 'Previous Button Text Color', 'learndash' ),
					'description' => __( 'This will override the setting under "Global Styles" if set.', 'learndash' ),
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_previous_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-action:first-child .ld-button:not(.ld-button-reverse):not(.learndash-link-previous-incomplete):not(.ld-button-transparent)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'          => 'learndash_ld30_topic_content_action_button_radius',
					'type'        => 'number',
					'label'       => __( 'Content Action Button Border Radius', 'learndash' ),
					'input_attrs' => [
						'min'         => 0,
						'max'         => 100,
						'step'        => 1,
						'placeholder' => __( 'Default', 'learndash' ),
					],
					'settings'    => [
						[
							'id'                => 'learndash_ld30_topic_content_action_button_radius',
							'sanitize_callback' => 'sanitize_text_field', // Ensures a 0 isn't saved if the field is blank.
							'selector'          => '.learndash_post_sfwd-topic .learndash-wrapper .ld-content-actions .ld-content-action a, .learndash_post_sfwd-topic .learndash-wrapper .ld-content-actions .learndash_mark_complete_button',
							'property'          => 'border-radius',
							'unit'              => 'px',
							'important'         => true,
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the Quiz Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Quiz Section Config.
	 */
	private function get_quiz_section_config(): array {
		return [
			'id'       => 'learndash_ld30_quiz_styles',
			'title'    => sprintf(
				// translators: placeholders: Quiz label.
				__( '%s Pages', 'learndash' ),
				learndash_get_custom_label( 'quiz' )
			),
			'controls' => [
				[
					'id'       => 'learndash_ld30_quiz_breadcrumbs_text_color',
					'type'     => 'color',
					'label'    => __( 'Breadcrumbs Link Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_quiz_breadcrumbs_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-quiz .learndash-wrapper .ld-breadcrumbs a',
							'property'          => 'color',
							'important'         => true,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_quiz_start_button_background_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( 'Start %s Button Background Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_quiz_start_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-quiz .learndash-wrapper .wpProQuiz_content .wpProQuiz_button:not(.wpProQuiz_button_reShowQuestion):not([disabled])',
							'property'          => 'background-color',
							'important'         => true,
							'default'           => $this->colors['primary'],
							'supports'          => [
								'button-hover',
								'button-focus',
							],
							'transport'         => 'refresh', // Required due to the button-hover support.
						],
						[
							'id'                => 'learndash_ld30_quiz_start_button_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-quiz .learndash-wrapper .wpProQuiz_content .wpProQuiz_button:focus:not(.wpProQuiz_button_reShowQuestion)',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_quiz_start_button_text_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Quiz label.
						__( 'Start %s Button Text Color', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_quiz_start_button_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.single-sfwd-quiz .learndash-wrapper .wpProQuiz_content .wpProQuiz_button:not(.wpProQuiz_button_reShowQuestion)',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
			],
		];
	}

	/**
	 * Returns the Navigation Panel Section Config.
	 *
	 * @since 4.15.0
	 *
	 * @return Section_Config Navigation Panel Section Config.
	 */
	private function get_navigation_panel_section_config(): array {
		return [
			'id'       => 'learndash_ld30_navigation_styles',
			'title'    => __( 'Focus Mode Navigation Panel', 'learndash' ),
			'controls' => [
				[
					'id'       => 'learndash_ld30_navigation_panel_header_background_color',
					'type'     => 'color',
					'label'    => __( 'Header Background Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_navigation_panel_header_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => 'body .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-course-navigation-heading, body .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_header_text_color',
					'type'     => 'color',
					'label'    => __( 'Header Text Color', 'learndash' ),
					'settings' => [
						[
							'id'                => 'learndash_ld30_navigation_panel_header_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => 'body .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-course-navigation-heading h3 a',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_header_arrow_background_color',
					'type'     => 'color',
					'label'    => __( 'Header Arrow Icon Background Color', 'learndash' ),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:not(:hover):not(:focus) .ld-icon',
							'property'          => 'background-color',
							'default'           => $this->colors['primary'],
							'important'         => true, // Required due to other CSS in the plugin using !important.
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:hover .ld-icon, .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:focus .ld-icon',
							'property'          => 'border-color',
							'default'           => $this->colors['primary'],
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:hover .ld-icon, .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:focus .ld-icon',
							'property'          => 'color',
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_header_arrow_text_text_color',
					'type'     => 'color',
					'label'    => __( 'Header Arrow Icon Text Color', 'learndash' ),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_text_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:hover .ld-icon, .learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:focus .ld-icon',
							'property'          => 'background-color',
							'default'           => '#fff',
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_text_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:not(:hover):not(:focus) .ld-icon',
							'property'          => 'border-color',
							'default'           => '#fff',
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_text_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger .ld-icon',
							'property'          => 'color',
							'default'           => '#fff',
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_header_arrow_text_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-trigger:focus .ld-icon',
							'property'          => 'outline-color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_lesson_content_preview_arrow_background_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s Content Preview Arrow Background Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						/**
						 * This workaround allows us to set multiple CSS properties based on the
						 * same Setting via the Control.
						 *
						 * The IDs are intentionally the same to ensure we only create one theme_mod.
						 *
						 * The Customizer itself will ignore Settings with duplicate IDs but our CSS
						 * and JS code will not, which means it will properly assign the additional
						 * CSS Properties as defined by these configs.
						 */
						[
							'id'                => 'learndash_ld30_navigation_panel_lesson_content_preview_arrow_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => 'body .learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon',
							'property'          => 'background-color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
						[
							'id'                => 'learndash_ld30_navigation_panel_lesson_content_preview_arrow_background_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => 'body .learndash-wrapper .ld-expand-button.ld-button-alternate:focus .ld-icon',
							'property'          => 'outline-color',
							'important'         => false,
							'default'           => $this->colors['primary'],
						],

					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_lesson_content_preview_arrow_text_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s Content Preview Arrow Text Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_navigation_panel_lesson_content_preview_arrow_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => 'body .learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon',
							'property'          => 'color',
							'default'           => '#fff',
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_lesson_content_preview_text_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( '%s Content Preview Text Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_navigation_panel_lesson_content_preview_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-expand-button.ld-button-alternate .ld-text',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
				[
					'id'       => 'learndash_ld30_navigation_panel_current_lesson_text_color',
					'type'     => 'color',
					'label'    => sprintf(
						// translators: placeholders: Lesson label.
						__( 'Current %s Text Color', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
					),
					'settings' => [
						[
							'id'                => 'learndash_ld30_navigation_panel_current_lesson_text_color',
							'sanitize_callback' => 'sanitize_hex_color',
							'selector'          => '.learndash-wrapper .ld-focus-sidebar .ld-lesson-item.ld-is-current-lesson .ld-lesson-title',
							'property'          => 'color',
							'important'         => true, // Required due to other CSS in the plugin using !important.
							'default'           => $this->colors['primary'],
						],
					],
				],
			],
		];
	}
}
