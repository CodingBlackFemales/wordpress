<?php
/**
 * Single Certificate Preview Template
 *
 * @since    3.14.0
 * @version  3.14.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="llms-certificate llms-loop-item-content">

	<div class="llms-certificate__badge">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/svg/certificate-icon.svg ' ); ?>" alt="Certificate"/>
	</div>

	<div class="llms-certificate__body">

		<?php do_action( 'lifterlms_before_certificate_preview', $certificate ); ?>

		<div class="llms-certificate__label"><?php esc_html_e( 'Certificate in', 'buddyboss-theme' ) ?></div>
		<a class="llms-certificate__link" data-id="<?php echo esc_attr($certificate->get( 'id' ) ); ?>" href="<?php echo esc_url( get_permalink( $certificate->get( 'id' ) ) ); ?>" id="<?php printf( 'llms-certificate-%d', $certificate->get( 'id' ) ); ?>">
			<h4 class="llms-certificate-title"><?php echo $certificate->get( 'certificate_title' ); ?></h4>
		</a>

		<div class="llms-certificate__footer flex align-items-center">
			<div class="llms-certificate-date">
				<div class="llms-certificate__date-label"><?php esc_html_e( 'Earned on', 'buddyboss-theme' ) ?></div>
				<div class="llms-certificate__moment"><?php echo $certificate->get_earned_date(); ?></div>
			</div>
			<div class="llms-certificate__download push-right">
				<form action="" method="POST">
					<button class="llms-certificate__downloadBtn button" type="submit" name="llms_generate_cert">
						<i class="bb-icon-rl bb-icon-arrow-down"></i>
					</button>

					<input type="hidden" name="certificate_id" value="<?php echo $certificate->get( 'id' ); ?>">
					<?php wp_nonce_field( 'llms-cert-actions', '_llms_cert_actions_nonce' ); ?>
				</form>
			</div>
		</div>

		<?php do_action( 'lifterlms_after_certificate_preview', $certificate ); ?>

	</div>

</div>

