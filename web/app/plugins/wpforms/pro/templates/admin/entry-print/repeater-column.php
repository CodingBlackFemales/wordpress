<?php
/**
 * Entry print repeater field columns template.
 *
 * @since 1.8.9
 *
 * @var array  $field           Field data.
 * @var array  $form_data       Form data and settings.
 * @var object $entry           Entry.
 * @var array  $columns         Columns data.
 * @var bool   $is_hidden_by_cl Whether the field is hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

if ( empty( $form_data['fields'] ) || empty( $row_data ) ) {
	return;
}

$current_column = 0;

?>
<?php foreach ( $row_data as $data ) : ?>
	<?php
	$preset_width = ! empty( $data['width_preset'] ) ? (int) $data['width_preset'] : 50;

	if ( $preset_width === 33 ) {
		$preset_width = 33.33333;
	} elseif ( $preset_width === 67 ) {
		$preset_width = 66.66666;
	}

	if ( ! empty( $data['width_custom'] ) ) {
		$preset_width = (int) $data['width_custom'];
	}

	$is_column_empty = ! isset( $columns[ $current_column ] ) || LayoutHelpers::is_column_empty( $columns[ $current_column ] );
	$column_classes  = [
		'wpforms-field-layout-column',
		$is_column_empty ? 'wpforms-field-layout-column-empty' : '',
	];
	?>

	<div class="<?php echo wpforms_sanitize_classes( $column_classes, true ); ?>" style="width: <?php echo esc_attr( $preset_width ); ?>%">
		<?php if ( $data['field'] ) : ?>
			<?php
			echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'admin/entry-print/field',
				[
					'entry'           => $entry,
					'form_data'       => $form_data,
					'field'           => $data['field'],
					'is_hidden_by_cl' => $is_hidden_by_cl,
				],
				true
			);
			?>
		<?php endif; ?>
	</div>
<?php
	++$current_column;

	endforeach;
?>
