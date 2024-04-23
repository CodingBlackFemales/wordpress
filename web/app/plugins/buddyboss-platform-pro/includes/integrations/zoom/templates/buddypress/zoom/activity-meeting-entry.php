<?php
/**
 * BuddyBoss - Zoom Activity Meeting Entry
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

$url = false;
if ( bp_get_zoom_meeting_group_id() && bp_is_active( 'groups' ) ) {
	$group_link = bp_get_group_permalink( groups_get_group( bp_get_zoom_meeting_group_id() ) );
	$url        = trailingslashit( $group_link . 'zoom/meetings/' . bp_get_zoom_meeting_id() );
}

$utc_date_time = bp_get_zoom_meeting_start_date_utc();
if ( bp_get_zoom_meeting_recurring() ) {
	$occurrence_utc_date_time = bp_zoom_get_first_occurrence_date_utc( bp_get_zoom_meeting_id() );
	if ( ! empty( $occurrence_utc_date_time ) ) {
		$utc_date_time = $occurrence_utc_date_time;
	}
}

$current_date             = wp_date( 'U' );
$occurrence_date_unix     = wp_date( 'U', strtotime( $utc_date_time ), new DateTimeZone( 'UTC' ) );
$meeting_is_started       = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) );
$show_join_meeting_button = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ), new DateTimeZone( 'UTC' ) ) ) );
$date                     = wp_date( bp_core_date_format(), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) );

$meeting_date_obj = new DateTime( $utc_date_time );
$meeting_date_obj->setTimezone( wp_timezone() );
$meeting_date_obj->modify( '+' . bp_get_zoom_meeting_duration() . ' minutes' );
$meeting_date_unix = $meeting_date_obj->format( 'U' );
?>
<div class="zoom-meeting-block">
	<div class="zoom-meeting-block-info">
		<a href="<?php echo $url ? esc_url( $url ) : ''; ?>"><h2><?php bp_zoom_meeting_title(); ?></h2></a>
		<div class="bb-meeting-date zoom-meeting_date"><?php echo esc_html( $date ) . ( ! empty( bp_get_zoom_meeting_timezone() ) ? ' (' . esc_html( bp_zoom_get_timezone_label( bp_get_zoom_meeting_timezone() ) ) . ')' : '' ); ?></div>
		<?php if ( bp_get_zoom_meeting_recurring() ) : ?>
			<div class="bb-meeting-occurrence"><?php echo esc_html( bp_zoom_get_recurrence_label( bp_get_zoom_meeting_id() ) ); ?></div>
		<?php endif; ?>

		<div class="bp-zoom-block-show-details">
			<a href="#bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="show-meeting-details">
				<span class="bb-icon-l bb-icon-calendar"></span> <?php esc_html_e( 'Meeting Details', 'buddyboss-pro' ); ?>
			</a>
		</div>

		<div id="bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="bzm-white-popup bp-zoom-block-show-details mfp-hide">
			<header class="bb-zm-model-header">
				<span><?php bp_zoom_meeting_title(); ?></span>
				<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			</header>
			<div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table">
				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Date and Time', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<?php echo esc_html( $date ) . ( ! empty( bp_get_zoom_meeting_timezone() ) ? ' (' . esc_html( bp_zoom_get_timezone_label( bp_get_zoom_meeting_timezone() ) ) . ')' : '' ); ?>
					</div>
				</div>
				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Meeting ID', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<span class="meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></span>
					</div>
				</div>
				<?php if ( ! empty( bp_get_zoom_meeting_description() ) ) { ?>
					<div class="single-meeting-item">
						<div class="meeting-item-head"><?php esc_html_e( 'Description', 'buddyboss-pro' ); ?></div>
						<div class="meeting-item-col"><?php echo nl2br( bp_get_zoom_meeting_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					</div>
					<?php
				}
				$duration = bp_get_zoom_meeting_duration();
				$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
				$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
				?>
				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<?php
						if ( 0 < $hours ) {
							/* translators: %d is number of hours */
							echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						if ( 0 < $minutes ) {
							/* translators: %d is number of hours */
							echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>

				<?php
				$alert = bp_get_zoom_meeting_alert();
				if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
					$meeting_parent = BP_Zoom_Meeting::get_meeting_by_meeting_id( bp_get_zoom_meeting_parent() );

					if ( ! empty( $meeting_parent ) ) {
						$alert = $meeting_parent->alert;
					}
				}

				if ( ! empty( $alert ) ) {
					?>
					<div class="single-meeting-item">
						<div class="meeting-item-head"><?php esc_html_e( 'Meeting Notifications', 'buddyboss-pro' ); ?></div>
						<div class="meeting-item-col">
							<?php
							if ( $alert > 59 ) {
								/* translators: %d number of hours */
								echo sprintf( _n( '%d hour before', '%d hours before', $alert / 60, 'buddyboss-pro' ), $alert / 60 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} elseif ( $alert > 1 ) {
								/* translators: %d number of minutes */
								echo sprintf( _n( '%d minute before', '%d minutes before', $alert, 'buddyboss-pro' ), $alert ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								esc_html_e( 'Immediately before the meeting', 'buddyboss-pro' );
							}
							?>
						</div>
					</div>
				<?php } ?>

				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Meeting Password', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<?php if ( ! empty( bp_get_zoom_meeting_password() ) ) : ?>
							<div class="z-form-row-action">
								<div class="pass-wrap">
									<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
									<span class="show-password"><strong><?php bp_zoom_meeting_password(); ?></strong></span>
								</div>
								<div class="pass-toggle">
									<a href="javascript:;" class="toggle-password show-pass on">
										<i class="bb-icon-l bb-icon-eye"></i>
										<?php esc_html_e( 'Show password', 'buddyboss-pro' ); ?>
									</a>
									<a href="javascript:;" class="toggle-password hide-pass">
										<i class="bb-icon-l bb-icon-eye-slash"></i>
										<?php esc_html_e( 'Hide password', 'buddyboss-pro' ); ?>
									</a>
								</div>
							</div>
						<?php else : ?>
							<span class="no-pass-required">
								<i class="bb-icon-l bb-icon-times"></i>
								<span><?php esc_html_e( 'No password required', 'buddyboss-pro' ); ?></span>
							</span>
						<?php endif; ?>
					</div>
				</div>
				<?php
				$registration_url = bp_get_zoom_meeting_registration_url();
				if ( ! empty( $registration_url ) ) {
					?>
					<div class="single-meeting-item">
						<div class="meeting-item-head"><?php esc_html_e( 'Registration Link', 'buddyboss-pro' ); ?></div>
						<div class="meeting-item-col">
							<div class="copy-link-wrap">
								<a class="bb-registration-url" target="_blank" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
							</div>
						</div>
					</div>
					<?php
				}
				?>
				<?php $join_url = bp_get_zoom_meeting_zoom_join_url(); ?>
				<?php if ( ! empty( $join_url ) ) { ?>
					<div class="single-meeting-item">
						<div class="meeting-item-head"><?php esc_html_e( 'Meeting Link', 'buddyboss-pro' ); ?></div>
						<div class="meeting-item-col">
							<div class="copy-link-wrap">
								<a class="bb-invitation-url" target="_blank" href="<?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, bp_get_zoom_meeting_id() ) ); ?>"><?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, bp_get_zoom_meeting_id() ) ); ?></a>
								<a class="edit copy-invitation-link" href="#copy-invitation-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" role="button" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"><span class="bb-icon bb-icon-l bb-icon-eye"></span><?php esc_html_e( 'View Invitation', 'buddyboss-pro' ); ?></a>

								<div id="copy-invitation-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="bzm-white-popup copy-invitation-popup copy-invitation-popup-block mfp-hide">
									<header class="bb-zm-model-header">
										<span><?php esc_html_e( 'View Invitation', 'buddyboss-pro' ); ?></span>
										<a href="#bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="show-meeting-details" title="<?php esc_html_e( 'Close', 'buddyboss-pro' ); ?>"><i class="bb-icon-l bb-icon-times"></i></a>
									</header>

									<div id="meeting-invitation-container">
										<textarea id="meeting-invitation" readonly="readonly"><?php echo esc_html( bp_get_zoom_meeting_invitation( bp_get_zoom_meeting_zoom_meeting_id() ) ); ?></textarea>
									</div>

									<footer class="bb-zm-model-footer">
										<a href="#" id="copy-invitation-details" class="button small" data-copied="<?php esc_html_e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><?php esc_html_e( 'Copy Meeting Invitation', 'buddyboss-pro' ); ?></a>
									</footer>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Video', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<div class="video-info-wrap">
							<span><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></span>
							<span class="info-status"><?php echo bp_get_zoom_meeting_host_video() ? esc_html__( ' On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="video-info-wrap">
							<span><?php esc_html_e( 'Participant', 'buddyboss-pro' ); ?></span>
							<span class="info-status"><?php echo bp_get_zoom_meeting_participants_video() ? esc_html__( 'On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
						</div>
					</div>
				</div>
				<div class="single-meeting-item">
					<div class="meeting-item-head"><?php esc_html_e( 'Meeting Options', 'buddyboss-pro' ); ?></div>
					<div class="meeting-item-col">
						<?php
						$bp_get_zoom_meeting_join_before_host  = bp_get_zoom_meeting_join_before_host() ? 'yes' : 'no';
						$bp_get_zoom_meeting_mute_participants = bp_get_zoom_meeting_mute_participants() ? 'yes' : 'no';
						$bp_get_zoom_meeting_waiting_room      = bp_get_zoom_meeting_waiting_room() ? 'yes' : 'no';
						$bp_get_zoom_meeting_authentication    = ! empty( bp_get_zoom_meeting_authentication() ) ? 'yes' : 'no';
						$bp_get_zoom_meeting_auto_recording    = ( in_array( bp_get_zoom_meeting_auto_recording(), array( 'cloud', 'local' ), true ) ) ? 'yes' : 'no';
						?>
						<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_join_before_host ); ?>">
							<i class="<?php echo bp_get_zoom_meeting_join_before_host() ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Enable join before host', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_mute_participants ); ?>">
							<i class="<?php echo bp_get_zoom_meeting_mute_participants() ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_waiting_room ); ?>">
							<i class="<?php echo bp_get_zoom_meeting_waiting_room() ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Enable waiting room', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_authentication ); ?>">
							<i class="<?php echo ! empty( bp_get_zoom_meeting_authentication() ) ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_auto_recording ); ?>">
							<i class="<?php echo in_array( bp_get_zoom_meeting_auto_recording(), array( 'cloud', 'local' ), true ) ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
							<span>
								<?php
								if ( 'cloud' === bp_get_zoom_meeting_auto_recording() ) {
									esc_html_e( 'Record the meeting automatically in the cloud', 'buddyboss-pro' );
								} elseif ( 'local' === bp_get_zoom_meeting_auto_recording() ) {
									esc_html_e( 'Record the meeting automatically in the local computer', 'buddyboss-pro' );
								} else {
									esc_html_e( 'Do not record the meeting.', 'buddyboss-pro' );
								}
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

	<div class="zoom-meeting-block-right">
		<?php if ( ! $meeting_is_started ) : ?>
			<div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo esc_attr( $occurrence_date_unix ); ?>"></div>
		<?php endif; ?>
		<?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
			<div id="bp-zoom-meeting-recording-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-title="<?php bp_zoom_meeting_title(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="bp-zoom-meeting-recording-fetch">
				<?php set_query_var( 'recording_fetch', 'no' ); ?>
				<?php set_query_var( 'meeting_id', bp_get_zoom_meeting_zoom_meeting_id() ); ?>
				<?php set_query_var( 'topic', bp_get_zoom_meeting_title() ); ?>
				<?php
				if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
					set_query_var( 'occurrence_id', bp_get_zoom_meeting_occurrence_id() );
				}
				?>
				<?php bp_get_template_part( 'zoom/meeting/recordings' ); ?>
			</div>
		<?php endif; ?>
		<?php
		if ( 'started' === bp_get_zoom_meeting_current_status() || ( $show_join_meeting_button && $current_date < $meeting_date_unix ) ) :

			$meeting_number     = esc_attr( bp_get_zoom_meeting_zoom_meeting_id() );
			$user_role          = bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? 1 : 0;
			$browser_credential = bb_zoom_group_generate_browser_credential(
				array(
					'group_id'       => bp_get_zoom_meeting_group_id(),
					'meeting_number' => $meeting_number,
					'role'           => $user_role,
				)
			);
			?>
			<div class="meeting-actions">
				<?php
				if ( ! empty( $browser_credential['sign'] ) ) {
					?>
					<a href="#" class="button small outline join-meeting-in-browser" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-meeting-pwd="<?php bp_zoom_meeting_password(); ?>" data-is-host="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? esc_attr( '1' ) : esc_attr( '0' ); ?>" data-meeting-sign="<?php echo esc_attr( $browser_credential['sign'] ); ?>" data-meeting-sdk="<?php echo esc_attr( $browser_credential['sdk_client_id'] ); ?>">
					<?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) : ?>
							<?php esc_html_e( 'Host Meeting in Browser', 'buddyboss-pro' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Join Meeting in Browser', 'buddyboss-pro' ); ?>
						<?php endif; ?>
					</a>
				<?php } ?>

				<?php if ( ! bb_zoom_is_meeting_hide_urls_enabled() ) : ?>
					<a class="button small primary join-meeting-in-app" target="_blank" href="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? esc_url( bp_get_zoom_meeting_zoom_start_url() ) : esc_url( bp_get_zoom_meeting_zoom_join_url() ); ?>">
						<?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) : ?>
							<?php esc_html_e( 'Host Meeting in Zoom', 'buddyboss-pro' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Join Meeting in Zoom', 'buddyboss-pro' ); ?>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
