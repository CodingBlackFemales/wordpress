<?php
/**
 * View: Group Enrollment Access King - Seats Full.
 *
 * @since 4.22.0
 * @version 4.22.0
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
			// translators: placeholder: %s = Group label.
			esc_html_x( 'This %s is Full', 'When a group is full', 'learndash' ),
			esc_html( $product->get_type_label() )
		);
		?>
	</span>
</div>
