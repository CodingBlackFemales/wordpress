<?php
/**
 * View: PayPal Standard - Migration Actions.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-paypal-standard__migration-actions">
	<p>
		<a href="https://go.learndash.com/paypalprivacy" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'By saving this payment method, I agree to the PayPal Privacy Statement.', 'learndash' ); ?>
		</a>
	</p>

	<button
		class="ld-paypal-standard__migration-submit btn-join button button-primary button-large wp-element-button ld--ignore-inline-css"
		type="button"
	>
		<?php esc_html_e( 'Save Payment Method and Continue', 'learndash' ); ?>
	</button>
</div>
