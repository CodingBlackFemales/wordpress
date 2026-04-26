<?php
/**
 * View: PayPal Checkout FraudNet JSON.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @link https://developer.paypal.com/docs/checkout/apm/pay-upon-invoice/fraudnet/#embed-fraudnet-snippet
 *
 * @var array{
 *   f: string,
 *   s: string,
 *   sandbox: bool,
 * } $data The data to display.
 *
 * @package LearnDash\Core
 */

?>
<script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99">
	<?php echo wp_json_encode( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a JSON string. ?>
</script>
