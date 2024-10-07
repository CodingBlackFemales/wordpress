<?php
/**
 * Single entry layout field columns template.
 *
 * @since 1.9.0
 *
 * @var array                  $field           Field data.
 * @var array                  $form_data       Form data and settings.
 * @var WPForms_Entries_Single $entries_single  Single entry object.
 * @var bool                   $is_hidden_by_cl Is the field hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

$classes = [ 'wpforms-field-layout-column' ];

if ( $is_hidden_by_cl ) {
	$classes[] = 'wpforms-conditional-hidden';
}

if ( LayoutHelpers::is_layout_empty( $field ) ) {
	$classes[] = 'empty';

	if ( empty( $entries_single->entry_view_settings['fields']['show_empty_fields']['value'] ) ) {
		$classes[] = 'wpforms-hide';
	}
}
?>

<div class="<?php echo wpforms_sanitize_classes( $classes, true ); ?>">
	<?php
	echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'admin/entries/single-entry/block-header',
		[
			'field'          => $field,
			'form_data'      => $form_data,
			'entries_single' => $entries_single,
		],
		true
	);
	?>

	<div class="wpforms-entry-field-layout">
		<?php foreach ( $field['columns'] as $column ) : ?>
			<?php $width = $entries_single->get_layout_col_width( $column ); ?>
			<div class="wpforms-entry-field-layout-inner wpforms-field-layout-column" style="width: <?php echo esc_attr( $width ); ?>%">
				<?php
				foreach ( $column['fields'] as $child_field ) {
					$entries_single->print_field( $child_field, $form_data );
				}
				?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
