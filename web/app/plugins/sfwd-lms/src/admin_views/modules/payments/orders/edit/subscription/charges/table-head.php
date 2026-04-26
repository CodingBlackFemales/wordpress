<?php
/**
 * View: Order Subscription Charges - Table Head.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Charge[]  $charges Charges.
 * @var Template  $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Commerce\Charge;

?>
<div class="ld-order-items__thead" role="rowgroup">
	<?php foreach ( $charges as $charge ) : ?>
		<div class="ld-order-items__row" role="row">
			<span class="ld-order-items__column-title ld-order-items__column-title--first-child" role="columnheader" aria-sort="none">
				<?php esc_html_e( 'Charge ID', 'learndash' ); ?>
			</span>

			<span class="ld-order-items__column-title" role="columnheader" aria-sort="none">
				<?php esc_html_e( 'Date', 'learndash' ); ?>
			</span>

			<span class="ld-order-items__column-title ld-order-items__column-title--last-child" role="columnheader" aria-sort="none">
				<?php esc_html_e( 'Amount', 'learndash' ); ?>
			</span>

			<span
				class="ld-order-items__column-title ld-order-items__column-title--empty"
				role="columnheader"
				aria-hidden="true"
				aria-sort="none"
			>
				<?php // Empty cell to align the columns. ?>
			</span>
		</div>
	<?php endforeach; ?>
</div>
