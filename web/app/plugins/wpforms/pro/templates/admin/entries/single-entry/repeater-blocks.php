<?php
/**
 * Single entry repeater field blocks template.
 *
 * @since 1.8.9
 *
 * @var array                  $field           Field data.
 * @var array                  $form_data       Form data and settings.
 * @var WPForms_Entries_Single $entries_single  Single entry object.
 * @var bool                   $is_hidden_by_cl Is the field hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

$blocks = RepeaterHelpers::get_blocks( $field, $form_data );

if ( ! $blocks ) {
	return '';
}

$classes = [ 'wpforms-field-repeater-block' ];

if ( $is_hidden_by_cl ) {
	$classes[] = 'wpforms-conditional-hidden';
}
?>

<?php
foreach ( $blocks as $key => $rows ) :
	$block_classes = $classes;

	if ( RepeaterHelpers::is_empty_block( $rows ) ) {
		$block_classes[] = 'empty';

		if ( empty( $entries_single->entry_view_settings['fields']['show_empty_fields']['value'] ) ) {
			$block_classes[] = 'wpforms-hide';
		}
	}
	?>
	<div class="<?php echo wpforms_sanitize_classes( $block_classes, true ); ?>">
		<?php
		$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';

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

		<div class="wpforms-field-repeater-blocks">
			<?php foreach ( $rows as $row_data ) : ?>
				<div class="wpforms-layout-row">
					<?php
					echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'admin/entries/single-entry/repeater-column',
						[
							'row_data'       => $row_data,
							'form_data'      => $form_data,
							'entries_single' => $entries_single,
							'columns'        => $field['columns'] ?? [],
						],
						true
					);
					?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endforeach; ?>
