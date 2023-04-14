<?php
/**
 * Certificates Loop
 *
 * @since    3.14.0
 * @version  3.14.0
 */

defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'llms_before_certificate_loop' ); ?>

	<?php if ( $certificates ) : ?>

		<ul class="llms-certificates-loop listing-certificates <?php printf( 'loop-cols-%d', $cols ); ?>">

			<?php foreach ( $certificates as $certificate ) : ?>

				<li class="llms-certificate-loop-item certificate-item">
					<?php do_action( 'llms_certificate_preview', $certificate ); ?>
				</li>

			<?php endforeach; ?>

		</ul>

	<?php else : ?>

		<div class="llms-sd-section__blank">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/my-certificates.svg" alt="Certificates" />
			<p><?php echo apply_filters( 'lifterlms_no_certificates_text', __( 'You do not have any certificates yet.', 'buddyboss-theme' ) ); ?></p>
		</div>

	<?php endif; ?>

<?php do_action( 'llms_after_certificate_loop' ); ?>
