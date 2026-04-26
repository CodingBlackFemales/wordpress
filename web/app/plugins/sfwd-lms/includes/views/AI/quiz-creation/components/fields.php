<?php
/**
 * Quiz creation AI fields component.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 *
 * @var array<string, array{
 *     ?label: string,
 *     ?required: string|bool,
 *     ?type: string,
 *     ?help_text: string
 * }> $form_fields LearnDash answer types in key label pair.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Modules\AI\Quiz_Creation;
?>

<input type="hidden" name="action" value="<?php echo esc_attr( Quiz_Creation::$slug ); ?>">

<?php wp_nonce_field( Quiz_Creation::$slug ); ?>

<?php if ( isset( $form_fields ) && is_array( $form_fields ) ) : ?>
	<?php foreach ( $form_fields as $ld_field_id => $ld_field ) : ?>
		<div class="ld-w-full ld-mb-4">
			<label
				for="<?php echo esc_attr( $ld_field_id ); ?>"
				class="ld-block ld-text-gray-700 ld-text-sm ld-font-bold ld-mb-2"
			>
				<?php echo esc_html( $ld_field['label'] ); ?>

				<?php if ( isset( $ld_field['required'] ) && $ld_field['required'] ) : ?>
					<sup>*</sup>
				<?php endif; ?>
			</label>

			<?php
			$ld_field_instance = LearnDash_Settings_Fields::get_field_instance( $ld_field['type'] );

			if ( $ld_field_instance instanceof LearnDash_Settings_Fields ) {
				$ld_field_instance->create_section_field( $ld_field );
			}
			?>

			<?php if ( ! empty( $ld_field['help_text'] ) ) : ?>
				<p class="ld-w-full ld-italic">
					<?php echo esc_html( $ld_field['help_text'] ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<div class="ld-w-full ld-mb-4 ld-italic">
	<span><strong><sup>*</sup></strong> : <?php esc_html_e( 'Required', 'learndash' ); ?></span>
</div>
