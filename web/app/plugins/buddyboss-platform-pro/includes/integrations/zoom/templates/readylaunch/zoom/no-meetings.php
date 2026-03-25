<?php
/**
 * BuddyBoss - ReadyLaunch No Meetings
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

?>
<div class="bb-rl-zoom-no-meetings">
	<div class="none-meeting-figure"><i class="bb-icons-rl-info"></i></div>
	<div class="bb-rl-none-meeting-info">
		<p><?php esc_html_e( 'No meetings found', 'buddyboss-pro' ); ?></p>
		<?php
		if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
		?>
			<p class="no-zoom-cta"><?php esc_html_e( 'Create a new meeting or sync with Zoom via the Sync button.', 'buddyboss-pro' ); ?></p>
		<?php
		}
		?>
	</div>
</div>
