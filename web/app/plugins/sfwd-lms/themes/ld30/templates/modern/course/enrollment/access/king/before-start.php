<?php
/**
 * View: Course Enrollment Access King - Before Start.
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

?>
<div class="ld-enrollment__king ld-enrollment__king--before-start">
	<?php $this->template( 'modern/course/enrollment/access/king/label' ); ?>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			// translators: placeholder: %s = course start date.
			esc_html_x( 'Starts %s', 'When a course has not started', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_start_date() ) )
		);
		?>
	</span>
</div>
