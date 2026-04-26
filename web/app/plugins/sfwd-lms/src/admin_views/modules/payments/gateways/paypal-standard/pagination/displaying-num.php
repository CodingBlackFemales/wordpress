<?php
/**
 * View: PayPal Standard - Pagination Displaying Number.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var int $total_items Total number of items.
 *
 * @package LearnDash\Core
 */

?>
<span class="displaying-num">
	<?php
	printf(
		/* translators: %d: Number of items. */
		esc_html( _n( '%d item', '%d items', $total_items, 'learndash' ) ),
		esc_html( number_format_i18n( $total_items ) )
	);
	?>
</span>
