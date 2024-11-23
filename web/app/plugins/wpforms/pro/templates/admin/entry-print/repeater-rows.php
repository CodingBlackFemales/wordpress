<?php
/**
 * Entry print repeater field rows template.
 *
 * @since 1.8.9
 *
 * @var array  $field           Field data.
 * @var array  $form_data       Form data and settings.
 * @var object $entry           Entry.
 * @var bool   $is_hidden_by_cl Whether the field is hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;
use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

$rows = isset( $field['columns'] ) && is_array( $field['columns'] ) ? LayoutHelpers::get_row_data( $field ) : [];

if ( empty( $rows ) ) {
	return;
}

$field_description = $form_data['fields'][ $field['id'] ]['description'] ?? '';

$classes = [ 'wpforms-field-repeater-row' ];

if ( $is_hidden_by_cl ) {
	$classes[] = 'wpforms-conditional-hidden';
}

if ( RepeaterHelpers::is_empty_block( $rows ) ) {
	$classes[] = 'wpforms-field-repeater-block-empty';
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

	<div class="wpforms-field-repeater-rows">
		<?php foreach ( $rows as $row_data ) : ?>
			<div class="wpforms-layout-row">
				<?php
				echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'admin/entry-print/repeater-column',
					[
						'entry'           => $entry,
						'row_data'        => $row_data,
						'form_data'       => $form_data,
						'columns'         => $field['columns'] ?? [],
						'is_hidden_by_cl' => $is_hidden_by_cl,
					],
					true
				);
				?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
