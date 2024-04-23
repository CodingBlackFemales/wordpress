<?php
/**
 * BuddyBoss - Zoom Meeting Recordings
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

global $zoom_meeting_template;

if ( empty( $meeting_id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
	$meeting_id = bp_get_zoom_meeting_zoom_meeting_id();
}

if ( empty( $meeting_id ) ) {
	return;
}

$meeting_title = '';
if ( ! empty( $topic ) ) {
	$meeting_title = $topic;
}

$meeting = false;
if ( ! empty( $zoom_meeting_template->meeting->id ) ) {
	$m_id          = bp_get_zoom_meeting_id();
	$meeting_title = bp_get_zoom_meeting_title();
}

if ( ! empty( $m_id ) && ! empty( $meeting_id ) ) {
	$meeting_obj = new BP_Zoom_Meeting( $m_id );
	if ( ! empty( $meeting_obj ) && 'meeting_occurrence' === $meeting_obj->zoom_type ) {
		$m_id = false;
	}
}

if ( empty( $m_id ) && ! empty( $meeting_id ) ) {
	$meeting_row = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
	if ( ! empty( $meeting_row ) ) {
		$m_id = $meeting_row->id;
	}
}

if ( ! empty( $m_id ) ) {
	$meeting = new BP_Zoom_Meeting( $m_id );
	if ( empty( $meeting_title ) && ! empty( $meeting->title ) ) {
		$meeting_title = $meeting->title;
	}
}

if ( isset( $recording_fetch ) && 'yes' === $recording_fetch ) {
	bp_zoom_meeting_fetch_recordings( $meeting_id );
}

$recording_get_args = array(
	'meeting_id' => $meeting_id,
);

if ( ! empty( $occurrence_id ) ) {
	$occurrences = bp_zoom_meeting_get(
		array(
			'parent'  => $meeting_id,
			'sort'    => 'ASC',
			'orderby' => 'start_date_utc',
		)
	);

	if ( ! empty( $occurrences['meetings'] ) && 1 < count( $occurrences['meetings'] ) ) {
		$recording_start_date = false;
		$occurrence_index     = 0;
		foreach ( $occurrences['meetings'] as $occurrence ) {
			$occurrence_index++;

			if ( $occurrence_id === $occurrence->meeting_id ) {

				if ( 1 === $occurrence_index ) {
					if ( isset( $occurrences['meetings'][ $occurrence_index ]->start_date_utc ) ) {
						$occurrence_date     = new DateTime( $occurrence->start_date_utc, new DateTimeZone( 'UTC' ) );
						$occurrence_date_max = new DateTime( $occurrences['meetings'][ $occurrence_index ]->start_date_utc, new DateTimeZone( 'UTC' ) );
						$occurrence_interval = abs( round( ( strtotime( $occurrence_date_max->format( 'Y-m-d' ) ) - strtotime( $occurrence_date->format( 'Y-m-d' ) ) ) / 86400 ) );

						if ( $occurrence_interval >= 1 ) {
							$occurrence_date_max = $occurrence_date_max->modify( '-1 day' );
						}

						$recording_get_args['date_max'] = $occurrence_date_max->format( 'Y-m-d' ) . ' 23:59:59';
					} else {
						$occurrence_date                = new DateTime( $occurrence->start_date_utc, new DateTimeZone( 'UTC' ) );
						$recording_get_args['date_max'] = $occurrence_date->format( 'Y-m-d' ) . ' 23:59:59';
					}
				} elseif ( count( $occurrences['meetings'] ) <= $occurrence_index ) {
					$occurrence_date                = new DateTime( $occurrence->start_date_utc, new DateTimeZone( 'UTC' ) );
					$recording_get_args['date_min'] = $occurrence_date->format( 'Y-m-d' ) . ' 00:00:00';
				} else {
					$occurrence_date_min            = new DateTime( $occurrence->start_date_utc, new DateTimeZone( 'UTC' ) );
					$recording_get_args['date_min'] = $occurrence_date_min->format( 'Y-m-d' ) . ' 00:00:00';
					$occurrence_date_max            = new DateTime( $occurrences['meetings'][ $occurrence_index ]->start_date_utc, new DateTimeZone( 'UTC' ) );
					$occurrence_interval            = abs( round( ( strtotime( $occurrence_date_max->format( 'Y-m-d' ) ) - strtotime( $occurrence_date_min->format( 'Y-m-d' ) ) ) / 86400 ) );

					if ( $occurrence_interval >= 1 ) {
						$occurrence_date_max = $occurrence_date_max->modify( '-1 day' );
					}

					$recording_get_args['date_max'] = $occurrence_date_max->format( 'Y-m-d' ) . ' 23:59:59';
				}

				break;
			}
		}
	}
}

$recordings = bp_zoom_recording_get(
	array(),
	$recording_get_args
);

if ( ! empty( $m_id ) && empty( $occurrence_id ) ) {
	bp_zoom_meeting_update_meta( $m_id, 'zoom_recording_count', count( $recordings ) );
} elseif ( ! empty( $m_id ) && ! empty( $occurrence_id ) ) {
	$occurrence_obj = BP_Zoom_Meeting::get_meeting_by_meeting_id( $occurrence_id, $meeting_id );

	if ( ! empty( $occurrence_obj->id ) ) {
		bp_zoom_meeting_update_meta( $occurrence_obj->id, 'zoom_recording_count', count( $recordings ) );
	}
}

if ( empty( $recordings ) ) {
	return;
}

$recordings_groups = array();

foreach ( $recordings as $key => $item ) {
	if ( empty( $item->start_time ) ) {
		$recordings_groups[ $meeting->start_date_utc ][ $key ] = $item;
	} else {
		$recordings_groups[ $item->start_time ][ $key ] = $item;
	}
}

$recording_groups_dates       = array_keys( $recordings_groups );
$recording_groups_dates_print = array();
foreach ( $recording_groups_dates as $recording_groups_date ) {
	$recording_groups_dates_print[] = wp_date( 'Y-m-d', strtotime( $recording_groups_date ) );
}

$recording_groups_dates_print = array_unique( $recording_groups_dates_print );
?>
<a href="#bp-zoom-block-show-recordings-popup-<?php echo esc_attr( $meeting_id ); ?>" class="button small outline join-meeting-in-app show-recordings" data-meeting-id="<?php echo esc_attr( $meeting_id ); ?>"><?php esc_html_e( 'Show Recordings', 'buddyboss-pro' ); ?></a>

<div id="bp-zoom-block-show-recordings-popup-<?php echo esc_attr( $meeting_id ); ?>" class="bzm-white-popup bp-zoom-block-show-recordings mfp-hide">
	<header class="bb-zm-model-header">
		<span class="bp-meeting-title-recording-popup"><?php echo esc_attr( $meeting_title ); ?></span><?php esc_html_e( ' (Recordings)', 'buddyboss-pro' ); ?>
		<?php if ( count( $recording_groups_dates_print ) > 1 ) { ?>
			<select class="bp-zoom-recordings-dates">
				<?php
				foreach ( $recording_groups_dates_print as $recording_groups_dates_print_date ) {
					$recording_groups_dates_print_date_echo = new DateTime( $recording_groups_dates_print_date );
					$recording_groups_dates_print_date_echo = $recording_groups_dates_print_date_echo->format( bp_core_date_format() );
					?>
					<option value="<?php echo esc_attr( $recording_groups_dates_print_date ); ?>"><?php echo esc_html( $recording_groups_dates_print_date_echo ); ?></option>
					<?php
				}
				?>
			</select>
			<?php
		}
		?>
	</header>

	<div class="recording-list-row-wrap">
		<?php

		foreach ( $recordings_groups as $date => $recording_group ) {
			$recorded_date = wp_date( bp_core_date_format( false, true ), strtotime( $date ) ) . __( ' at ', 'buddyboss-pro' ) . wp_date( bp_core_date_format( true, false ), strtotime( $date ) );

			?>
			<div class="recording-list-row-group" data-recorded-date="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( $date ) ) ); ?>">
				<h4 class="clip_title"><?php echo esc_attr( $recorded_date ); ?></h4>
				<?php

				foreach ( $recording_group as $recording ) {
					$recorded_time       = '';
					$recording_file      = json_decode( $recording->details );
					$recording_type      = isset( $recording_file->recording_type ) ? $recording_file->recording_type : '';
					$recording_file_size = isset( $recording_file->file_size ) ? $recording_file->file_size : false;

					if ( 'TIMELINE' === $recording_file->file_type ) {
						continue;
					}

					if ( ! empty( $recording_file->recording_start ) && ! empty( $recording_file->recording_end ) ) {
						$datetime1     = date_create( $recording_file->recording_start );
						$datetime2     = date_create( $recording_file->recording_end );
						$interval      = date_diff( $datetime1, $datetime2 );
						$recorded_time = $interval->format( '%H:%I:%S' );
					}
					?>

					<div class="recording-list-row" data-recording-id="<?php echo esc_attr( $recording->id ); ?>">
						<div class="recording-preview-img">
							<span class="<?php echo ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) ? 'bb-icon-l bb-icon-play triangle-play-icon' : ''; ?> <?php echo esc_attr( $recording_type ); ?>"></span>
							<?php if ( in_array( $recording_type, array( 'shared_screen_with_speaker_view', 'shared_screen_with_gallery_view', 'active_speaker', 'shared_screen', 'shared_screen_with_speaker_view(CC)', 'gallery_view' ), true ) ) : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-video.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php elseif ( 'audio_only' === $recording_type ) : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-audio-only.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php elseif ( 'audio_transcript' === $recording_type ) : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-audio-transcript.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php elseif ( 'chat_file' === $recording_type ) : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-chat-file.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php elseif ( 'TIMELINE' === $recording_type || 'TIMELINE' === $recording_file->file_type ) : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-timeline.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php else : ?>
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/recording-audio-only.png' ) ); ?>" alt="<?php echo esc_attr( $recording_type ); ?>"/>
							<?php endif; ?>

							<?php
							if ( ! empty( $recorded_time ) && ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) ) {
								echo '<span class="bb-video-time">' . esc_html( $recorded_time ) . '</span>';
							}
							?>
							<div class="video_link">
								<?php if ( ! in_array( $recording_file->file_type, array( 'TIMELINE', 'TRANSCRIPT', 'CHAT', 'CC' ), true ) ) : ?>
									<a class="play_btn" href="#"><?php esc_html_e( 'Play', 'buddyboss-pro' ); ?></a>
								<?php endif; ?>
							</div>
						</div>

						<div class="recording-preview-info">
							<div class="recording-list-info">
								<h2 class="clip_title">
									<?php
									if ( in_array( $recording_type, array( 'shared_screen_with_speaker_view', 'shared_screen_with_gallery_view', 'active_speaker', 'shared_screen', 'shared_screen_with_speaker_view(CC)', 'gallery_view' ), true ) ) {
										esc_html_e( 'Video Recording', 'buddyboss-pro' );
									} elseif ( 'audio_only' === $recording_type ) {
										esc_html_e( 'Audio Recording', 'buddyboss-pro' );
									} elseif ( 'chat_file' === $recording_type ) {
										esc_html_e( 'Chat File', 'buddyboss-pro' );
									} elseif ( 'audio_transcript' === $recording_type ) {
										esc_html_e( 'Audio Transcript', 'buddyboss-pro' );
									} elseif ( 'TIMELINE' === $recording_type || 'TIMELINE' === $recording_file->file_type ) {
										esc_html_e( 'Timeline', 'buddyboss-pro' );
									}
									?>
								</h2>
								<?php if ( ! empty( $recording_file_size ) ) : ?>
									<div class="clip_description">
										<?php echo esc_html( bp_core_format_size_units( $recording_file_size, true ) ); ?>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $recording->password ) ) : ?>
									<div class="pass-toggle">
										<a href="#" class="toggle-password show-pass">
											<i class="bb-icon-l bb-icon-eye"></i><?php esc_html_e( 'Show password', 'buddyboss-pro' ); ?>
										</a>
										<span class="show-password bp-hide"><a href="#" class="toggle-password hide-pass"><i class="bb-icon-l bb-icon-eye-slash"></i></a><span class="recording-password"><?php echo esc_html( $recording->password ); ?></span></span>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( bp_zoom_is_zoom_recordings_links_enabled() ) : ?>
								<div class="recording-button-wrap">
									<?php if ( ! in_array( $recording_file->file_type, array( 'TIMELINE', 'TRANSCRIPT', 'CHAT', 'CC' ), true ) && ! empty( $recording_file->play_url ) ) : ?>
										<a href="#" id="copy-download-link" class="button small outline bb-copy-link" data-download-link="<?php echo esc_url( bp_zoom_get_recording_rewrite_url( $recording_file->play_url, $recording->id ) ); ?>" data-copied="<?php esc_html_e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><i class="bb-icon-l bb-icon-duplicate"></i><?php esc_html_e( 'Copy Link', 'buddyboss-pro' ); ?></a>
									<?php endif; ?>
									<?php if ( ! empty( $recording_file->download_url ) ) : ?>
										<a href="<?php echo esc_url( bp_zoom_get_recording_rewrite_url( $recording_file->download_url, $recording->id, true ) ); ?>" class="button small outline downloadmeeting downloadclip"><i class="bb-icon-l bb-icon-download"></i><?php esc_html_e( 'Download', 'buddyboss-pro' ); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php
						if ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) :
							$recording_access_token = bb_zoom_recording_get_access_token( $meeting_id );
							?>
							<div class="bb-media-model-wrapper bb-internal-model" style="display: none;">

								<a data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>" class="bb-close-media-theatre bb-close-model" href="#">
									<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14">
										<path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7"/>
									</svg>
								</a>

								<div class="bb-media-model-container">
									<div class="bb-media-model-inner">
										<div class="bb-media-section">
											<?php if ( 'MP4' === $recording_file->file_type ) : ?>
												<video controls>
													<source src="<?php echo esc_url( $recording_file->download_url . '?access_token=' . $recording_access_token ); ?>"
															type="video/mp4">
													<p><?php esc_html_e( 'Your browser does not support HTML5 video.', 'buddyboss-pro' ); ?></p>
												</video>
											<?php endif; ?>
											<?php if ( 'M4A' === $recording_file->file_type ) : ?>
												<audio controls>
													<source src="<?php echo esc_url( $recording_file->download_url . '?access_token=' . $recording_access_token ); ?>"
															type="audio/mp4">
													<p><?php esc_html_e( 'Your browser does not support HTML5 audio.', 'buddyboss-pro' ); ?></p>
												</audio>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<?php
				}

				?>
			</div>
			<?php
		}
		?>
	</div>
</div>
