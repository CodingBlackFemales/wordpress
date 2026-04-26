<?php
/**
 * View: Group Enrollment Access King - After End.
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
<div class="ld-enrollment__king ld-enrollment__king--after-end">
	<span class="ld-enrollment__king-description">
		<?php echo esc_html__( 'Ended', 'learndash' ); ?>
	</span>

	<span class="ld-enrollment__king-label">
		<?php
		printf(
			// translators: placeholder: %1$s = group label, placeholder: %2$s = group end date.
			esc_html_x( 'This %1$s ended on %2$s', 'When a group has ended', 'learndash' ),
			esc_html( $product->get_type_label( true ) ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_end_date() ) )
		);
		?>
	</span>
</div>
