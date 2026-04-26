<?php
/**
 * View: Group Enrollment Access King - Before End.
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
<div class="ld-enrollment__king ld-enrollment__king--before-end">
	<?php $this->template( 'modern/group/enrollment/access/king/label' ); ?>

	<span class="ld-enrollment__king-description">
		<?php
		printf(
			// translators: placeholder: %s = group end date.
			esc_html_x( 'Ends %s', 'When a group has not ended', 'learndash' ),
			esc_html( learndash_adjust_date_time_display( (int) $product->get_end_date() ) )
		);
		?>
	</span>
</div>
