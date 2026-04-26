<?php
/**
 * View: Users Dashboard Widget Avatar.
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
<img
	class="ld-dashboard-widget-users__avatar"
	src="<?php echo esc_url( (string) get_avatar_url( $user->ID ) ); ?>"
	alt="<?php echo esc_html( $user->display_name ); ?>"
>
