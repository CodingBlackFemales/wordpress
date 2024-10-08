<?php
/**
 * Field template for the Entry Print page.
 *
 * @var object $entry           Entry.
 * @var array  $form_data       Form data and settings.
 * @var array  $field           Entry field.
 * @var bool   $is_hidden_by_cl Whether the field is hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_description = isset( $form_data['fields'][ $field['id'] ]['description'] ) ? $form_data['fields'][ $field['id'] ]['description'] : '';
$is_toggled_field  = in_array( $field['type'], [ 'divider', 'pagebreak', 'html', 'content' ], true );
$is_choices_field  = in_array( $field['type'], [ 'radio', 'checkbox', 'payment-checkbox', 'payment-multiple' ], true );
$is_empty_field    = $is_choices_field ? wpforms_is_empty_string( wpforms_get_choices_value( $field, $form_data ) ) : wpforms_is_empty_string( $field['formatted_value'] );
$is_empty_quantity = isset( $field['quantity'] ) && ! $field['quantity'];
$is_empty_slider   = isset( $field['type'] ) && $field['type'] === 'number-slider' && ( $field['value'] ?? 0 ) === 0;
$field_class       = [ 'print-item', 'field', 'wpforms-field-' . $field['type'] ];

if ( ! $is_toggled_field && ( $is_empty_field || $is_empty_quantity || $is_empty_slider ) ) {
	$field_class[] = 'wpforms-field-empty';
}

if ( $is_hidden_by_cl ) {
	$field_class[] = 'wpforms-conditional-hidden';
}
?>

<div class="<?php echo wpforms_sanitize_classes( $field_class, true ); ?>">
	<p class="print-item-title field-name">
		<?php echo esc_html( $field['formatted_label'] ); ?>
		<span class="print-item-description field-description"><?php echo esc_html( $field_description ); ?></span>
	</p>
	<?php if ( ! in_array( $field['type'], [ 'divider', 'pagebreak' ], true ) ) { ?>
		<div class="print-item-value field-value">
			<?php
			echo $is_empty_field && ! $is_choices_field
				? esc_html__( 'Empty', 'wpforms' )
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				: $field['formatted_value'];
			?>
		</div>
	<?php } ?>
</div>
