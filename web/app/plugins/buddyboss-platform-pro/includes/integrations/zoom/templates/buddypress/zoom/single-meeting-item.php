<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

?>
<div class="meeting-item-container" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>>
	<div class="bb-title-wrap">
		<a href="#" class="bp-back-to-meeting-list"><span class="bb-icon-l bb-icon-angle-left"></span></a>
		<div>
			<h2 class="bb-title">
				<?php bp_zoom_meeting_title(); ?>
				<?php if ( 8 === bp_get_zoom_meeting_type() ) : ?>
					<span class="recurring-meeting-label"><?php esc_html_e( 'Recurring', 'buddyboss-pro' ); ?></span>
				<?php endif; ?>
			</h2>
			<div class="bb-timezone">
				<?php
				$utc_date_time = bp_get_zoom_meeting_start_date_utc();
				$time_zone     = bp_get_zoom_meeting_timezone();
				$date          = wp_date( bp_core_date_format(), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );
				echo esc_html( $date ) . ( ! empty( $time_zone ) ? ' (' . esc_html( bp_zoom_get_timezone_label( $time_zone ) ) . ')' : '' );
				?>
			</div>
		</div>
		<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) && bp_zoom_groups_can_user_manage_meeting( bp_get_zoom_meeting_id() ) ) : ?>
			<div class="meeting-actions">
				<a href="#" class="meeting-actions-anchor">
					<i class="bb-icon-f bb-icon-ellipsis-v"></i>
				</a>
				<div class="meeting-actions-list">
					<ul>
						<?php if ( true !== bp_get_zoom_meeting_is_past() ) : ?>
							<li class="bp-zoom-meeting-edit">
								<?php if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
									<a role="button" id="bp-zoom-meeting-occurrence-edit-button" class="edit-meeting" href="#" data-id="bp-meeting-edit">
										<i class="bb-icon-l bb-icon-edit"></i><?php esc_html_e( 'Edit this Meeting', 'buddyboss-pro' ); ?>
									</a>
									<div id="bp-zoom-edit-occurrence-popup-<?php echo esc_attr( bp_get_zoom_meeting_occurrence_id() ); ?>" class="bzm-white-popup mfp-hide bp-zoom-edit-occurrence-popup">
										<header class="bb-zm-model-header"><?php esc_html_e( 'You\'re changing a recurring meeting.', 'buddyboss-pro' ); ?></header>

										<div id="recurring-meeting-edit-container">
											<p>
												<?php esc_html_e( 'Do you want to edit all occurrences of this meeting, or only the selected occurrence?', 'buddyboss-pro' ); ?>
											</p>
										</div>

										<footer class="bb-zm-model-footer">
											<a href="#" id="bp-zoom-all-meeting-edit" class="button outline small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'All occurrences', 'buddyboss-pro' ); ?></a>
											<a href="#" id="bp-zoom-only-this-meeting-edit" class="button small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Only this meeting', 'buddyboss-pro' ); ?></a>
