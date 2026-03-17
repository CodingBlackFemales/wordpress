<?php
/**
 * BuddyBoss Header Template.
 *
 * @package BuddyBoss
 *
 * @since 2.14.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="bb-theme-tab-header">
	<div class="bb-theme-branding-header">
		<img alt="" class="bb-branding-logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/BBLogo.png' ); ?>" />
	</div>
	<div class="bb-theme-header-actions">
		<?php do_action( 'buddyboss_theme_admin_header_actions' ); ?>
	</div>
</div>
