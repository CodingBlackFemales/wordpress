<?php
/**
 * BuddyBoss - Groups Zoom Single Webinar
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.9
 */

?>
<div class="webinar-item-container" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() || bp_get_zoom_webinar_recurring() ) ? '1' : '0'; ?>" <?php echo 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>>
	<div class="bb-title-wrap">
		<a href="#" class="bp-back-to-webinar-list"><span class="bb-icon-l bb-icon-angle-left"></span></a>
		<div>
			<h2 class="bb-title">
				<?php bp_zoom_webinar_title(); ?>
				<?php if ( 9 === bp_get_zoom_webinar_type() ) : ?>
					<span class="recurring-webinar-label"><?php esc_html_e( 'Recurring', 'buddyboss-pro' ); ?></span>
				<?php endif; ?>
			</h2>
			<div class="bb-timezone">
				<?php
				$utc_date_time = bp_get_zoom_webinar_start_date_utc();
				$time_zone     = bp_get_zoom_webinar_timezone();
				$date          = wp_date( bp_core_date_format(), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );
				echo esc_html( $date ) . ( ! empty( $time_zone ) ? ' (' . esc_html( bp_zoom_get_timezone_label( $time_zone ) ) . ')' : '' );
				?>
			</div>
		</div>
		<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) && bp_zoom_groups_can_user_manage_webinar( bp_get_zoom_webinar_id() ) ) : ?>
			<div class="webinar-actions">
				<a href="#" class="webinar-actions-anchor">
					<i class="bb-icon-f bb-icon-ellipsis-v"></i>
				</a>
				<div class="webinar-actions-list">
					<ul>
						<?php if ( true !== bp_get_zoom_webinar_is_past() ) : ?>
							<li class="bp-zoom-webinar-edit">
								<?php if ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ) : ?>
									<a role="button" id="bp-zoom-webinar-occurrence-edit-button" class="edit-webinar" href="#" data-id="bp-webinar-edit">
										<i class="bb-icon-l bb-icon-edit"></i><?php esc_html_e( 'Edit this Webinar', 'buddyboss-pro' ); ?>
									</a>
									<div id="bp-zoom-edit-occurrence-popup-<?php echo esc_attr( bp_get_zoom_webinar_occurrence_id() ); ?>" class="bzm-white-popup mfp-hide bp-zoom-edit-occurrence-popup">
										<header class="bb-zm-model-header"><?php esc_html_e( 'You\'re changing a recurring webinar.', 'buddyboss-pro' ); ?></header>

										<div id="recurring-webinar-edit-container">
											<p>
												<?php esc_html_e( 'Do you want to edit all occurrences of this webinar, or only the selected occurrence?', 'buddyboss-pro' ); ?>
											</p>
										</div>

										<footer class="bb-zm-model-footer">
											<a href="#" id="bp-zoom-all-webinar-edit" class="button outline small" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() || bp_get_zoom_webinar_recurring() ) ? '1' : '0'; ?>" <?php echo 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'All occurrences', 'buddyboss-pro' ); ?></a>
											<a href="#" id="bp-zoom-only-this-webinar-edit" class="button small" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() || bp_get_zoom_webinar_recurring() ) ? '1' : '0'; ?>" <?php echo 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Only this webinar', 'buddyboss-pro' ); ?></a>
