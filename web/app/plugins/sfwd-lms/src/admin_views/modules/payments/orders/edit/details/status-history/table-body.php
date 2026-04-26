<?php
/**
 * View: Order Customer Details Table Body.
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
use LearnDash\Core\Utilities\Cast;
?>
<div class="ld-order-details__table--tbody" role="rowgroup">
	<div role="row">
		<span role="cell">
			<?php
				printf(
					'%1$s %2$s',
					$transaction->get_post()->post_status === 'draft'
						? esc_html__( 'Ordered', 'learndash' )
						: esc_html__( 'Completed', 'learndash' ),
					esc_html(
						learndash_adjust_date_time_display(
							Cast::to_int(
								strtotime(
									$transaction->get_post()->post_date_gmt
								)
							)
						)
					)
				);
				?>
		</span>
	</div>
</div>
