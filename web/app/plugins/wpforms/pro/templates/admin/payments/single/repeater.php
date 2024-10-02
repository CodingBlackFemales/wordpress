<?php
/**
 * Single Payment page - Payment entry repeater template.
 *
 * @since 1.8.9
 *
 * @var array $field        Field data.
 * @var array $form_data    Form data.
 * @var array $entry_fields Entry fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

$blocks = RepeaterHelpers::get_blocks( $field, $form_data );

if ( ! $blocks ) {
	return '';
}

?>

<?php foreach ( $blocks as $key => $rows ) : ?>
	<div class="wpforms-payment-entry-repeater-block">
		<?php
		$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';
		?>

		<p class="wpforms-payment-entry-field-name">
			<?php echo esc_html( $field['label'] . $block_number ); ?>
		</p>

		<?php
		foreach ( $rows as $row_data ) {
			foreach ( $row_data as $data ) {
				if ( isset( $data['field'] ) && $data['field'] ) {
					echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'admin/payments/single/field',
						[
							'field' => $data['field'],
						],
						true
					);
				}
			}
		}
		?>
	</div>
<?php endforeach; ?>
