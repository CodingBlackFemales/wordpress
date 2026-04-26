<?php
/**
 * View: Users Dashboard Widget Title.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var WP_User  $user User.
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-widget-users__title">
	<span class="ld-dashboard-widget-users__name">
		<?php echo esc_html( $user->display_name ); ?>
	</span>

	<span class="ld-dashboard-widget-users__label">
		<?php echo esc_html( $user->user_email ); ?>
	</span>
</div>
