<?php
/**
 * View: Users Dashboard Widget.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Users    $widget Widget.
 * @var Template $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Users;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget ld-dashboard-widget-users <?php echo esc_attr( ! empty( $widget->get_users() ) ? 'ld-dashboard-widget-users--not-empty' : '' ); ?>">
	<?php if ( empty( $widget->get_users() ) ) : ?>
		<?php $this->template( 'dashboard/widget/empty' ); ?>
	<?php else : ?>
		<?php foreach ( $widget->get_users() as $user ) : ?>
			<?php $this->template( 'dashboard/widgets/users/item', compact( 'user' ) ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
