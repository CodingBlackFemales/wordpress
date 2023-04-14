<?php
/**
 * Single certificate main content.
 *
 * @package LifterLMS/Templates/Certificates
 *
 * @since 6.0.0
 * @version 6.0.0
 *
 * @param LLMS_User_Certificate $certificate Certificate object.
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="llms-certificate-wrapper">
	<div id="certificate-<?php echo esc_attr( $certificate->get( 'id' ) ); ?>" <?php post_class( array( 'llms-certificate-container', 'cert-template-v2' ) ); ?>>

		<?php llms_print_notices(); ?>

		<?php
			do_action( 'before_lifterlms_certificate_main_content', $certificate );
		?>

		<?php echo wp_kses_post( llms_get_certificate_content() ); ?>

		<?php
			do_action( 'after_lifterlms_certificate_main_content', $certificate );
		?>

	</div>
</div>
