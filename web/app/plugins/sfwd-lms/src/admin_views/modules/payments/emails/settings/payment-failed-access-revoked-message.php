<?php
/**
 * Template: Payment Emails Metabox - Payment Failed Access Revoked Message.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @package LearnDash\Core
 */

?>

<?php esc_html_e( 'Hi {first_name},', 'learndash' ); ?>
<br/>
<?php echo wp_kses_post( __( 'We weren\'t able to process your subscription payment after multiple attempts. As a result, your subscription has been paused and access to {product_name} is currently unavailable.', 'learndash' ) ); ?>
<br/>
<br/>
<?php esc_html_e( 'To regain access, please re-enroll.', 'learndash' ); ?>
<br/>
<br/>
<?php
printf( '<a href="%1$s">%2$s</a>', '{product_url}', esc_html__( 'Re-Enroll Now', 'learndash' ) );
