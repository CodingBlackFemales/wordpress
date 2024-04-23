<?php
/**
 * BuddyBoss - Zoom Activity Webinar Entry
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.9
 */

$url = false;
if ( bp_get_zoom_webinar_group_id() && bp_is_active( 'groups' ) ) {
	$group_link = bp_get_group_permalink( groups_get_group( bp_get_zoom_webinar_group_id() ) );
	$url        = trailingslashit( $group_link . 'zoom/webinars/' . bp_get_zoom_webinar_id() );
}

$utc_date_time = bp_get_zoom_webinar_start_date_utc();
if ( bp_get_zoom_webinar_recurring() ) {
	$occurrence_utc_date_time = bp_zoom_get_webinar_first_occurrence_date_utc( bp_get_zoom_webinar_id() );
	if ( ! empty( $occurrence_utc_date_time ) ) {
		$utc_date_time = $occurrence_utc_date_time;
	}
}

$current_date             = wp_date( 'U' );
$occurrence_date_unix     = wp_date( 'U', strtotime( $utc_date_time ), new DateTimeZone( 'UTC' ) );
$webinar_is_started       = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) );
$show_join_webinar_button = ! ( ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ), new DateTimeZone( 'UTC' ) ) ) );
$date                     = wp_date( bp_core_date_format(), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_webinar_timezone() ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_webinar_timezone() ) );

