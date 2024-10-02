<?php

namespace WPForms\Pro\Forms\Fields\Traits\Layout;

use WPForms\Frontend\Frontend as WPFormsFrontend;
use WPForms\Pro\Forms\Fields\Layout\Helpers;
use WPForms\Pro\Forms\Fields\Layout\Field as LayoutField;
use WPForms\Pro\Forms\Fields\Repeater\Field as RepeaterField; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement

/**
 * The Layout and Repeater fields' Frontend trait.
 *
 * @since 1.8.9
 */
trait Frontend {

	/**
	 * Instance of the Field class.
	 *
	 * @since 1.8.9
	 *
	 * @var LayoutField|RepeaterField
	 */
	private $field_obj;

	/**
	 * Instance of the WPForms\Frontend\Frontend class.
	 *
	 * @since 1.8.9
	 *
	 * @var WPFormsFrontend
	 */
	private $frontend;

	/**
	 * Class constructor.
	 *
	 * @since 1.8.9
	 *
	 * @param LayoutField|RepeaterField $field_obj Instance of the Field class.
	 */
	public function __construct( $field_obj ) {

		$this->field_obj = $field_obj;
		$this->frontend  = wpforms()->obj( 'frontend' );

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_css' ] );
		add_filter( 'wpforms_frontend_fields_base_level', [ $this->field_obj, 'filter_base_fields' ] );
		add_filter( 'wpforms_process_after_filter', [ $this->field_obj, 'filter_fields_remove_layout' ], PHP_INT_MAX, 3 );
		add_filter( 'wpforms_frontend_output_form_is_empty', [ $this, 'filter_output_form_is_empty' ], 10, 2 );
	}

	/**
	 * Frontend CSS enqueues.
	 *
	 * @since 1.8.9
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_css( $forms ) {

		if (
			! $this->frontend->assets_global() &&
			! wpforms_has_field_type( [ 'layout', 'repeater' ], $forms, true )
		) {
			return;
		}

		$min = wpforms_get_min_suffix();

		/**
		 * Filter the breakpoint (as viewport width in pixels) for the layout and repeater fields.
		 *
		 * @since 1.9.1
		 *
		 * @param int   $viewport_breakpoint The viewport width in pixels.
		 * @param array $forms               Form data.
		 */
		$viewport_breakpoint = (int) apply_filters( 'wpforms_frontend_enqueue_css_layout_field_viewport_breakpoint', 600, $forms );

		wp_enqueue_style(
			'wpforms-layout',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/layout{$min}.css",
			[],
			WPFORMS_VERSION
		);

		wp_enqueue_style(
			'wpforms-layout-screen-big',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/layout-screen-big{$min}.css",
			[],
			WPFORMS_VERSION,
			sprintf( '(min-width: %dpx)', $viewport_breakpoint + 1 )
		);

