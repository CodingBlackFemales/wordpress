<?php
/**
 * View: Course Enrollment Access King - Seats Full.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

?>
<div class="ld-enrollment__king ld-enrollment__king--seats-full">
	<span class="ld-enrollment__king-description">
		<?php echo esc_html__( 'Full', 'learndash' ); ?>
	</span>

	<span class="ld-enrollment__king-label">
		<?php
		printf(
			// translators: placeholder: %s = Course label.
			esc_html_x( 'This %s is Full', 'When a course is full', 'learndash' ),
			esc_html( $product->get_type_label() )
		);
		?>
	</span>
</div>
