<?php
/**
 * BuddyBoss - Groups Zoom Loop Meetings
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

global $bp_zoom_current_meeting;
?>
<li class="meeting-item <?php bp_zoom_meeting_loop_classes(); ?>" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
	<div class="meeting-item-col meeting-topic">
		<a href="<?php bp_zoom_meeting_url( bp_get_current_group_id(), bp_get_zoom_meeting_id() ); ?>" class="meeting-title">
			<?php bp_zoom_meeting_title(); ?>
		</a>
		<?php if ( 'started' === bp_get_zoom_meeting_current_status() ) : ?>
			<span class="live-meeting-label"><?php esc_html_e( 'Live', 'buddyboss-pro' ); ?></span>
		<?php endif; ?>
		<?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
			<?php $recording_count = bp_get_zoom_meeting_recording_count(); ?>
			<?php if ( ! empty( $recording_count ) ) : ?>
				<a role="button" href="#" class="button small view-recordings bp-zoom-meeting-view-recordings">
					<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>
					<span class="record-count"><?php echo esc_html( $recording_count ); ?></span>
				</a>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div class="meeting-item-col meeting-meta-wrap">
		<?php /* translators: %d is meeting ID from zoom. */ ?>
		<div class="meeting-id"><?php printf( esc_html__( 'ID: %d', 'buddyboss-pro' ), esc_html( bp_get_zoom_meeting_zoom_meeting_id() ) ); ?></div>
		<?php if ( 8 === bp_get_zoom_meeting_type() ) : ?>
			<span class="recurring-meeting-label" data-bp-tooltip="<?php esc_html_e( 'Recurring', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left"></span>
		<?php endif; ?>
		<div class="meeting-date">
			<?php echo esc_html( wp_date( bp_core_date_format( false, true ), strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) ) ); ?>
		</div>
	</div>
</li>
