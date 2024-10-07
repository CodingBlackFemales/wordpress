<?php
/**
 * Suppress inspection on private properties `frontend_obj` and `builder_obj`.
 * They are used via getter `get_object()`.
 *
 * @noinspection PhpPropertyOnlyWrittenInspection
 */

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Traits\Layout\Field as LayoutFieldTrait;
use WPForms_Field;

/**
 * Repeater field.
 *
 * @since 1.8.9
 */
class Field extends WPForms_Field {

	use LayoutFieldTrait;

	/**
	 * Instance of the Builder class for Layout Field.
	 *
	 * @since 1.8.9
	 *
	 * @var Builder
	 */
	protected $builder_obj;

	/**
	 * Display selector values.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	const DISPLAY_VALUES = [
		'rows',
		'blocks',
	];

	/**
	 * Layout presets.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	const PRESETS = [
		'100',
		'50-50',
		'67-33',
		'33-67',
		'33-33-33',
		'50-25-25',
		'25-25-50',
		'25-50-25',
		'25-25-25-25',
	];

	/**
	 * Field types that not allowed to drag into the column.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	const NOT_ALLOWED_FIELDS = [
		'layout',
		'repeater',
		'pagebreak',
		'divider',
		'entry-preview',
		'captcha',
		'file-upload',
		'likert_scale',
		'net_promoter_score',
		'credit-card',
		'payment-checkbox',
		'payment-multiple',
		'payment-select',
		'payment-single',
		'payment-total',
		'payment-coupon',
		'paypal-commerce',
		'stripe-credit-card',
		'square',
		'authorize_net',
		'internal-information',
	];

	/**
	 * Handle name for `wp_register_styles`.
	 *
	 * @since 1.8.9
	 *
	 * @var string
	 */
	public $style_handle = 'wpforms-repeater';

	/**
	 * Maximum allowed rows.
	 *
	 * @since 1.8.9
	 *
	 * @var int
	 */
	const ROWS_LIMIT_MAX = 200;

	/**
	 * Maximum allowed rows by default.
	 *
	 * @since 1.8.9
	 *
	 * @var int
	 */
	const DEFAULT_ROWS_LIMIT_MAX = 10;

	/**
	 * Columns settings by default.
	 *
	 * @since 1.8.9
	 *
	 * @var array
	 */
	const DEFAULT_COLUMNS = [
		0 => [
			'width_custom' => '',
			'width_preset' => '100',
			'fields'       => [],
		],
	];

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.9
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Repeater', 'wpforms' );
		$this->keywords = esc_html__( 'repeater, row, column', 'wpforms' );
		$this->type     = 'repeater';
		$this->icon     = 'fa-list fa-flip-horizontal';
		$this->order    = 150;
		$this->group    = 'fancy';

		// Default settings.
		$this->defaults = [
			'label'               => $this->name,
			'name'                => $this->name,
			'description'         => '',
			'label_hide'          => '0',
			'size'                => 'medium',
			'preset'              => '100',
			'display'             => 'rows',
			'button_type'         => 'buttons_with_icons',
			'button_add_label'    => esc_html__( 'Add', 'wpforms' ),
			'button_remove_label' => esc_html__( 'Remove', 'wpforms' ),
			'rows_limit_min'      => '1',
			'rows_limit_max'      => self::DEFAULT_ROWS_LIMIT_MAX,
			'columns'             => self::DEFAULT_COLUMNS,
		];

		$this->init_objects();
		$this->hooks();
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.8.9
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Field value that was submitted.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		if ( is_array( $field_submit ) ) {
			$field_submit = array_filter( $field_submit );
			$field_submit = implode( "\r\n", $field_submit );
		}

		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '';

		// Sanitize but keep line breaks.
		$value = wpforms_sanitize_textarea_field( $field_submit );

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'    => $name,
			'value'   => $value,
			'id'      => wpforms_validate_field_id( $field_id ),
			'columns' => ! empty( $form_data['fields'][ $field_id ]['columns'] ) ? $form_data['fields'][ $field_id ]['columns'] : [],
			'preset'  => ! empty( $form_data['fields'][ $field_id ]['preset'] ) ? $form_data['fields'][ $field_id ]['preset'] : '100',
			'label'   => ! empty( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '',
			'type'    => $this->type,
		];
	}

	/**
	 * Remove unsupported child fields from the field columns' data.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return array Filtered field settings.
	 */
	public function remove_unsupported_child_fields( array $field, array $form_data ): array {

		if ( empty( $field['columns'] ) || empty( $form_data['fields'] ) ) {
			return $field;
		}

		foreach ( $field['columns'] as $c => $column ) {
			$column_fields = $column['fields'] ?? [];

			foreach ( $column_fields as $f => $field_id ) {
				if (
					! isset( $form_data['fields'][ $field_id ]['type'] ) ||
					in_array( $form_data['fields'][ $field_id ]['type'], self::NOT_ALLOWED_FIELDS, true )
				) {
					unset( $column_fields[ $f ] );
				}
			}

			$field['columns'][ $c ]['fields'] = $column_fields;
		}

		return $field;
	}
}
