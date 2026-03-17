<?php
/**
 * BuddyBoss - ReadyLaunch Create Webinar
 *
 * @package BuddyBossPro/Integration/Zoom/Template/ReadyLaunch
 * @since 1.0.0
 */

?>
<div class="bb-rl-create-event-header">
	<h4 class="bb-title"><?php esc_html_e( 'Create Webinar', 'buddyboss-pro' ); ?></h4>
    <a class="bb-rl-close-create-webinar-form" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
        <span class="bb-icons-rl-x"></span>
    </a>
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

$default_group_host_email = bb_zoom_group_get_email_account( $group_id );

$disable_registration            = false;
$disable_recording               = false;
$disable_alt_host                = false;
$disable_authentication_settings = false;
$host_type                       = bb_zoom_group_get_host_type( $group_id );
if ( 1 === (int) $host_type ) {
	$disable_registration = true;
	$disable_recording    = true;
	$disable_alt_host     = true;
} elseif ( bb_zoom_is_webinar_hide_urls_enabled() ) {
	$disable_registration            = true;
	$disable_authentication_settings = true;
}
?>
<div class="bb-rl-create-event-wrapper">
	<div class="bp-webinar-fields-wrap">
		<div class="bb-field-wrapper">
			<div class="bb-field-wrapper-inner">
				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-title"><?php esc_html_e( 'Webinar Title', 'buddyboss-pro' ); ?> *</label>
					<div class="bb-webinar-input-wrap">
						<input autocomplete="off" type="text" id="bp-zoom-webinar-title" value="" name="bp-zoom-webinar-title" />
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-description"><?php esc_html_e( 'Description (optional)', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<textarea id="bp-zoom-webinar-description" name="bp-zoom-webinar-description"></textarea>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-password"><?php esc_html_e( 'Passcode (optional)', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap bp-toggle-webinar-password-wrap">
						<a href="#" class="bp-toggle-webinar-password"><i class="bb-icon-l bb-icon-eye"></i><i class="bb-icon-l bb-icon-eye-slash"></i></a>
						<input autocomplete="new-password" type="password" id="bp-zoom-webinar-password" value="" name="bp-zoom-webinar-password" />
					</div>
				</div>
			</div>

			<hr />

			<div class="bb-field-wrapper-inner">
				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-start-date"><?php esc_html_e( 'When', 'buddyboss-pro' ); ?> *</label>
					<div class="bp-wrap-duration bb-webinar-input-wrap">
						<div class="bb-field-wrap start-date-picker">
							<input type="text" id="bp-zoom-webinar-start-date" value="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( 'now' ) ) ); ?>" name="bp-zoom-webinar-start-date" placeholder="yyyy-mm-dd" autocomplete="off" />
						</div>
						<div class="bb-field-wrap start-time-picker">
							<?php
							$pending_minutes = 60 - wp_date( 'i', strtotime( 'now' ) );
							$current_minutes = strtotime( '+ ' . $pending_minutes . ' minutes' );
							?>
							<input type="text" id="bp-zoom-webinar-start-time" name="bp-zoom-webinar-start-time" autocomplete="off" placeholder="hh:mm" value="<?php echo esc_attr( wp_date( 'h:i', $current_minutes ) ); ?>" autocomplete="off" />
						</div>
						<div class="bb-field-wrap bp-zoom-webinar-time-meridian-wrap">
							<label for="bp-zoom-webinar-start-time-meridian-am">
								<input type="radio" value="am" id="bp-zoom-webinar-start-time-meridian-am" name="bp-zoom-webinar-start-time-meridian" <?php checked( 'AM', wp_date( 'A', $current_minutes ) ); ?>>
								<span class="bb-time-meridian"><?php esc_html_e( 'AM', 'buddyboss-pro' ); ?></span>
							</label>
							<label for="bp-zoom-webinar-start-time-meridian-pm">
								<input type="radio" value="pm" id="bp-zoom-webinar-start-time-meridian-pm" name="bp-zoom-webinar-start-time-meridian" <?php checked( 'PM', wp_date( 'A', $current_minutes ) ); ?>>
								<span class="bb-time-meridian"><?php esc_html_e( 'PM', 'buddyboss-pro' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-duration"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?> *</label>
					<div class="bp-wrap-duration bb-webinar-input-wrap">
						<div class="bb-field-wrap">
							<select id="bp-zoom-webinar-duration-hr" name="bp-zoom-webinar-duration-hr">
								<?php
								for ( $hr = 0; $hr <= 24; $hr ++ ) {
									echo '<option value="' . esc_attr( $hr ) . '">' . esc_attr( $hr ) . '</option>';
								}
								?>
							</select>
							<label for="bp-zoom-webinar-duration-hr"><?php esc_html_e( 'hr', 'buddyboss-pro' ); ?></label>
						</div>
						<div class="bb-field-wrap">
							<select id="bp-zoom-webinar-duration-min" name="bp-zoom-webinar-duration-min">
								<?php
								$min = 0;
								while ( $min <= 45 ) {
									$selected = ( 30 === $min ) ? 'selected="selected"' : '';
									echo '<option value="' . esc_attr( $min ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $min ) . '</option>';
									$min = $min + 15;
								}
								?>
							</select>
							<label for="bp-zoom-webinar-duration-min"><?php esc_html_e( 'min', 'buddyboss-pro' ); ?></label>
						</div>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-zoom-webinar-timezone"><?php esc_html_e( 'Timezone', 'buddyboss-pro' ); ?> *</label>
					<div class="bb-webinar-input-wrap">
						<select id="bp-zoom-webinar-timezone" name="bp-zoom-webinar-timezone">
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
					<label for="bp-zoom-webinar-alert"><?php esc_html_e( 'Webinar Notifications', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<div class="bb-field-wrap checkbox-row">
							<input type="checkbox" name="bp-zoom-webinar-notification" id="bp-zoom-webinar-notification" value="yes" class="bs-styled-checkbox"/>
							<label for="bp-zoom-webinar-notification" id="bb-notification-webinar-label"></label>
							<span class="bb-recurring-webinar-text">
								<?php esc_html_e( 'Send', 'buddyboss-pro' ); ?>
								<select id="bp-zoom-webinar-alert" name="bp-zoom-webinar-alert" disabled="disabled">
									<option value="1"><?php esc_html_e( 'immediately', 'buddyboss-pro' ); ?></option>
									<option value="15" selected><?php esc_html_e( '15 minutes', 'buddyboss-pro' ); ?></option>
									<option value="30"><?php esc_html_e( '30 minutes', 'buddyboss-pro' ); ?></option>
									<option value="60"><?php esc_html_e( '1 hour', 'buddyboss-pro' ); ?></option>
									<option value="120"><?php esc_html_e( '2 hours', 'buddyboss-pro' ); ?></option>
									<option value="180"><?php esc_html_e( '3 hours', 'buddyboss-pro' ); ?></option>
									<option value="240"><?php esc_html_e( '4 hours', 'buddyboss-pro' ); ?></option>
									<option value="300"><?php esc_html_e( '5 hours', 'buddyboss-pro' ); ?></option>
								</select>
								<?php esc_html_e( 'before webinar', 'buddyboss-pro' ); ?>
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
			</div>

			<hr />

			<div class="bb-field-wrapper-inner">
				<div class="bb-field-wrap">
					<label class="bb-checkbox-label" for="bp-zoom-webinar-recurring">
						<input type="checkbox" id="bp-zoom-webinar-recurring" value="yes" />
						<span class="bb-recurring-webinar-text"><?php esc_html_e( 'Recurring webinar', 'buddyboss-pro' ); ?></span>
					</label>
				</div>
				<div class="bp-zoom-webinar-recurring-options bp-hide">
					<div class="bb-field-wrap">
						<label for="bp-zoom-webinar-recurrence"><?php esc_html_e( 'Recurrence', 'buddyboss-pro' ); ?></label>
						<div class="bb-webinar-input-wrap">
							<select name="bp-zoom-webinar-recurrence" id="bp-zoom-webinar-recurrence">
								<option value="1"><?php esc_html_e( 'Daily', 'buddyboss-pro' ); ?></option>
								<option value="2"><?php esc_html_e( 'Weekly', 'buddyboss-pro' ); ?></option>
								<option value="3"><?php esc_html_e( 'Monthly', 'buddyboss-pro' ); ?></option>
							</select>
						</div>
					</div>

					<div class="bp-zoom-webinar-recurring-sub-options">
						<div class="bb-field-wrap bp-zoom-webinar-repeat-wrap">
							<label for="bp-zoom-webinar-repeat-interval"><?php esc_html_e( 'Repeat every', 'buddyboss-pro' ); ?></label>
							<div class="bb-webinar-input-wrap">
								<select name="bp-zoom-webinar-repeat-interval" id="bp-zoom-webinar-repeat-interval">
									<?php for ( $i = 1; $i <= 15; $i++ ) : ?>
										<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
									<?php endfor; ?>
								</select>
								<span id="bp-zoom-webinar-repeat-interval-type"><?php esc_html_e( 'day', 'buddyboss-pro' ); ?></span>
							</div>
						</div>
						<div class="bb-field-wrap bp-zoom-webinar-occurs-on bp-hide">
							<label><?php esc_html_e( 'Occurs on', 'buddyboss-pro' ); ?></label>
							<div class="bb-webinar-input-wrap">
								<div id="bp-zoom-webinar-occurs-on-week">
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-sun" value="1" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-sun"><span><?php esc_html_e( 'Sun', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-mon" value="2" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-mon"><span><?php esc_html_e( 'Mon', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-tue" value="3" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-tue"><span><?php esc_html_e( 'Tue', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-wed" value="4" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-wed"><span><?php esc_html_e( 'Wed', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-thu" value="5" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-thu"><span><?php esc_html_e( 'Thu', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-fri" value="6" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-fri"><span><?php esc_html_e( 'Fri', 'buddyboss-pro' ); ?></span></label>
									<input type="checkbox" name="bp-zoom-webinar-weekly-days[]" id="bp-zoom-webinar-weekly-days-sat" value="7" class="bs-styled-checkbox"/>
									<label for="bp-zoom-webinar-weekly-days-sat"><span><?php esc_html_e( 'Sat', 'buddyboss-pro' ); ?></span></label>
								</div>
								<div id="bp-zoom-webinar-occurs-on-month" class="bp-hide">
									<select id="bp-zoom-webinar-monthly-occurs-on" name="bp-zoom-webinar-monthly-occurs-on">
										<option value="1"><?php esc_html_e( 'Monthly on day', 'buddyboss-pro' ); ?></option>
										<option value="2"><?php esc_html_e( 'Monthly on', 'buddyboss-pro' ); ?></option>
									</select>
									<div class="bp-zoom-webinar-monthly-date-select">
										<select id="bp-zoom-webinar-monthly-day" name="bp-zoom-webinar-monthly-day">
											<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
												<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
											<?php endfor; ?>
										</select>
									</div>
									<div class="bp-zoom-webinar-monthly-week-select bp-hide">
										<select id="bp-zoom-webinar-monthly-week" name="bp-zoom-webinar-monthly-week">
											<option value="1"><?php esc_html_e( 'First', 'buddyboss-pro' ); ?></option>
											<option value="2"><?php esc_html_e( 'Second', 'buddyboss-pro' ); ?></option>
											<option value="3"><?php esc_html_e( 'Third', 'buddyboss-pro' ); ?></option>
											<option value="4"><?php esc_html_e( 'Fourth', 'buddyboss-pro' ); ?></option>
											<option value="-1"><?php esc_html_e( 'Last', 'buddyboss-pro' ); ?></option>
										</select>
										<select id="bp-zoom-webinar-monthly-week-day" name="bp-zoom-webinar-monthly-week-day">
											<option value="1"><?php esc_html_e( 'Sunday', 'buddyboss-pro' ); ?></option>
											<option value="2"><?php esc_html_e( 'Monday', 'buddyboss-pro' ); ?></option>
											<option value="3"><?php esc_html_e( 'Tuesday', 'buddyboss-pro' ); ?></option>
											<option value="4"><?php esc_html_e( 'Wednesday', 'buddyboss-pro' ); ?></option>
											<option value="5"><?php esc_html_e( 'Thursday', 'buddyboss-pro' ); ?></option>
											<option value="6"><?php esc_html_e( 'Friday', 'buddyboss-pro' ); ?></option>
											<option value="7"><?php esc_html_e( 'Saturday', 'buddyboss-pro' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="bb-field-wrap">
							<label><?php esc_html_e( 'End date', 'buddyboss-pro' ); ?></label>
							<div class="bb-webinar-input-wrap">
								<div>
									<input type="radio" id="bp-zoom-webinar-end-date-option" name="bp-zoom-webinar-end-option" value="date"/>
									<input type="text" id="bp-zoom-webinar-end-date-selector" class="bp-zoom-webinar-end-date-selector" placeholder="yyyy-mm-dd"/>
								</div>
								<div>
									<input type="radio" id="bp-zoom-webinar-end-occurrences-option" name="bp-zoom-webinar-end-option" value="occurrences"/>
									<label for="bp-zoom-webinar-end-occurrences-option" class="bp-zoom-webinar-end-occurrences-label">
										<?php esc_html_e( 'After', 'buddyboss-pro' ); ?>
										<select id="bp-zoom-webinar-end-occurrences" name="bp-zoom-webinar-end-occurrences">
											<?php for ( $i = 1; $i <= 100; $i++ ) : ?>
												<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
											<?php endfor; ?>
										</select>
										<?php esc_html_e( 'occurrence(s)', 'buddyboss-pro' ); ?>
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
					<label for="bp-zoom-webinar-registration"><?php esc_html_e( 'Registration', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<input type="checkbox" name="bp-zoom-webinar-registration" id="bp-zoom-webinar-registration" value="yes" <?php disabled( $disable_registration ); ?> class="bs-styled-checkbox"/>
						<label for="bp-zoom-webinar-registration"></label>
						<span class="description"><?php esc_html_e( 'Require registration to join webinar', 'buddyboss-pro' ); ?></span>
					</div>
				</div>
				
				<div id="bp-zoom-webinar-registration-options" class="bp-hide">
					<div class="bb-field-wrap">
						<label for="bp-zoom-webinar-registration-type"><?php esc_html_e( 'Registration Type', 'buddyboss-pro' ); ?></label>
						<div class="bb-webinar-input-wrap">
							<select id="bp-zoom-webinar-registration-type" name="bp-zoom-webinar-registration-type">
								<option value="1"><?php esc_html_e( 'Attendees register once and can attend any of the recurring webinar sessions', 'buddyboss-pro' ); ?></option>
								<option value="2"><?php esc_html_e( 'Attendees need to register for each occurrence to attend', 'buddyboss-pro' ); ?></option>
								<option value="3"><?php esc_html_e( 'Attendees register once and can choose one or more occurrences to attend', 'buddyboss-pro' ); ?></option>
							</select>
						</div>
					</div>
				</div>
				
				<div class="bb-field-wrap bp-zoom-webinar-authentication-container">
					<label for="bp-zoom-webinar-authentication"><?php esc_html_e( 'Authentication', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<input type="checkbox" name="bp-zoom-webinar-authentication" id="bp-zoom-webinar-authentication" value="yes" <?php disabled( $disable_authentication_settings ); ?> class="bs-styled-checkbox" />
						<label for="bp-zoom-webinar-authentication"></label>
						<span class="description"><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
					</div>
				</div>
				
				<div class="bb-field-wrap bp-zoom-webinar-password-container">
					<label for="bp-zoom-webinar-alt-host-ids"><?php esc_html_e( 'Alternative Hosts', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<input type="text" id="bp-zoom-webinar-alt-host-ids" value="" <?php disabled( $disable_alt_host ); ?> placeholder="<?php esc_html_e( 'Emails, comma separated', 'buddyboss-pro' ); ?>" />
						<input type="hidden" name="bp-zoom-webinar-alt-host-ids" value="" />
					</div>
				</div>
				
				<div class="bb-field-wrap bp-zoom-webinar-auto-recording-container bp-hide">
					<label for="bp-zoom-webinar-auto-recording"><?php esc_html_e( 'Record webinar', 'buddyboss-pro' ); ?></label>
					<div class="bb-webinar-input-wrap">
						<select id="bp-zoom-webinar-auto-recording" name="bp-zoom-webinar-auto-recording" <?php disabled( $disable_recording ); ?>>
							<option value="none"><?php esc_html_e( 'Do not record', 'buddyboss-pro' ); ?></option>
							<option value="cloud"><?php esc_html_e( 'Record automatically in the cloud', 'buddyboss-pro' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<footer class="text-right flex bb-rl-zoom-event-footer">
		<div class="bb-field-wrap bb-field-submit-wrap">
			<button type="submit" id="bp-zoom-webinar-submit" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
				<?php esc_html_e( 'Create Webinar', 'buddyboss-pro' ); ?>
			</button>
		</div>
	</footer>
</div>