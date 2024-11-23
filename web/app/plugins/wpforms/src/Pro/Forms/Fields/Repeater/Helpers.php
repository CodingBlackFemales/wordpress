<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

/**
 * Class Helpers to provide helper methods for the Repeater field.
 *
 * @since 1.8.9
 */
class Helpers {

	/**
	 * Normalize the Repeater field settings.
	 *
	 * @since 1.8.9
	 *
	 * @param array $repeater_field Repeater field settings.
	 *
	 * @return array
	 */
	public static function normalize_repeater_setting( array $repeater_field ): array {

		$repeater_field['columns'] = $repeater_field['columns'] ?? Field::DEFAULT_COLUMNS;

		foreach ( $repeater_field['columns'] as $key => $column ) {

			// Ensure that the column has the `fields` array.
			$repeater_field['columns'][ $key ]['fields'] = $column['fields'] ?? [];
		}

		return $repeater_field;
	}

	/**
	 * Remove child fields after moving to repeater field.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public static function remove_child_fields_after_moving_to_repeater_field( array $form_data ): array {

		$form_fields = $form_data['fields'] ?? [];

		$repeater_fields = self::get_repeater_fields( $form_fields );

		foreach ( $repeater_fields as $repeater_field ) {
			$fields = self::get_repeater_all_field_ids( $repeater_field );

			foreach ( $fields as $field_id ) {
				unset( $form_data['fields'][ $field_id ] );
			}
		}

		return $form_data;
	}

	/**
	 * Get all field IDs from the Repeater field settings.
	 *
	 * @since 1.8.9
	 *
	 * @param array $repeater_field Repeater field settings.
	 *
	 * @return array
	 */
	public static function get_repeater_all_field_ids( array $repeater_field ): array {

		return array_merge( ...wp_list_pluck( $repeater_field['columns'] ?? [], 'fields' ) );
	}

	/**
	 * Get all repeater fields from the form fields.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_fields Form fields.
	 *
	 * @return array
	 */
	public static function get_repeater_fields( array $form_fields ): array {

		return array_filter(
			$form_fields,
			static function ( $field ) {

				return $field['type'] === 'repeater';
			}
		);
	}

	/**
	 * Get the repeater field blocks.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public static function get_blocks( array $field, array $form_data ): array {

		$rows = isset( $field['columns'] ) && is_array( $field['columns'] ) ? LayoutHelpers::get_row_data( $field ) : [];

		if ( ! isset( $form_data['fields'][ $field['id'] ] ) || empty( $rows ) ) {
			return [];
		}

		$chunk_size = self::get_repeater_chunk_size( $form_data['fields'][ $field['id'] ] );

		if ( ! $chunk_size ) {
			return [];
		}

		return array_chunk( $rows, $chunk_size );
	}

	/**
	 * Get the number of repeater clones.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field_settings Field data.
	 *
	 * @return int
	 */
	public static function get_repeater_chunk_size( array $field_settings ): int {

		$max_fields_count = 0;

		foreach ( $field_settings['columns'] as $column ) {
			$fields_count  = 0;
			$column_fields = $column['fields'] ?? [];

			foreach ( $column_fields as $field ) {
				if ( ! wpforms_is_repeater_child_field( $field ) ) {
					++$fields_count;
				}
			}

			if ( $fields_count > $max_fields_count ) {
				$max_fields_count = $fields_count;
			}
		}

		return $max_fields_count;
	}

	/**
	 * Get the original field IDs from the Repeater field settings.
	 *
	 * @since 1.8.9
	 *
	 * @param array $repeater_field Repeater field settings.
	 *
	 * @return array
	 */
	public static function get_repeater_original_field_ids( array $repeater_field ): array {

		// Get all the inner field ids.
		$ids = self::get_repeater_all_field_ids( $repeater_field );

		// Filter out the child fields.
		foreach ( $ids as $key => $id ) {
			if ( wpforms_is_repeater_child_field( $id ) ) {
				unset( $ids[ $key ] );
			}
		}

		return $ids;
	}

	/**
	 * Get the repeater clones.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field_settings Field data.
	 *
	 * @return array
	 */
	private static function get_repeater_clones( array $field_settings ): array {

		$clones = [];

		foreach ( $field_settings['columns'] as $column ) {
			$column_fields = $column['fields'] ?? [];

			foreach ( $column_fields as $field ) {
				if ( wpforms_is_repeater_child_field( $field ) ) {
					$field_id = is_array( $field ) ? $field['id'] : $field;
					$ids      = wpforms_get_repeater_field_ids( $field_id );

					$clones[] = $ids['index_id'];
				}
			}
		}

		return array_unique( $clones );
	}

