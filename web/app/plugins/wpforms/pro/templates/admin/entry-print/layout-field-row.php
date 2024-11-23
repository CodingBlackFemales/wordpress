<?php
/**
 * Field Layout template for the Entry Print page.
 *
 * @var object $entry           Entry.
 * @var array  $form_data       Form data and settings.
 * @var array  $field           Entry field data.
 * @var bool   $is_hidden_by_cl Whether the field is hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

$rows              = isset( $field['columns'] ) && is_array( $field['columns'] ) ? LayoutHelpers::get_row_data( $field ) : [];
$field_description = $form_data['fields'][ $field['id'] ]['description'] ?? '';

$classes = [ 'wpforms-field-layout-row' ];

if ( $is_hidden_by_cl ) {
	$classes[] = 'wpforms-conditional-hidden';
}

if ( LayoutHelpers::is_layout_empty( $field ) ) {
	$classes[] = 'wpforms-field-layout-empty';
}
?>
<div class="<?php echo wpforms_sanitize_classes( $classes, true ); ?>">
	<p class="print-item-title field-name">
		<?php if ( isset( $field['label_hide'] ) && ! $field['label_hide'] && ! empty( $field['label'] ) ) { ?>
			<span class="print-item-title-wrapper">
				<?php echo esc_html( $field['label'] ); ?>
			</span>
		<?php } ?>

		<?php if ( ! empty( $field_description ) ) : ?>
			<span class="print-item-description field-description">
				<?php echo esc_html( $field_description ); ?>
			</span>
		<?php endif; ?>
	</p>

	<div class="print-item field wpforms-field-layout-rows">
		<?php foreach ( $rows as $row ) { ?>
			<div class="wpforms-layout-row">
				<?php
				foreach ( $row as $column ) {
					$field_html   = '';
					$preset_width = ! empty( $column['width_preset'] ) ? (int) $column['width_preset'] : 50;

					if ( $preset_width === 33 ) {
						$preset_width = 33.33333;
					}

					if ( $preset_width === 67 ) {
						$preset_width = 66.66666;
					}

					if ( ! empty( $column['width_custom'] ) ) {
						$preset_width = (int) $column['width_custom'];
					}

					if ( ! empty( $column['field'] ) ) {
						$field_html = wpforms_render(
							'admin/entry-print/field',
							[
								'entry'           => $entry,
								'form_data'       => $form_data,
								'field'           => $column['field'],
								'is_hidden_by_cl' => $is_hidden_by_cl,
							],
							true
						);
					}

					$column_classes = [ 'wpforms-field-layout-column' ];

					if ( LayoutHelpers::is_column_empty( $column ) ) {
						$column_classes[] = 'wpforms-field-layout-column-empty';
					}

					printf(
						'<div class="%1$s" style="width: %2$s">%3$s</div>',
						wpforms_sanitize_classes( $column_classes, true ),
						esc_attr( (float) $preset_width . '%' ),
						$field_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				}
				?>
			</div>
		<?php } ?>
	</div>
</div>
