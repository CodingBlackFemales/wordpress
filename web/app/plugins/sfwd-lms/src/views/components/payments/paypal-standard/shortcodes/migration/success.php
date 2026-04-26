<?php
/**
 * View: PayPal Standard - Migration Success.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this Current instance of template engine rendering this template.

 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-paypal-standard__migration-success">
	<h2>
		<?php esc_html_e( 'Payment method updated', 'learndash' ); ?>
	</h2>

	<p>
		<?php
			echo esc_html(
				sprintf(
					// translators: 1: Courses label, 2: Groups label.
					__( 'Your payment method has been updated successfully, you can now continue with your %1$s and %2$s.', 'learndash' ),
					learndash_get_custom_label( 'courses' ),
					learndash_get_custom_label( 'groups' )
				)
			);
			?>
	</p>
</div>
