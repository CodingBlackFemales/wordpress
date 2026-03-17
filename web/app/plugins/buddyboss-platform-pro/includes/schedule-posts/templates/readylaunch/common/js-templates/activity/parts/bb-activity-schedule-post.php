<?php
/**
 * ReadyLaunch - The template for displaying schedule activity post form.
 *
 * @since 2.7.50
 *
 * @package  BuddyBossPro
 *
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-schedule-post">
	<?php
	if ( bp_is_active( 'activity' ) ) :
		?>
		<# if ( false === data.edit_activity || 'scheduled' === data.activity_action_type ) {
		#>
		<div class="bb-schedule-post_dropdown_section bp-hide">
			<a href="#" class="bb-schedule-post_dropdown_button {{data.activity_action_type === 'scheduled' ? 'is_scheduled' : ''}}">
				<i class="bb-icons-rl-clock"></i>
				<# if ( data.activity_schedule_date && data.activity_schedule_time ) {  #>
					<span class="activity-post-schedule-details">
						{{{data.activity_schedule_date}}} <?php esc_html_e( 'at', 'buddyboss-pro' ); ?> {{{data.activity_schedule_time}}} <span class="activity-post-meridiem">{{{data.activity_schedule_meridiem}}}</span>
					</span>
				<# } else { #>
					<span><?php echo esc_html__( 'Schedule', 'buddyboss-pro' ); ?></span>
				<# } #>
				<i class="bb-icons-rl-caret-down"></i>
			</a>
			<div class="bb-schedule-post_dropdown_list">
				<ul>
					<li>
						<a href="#" class="bb-schedule-post_action"><i class="bb-icons-rl-calendar-blank"></i>{{{data.activity_schedule_date && data.activity_schedule_time ? '<?php echo esc_html__( 'Edit schedule', 'buddyboss-pro' ); ?>' : '<?php echo esc_html__( 'Schedule post', 'buddyboss-pro' ); ?>'}}}
						</a>
					</li>
					<li>
						<a href="#" id="bb-view-schedule-posts" class="bb-view-schedule-posts"><i class="bb-icons-rl-calendar-check"></i><?php echo esc_html__( 'View scheduled posts', 'buddyboss-pro' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div class="bb-schedule-post_modal">
				<div class="bb-action-popup" id="bb-schedule-post_form_modal" style="display: none">
					<transition name="modal">
						<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
							<div class="bb-rl-modal-wrapper">
								<div class="bb-rl-modal-container ">
									<header class="bb-rl-modal-header">
										<h4>
											<span class="target_name"><?php echo esc_html__( 'Schedule post', 'buddyboss-pro' ); ?></span>
										</h4>
										<a class="bb-rl-modal-close-button bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
											<span class="bb-icons-rl-x"></span>
										</a>
									</header>
									<div class="bb-rl-modal-content">
										<?php
										$formatted_date = wp_date( get_option( 'date_format' ) );
										$formatted_time = wp_date( get_option( 'time_format' ) );
										?>
										<p class="schedule-date"><?php echo esc_html( $formatted_date ); ?> <?php echo esc_html__( 'at', 'buddyboss-pro' ); ?>
											<span class="bb-server-time"><?php echo esc_html( $formatted_time ); ?></span>
										</p>

										<label><?php echo esc_html__( 'Date', 'buddyboss-pro' ); ?></label>
										<div class="input-field">
											<input type="text" name="bb-schedule-activity-date-field" class="bb-schedule-activity-date-field" placeholder="dd/mm/yy" value="{{data.activity_schedule_date ? data.activity_schedule_date : ''}}">
											<input type="hidden" name="activity_schedule_date_raw" class="bb-schedule-activity-date-raw" value="{{data.activity_schedule_date_raw ? data.activity_schedule_date_raw : ''}}">
											<i class="bb-icons-rl-calendar-blank"></i>
										</div>

										<label><?php echo esc_html__( 'Time', 'buddyboss-pro' ); ?></label>
										<div class="input-field-inline">
											<div class="input-field bb-schedule-activity-time-wrap">
												<input type="text" name="bb-schedule-activity-time-field" class="bb-schedule-activity-time-field" placeholder="hh:mm" value="{{data.activity_schedule_time ? data.activity_schedule_time : ''}}">
												<i class="bb-icons-rl-clock"></i>
											</div>
										</div>
										<div class="input-field-inline">
											<div class="input-field bb-schedule-activity-meridian-wrap">
												<label for="bb-schedule-activity-meridian-am">
													<input type="radio" value="am" id="bb-schedule-activity-meridian-am" name="bb-schedule-activity-meridian" <# if ( data.activity_schedule_meridiem == 'am' ) { #> checked <# } #>>
													<span class="bb-time-meridian"><?php echo esc_html__( 'AM', 'buddyboss-pro' ); ?></span>
												</label>
												<label for="bb-schedule-activity-meridian-pm">
													<input type="radio" value="pm" id="bb-schedule-activity-meridian-pm" name="bb-schedule-activity-meridian" <# if ( data.activity_schedule_meridiem == 'pm' || data.activity_schedule_meridiem == undefined ) { #> checked <# } #>>
													<span class="bb-time-meridian"><?php echo esc_html__( 'PM', 'buddyboss-pro' ); ?></span>
												</label>
											</div>
										</div>
									</div>

									<footer class="bb-rl-model-footer flex">
										<div>
											<a href="#" class="bb-view-all-scheduled-posts"><?php echo esc_html__( 'View scheduled posts', 'buddyboss-pro' ); ?>
												<i class="bb-icons-rl-arrow-right"></i>
											</a>
										</div>
										<div>
											<a href="#" class="bb-rl-button bb-rl-button--secondaryFill bb-schedule-activity-clear"><?php echo esc_html__( 'Cancel', 'buddyboss-pro' ); ?></a>
											<a class="bb-rl-button bb-rl-button--brandFill bb-schedule-activity" href="#" disabled><?php echo esc_html__( 'Schedule', 'buddyboss-pro' ); ?></a>
										</div>
									</footer>
								</div>
							</div>
						</div>
					</transition>
				</div> <!-- .bb-action-popup -->
			</div>

			<div class="bb-schedule-posts_modal">
				<div class="bb-action-popup bb-view-schedule-posts_modal" id="bb-schedule-posts_modal" style="display: none">
					<transition name="modal">
						<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
							<div class="bb-rl-modal-wrapper">
								<div class="bb-rl-modal-container ">
									<header class="bb-rl-modal-header">
										<h4>
											<span class="target_name"><?php echo esc_html__( 'Scheduled posts', 'buddyboss-pro' ); ?></span>
											<span class="bb-rl-schedule-post-count bb-rl-heading-count"></span>
										</h4>
										<a class="bb-rl-modal-close-button bb-close-action-popup bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
											<span class="bb-icons-rl-x"></span>
										</a>
									</header>
									<div class="bb-rl-modal-content bb-action-popup-content">
										<div class="schedule-posts-placeholder_loader">
											<i class="bb-rl-loader"></i>
										</div>
										<div class="schedule-posts-placeholder">
											<i class="bb-icons-rl-empty"></i>
											<h2><?php echo esc_html__( 'No Scheduled Posts Found', 'buddyboss-pro' ); ?></h2>
											<p><?php echo esc_html__( 'You do not have any posts scheduled at the moment.', 'buddyboss-pro' ); ?></p>
										</div>
										<div class="schedule-posts-content"></div>
									</div>
								</div>
							</div>
						</div>
					</transition>
				</div> <!-- .bb-action-popup -->
			</div>
		</div>
		<# } #>
	<?php endif; ?>
</script>
