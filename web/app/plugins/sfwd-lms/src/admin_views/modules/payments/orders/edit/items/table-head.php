<?php
/**
 * Template: Order items header.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-order-items__thead" role="rowgroup">
	<div class="ld-order-items__row" role="row">
		<span class="ld-order-items__column-title ld-order-items__column-title--first-child ld-order-items__column-title-name" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Name', 'learndash' ); ?>
		</span>

		<span class="ld-order-items__column-title ld-order-items__column-title-enrollment-status" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Enrollment Status', 'learndash' ); ?>
		</span>

		<span class="ld-order-items__column-title ld-order-items__column-title-pricing-details" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Pricing Details', 'learndash' ); ?>
		</span>

		<span class="ld-order-items__column-title ld-order-items__column-title--last-child ld-order-items__column-title-price" role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Price', 'learndash' ); ?>
		</span>
	</div>
</div>
