<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Traits\Layout\Frontend as LayoutFrontendTrait;

/**
 * The Repeater field's Frontend class.
 *
 * @since 1.8.9
 */
class Frontend {

	use LayoutFrontendTrait {
		hooks as layout_hooks;
		enqueue_css as layout_enqueue_css;
		display_rows as layout_display_rows;
	}

	/**
	 * Current form data.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	private $form_data;

	/**
	 * Flag to reset field value to default before rendering.
	 *
	 * @since 1.8.9
	 *
	 * @var bool
	 */
	private $reset_field_value = false;

	/**
	 * Entry data to pre-populate cloned fields' values.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	private $populate_entry;

	/**
	 * Register hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		$this->layout_hooks();

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );
		add_filter( 'wpforms_frontend_form_data', [ $this, 'prepare_form_data' ], PHP_INT_MAX );
		add_filter( "wpforms_field_properties_{$this->field_obj->type}", [ $this, 'field_properties' ], 10, 3 );
		add_filter( 'wpforms_field_properties', [ $this, 'reset_field_value_to_default' ], PHP_INT_MAX, 3 );
		add_action( 'wpforms_display_field_before', [ $this, 'field_label' ], 15, 2 );
	}

	/**
	 * Excluded from the Entry Preview display.
	 *
	 * @since 1.8.9
	 * @deprecated 1.9.0
	 *
	 * @param bool  $exclude   Exclude the field.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function entry_preview_exclude_field( $exclude, $field, $form_data ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		_deprecated_function( __METHOD__, '1.9.0 of the WPForms plugin' );

		if ( $field['type'] === $this->field_obj->type ) {
			return true;
		}

		return (bool) $exclude;
	}

	/**
	 * Frontend CSS enqueues.
	 *
	 * @since 1.8.9
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_css( $forms ) {

		$this->layout_enqueue_css( $forms );

		if (
			! $this->frontend->assets_global() &&
			! wpforms_has_field_type( $this->field_obj->type, $forms, true )
		) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			$this->field_obj->style_handle,
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/repeater{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Frontend JS enqueues.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $forms Forms on the current page.
	 */
	public function enqueue_frontend_js( $forms ) {

		$forms = (array) $forms;

		if (
			! $this->frontend->assets_global() &&
			! wpforms_has_field_type( $this->field_obj->type, $forms, true )
		) {
			return;
		}

		$min       = wpforms_get_min_suffix();
		$in_footer = ! wpforms_is_frontend_js_header_force_load();

		wp_enqueue_script(
			$this->field_obj->style_handle,
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/repeater{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION,
			$in_footer
		);
	}

	/**
	 * Prepare frontend form data.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $form_data Form data and settings.
	 *
	 * @return array
	 */
	public function prepare_form_data( $form_data ): array {

		$form_data = (array) $form_data;

		/**
		 * Filters entry data to pre-populate cloned fields' values.
		 *
		 * @since 1.8.9
		 *
		 * @param array $entry     Entry data. Defaults to [].
		 * @param array $form_data Form data.
		 */
		$this->populate_entry = apply_filters( 'wpforms_pro_forms_fields_repeater_frontend_clones_populate_entry', [], $form_data );

		$process = wpforms()->obj( 'repeater_process' );

		if ( ! $process ) {
			return $form_data;
		}

		$this->form_data = $process->add_repeater_child_fields_to_form_data( $form_data, $this->populate_entry );
		$this->form_data = $process->move_child_fields_to_repeater_field( $this->form_data );

		return $form_data;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.8.9
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$display  = $field['display'] ?? $this->field_obj->defaults['display'];
		$preset   = $field['preset'] ?? $this->field_obj->defaults['preset'];
		$rows_min = (int) ( $field['rows_limit_min'] ?? $this->field_obj->defaults['rows_limit_min'] );
		$rows_max = (int) ( $field['rows_limit_max'] ?? $this->field_obj->defaults['rows_limit_max'] );

		$properties['container']['class'][]           = 'wpforms-field-repeater-display-' . $display;
		$properties['container']['data']['rows-min']  = $rows_min;
		$properties['container']['data']['rows-max']  = $rows_max;
		$properties['container']['data']['clone-num'] = $rows_min + 1;

		// Disable default label.
		$properties['label']['disabled'] = true;

		$properties['inputs']['primary']['class'][] = 'wpforms-field-repeater-display-' . $display;
		$properties['inputs']['primary']['class'][] = 'wpforms-field-repeater-preset-' . $preset;

		$properties['description']['position'] = 'before';

		return $properties;
	}

	/**
	 * Reset field value to default in properties.
	 *
	 * @since        1.8.9
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function reset_field_value_to_default( $properties, $field, $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$properties = (array) $properties;

		if ( ! $this->reset_field_value ) {
			return $properties;
		}

		foreach ( $properties['inputs'] as $key => $input ) {

			// Reset choice-based fields to default.
			if ( isset( $field['choices'] ) ) {
				$properties['inputs'][ $key ]['default'] = (bool) ( $field['choices'][ $key ]['default'] ?? false );

				// Image and icon choices have an additional class.
				if ( ! empty( $input['container']['class'] ) ) {
					$properties['inputs'][ $key ]['container']['class'] = $properties['inputs'][ $key ]['default'] ?
						array_merge( $input['container']['class'], [ 'wpforms-selected' ] ) :
						array_diff( $input['container']['class'], [ 'wpforms-selected' ] );
				}

				continue;
			}

			if ( $field['type'] === 'rating' ) {
				$properties['inputs'][ $key ]['rating']['default'] = '';

				continue;
			}

			// Some fields have 'default_value' instead of 'default'.
			if ( in_array( $field['type'], [ 'richtext', 'textarea', 'number-slider', 'hidden' ], true ) ) {
				$properties['inputs'][ $key ]['attr']['value'] = $field['default_value'] ?? '';

				continue;
			}

			if ( isset( $input['attr']['value'] ) ) {
				$properties['inputs'][ $key ]['attr']['value'] = $field['default'] ?? '';
			}
		}

		return $properties;
	}

	/**
	 * Display rows layout.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	private function display_rows( array $field ) {

		$display              = $field['display'] ?? $this->field_obj->defaults['display'];
		$row_buttons          = $display === 'rows' ? $this->get_row_buttons( $field ) : '';
		$field                = $this->field_obj->remove_unsupported_child_fields( $field, (array) $this->form_data );
		$original_fields_html = $this->layout_display_rows( $field, false, $row_buttons );

		// If there are no rows, there is nothing to display.
		if ( empty( $original_fields_html ) ) {
			return;
		}

		$clone_tpl   = $this->get_clone_template( $field, $row_buttons );
		$clones_html = empty( $this->populate_entry ) ?
			$this->get_clones_html( $field, $clone_tpl ) :
			$this->get_populated_clones_html( $field );

		$clone_list = $this->get_clone_list_hidden_input( $field );

		$template_html = sprintf(
			'<script type="text/html" class="tmpl-wpforms-field-repeater-template-%1$d-%2$d">%3$s</script>',
			$field['id'] ?? 0,
			$this->field_obj->form_data['id'] ?? 0,
			$clone_tpl // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		$blocks_buttons = $display === 'blocks' ? $this->get_blocks_buttons( $field ) : '';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $clone_list . $template_html . $original_fields_html . $blocks_buttons . $clones_html;
	}

	/**
	 * Get clone template.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $field       Field settings.
	 * @param string $row_buttons Row buttons.
	 *
	 * @return string
	 */
	private function get_clone_template( array $field, string $row_buttons ): string {

		// Reset the field value to default before rendering the rows.
		$this->reset_field_value = true;
		$original_fields_html    = $this->layout_display_rows( $field, false, $row_buttons );
		$this->reset_field_value = false;

		/**
		 * Convert the rows' markup to template for further cloning in JS.
		 *
		 * The first pattern '/wpforms-([\d]+)-field_([\d]+)/' is looking for strings that start with "wpforms-",
		 * followed by one or more digits ([\d]+), followed by "-field_", and then followed by one or more digits.
		 * The parentheses are used to capture these digits for use in the replacement string.
		 * The replacement string for this pattern is 'wpforms-$1-field_$2{ROW}',
		 * where $1 and $2 are the digits captured by the first and second set of parentheses in the pattern.
		 *
		 * The second pattern '/wpforms\[fields\]\[([\d]+)\]/' is looking for strings that start with "wpforms[fields][",
		 * followed by one or more digits, and then followed by a closing bracket.
		 * The replacement string for this pattern is 'wpforms[fields][$1{ROW}]',
		 * where $1 is the digit captured by the parentheses in the pattern.
		 *
		 * The third pattern '/data-field-id="([\d]+)"/' is looking for strings that start with 'data-field-id="',
		 * followed by one or more digits, and then followed by a closing double quote.
		 * The replacement string for this pattern is 'data-field-id="$1_{CLONE}"',
		 * where $1 is the digit captured by the parentheses in the pattern.
		 */
		$clone_tpl = preg_replace(
			[
				'/wpforms-([\d]+)-field_([\d]+)/',
				'/wpforms\[fields\]\[([\d]+)\]/',
				'/data-field-id="([\d]+)"/',
			],
			[
				'wpforms-$1-field_$2_{CLONE}',
				'wpforms[fields][$1_{CLONE}]',
				'data-field-id="$1_{CLONE}"',
			],
			$original_fields_html
		);

		$display        = $field['display'] ?? $this->field_obj->defaults['display'];
		$block_title    = '';
		$block_descr    = '';
		$blocks_buttons = '';

		if ( $display === 'blocks' ) {
			$block_title    = $this->get_blocks_title( $field, '{CLONE}' );
			$block_descr    = $this->get_blocks_description( $field );
			$blocks_buttons = $this->get_blocks_buttons( $field );
		}

		return sprintf(
			'<div id="wpforms-%1$d-repeater-field_%2$d-clone_{CLONE}" class="wpforms-field-repeater-clone-wrap" data-clone="{CLONE}">
				%3$s%4$s%5$s%6$s
			</div>',
			(int) ( $this->field_obj->form_data['id'] ?? '0' ),
			(int) $field['id'],
			$block_title,
			$block_descr,
			$clone_tpl,
			$blocks_buttons
		);
	}

	/**
	 * Get the Add icon SVG code.
	 *
	 * @since 1.8.9.4
	 *
	 * @return string
	 */
	private function get_add_icon_svg(): string {

		return '<svg width="16" height="17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.129 7.984v1.032a.392.392 0 0 1-.387.387H8.903v2.839a.392.392 0 0 1-.387.387H7.484a.373.373 0 0 1-.387-.387V9.403H4.258a.373.373 0 0 1-.387-.387V7.984c0-.194.161-.387.387-.387h2.839V4.758c0-.193.161-.387.387-.387h1.032c.194 0 .387.194.387.387v2.839h2.839c.193 0 .387.193.387.387ZM16 8.5c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8 8 3.58 8 8Zm-1.548 0c0-3.548-2.904-6.452-6.452-6.452A6.45 6.45 0 0 0 1.548 8.5 6.43 6.43 0 0 0 8 14.952 6.45 6.45 0 0 0 14.452 8.5Z" fill="currentColor"/></svg>';
	}

	/**
	 * Get the Remove icon SVG code.
	 *
	 * @since 1.8.9.4
	 *
	 * @return string
	 */
	private function get_remove_icon_svg(): string {

		return '<svg width="16" height="17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.258 9.403a.373.373 0 0 1-.387-.387V7.984c0-.194.161-.387.387-.387h7.484c.193 0 .387.193.387.387v1.032a.392.392 0 0 1-.387.387H4.258ZM16 8.5c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8 8 3.58 8 8Zm-1.548 0c0-3.548-2.904-6.452-6.452-6.452A6.45 6.45 0 0 0 1.548 8.5 6.43 6.43 0 0 0 8 14.952 6.45 6.45 0 0 0 14.452 8.5Z" fill="currentColor"/></svg>';
	}

	/**
	 * Get the row buttons.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_row_buttons( array $field ): string {

		return sprintf(
			'<div class="wpforms-field-repeater-display-rows-buttons">
				<button type="button" class="wpforms-field-repeater-button-add" title="%1$s">
					%2$s
				</button>
				<button type="button" class="wpforms-field-repeater-button-remove wpforms-disabled" title="%3$s">
					%4$s
				</button>
			</div>',
			esc_attr( $field['button_add_label'] ?? $this->field_obj->defaults['button_add_label'] ),
			$this->get_add_icon_svg(),
			esc_attr( $field['button_remove_label'] ?? $this->field_obj->defaults['button_remove_label'] ),
			$this->get_remove_icon_svg()
		);
	}

	/**
	 * Get the blocks' buttons.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_blocks_buttons( array $field ): string {

		return sprintf(
			'<div class="wpforms-field-repeater-display-blocks-buttons" data-button-type="%1$s">
				<button type="button" class="wpforms-field-repeater-button-add">
					%2$s<span>%3$s</span>
				</button>
				<button type="button" class="wpforms-field-repeater-button-remove wpforms-disabled">
					%4$s<span>%5$s</span>
				</button>
			</div>',
			esc_attr( $field['button_type'] ?? $this->field_obj->defaults['button_type'] ),
			$this->get_add_icon_svg(),
			esc_html( $field['button_add_label'] ?? $this->field_obj->defaults['button_add_label'] ),
			$this->get_remove_icon_svg(),
			esc_html( $field['button_remove_label'] ?? $this->field_obj->defaults['button_remove_label'] )
		);
	}

	/**
	 * Get the block title.
	 *
	 * @since 1.8.9
	 *
	 * @param array      $field        Field settings.
	 * @param int|string $block_number Block number.
	 *
	 * @return string
	 */
	private function get_blocks_title( array $field, $block_number ): string {

		if ( ! empty( $field['label_hide'] ) ) {
			return '';
		}

		if ( ! isset( $field['label'] ) || wpforms_is_empty_string( $field['label'] ) ) {
			return '';
		}

		$block_num_str = ! empty( $block_number ) ? ' <span class="wpforms-wpforms-field-repeater-block-num">#' . $block_number . '</span>' : '';

		return sprintf(
			'<h3 class="%1$s">
				%2$s%3$s
			</h3>',
			$block_number !== '' ? 'wpforms-field-repeater-block-title' : 'wpforms-field-label',
			esc_html( $field['label'] ),
			$block_num_str
		);
	}

	/**
	 * Display the custom field label.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $field     Field data and settings.
	 * @param array       $form_data Form data and settings.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_label( $field, array $form_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$field = (array) $field;

		if ( ! isset( $field['type'] ) || $field['type'] !== $this->field_obj->type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->get_blocks_title( $field, '' );
	}

	/**
	 * Get the blocks' description.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_blocks_description( array $field ): string {

		return ! empty( $field['description'] ) ?
			'<div class="wpforms-field-description">' . do_shortcode( $field['description'] ) . '</div>' :
			'';
	}

	/**
	 * Get pre-generated clones HTML.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $field    Field settings.
	 * @param string $rows_tpl Rows template.
	 *
	 * @return string
	 */
	private function get_clones_html( array $field, string $rows_tpl ): string {

		$rows_num = $field['rows_limit_min'] ?? $this->field_obj->defaults['rows_limit_min'];

		if ( $rows_num < 2 ) {
			return '';
		}

		$clones_html = '';

		for ( $i = 2; $i <= $rows_num; $i++ ) {
			$clones_html .= str_replace( '{CLONE}', $i, $rows_tpl );
		}

		return $clones_html;
	}

	/**
	 * Get pre-populated clones HTML.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_populated_clones_html( array $field ): string { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Pass the form data with cloned fields to the field object.
		// This is needed to get the correct field settings for the cloned fields.
		$this->field_obj->form_data = $this->form_data;

		$repeater = $this->form_data['fields'][ $field['id'] ] ?? [];
		$blocks   = Helpers::get_blocks( $repeater, $this->field_obj->form_data );

		// Remove the first block as it's already displayed.
		array_shift( $blocks );

		if ( empty( $blocks ) ) {
			return '';
		}

		$display        = $field['display'] ?? $this->field_obj->defaults['display'];
		$row_buttons    = $display === 'rows' ? $this->get_row_buttons( $field ) : '';
		$block_descr    = '';
		$blocks_buttons = '';
		$clones_html    = '';

		if ( $display === 'blocks' ) {
			$block_descr    = $this->get_blocks_description( $field );
			$blocks_buttons = $this->get_blocks_buttons( $field );
		}

		foreach ( $blocks as $key => $rows ) {
			$block_num   = $key + 2;
			$block_title = $display === 'blocks' ? $this->get_blocks_title( $field, $block_num ) : '';
			$fields_html = $this->get_rows_html( $rows, $field, $row_buttons );
			$block_index = $this->get_populated_clone_index( $rows );

			$clones_html .= sprintf(
				'<div id="wpforms-%1$d-repeater-field_%2$d-clone_%3$s" class="wpforms-field-repeater-clone-wrap" data-clone="%3$s">
					%4$s%5$s%6$s%7$s
				</div>',
				(int) ( $this->form_data['id'] ?? '0' ),
				(int) $field['id'],
				$block_index,
				$block_title,
				$block_descr,
				$fields_html,
				$blocks_buttons
			);
		}

		return $clones_html;
	}

	/**
	 * Get the repeater field block index.
	 *
	 * @since 1.8.9
	 *
	 * @param array $rows Rows data.
	 *
	 * @return string
	 */
	private function get_populated_clone_index( array $rows ): string {

		// We actually should get the fields from the first row.
		// The indexes in all rows should be the same.
		if ( empty( $rows[0] ) ) {
			return '';
		}

		$field_id = '';

		foreach ( (array) $rows[0] as $column ) {
			if ( ! empty( $column['field'] ) ) {
				$field_id = $column['field'];

				break;
			}
		}

		$field_ids = wpforms_get_repeater_field_ids( $field_id );

		return $field_ids['index_id'] ?? '';
	}

	/**
	 * Get clone list hidden input.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_clone_list_hidden_input( array $field ): string {

		$rows_num = $field['rows_limit_min'] ?? $this->field_obj->defaults['rows_limit_min'];
		$range    = $rows_num > 1 ? range( 2, $rows_num ) : [];

		return sprintf(
			'<input type="hidden" class="wpforms-field-repeater-clone-list" name="wpforms[fields][%1$d][clone_list]" value="%2$s">',
			(int) $field['id'],
			wp_json_encode( $range )
		);
	}
}
