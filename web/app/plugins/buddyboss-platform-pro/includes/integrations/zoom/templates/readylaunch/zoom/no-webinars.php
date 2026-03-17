<?php
/**
 * BuddyBoss - ReadyLaunch No Webinars
 *
 * @package BuddyBossPro/Integration/Zoom/Template/ReadyLaunch
 * @since 1.0.0
 */

?>
<div class="bb-rl-zoom-no-meetings bb-rl-zoom-no-webinars">
	<div class="none-meeting-figure none-webinar-figure"><i class="bb-icons-rl-info"></i></div>
	<div class="bb-rl-none-meeting-info bb-rl-none-webinar-info">
		<p><?php esc_html_e( 'No webinars found', 'buddyboss-pro' ); ?></p>
		<?php
		if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
		?>
			<p class="no-zoom-cta"><?php esc_html_e( 'Create a new webinar or sync with Zoom via the Sync button.', 'buddyboss-pro' ); ?></p>
		<?php
		}
		?>
	</div>
</div> 