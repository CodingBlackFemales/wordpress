<?php
/**
 * BuddyBoss - Webinar Block Front end
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since   1.0.9
 */

global $bp_zoom_webinar_block;

if ( empty( $bp_zoom_webinar_block ) ) {
	return;
}

$webinar_id             = ! empty( $bp_zoom_webinar_block->id ) ? $bp_zoom_webinar_block->id : '';
$topic                  = ! empty( $bp_zoom_webinar_block->topic ) ? $bp_zoom_webinar_block->topic : '';
$agenda                 = ! empty( $bp_zoom_webinar_block->agenda ) ? $bp_zoom_webinar_block->agenda : '';
$duration               = ! empty( $bp_zoom_webinar_block->duration ) ? $bp_zoom_webinar_block->duration : '0';
$host_id                = ! empty( $bp_zoom_webinar_block->host_id ) ? $bp_zoom_webinar_block->host_id : '';
$alt_hosts              = ! empty( $bp_zoom_webinar_block->settings->alternative_hosts ) ? $bp_zoom_webinar_block->settings->alternative_hosts : '';
$password               = ! empty( $bp_zoom_webinar_block->password ) ? $bp_zoom_webinar_block->password : '';
$start_time             = ! empty( $bp_zoom_webinar_block->start_time ) ? $bp_zoom_webinar_block->start_time : 'now';
$timezone               = ! empty( $bp_zoom_webinar_block->timezone ) ? bb_zoom_get_server_allowed_timezone( $bp_zoom_webinar_block->timezone ) : 'UTC';
$start_url              = ! empty( $bp_zoom_webinar_block->start_url ) ? $bp_zoom_webinar_block->start_url : '';
$join_url               = ! empty( $bp_zoom_webinar_block->join_url ) ? $bp_zoom_webinar_block->join_url : '';
$registration_url       = ! empty( $bp_zoom_webinar_block->registration_url ) ? $bp_zoom_webinar_block->registration_url : '';
$host_video             = ! empty( $bp_zoom_webinar_block->settings->host_video ) ? $bp_zoom_webinar_block->settings->host_video : false;
$panelists_video        = ! empty( $bp_zoom_webinar_block->settings->panelists_video ) ? $bp_zoom_webinar_block->settings->panelists_video : false;
$practice_session       = ! empty( $bp_zoom_webinar_block->settings->practice_session ) ? $bp_zoom_webinar_block->settings->practice_session : false;
$on_demand              = ! empty( $bp_zoom_webinar_block->settings->on_demand ) ? $bp_zoom_webinar_block->settings->on_demand : false;
$webinar_authentication = ! empty( $bp_zoom_webinar_block->settings->meeting_authentication ) ? $bp_zoom_webinar_block->settings->meeting_authentication : false;
$auto_recording         = ! empty( $bp_zoom_webinar_block->settings->auto_recording ) ? $bp_zoom_webinar_block->settings->auto_recording : 'none';
$can_start_webinar      = false;
$occurrences            = ! empty( $bp_zoom_webinar_block->occurrences ) ? $bp_zoom_webinar_block->occurrences : array();
$recurring              = isset( $bp_zoom_webinar_block->type ) && 9 === $bp_zoom_webinar_block->type;
$recurrence             = ! empty( $bp_zoom_webinar_block->recurrence ) ? $bp_zoom_webinar_block->recurrence : false;
$webinar_status         = ! empty( $bp_zoom_webinar_block->status ) ? $bp_zoom_webinar_block->status : '';
$block_class_name       = isset( $bp_zoom_webinar_block->block_class_name ) ? $bp_zoom_webinar_block->block_class_name : '';


