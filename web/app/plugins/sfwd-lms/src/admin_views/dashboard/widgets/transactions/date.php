<?php
/**
 * View: Transactions Dashboard Widget Item Date.
 *
 * @since 4.9.0
 * @version 4.9.1
 *
 * @var Transaction  $transaction Transaction.
 * @var Template     $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget-transactions__column">
	<span class="ld-dashboard-widget-transactions__label">
		<?php
		echo esc_html(
			learndash_adjust_date_time_display(
				(int) strtotime( $transaction->get_post()->post_date_gmt )
			)
		);
		?>
	</span>

	<span class="ld-dashboard-widget-transactions__label ld-dashboard-widget-transactions__label--small">
		<?php esc_html_e( 'Created on', 'learndash' ); ?>
	</span>
</div>
