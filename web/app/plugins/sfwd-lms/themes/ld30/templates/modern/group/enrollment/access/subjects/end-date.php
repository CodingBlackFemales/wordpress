<?php
/**
 * View: Group Enrollment Access Subject - End Date.
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
<div class="ld-enrollment__subject ld-enrollment__subject--end-date">
	<?php
	printf(
		// translators: placeholder: %s = group end date.
		esc_html_x( 'Ends %s', 'When a group has not ended', 'learndash' ),
		esc_html( learndash_adjust_date_time_display( (int) $product->get_end_date() ) )
	);
	?>
</div>
