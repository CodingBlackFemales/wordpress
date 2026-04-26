<?php
/**
 * View: Transactions Dashboard Widget.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Transactions $widget Widget.
 * @var Template     $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Transactions;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget ld-dashboard-widget-transactions <?php echo esc_attr( ! empty( $widget->get_transactions() ) ? 'ld-dashboard-widget-transactions--not-empty' : '' ); ?>">
	<?php if ( empty( $widget->get_transactions() ) ) : ?>
		<?php $this->template( 'dashboard/widget/empty' ); ?>
	<?php else : ?>
		<?php foreach ( $widget->get_transactions() as $transaction ) : ?>
			<?php $this->template( 'dashboard/widgets/transactions/item', compact( 'transaction' ) ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
