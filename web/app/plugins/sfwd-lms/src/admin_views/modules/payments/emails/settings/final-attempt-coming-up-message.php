<?php
/**
 * Template: Payment Emails Metabox - Final Attempt Coming Up Message.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @package LearnDash\Core
 */

?>

<?php esc_html_e( 'Hi {first_name},', 'learndash' ); ?>
<br/>
<?php echo wp_kses_post( __( 'We\'ll make a <strong>final attempt</strong> to process your subscription payment for {product_name} in a few days.', 'learndash' ) ); ?>
<br/>
<br/>
<?php esc_html_e( 'If it fails again, your subscription will be paused and access will be revoked.', 'learndash' ); ?>
<br/>
<br/>
<?php
printf( '<a href="%1$s">%2$s</a>', '#', esc_html__( 'Update Payment Info', 'learndash' ) ); // TODO: Add the payment info URL.
