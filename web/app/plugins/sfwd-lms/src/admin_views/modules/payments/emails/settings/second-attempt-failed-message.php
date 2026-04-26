<?php
/**
 * Template: Payment Emails Metabox - Second Attempt Failed Message.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @package LearnDash\Core
 */

?>

<?php esc_html_e( 'Hi {first_name},', 'learndash' ); ?>
<br/>
<?php esc_html_e( 'Our second attempt to process your subscription payment for {product_name} was unsuccessful.', 'learndash' ); ?>
<br/>
<br/>
<?php echo wp_kses_post( __( 'We\'ll try again in <strong>5 days</strong>, but to prevent losing access, we recommend reviewing your account or updating your payment method now.', 'learndash' ) ); ?>
<br/>
<br/>
<?php
printf( '<a href="%s">%s</a>', '#', esc_html__( 'Update Payment Info', 'learndash' ) ); // TODO: Add the payment info URL.
