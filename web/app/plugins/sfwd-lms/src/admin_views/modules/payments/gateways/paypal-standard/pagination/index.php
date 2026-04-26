<?php
/**
 * View: PayPal Standard - Current Subscriptions pagination.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this          Current instance of template engine rendering this template.
 * @var int      $current_page  Current page number.
 * @var int      $total_items   Total items.
 * @var int      $items_per_page Items per page.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$total_pages = ceil( $total_items / $items_per_page );

// Don't show pagination if there's only one page or no items.
if ( $total_pages <= 1 || $total_items === 0 ) {
	return;
}

// Calculate item range display.
$start_item = ( ( $current_page - 1 ) * $items_per_page ) + 1;
$end_item   = min( $current_page * $items_per_page, $total_items );

?>
<div class="tablenav bottom">
	<div class="tablenav-pages">
		<?php
		$this::show_admin_template(
			'modules/payments/gateways/paypal-standard/pagination/displaying-num',
			[
				'total_items' => $total_items,
			]
		);
		?>

		<?php if ( $total_pages > 1 ) : ?>
			<?php
			$this::show_admin_template(
				'modules/payments/gateways/paypal-standard/pagination/navigation',
				[
					'current_page' => $current_page,
					'total_pages'  => $total_pages,
				]
			);
			?>
		<?php endif; ?>
	</div>
</div>
