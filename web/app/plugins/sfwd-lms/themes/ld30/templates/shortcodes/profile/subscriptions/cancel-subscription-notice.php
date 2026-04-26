<?php
/**
 * View: Profile Subscriptions - Cancel Subscription Notice.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @package LearnDash\Core
 */

$transient_name = 'ld_subscription_canceled_user_' . get_current_user_id();

$cancellation_result = get_transient( $transient_name );

if ( false === $cancellation_result ) {
	return;
}

// Remove the transient.

delete_transient( $transient_name );

// Output the message.

if ( $cancellation_result ) {
	$message = __( 'The subscription has been canceled successfully.', 'learndash' );
} else {
	$message = __( 'The subscription could not be canceled. Please try again or contact support.', 'learndash' );
}
?>
<script>
	document.addEventListener( 'DOMContentLoaded', function() {
		alert( '<?php echo esc_js( $message ); ?>' );
	});
</script>
