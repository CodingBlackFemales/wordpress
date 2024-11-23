<?php

namespace WPForms\Pro\Forms\Fields\Traits\Layout;

use WP_Post;
use WPForms_Field_Layout;
use WPForms_Builder_Panel_Fields;

/**
 * The Layout and Repeater fields' Builder trait.
 *
 * @since 1.8.9
 */
trait Builder {

	/**
	 * Instance of the WPForms_Field_Layout class.
	 *
	 * @since 1.8.9
	 *
	 * @var WPForms_Field_Layout
	 */
	private $field_obj;

	/**
	 * Class constructor.
	 *
	 * @since 1.8.9
	 *
	 * @param WPForms_Field_Layout $field_obj Instance of the WPForms_Field_Layout class.
	 */
	public function __construct( $field_obj ) {

		$this->field_obj = $field_obj;

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
		add_filter( 'wpforms_save_form_args', [ $this, 'save_form_args' ], 20, 3 );
		add_filter( 'wpforms_builder_panel_fields_preview_fields', [ $this->field_obj, 'filter_base_fields' ] );
		add_filter( 'wpforms_builder_strings', [ $this, 'get_localized_strings' ], 10, 2 );
		add_action( 'wpforms_builder_print_footer_scripts', [ $this, 'field_preview_column_plus_placeholder_template' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.8.9
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-builder-field-layout',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/layout{$min}.js",
			[ 'wpforms-builder' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Columns JSON hidden field option.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function field_option_columns_json( $field ) {

		printf(
			'<input type="hidden" name="fields[%1$d][columns-json]" id="wpforms-field-option-%1$d-columns-json" value="%2$s">',
			(int) $field['id'],
			esc_attr( wp_json_encode( $field['columns'] ?? $this->field_obj->defaults['columns'] ) )
		);
	}

	/**
	 * Preset selector field option.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	private function field_option_preset_selector( $field ) {

		// Defaults.
		$display = $field['display'] ?? 'columns';
		$presets = $this->field_obj->get_presets();
		$label   = $field['type'] === 'repeater' ? esc_html__( 'Layout', 'wpforms' ) : esc_html__( 'Select a Layout.', 'wpforms' );

		$this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'    => 'preset',
				'value'   => $label,
				'tooltip' => esc_html__( 'Select a predefined layout.', 'wpforms' ),
			]
		);

		$inputs = '';

		foreach ( $presets as $preset ) {

			$inputs .= sprintf(
				'<input type="radio" name="fields[%1$d][preset]" id="wpforms-field-option-%1$d-preset-%2$s" value="%2$s" %3$s><label for="wpforms-field-option-%1$d-preset-%2$s" class="preset-%2$s"></label>',
				(int) $field['id'],
				esc_attr( $preset ),
				$field['preset'] === $preset ? 'checked' : ''
			);
		}

		$this->field_obj->field_element(
			'row',
			$field,
			[
				'slug'    => 'preset',
				'content' => $inputs,
				'class'   => $display === 'columns' ? '' : 'wpforms-layout-display-rows',
			]
		);
	}

	/**
	 * Display layout field preview inside the builder.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_preview( $field ) {

		// Label.
		$this->field_obj->field_preview_option( 'label', $field );

		// Description.
		$this->field_obj->field_preview_option( 'description', $field );

		// Columns.
		$columns       = isset( $field['columns'] ) && is_array( $field['columns'] ) ? $field['columns'] : $this->field_obj->defaults['columns'];
		$columns_class = ! empty( $field['display'] ) ? ' wpforms-layout-display-' . esc_attr( $field['display'] ) : '';
		$columns_html  = '';

		foreach ( $columns as $column ) {

			$preset_class = ! empty( $column['width_preset'] ) ? ' wpforms-layout-column-' . (int) $column['width_preset'] : '';
			$style_width  = ! empty( $column['width_custom'] ) ? ' style="width: ' . (int) $column['width_custom'] . '%;"' : '';

			$columns_html .= sprintf(
				'<div class="wpforms-layout-column%1$s" %2$s>%3$s</div>',
				esc_attr( $preset_class ),
				$style_width, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$this->field_preview_column_content( $column )
			);
		}

		$this->field_preview_columns_wrap( $columns_html, $columns_class, $field );
	}

	/**
	 * Output the Layout field preview columns wrapped by columns container.
	 *
	 * @since 1.8.9
	 *
	 * @param string $columns_html  Columns HTML.
	 * @param string $columns_class Columns container CSS class.
	 * @param array  $field         Field settings.
	 */
	protected function field_preview_columns_wrap( $columns_html, $columns_class, $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		printf(
			'<div class="wpforms-field-layout-columns%1$s">%2$s</div>',
			esc_attr( $columns_class ),
			$columns_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Generate layout field preview column content.
	 *
	 * @since 1.8.9
	 *
	 * @param array $column Column data.
	 *
	 * @return string Column content HTML.
	 */
	public function field_preview_column_content( $column ): string {

		$content = $this->get_field_preview_column_plus_placeholder_template();

		if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
			return $content;
		}

		$panel_fields_obj = WPForms_Builder_Panel_Fields::instance();

		ob_start();

		foreach ( $column['fields'] as $field_id ) {

			if ( empty( $panel_fields_obj->form_data['fields'][ $field_id ] ) ) {
				continue;
			}

			$panel_fields_obj->preview_single_field( $panel_fields_obj->form_data['fields'][ $field_id ], [] );
		}

		$content .= ob_get_clean();

		return $content;
	}

	/**
	 * Process layout fields data before saving the form.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form Form array which is usable with `wp_update_post()`.
	 * @param array $data Data retrieved from $_POST and processed.
	 * @param array $args Empty by default. May contain custom data not intended to be saved, but used for processing.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function save_form_args( $form, $data, $args ): array {

		$form = (array) $form;

		// Get a filtered form content.
		$form_data = json_decode( stripslashes( $form['post_content'] ), true );

		if ( empty( $form_data['fields'] ) || empty( $args['context'] ) || $args['context'] !== 'save_form' ) {
			return $form;
		}

		foreach ( (array) $form_data['fields'] as $id => $field ) {

			// Process only Layout-based fields.
			if ( empty( $field['type'] ) || $field['type'] !== $this->field_obj->type ) {
				continue;
			}

			// Decode columns data from JSON.
			if ( isset( $field['columns-json'] ) ) {
				$field['columns'] = $this->decode_columns_json( $field['columns-json'] );

				// Do not need to store JSON.
				unset( $field['columns-json'] );
			}

			// Set defaults to some field options.
			// For example, we don't have a Label option in the Form Builder.
			$form_data['fields'][ $id ] = wp_parse_args( $field, $this->field_obj->defaults );
		}

		$form['post_content'] = wpforms_encode( $form_data );

		return $form;
	}

	/**
	 * Decode columns JSON data.
	 *
	 * @since 1.8.9
	 *
	 * @param string $json Columns JSON data.
	 *
	 * @return array
	 */
	private function decode_columns_json( string $json ): array {

		$columns = (array) json_decode( $json, true );

		// Ensure that each column has the `fields` array.
		foreach ( $columns as $c => $column ) {
			$columns[ $c ]['fields'] = $column['fields'] ?? [];
		}

		return $columns;
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
	 * @noinspection HtmlUnknownTarget
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function get_localized_strings( $strings, $form ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$legacy_layout_notice_text = sprintf(
			wp_kses( /* translators: %1$s - WPForms.com URL to a related doc. */
				__( 'We’ve added a new field to help you build advanced form layouts more easily. Give the Layout Field a try! Layout CSS classes are still supported. <a href="%1$s" target="_blank" rel="noopener noreferrer">Learn More</a>', 'wpforms' ),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'rel'    => [],
					],
				]
			),
			esc_url(
				wpforms_utm_link( 'https://wpforms.com/docs/how-to-use-the-layout-field-in-wpforms/', 'Field Options', 'How to Use the Layout Field Documentation' )
			)
		);

		$strings['layout'] = [
			'not_allowed_fields'         => $this->field_obj->get_not_allowed_fields(),
			/* translators: %s - field name. */
			'not_allowed_alert_text'     => esc_html__( 'The %s field can’t be placed inside a Layout field.', 'wpforms' ),
			'empty_label'                => esc_html__( 'Layout', 'wpforms' ),
			'got_it'                     => esc_html__( 'Got it!', 'wpforms' ),
			'size_notice_text'           => esc_html__( 'Field size cannot be changed when used in a layout.', 'wpforms' ),
			'size_notice_tooltip'        => esc_html__( 'When a field is placed inside a column, the field size always equals the column width.', 'wpforms' ),
			'dont_show_again'            => esc_html__( 'Don’t Show Again', 'wpforms' ),
			'legacy_layout_notice_title' => esc_html__( 'Layouts Have Moved!', 'wpforms' ),
			'legacy_layout_notice_text'  => $legacy_layout_notice_text,
			'enabled_cf_alert_text'      => esc_html__( 'Conversational Forms cannot be enabled because your form contains a Layout field.', 'wpforms' ),
			'field_add_cf_alert_text'    => esc_html__( 'The Layout field cannot be used when Conversational Forms is enabled.', 'wpforms' ),
			'delete_confirm'             => esc_html__( 'Are you sure you want to delete the Layout field? Deleting this field will also delete the fields inside it.', 'wpforms' ),
			'cl_notice_text'             => esc_html__( 'Cannot be enabled because the Layout field contains Conditional Logic.', 'wpforms' ),
			'cl_notice_text_grp'         => esc_html__( 'Conditional Logic has been disabled because the Layout field contains Conditional Logic.', 'wpforms' ),
		];

		return $strings;
	}

	/**
	 * Get template for the column "plus" placeholder.
	 *
	 * @since 1.8.9
	 *
	 * @return string
	 */
	private function get_field_preview_column_plus_placeholder_template(): string {

		return sprintf(
			'<div class="wpforms-layout-column-placeholder">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="14" viewBox="0 0 12 14" fill="none">
					<path id="fa-caret-square-o-up" d="M11.25 14H0.75C0.3125 14 0 13.6875 0 13.25V13.5C0 13.0938 0.3125 12.75 0.75 12.75H11.25C11.6562 12.75 12 13.0938 12 13.5V13.25C12 13.6875 11.6562 14 11.25 14ZM4 0.75C4 0.34375 4.3125 0 4.75 0H7.25C7.65625 0 8 0.34375 8 0.75V5H10.7188C11.2812 5 11.5625 5.6875 11.1562 6.09375L6.40625 10.8438C6.1875 11.0625 5.78125 11.0625 5.5625 10.8438L0.8125 6.09375C0.40625 5.6875 0.6875 5 1.25 5H4V0.75Z" fill="#A6A6A6" class="wpforms-plus-path"/>
				</svg>
				<span>%1$s</span>
			</div>',
			esc_html__( 'Add Fields', 'wpforms' )
		);
	}

	/**
	 * Output template for the column "plus" placeholder.
	 *
	 * @since 1.8.9
	 */
	public function field_preview_column_plus_placeholder_template() {

		?>
		<script type="text/html" id="tmpl-wpforms-layout-field-column-plus-placeholder-template">
			<?php echo $this->get_field_preview_column_plus_placeholder_template(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</script>
		<?php
	}
}
