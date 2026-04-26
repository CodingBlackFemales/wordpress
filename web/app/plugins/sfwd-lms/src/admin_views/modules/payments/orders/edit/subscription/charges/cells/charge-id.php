<?php
/**
 * View: Order Subscription Charges - Table Body - Field: Charge ID.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Charge $charge Charge object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Charge;
?>
<span class="ld-order-items__item-data ld-order-items__item-data--first-child" role="cell">
	<?php
	echo esc_html(
		sprintf(
			// Translators: %s is the subscription charge ID.
			_x( '#%s', 'Subscription charge ID', 'learndash' ),
			$charge->get_id()
		)
	);
	?>
</span>
