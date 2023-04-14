<?php
/**
 * BuddyBoss - Groups Zoom Loop Webinars
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.9
 */

?>
<li class="webinar-item <?php bp_zoom_webinar_loop_classes(); ?>" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>">
	<div class="webinar-item-col webinar-topic">
		<a href="<?php bp_zoom_webinar_url( bp_get_current_group_id(), bp_get_zoom_webinar_id() ); ?>" class="webinar-title">
			<?php bp_zoom_webinar_title(); ?>
		</a>
		<?php if ( 'started' === bp_get_zoom_webinar_current_status() ) : ?>
			<span class="live-webinar-label"><?php esc_html_e( 'Live', 'buddyboss-pro' ); ?></span>
		<?php endif; ?>
		<?php $recording_count = bp_get_zoom_webinar_recording_count(); ?>
		<?php if ( bp_zoom_is_zoom_recordings_enabled() && ! empty( $recording_count ) ) : ?>
			<a role="button" href="#" class="button small view-recordings bp-zoom-webinar-view-recordings">
				<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>
				<span class="record-count"><?php echo esc_html( $recording_count ); ?></span>
			</a>
		<?php endif; ?>
	</div>

	<div class="webinar-item-col webinar-meta-wrap">
		<?php /* translators: %d is webinar ID from zoom. */ ?>
		<div class="webinar-id"><?php printf( esc_html__( 'ID: %d', 'buddyboss-pro' ), esc_html( bp_get_zoom_webinar_zoom_webinar_id() ) ); ?></div>
		<?php if ( 9 === bp_get_zoom_webinar_type() ) : ?>
			<span class="recurring-webinar-label" data-bp-tooltip="<?php esc_html_e( 'Recurring', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left"></span>
		<?php endif; ?>
		<div class="webinar-date">
			<?php echo esc_html( wp_date( bp_core_date_format( false, true ), strtotime( bp_get_zoom_webinar_start_date_utc() ), new DateTimeZone( bp_get_zoom_webinar_timezone() ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( bp_get_zoom_webinar_start_date_utc() ), new DateTimeZone( bp_get_zoom_webinar_timezone() ) ) ); ?>
		</div>
	</div>
</li>
