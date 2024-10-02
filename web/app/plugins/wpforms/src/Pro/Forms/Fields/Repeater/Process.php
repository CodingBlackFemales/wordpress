<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;
use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

/**
 * Repeater field's Process class.
 *
 * @since 1.8.9
 */
class Process {
	/**
	 * Initialize.
	 *
	 * @since 1.8.9
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {
		// Form data: before save entry.
		add_filter( 'wpforms_process_before_form_data', [ $this, 'prepare_form_data' ], 10, 2 );

		// Form data: entry edit setup.
		add_filter( 'wpforms_pro_admin_entries_edit_form_data', [ $this, 'add_repeater_child_fields_to_form_data' ], 10, 2 );
		add_filter( 'wpforms_pro_admin_entries_edit_form_data', [ $this, 'move_child_fields_to_repeater_field' ], 20 );

		// Form data: entry edit process.
		add_filter( 'wpforms_pro_admin_entries_edit_process_before_form_data', [ $this, 'entries_edit_process_before_form_data' ], 10, 3 );

		// Entry view page.
		add_filter( 'wpforms_entry_single_data', [ $this, 'prepare_entry_data' ], 1010, 3 );
		add_filter( 'wpforms_entries_single_form_data', [ $this, 'add_repeater_child_fields_to_form_data' ], 10, 2 );
		add_filter( 'wpforms_entries_single_details_form_data', [ $this, 'add_repeater_child_fields_to_form_data' ], 10, 2 );

		// Entry preview field.
		add_filter( 'wpforms_entry_preview_form_data', [ $this, 'prepare_form_data' ], 10, 2 );

		// Form data: entry print.
		add_filter( 'wpforms_pro_admin_entries_print_preview_form_data', [ $this, 'add_repeater_child_fields_to_form_data' ], 10, 2 );
		add_filter( 'wpforms_pro_admin_entries_print_preview_form_data', [ $this, 'populate_all_conditional_settings' ], 20, 2 );

		// Export.
		add_filter( 'wpforms_pro_admin_entries_export_ajax_form_data', [ $this, 'add_all_repeater_child_fields_to_form_data' ], 10, 2 );
		add_filter( 'wpforms_pro_admin_entries_export_ajax_form_data', [ $this, 'move_child_fields_to_repeater_field' ], 20 );

		// Form data: payment entry.
		add_filter( 'wpforms_admin_payments_views_single_form_data', [ $this, 'add_repeater_child_fields_to_form_data' ], 10, 2 );
		add_filter( 'wpforms_admin_payments_views_single_form_data', [ $this, 'move_child_fields_to_repeater_field' ], 20 );
	}

	/**
	 * The form data pre-processing.
	 *
	 * @since        1.8.9
	 *
	 * @param array|mixed $form_data       Form data.
	 * @param array       $submitted_entry Submitted entry data.
	 * @param object      $saved_entry     Saved entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function entries_edit_process_before_form_data( $form_data, $submitted_entry, $saved_entry ): array {

		$form_data = (array) $form_data;

		return $this->add_repeater_child_fields_to_form_data( $form_data, $saved_entry );
	}

	/**
	 * The form data pre-processing.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed  $form_data Form data.
	 * @param array|object $entry     Entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function prepare_form_data( $form_data, $entry ): array {

		$form_data = (array) $form_data;

		$form_data = $this->add_repeater_child_fields_to_form_data( $form_data, $entry );
		$form_data = $this->populate_all_conditional_settings( $form_data, $entry );
		$form_data = $this->move_child_fields_to_repeater_field( $form_data );

		return $this->remove_rows_out_of_limit( $form_data );
	}

	/**
	 * The entry data pre-processing.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $fields    Form fields.
	 * @param object      $entry     Entry fields.
	 * @param array       $form_data Form data.
	 *
	 * @return array
	 */
	public function prepare_entry_data( $fields, $entry, array $form_data ): array {

		$fields = (array) $fields;
		$fields = $this->add_missing_fields_to_entry( $fields, $entry, $form_data );

		return $this->move_child_fields_to_repeater_field( $fields );
	}

