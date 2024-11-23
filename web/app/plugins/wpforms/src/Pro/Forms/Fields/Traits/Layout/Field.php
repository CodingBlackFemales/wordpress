<?php
/**
 * Suppress inspection on private properties `frontend_obj` and `builder_obj`.
 * They are used via getter `get_object()`.
 *
 * @noinspection PhpPropertyOnlyWrittenInspection
 */

namespace WPForms\Pro\Forms\Fields\Traits\Layout;

use WPForms\Pro\Forms\Fields\Layout\Helpers;

/**
 * Layout and Repeater Field trait.
 *
 * @since 1.8.9
 */
trait Field {

	/**
	 * Hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		add_filter( 'wpforms_field_new_default', [ $this, 'field_new_default' ] );
		add_filter( 'wpforms_entry_single_data', [ $this, 'filter_fields_remove_layout' ], 1000, 3 );

		add_filter( 'wpforms_pro_admin_entries_print_preview_fields', [ $this, 'filter_entries_print_preview_fields' ] );
		add_filter( 'wpforms_pro_admin_entries_edit_form_data', [ $this, 'filter_entries_print_preview_fields' ], 40 );
		add_filter( 'wpforms_entry_preview_fields', [ $this, 'filter_entries_print_preview_fields' ] );
		add_filter( 'wpforms_admin_payments_views_single_fields', [ $this, 'filter_entries_print_preview_fields' ] );
		add_filter( 'wpforms_pro_fields_entry_preview_print_entry_preview_exclude_field', [ $this, 'exclude_hidden_fields' ], 10, 3 );

		add_filter( 'register_block_type_args', [ $this, 'register_block_type_args' ], 20, 2 );
		add_filter( 'wpforms_conversational_form_detected', [ $this, 'cf_frontend_hooks' ], 10, 2 );
		add_filter( 'wpforms_field_properties_layout', [ $this, 'field_properties' ], 5, 3 );
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

		// Null 'for' value for label as there no input for it.
		unset( $properties['label']['attr']['for'] );

		return (array) $properties;
	}

	/**
	 * Initialize sub-objects.
	 *
	 * @since 1.8.9
	 */
	private function init_objects() {

		$is_ajax = wp_doing_ajax();

		if ( $is_ajax || wpforms_is_admin_page( 'builder' ) ) {
			$this->builder_obj = $this->get_object( 'Builder' );
		}

		if ( $is_ajax || ! is_admin() ) {
			$this->frontend_obj = $this->get_object( 'Frontend' );
		}
	}

	/**
	 * Define new field defaults.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 *
	 * @return array Field settings.
	 */
	public function field_new_default( $field ): array {

		if ( $this->type !== ( $field['type'] ?? '' ) ) {
			return (array) $field;
		}

		return wp_parse_args( $field, $this->defaults );
	}

	/**
	 * Get filtered presets.
	 *
	 * @since 1.8.9
	 *
	 * @return array Presets array.
	 */
	public function get_presets(): array {

		/**
		 * Filters the Layout or Repeater field's presets' list.
		 *
		 * @since 1.8.9
		 *
		 * @param array $presets An array of the layout field presets.
		 */
		return (array) apply_filters( "wpforms_field_{$this->type}_get_presets", self::PRESETS );
	}