		wp_enqueue_style(
			'wpforms-layout-screen-small',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/layout-screen-small{$min}.css",
			[],
			WPFORMS_VERSION,
			sprintf( '(max-width: %dpx)', $viewport_breakpoint )
		);
	}

	/**
	 * Display field on the front-end.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data and settings.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_display( array $field, array $form_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( isset( $field['display'] ) && ( $field['display'] === 'rows' || $field['display'] === 'blocks' ) ) {
			$this->display_rows( $field );
		} else {
			$this->display_columns( $field );
		}
	}

	/**
	 * Display `rows` layout.
	 *
	 * @since 1.8.8
	 *
	 * @param array  $field       Field settings.
	 * @param bool   $to_print    Whether to print the output or return it.
	 * @param string $row_buttons Row buttons HTML. Used in the Repeater field.
	 *
	 * @return string
	 */
	private function display_rows( array $field, bool $to_print = true, string $row_buttons = '' ): string {

		$rows = isset( $field['columns'] ) && is_array( $field['columns'] ) ? Helpers::get_row_data( $field ) : [];

		if ( empty( $rows ) ) {
			return '';
		}

		$rows_html = $this->get_rows_html( $rows, $field, $row_buttons );

		if ( $to_print ) {
			echo $rows_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return '';
		}

		return $rows_html;
	}

	/**
	 * Get `rows` layout HTML.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $rows        Rows data.
	 * @param array  $field       Field settings.
	 * @param string $row_buttons Row buttons HTML. Used in the Repeater field.
	 *
	 * @return string
	 */
	private function get_rows_html( array $rows, array $field, string $row_buttons = '' ): string {

		if ( empty( $rows ) ) {
			return '';
		}

		$rows_html = '';

		foreach ( $rows as $row ) {
			$rows_html .= sprintf(
				'<div class="wpforms-layout-row">%1$s%2$s</div>',
				$this->get_column_row_content( $row ),
				$row_buttons
			);
		}

		return sprintf(
			'<div class="wpforms-field-layout-rows %1$s">%2$s</div>',
			wpforms_sanitize_classes( $field['properties']['inputs']['primary']['class'] ?? '', true ),
			$rows_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Get column content HTML.
	 *
	 * @since 1.8.8
	 *
	 * @param array $row Row data.
	 *
	 * @return string
	 */
	public function get_column_row_content( array $row ): string {

		$form_data = $this->field_obj->form_data;

		// Bail if we don't have the column fields data for some reason.
		if ( empty( $form_data['fields'] ) || empty( $row ) ) {
			return '';
		}

		ob_start();

		foreach ( $row as $data ) {
			$field = $form_data['fields'][ $data['field'] ] ?? false;

			$column_preset_class = ' wpforms-layout-column-' . (int) $data['width_preset'];
			$style_width         = ! empty( $row['width_custom'] ) ? ' style="width: ' . (int) $row['width_custom'] . '%;"' : '';

			echo '<div class="wpforms-layout-column' . esc_attr( $column_preset_class ) . '"' . $style_width . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( $field ) {
				$this->frontend->render_field( $form_data, $field );
			}

			echo '</div>';
		}

		return ob_get_clean();
	}


	/**
	 * Display columns layout.
	 *
	 * @since 1.8.8
	 *
	 * @param array $field Field settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	private function display_columns( array $field ) {

		$columns_html = '';
		$columns      = isset( $field['columns'] ) && is_array( $field['columns'] ) ? $field['columns'] : [];
		$preset_class = $field['preset'] ?? $this->field_obj->defaults['preset'];

		foreach ( $columns as $column ) {

			$column_preset_class = ! empty( $column['width_preset'] ) ? ' wpforms-layout-column-' . (int) $column['width_preset'] : '';
			$style_width         = ! empty( $column['width_custom'] ) ? ' style="width: ' . (int) $column['width_custom'] . '%;"' : '';

			$columns_html .= sprintf(
				'<div class="wpforms-layout-column%1$s" %2$s>%3$s</div>',
				esc_attr( $column_preset_class ),
				$style_width,
				$this->get_column_content( $column )
			);
		}

		printf(
			'<div class="wpforms-field-layout-columns wpforms-field-layout-preset-%1$s">%2$s</div>',
			esc_attr( $preset_class ),
			$columns_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Get column content HTML.
	 *
	 * @since 1.8.9
	 *
	 * @param array $column Column data.
	 *
	 * @return string
	 */
	public function get_column_content( $column ): string {

		$form_data = $this->field_obj->form_data;

		// Bail if we don't have the column fields data for some reason.
		if ( empty( $form_data['fields'] ) || empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
			return '';
		}

		ob_start();

		foreach ( $column['fields'] as $field_id ) {

			$field = $form_data['fields'][ $field_id ] ?? false;

			if ( ! $field ) {
				continue;
			}

			$this->frontend->render_field( $form_data, $field );
		}

		return ob_get_clean();
	}

	/**
	 * Check if the form is empty before output to the frontend.
	 *
	 * @since 1.8.9
	 *
	 * @param bool  $form_is_empty True if the form is empty.
	 * @param array $form_data     Form data.
	 *
	 * @return bool
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	public function filter_output_form_is_empty( $form_is_empty, $form_data ): bool {

		if ( $form_is_empty || ! isset( $form_data['fields'] ) || ! is_array( $form_data['fields'] ) ) {
			return (bool) $form_is_empty;
		}

		$field_type = $this->field_obj->type;

		$layout_fields = wpforms_chain( wp_list_pluck( $form_data['fields'], 'type' ) )
			->array_filter(
				static function ( $type ) use ( $field_type ) {

					return $type === $field_type;
				}
			)
			->value();

		return count( $layout_fields ) === count( $form_data['fields'] );
	}
}