<!--                                            <a href="javascript:$.magnificPopup.close();"><?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?></a>-->
										</footer>
									</div>
								<?php else : ?>
									<a role="button" id="bp-zoom-webinar-edit-button" class="edit-webinar" href="#" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ! empty( bp_get_zoom_webinar_parent() ) ? '1' : '0'; ?>" <?php echo ! empty( bp_get_zoom_webinar_parent() ) ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>>
										<i class="bb-icon-l bb-icon-edit"></i><?php esc_html_e( 'Edit this Webinar', 'buddyboss-pro' ); ?>
									</a>
								<?php endif; ?>
							</li>
						<?php endif; ?>
						<li class="bp-zoom-webinar-delete">
							<?php if ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ) : ?>
								<a role="button" id="bp-zoom-webinar-occurrence-delete-button" class="delete" href="#">
									<i class="bb-icon-l bb-icon-trash"></i><?php esc_html_e( 'Delete this Webinar', 'buddyboss-pro' ); ?>
								</a>
								<div id="bp-zoom-delete-occurrence-popup-<?php echo esc_attr( bp_get_zoom_webinar_occurrence_id() ); ?>" class="bzm-white-popup mfp-hide bp-zoom-delete-occurrence-popup">
									<header class="bb-zm-model-header"><?php esc_html_e( 'Delete Webinar', 'buddyboss-pro' ); ?></header>

									<div id="recurring-webinar-delete-container">
										<p>
											<?php echo esc_html__( 'Topic: ', 'buddyboss-pro' ) . esc_html( bp_get_zoom_webinar_title() ); ?><br/>
											<?php echo esc_html__( 'Time: ', 'buddyboss-pro' ) . esc_html( $date ); ?>
										</p>
									</div>

									<footer class="bb-zm-model-footer">
										<a href="#" id="bp-zoom-only-this-webinar-delete" class="button small" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() || bp_get_zoom_webinar_recurring() ) ? '1' : '0'; ?>" <?php echo 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Delete This Occurrence', 'buddyboss-pro' ); ?></a>
										<a href="#" id="bp-zoom-all-webinar-delete"  class="button outline small error" data-id="<?php bp_zoom_webinar_id(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-is-recurring="<?php echo ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() || bp_get_zoom_webinar_recurring() ) ? '1' : '0'; ?>" <?php echo 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ? 'data-occurrence-id="' . esc_attr( bp_get_zoom_webinar_occurrence_id() ) . '"' : ''; ?>><?php esc_html_e( 'Delete All Occurrences', 'buddyboss-pro' ); ?></a>
									</footer>
								</div>
							<?php else : ?>
								<a role="button" class="delete bp-zoom-delete-webinar" href="javascript:;"><i class="bb-icon-l bb-icon-trash"></i><?php esc_html_e( 'Delete this Webinar', 'buddyboss-pro' ); ?></a>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div id="bp-zoom-single-webinar" class="webinar-item webinar-item-table single-webinar-item-table" data-webinar-start-date="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( bp_get_zoom_webinar_start_date_utc() ), new DateTimeZone( bp_get_zoom_webinar_timezone() ) ) ); ?>">
		<div class="single-webinar-item">
			<div class="webinar-item-head"><?php esc_html_e( 'Webinar ID', 'buddyboss-pro' ); ?></div>
			<div class="webinar-item-col">
				<span class="webinar-id"><?php bp_zoom_webinar_zoom_webinar_id(); ?></span>
				<?php if ( bp_get_zoom_webinar_recurring() || 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ) : ?>
					<div class="bb-webinar-occurrence"><?php echo esc_html( bp_zoom_get_webinar_recurrence_label( bp_get_zoom_webinar_id() ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( bp_get_zoom_webinar_description() ) ) : ?>
			<div class="single-webinar-item">
				<div class="webinar-item-head"><?php esc_html_e( 'Description', 'buddyboss-pro' ); ?></div>
				<div class="webinar-item-col"><?php echo nl2br( bp_get_zoom_webinar_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		<?php endif; ?>

		<?php
		if ( true !== bp_get_zoom_webinar_is_past() ) {
			$duration = bp_get_zoom_webinar_duration();
			$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
			$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
			?>
			<div class="single-webinar-item">
				<div class="webinar-item-head"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></div>
				<div class="webinar-item-col">
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

		$alert = bp_get_zoom_webinar_alert();
		if ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ) {
			$webinar_parent = BP_Zoom_Webinar::get_webinar_by_webinar_id( bp_get_zoom_webinar_parent() );

			if ( ! empty( $webinar_parent ) ) {
				$alert = $webinar_parent->alert;
			}
		}

		if ( ! empty( $alert ) ) {
			?>
			<div class="single-webinar-item">
				<div class="webinar-item-head"><?php esc_html_e( 'Webinar Notifications', 'buddyboss-pro' ); ?></div>
				<div class="webinar-item-col">
					<?php
					if ( $alert > 59 ) {
						/* translators: %d number of hours */
						echo sprintf( _n( '%d hour before', '%d hours before', $alert / 60, 'buddyboss-pro' ), $alert / 60 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} elseif ( $alert > 1 ) {
						/* translators: %d number of minutes */
						echo sprintf( _n( '%d minute before', '%d minutes before', $alert, 'buddyboss-pro' ), $alert ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						esc_html_e( 'Immediately before the webinar', 'buddyboss-pro' );
					}
					?>
				</div>
			</div>
		<?php } ?>

		<div class="single-webinar-item">
			<div class="webinar-item-head"><?php esc_html_e( 'Webinar Passcode', 'buddyboss-pro' ); ?></div>
			<div class="webinar-item-col">
				<?php if ( ! empty( bp_get_zoom_webinar_password() ) ) : ?>
					<div class="z-form-row-action">
						<div class="pass-wrap">
							<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
							<span class="show-password"><strong><?php echo esc_html( bp_get_zoom_webinar_password() ); ?></strong></span>
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
		$registration_url = bp_get_zoom_webinar_registration_url();
		if ( ! empty( $registration_url ) ) {
			?>
			<div class="single-webinar-item">
				<div class="webinar-item-head"><?php esc_html_e( 'Registration Link', 'buddyboss-pro' ); ?></div>
				<div class="webinar-item-col">
					<div class="copy-link-wrap">
						<a class="bb-registration-url" target="_blank" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
					</div>
				</div>
			</div>
			<?php
		}

		$join_url = bp_get_zoom_webinar_zoom_join_url();
		if ( ! empty( $join_url ) ) {
			?>
			<div class="single-webinar-item">
				<div class="webinar-item-head"><?php esc_html_e( 'Webinar Link', 'buddyboss-pro' ); ?></div>
				<div class="webinar-item-col">
					<div class="copy-link-wrap">
						<a class="bb-invitation-url" <?php echo ! bb_zoom_is_webinar_hide_urls_enabled() ? 'target="_blank"' : ''; ?> href="<?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, bp_get_zoom_webinar_id() ) ); ?>"><?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, bp_get_zoom_webinar_id() ) ); ?></a>
					</div>
				</div>
			</div>
		<?php } ?>
		<div class="single-webinar-item">
			<div class="webinar-item-head"><?php esc_html_e( 'Video', 'buddyboss-pro' ); ?></div>
			<div class="webinar-item-col">
				<div class="video-info-wrap">
					<span><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></span>
					<span class="info-status"><?php echo bp_get_zoom_webinar_host_video() ? esc_html__( ' On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="video-info-wrap">
					<span><?php esc_html_e( 'Panelists', 'buddyboss-pro' ); ?></span>
					<span class="info-status"><?php echo bp_get_zoom_webinar_panelists_video() ? esc_html__( 'On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
				</div>
			</div>
		</div>
		<div class="single-webinar-item">
			<div class="webinar-item-head"><?php esc_html_e( 'Webinar Options', 'buddyboss-pro' ); ?></div>
			<div class="webinar-item-col">
				<?php
				$bp_get_zoom_webinar_practice_session = bp_get_zoom_webinar_practice_session() ? 'yes' : 'no';
				$bp_get_zoom_webinar_authentication   = bp_get_zoom_webinar_authentication() ? 'yes' : 'no';
				$bp_get_zoom_webinar_auto_recording   = ( in_array( bp_get_zoom_webinar_auto_recording(), array( 'cloud', 'local' ), true ) ) ? 'yes' : 'no';
				?>
				<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_practice_session ); ?>">
					<i class="<?php echo bp_get_zoom_webinar_practice_session() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span><?php esc_html_e( 'Enable practice session', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_authentication ); ?>">
					<i class="<?php echo bp_get_zoom_webinar_authentication() ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_auto_recording ); ?>">
					<i class="<?php echo in_array( bp_get_zoom_webinar_auto_recording(), array( 'cloud', 'local' ), true ) ? esc_html( 'bb-icon-l bb-icon-check' ) : esc_html( 'bb-icon-l bb-icon-times' ); ?>"></i>
					<span>
						<?php
						if ( 'cloud' === bp_get_zoom_webinar_auto_recording() ) {
							esc_html_e( 'Record the webinar automatically in the cloud', 'buddyboss-pro' );
						} elseif ( 'local' === bp_get_zoom_webinar_auto_recording() ) {
							esc_html_e( 'Record the webinar automatically in the local computer', 'buddyboss-pro' );
						} else {
							esc_html_e( 'Do not record the webinar.', 'buddyboss-pro' );
						}
						?>
					</span>
				</div>
			</div>
		</div>

		<?php
		$occurrence_date_unix     = wp_date( 'U', strtotime( bp_get_zoom_webinar_start_date_utc() ), new DateTimeZone( 'UTC' ) );
		$webinar_is_started       = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) );
		$show_join_webinar_button = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ), new DateTimeZone( 'UTC' ) ) ) );

		$current_date     = wp_date( 'U' );
		$webinar_date_obj = new DateTime( bp_get_zoom_webinar_start_date_utc(), new DateTimeZone( 'UTC' ) );
		$webinar_date_obj->modify( '+' . bp_get_zoom_webinar_duration() . ' minutes' );
		$webinar_date_unix  = $webinar_date_obj->format( 'U' );
		$meeting_number     = esc_attr( bp_get_zoom_webinar_zoom_webinar_id() );
		$role               = bp_zoom_can_current_user_start_webinar( bp_get_zoom_webinar_id() ) ? 1 : 0; // phpcs:ignore
		$browser_credential = bb_zoom_group_generate_browser_credential(
			array(
				'group_id'       => bp_get_zoom_webinar_group_id(),
				'meeting_number' => $meeting_number,
				'role'           => $role,
			)
		);
		?>

		<?php if ( ! $webinar_is_started ) : ?>
			<div class="single-webinar-item bb-countdown-wrap">
				<div class="webinar-item-head"></div>
				<div class="webinar-item-col">
					<div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo esc_attr( $occurrence_date_unix ); ?>"></div>
				</div>
			</div>

		<?php endif; ?>

		<div class="single-webinar-item last-col webinar-buttons-wrap">
			<?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
				<div class="bb-recordings-wrap">
					<div class="webinar-item-head"></div>
					<div id="bp-zoom-webinar-recording-<?php echo esc_attr( bp_get_zoom_webinar_zoom_webinar_id() ); ?>" class="bp-zoom-webinar-recording-fetch" data-title="<?php echo esc_attr( bp_get_zoom_webinar_title() ); ?>" data-webinar-id="<?php echo esc_attr( bp_get_zoom_webinar_zoom_webinar_id() ); ?>">
						<?php
						if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
							set_query_var( 'recording_fetch', 'yes' );
						} else {
							set_query_var( 'recording_fetch', 'no' );
						}
						?>
						<?php bp_get_template_part( 'zoom/webinar/recordings' ); ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( 'started' === bp_get_zoom_webinar_current_status() || ( $show_join_webinar_button && $current_date < $webinar_date_unix ) ) : ?>
				<div class="webinar-item-col webinar-action last-col full text-right">
					<?php
					$can_host = bp_zoom_can_current_user_start_webinar( bp_get_zoom_webinar_id() );

					if (
						! empty( $browser_credential['sign'] ) &&
						! $can_host &&
						! bp_get_zoom_webinar_authentication() &&
						! bp_get_zoom_webinar_registration_url()
					) {
						?>
						<a href="#" data-webinar-id="<?php echo esc_attr( bp_get_zoom_webinar_zoom_webinar_id() ); ?>"
						data-webinar-pwd="<?php echo esc_attr( bp_get_zoom_webinar_password() ); ?>"
						data-is-host="<?php echo $can_host ? esc_attr( '1' ) : esc_attr( '0' ); ?>"
						data-meeting-sign="<?php echo esc_attr( $browser_credential['sign'] ); ?>" data-meeting-sdk="<?php echo esc_attr( $browser_credential['sdk_client_id'] ); ?>"
						class="button outline small join-webinar-in-browser">
							<?php esc_html_e( 'Join Webinar in Browser', 'buddyboss-pro' ); ?>
						</a>
						<?php
					}

					if (
						! bb_zoom_is_webinar_hide_urls_enabled() ||
						(
							$can_host ||
							(
								bp_get_zoom_webinar_authentication() ||
								bp_get_zoom_webinar_registration_url()
							)
						)
					) {
						?>
						<a type="button"
						class="button primary small join-webinar-in-app"
						target="_blank"
						href="<?php echo $can_host ? esc_url( bp_get_zoom_webinar_zoom_start_url() ) : esc_url( bp_get_zoom_webinar_zoom_join_url() ); ?>">
							<?php
							if ( $can_host ) {
								esc_html_e( 'Host Webinar in Zoom', 'buddyboss-pro' );
							} else {
								esc_html_e( 'Join Webinar in Zoom', 'buddyboss-pro' );
							}
							?>
						</a>
					<?php } ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