	/**
	 * Add missing fields to entry data.
	 *
	 * It's needed to make sure all the fields are present in the entry data,
	 * otherwise blocks are not displayed correctly.
	 * This can happen when a field is added by addon, but then addon was deactivated.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $fields    Entry fields.
	 * @param object $entry     Entry data.
	 * @param array  $form_data Form data.
	 *
	 * @return array
	 */
	private function add_missing_fields_to_entry( array $fields, $entry, array $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$form_obj = wpforms()->obj( 'form' );

		if ( ! method_exists( $form_obj, 'get' ) ) {
			return $fields;
		}

		$form_id      = ( $form_data['id'] ?? $entry->form_id ) ?? 0;
		$db_form_data = $form_obj->get( $form_id, [ 'content_only' => true ] );
		$form_fields  = $db_form_data['fields'] ?? [];
		$repeaters    = RepeaterHelpers::get_repeater_fields( $form_fields );

		if ( empty( $form_fields ) ) {
			return $fields;
		}

		foreach ( $repeaters as $repeater_field ) {
			$repeater_clones = RepeaterHelpers::get_repeater_clones_from_fields( $repeater_field, $fields );
			$original_fields = RepeaterHelpers::get_repeater_original_field_ids( $repeater_field );

			foreach ( $original_fields as $original_field_id ) {
				$fields[ $original_field_id ] = $fields[ $original_field_id ] ??
					[
						'id'    => $original_field_id,
						'type'  => $form_fields[ $original_field_id ]['type'] ?? '',
						'name'  => $form_fields[ $original_field_id ]['label'] ?? '',
						'value' => '',
					];

				foreach ( $repeater_clones as $clone_index ) {
					$clone_field_id = $original_field_id . '_' . $clone_index;

					$fields[ $clone_field_id ] = $fields[ $clone_field_id ] ??
						[
							'id'    => $clone_field_id,
							'type'  => $fields[ $original_field_id ]['type'],
							'name'  => $fields[ $original_field_id ]['name'],
							'value' => '',
						];
				}
			}
		}

		return $fields;
	}

	/**
	 * Remove rows out of limit.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $form_data Form data.
	 *
	 * @return array
	 */
	public function remove_rows_out_of_limit( $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$form_data = (array) $form_data;

		foreach ( $form_data['fields'] as $field ) {
			if ( $field['type'] !== 'repeater' ) {
				continue;
			}

			$max_rows = $field['rows_limit_max'] ?? 10;
			$rows     = isset( $field['columns'] ) && is_array( $field['columns'] ) ? LayoutHelpers::get_row_data( $field ) : [];

			if ( empty( $rows ) ) {
				continue;
			}

			if ( $field['display'] === 'blocks' ) {
				$blocks = array_chunk( $rows, RepeaterHelpers::get_repeater_chunk_size( $field ) );

				$this->remove_blocks( array_slice( $blocks, $max_rows ), $form_data );
			} else {
				$this->remove_rows( array_slice( $rows, $max_rows ), $form_data );
			}
		}

		return $form_data;
	}

	/**
	 * Remove blocks out of limit.
	 *
	 * @since 1.8.9
	 *
	 * @param array $blocks    Blocks data.
	 * @param array $form_data Form data.
	 */
	private function remove_blocks( array $blocks, array &$form_data ) {

		foreach ( $blocks as $rows ) {
			$this->remove_rows( $rows, $form_data );
		}
	}

	/**
	 * Remove rows out of limit.
	 *
	 * @since 1.8.9
	 *
	 * @param array $rows      Rows data.
	 * @param array $form_data Form data.
	 */
	private function remove_rows( array $rows, array &$form_data ) {

		foreach ( $rows as $row ) {
			foreach ( $row as $field_data ) {
				unset( $form_data['fields'][ $field_data['field'] ] );
			}
		}
	}

