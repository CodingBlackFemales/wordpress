<?php
/**
 * View: Group Enrollment Access King - Before Start.
 *
 * @since 4.22.0
 * @version 4.22.0
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
	<?php $this->template( 'modern/group/enrollment/access/king/label' ); ?>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			// translators: placeholder: %s = group start date.
			esc_html_x( 'Starts %s', 'When a group has not started', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_start_date() ) )
		);
		?>
	</span>
</div>
