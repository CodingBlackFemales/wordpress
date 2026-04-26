<?php
/**
 * View: Order Subscription Details - Table Header.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-order-items__thead" role="rowgroup">
	<div class="ld-order-items__row" role="row">
		<span class="ld-order-items__column-title ld-order-items__column-title--charges ld-order-items__column-title--first-child" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Gateway Subscription ID', 'learndash' ); ?>
		</span>

		<span class="ld-order-items__column-title ld-order-items__column-title--charges" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Next Payment', 'learndash' ); ?>
		</span>

		<span class="ld-order-items__column-title ld-order-items__column-title--charges ld-order-items__column-title--last-child" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Status', 'learndash' ); ?>
		</span>

		<span
			aria-hidden="true"
			aria-sort="none"
			class="ld-order-items__column-title ld-order-items__column-title--charges ld-order-items__column-title--empty"
			role="columnheader"
		>
		</span>
	</div>
</div>
