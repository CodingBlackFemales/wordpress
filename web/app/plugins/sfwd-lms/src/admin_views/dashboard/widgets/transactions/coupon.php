<?php
/**
 * View: Transactions Dashboard Widget Coupon.
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
	<?php if ( $transaction->has_coupon() ) : ?>
		<span class="ld-dashboard-widget-transactions__label">
			<?php echo esc_html( $transaction->get_coupon_data()->code ); ?>
		</span>

		<span class="ld-dashboard-widget-transactions__label ld-dashboard-widget-transactions__label--small">
			<?php esc_html_e( 'Coupon', 'learndash' ); ?>
		</span>
	<?php endif; ?>
</div>