$webinar_date_obj = new DateTime( $utc_date_time );
$webinar_date_obj->setTimezone( wp_timezone() );
$webinar_date_obj->modify( '+' . bp_get_zoom_webinar_duration() . ' minutes' );
$webinar_date_unix = $webinar_date_obj->format( 'U' );
?>
<div class="zoom-webinar-block">
	<div class="zoom-webinar-block-info">
		<a href="<?php echo $url ? esc_url( $url ) : ''; ?>"><h2><?php bp_zoom_webinar_title(); ?></h2></a>
		<div class="bb-webinar-date zoom-webinar_date"><?php echo esc_html( $date ) . ( ! empty( bp_get_zoom_webinar_timezone() ) ? ' (' . esc_html( bp_zoom_get_timezone_label( bp_get_zoom_webinar_timezone() ) ) . ')' : '' ); ?></div>
		<?php if ( bp_get_zoom_webinar_recurring() ) : ?>
			<div class="bb-webinar-occurrence"><?php echo esc_html( bp_zoom_get_webinar_recurrence_label( bp_get_zoom_webinar_id() ) ); ?></div>
		<?php endif; ?>
		<div class="bp-zoom-block-show-details">
			<a href="#bp-zoom-block-show-details-popup-<?php bp_zoom_webinar_zoom_webinar_id(); ?>" class="show-webinar-details">
				<span class="bb-icon-l bb-icon-calendar"></span> <?php esc_html_e( 'Webinar Details', 'buddyboss-pro' ); ?>
			</a>
		</div>
		<div id="bp-zoom-block-show-details-popup-<?php bp_zoom_webinar_zoom_webinar_id(); ?>" class="bzm-white-popup bp-zoom-block-show-details mfp-hide">
			<header class="bb-zm-model-header">
				<span><?php bp_zoom_webinar_title(); ?></span>
				<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			</header>
			<div id="bp-zoom-single-webinar" class="webinar-item webinar-item-table single-webinar-item-table">
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Date and Time', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php echo esc_html( $date ) . ( ! empty( bp_get_zoom_webinar_timezone() ) ? ' (' . esc_html( bp_zoom_get_timezone_label( bp_get_zoom_webinar_timezone() ) ) . ')' : '' ); ?>
					</div>
				</div>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar ID', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<span class="webinar-id"><?php bp_zoom_webinar_zoom_webinar_id(); ?></span>
					</div>
				</div>
				<?php if ( ! empty( bp_get_zoom_webinar_description() ) ) { ?>
					<div class="single-webinar-item">
						<div class="webinar-item-head"><?php esc_html_e( 'Description', 'buddyboss-pro' ); ?></div>
						<div class="webinar-item-col"><?php echo nl2br( bp_get_zoom_webinar_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					</div>
					<?php
				}
				$duration = bp_get_zoom_webinar_duration();
				$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
				$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
				?>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
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
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar Password', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php if ( ! empty( bp_get_zoom_webinar_password() ) ) : ?>
							<div class="z-form-row-action">
								<div class="pass-wrap">
									<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
									<span class="show-password"><strong><?php bp_zoom_webinar_password(); ?></strong></span>
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
				?>
				<?php $join_url = bp_get_zoom_webinar_zoom_join_url(); ?>
				<?php if ( ! empty( $join_url ) ) { ?>
					<div class="single-webinar-item">
						<div class="webinar-item-head"><?php esc_html_e( 'Webinar Link', 'buddyboss-pro' ); ?></div>
						<div class="webinar-item-col">
							<div class="copy-link-wrap">
								<a class="bb-invitation-url" target="_blank" href="<?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, bp_get_zoom_webinar_id() ) ); ?>"><?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, bp_get_zoom_webinar_id() ) ); ?></a>
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
							<span><?php esc_html_e( 'Participant', 'buddyboss-pro' ); ?></span>
							<span class="info-status"><?php echo bp_get_zoom_webinar_panelists_video() ? esc_html__( 'On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
						</div>
					</div>
				</div>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar Options', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php
						$bp_get_zoom_webinar_practice_session = bp_get_zoom_webinar_practice_session() ? 'yes' : 'no';
						$bp_get_zoom_webinar_authentication   = ! empty( bp_get_zoom_webinar_authentication() ) ? 'yes' : 'no';
						$bp_get_zoom_webinar_auto_recording   = ( in_array( bp_get_zoom_webinar_auto_recording(), array( 'cloud', 'local' ), true ) ) ? 'yes' : 'no';
						?>
						<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_practice_session ); ?>">
							<i class="<?php echo bp_get_zoom_webinar_practice_session() ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_authentication ); ?>">
							<i class="<?php echo ! empty( bp_get_zoom_webinar_authentication() ) ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
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
			</div>
		</div>

	</div>

	<div class="zoom-webinar-block-right">
		<?php if ( ! $webinar_is_started ) : ?>
			<div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo esc_attr( $occurrence_date_unix ); ?>"></div>
		<?php endif; ?>
		<?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
			<div id="bp-zoom-webinar-recording-<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-title="<?php bp_zoom_webinar_title(); ?>" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" class="bp-zoom-webinar-recording-fetch">
				<?php set_query_var( 'recording_fetch', 'no' ); ?>
				<?php set_query_var( 'webinar_id', bp_get_zoom_webinar_zoom_webinar_id() ); ?>
				<?php set_query_var( 'topic', bp_get_zoom_webinar_title() ); ?>
				<?php
				if ( 'webinar_occurrence' === bp_get_zoom_webinar_zoom_type() ) {
					set_query_var( 'occurrence_id', bp_get_zoom_webinar_occurrence_id() );
				}
				?>
				<?php bp_get_template_part( 'zoom/webinar/recordings' ); ?>
			</div>
		<?php endif; ?>
		<?php
		if ( 'started' === bp_get_zoom_webinar_current_status() || ( $show_join_webinar_button && $current_date < $webinar_date_unix ) ) :
			$webinar_number     = esc_attr( bp_get_zoom_webinar_zoom_webinar_id() );
			$role               = bp_zoom_can_current_user_start_webinar( bp_get_zoom_webinar_id() ) ? 1 : 0;  // phpcs:ignore
			$browser_credential = bb_zoom_group_generate_browser_credential(
				array(
					'group_id'       => bp_get_zoom_webinar_group_id(),
					'meeting_number' => $webinar_number,
					'role'           => $role,
				)
			);
			?>
			<div class="webinar-actions">
				<?php
				$can_host = bp_zoom_can_current_user_start_webinar( bp_get_zoom_webinar_id() );

				if (
					! empty( $browser_credential['sign'] ) &&
					! $can_host &&
					! bp_get_zoom_webinar_authentication() &&
					! bp_get_zoom_webinar_registration_url()
				) {
					?>
					<a href="#" class="button small outline join-webinar-in-browser" data-webinar-id="<?php bp_zoom_webinar_zoom_webinar_id(); ?>" data-webinar-pwd="<?php bp_zoom_webinar_password(); ?>" data-is-host="<?php echo $can_host ? esc_attr( '1' ) : esc_attr( '0' ); ?>" data-meeting-sign="<?php echo esc_attr( $browser_credential['sign'] ); ?>" data-meeting-sdk="<?php echo esc_attr( $browser_credential['sdk_client_id'] ); ?>">
						<?php esc_html_e( 'Join Webinar in Browser', 'buddyboss-pro' ); ?>
					</a>
					<?php
				}

				if (
					! bb_zoom_is_webinar_hide_urls_enabled() ||
					(
						$can_host ||
						( bp_get_zoom_webinar_authentication() || bp_get_zoom_webinar_registration_url() )
					)
				) {
					?>
					<a class="button small primary join-webinar-in-app" target="_blank" href="<?php echo $can_host ? esc_url( bp_get_zoom_webinar_zoom_start_url() ) : esc_url( bp_get_zoom_webinar_zoom_join_url() ); ?>">
						<?php if ( $can_host ) : ?>
							<?php esc_html_e( 'Host Webinar in Zoom', 'buddyboss-pro' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Join Webinar in Zoom', 'buddyboss-pro' ); ?>
						<?php endif; ?>
					</a>
				<?php } ?>
			</div>
		<?php endif; ?>
	</div>
</div>
