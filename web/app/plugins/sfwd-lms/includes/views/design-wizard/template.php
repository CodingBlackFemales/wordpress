<?php
/**
 * Individual template look
 *
 * @package LearnDash_Design_Wizard
 *
 * @var LearnDash_Design_Wizard $design_wizard
 * @var array<string> $template_details
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div
	class="template"
	data-id="<?php echo esc_attr( $template_details['id'] ); ?>"
	data-theme_template_id="<?php echo esc_attr( $template_details['theme_template_id'] ); ?>"
	data-preview_url="<?php echo esc_url( $template_details['preview_url'] ); ?>"
>
	<figure>
		<div class="image-wrapper">
			<img
				src="<?php echo esc_url( $design_wizard->get_template_preview_image_url( $template_details['id'] ) ); ?>"
				alt="<?php echo esc_attr( $template_details['label'] ); ?>"
				loading="lazy"
			>
		</div>
		<figcaption>
			<div class="label">
				<?php echo esc_html( $template_details['label'] ); ?>
			</div>
		</figcaption>
		<div class="actions">
			<?php if ( ! empty( $template_details['preview_url'] ) ) : ?>
				<button class="preview button"><?php esc_html_e( 'Preview', 'learndash' ); ?></button>
			<?php endif; ?>

			<button class="select button"><?php esc_html_e( 'Select', 'learndash' ); ?></button>
		</div>
	</figure>
</div>
