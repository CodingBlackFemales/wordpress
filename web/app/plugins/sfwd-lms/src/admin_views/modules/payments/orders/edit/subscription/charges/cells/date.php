<?php
/**
 * View: Order Subscription Charges - Table Body - Field: Date.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Charge $charge Charge object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Charge;

$date = learndash_adjust_date_time_display( $charge->get_date(), 'Y/m/d' );
?>
<span class="ld-order-items__item-data" role="cell">
	<?php echo esc_html( $date ); ?>
</span>
