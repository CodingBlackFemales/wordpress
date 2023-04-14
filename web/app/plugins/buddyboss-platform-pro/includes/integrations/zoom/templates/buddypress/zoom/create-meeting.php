<?php
/**
 * BuddyBoss - Create Meeting
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

?>
<div class="bb-title-wrap">
	<h2 class="bb-title"><?php esc_html_e( 'Create Meeting', 'buddyboss-pro' ); ?></h2>
	<a href="#" class="bp-close-create-meeting-form"><span class="bb-icon-l bb-icon-times"></span></a>
</div>
<?php
$group_id         = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );
$current_group_id = bp_is_group() ? bp_get_current_group_id() : false;
if ( ! empty( $current_group_id ) ) {
	$group_id = $current_group_id;
}

if ( ! bp_zoom_is_group_setup( $group_id ) ) {
	$group_link         = bp_get_group_permalink( groups_get_group( $group_id ) );
	$zoom_settings_link = trailingslashit( $group_link . 'admin/zoom' );
	?>
	<div class="bp-feedback error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			printf(
				/* translators: %s is settings link */
				esc_html__( 'This group does not have Zoom properly configured. Please update the %s.', 'buddyboss-pro' ),
				'<a href="' . esc_url( $zoom_settings_link ) . '">' . esc_html__( 'Zoom settings', 'buddyboss-pro' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php
	return false;
}

$current_user_data = get_userdata( get_current_user_id() );
if ( empty( $current_user_data ) ) {
	return false;
}

$default_group_host_email = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );

