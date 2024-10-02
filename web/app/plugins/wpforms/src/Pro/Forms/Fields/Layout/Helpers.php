<?php

namespace WPForms\Pro\Forms\Fields\Layout;

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

/**
 * Class Helpers for Layout Field.
 *
 * @since 1.8.8
 */
class Helpers {

	/**
	 * Remove child fields after moving to layout field.
	 *
	 * @since 1.9.0
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public static function remove_fields_after_moving_to_layout_field( array $form_data ): array {

		$form_fields = $form_data['fields'] ?? [];

		$layout_fields = self::get_layout_fields( $form_fields );

		foreach ( $layout_fields as $layout_field ) {
			$fields = self::get_layout_all_field_ids( $layout_field );

			foreach ( $fields as $field_id ) {
				unset( $form_data['fields'][ $field_id ] );
			}
		}

		return $form_data;
	}

	/**
	 * Get all field IDs from the Layout field settings.
	 *
	 * @since 1.9.0
	 *
	 * @param array $layout_fields Layout field settings.
	 *
	 * @return array
	 */
	public static function get_layout_all_field_ids( array $layout_fields ): array {

		return array_merge( ...wp_list_pluck( $layout_fields['columns'] ?? [], 'fields' ) );
	}

	/**
	 * Get all layout fields from the form fields.
	 *
	 * @since 1.9.0
	 *
	 * @param array $form_fields Form fields.
	 *
	 * @return array
	 */
	public static function get_layout_fields( array $form_fields ): array {

		return array_filter(
			$form_fields,
			static function ( $field ) {

				return $field['type'] === 'layout';
			}
		);
	}

	/**
	 * Reorder fields within rows in the form data.
	 *
	 * @since 1.8.8
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public static function reorder_fields_within_rows( array $form_data ): array {

		if ( ! isset( $form_data['fields'] ) || ! is_array( $form_data['fields'] ) ) {
			return $form_data;
		}

		foreach ( $form_data['fields'] as $field_id => $field ) {
			if ( ! self::is_layout_based_field( $field['type'] ) ) {
				continue;
			}

			if ( $field['type'] === 'layout' && ( ! isset( $field['display'] ) || $field['display'] === 'columns' ) ) {
				continue;
			}

			$rows           = self::get_row_data( $field );
			$current_fields = self::get_current_fields( $rows, $form_data );
			$form_data      = self::reorder_fields( $field_id, $current_fields, $form_data );
		}

		return $form_data;
	}

	/**
	 * Check if the field type is layout-based.
	 *
	 * @since 1.9.0
	 *
	 * @param string $field_type Field type.
	 *
	 * @return bool
	 */
	public static function is_layout_based_field( string $field_type ): bool {

		return in_array( $field_type, [ 'layout', 'repeater' ], true );
	}

	/**
	 * Convert columns to rows.
	 *
	 * @since 1.8.8
	 *
	 * @param array $field_settings Field data.
	 *
	 * @return array
	 */
	public static function get_row_data( array $field_settings ): array {

		$rows = [];

		if ( ! self::is_layout_based_field( $field_settings['type'] ) ) {
			return $rows;
		}

		foreach ( $field_settings['columns'] as $column_index => $item ) {
			$row_index = 0;

			foreach ( $item['fields'] as $field ) {
				if ( wpforms_is_repeater_child_field( $field ) ) {
					continue;
				}

				$rows[ $row_index ][ $column_index ] = [
					'width_preset' => $item['width_preset'],
					'field'        => $field,
				];

				++$row_index;
			}
		}

		if ( $field_settings['type'] === 'repeater' ) {
			RepeaterHelpers::create_repeater_rows( $field_settings, $rows );
		}

		self::add_missing_columns_to_row( $field_settings, $rows );

		return $rows;
	}

