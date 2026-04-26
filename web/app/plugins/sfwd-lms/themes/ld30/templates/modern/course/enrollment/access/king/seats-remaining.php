<?php
/**
 * View: Course Enrollment Access King - Seats Remaining.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product  $product Product model.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

$available_seats = (int) $product->get_seats_available();

// If there are no seats available, we don't show the seats remaining.
if ( $available_seats <= 0 ) {
	return;
}

?>
<div class="ld-enrollment__king ld-enrollment__king--seats-remaining">
	<?php $this->template( 'modern/course/enrollment/access/king/label' ); ?>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			esc_html(
				// Translators: %d: Number of seats available.
				_nx(
					'%d Place Remaining',
					'%d Places Remaining',
					$available_seats,
					'Course seats remaining',
					'learndash'
				)
			),
			esc_html( (string) $available_seats )
		);
		?>
	</span>
</div>
