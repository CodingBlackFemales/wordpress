<?php

namespace WPForms\Pro\Forms\Fields\Layout;

use WPForms\Pro\Forms\Fields\Traits\Layout\Frontend as LayoutFrontendTrait;

/**
 * The Layout field's Frontend class.
 *
 * @since 1.8.9
 */
class Frontend {

	use LayoutFrontendTrait {
		hooks as trait_hooks;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		$this->trait_hooks();

		add_filter( "wpforms_field_properties_{$this->field_obj->type}", [ $this, 'field_properties' ], 10, 3 );
		add_action( 'wpforms_display_field_before', [ $this, 'field_label' ], 15, 2 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.0
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

		// Disable default label.
		$properties['label']['disabled'] = true;

		$properties['description']['position'] = 'before';

		return $properties;
	}

	/**
	 * Display the custom field label.
	 *
	 * @since 1.9.0
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
		echo $this->get_title( $field );
	}

	/**
	 * Get the title.
	 *
	 * @since 1.9.0
	 *
	 * @param array $field Field settings.
	 *
	 * @return string
	 */
	private function get_title( array $field ): string {

		if ( ! empty( $field['label_hide'] ) ) {
			return '';
		}

		if ( ! isset( $field['label'] ) || wpforms_is_empty_string( $field['label'] ) ) {
			return '';
		}

		return sprintf(
			'<h3 class="wpforms-field-label">
				%1$s
			</h3>',
			esc_html( $field['label'] )
		);
	}
}
