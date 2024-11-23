<?php

namespace WPForms\Pro\Integrations\AI\API;

use WPForms\Integrations\AI\API\API;
use WPForms\Integrations\AI\Helpers;
use WPForms\Pro\Integrations\AI\Admin\Ajax\Forms as FormsAjax;

/**
 * Form API class.
 *
 * @since 1.9.2
 */
class Forms extends API {

	const ENDPOINT = '/ai-forms';

	/**
	 * Get form from the API.
	 *
	 * @since 1.9.2
	 *
	 * @param string $prompt     Prompt to get the form.
	 * @param string $session_id Session ID.
	 *
	 * @return array
	 */
	public function form( string $prompt, string $session_id = '' ): array {

		$args = [
			'userPrompt' => $this->prepare_prompt( $prompt ),
			'limit'      => $this->get_limit(),
		];

		if ( ! empty( $session_id ) ) {
			$args['sessionId'] = $session_id;
		}

		// Add available addons to the request arguments.
		$args['addons'] = $this->get_available_addons();

		// Add GDPR setting to the request arguments.
		$args['gdpr'] = wpforms_setting( 'gdpr' );

		$response = $this->request->post( self::ENDPOINT, $args );

		if ( $response->has_errors() ) {
			$error_data = $response->get_error_data();

			Helpers::log_error( $response->get_log_message( $error_data ), self::ENDPOINT, $args );

			return $error_data;
		}

		return $this->normalize_form_data( $response->get_body() );
	}

	/**
     * Get available addons.
     *
     * @since 1.9.2
     *
     * @return array
     */
    private function get_available_addons(): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$addons_obj = wpforms()->obj( 'addons' );

		if ( ! $addons_obj ) {
			return [];
		}

		$addons = [];

		// Get available addons.
		foreach ( FormsAjax::FORM_GENERATOR_REQUIRED_ADDONS as $slug ) {
			$addon = $addons_obj->get_addon( $slug );

			if (
				empty( $addon ) || // Exceptional case when `addons.json` is not loaded.
				empty( $addon['clear_slug'] ) ||
				( isset( $addon['status'] ) && $addon['status'] !== 'active' )
			) {
				continue;
			}

			$addons[] = $addon['clear_slug'];
		}

		return $addons;
    }

	/**
	 * Normalize form data.
	 *
	 * @since 1.9.2
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private function normalize_form_data( array $form_data ): array {

		// Recursively normalize form data.
		$form_data = $this->normalize_form_data_recursive( $form_data );

		// Fix fields data.
		$form_data = $this->fix_fields_data( $form_data );

		// Notifications and confirmations arrays should be indexed from 1.
		if ( ! empty( $form_data['settings']['notifications'] ) ) {
			$form_data['settings']['notifications'] = array_combine(
				range( 1, count( $form_data['settings']['notifications'] ) ),
				array_values( $form_data['settings']['notifications'] )
			);
		}

		if ( ! empty( $form_data['settings']['confirmations'] ) ) {
			$form_data['settings']['confirmations'] = array_combine(
				range( 1, count( $form_data['settings']['confirmations'] ) ),
				array_values( $form_data['settings']['confirmations'] )
			);
		}

		return $form_data;
	}

	/**
	 * Normalize form data recursive.
	 *
	 * @since 1.9.2
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private function normalize_form_data_recursive( array $form_data ): array {

		foreach ( $form_data as $key => $value ) {
			if ( is_array( $value ) ) {
				$form_data[ $key ] = $this->normalize_form_data_recursive( $value );
			}

			// Convert `false` and `true` values to '0' and '1'.
			$form_data[ $key ] = $form_data[ $key ] === false ? '0' : $form_data[ $key ];
			$form_data[ $key ] = $form_data[ $key ] === true ? '1' : $form_data[ $key ];

			// Remove null values.
			if ( $form_data[ $key ] === null ) {
				unset( $form_data[ $key ] );
			}
		}

		return $form_data;
	}

	/**
	 * Fix fields' data.
	 *
	 * @since 1.9.2
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private function fix_fields_data( array $form_data ): array {

		$updated_fields_data = [];

		// Fix array keys. The key should be identical to `id`.
		foreach ( $form_data['fields'] as $field_data ) {
			$updated_fields_data[ (string) $field_data['id'] ] = $field_data;
		}

		$form_data['fields'] = $updated_fields_data;

		// Fix choice values and choices array indexes.
		foreach ( $form_data['fields'] as $id => $field_data ) {
			$form_data['fields'][ $id ] = $this->fix_choices( $field_data );
		}

		// Fix conditional logic rules.
		foreach ( $form_data['fields'] as $id => $field_data ) {
			$form_data['fields'][ $id ] = $this->fix_field_cl( $field_data, $form_data );
		}

		return $form_data;
	}

	/**
	 * Fix field's conditional logic rules.
	 *
	 * @since 1.9.2
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private function fix_field_cl( array $field, array $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( empty( $field['conditionals'] ) || empty( $field['conditional_logic'] ) ) {
			return $field;
		}

		// Loop groups.
		foreach ( $field['conditionals'] as $group_key => $group ) {

			// Loop rules.
			foreach ( $group as $rule_key => $rule ) {
				$choices = $form_data['fields'][ $rule['field'] ]['choices'] ?? [];

				// We only need to update rules for choice-based fields.
				if ( empty( $choices ) ) {
					continue;
				}

				// AI uses choice value, but we should use the index of the choice in the `choices` array.
				$field['conditionals'][ $group_key ][ $rule_key ]['value'] = $this->get_choice_index( $choices, $rule['value'] );

				// Continue if the operator is supported by the choice-based field.
				if ( in_array( $rule['operator'], [ '==', '!=', 'e', '!e' ], true ) ) {
					continue;
				}

				// Fix `operator` value for choice-based fields.
				$rule['operator'] = in_array( $rule['operator'], [ 'c', '^', '>', '<' ], true ) ? '==' : $rule['operator'];
				$rule['operator'] = in_array( $rule['operator'], [ '!c', '~' ], true ) ? '!=' : $rule['operator'];

				$field['conditionals'][ $group_key ][ $rule_key ]['operator'] = $rule['operator'];
			}
		}

		return $field;
	}

	/**
	 * Find choice index in the `choices` array.
	 *
	 * @since 1.9.2
	 *
	 * @param array  $choices Choices data.
	 * @param string $value   Value to find in choices.
	 *
	 * @return string|null
	 */
	private function get_choice_index( array $choices, string $value ) {

		$index = array_search( $value, array_column( $choices, 'value' ), true );

		if ( $index === false ) {
			$index = array_search( $value, array_column( $choices, 'label' ), true );
		}

		$choices_keys = array_keys( $choices );

		return $index === false ? null : $choices_keys[ $index ];
	}

	/**
	 * Fix choices.
	 *
	 * Remove unnecessary values from choices.
	 *
	 * @since 1.9.2
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	private function fix_choices( array $field ): array {

		if ( empty( $field['choices'] ) ) {
			return $field;
		}

		// Remove values from choices for non-payment fields.
		if ( ! in_array( $field['type'], [ 'payment-multiple', 'payment-checkbox', 'payment-select' ], true ) ) {
			// Remove values from choices.
			foreach ( $field['choices'] as $i => $choice ) {
				$field['choices'][ $i ]['value'] = '';
			}
		}

		$updated_choices = [];

		// Update array keys to start from 1.
		foreach ( $field['choices'] as $i => $choice ) {
			$updated_choices[ $i + 1 ] = $choice;
		}

		$field['choices'] = $updated_choices;

		return $field;
	}
}