	/**
	 * Get the repeater clones.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field_settings Field data.
	 * @param array $fields         Form fields.
	 *
	 * @return array
	 */
	public static function get_repeater_clones_from_fields( array $field_settings, array $fields ): array {

		$original_fields = self::get_repeater_original_field_ids( $field_settings );
		$clones          = [];
		$field_ids       = array_keys( $fields );

		foreach ( $original_fields as $original_field_id ) {
			foreach ( $field_ids as $field_id ) {
				if ( ! wpforms_is_repeater_child_field( $field_id ) ) {
					continue;
				}

				$clone_ids = wpforms_get_repeater_field_ids( $field_id );

				if ( $original_field_id === (int) $clone_ids['original_id'] ) {
					$clones[] = $clone_ids['index_id'];
				}
			}
		}

		return array_unique( $clones );
	}

	/**
	 * Create repeater rows.
	 *
	 * @since 1.8.9
	 *
	 * @param array $columns Columns data.
	 * @param array $rows    Rows data.
	 */
	public static function create_repeater_rows( array $columns, array &$rows ) {

		$clones = self::get_repeater_clones( $columns );

		foreach ( $clones as $clone_id ) {
			$clone_rows = self::create_clone_rows( $columns, $rows, $clone_id );

			foreach ( $clone_rows as $clone_row ) {
				$rows[] = $clone_row;
			}
		}
	}

	/**
	 * Create clone rows.
	 *
	 * @since 1.8.9
	 *
	 * @param array $columns Columns data.
	 * @param array $rows    Rows data.
	 * @param int   $i       Clone index.
	 *
	 * @return array
	 */
	private static function create_clone_rows( array $columns, array $rows, int $i ): array {

		$temp = [];

		foreach ( $rows as $row_index => $row ) {
			foreach ( $row as $column ) {
				$original_id = is_array( $column['field'] ) ? $column['field']['id'] : $column['field'];

				self::create_clone_columns( $columns, $original_id, $i, $row_index, $temp );
			}
		}

		return $temp;
	}

	/**
	 * Create clone columns.
	 *
	 * @since 1.8.9
	 *
	 * @param array      $columns     Columns data.
	 * @param int|string $original_id Original field ID.
	 * @param int        $i           Clone index.
	 * @param int        $row_index   Row index.
	 * @param array      $temp        Temporary array.
	 */
	private static function create_clone_columns( array $columns, $original_id, int $i, int $row_index, array &$temp ) {

		foreach ( $columns['columns'] as $column_index => $column ) {
			$column_fields = $column['fields'] ?? [];

			foreach ( $column_fields as $field ) {
				if ( wpforms_is_repeater_child_field( $field ) ) {
					self::create_clone_field( $field, $original_id, $i, $row_index, $column_index, $column, $temp );
				}
			}
		}
	}

	/**
	 * Create clone field.
	 *
	 * @since 1.8.9
	 *
	 * @param int|string|array $field        Field data.
	 * @param int|string       $original_id  Original field ID.
	 * @param int              $i            Clone index.
	 * @param int              $row_index    Row index.
	 * @param int              $column_index Column index.
	 * @param array            $item         Item data.
	 * @param array            $temp         Temporary array.
	 */
	private static function create_clone_field( $field, $original_id, int $i, int $row_index, int $column_index, array $item, array &$temp ) {

		$ids = wpforms_get_repeater_field_ids( $field );

		if ( (string) $original_id === (string) $ids['original_id'] && (string) $i === (string) $ids['index_id'] ) {
			$temp[ $row_index ][ $column_index ] = [
				'width_preset' => $item['width_preset'],
				'field'        => $field,
			];
		}
	}

	/**
	 * Determine if the block has only empty fields.
	 *
	 * @since 1.9.1
	 *
	 * @param array $block Block settings.
	 *
	 * @return bool
	 */
	public static function is_empty_block( array $block ): bool {

		foreach ( $block as $rows ) {
			if ( ! LayoutHelpers::is_layout_empty( [ 'columns' => $rows ] ) ) {
				return false;
			}
		}

		return true;
	}
}