	/**
	 * Check if the layout is empty.
	 *
	 * @since 1.9.1
	 *
	 * @param array $layout Layout data.
	 *
	 * @return bool
	 */
	public static function is_layout_empty( array $layout ): bool {

		if ( empty( $layout['columns'] ) ) {
			return true;
		}

		foreach ( $layout['columns'] as $column ) {
			if ( ! self::is_column_empty( $column ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the column is empty.
	 * Repeater field column has only one field.
	 *
	 * @since 1.9.0
	 *
	 * @param array $column Column data.
	 *
	 * @return bool
	 */
	public static function is_column_empty( array $column ): bool {

		if ( empty( $column['fields'] ) && empty( $column['field'] ) ) {
			return true;
		}

		$fields = isset( $column['field'] ) ? [ $column['field'] ] : $column['fields'];

		$non_empty_fields = array_filter(
			$fields,
			static function ( $field ) {

				return ! wpforms_is_empty_string( $field['value'] ?? '' );
			}
		);

		return ! $non_empty_fields;
	}

	/**
	 * Reorders fields within rows and reconstructs the form data array.
	 *
	 * @since 1.8.8
	 *
	 * @param int   $field_id       The ID of the layout field.
	 * @param array $current_fields The fields to be reordered within rows.
	 * @param array $form_data      The original form data.
	 *
	 * @return array
	 */
	private static function reorder_fields( int $field_id, array $current_fields, array $form_data ): array {

		$new_array = [];

		foreach ( $form_data['fields'] as $key => $value ) {
			$new_array[ $key ] = $value;

			if ( (string) $key === (string) $field_id ) {
				foreach ( $current_fields as $new_key => $new_value ) {
					$new_array[ $new_key ] = $new_value;
				}
			}
		}

		$form_data['fields'] = $new_array;

		return $form_data;
	}

	/**
	 * Retrieves and removes current fields from the form data.
	 *
	 * @since 1.8.8
	 *
	 * @param array $rows      The rows extracted from the layout field.
	 * @param array $form_data Reference to the original form data.
	 *
	 * @return array
	 */
	private static function get_current_fields( array $rows, array &$form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$current_fields = [];

		foreach ( $rows as $row ) {
			foreach ( $row as $column ) {
				if ( empty( $column['field'] ) ) {
					continue;
				}

				// Extract field id from the column data.
				$field_id = is_array( $column['field'] )
					? ( $column['field']['id'] ?? '' )
					: $column['field'];

				if ( ! isset( $form_data['fields'][ $field_id ] ) ) {
					continue;
				}

				$current_fields[ $field_id ] = $form_data['fields'][ $field_id ];

				unset( $form_data['fields'][ $field_id ] );
			}
		}

		return $current_fields;
	}

	/**
	 * Add missing columns to row.
	 *
	 * @since 1.8.8
	 *
	 * @param array $field_settings Columns data.
	 * @param array $rows           Rows data.
	 */
	private static function add_missing_columns_to_row( array $field_settings, array &$rows ) {

		$preset_values = explode( '-', $field_settings['preset'] );
		$columns_count = count( $preset_values );

		foreach ( $rows as $row_index => $row ) {
			if ( count( $row ) < $columns_count ) {
				self::add_missing_presets_to_row( $rows, $preset_values, $row_index );
			}
		}
	}

	/**
	 * Add missing width presets to row.
	 *
	 * @since 1.8.8
	 *
	 * @param array $rows          Rows data.
	 * @param array $preset_values Preset values.
	 * @param int   $row_index     Row index.
	 */
	private static function add_missing_presets_to_row( array &$rows, array $preset_values, int $row_index ) {

		foreach ( $preset_values as $preset_index => $preset ) {
			if ( ! isset( $rows[ $row_index ][ $preset_index ] ) ) {
				$rows[ $row_index ][ $preset_index ] = [
					'width_preset' => $preset,
					'field'        => '',
				];

				ksort( $rows[ $row_index ] );
			}
		}
	}
}
