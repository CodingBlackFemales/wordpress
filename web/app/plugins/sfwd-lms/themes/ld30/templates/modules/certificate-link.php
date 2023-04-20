<?php
/**
 * LearnDash LD30 Displays the certificate link
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the certificate link wrapper.
 *
 * @since 3.0.0
 */
do_action( 'learndash-certificate-wrapper-before' ); ?>

<div class="ld-course-certificate">

	<?php
	/**
	 * Fires before the certificate link.
	 *
	 * @since 3.0.0
	 */
	do_action( 'learndash-certificate-before' );
	?>

	<a href="<?php echo esc_url( $course_certficate_link ); ?>" class="ld-button"><span class="ld-icon ld-icon-certificate"></span> <?php esc_html_e( 'Download Certificate', 'learndash' ); ?></a>

	<?php
	/**
	 * Fires after the certificate link.
	 *
	 * @since 3.0.0
	 */
	do_action( 'learndash-certificate-after' );
	?>

</div> <!--/.ld-course-certificate-->

<?php
/**
 * Fires after the certificate link wrapper.
 *
 * @since 3.0.0
 */
do_action( 'learndash-certificate-wrapper-after' ); ?>
