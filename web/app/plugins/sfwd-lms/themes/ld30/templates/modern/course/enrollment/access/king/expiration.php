<?php
/**
 * View: Course Enrollment Access King - Expiration.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product  $product Product model.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;

$expiration_in_days = Cast::to_int( $product->get_setting( 'expire_access_days' ) );
?>
<div class="ld-enrollment__king ld-enrollment__king--expiration">
	<?php $this->template( 'modern/course/enrollment/access/king/label' ); ?>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			// translators: placeholder: %d = Number of days.
			esc_html_x( '%d-Day Access', 'When a course has expiration', 'learndash' ),
			esc_html( (string) $expiration_in_days )
		);
		?>
	</span>
</div>