<!--                                            <a href="javascript:$.magnificPopup.close();"><?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?></a>-->
										</footer>
									</div>
								<?php else : ?>
									<a role="button" id="bp-zoom-meeting-edit-button" class="edit-meeting" href="#" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ! empty( bp_get_zoom_meeting_parent() ) ? '1' : '0'; ?>" <?php echo ! empty( bp_get_zoom_meeting_parent() ) ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>>
										<i class="bb-icon-l bb-icon-edit"></i><?php esc_html_e( 'Edit this Meeting', 'buddyboss-pro' ); ?>
									</a>
								<?php endif; ?>
							</li>
						<?php endif; ?>
						<li class="bp-zoom-meeting-delete">
							<?php if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
								<a role="button" id="bp-zoom-meeting-occurrence-delete-button" class="delete" href="#">
									<i class="bb-icon-l bb-icon-trash"></i><?php esc_html_e( 'Delete this Meeting', 'buddyboss-pro' ); ?>
								</a>
								<div id="bp-zoom-delete-occurrence-popup-<?php echo esc_attr( bp_get_zoom_meeting_occurrence_id() ); ?>" class="bzm-white-popup mfp-hide bp-zoom-delete-occurrence-popup">
									<header class="bb-zm-model-header"><?php esc_html_e( 'Delete Meeting', 'buddyboss-pro' ); ?></header>

									<div id="recurring-meeting-delete-container">
										<p>
											<?php echo esc_html__( 'Topic: ', 'buddyboss-pro' ) . esc_html( bp_get_zoom_meeting_title() ); ?><br/>
											<?php echo esc_html__( 'Time: ', 'buddyboss-pro' ) . esc_html( $date ); ?>
										</p>
									</div>

									<footer class="bb-zm-model-footer">
										<a href="#" id="bp-zoom-only-this-meeting-delete" class="button small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Delete This Occurrence', 'buddyboss-pro' ); ?></a>
										<a href="#" id="bp-zoom-all-meeting-delete"  class="button outline small error" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Delete All Occurrences', 'buddyboss-pro' ); ?></a>
									</footer>
								</div>
							<?php else : ?>
								<a role="button" class="delete bp-zoom-delete-meeting" href="javascript:;"><i class="bb-icon-l bb-icon-trash"></i><?php esc_html_e( 'Delete this Meeting', 'buddyboss-pro' ); ?></a>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table" data-meeting-start-date="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) ) ); ?>">
		<div class="single-meeting-item">
			<div class="meeting-item-head"><?php esc_html_e( 'Meeting ID', 'buddyboss-pro' ); ?></div>
			<div class="meeting-item-col">
				<span class="meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></span>
				<?php if ( bp_get_zoom_meeting_recurring() || 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
					<div class="bb-meeting-occurrence"><?php echo esc_html( bp_zoom_get_recurrence_label( bp_get_zoom_meeting_id() ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( bp_get_zoom_meeting_description() ) ) : ?>
			<div class="single-meeting-item">
				<div class="meeting-item-head"><?php esc_html_e( 'Description', 'buddyboss-pro' ); ?></div>
				<div class="meeting-item-col"><?php echo nl2br( bp_get_zoom_meeting_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		<?php endif; ?>

		<?php
		if ( true !== bp_get_zoom_meeting_is_past() ) {
			$duration = bp_get_zoom_meeting_duration();
			$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
			$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
			?>
			<div class="single-meeting-item">
				<div class="meeting-item-head"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></div>
				<div class="meeting-item-col">
					<?php
					if ( 0 < $hours ) {
						/* translators: %d number of hours */
						echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( 0 < $minutes ) {
						/* translators: %d number of minutes */
						echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			<?php
		}

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
			<div class="meeting-item-head"><?php esc_html_e( 'Meeting Passcode', 'buddyboss-pro' ); ?></div>
			<div class="meeting-item-col">
				<?php if ( ! empty( bp_get_zoom_meeting_password() ) ) : ?>
					<div class="z-form-row-action">
						<div class="pass-wrap">
							<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
							<span class="show-password"><strong><?php echo esc_html( bp_get_zoom_meeting_password() ); ?></strong></span>
						</div>
						<div class="pass-toggle">
							<a href="javascript:;" class="toggle-password show-pass on"><i class="bb-icon-l bb-icon-eye"></i><?php esc_html_e( 'Show passcode', 'buddyboss-pro' ); ?></a>
							<a href="javascript:;" class="toggle-password hide-pass"><i class="bb-icon-l bb-icon-eye-slash"></i><?php esc_html_e( 'Hide passcode', 'buddyboss-pro' ); ?></a>
						</div>
					</div>
				<?php else : ?>
					<span class="no-pass-required">
						<i class="bb-icon-l bb-icon-times"></i>
						<span><?php esc_html_e( 'No passcode required', 'buddyboss-pro' ); ?></span>
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

		$join_url = bp_get_zoom_meeting_zoom_join_url();
		if ( ! empty( $join_url ) ) {
			?>
			<div class="single-meeting-item">
				<div class="meeting-item-head"><?php esc_html_e( 'Meeting Link', 'buddyboss-pro' ); ?></div>
				<div class="meeting-item-col">
					<div class="copy-link-wrap">
						<a class="bb-invitation-url" <?php echo ! bb_zoom_is_meeting_hide_urls_enabled() ? 'target="_blank"' : ''; ?> href="<?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, bp_get_zoom_meeting_id() ) ); ?>"><?php echo esc_url( bp_zoom_get_meeting_rewrite_url( $join_url, bp_get_zoom_meeting_id() ) ); ?></a>
						<a id="copy-invitation-link" class="edit copy-invitation-link" href="#copy-invitation-popup" role="button" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"><span class="bb-icon bb-icon-l bb-icon-eye"></span><?php esc_html_e( 'View Invitation', 'buddyboss-pro' ); ?></a>

						<div id="copy-invitation-popup" class="bzm-white-popup copy-invitation-popup mfp-hide">
							<header class="bb-zm-model-header"><?php esc_html_e( 'View Invitation', 'buddyboss-pro' ); ?></header>

							<div id="meeting-invitation-container">
								<textarea id="meeting-invitation" readonly="readonly"><?php echo esc_html( bp_get_zoom_meeting_invitation( bp_get_zoom_meeting_zoom_meeting_id() ) ); ?></textarea>
							</div>

							<footer class="bb-zm-model-footer">
								<a href="#" id="copy-invitation-details" class="button small" data-copied="<?php esc_attr_e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><?php esc_html_e( 'Copy Meeting Invitation', 'buddyboss-pro' ); ?></a>
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
				$bp_get_zoom_meeting_authentication    = bp_get_zoom_meeting_authentication() ? 'yes' : 'no';
				$bp_get_zoom_meeting_auto_recording    = ( in_array( bp_get_zoom_meeting_auto_recording(), array( 'cloud', 'local' ), true ) ) ? 'yes' : 'no';
				?>
				<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_join_before_host ); ?>">
					<i class="<?php echo bp_get_zoom_meeting_join_before_host() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span><?php esc_html_e( 'Enable join before host', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_mute_participants ); ?>">
					<i class="<?php echo bp_get_zoom_meeting_mute_participants() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span><?php esc_html_e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_waiting_room ); ?>">
					<i class="<?php echo bp_get_zoom_meeting_waiting_room() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span><?php esc_html_e( 'Enable waiting room', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-meeting-option <?php echo esc_attr( $bp_get_zoom_meeting_authentication ); ?>">
					<i class="<?php echo bp_get_zoom_meeting_authentication() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
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

		<?php
		$occurrence_date_unix     = wp_date( 'U', strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( 'UTC' ) );
		$meeting_is_started       = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) );
		$show_join_meeting_button = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ), new DateTimeZone( 'UTC' ) ) ) );

		$current_date     = wp_date( 'U' );
		$meeting_date_obj = new DateTime( bp_get_zoom_meeting_start_date_utc(), new DateTimeZone( 'UTC' ) );
		$meeting_date_obj->modify( '+' . bp_get_zoom_meeting_duration() . ' minutes' );
		$meeting_date_unix  = $meeting_date_obj->format( 'U' );
		$meeting_number     = esc_attr( bp_get_zoom_meeting_zoom_meeting_id() );
		$role               = bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? 1 : 0; // phpcs:ignore
		$browser_credential = bb_zoom_group_generate_browser_credential(
			array(
				'group_id'       => bp_get_zoom_meeting_group_id(),
				'meeting_number' => $meeting_number,
				'role'           => $role,
			)
		);
		?>

		<?php if ( ! $meeting_is_started ) : ?>
			<div class="single-meeting-item bb-countdown-wrap">
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo esc_attr( $occurrence_date_unix ); ?>"></div>
				</div>
			</div>

		<?php endif; ?>

		<div class="single-meeting-item last-col meeting-buttons-wrap">
			<div class="meeting-item-col meeting-action last-col full text-right <?php echo ( 'started' === bp_get_zoom_meeting_current_status() || ( $show_join_meeting_button && $current_date < $meeting_date_unix ) ) ? '' : 'bp-hide'; ?>">

				<?php
				if ( ! empty( $browser_credential['sign'] ) ) {
					?>
					<a href="#" data-meeting-id="<?php echo esc_attr( bp_get_zoom_meeting_zoom_meeting_id() ); ?>"
					data-meeting-pwd="<?php echo esc_attr( bp_get_zoom_meeting_password() ); ?>"
					data-is-host="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? esc_attr( '1' ) : esc_attr( '0' ); ?>"
					data-meeting-sign="<?php echo esc_attr( $browser_credential['sign'] ); ?>" data-meeting-sdk="<?php echo esc_attr( $browser_credential['sdk_client_id'] ); ?>"
					class="button outline small join-meeting-in-browser">
						<?php
						if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) {
							esc_html_e( 'Host Meeting in Browser', 'buddyboss-pro' );
						} else {
							esc_html_e( 'Join Meeting in Browser', 'buddyboss-pro' );
						}
						?>
					</a>
				<?php } ?>

				<?php if ( ! bb_zoom_is_meeting_hide_urls_enabled() ) : ?>
					<a type="button" class="button primary small join-meeting-in-app" target="_blank" href="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? esc_url( bp_get_zoom_meeting_zoom_start_url() ) : esc_url( bp_get_zoom_meeting_zoom_join_url() ); ?>">
					<?php
					if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) {
						esc_html_e( 'Host Meeting in Zoom', 'buddyboss-pro' );
					} else {
						esc_html_e( 'Join Meeting in Zoom', 'buddyboss-pro' );
					}
					?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
				<div class="bb-recordings-wrap">
					<div class="meeting-item-head"></div>
					<div id="bp-zoom-meeting-recording-<?php echo esc_attr( bp_get_zoom_meeting_zoom_meeting_id() ); ?>" class="bp-zoom-meeting-recording-fetch" data-title="<?php echo esc_attr( bp_get_zoom_meeting_title() ); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_meeting_occurrence_id() ) . '"' : ''; ?>>
						<?php
						if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
							set_query_var( 'recording_fetch', 'yes' );
						} else {
							set_query_var( 'recording_fetch', 'no' );
						}
						if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
							set_query_var( 'occurrence_id', bp_get_zoom_meeting_occurrence_id() );
						}
						?>
						<?php bp_get_template_part( 'zoom/meeting/recordings' ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
