<?php
/**
 * The template for displaying schedule activity post case heading.
 *
 * @since 2.5.20
 *
 * @package BuddyBossPro
 *
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-schedule-details">
	<?php
	if ( bp_is_active( 'activity' ) ) :
		?>
		<# if ( data.activity_schedule_date && data.activity_schedule_time ) {  #>
			<span class="activity-post-schedule-details">
				<i class="bb-icon-f bb-icon-clock"></i><strong><?php esc_html_e( 'Posting:', 'buddyboss-pro' ); ?></strong> {{{data.activity_schedule_date}}} <?php esc_html_e( 'at', 'buddyboss-pro' ); ?> {{{data.activity_schedule_time}}} <span class="activity-post-meridiem">{{{data.activity_schedule_meridiem}}}</span>
			</span>
		<# } #>
	<?php endif; ?>
</script>
