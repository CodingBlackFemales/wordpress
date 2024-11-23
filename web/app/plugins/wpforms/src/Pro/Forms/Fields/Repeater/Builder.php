<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WP_Post;
use WPForms\Forms\Fields\Helpers\RequirementsAlerts;
use WPForms\Pro\Forms\Fields\Traits\Layout\Builder as LayoutBuilderTrait;

/**
 * Repeater field's Builder class.
 *
 * @since 1.8.9
 */
class Builder {

	use LayoutBuilderTrait {
		hooks as layout_hooks;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		$this->layout_hooks();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'wpforms_builder_strings', [ $this, 'get_localized_strings' ], 10, 2 );
		add_action( 'wpforms_builder_print_footer_scripts', [ $this, 'field_preview_display_rows_buttons_template' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.8.9
	 */
	public function enqueue_assets() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-builder-field-repeater',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/repeater{$min}.js",
			[ 'wpforms-builder', 'wpforms-builder-field-layout' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Pass localized strings to builder.
	 *
	 * @since 1.8.9
	 *
	 * @param array   $strings All strings that will be passed to builder.
	 * @param WP_Post $form    Form object.
	 *
	 * @return array
	 */
	public function get_localized_strings( $strings, $form ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$form_data = wpforms_decode( $form->post_content ?? '' );

		$strings['repeater'] = [
			'size_notice_text'                => esc_html__( 'Field size cannot be changed when used in a repeater.', 'wpforms' ),
			'size_notice_tooltip'             => esc_html__( 'When a field is placed inside a column, the field size always equals the column width.', 'wpforms' ),
			'not_allowed_fields'              => $this->field_obj->get_not_allowed_fields(),
			'rows_limit_max'                  => Field::ROWS_LIMIT_MAX,
			'not_allowed'                     => esc_html__( 'Not Allowed', 'wpforms' ),
			/* translators: %s - Field name. */
			'not_allowed_alert_text'          => esc_html__( 'The %s field can’t be placed inside a Repeater field.', 'wpforms' ),
			'move_to_rows_rejected_alert'     => esc_html__( 'Only one field is allowed in each column when the Display option is set to Rows.', 'wpforms' ),
			'cant_switch_to_rows_alert'       => esc_html__( 'You can’t change Display to Rows because only one field per column is allowed.', 'wpforms' ),
			'cl_notice_text'                  => esc_html__( 'Conditional Logic cannot be enabled when the field is inside a Repeater.', 'wpforms' ),
			'cl_notice_text_grp'              => esc_html__( 'Conditional Logic has been disabled because this field has been placed inside a Repeater.', 'wpforms' ),
			'calculation_notice_text'         => esc_html__( 'Calculation cannot be enabled when the field is inside a Repeater.', 'wpforms' ),
			'calculation_notice_text_grp'     => esc_html__( 'Calculation has been disabled because this field has been placed inside a Repeater.', 'wpforms' ),
			'calculation_notice_tooltip'      => esc_html__( 'When a field is placed inside a Repeater field, Calculation is disabled.', 'wpforms' ),
			'delete_confirm'                  => esc_html__( 'Are you sure you want to delete the Repeater field? Deleting this field will also delete the fields inside it.', 'wpforms' ),
			'enabled_cf_alert_text'           => esc_html__( 'Conversational Forms cannot be enabled because your form contains a Repeater field.', 'wpforms' ),
			'field_add_cf_alert_text'         => esc_html__( 'The Repeater field cannot be used when Conversational Forms is enabled.', 'wpforms' ),
			'addons_requirements'             => [
				'wpforms-form-abandonment' => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-form-abandonment' ),
				'wpforms-save-resume'      => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-save-resume' ),
				'wpforms-geolocation'      => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-geolocation' ),
				'wpforms-signatures'       => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-signatures' ),
				'wpforms-lead-forms'       => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-lead-forms' ),
				'wpforms-google-sheets'    => RequirementsAlerts::is_inside_repeater_allowed( 'wpforms-google-sheets' ),
			],
			'addons_requirements_alert_text'  => [
				'wpforms-form-abandonment' => RequirementsAlerts::get_repeater_alert_text( 'Form Abandonment' ),
				'wpforms-save-resume'      => RequirementsAlerts::get_repeater_alert_text( 'Save and Resume' ),
				'wpforms-lead-forms'       => esc_html__( 'Lead Forms cannot be enabled because your form contains a Repeater field.', 'wpforms' ),
				'wpforms-google-sheets'    => RequirementsAlerts::get_repeater_alert_text( 'Google Sheets' ),
			],
			'is_google_sheets_has_connection' => ! empty( $form_data['providers']['google-sheets'] ),
			'addons_requirements_alert'       => [
				'wpforms-geolocation' => RequirementsAlerts::get_repeater_alert( 'Geolocation', 'wpforms-geolocation' ),
				'wpforms-signatures'  => RequirementsAlerts::get_repeater_alert( 'Signatures', 'wpforms-signatures' ),
				'wpforms-lead-forms'  => esc_html__( 'The Repeater field cannot be used when Lead Forms is enabled.', 'wpforms' ),
			],
			'fields_mapping'                  => [
				'title'   => esc_html__( 'Are you sure you want to move this field?', 'wpforms' ),
				'and'     => esc_html__( 'and', 'wpforms' ),
				/* translators: %s - Addon name. */
				'content' => esc_html__( 'It\'s currently mapped to %s, which will be reset if you add this field to a repeater.', 'wpforms' ),
			],
		];

		return $strings;
	}

	/**
	 * Field options panel.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {

		$this->field_option_columns_json( $field );

		// Basic options open markup.
		$this->field_obj->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		$this->field_obj->field_option(
			'label',
			$field,
			[
				'tooltip' => esc_html__( 'Enter text for the repeater field label. Repeater labels are more like headings and can be hidden in the Advanced Settings.', 'wpforms' ),
			]
		);

		$this->field_option_display_selector( $field );
		$this->field_option_preset_selector( $field );
		$this->field_option_button_type( $field );
		$this->field_option_button_labels( $field );
		$this->field_option_limit( $field );

		// Basic options close markup.
		$this->field_obj->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'close',
			]
		);

		// Advanced options open markup.
		$this->field_obj->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		$this->field_options_advanced( $field );

		// Advanced options close markup.
		$this->field_obj->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'close',
			]
		);
	}

	/**
	 * The Advanced field options.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_options_advanced( $field ) {

		// Size.
		$this->field_obj->field_option(
			'size',
			$field,
			[
				'class' => $field['preset'] !== '100' ? 'wpforms-disabled' : '',
			]
		);

		// Description.
		$this->field_obj->field_option( 'description', $field );

		// Hide label.
		$this->field_obj->field_option( 'label_hide', $field );
	}

	/**
	 * The `Display` field option.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_option_display_selector( $field ) {

		$this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'    => 'display',
				'value'   => esc_html__( 'Display', 'wpforms' ),
				'tooltip' => esc_html__( 'Choose whether you want your fields to be repeated as single rows or as a block of fields.', 'wpforms' ),
			]
		);

		$inputs = '';

		foreach ( Field::DISPLAY_VALUES as $value ) {

			$inputs .= sprintf(
				'<input type="radio" name="fields[%1$d][display]" id="wpforms-field-option-%1$d-display-%2$s" value="%2$s"%3$s><label for="wpforms-field-option-%1$d-display-%2$s" class="display-%2$s"></label>',
				(int) $field['id'],
				esc_attr( $value ),
				$field['display'] === $value ? ' checked' : ''
			);
		}

		$this->field_obj->field_element(
			'row',
			$field,
			[
				'slug'    => 'display',
				'content' => $inputs,
			]
		);
	}

	/**
	 * The Button Type field option.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_option_button_type( $field ) {

		$output = $this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'    => 'button_type',
				'value'   => esc_html__( 'Button Type', 'wpforms' ),
				'tooltip' => esc_html__( 'Select the type of buttons to use for the repeater field.', 'wpforms' ),
			],
			false
		);

		$output .= $this->field_obj->field_element(
			'select',
			$field,
			[
				'slug'    => 'button_type',
				'value'   => $field['button_type'] ?? 'buttons_with_icons',
				'options' => [
					'buttons_with_icons' => esc_html__( 'Buttons with icons', 'wpforms' ),
					'buttons'            => esc_html__( 'Buttons', 'wpforms' ),
					'icons_with_text'    => esc_html__( 'Icons with text', 'wpforms' ),
					'icons'              => esc_html__( 'Icons', 'wpforms' ),
					'plain_text'         => esc_html__( 'Plain text', 'wpforms' ),
				],
			],
			false
		);

		$this->field_obj->field_element(
			'row',
			$field,
			[
				'slug'    => 'button-type',
				'content' => $output,
				'class'   => $field['display'] === 'rows' ? 'wpforms-hidden' : '',
			]
		);
	}

	/**
	 * The Button Labels field options.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_option_button_labels( $field ) {

		printf(
			'<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-button-labels %1$s"
				id="wpforms-field-option-row-%1$d-button-labels"
				data-field-id="%2$d">',
			$field['display'] === 'rows' ? 'wpforms-hidden' : '',
			(int) $field['id']
		);

		$this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'    => 'button_labels',
				'value'   => esc_html__( 'Button Labels', 'wpforms' ),
				'tooltip' => esc_html__( 'Enter text for the repeater field buttons.', 'wpforms' ),
			]
		);

		echo '<div class="wpforms-field-options-columns-2 wpforms-field-options-columns">';
		echo '<div class="wpforms-field-options-column">';

		printf(
			'<input type="text" class="add" id="wpforms-field-option-%1$d-button_add_label" name="fields[%1$d][button_add_label]" value="%2$s">',
			absint( $field['id'] ),
			esc_attr( $field['button_add_label'] ?? $this->field_obj->defaults['button_add_label'] )
		);

		printf(
			'<label for="wpforms-field-option-%1$d-button_add_label" class="sub-label">%2$s</label>',
			(int) $field['id'],
			esc_html__( 'Add Label', 'wpforms' )
		);

		echo '</div>';
		echo '<div class="wpforms-field-options-column">';

		printf(
			'<input type="text" class="remove" id="wpforms-field-option-%1$d-button_remove_label" name="fields[%1$d][button_remove_label]" value="%2$s">',
			(int) $field['id'],
			esc_attr( $field['button_remove_label'] ?? $this->field_obj->defaults['button_remove_label'] )
		);

		printf(
			'<label for="wpforms-field-option-%1$d-button_remove_label" class="sub-label">%2$s</label>',
			absint( $field['id'] ),
			esc_html__( 'Add Label', 'wpforms' )
		);

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * The Limit rows field options.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_option_limit( $field ) {

		printf(
			'<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-rows-limit"
				id="wpforms-field-option-row-%1$d-rows_limit"
				data-field-id="%1$d">',
			(int) $field['id']
		);

		$this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'    => 'rows_limit',
				'value'   => esc_html__( 'Limit', 'wpforms' ),
				'tooltip' => esc_html__( 'Set the minimum and maximum number of times the field can be repeated.', 'wpforms' ),
			]
		);

		echo '<div class="wpforms-field-options-columns-2 wpforms-field-options-columns">';
		echo '<div class="wpforms-field-options-column">';

		printf(
			'<input type="number" class="rows-limit-min" id="wpforms-field-option-%1$d-rows_limit_min" name="fields[%1$d][rows_limit_min]" value="%2$s" min="1" max="%3$d" step="1">',
			absint( $field['id'] ),
			esc_attr( $field['rows_limit_min'] ?? $this->field_obj->defaults['rows_limit_min'] ),
			Field::ROWS_LIMIT_MAX // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		printf(
			'<label for="wpforms-field-option-%1$d-rows_limit_min" class="sub-label">%2$s</label>',
			(int) $field['id'],
			esc_html__( 'Minimum', 'wpforms' )
		);

		echo '</div>';
		echo '<div class="wpforms-field-options-column">';

		printf(
			'<input type="number" class="rows-limit-max" id="wpforms-field-option-%1$d-rows_limit_max" name="fields[%1$d][rows_limit_max]" value="%2$s" min="2" max="%3$d" step="1">',
			(int) $field['id'],
			esc_attr( $field['rows_limit_max'] ?? $this->field_obj->defaults['rows_limit_max'] ),
			Field::ROWS_LIMIT_MAX // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		printf(
			'<label for="wpforms-field-option-%1$d-rows_limit_max" class="sub-label">%2$s</label>',
			absint( $field['id'] ),
			esc_html__( 'Maximum', 'wpforms' )
		);

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Output the Repeater field preview columns wrapped by columns' container.
	 *
	 * @since 1.8.9
	 *
	 * @param string $columns_html  Columns HTML.
	 * @param string $columns_class Columns container CSS class.
	 * @param array  $field         Field settings.
	 */
	protected function field_preview_columns_wrap( $columns_html, $columns_class, $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Generic.Metrics.CyclomaticComplexity.TooHigh

		$display           = $field['display'] ?? $this->field_obj->defaults['display'];
		$row_buttons_class = $display === 'rows' ? '' : 'wpforms-hidden';

		printf(
			'<div class="wpforms-field-layout-columns %1$s">
				%2$s%3$s
			</div>
			%4$s
			',
			esc_attr( $columns_class ),
			$columns_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->get_field_preview_display_rows_buttons( $row_buttons_class ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->get_field_preview_display_blocks_buttons( $field ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Get the Display rows buttons.
	 *
	 * @since 1.8.9
	 *
	 * @param string $class_name CSS class name.
	 *
	 * @return string
	 */
	private function get_field_preview_display_rows_buttons( $class_name = '' ): string {

		return sprintf(
			'<div class="wpforms-field-repeater-display-rows-buttons %1$s">
				<button type="button" class="dashicons dashicons-insert wpforms-field-repeater-display-rows-buttons-add"></button>
				<button type="button" class="dashicons dashicons-remove wpforms-field-repeater-display-rows-buttons-remove"></button>
			</div>',
			esc_attr( $class_name )
		);
	}

	/**
	 * Get the Display blocks buttons.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_field_preview_display_blocks_buttons( $field ): string {

		$display     = $field['display'] ?? $this->field_obj->defaults['display'];
		$button_type = $field['button_type'] ?? $this->field_obj->defaults['button_type'];
		$class_name  = $display === 'blocks' ? '' : 'wpforms-hidden';

		return sprintf(
			'<div class="wpforms-field-repeater-display-blocks-buttons %1$s" data-button-type="%2$s">
				<button type="button" class="wpforms-field-repeater-display-blocks-buttons-add">
					<i class="dashicons dashicons-insert"></i><span>%3$s</span>
				</button>
				<button type="button" class="wpforms-field-repeater-display-blocks-buttons-remove">
					<i class="dashicons dashicons-remove"></i><span>%4$s</span>
				</button>
			</div>',
			esc_attr( $class_name ),
			esc_attr( $button_type ),
			esc_html( $field['button_add_label'] ?? $this->field_obj->defaults['button_add_label'] ),
			esc_html( $field['button_remove_label'] ?? $this->field_obj->defaults['button_remove_label'] )
		);
	}

	/**
	 * Output template of the Display rows buttons.
	 *
	 * @since 1.8.9
	 */
	public function field_preview_display_rows_buttons_template() {

		?>
		<script type="text/html" id="tmpl-wpforms-repeater-field-display-rows-buttons-template">
			<?php echo $this->get_field_preview_display_rows_buttons( '{{ data.class }}' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</script>
		<?php
	}
}
