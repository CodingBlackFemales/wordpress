<?php
/**
 * View: Group Enrollment Access - Seats Remaining.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

$available_seats = (int) $product->get_seats_available();

// If there are no seats available, we don't show the seats remaining.
if ( $available_seats <= 0 ) {
	return;
}

?>
<span class="ld-enrollment__seats-remaining">
	<?php
	printf(
		esc_html(
			// Translators: %d: Number of seats available.
			_nx(
				'%d Place Remaining',
				'%d Places Remaining',
				$available_seats,
				'Group seats remaining',
				'learndash'
			)
		),
		esc_html( (string) $available_seats )
	);
	?>
</span>
