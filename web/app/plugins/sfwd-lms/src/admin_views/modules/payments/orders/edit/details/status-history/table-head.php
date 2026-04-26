<?php
/**
 * View: Order Customer Details Table Head.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template    $this        Current instance of template engine rendering this template.
 * @var Transaction $transaction Transaction object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Transaction;
?>
<div class="ld-order-details__thead" role="rowgroup">
	<div role="row">
		<span role="columnheader" aria-sort="none">
			<?php esc_html_e( 'Status History', 'learndash' ); ?>
		</span>
	</div>
</div>
