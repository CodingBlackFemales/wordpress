<?php
/**
 * View: Course Enrollment Access Subject - Expiration.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;

$expiration_in_days = Cast::to_int( $product->get_setting( 'expire_access_days' ) );

?>
<div class="ld-enrollment__subject ld-enrollment__subject--expiration">
	<?php
	printf(
		// translators: placeholder: %d = Number of days.
		esc_html_x( '%d-Day Access', 'When a course has expiration', 'learndash' ),
		esc_html( (string) $expiration_in_days )
	);
	?>
</div>
