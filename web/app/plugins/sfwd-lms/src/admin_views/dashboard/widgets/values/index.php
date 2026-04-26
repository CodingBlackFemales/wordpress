<?php
/**
 * View: Values Dashboard Widget.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Values   $widget Widget.
 * @var Template $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Values;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget ld-dashboard-widget-values <?php echo esc_attr( ! empty( $widget->get_items() ) ? 'ld-dashboard-widget-users--not-empty' : '' ); ?>">
	<?php if ( empty( $widget->get_items() ) ) : ?>
		<?php $this->template( 'dashboard/widget/empty' ); ?>
	<?php else : ?>
		<?php foreach ( $widget->get_items() as $item ) : ?>
			<?php $this->template( 'dashboard/widgets/values/item', compact( 'item' ) ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
