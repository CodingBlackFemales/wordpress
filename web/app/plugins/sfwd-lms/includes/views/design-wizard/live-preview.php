<?php
/**
 * Live preview template
 *
 * @package LearnDash_Design_Wizard
 *
 * @var array<string> $template_details
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>

<div class="preview">
	<div class="text-wrapper"><?php esc_html_e( 'Loading', 'learndash' ); ?>...</div>
	<div class="iframe-wrapper">
		<iframe
			class="ld-site-preview"
			id="ld-site-preview"
			src="<?php echo esc_url( $template_details['preview_url'] ); ?>"
			frameborder="0"
		></iframe>
	</div>
</div>
