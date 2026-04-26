<?php
/**
 * View: Transactions Dashboard Widget User.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Transaction $transaction Transaction.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget-transactions__column">
	<span class="ld-dashboard-widget-transactions__label">
		<?php echo esc_html( $transaction->get_user()->display_name ); ?>
	</span>

	<span class="ld-dashboard-widget-transactions__label ld-dashboard-widget-transactions__label--small">
		<?php if ( ! $transaction->is_free() ) : ?>
			<?php
			printf(
				// Translators: placeholder: Gateway label.
				esc_html_x( 'Via %s', 'placeholder: Gateway label', 'learndash' ),
				esc_html( $transaction->get_gateway_label() )
			);
			?>
		<?php endif; ?>
	</span>
</div>