$disable_registration = false;
$disable_recording    = false;
$disable_alt_host     = false;
$host_type            = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-type', true );
if ( 1 === (int) $host_type ) {
	$disable_registration = true;
	$disable_recording    = true;
	$disable_alt_host     = true;
}
?>
<div class="bp-meeting-fields-wrap">
	<div class="bb-field-wrapper">
		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-title"><?php esc_html_e( 'Meeting Title', 'buddyboss-pro' ); ?> *</label>
				<div class="bb-meeting-input-wrap">
					<input autocomplete="off" type="text" id="bp-zoom-meeting-title" value="" name="bp-zoom-meeting-title" />
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-description"><?php esc_html_e( 'Description (optional)', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap">
					<textarea id="bp-zoom-meeting-description" name="bp-zoom-meeting-description"></textarea>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-password"><?php esc_html_e( 'Passcode (optional)', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap bp-toggle-meeting-password-wrap">
					<a href="#" class="bp-toggle-meeting-password"><i class="bb-icon-l bb-icon-eye"></i><i class="bb-icon-l bb-icon-eye-slash"></i></a>
					<input autocomplete="new-password" type="password" id="bp-zoom-meeting-password" value="" name="bp-zoom-meeting-password" />
				</div>
			</div>
		</div>

		<hr />

		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-start-date"><?php esc_html_e( 'When', 'buddyboss-pro' ); ?> *</label>
				<div class="bp-wrap-duration bb-meeting-input-wrap">
					<div class="bb-field-wrap start-date-picker">
						<input type="text" id="bp-zoom-meeting-start-date" value="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( 'now' ) ) ); ?>" name="bp-zoom-meeting-start-date" placeholder="yyyy-mm-dd" autocomplete="off" />
					</div>
					<div class="bb-field-wrap start-time-picker">
						<?php
						$pending_minutes = 60 - wp_date( 'i', strtotime( 'now' ) );
						$current_minutes = strtotime( '+ ' . $pending_minutes . ' minutes' );
						?>
						<input type="text" id="bp-zoom-meeting-start-time" name="bp-zoom-meeting-start-time" autocomplete="off" placeholder="hh:mm" value="<?php echo esc_attr( wp_date( 'h:i', $current_minutes ) ); ?>" autocomplete="off" />
					</div>
					<div class="bb-field-wrap bp-zoom-meeting-time-meridian-wrap">
						<label for="bp-zoom-meeting-start-time-meridian-am">
							<input type="radio" value="am" id="bp-zoom-meeting-start-time-meridian-am" name="bp-zoom-meeting-start-time-meridian" <?php checked( 'AM', wp_date( 'A', $current_minutes ) ); ?>>
							<span class="bb-time-meridian"><?php esc_html_e( 'AM', 'buddyboss-pro' ); ?></span>
						</label>
						<label for="bp-zoom-meeting-start-time-meridian-pm">
							<input type="radio" value="pm" id="bp-zoom-meeting-start-time-meridian-pm" name="bp-zoom-meeting-start-time-meridian" <?php checked( 'PM', wp_date( 'A', $current_minutes ) ); ?>>
							<span class="bb-time-meridian"><?php esc_html_e( 'PM', 'buddyboss-pro' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-duration"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?> *</label>
				<div class="bp-wrap-duration bb-meeting-input-wrap">
					<div class="bb-field-wrap">
						<select id="bp-zoom-meeting-duration-hr" name="bp-zoom-meeting-duration-hr">
							<?php
							for ( $hr = 0; $hr <= 24; $hr ++ ) {
								echo '<option value="' . esc_attr( $hr ) . '">' . esc_attr( $hr ) . '</option>';
							}
							?>
						</select>
						<label for="bp-zoom-meeting-duration-hr"><?php esc_html_e( 'hr', 'buddyboss-pro' ); ?></label>
					</div>
					<div class="bb-field-wrap">
						<select id="bp-zoom-meeting-duration-min" name="bp-zoom-meeting-duration-min">
							<?php
							$min = 0;
							while ( $min <= 45 ) {
								$selected = ( 30 === $min ) ? 'selected="selected"' : '';
								echo '<option value="' . esc_attr( $min ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $min ) . '</option>';
								$min = $min + 15;
							}
							?>
						</select>
						<label for="bp-zoom-meeting-duration-min"><?php esc_html_e( 'min', 'buddyboss-pro' ); ?></label>
					</div>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-timezone"><?php esc_html_e( 'Timezone', 'buddyboss-pro' ); ?> *</label>
				<div class="bb-meeting-input-wrap">
					<select id="bp-zoom-meeting-timezone" name="bp-zoom-meeting-timezone">
						<?php
						$timezones          = bp_zoom_get_timezone_options();
						$wp_timezone_str    = get_option( 'timezone_string' );
						$selected_time_zone = '';

						if ( empty( $wp_timezone_str ) ) {
							$wp_timezone_str_offset = get_option( 'gmt_offset' );
						} else {
							$time                   = new DateTime( 'now', new DateTimeZone( $wp_timezone_str ) );
							$wp_timezone_str_offset = $time->getOffset() / 60 / 60;
						}

						if ( ! empty( $timezones ) ) {
							foreach ( $timezones as $key => $time_zone ) {
								if ( $key === $wp_timezone_str ) {
									$selected_time_zone = $key;
									break;
								}

								$date            = new DateTime( 'now', new DateTimeZone( $key ) );
								$offset_in_hours = $date->getOffset() / 60 / 60;

								if ( (float) $wp_timezone_str_offset === (float) $offset_in_hours ) {
									$selected_time_zone = $key;
								}
							}
						}
						?>
						<?php foreach ( $timezones as $k => $timezone ) { ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php echo $k === $selected_time_zone ? 'selected="selected"' : ''; ?>><?php echo esc_html( $timezone ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-alert"><?php esc_html_e( 'Meeting Notifications', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap">
					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" name="bp-zoom-meeting-notification" id="bp-zoom-meeting-notification" value="yes" class="bs-styled-checkbox"/>
						<label for="bp-zoom-meeting-notification" id="bb-notification-meeting-label"></label>
						<span class="bb-recurring-meeting-text">
							<?php esc_html_e( 'Send', 'buddyboss-pro' ); ?>
							<select id="bp-zoom-meeting-alert" name="bp-zoom-meeting-alert" disabled="disabled">
								<option value="1"><?php esc_html_e( 'immediately', 'buddyboss-pro' ); ?></option>
								<option value="15" selected><?php esc_html_e( '15 minutes', 'buddyboss-pro' ); ?></option>
								<option value="30"><?php esc_html_e( '30 minutes', 'buddyboss-pro' ); ?></option>
								<option value="60"><?php esc_html_e( '1 hour', 'buddyboss-pro' ); ?></option>
								<option value="120"><?php esc_html_e( '2 hours', 'buddyboss-pro' ); ?></option>
								<option value="180"><?php esc_html_e( '3 hours', 'buddyboss-pro' ); ?></option>
								<option value="240"><?php esc_html_e( '4 hours', 'buddyboss-pro' ); ?></option>
								<option value="300"><?php esc_html_e( '5 hours', 'buddyboss-pro' ); ?></option>
							</select>
							<?php esc_html_e( 'before meeting', 'buddyboss-pro' ); ?>
						</span>
					</div>
					<p class="description"><?php esc_html_e( 'Enabling this option will create the following: ', 'buddyboss-pro' ); ?></p>
					<ul class="description">
						<li><?php esc_html_e( 'Site notification for group members.', 'buddyboss-pro' ); ?></li>
						<li><?php esc_html_e( 'Email notification to group members.', 'buddyboss-pro' ); ?></li>
						<li><?php esc_html_e( 'Activity notification in group news feed.', 'buddyboss-pro' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label></label>
				<div class="bb-field-wrap checkbox-row bb-meeting-input-wrap">
					<input type="checkbox" name="bp-zoom-meeting-recurring" id="bp-zoom-meeting-recurring" value="yes" class="bs-styled-checkbox"/>
					<label for="bp-zoom-meeting-recurring" id="bb-recurring-meeting-label"><span class="bb-recurring-meeting-text"><?php esc_html_e( 'Recurring meeting', 'buddyboss-pro' ); ?></span></label>
				</div>
			</div>

			<div class="bp-zoom-meeting-recurring-options bp-hide">
				<div class="bb-field-wrap">
					<label for="bp-zoom-meeting-recurrence"><?php esc_html_e( 'Recurrence', 'buddyboss-pro' ); ?></label>
					<div class="bb-meeting-input-wrap">
						<select name="bp-zoom-meeting-recurrence" id="bp-zoom-meeting-recurrence">
							<option value="1"><?php esc_html_e( 'Daily', 'buddyboss-pro' ); ?></option>
							<option value="2"><?php esc_html_e( 'Weekly', 'buddyboss-pro' ); ?></option>
							<option value="3"><?php esc_html_e( 'Monthly', 'buddyboss-pro' ); ?></option>
						</select>
					</div>
				</div>

				<div class="bp-zoom-meeting-recurring-sub-options">
					<div class="bb-field-wrap bp-zoom-meeting-repeat-wrap">
						<label for="bp-zoom-meeting-repeat-interval"><?php esc_html_e( 'Repeat every', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap">
							<select name="bp-zoom-meeting-repeat-interval" id="bp-zoom-meeting-repeat-interval">
								<?php for ( $i = 1; $i <= 15; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
								<?php endfor; ?>
							</select>
							<span id="bp-zoom-meeting-repeat-interval-type"><?php esc_html_e( 'day', 'buddyboss-pro' ); ?></span>
						</div>
					</div>
					<div class="bb-field-wrap bp-zoom-meeting-occurs-on bp-hide">
						<label><?php esc_html_e( 'Occurs on', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap">
							<div id="bp-zoom-meeting-occurs-on-week">
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-sun" value="1" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-sun"><span><?php esc_html_e( 'Sun', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-mon" value="2" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-mon"><span><?php esc_html_e( 'Mon', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-tue" value="3" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-tue"><span><?php esc_html_e( 'Tue', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-wed" value="4" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-wed"><span><?php esc_html_e( 'Wed', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-thu" value="5" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-thu"><span><?php esc_html_e( 'Thu', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-fri" value="6" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-fri"><span><?php esc_html_e( 'Fri', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-sat" value="7" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-weekly-days-sat"><span><?php esc_html_e( 'Sat', 'buddyboss-pro' ); ?></span></label>
							</div>
							<div id="bp-zoom-meeting-occurs-on-month" class="bp-hide">
								<div>
									<input type="radio" value="day" id="bp-zoom-meeting-occurs-month-day-select" name="bp-zoom-meeting-monthly-occurs-on" class="bs-styled-radio" checked/>
									<label for="bp-zoom-meeting-occurs-month-day-select">
										<?php esc_html_e( 'Day', 'buddyboss-pro' ); ?>
										<select id="bp-zoom-meeting-monthly-day" name="bp-zoom-meeting-monthly-day">
											<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
												<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
											<?php endfor; ?>
										</select>
										<?php esc_html_e( 'of the month', 'buddyboss-pro' ); ?>
									</label>
								</div>
								<div>
									<input type="radio" value="week" id="bp-zoom-meeting-occurs-month-week-select" name="bp-zoom-meeting-monthly-occurs-on" class="bs-styled-radio"/>
									<label for="bp-zoom-meeting-occurs-month-week-select">
										<select id="bp-zoom-meeting-monthly-week" name="bp-zoom-meeting-monthly-week">
											<option value="1"><?php esc_html_e( 'First', 'buddyboss-pro' ); ?></option>
											<option value="2"><?php esc_html_e( 'Second', 'buddyboss-pro' ); ?></option>
											<option value="3"><?php esc_html_e( 'Third', 'buddyboss-pro' ); ?></option>
											<option value="4"><?php esc_html_e( 'Fourth', 'buddyboss-pro' ); ?></option>
											<option value="-1"><?php esc_html_e( 'Last', 'buddyboss-pro' ); ?></option>
										</select>
										<select id="bp-zoom-meeting-monthly-week-day" name="bp-zoom-meeting-monthly-week-day">
											<option value="1"><?php esc_html_e( 'Sun', 'buddyboss-pro' ); ?></option>
											<option value="2"><?php esc_html_e( 'Mon', 'buddyboss-pro' ); ?></option>
											<option value="3"><?php esc_html_e( 'Tue', 'buddyboss-pro' ); ?></option>
											<option value="4"><?php esc_html_e( 'Wed', 'buddyboss-pro' ); ?></option>
											<option value="5"><?php esc_html_e( 'Thu', 'buddyboss-pro' ); ?></option>
											<option value="6"><?php esc_html_e( 'Fri', 'buddyboss-pro' ); ?></option>
											<option value="7"><?php esc_html_e( 'Sat', 'buddyboss-pro' ); ?></option>
										</select>
										<span class="bp-zoom-meeting-occurs-month-week-select-label"><?php esc_html_e( 'of the month', 'buddyboss-pro' ); ?></span>
									</label>
								</div>
							</div>
						</div>
					</div>
					<div class="bb-field-wrap">
						<label><?php esc_html_e( 'End date', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap bp-zoom-meeting-end-date-time-wrap">
							<div>
								<input type="radio" value="date" id="bp-zoom-meeting-end-date-select" name="bp-zoom-meeting-end-time-select" class="bs-styled-radio" checked/>
								<label for="bp-zoom-meeting-end-date-select">
									<?php esc_html_e( 'By', 'buddyboss-pro' ); ?>
									<div class="bb-field-wrap end-date-picker">
										<input type="text" id="bp-zoom-meeting-end-date-time" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+6 days' ) ) ); ?>" name="bp-zoom-meeting-end-date-time" placeholder="yyyy-mm-dd" />
									</div>
								</label>
							</div>
							<div>
								<input type="radio" value="times" id="bp-zoom-meeting-end-times-select" name="bp-zoom-meeting-end-time-select" class="bs-styled-radio"/>
								<label for="bp-zoom-meeting-end-times-select">
									<?php esc_html_e( 'After', 'buddyboss-pro' ); ?>
									<select id="bp-zoom-meeting-end-times" name="bp-zoom-meeting-end-times">
										<?php for ( $i = 1; $i <= 20; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
										<?php endfor; ?>
									</select>
									<?php esc_html_e( 'occurrences', 'buddyboss-pro' ); ?>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>

		<hr />

		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label class="bb-video-label"><?php esc_html_e( 'Video', 'buddyboss-pro' ); ?></label>
				<div class="bb-video-fields-wrap bb-meeting-input-wrap">
					<div class="bb-field-wrap checkbox-row">
						<label for="bp-zoom-meeting-host-video">
							<span class="label-span"><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></span>
							<div class="bb-toggle-switch">
								<input type="checkbox" id="bp-zoom-meeting-host-video" value="yes" name="bp-zoom-meeting-host-video" class="bs-styled-checkbox"/>
								<span class="bb-toggle-slider"></span>
							</div>
						</label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<label for="bp-zoom-meeting-participants-video">
							<span class="label-span"><?php esc_html_e( 'Participants', 'buddyboss-pro' ); ?></span>
							<div class="bb-toggle-switch">
								<input type="checkbox" id="bp-zoom-meeting-participants-video" value="yes" name="bp-zoom-meeting-participants-video" class="bs-styled-checkbox"/>
								<span class="bb-toggle-slider"></span>
							</div>
						</label>
					</div>
					<p class="description"><?php esc_html_e( 'Start video when host and participants join the meeting.', 'buddyboss-pro' ); ?></p>
				</div>
			</div>
		</div>

		<hr />

		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label><?php esc_html_e( 'Meeting Options', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-options-wrap bb-meeting-input-wrap">
					<?php if ( ! $disable_registration ) : ?>
						<div class="bb-field-wrap checkbox-row bp-zoom-meeting-registration-wrapper">
							<input type="checkbox" name="bp-zoom-meeting-registration" id="bp-zoom-meeting-registration" value="yes" class="bs-styled-checkbox" />
							<label for="bp-zoom-meeting-registration"><span><?php esc_html_e( 'Require Registration', 'buddyboss-pro' ); ?></span></label>

							<div class="bp-zoom-meeting-registration-options bp-hide">
								<input type="radio" value="1" id="bp-zoom-meeting-registration-type-1" name="bp-zoom-meeting-registration-type" class="bs-styled-radio" checked/>
								<label for="bp-zoom-meeting-registration-type-1"><span><?php esc_html_e( 'Attendees register once and can attend any of the occurrences', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="2" id="bp-zoom-meeting-registration-type-2" name="bp-zoom-meeting-registration-type" class="bs-styled-radio"/>
								<label for="bp-zoom-meeting-registration-type-2"><span><?php esc_html_e( 'Attendees need to register for each occurrence to attend', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="3" id="bp-zoom-meeting-registration-type-3" name="bp-zoom-meeting-registration-type" class="bs-styled-radio"/>
								<label for="bp-zoom-meeting-registration-type-3"><span><?php esc_html_e( 'Attendees register once and can choose one or more occurrences to attend', 'buddyboss-pro' ); ?></span></label>
							</div>
						</div>
					<?php endif; ?>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-join-before-host" value="yes" name="bp-zoom-meeting-join-before-host" class="bs-styled-checkbox"/>
						<label for="bp-zoom-meeting-join-before-host"><span><?php esc_html_e( 'Enable join before host', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-mute-participants" value="yes" name="bp-zoom-meeting-mute-participants" class="bs-styled-checkbox"/>
						<label for="bp-zoom-meeting-mute-participants"><span><?php esc_html_e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-waiting-room" value="yes" name="bp-zoom-meeting-waiting-room" class="bs-styled-checkbox"/>
						<label for="bp-zoom-meeting-waiting-room"><span><?php esc_html_e( 'Enable waiting room', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-authentication" value="yes" name="bp-zoom-meeting-authentication" class="bs-styled-checkbox"/>
						<label for="bp-zoom-meeting-authentication"><span><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap full-row">
						<?php if ( ! $disable_recording ) : ?>
							<input type="checkbox" id="bp-zoom-meeting-auto-recording" value="yes" name="bp-zoom-meeting-auto-recording" class="bs-styled-checkbox"/>
							<label for="bp-zoom-meeting-auto-recording"><span><?php esc_html_e( 'Record the meeting automatically', 'buddyboss-pro' ); ?></span></label>

							<div class="bp-zoom-meeting-auto-recording-options bp-hide">
								<input type="radio" value="local" id="bp-zoom-meeting-recording-local" name="bp-zoom-meeting-recording" class="bs-styled-radio" checked/>
								<label for="bp-zoom-meeting-recording-local"><span><?php esc_html_e( 'On the local computer', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="cloud" id="bp-zoom-meeting-recording-cloud" name="bp-zoom-meeting-recording" class="bs-styled-radio"/>
								<label for="bp-zoom-meeting-recording-cloud"><span><?php esc_html_e( 'In the cloud', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php else : ?>
							<div class="bb-field-wrap checkbox-row">
								<input type="checkbox" id="bp-zoom-meeting-auto-recording" value="yes" name="bp-zoom-meeting-auto-recording" class="bs-styled-checkbox"/>
								<label for="bp-zoom-meeting-auto-recording"><span><?php esc_html_e( 'Record automatically onto local computer', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<hr />

		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap full-row">
				<label for="bp-zoom-meeting-host"><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap">
					<input type="text" id="bp-zoom-meeting-host" value="<?php echo esc_attr( bp_zoom_groups_api_host_show( $group_id ) ); ?>" name="bp-zoom-meeting-host" disabled />
					<p class="description"><?php esc_html_e( 'Default host for all meetings in this group.', 'buddyboss-pro' ); ?></p>
				</div>
			</div>
			<?php if ( ! $disable_alt_host ) : ?>
				<div class="bb-field-wrap full-row bp-zoom-meeting-alt-host">
					<label for="bp-zoom-meeting-alt-host-ids"><?php esc_html_e( 'Alternative Hosts', 'buddyboss-pro' ); ?></label>
					<div class="bb-meeting-host-select-wrap bb-meeting-input-wrap">
						<input type="text" placeholder="<?php esc_html_e( 'Example: mary@company.com, peter@school.edu', 'buddyboss-pro' ); ?>" id="bp-zoom-meeting-alt-host-ids" name="bp-zoom-meeting-alt-host-ids" value="<?php echo $default_group_host_email !== $current_user_data->user_email ? esc_html( $current_user_data->user_email ) : ''; ?>" />
						<p class="description"><?php esc_html_e( 'Additional hosts for this meeting, entered by email, comma separated. Each email added needs to match with a user in the default host\'s Zoom account.', 'buddyboss-pro' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<hr />

	<footer class="bb-model-footer text-right">
		<?php
		wp_nonce_field( 'bp_zoom_meeting' );
		if ( ! empty( $group_id ) ) {
			?>
			<input type="hidden" id="bp-zoom-meeting-group-id" name="bp-zoom-meeting-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
		<?php } ?>
		<input type="hidden" name="action" value="zoom_meeting_add" />
		<a class="button submit" id="bp-zoom-meeting-form-submit" href="#"><?php esc_html_e( 'Create Meeting', 'buddyboss-pro' ); ?></a>
	</footer>
</div>
