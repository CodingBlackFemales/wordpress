<?php
/**
 * View: PayPal Checkout Connected Message.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Template $this      Current instance of template engine rendering this template.
 * @var bool     $test_mode Whether the PayPal Checkout is in test mode.
 * @var string   $url       URL to connect PayPal Checkout.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div
	id="ld-paypal-checkout-connected-message"
	class="ld-paypal-checkout-connected-message hidden"
	title="<?php esc_html_e( 'PayPal Checkout Connected', 'learndash' ); ?>"
>
	<h2 class="ld-paypal-checkout-connected-message__title">
		<?php esc_html_e( 'You are now connected to PayPal, here\'s what\'s next...', 'learndash' ); ?>
	</h2>

	<?php if ( $test_mode ) : ?>
		<p class="ld-paypal-checkout-connected-message__text ld-paypal-checkout-connected-message__text--warning">
			<strong><?php esc_html_e( 'You are currently in test mode. Please note that test mode is for testing only and does not affect your live account.', 'learndash' ); ?></strong>
		</p>
	<?php endif; ?>

	<p class="ld-paypal-checkout-connected-message__text">
		<?php esc_html_e( 'PayPal allows you to accept credit and debit cards directly on your website. Because of this, your site needs to maintain PCI-DSS compliance.', 'learndash' ); ?>
	</p>

	<p class="ld-paypal-checkout-connected-message__text">
		<?php esc_html_e( 'LearnDash never stores sensitive information like card details to your server and works seamlessly with SSL certificates.', 'learndash' ); ?>
	</p>

	<p class="ld-paypal-checkout-connected-message__text">
		<?php esc_html_e( 'Compliance is comprised of, but not limited to:', 'learndash' ); ?>
	</p>

	<?php
		$this::show_admin_template(
			'modules/payments/gateways/paypal/text-list',
			[
				'list'  => [
					__( 'Using a trusted, secure hosting provider &mdash; preferably one which claims and actively promotes PCI compliance.', 'learndash' ),
					__( 'Maintain security best practices when setting passwords and limit access to your server.', 'learndash' ),
					__( 'Implement an SSL certificate to keep your sales secure.', 'learndash' ),
					__( 'Keep WordPress and plugins up to date to ensure latest security fixes are present.', 'learndash' ),
				],
				'class' => 'ld-paypal-checkout-connected-message__list',
			]
		);
		?>

	<a href="<?php echo esc_url( $url ); ?>" class="ld-paypal-checkout-connected-message__button button button-primary">
		<?php esc_html_e( 'Got it, thanks!', 'learndash' ); ?>
	</a>
</div>
