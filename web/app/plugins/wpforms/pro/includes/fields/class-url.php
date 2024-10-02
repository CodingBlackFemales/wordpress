<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL text field.
 *
 * @since 1.0.0
 */
class WPForms_Field_URL extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Website / URL', 'wpforms' );
		$this->keywords = esc_html__( 'uri, link, hyperlink', 'wpforms' );
		$this->type     = 'url';
		$this->icon     = 'fa-link';
		$this->order    = 90;
		$this->group    = 'fancy';

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.0
	 */
	private function hooks() {

		add_filter( 'wpforms_html_field_value', [ $this, 'output_field_value' ], 10, 4 );
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 */
	public function field_options( $field ) {
		/*
		 * Basic field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'basic-options', $field, $args );

		// Label.
		$this->field_option( 'label', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'basic-options', $field, $args );

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'advanced-options', $field, $args );

		// Size.
		$this->field_option( 'size', $field );

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Default value.
		$this->field_option( 'default_value', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'advanced-options', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder   = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$default_value = ! empty( $field['default_value'] ) ? $field['default_value'] : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="url" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $default_value ) . '" class="primary-input" readonly>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<input type="url" %s %s>',
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Validate field on form submit.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted field value (raw data).
	 * @param array  $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$form_id = $form_data['id'];

		// Basic required check - If field is marked as required, check for entry data.
		if ( empty( $field_submit ) && ! empty( $form_data['fields'][ $field_id ]['required'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_get_required_label();
		}

		// Check that URL is in the valid format.
		if ( ! empty( $field_submit ) && ! wpforms_is_url( $field_submit ) ) {
			/**
			 * Filters the URL field error message.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message Error message.
			 */
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = apply_filters( 'wpforms_valid_url_label', esc_html__( 'Please enter a valid URL.', 'wpforms' ) ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		}
	}

	/**
	 * Format field.
	 *
	 * @since 1.5.8
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted value.
	 * @param array  $form_data    Form data.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		// Set field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '',
			'value' => esc_url_raw( $field_submit ),
			'id'    => wpforms_validate_field_id( $field_id ),
			'type'  => $this->type,
		];
	}

	/**
	 * Filter the field value on output.
	 *
	 * This is necessary to perform escaping of non-sanitized values before output.
	 *
	 * @since 1.9.0
	 *
	 * @param string|mixed $value     Field value.
	 * @param array        $field     Entry field data.
	 * @param array        $form_data Form data and settings.
	 * @param string       $context   Value display context.
	 *
	 * @return string|mixed
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function output_field_value( $value, array $field, array $form_data = [], string $context = '' ) {

		if ( $this->type !== ( $field['type'] ?? '' ) ) {
			return $value;
		}

		return esc_url( $field['value'] ?? $value );
	}
}

new WPForms_Field_URL();