if ( is_user_logged_in() ) {
	$current_userdata = get_userdata( get_current_user_id() );

	if ( ! empty( $current_userdata ) ) {
		$api_email = bb_zoom_account_email();
		if ( $api_email === $current_userdata->user_email ) {
			$can_start_webinar = true;
		} elseif ( in_array( $current_userdata->user_email, explode( ',', $alt_hosts ), true ) ) {
			$can_start_webinar = true;
		} else {
			$userinfo = get_transient( 'bp_zoom_user_info_' . $host_id );

			if ( empty( $userinfo ) ) {
				$userinfo = bp_zoom_conference()->get_user_info( $host_id );
				if ( 200 === $userinfo['code'] && ! empty( $userinfo['response'] ) ) {
					set_transient( 'bp_zoom_user_info_' . $host_id, wp_json_encode( $userinfo['response'] ), HOUR_IN_SECONDS );
					$userinfo = $userinfo['response'];
				}
			} else {
				$userinfo = json_decode( $userinfo );
			}

			if ( ! empty( $userinfo ) && isset( $userinfo->email ) && $current_userdata->user_email === $userinfo->email ) {
				$can_start_webinar = true;
			}
		}
	}
}

$meeting_number = esc_attr( $webinar_id );
$role           = $can_start_webinar ? 1 : 0; // phpcs:ignore

$api_key       = '';
$api_secret    = '';
$sdk_client_id = '';
$sign          = '';
if ( bb_zoom_is_meeting_sdk() ) {
	$api_key       = bb_zoom_sdk_client_id();
	$api_secret    = bb_zoom_sdk_client_secret();
	$sdk_client_id = $api_key;
}

if ( ! empty( $api_key ) && ! empty( $api_secret ) && ! empty( $meeting_number ) ) {
	$sign = bb_get_meeting_signature( $api_key, $api_secret, $meeting_number, $role );
}

$webinar_date_raw   = false;
$webinar_is_started = false;
$current_webinar    = false;

if ( $recurring && ! empty( $occurrences ) ) {
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			continue;
		}

		$occurrence_date_obj = new DateTime( $occurrence->start_time );
		$occurrence_date_obj->modify( '+' . $occurrence->duration . ' minutes' );
		$occurrence_date_obj->setTimezone( wp_timezone() );
		$occurrence_date_unix = $occurrence_date_obj->format( 'U' );

		if ( wp_date( 'U' ) < $occurrence_date_unix ) {
			$start_time = $occurrence->start_time;
			$duration   = $occurrence->duration;
			break;
		}
	}
}

$occurrence_date          = new DateTime( $start_time );
$occurrence_date_unix     = $occurrence_date->format( 'U' );
$webinar_is_started       = ( $occurrence_date_unix > wp_date( 'U', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) ? false : true;
$show_join_webinar_button = ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ), new DateTimeZone( 'UTC' ) ) ) ? false : true;
$current_date             = wp_date( 'U' );

$occurrence_date->setTimezone( wp_timezone() );
$occurrence_date->modify( '+' . $duration . ' minutes' );
$webinar_date_unix = $occurrence_date->format( 'U' );
$date              = wp_date( bp_core_date_format( false, true ), strtotime( $start_time ), new DateTimeZone( $timezone ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $start_time ), new DateTimeZone( $timezone ) );
?>

