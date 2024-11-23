<?php

namespace WPForms\Pro\Forms\Fields\Layout;

use WPForms\Pro\Forms\Fields\Traits\Layout\Builder as LayoutBuilderTrait;

/**
 * Layout field's Builder class.
 *
 * @since 1.7.7
 */
class Builder {

	use LayoutBuilderTrait {
		hooks as trait_hooks;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.7.7
	 */
	private function hooks() {

		$this->trait_hooks();

		add_filter( 'wpforms_field_new_class', [ $this, 'preview_field_new_class' ], 10, 2 );
	}

	/**
	 * Field options panel.
	 *
	 * @since 1.7.7
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {

		// Defaults.
		$display = $field['display'] ?? 'columns';

		$this->field_option_columns_json( $field );

		// Options open markup.
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
				'tooltip' => esc_html__( 'Enter text for the Layout field label. It will help identify your layout block inside the form builder, but will not be displayed in the form.', 'wpforms' ),
			]
		);

		$this->field_option_preset_selector( $field );

		$output = $this->field_obj->field_element(
			'label',
			$field,
			[
				'slug'  => 'display',
				'value' => esc_html__( 'Display', 'wpforms' ),
			],
			false
		);

		$output .= $this->field_obj->field_element(
			'select',
			$field,
			[
				'slug'    => 'display',
				'value'   => $display,
				'options' => [
					'rows'    => esc_html__( 'Rows - fields are ordered from left to right', 'wpforms' ),
					'columns' => esc_html__( 'Columns - fields are ordered from top to bottom', 'wpforms' ),
				],
			],
			false
		);

		$this->field_obj->field_element(
			'row',
			$field,
			[
				'slug'    => 'display',
				'content' => $output,
			]
		);

		// Options close markup.
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
	 * @since 1.9.0
	 *
	 * @param array $field Field settings.
	 */
	private function field_options_advanced( array $field ) {

		$this->field_obj->field_option( 'description', $field );
		$this->field_obj->field_option( 'label_hide', $field );
	}

	/**
	 * Get new field CSS class.
	 *
	 * @since 1.7.7
	 *
	 * @param string $css_class Preview new field CSS class.
	 * @param array  $field     Field data.
	 *
	 * @return string
	 */
	public function preview_field_new_class( $css_class, $field ): string {

		$css_class = (string) $css_class;

		if ( empty( $field['type'] ) || $field['type'] !== $this->field_obj->type ) {
			return $css_class;
		}

		return trim( $css_class . ' label_hide' );
	}
}
