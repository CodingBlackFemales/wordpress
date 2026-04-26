<?php
/**
 * View: Order Actions.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template     $this         Current instance of template engine rendering this template.
 * @var Invoice|null $invoice      Invoice object or null if it cannot be found.
 * @var string       $nonce_action Nonce key.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Invoice;
use LearnDash\Core\Models\Transaction;

$resend_invoice_url = add_query_arg(
	[
		'resend_invoice' => true,
		'nonce'          => wp_create_nonce( $nonce_action ),
	]
);

if (
	$invoice instanceof Invoice
	&& $invoice->get_transaction() instanceof Transaction
) : ?>
	<a href="<?php echo esc_attr( $resend_invoice_url ); ?>">
		<?php esc_html_e( 'Resend Invoice Email to Customer', 'learndash' ); ?>
	</a>
	<?php
else :
	echo esc_html(
		sprintf(
			// translators: placeholder: Order label.
			__( 'An Invoice Email cannot be sent for this %s.', 'learndash' ),
			learndash_get_custom_label( 'order' )
		)
	);
endif;