<div class="zoom-webinar-block <?php echo esc_attr( $block_class_name ); ?>">
	<div class="zoom-webinar-block-info">
		<h2 id="bp-zoom-webinar-block-title-<?php echo esc_attr( $webinar_id ); ?>">
			<?php echo esc_html( $topic ); ?>
			<?php if ( $recurring ) : ?>
				<span class="recurring-webinar-label"><?php esc_html_e( 'Recurring', 'buddyboss-pro' ); ?></span>
			<?php endif; ?>
			<?php if ( 'started' === $webinar_status ) : ?>
				<span class="live-webinar-label"><?php esc_html_e( 'Live', 'buddyboss-pro' ); ?></span>
			<?php endif; ?>
		</h2>
		<div class="bb-webinar-date zoom-webinar_date"><?php echo esc_html( $date ) . ( ! empty( $timezone ) ? ' (' . esc_html( bp_zoom_get_timezone_label( $timezone ) ) . ')' : '' ); ?></div>
		<?php if ( $recurring ) : ?>
			<div class="bb-webinar-occurrence"><?php echo esc_html( bp_zoom_get_recurrence_label( $bp_zoom_webinar_block->id, $bp_zoom_webinar_block ) ); ?></div>
		<?php endif; ?>
		<div class="bp-zoom-block-show-details">
			<a href="#bp-zoom-block-show-details-popup-<?php echo esc_attr( $webinar_id ); ?>" class="show-webinar-details">
				<span class="bb-icon-l bb-icon-calendar"></span> <?php esc_html_e( 'Webinar Details', 'buddyboss-pro' ); ?>
			</a>
		</div>
		<div id="bp-zoom-block-show-details-popup-<?php echo esc_attr( $webinar_id ); ?>" class="bzm-white-popup bp-zoom-block-show-details mfp-hide">
			<header class="bb-zm-model-header"><?php echo esc_html( $topic ); ?>
				<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			</header>
			<div id="bp-zoom-single-webinar" class="webinar-item webinar-item-table single-webinar-item-table">
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Date and Time', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php echo esc_html( $date ) . ( ! empty( $timezone ) ? ' (' . esc_html( bp_zoom_get_timezone_label( $timezone ) ) . ')' : '' ); ?>
					</div>
				</div>
				<?php if ( $recurring ) : ?>
					<div class="single-webinar-item">
						<div class="webinar-item-head"><?php esc_html_e( 'Occurrences', 'buddyboss-pro' ); ?></div>
						<div class="webinar-item-col">
							<?php echo esc_html( bp_zoom_get_webinar_recurrence_label( $bp_zoom_webinar_block->id, $bp_zoom_webinar_block ) ); ?>
						</div>
					</div>
				<?php endif; ?>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar ID', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<span class="webinar-id"><?php echo esc_html( $webinar_id ); ?></span>
					</div>
				</div>
				<?php if ( ! empty( $agenda ) ) { ?>
					<div class="single-webinar-item">
						<div class="webinar-item-head"><?php esc_html_e( 'Description', 'buddyboss-pro' ); ?></div>
						<div class="webinar-item-col"><?php echo nl2br( $agenda ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					</div>
					<?php
				}
				$hours   = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
				$minutes = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
				?>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php
						if ( 0 < $hours ) {
							/* translators: %d is number of hours. */
							echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						if ( 0 < $minutes ) {
							/* translators: %d is number of minutes. */
							echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar Passcode', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php if ( ! empty( $password ) ) : ?>
							<div class="z-form-row-action">
								<div class="pass-wrap">
									<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
									<span class="show-password"><strong><?php echo esc_html( $password ); ?></strong></span>
								</div>
								<div class="pass-toggle">
									<a href="javascript:;" class="toggle-password show-pass on">
										<i class="bb-icon-l bb-icon-eye"></i><?php esc_html_e( 'Show passcode', 'buddyboss-pro' ); ?>
									</a>
									<a href="javascript:;" class="toggle-password hide-pass">
										<i class="bb-icon-l bb-icon-eye-slash"></i><?php esc_html_e( 'Hide passcode', 'buddyboss-pro' ); ?>
									</a>
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
				<?php if ( ! empty( $join_url ) ) { ?>
					<div class="single-webinar-item">
						<div class="webinar-item-head"><?php esc_html_e( 'Webinar Link', 'buddyboss-pro' ); ?></div>
						<div class="webinar-item-col">
							<div class="copy-link-wrap">
								<a class="bb-invitation-url" target="_blank" href="<?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, 0, $webinar_id ) ); ?>"><?php echo esc_url( bp_zoom_get_webinar_rewrite_url( $join_url, 0, $webinar_id ) ); ?></a>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Video', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<div class="video-info-wrap">
							<span><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></span>
							<span class="info-status"><?php echo $host_video ? esc_html__( ' On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="video-info-wrap">
							<span><?php esc_html_e( 'Panelists', 'buddyboss-pro' ); ?></span>
							<span class="info-status"><?php echo $panelists_video ? esc_html__( 'On', 'buddyboss-pro' ) : esc_html__( 'Off', 'buddyboss-pro' ); ?></span>
						</div>
					</div>
				</div>
				<div class="single-webinar-item">
					<div class="webinar-item-head"><?php esc_html_e( 'Webinar Options', 'buddyboss-pro' ); ?></div>
					<div class="webinar-item-col">
						<?php
						$bp_get_zoom_webinar_pratice_session = $practice_session ? 'yes' : 'no';
						$bp_get_zoom_webinar_authentication  = $webinar_authentication ? 'yes' : 'no';
						$bp_get_zoom_webinar_auto_recording  = ( in_array( $auto_recording, array( 'cloud', 'local' ), true ) ) ? 'yes' : 'no';
						?>
						<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_pratice_session ); ?>">
							<i class="<?php echo $practice_session ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Enable practice session', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_authentication ); ?>">
							<i class="<?php echo $webinar_authentication ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
						</div>
						<div class="bb-webinar-option <?php echo esc_attr( $bp_get_zoom_webinar_auto_recording ); ?>">
							<i class="<?php echo in_array( $auto_recording, array( 'cloud', 'local' ), true ) ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-times'; ?>"></i>
							<span>
								<?php
								if ( 'cloud' === $auto_recording ) {
									esc_html_e( 'Record the webinar automatically in the cloud', 'buddyboss-pro' );
								} elseif ( 'local' === $auto_recording ) {
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
		<?php
		if ( bp_zoom_is_zoom_recordings_enabled() ) :
			?>
			<div id="bp-zoom-webinar-recording-<?php echo esc_attr( $webinar_id ); ?>" data-title="<?php echo esc_attr( $topic ); ?>"
					data-webinar-id="<?php echo esc_attr( $webinar_id ); ?>" data-zoom-block="<?php echo get_the_ID(); ?>" class="bp-zoom-webinar-recording-fetch">
				<?php set_query_var( 'recording_fetch', 'no' ); ?>
				<?php set_query_var( 'webinar_id', $webinar_id ); ?>
				<?php set_query_var( 'topic', $topic ); ?>
				<?php bp_get_template_part( 'zoom/webinar/recordings' ); ?>
			</div>
		<?php endif; ?>
		<div class="webinar-actions <?php echo 'started' === $webinar_status || ( $show_join_webinar_button && $current_date < $webinar_date_unix ) ? '' : 'bp-hide'; ?>">
			<?php if ( ! empty( $sign ) && ! $can_start_webinar && empty( $registration_url ) && empty( $webinar_authentication ) ) : ?>
				<a href="#" class="button small outline join-webinar-in-browser" data-webinar-id="<?php echo esc_attr( $webinar_id ); ?>" data-webinar-pwd="<?php echo esc_attr( $password ); ?>" data-is-host="<?php echo $can_start_webinar ? esc_attr( '1' ) : esc_attr( '0' ); ?>" data-meeting-sign="<?php echo esc_attr( $sign ); ?>" data-meeting-sdk="<?php echo esc_attr( $sdk_client_id ); ?>">
					<?php esc_html_e( 'Join Webinar in Browser', 'buddyboss-pro' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! bb_zoom_is_webinar_hide_urls_enabled() || ( $can_start_webinar || ( ! $can_start_webinar && ( empty( $registration_url ) || empty( $webinar_authentication ) ) ) ) ) : ?>
				<a class="button small primary join-webinar-in-app" target="_blank" href="<?php echo $can_start_webinar ? esc_url( $start_url ) : esc_url( $join_url ); ?>">
					<?php if ( $can_start_webinar ) : ?>
						<?php esc_html_e( 'Host Webinar in Zoom', 'buddyboss-pro' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Join Webinar in Zoom', 'buddyboss-pro' ); ?>
					<?php endif; ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