	/**
	 * Get filtered not allowed fields' list.
	 *
	 * @since 1.8.9
	 *
	 * @return array Not allowed fields' list.
	 */
	public function get_not_allowed_fields(): array {

		/**
		 * Filters the Layout or Repeater field's list of the fields that not allowed to be placed inside the column.
		 *
		 * @since 1.8.9
		 *
		 * @param array $not_allowed_fields An array of the not allowed fields types.
		 */
		return (array) apply_filters( "wpforms_field_{$this->type}_get_not_allowed_fields", self::NOT_ALLOWED_FIELDS );
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {

		$this->get_object( 'Builder' )->field_options( $field );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {

		$this->get_object( 'Builder' )->field_preview( $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field      Field settings.
	 * @param array $deprecated Deprecated.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		$this->get_object( 'Frontend' )->field_display( $field, $form_data );
	}

	/**
	 * Filter base level fields.
	 *
	 * @since 1.8.9
	 *
	 * @param array $fields Form fields.
	 *
	 * @return array Form fields without the fields in the columns.
	 */
	public function filter_base_fields( $fields ): array {

		$fields_in_columns = [];

		foreach ( $fields as $field ) {

			if ( empty( $field['type'] ) || $field['type'] !== $this->type || empty( $field['columns'] ) ) {
				continue;
			}

			foreach ( $field['columns'] as $column ) {

				if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
					continue;
				}

				array_push( $fields_in_columns, ...$column['fields'] );
			}
		}

		foreach ( $fields_in_columns as $field_id ) {
			unset( $fields[ $field_id ] );
		}

		return $fields;
	}

	/**
	 * Filter fields data. Remove the Layout or Repeater fields from the fields' list.
	 *
	 * @since 1.8.9
	 *
	 * @param array $fields    Fields data.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form data.
	 *
	 * @return array Fields data without the layout fields.
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter_fields_remove_layout( $fields, $entry, $form_data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$fields = (array) $fields;

		if ( empty( $fields ) ) {
			return $fields;
		}

		foreach ( $fields as $id => $field ) {

			if ( empty( $field['type'] ) || $field['type'] !== $this->type ) {
				continue;
			}

			unset( $fields[ $id ] );
		}

		return $fields;
	}

	/**
	 * Filter fields data. Add the fields from the columns to the fields' list.
	 *
	 * @since 1.8.9
	 *
	 * @param array $data The field list OR column data.
	 *
	 * @return array
	 */
	public function filter_entries_print_preview_fields( $data ): array { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh

		$fields = $data['fields'] ?? $data;

		foreach ( $fields as $key => $field ) {
			if ( ! isset( $field['type'] ) || ! Helpers::is_layout_based_field( $field['type'] ) ) {
				continue;
			}

			if ( ! isset( $field['columns'] ) || ! is_array( $field['columns'] ) ) {
				continue;
			}

			foreach ( $field['columns'] as $column_index => $column ) {
				foreach ( $column['fields'] as $layout_field_index => $layout_field_id ) {
					if ( is_array( $layout_field_id ) ) {
						continue;
					}

					if ( empty( $fields[ $layout_field_id ] ) ) {
						unset( $fields[ $key ]['columns'][ $column_index ]['fields'][ $layout_field_index ] );
						continue;
					}

					$fields[ $key ]['columns'][ $column_index ]['fields'][ $layout_field_index ] = $fields[ $layout_field_id ];

					unset( $fields[ $layout_field_id ] );
				}
			}
		}

		if ( isset( $data['fields'] ) ) {
			$data['fields'] = $fields;

			return $data;
		}

		return $fields;
	}

	/**
	 * Exclude hidden fields from the entry preview field.
	 *
	 * @since 1.9.1
	 *
	 * @param bool  $hide      Hide the field.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function exclude_hidden_fields( $hide, array $field, array $form_data ): bool {

		$hide = (bool) $hide;

		if ( ! isset( $field['type'] ) || $field['type'] !== $this->type ) {
			return $hide;
		}

		if ( isset( $field['id'] ) && wpforms_conditional_logic_fields()->field_is_hidden( $form_data, $field['id'] ) ) {
			return true;
		}

		return $hide;
	}

	/**
	 * Hooks that must be registered only on the Conversational Forms Frontend page.
	 *
	 * @since 1.8.9
	 *
	 * @param array   $form_data Form data.
	 * @param integer $form_id   Form ID.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function cf_frontend_hooks( $form_data, $form_id ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// This filter is needed to remove the Layout fields from the CF frontend.
		add_filter( 'wpforms_frontend_form_data', [ $this, 'filter_fields_remove_layout_cf' ] );
	}

	/**
	 * Filter fields data. Remove the Layout and Repeater fields from the fields' list in CF.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array Fields data without the layout fields.
	 */
	public function filter_fields_remove_layout_cf( $form_data ): array {

		$form_data['fields'] = $this->filter_fields_remove_layout( $form_data['fields'], [], $form_data );

		return $form_data;
	}

	/**
	 * Load enqueues for the Gutenberg editor in WP version < 5.5.
	 *
	 * @since 1.8.9
	 * @deprecated 1.8.7
	 */
	public function gutenberg_enqueues() {

		_deprecated_function( __METHOD__, '1.8.7 of the WPForms plugin' );

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			$this->style_handle,
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/layout{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Set editor style handle for block type editor.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $args       Array of arguments for registering a block type.
	 * @param string $block_type Block type name including namespace.
	 */
	public function register_block_type_args( $args, $block_type ): array {

		$args = (array) $args;

		if ( $block_type !== 'wpforms/form-selector' || ! is_admin() ) {
			return $args;
		}

		$min = wpforms_get_min_suffix();

		// CSS.
		wp_register_style(
			$this->style_handle,
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/{$this->type}{$min}.css",
			[ $args['editor_style'] ],
			WPFORMS_VERSION
		);

		$args['editor_style'] = $this->style_handle;

		return $args;
	}
}
