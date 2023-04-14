<?php
/**
 * Single certificate main content.
 *
 * This is the legacy template for certificates built prior to version 6.
 *
 * @package LifterLMS/Templates/Certificates
 *
 * @since 6.0.0
 * @version 6.0.0
 *
 * @param LLMS_User_Certificate $certificate Certificate object.
 */

defined( 'ABSPATH' ) || exit;

$image = llms_get_certificate_image( $certificate->get( 'id' ) );
?>
<div class="llms-certificate-container" style="width:<?php echo esc_attr( $image['width'] ); ?>px; height:<?php echo esc_attr( $image['height'] ); ?>px;">
	<img src="<?php echo esc_url( $image['src'] ); ?>" style="margin-bottom:-<?php echo esc_attr( $image['height'] ); ?>px;" alt="<?php esc_html_e( 'Certificate Background', 'buddyboss-theme' ); ?>" class="certificate-background">
	<div id="certificate-<?php echo esc_attr( $certificate->get( 'id' ) ); ?>" <?php post_class(); ?>>

		<div class="llms-summary">

			<?php llms_print_notices(); ?>

			<?php
				do_action( 'before_lifterlms_certificate_main_content', $certificate );
			?>

			<h1><?php echo wp_kses_post( llms_get_certificate_title() ); ?></h1>
			<?php echo wp_kses_post( llms_get_certificate_content() ); ?>

			<?php
				do_action( 'after_lifterlms_certificate_main_content', $certificate );
			?>

		</div>
	</div>
</div>
