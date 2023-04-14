<?php
/**
 * BuddyBoss - Groups Create Zoom Meetings
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

$recurring_details           = bp_get_zoom_meeting_recurring_details();
$recurrence_details          = false;
$recurrence_type             = false;
$recurrence_repeat_interval  = false;
$recurrence_end_date_time    = false;
$recurrence_end_times        = false;
$recurrence_monthly_day      = false;
$recurrence_weekly_days      = array();
$recurrence_monthly_week     = false;
$recurrence_monthly_week_day = false;
if ( ! empty( $recurring_details['recurrence'] ) ) {
	$recurrence_details = (object) $recurring_details['recurrence'];

	$recurrence_type             = isset( $recurrence_details->type ) ? $recurrence_details->type : false;
	$recurrence_repeat_interval  = isset( $recurrence_details->repeat_interval ) ? $recurrence_details->repeat_interval : false;
	$recurrence_end_date_time    = isset( $recurrence_details->end_date_time ) ? $recurrence_details->end_date_time : false;
	$recurrence_end_times        = isset( $recurrence_details->end_times ) ? $recurrence_details->end_times : false;
	$recurrence_weekly_days      = isset( $recurrence_details->weekly_days ) ? explode( ',', $recurrence_details->weekly_days ) : array();
	$recurrence_monthly_day      = isset( $recurrence_details->monthly_day ) ? $recurrence_details->monthly_day : false;
	$recurrence_monthly_week     = isset( $recurrence_details->monthly_week ) ? $recurrence_details->monthly_week : false;
	$recurrence_monthly_week_day = isset( $recurrence_details->monthly_week_day ) ? $recurrence_details->monthly_week_day : false;
}

if ( 1 === $recurrence_type ) {
	$interval_length  = 15;
	$recurrence_label = esc_html__( 'day', 'buddyboss-pro' );
} elseif ( 2 === $recurrence_type ) {
	$interval_length  = 12;
	$recurrence_label = esc_html__( 'week', 'buddyboss-pro' );
} elseif ( 3 === $recurrence_type ) {
	$interval_length  = 3;
	$recurrence_label = esc_html__( 'month', 'buddyboss-pro' );
} else {
	$interval_length  = 0;
	$recurrence_label = esc_html__( 'day', 'buddyboss-pro' );
}

$disable_registration = false;
$disable_recording    = false;
$disable_alt_host     = false;
$host_type            = groups_get_groupmeta( bp_get_zoom_meeting_group_id(), 'bp-group-zoom-api-host-type', true );
if ( 1 === (int) $host_type ) {
	$disable_registration = true;
	$disable_recording    = true;
	$disable_alt_host     = true;
}

$occurrence_edit = false;
if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
	$occurrence_edit      = true;
	$disable_registration = true;
}
?>

<div class="bb-title-wrap">
	<h2 class="bb-title"><?php esc_html_e( 'Edit Meeting', 'buddyboss-pro' ); ?></h2>
	<!--<a href="#" class="bp-close-create-meeting-form"><span class="bb-icon-x"></span></a>-->
</div>

<div class="bp-meeting-fields-wrap">
	<div class="bb-field-wrapper">
		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-title"><?php esc_html_e( 'Meeting Title', 'buddyboss-pro' ); ?> *</label>
				<div class="bb-meeting-input-wrap">
					<input <?php echo $occurrence_edit ? 'disabled' : ''; ?> autocomplete="off" type="text" id="bp-zoom-meeting-title" value="<?php bp_zoom_meeting_title(); ?>" name="bp-zoom-meeting-title" />
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-description"><?php esc_html_e( 'Description (optional)', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap">
					<textarea id="bp-zoom-meeting-description" name="bp-zoom-meeting-description"><?php bp_zoom_meeting_description(); ?></textarea>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-password"><?php esc_html_e( 'Passcode (optional)', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap bp-toggle-meeting-password-wrap">
					<a href="#" class="bp-toggle-meeting-password"><i class="bb-icon-l bb-icon-eye"></i><i class="bb-icon-l bb-icon-eye-slash"></i></a>
					<input <?php echo $occurrence_edit ? 'disabled' : ''; ?> autocomplete="new-password" type="password" id="bp-zoom-meeting-password" value="<?php bp_zoom_meeting_password(); ?>" name="bp-zoom-meeting-password"/>
				</div>
			</div>
		</div>

		<hr />

		<div class="bb-field-wrapper-inner">

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-start-date"><?php esc_html_e( 'When', 'buddyboss-pro' ); ?> *</label>
				<?php
				if ( 'meeting_occurrence' !== bp_get_zoom_meeting_zoom_type() ) {
					if ( bp_get_zoom_meeting_recurring() ) {
						$start_date_time = false;
						$meeting_details = bp_get_zoom_meeting_zoom_details( bp_get_zoom_meeting_id() );
						if ( ! empty( $meeting_details ) && ! empty( $meeting_details['occurrences'] ) ) {
							$occurrences = $meeting_details['occurrences'];
							foreach ( $occurrences as $occurrence ) {
								if ( 'deleted' !== $occurrence['status'] ) {
									$start_date_time = wp_date( 'Y-m-d g:i a', strtotime( $occurrence['start_time'] ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) );
									break;
								}
							}
						}
						if ( empty( $start_date_time ) ) {
							$start_date_time = wp_date( 'Y-m-d g:i a', strtotime( 'now' ) );
						}
					} else {
						$start_date_time = bp_get_zoom_meeting_start_date();
					}
				} elseif ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
					$start_date_time = wp_date( 'Y-m-d g:i a', strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) );
				} else {
					$start_date_time = wp_date( 'Y-m-d g:i a', strtotime( 'now' ) );
				}
				$start_date          = gmdate( 'Y-m-d', strtotime( $start_date_time ) );
				$start_time          = gmdate( 'h:i', strtotime( $start_date_time ) );
				$start_time_meridian = gmdate( 'A', strtotime( $start_date_time ) );

				if ( empty( $start_time ) ) {
					$start_time = '00:00';
				} else {
					$explode_start_time = explode( ':', $start_time );
					if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) ) {
						$explode_start_time[0] = '00';
					}
					$start_time = implode( ':', $explode_start_time );
				}
				?>
				<div class="bp-wrap-duration bb-meeting-input-wrap">
					<div class="bb-field-wrap start-date-picker">
						<input type="text" id="bp-zoom-meeting-start-date" value="<?php echo esc_attr( $start_date ); ?>" name="bp-zoom-meeting-start-date" placeholder="yyyy-mm-dd" autocomplete="off"/>
					</div>
					<div class="bb-field-wrap start-time-picker">
						<input type="text" id="bp-zoom-meeting-start-time" value="<?php echo esc_attr( $start_time ); ?>" name="bp-zoom-meeting-start-time" placeholder="hh:mm" autocomplete="off" />
					</div>
					<div class="bb-field-wrap bp-zoom-meeting-time-meridian-wrap">
						<label for="bp-zoom-meeting-start-time-meridian-am">
							<input type="radio" value="am" id="bp-zoom-meeting-start-time-meridian-am" name="bp-zoom-meeting-start-time-meridian" <?php checked( 'AM', $start_time_meridian ); ?>>
							<span class="bb-time-meridian"><?php esc_html_e( 'AM', 'buddyboss-pro' ); ?></span>
						</label>
						<label for="bp-zoom-meeting-start-time-meridian-pm">
							<input type="radio" value="pm" id="bp-zoom-meeting-start-time-meridian-pm" name="bp-zoom-meeting-start-time-meridian" <?php checked( 'PM', $start_time_meridian ); ?>>
							<span class="bb-time-meridian"><?php esc_html_e( 'PM', 'buddyboss-pro' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="bb-field-wrap">
				<?php
				$duration = bp_get_zoom_meeting_duration();
				$hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
				$minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
				?>
				<label for="bp-zoom-meeting-duration"><?php esc_html_e( 'Duration', 'buddyboss-pro' ); ?> *</label>
				<div class="bp-wrap-duration">
					<div class="bb-field-wrap">
						<select id="bp-zoom-meeting-duration-hr" name="bp-zoom-meeting-duration-hr">
							<?php
							for ( $hr = 0; $hr <= 24; $hr ++ ) {
								echo '<option value="' . esc_attr( $hr ) . '" ' . selected( $hours, $hr, false ) . '>' . esc_attr( $hr ) . '</option>';
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
								echo '<option value="' . esc_attr( $min ) . '" ' . selected( $minutes, $min, false ) . '>' . esc_attr( $min ) . '</option>';
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
					<select <?php echo $occurrence_edit ? 'disabled' : ''; ?> id="bp-zoom-meeting-timezone" name="bp-zoom-meeting-timezone">
					<?php $timezones = bp_zoom_get_timezone_options(); ?>
					<?php foreach ( $timezones as $k => $timezone ) { ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php echo bp_get_zoom_meeting_timezone() === $k ? 'selected' : ''; ?>><?php echo esc_html( $timezone ); ?></option>
					<?php } ?>
				</select>
				</div>
			</div>

			<?php if ( ! $occurrence_edit ) : ?>

			<div class="bb-field-wrap">
				<label for="bp-zoom-meeting-alert"><?php esc_html_e( 'Meeting Notifications', 'buddyboss-pro' ); ?></label>
				<div class="bb-meeting-input-wrap">
					<?php $alert = bp_get_zoom_meeting_alert(); ?>
					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" name="bp-zoom-meeting-notification" id="bp-zoom-meeting-notification" value="yes" class="bs-styled-checkbox" <?php checked( ! empty( $alert ), 1 ); ?>/>
						<label for="bp-zoom-meeting-notification" id="bb-notification-meeting-label"></label>
						<span class="bb-recurring-meeting-text">
							<?php esc_html_e( 'Send', 'buddyboss-pro' ); ?>
							<select id="bp-zoom-meeting-alert" name="bp-zoom-meeting-alert" <?php disabled( empty( $alert ), true ); ?>>
								<option value="1" <?php selected( '1', $alert, true ); ?>><?php esc_html_e( 'immediately', 'buddyboss-pro' ); ?></option>
								<option value="15" <?php selected( '15', $alert, true ); ?>><?php esc_html_e( '15 minutes', 'buddyboss-pro' ); ?></option>
								<option value="30" <?php selected( '30', $alert, true ); ?>><?php esc_html_e( '30 minutes', 'buddyboss-pro' ); ?></option>
								<option value="60" <?php selected( '60', $alert, true ); ?>><?php esc_html_e( '1 hour', 'buddyboss-pro' ); ?></option>
								<option value="120" <?php selected( '120', $alert, true ); ?>><?php esc_html_e( '2 hours', 'buddyboss-pro' ); ?></option>
								<option value="180" <?php selected( '180', $alert, true ); ?>><?php esc_html_e( '3 hours', 'buddyboss-pro' ); ?></option>
								<option value="240" <?php selected( '240', $alert, true ); ?>><?php esc_html_e( '4 hours', 'buddyboss-pro' ); ?></option>
								<option value="300" <?php selected( '300', $alert, true ); ?>><?php esc_html_e( '5 hours', 'buddyboss-pro' ); ?></option>
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

			<?php endif; ?>

			<div class="bb-field-wrap">
				<label></label>
				<div class="bb-field-wrap checkbox-row bb-meeting-input-wrap">
					<input <?php echo $occurrence_edit ? 'disabled' : ''; ?> type="checkbox" name="bp-zoom-meeting-recurring" id="bp-zoom-meeting-recurring" value="yes" class="bs-styled-checkbox" <?php checked( true, bp_get_zoom_meeting_recurring() ); ?>/>
					<label for="bp-zoom-meeting-recurring" id="bb-recurring-meeting-label"><span class="bb-recurring-meeting-text"><?php esc_html_e( 'Recurring meeting', 'buddyboss-pro' ); ?></span></label>
				</div>
			</div>

			<div class="bp-zoom-meeting-recurring-options <?php echo ! bp_get_zoom_meeting_recurring() ? 'bp-hide' : ''; ?>">
				<div class="bb-field-wrap">
					<label for="bp-zoom-meeting-recurrence"><?php esc_html_e( 'Recurrence', 'buddyboss-pro' ); ?></label>
					<select name="bp-zoom-meeting-recurrence" id="bp-zoom-meeting-recurrence">
						<option value="1" <?php echo 1 === $recurrence_type ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Daily', 'buddyboss-pro' ); ?></option>
						<option value="2" <?php echo 2 === $recurrence_type ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Weekly', 'buddyboss-pro' ); ?></option>
						<option value="3" <?php echo 3 === $recurrence_type ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Monthly', 'buddyboss-pro' ); ?></option>
					</select>
				</div>

				<div class="bp-zoom-meeting-recurring-sub-options">
					<div class="bb-field-wrap bp-zoom-meeting-repeat-wrap">
						<label for="bp-zoom-meeting-repeat-interval"><?php esc_html_e( 'Repeat every', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap">
							<select name="bp-zoom-meeting-repeat-interval" id="bp-zoom-meeting-repeat-interval">
								<?php
								if ( 2 === $recurrence_type ) {
									$repeat_interval_counter = 12;
								} elseif ( 3 === $recurrence_type ) {
									$repeat_interval_counter = 3;
								} else {
									$repeat_interval_counter = 15;
								}
								?>
								<?php for ( $i = 1; $i <= $repeat_interval_counter; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php echo $i === $recurrence_repeat_interval ? 'selected="selected"' : ''; ?>><?php echo esc_html( $i ); ?></option>
								<?php endfor; ?>
							</select>
							<span id="bp-zoom-meeting-repeat-interval-type"><?php echo esc_html( $recurrence_label ); ?></span>
						</div>
					</div>
					<div class="bb-field-wrap bp-zoom-meeting-occurs-on <?php echo 1 === $recurrence_type || empty( $recurrence_type ) ? 'bp-hide' : ''; ?>">
						<label><?php esc_html_e( 'Occurs on', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap">
							<div id="bp-zoom-meeting-occurs-on-week" class="<?php echo 2 === $recurrence_type ? '' : 'bp-hide'; ?>">
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-sun" value="1" class="bs-styled-checkbox" <?php echo in_array( '1', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-sun"><span><?php esc_html_e( 'Sun', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-mon" value="2" class="bs-styled-checkbox" <?php echo in_array( '2', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-mon"><span><?php esc_html_e( 'Mon', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-tue" value="3" class="bs-styled-checkbox" <?php echo in_array( '3', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-tue"><span><?php esc_html_e( 'Tue', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-wed" value="4" class="bs-styled-checkbox" <?php echo in_array( '4', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-wed"><span><?php esc_html_e( 'Wed', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-thu" value="5" class="bs-styled-checkbox" <?php echo in_array( '5', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-thu"><span><?php esc_html_e( 'Thu', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-fri" value="6" class="bs-styled-checkbox" <?php echo in_array( '6', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-fri"><span><?php esc_html_e( 'Fri', 'buddyboss-pro' ); ?></span></label>
								<input type="checkbox" name="bp-zoom-meeting-weekly-days[]" id="bp-zoom-meeting-weekly-days-sat" value="7" class="bs-styled-checkbox" <?php echo in_array( '7', $recurrence_weekly_days, true ) ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-weekly-days-sat"><span><?php esc_html_e( 'Sat', 'buddyboss-pro' ); ?></span></label>
							</div>
							<div id="bp-zoom-meeting-occurs-on-month" class="<?php echo 3 === $recurrence_type ? '' : 'bp-hide'; ?>">
								<input type="radio" value="day" id="bp-zoom-meeting-occurs-month-day-select" name="bp-zoom-meeting-monthly-occurs-on" class="bs-styled-radio" <?php echo false !== $recurrence_monthly_day ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-occurs-month-day-select">
									<?php esc_html_e( 'Day', 'buddyboss-pro' ); ?>
									<select id="bp-zoom-meeting-monthly-day" name="bp-zoom-meeting-monthly-day">
										<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $recurrence_monthly_day, true ); ?>><?php echo esc_html( $i ); ?></option>
										<?php endfor; ?>
									</select>
									<?php esc_html_e( 'of the month', 'buddyboss-pro' ); ?>
								</label>
								<input type="radio" value="week" id="bp-zoom-meeting-occurs-month-week-select" name="bp-zoom-meeting-monthly-occurs-on" class="bs-styled-radio" <?php echo false === $recurrence_monthly_day ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-occurs-month-week-select">
									<select id="bp-zoom-meeting-monthly-week" name="bp-zoom-meeting-monthly-week">
										<option value="1" <?php echo 1 === $recurrence_monthly_week ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'First', 'buddyboss-pro' ); ?></option>
										<option value="2" <?php echo 2 === $recurrence_monthly_week ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Second', 'buddyboss-pro' ); ?></option>
										<option value="3" <?php echo 3 === $recurrence_monthly_week ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Third', 'buddyboss-pro' ); ?></option>
										<option value="4" <?php echo 4 === $recurrence_monthly_week ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Fourth', 'buddyboss-pro' ); ?></option>
										<option value="-1" <?php echo - 1 === $recurrence_monthly_week ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Last', 'buddyboss-pro' ); ?></option>
									</select>
									<select id="bp-zoom-meeting-monthly-week-day" name="bp-zoom-meeting-monthly-week-day">
										<option value="1" <?php echo 1 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Sun', 'buddyboss-pro' ); ?></option>
										<option value="2" <?php echo 2 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Mon', 'buddyboss-pro' ); ?></option>
										<option value="3" <?php echo 3 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Tue', 'buddyboss-pro' ); ?></option>
										<option value="4" <?php echo 4 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Wed', 'buddyboss-pro' ); ?></option>
										<option value="5" <?php echo 5 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Thu', 'buddyboss-pro' ); ?></option>
										<option value="6" <?php echo 6 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Fri', 'buddyboss-pro' ); ?></option>
										<option value="7" <?php echo 7 === $recurrence_monthly_week_day ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Sat', 'buddyboss-pro' ); ?></option>
									</select>
									<?php esc_html_e( 'of the month', 'buddyboss-pro' ); ?>
								</label>
							</div>
						</div>
					</div>
					<div class="bb-field-wrap">
						<label><?php esc_html_e( 'End date', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-input-wrap bp-zoom-meeting-end-date-time-wrap">
							<div>
								<input type="radio" value="date" id="bp-zoom-meeting-end-date-select" name="bp-zoom-meeting-end-time-select" class="bs-styled-radio" <?php echo false !== $recurrence_end_date_time ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-end-date-select">
									<?php esc_html_e( 'By', 'buddyboss-pro' ); ?>
									<div class="bb-field-wrap end-date-picker">
										<input type="text" id="bp-zoom-meeting-end-date-time" value="<?php echo false !== $recurrence_end_date_time ? esc_attr( gmdate( 'Y-m-d', strtotime( $recurrence_end_date_time ) ) ) : esc_attr( gmdate( 'Y-m-d', strtotime( '+7 days' ) ) ); ?>" name="bp-zoom-meeting-end-date-time" placeholder="yyyy-mm-dd" />
									</div>
								</label>
							</div>
							<div>
								<input type="radio" value="times" id="bp-zoom-meeting-end-times-select" name="bp-zoom-meeting-end-time-select" class="bs-styled-radio" <?php echo false === $recurrence_end_date_time ? 'checked' : ''; ?>/>
								<label for="bp-zoom-meeting-end-times-select">
									<?php esc_html_e( 'After', 'buddyboss-pro' ); ?>
									<select id="bp-zoom-meeting-end-times" name="bp-zoom-meeting-end-times">
										<?php for ( $i = 1; $i <= 20; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>" <?php echo $i === $recurrence_end_times || ( empty( $recurrence_end_times ) && 7 === $i ) ? 'selected="selected"' : ''; ?>><?php echo esc_html( $i ); ?></option>
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
				<div class="bb-video-fields-wrap">
					<div class="bb-field-wrap checkbox-row">
						<label for="bp-zoom-meeting-host-video">
							<span class="label-span"><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></span>
							<div class="bb-toggle-switch">
								<input type="checkbox" id="bp-zoom-meeting-host-video" value="yes" name="bp-zoom-meeting-host-video" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_host_video() ); ?>/>
								<span class="bb-toggle-slider"></span>
							</div>
						</label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<label for="bp-zoom-meeting-participants-video">
							<span class="label-span"><?php esc_html_e( 'Participants', 'buddyboss-pro' ); ?></span>
							<div class="bb-toggle-switch">
								<input type="checkbox" id="bp-zoom-meeting-participants-video" value="yes" name="bp-zoom-meeting-participants-video" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_participants_video() ); ?>/>
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
				<div class="bb-meeting-options-wrap">
					<?php if ( ! $disable_registration ) : ?>
						<div class="bb-field-wrap checkbox-row">
							<input type="checkbox" name="bp-zoom-meeting-registration" id="bp-zoom-meeting-registration" value="yes" class="bs-styled-checkbox" <?php checked( 1, ! empty( bp_get_zoom_meeting_registration_url() ) ); ?>/>
							<label for="bp-zoom-meeting-registration"><span><?php esc_html_e( 'Require Registration', 'buddyboss-pro' ); ?></span></label>

							<div class="bp-zoom-meeting-registration-options <?php echo ! empty( bp_get_zoom_meeting_registration_url() ) && bp_get_zoom_meeting_recurring() ? '' : 'bp-hide'; ?>">
								<input type="radio" value="1" id="bp-zoom-meeting-registration-type-1" name="bp-zoom-meeting-registration-type" class="bs-styled-radio" <?php checked( 1, ! empty( bp_get_zoom_meeting_registration_url() ) ); ?> <?php checked( 1, bp_get_zoom_meeting_registration_type() ); ?> />
								<label for="bp-zoom-meeting-registration-type-1"><span><?php esc_html_e( 'Attendees register once and can attend any of the occurrences', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="2" id="bp-zoom-meeting-registration-type-2" name="bp-zoom-meeting-registration-type" class="bs-styled-radio" <?php checked( 2, bp_get_zoom_meeting_registration_type() ); ?>/>
								<label for="bp-zoom-meeting-registration-type-2"><span><?php esc_html_e( 'Attendees need to register for each occurrence to attend', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="3" id="bp-zoom-meeting-registration-type-3" name="bp-zoom-meeting-registration-type" class="bs-styled-radio" <?php checked( 3, bp_get_zoom_meeting_registration_type() ); ?>/>
								<label for="bp-zoom-meeting-registration-type-3"><span><?php esc_html_e( 'Attendees register once and can choose one or more occurrences to attend', 'buddyboss-pro' ); ?></span></label>
							</div>
						</div>
					<?php endif; ?>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-join-before-host" value="yes" name="bp-zoom-meeting-join-before-host" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_join_before_host() ); ?> />
						<label for="bp-zoom-meeting-join-before-host"><span><?php esc_html_e( 'Enable join before host', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-mute-participants" value="yes" name="bp-zoom-meeting-mute-participants" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_mute_participants() ); ?> />
						<label for="bp-zoom-meeting-mute-participants"><span><?php esc_html_e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span></label>
					</div>

					<div class="bb-field-wrap checkbox-row">
						<input type="checkbox" id="bp-zoom-meeting-waiting-room" value="yes" name="bp-zoom-meeting-waiting-room" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_waiting_room() ); ?> />
						<label for="bp-zoom-meeting-waiting-room"><span><?php esc_html_e( 'Enable waiting room', 'buddyboss-pro' ); ?></span></label>
					</div>

					<?php if ( ! $occurrence_edit ) : ?>
						<div class="bb-field-wrap checkbox-row">
							<input type="checkbox" id="bp-zoom-meeting-authentication" value="yes" name="bp-zoom-meeting-authentication" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_authentication() ); ?> />
							<label for="bp-zoom-meeting-authentication"><span><?php esc_html_e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span></label>
						</div>
					<?php endif; ?>

					<div class="bb-field-wrap full-row">
						<?php if ( ! $disable_recording ) : ?>
							<input type="checkbox" id="bp-zoom-meeting-auto-recording" value="yes" name="bp-zoom-meeting-auto-recording" class="bs-styled-checkbox"
								<?php
								echo in_array(
									bp_get_zoom_meeting_auto_recording(),
									array(
										'local',
										'cloud',
									),
									true
								) ? 'checked' : '';
								?>
							/>
							<label for="bp-zoom-meeting-auto-recording"><span><?php esc_html_e( 'Record the meeting automatically', 'buddyboss-pro' ); ?></span></label>

							<div class="bp-zoom-meeting-auto-recording-options
							<?php
							echo in_array(
								bp_get_zoom_meeting_auto_recording(),
								array(
									'local',
									'cloud',
								),
								true
							) ? '' : 'bp-hide';
							?>
							">
								<input type="radio" value="local" id="bp-zoom-meeting-recording-local" name="bp-zoom-meeting-recording" class="bs-styled-radio" <?php checked( 'local', bp_get_zoom_meeting_auto_recording() ); ?> />
								<label for="bp-zoom-meeting-recording-local"><span><?php esc_html_e( 'On the local computer', 'buddyboss-pro' ); ?></span></label>
								<input type="radio" value="cloud" id="bp-zoom-meeting-recording-cloud" name="bp-zoom-meeting-recording" class="bs-styled-radio" <?php checked( 'cloud', bp_get_zoom_meeting_auto_recording() ); ?>/>
								<label for="bp-zoom-meeting-recording-cloud"><span><?php esc_html_e( 'In the cloud', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php else : ?>
							<div class="bb-field-wrap checkbox-row">
								<input type="checkbox" id="bp-zoom-meeting-auto-recording" value="yes" name="bp-zoom-meeting-auto-recording" class="bs-styled-checkbox" <?php checked( 'local', bp_get_zoom_meeting_auto_recording() ); ?>/>
								<label for="bp-zoom-meeting-auto-recording"><span><?php esc_html_e( 'Record automatically onto local computer', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ( ! $occurrence_edit ) : ?>

			<hr />

			<div class="bb-field-wrapper-inner">
				<div class="bb-field-wrap full-row">
					<label for="bp-zoom-meeting-host"><?php esc_html_e( 'Host', 'buddyboss-pro' ); ?></label>
					<div class="bb-meeting-input-wrap">
						<input type="text" id="bp-zoom-meeting-host" value="<?php echo esc_attr( bp_zoom_api_host_show() ); ?>" name="bp-zoom-meeting-host" disabled />
						<p class="description"><?php esc_html_e( 'Default host for all meetings in this group.', 'buddyboss-pro' ); ?></p>
					</div>
				</div>

				<?php if ( ! $disable_alt_host ) : ?>
					<div class="bb-field-wrap full-row bp-zoom-meeting-alt-host">
						<label for="bp-zoom-meeting-alt-host-ids"><?php esc_html_e( 'Alternative Hosts', 'buddyboss-pro' ); ?></label>
						<div class="bb-meeting-host-select-wrap bb-meeting-input-wrap">
							<input type="text" placeholder="<?php esc_html_e( 'Example: mary@company.com, peter@school.edu', 'buddyboss-pro' ); ?>" id="bp-zoom-meeting-alt-host-ids" name="bp-zoom-meeting-alt-host-ids" value="<?php echo esc_attr( bp_get_zoom_meeting_alternative_host_ids() ); ?>" />
							<p class="description"><?php esc_html_e( 'Additional hosts for this meeting, entered by email, comma separated. Each email added needs to match with a user in the default host\'s Zoom account.', 'buddyboss-pro' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>

		<?php endif; ?>

	</div>

	<hr />

	<footer class="bb-model-footer meeting-item text-right" data-id="<?php bp_zoom_meeting_id(); ?>" data-zoom-type="<?php echo esc_attr( bp_get_zoom_meeting_zoom_type() ); ?>" data-action="edit-cancel">
		<?php wp_nonce_field( 'bp_zoom_meeting' ); ?>
		<?php if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
			<input type="hidden" name="action" value="zoom_meeting_occurrence_edit"/>
			<input type="hidden" id="bp-zoom-meeting-zoom-occurrence-id" name="bp-zoom-meeting-zoom-occurrence-id" value="<?php bp_zoom_meeting_occurrence_id(); ?>"/>
		<?php else : ?>
			<input type="hidden" name="action" value="zoom_meeting_add" />
		<?php endif; ?>
		<input type="hidden" id="bp-zoom-meeting-id" name="bp-zoom-meeting-id" value="<?php bp_zoom_meeting_id(); ?>"/>
		<input type="hidden" id="bp-zoom-meeting-zoom-id" name="bp-zoom-meeting-zoom-id" value="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"/>
		<input type="hidden" id="bp-zoom-meeting-group-id" name="bp-zoom-meeting-group-id" value="<?php bp_zoom_meeting_group_id(); ?>"/>
		<a href="#" id="bp-zoom-meeting-cancel-edit" class="text-button small"><?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?></a>
		<a id="bp-zoom-meeting-form-submit" name="bp-zoom-meeting-form-submit" class="button submit"><?php esc_html_e( 'Update Meeting', 'buddyboss-pro' ); ?></a>
	</footer>
</div>
