<?php

namespace WPForms\Pro\Forms\Fields\CustomCaptcha;

/**
 * Custom Captcha field: frontend handling class.
 *
 * @since 1.8.7
 */
class Frontend {

	/**
	 * The main field class object.
	 *
	 * @since 1.8.7
	 *
	 * @var Field $field Field class object.
	 */
	private $field;

	/**
	 * Init class.
	 *
	 * @since 1.8.7
	 *
	 * @param object $field Field class object.
	 */
	public function init( $field ) {

		$this->field = $field;

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.7
	 */
	private function hooks() {

		$type = $this->field::TYPE;

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'frontend_js' ] );

		// Remove the field from saved data.
		add_filter( 'wpforms_process_after_filter', [ $this, 'process_remove_field' ], 10, 3 );

		// Define additional field properties.
		add_filter( "wpforms_field_properties_{$type}", [ $this, 'field_properties' ], 5, 3 );
	}

	/**
	 * Enqueue frontend field js.
	 *
	 * @since 1.8.7
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function frontend_js( $forms ) {

		if (
			wpforms_has_field_type( $this->field::TYPE, $forms, true ) === true ||
			wpforms()->get( 'frontend' )->assets_global()
		) {

			$min = wpforms_get_min_suffix();

			wp_enqueue_script(
				'wpforms-captcha',
				WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/custom-captcha{$min}.js",
				[ 'jquery', 'wpforms' ],
				WPFORMS_VERSION,
				true
			);

			$strings = [
				'max'      => $this->field->math['max'],
				'min'      => $this->field->math['min'],
				'cal'      => $this->field->math['cal'],
				'errorMsg' => esc_html__( 'Incorrect answer.', 'wpforms' ),
			];

			wp_localize_script( 'wpforms-captcha', 'wpforms_captcha', $strings );
		}
	}

	/**
	 * Don't store captcha fields since it's for validation only.
	 *
	 * @since 1.8.7
	 *
	 * @param array $fields    Field settings.
	 * @param array $entry     Form $_POST.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array
	 */
	public function process_remove_field( $fields, $entry, $form_data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		foreach ( $fields as $id => $field ) {
			// Remove captcha from saved data.
			if ( ! empty( $field['type'] ) && $field['type'] === $this->field::TYPE ) {
				unset( $fields[ $id ] );
			}
		}

		return $fields;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.8.7
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$field_id = absint( $field['id'] );
		$format   = ! empty( $field['format'] ) ? $field['format'] : 'math';

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "wpforms[fields][{$field_id}][a]";

		// Input Primary: adjust class.
		$properties['inputs']['primary']['class'][] = 'a';

		// Input Primary: type data attr.
		$properties['inputs']['primary']['data']['rule-wpf-captcha'] = $format;

		// Input Primary: mark this field as wrapped.
		$properties['inputs']['primary']['data']['is-wrapped-field'] = true;

		return $properties;
	}
}