	/**
	 * Move child fields to the repeater.
	 *
	 * @since 1.8.9
	 *
	 * @param array|mixed $data Form data.
	 *
	 * @return array
	 */
	public function move_child_fields_to_repeater_field( $data ): array { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh

		$data   = (array) $data;
		$fields = (array) ( $data['fields'] ?? $data );

		foreach ( $fields as $field_id => $field_data ) {
			if ( ! wpforms_is_repeater_child_field( $field_id ) ) {
				continue;
			}

			$ids = wpforms_get_repeater_field_ids( $field_id );

			foreach ( $fields as $layout_field_id => $layout_field_data ) {
				if ( $layout_field_data['type'] === 'repeater' ) {
					foreach ( $layout_field_data['columns'] as $column_key => $column ) {
						if ( in_array( $ids['original_id'], $column['fields'], false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
							$fields[ $layout_field_id ]['columns'][ $column_key ]['fields'][ $field_id ] = $field_id;
						}
					}
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
	 * Add child fields to form data.
	 *
	 * @since        1.8.9
	 *
	 * @param array|mixed  $form_data Form data.
	 * @param array|object $entry     Entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function add_repeater_child_fields_to_form_data( $form_data, $entry ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$form_data   = (array) $form_data;
		$form_fields = $form_data['fields'] ?? [];

		if ( is_array( $entry ) ) {
			$fields = $entry['fields'] ?? $entry;
		} else {
			$fields = wpforms_decode( $entry->fields );
		}

		$repeater_fields = RepeaterHelpers::get_repeater_fields( $form_fields );

		foreach ( $repeater_fields as $repeater_id => $repeater_field ) {
			$repeater_field  = RepeaterHelpers::normalize_repeater_setting( $repeater_field );
			$original_fields = RepeaterHelpers::get_repeater_original_field_ids( $repeater_field );

			// Update Repeater field in the form data.
			$form_fields[ $repeater_id ] = $repeater_field;

			if ( isset( $fields[ $repeater_id ]['clone_list'] ) ) {
				$clone_list = json_decode( $fields[ $repeater_id ]['clone_list'] ) ?? [];
			} else {
				$form_fields = $this->populate_cloned_fields_by_entry_fields( $form_fields, $original_fields, $fields );
				$clone_list  = RepeaterHelpers::get_repeater_clones_from_fields( $repeater_field, $form_fields );
			}

			// Make sure the clone list doesn't contain more than the maximum allowed clones.
			$clone_list = array_slice( $clone_list, 0, absint( $repeater_field['rows_limit_max'] ?? Field::DEFAULT_ROWS_LIMIT_MAX ) );

			// Populate skipped cloned fields, like Content or HTML fields.
			$form_fields = $this->populate_cloned_fields_by_clone_list( $form_fields, $original_fields, $clone_list );
		}

		$form_data['fields'] = $form_fields;

		return $form_data;
	}

	/**
	 * Fill in the form fields with the cloned fields by clone list.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_fields     Form fields.
	 * @param array $original_fields Original field IDs.
	 * @param array $clone_list      Clones list.
	 *
	 * @return array
	 */
	private function populate_cloned_fields_by_clone_list( array $form_fields, array $original_fields, array $clone_list ): array {

		foreach ( $clone_list as $clone_num ) {
			foreach ( $original_fields as $original_field_id ) {
				$cloned_field_id = $original_field_id . '_' . $clone_num;

				if ( isset( $form_fields[ $original_field_id ] ) && ! isset( $form_fields[ $cloned_field_id ] ) ) {
					$form_fields[ $cloned_field_id ]       = $form_fields[ $original_field_id ];
					$form_fields[ $cloned_field_id ]['id'] = $cloned_field_id;
				}
			}
		}

		return $form_fields;
	}

	/**
	 * Fill in the form fields with the cloned fields by entry fields.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_fields     Form fields.
	 * @param array $original_fields Original field IDs.
	 * @param array $entry_fields    Entry fields.
	 *
	 * @return array
	 */
	private function populate_cloned_fields_by_entry_fields( array $form_fields, array $original_fields, array $entry_fields ): array {

		foreach ( $entry_fields as $key => $field_value ) {
			if ( ! wpforms_is_repeater_child_field( $key ) ) {
				continue;
			}

			$ids = wpforms_get_repeater_field_ids( $key );

			if ( ! in_array( $ids['original_id'], $original_fields, false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
				continue;
			}

			if ( isset( $form_fields[ $ids['original_id'] ] ) ) {
				$form_fields[ $key ]       = $form_fields[ $ids['original_id'] ];
				$form_fields[ $key ]['id'] = $key;
			}
		}

		return $form_fields;
	}

	/**
	 * Populate all the Repeater fields' conditional settings to child fields.
	 * Will be used as a hook, so we should keep it public.
	 *
	 * @since 1.8.9
	 * @deprecated 1.9.0
	 *
	 * @param array|mixed  $form_data Form data.
	 * @param array|object $entry     Entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function populate_repeaters_conditional_settings( $form_data, $entry ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		_deprecated_function( __METHOD__, '1.9.0 of the WPForms plugin', '\WPForms\Pro\Forms\Fields\Repeater\Process::populate_all_conditional_settings' );

		$form_data = (array) $form_data;
		$fields    = $form_data['fields'] ?? [];

		foreach ( $fields as $field_id => $field ) {
			$form_data = $this->populate_field_conditional_settings( $form_data, $field_id, 'repeater' );
		}

		return $form_data;
	}

	/**
	 * Populate all the layout and repeater fields' conditional settings to child fields.
	 * Will be used as a hook, so we should keep it public.
	 *
	 * @since 1.9.0
	 *
	 * @param array|mixed  $form_data Form data.
	 * @param array|object $entry     Entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function populate_all_conditional_settings( $form_data, $entry ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$form_data = (array) $form_data;
		$fields    = $form_data['fields'] ?? [];

		foreach ( $fields as $field_id => $field ) {
			$form_data = $this->populate_field_conditional_settings( $form_data, $field_id, 'layout' );
			$form_data = $this->populate_field_conditional_settings( $form_data, $field_id, 'repeater' );
		}

		return $form_data;
	}

	/**
	 * Populate the field conditional settings to child fields.
	 * This is needed to process Conditional Logic on every child field and their clones on backend.
	 *
	 * @since 1.9.0
	 *
	 * @param array      $form_data  Form data.
	 * @param int|string $field_id   Field ID.
	 * @param string     $field_type Field type ('layout' or 'repeater').
	 *
	 * @return array
	 */
	private function populate_field_conditional_settings( array $form_data, $field_id, string $field_type ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$field = $form_data['fields'][ $field_id ] ?? [];

		// Continue only for the specified field type.
		if ( $field_type !== ( $field['type'] ?? '' ) ) {
			return $form_data;
		}

		// The field's conditional logic settings.
		$conditional_logic     = $field['conditional_logic'] ?? '';
		$conditional_type      = $field['conditional_type'] ?? '';
		$conditionals          = $field['conditionals'] ?? [];
		$has_conditional_logic = ! empty( $conditional_logic ) && ! empty( $conditionals );

		if ( ! $has_conditional_logic ) {
			return $form_data;
		}

		// All the child fields in a flat list.
		$child_fields = array_merge( ...wp_list_pluck( $field['columns'] ?? [], 'fields' ) );

		// Populate CL settings to all child fields.
		foreach ( $child_fields as $child_id ) {
			foreach ( array_keys( $form_data['fields'] ) as $form_field_id ) {
				if ( $child_id !== $form_field_id && ( $field_type !== 'repeater' || strpos( $form_field_id, $child_id . '_' ) !== 0 ) ) {
					continue;
				}

				$form_data['fields'][ $form_field_id ]['conditional_logic'] = $conditional_logic;
				$form_data['fields'][ $form_field_id ]['conditional_type']  = $conditional_type;
				$form_data['fields'][ $form_field_id ]['conditionals']      = $conditionals;
				$form_data['conditional_fields'][]                          = $form_field_id;
			}
		}

		return $form_data;
	}

	/**
	 * Add all repeater child fields to form data.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 *
	 * @return array
	 */
	public function add_all_repeater_child_fields_to_form_data( $form_data, $entry_id ): array {

		$form_data = (array) $form_data;
		$entry_obj = wpforms()->obj( 'entry' );

		if ( $entry_id ) {
			$entry = $entry_obj->get( $entry_id );
		} else {
			$entry = $entry_obj->get_entries( [ 'form_id' => $form_data['id'] ] );
		}

		$entry = is_array( $entry ) ? $entry : [ $entry ];

		foreach ( $entry as $item ) {
			$form_data = $this->add_repeater_child_fields_to_form_data( $form_data, $item );
		}

		return $form_data;
	}
}
