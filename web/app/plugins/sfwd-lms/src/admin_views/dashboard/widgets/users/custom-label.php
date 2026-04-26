<?php
/**
 * View: Users Dashboard Widget Custom Label.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var WP_User  $user   User.
 * @var Users    $widget Widget.
 * @var Template $this   Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\Users;
use LearnDash\Core\Template\Template;

if ( empty( $widget->get_custom_label_property() ) ) {
	return;
}
?>
<span class="ld-dashboard-widget-users__label">
	<?php echo esc_html( $user->{$widget->get_custom_label_property()} ); ?>
</span>
