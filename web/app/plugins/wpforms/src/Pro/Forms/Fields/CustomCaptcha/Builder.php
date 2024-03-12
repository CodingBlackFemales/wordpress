<?php

namespace WPForms\Pro\Forms\Fields\CustomCaptcha;

/**
 * Custom Captcha field: builder handling class.
 *
 * @since 1.8.7
 */
class Builder {

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

		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_builder_enqueues' ] );

		// Set field as required by default.
		add_filter( 'wpforms_field_new_required', [ $this, 'field_default_required' ], 10, 2 );

		// Remove empty values before saving the form in Form Builder.
		add_filter( 'wpforms_save_form_args', [ $this, 'save_form' ], 11, 3 );

		// Do not display this field on the entry edit admin page.
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$type}", '__return_false' );
	}

	/**
	 * Enqueue for the admin form builder.
	 *
	 * @since 1.8.7
	 */
	public function admin_builder_enqueues() {

		$min = wpforms_get_min_suffix();

		// JavaScript.
		wp_enqueue_script(
			'wpforms-builder-custom-captcha',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/custom-captcha{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION
		);

		// Localize strings.
		wp_localize_script(
			'wpforms-builder-custom-captcha',
			'wpforms_builder_custom_captcha',
			[
				'error_not_empty_question' => esc_html__( 'Custom Captcha field should contain at least one not empty question.', 'wpforms' ),
			]
		);
	}

	/**
	 * Field should default to being required.
	 *
	 * @since 1.8.7
	 *
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function field_default_required( $required, $field ): bool {

		if ( $field['type'] === $this->field::TYPE ) {
			return true;
		}

		return $required;
	}

	/**
	 * Pre-process field data before saving it in form_data when editing form.
	 *
	 * @since 1.8.7
	 *
	 * @param array $form Form array, usable with wp_update_post.
	 * @param array $data Data retrieved from $_POST and processed.
	 * @param array $args Update form arguments.
	 *
	 * @return array
	 */
	public function save_form( $form, $data, $args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$form_data = json_decode( stripslashes( $form['post_content'] ), true );

		if ( empty( $form_data['fields'] ) ) {
			return $form;
		}

		foreach ( (array) $form_data['fields'] as $key => $field ) {

			if ( empty( $field['type'] ) || $field['type'] !== $this->field::TYPE ) {
				continue;
			}

			if ( $field['format'] !== 'qa' ) {
				continue;
			}

			$form_data['fields'][ $key ]['questions'] = ! empty( $form_data['fields'][ $key ]['questions'] ) ?
			$this->field->remove_empty_questions( $form_data['fields'][ $key ]['questions'] ) :
				[];
		}

		$form['post_content'] = wpforms_encode( $form_data );

		return $form;
	}
}
