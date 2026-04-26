<?php
/**
 * Template: Payment Emails Metabox - Initial Payment Failed Message.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @package LearnDash\Core
 */

?>

<?php esc_html_e( 'Hi {first_name},', 'learndash' ); ?>
<br/>
<?php echo wp_kses_post( __( 'We couldn\'t process your recent subscription payment. We\'ll try again automatically in about <strong>1 hour</strong>.', 'learndash' ) ); ?>
<br/>
<br/>
<?php esc_html_e( 'Please double-check your payment method to avoid losing access.', 'learndash' ); ?>
<br/>
<br/>
<?php
printf( '<a href="%1$s">%2$s</a>', '#', esc_html__( 'Update Payment Info', 'learndash' ) ); // TODO: Add the payment info URL.
