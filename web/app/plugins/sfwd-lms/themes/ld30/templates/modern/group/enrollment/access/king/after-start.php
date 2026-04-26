<?php
/**
 * View: Group Enrollment Access King - After Start.
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
<div class="ld-enrollment__king ld-enrollment__king--after-start">
	<span class="ld-enrollment__king-label">
		<?php echo esc_html__( 'Access', 'learndash' ); ?>
	</span>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			// translators: placeholder: %s = group start date.
			esc_html_x( 'Started %s', 'When a group has started', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_start_date() ) )
		);
		?>
	</span>
</div>
