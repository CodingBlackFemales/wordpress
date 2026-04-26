<?php
/**
 * View: Course Enrollment Access King - After End.
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
<div class="ld-enrollment__king ld-enrollment__king--after-end">
	<span class="ld-enrollment__king-description">
		<?php echo esc_html__( 'Ended', 'learndash' ); ?>
	</span>

	<span class="ld-enrollment__king-label">
		<?php
		printf(
			// translators: placeholder: %1$s = course label, placeholder: %2$s = course end date.
			esc_html_x( 'This %1$s ended on %2$s', 'When a course has ended', 'learndash' ),
			esc_html( $product->get_type_label( true ) ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_end_date() ) )
		);
		?>
	</span>
</div>
