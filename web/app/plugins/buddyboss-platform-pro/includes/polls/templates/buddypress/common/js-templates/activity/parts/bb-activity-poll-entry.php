<script type="text/html" id="tmpl-bb-activity-poll-entry">
	<#
	var getPoll = data.poll;
	var pollId = getPoll.id;
	var getPollOptions = data.all_options ? Object.values( data.all_options ) : [];
	var total_votes = data.total_votes;
	var settings = getPoll.settings;
	var allow_multiple_options = settings.allow_multiple_options;
	var allow_new_option = settings.allow_new_option;
	var duration = settings.duration;
	var pollUserID = parseInt( data.poll.user_id );
	var poll_end_date_timestamp = parseInt( getPoll.vote_disabled_date, 10 );
	var current_timestamp = Math.floor( new Date().getTime() / 1000 );
	var poll_closed = poll_end_date_timestamp < current_timestamp;
	var votedOptionId = data.vote_data ? parseInt( data.vote_data.option_id ) : 0;
	var optionIndex = 0;
	var see_less_class = '';
	#>
	<div class="bb-activity-poll_header">
		<h3>{{getPoll.question}}</h3>
	</div>

	<div class="bb-activity-poll_content">
		<div class="bb-activity-poll-options">
			<# if ( getPollOptions && getPollOptions.length > 0 ) {
				optionIndex = getPollOptions.findIndex( option => option.id === votedOptionId );
				_.each( getPollOptions, function( option, index ) {
					var more_class = '';

					if ( index > 4 ) {
						more_class = 'bb-activity-poll-option-hide';
					}

					if ( optionIndex > 4 ) {
						more_class = '';
						see_less_class = 'see-less';
					}

					var optionUserId = parseInt( option.user_id );
					var dateRecorded = new Date( option.date_recorded ).getTime();
					var datUpdated = new Date( option.date_updated ).getTime();

					var option_percentage = 0;
					if ( total_votes ) {
						 option_percentage = Math.round(( option.total_votes / total_votes ) * 10000 ) / 100;
					} #>
					<div class="bb-activity-poll-option {{more_class}}">
						<#
						var style = '';
						if ( option_percentage ) {
							style = "style=width:" + option_percentage + "%;";
						}
						#>
						<div class="bb-poll-option-fill" {{style}}></div>
						<#
						var field_name = 'radio';
						if ( allow_multiple_options ) {
							field_name = 'checkbox';
						}
						#>
						<div class="bp-{{field_name}}-wrap bb-option-field-wrap">
							<# if ( ! poll_closed ) {
								if ( optionUserId !== pollUserID ) {
									if ( BP_Nouveau.activity.params.user_id === optionUserId ) { #>
										<span class="bb-activity-poll-option-note"><?php esc_html_e( 'Added by you', 'buddyboss-pro' ); ?></span>
									<# } else { #>
										<span class="bb-activity-poll-option-note">
											<?php
											printf(
											/* translators: %s: User link */
												__( 'Added by %s', 'buddyboss-pro' ),
												sprintf(
													'<a href="%s" target="_blank">%s</a>',
													'{{{option.user_data.user_domain}}}',
													'{{{option.user_data.username}}}'
												)
											);
											?>
										</span>
									<# }
								}
								if ( BP_Nouveau.activity.params.user_id ) { #>
									<input type="{{field_name}}"
									       class="bs-styled-{{field_name}} bb-option-input-wrap"
									       id="bb-activity-poll-option-{{pollId}}{{index}}"
									       name="bb-activity-poll-option-{{pollId}}"
									       value="{{option.option_title}}"
									       data-opt_id="<# if ( option.id ) { #>{{option.id}}<# } else { #> {{index}} <# } #>"
									       <# if ( option.is_selected ) { #>checked="checked"<# } else { #> '' <# } #>
									/>
								<# }
							} #>
							<label for="bb-activity-poll-option-{{pollId}}{{index}}">
								<span>{{option.option_title}}</span>
							</label>
						</div>
						<div class="bb-poll-right">
							<span class="bb-poll-option-state">
								<# if ( option_percentage ) { #>{{option_percentage}}<# } else { #>0<# } #>%
							</span>
							<a href="#" class="<# if ( option_percentage ) { #> bb-poll-option-view-state  <# } else { #> bb-poll-no-vote <# } #>" data-opt_id="<# if ( option.id ) { #>{{option.id}}<# } else { #> {{index}} <# } #>" aria-label="<?php esc_attr_e( 'View State', 'buddyboss-pro' ); ?>"><i class="bb-icon-angle-right"></i></a>
						</div>
						<#
						if (
							! poll_closed &&
							optionUserId !== pollUserID &&
							BP_Nouveau.activity.params.user_id === optionUserId
						) { #>
							<a href="#" class="bb-poll-option_remove" role="button" aria-label="<?php esc_html_e( 'Remove Option', 'buddyboss-pro' ); ?>"><span class="bb-icon-l bb-icon-times"></span></a>
							<#
						}
						#>
					</div>
					<#
				} );
			}
			var show_hide_new_option = 'bb-activity-poll-option-hide';
			if (
				BP_Nouveau.activity.params.user_id &&
				! poll_closed &&
				allow_new_option &&
				getPollOptions &&
				getPollOptions.length < 10
			) {
				show_hide_new_option = '';
			}
			#>
			<div class="bb-activity-poll-option bb-activity-poll-new-option {{show_hide_new_option}}">
				<span class="bb-icon-f bb-icon-plus"></span>
				<input type="text" class="bb-activity-poll-new-option-input" placeholder="<?php esc_html_e( 'Add Option', 'buddyboss-pro' ); ?>" maxlength="50"/>
				<a href="#" class="bb-activity-option-submit">
					<span class="bb-icon-f bb-icon-plus"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Submit option', 'buddyboss-pro' ); ?></span>
				</a>
			</div>
			<div class="bb-poll-error"><?php esc_html_e( 'This is already an option', 'buddyboss-pro' ); ?></div>
			<div class="bb-poll-error limit-error"></div>
			<# if ( getPollOptions.length > 5 ) { #>
				<div class="bb-activity-poll-see-more">
					<a href="#" class="bb-activity-poll-see-more-link {{see_less_class}}" role="button">
						<span class="bb-poll-see-more-text"><?php esc_html_e( 'See All', 'buddyboss-pro' ); ?></span>
						<span class="bb-poll-see-less-text"><?php esc_html_e( 'See Less', 'buddyboss-pro' ); ?></span>
					</a>
				</div>
			<# } #>
			<div class="bb-activity-poll-footer">
				<span class="bb-activity-poll_votes">
					<# if ( 1 === total_votes ) { #>
						<?php
						/* translators: %d: Total votes */
						echo sprintf( esc_html__( '%s vote', 'buddyboss-pro' ), '{{{total_votes}}}' );
						?>
					<# } else { #>
						<?php
						/* translators: %d: Total votes */
						echo sprintf( esc_html__( '%s votes', 'buddyboss-pro' ), '{{{total_votes}}}' );
						?>
					<# } #>
				</span>
				<span class="bb-activity-poll_duration">
					<# if ( poll_closed ) { #>
						<?php esc_html_e( 'Poll Closed', 'buddyboss-pro' ); ?>
					<# } else {
						var difference_in_seconds = poll_end_date_timestamp - current_timestamp;
						var days_left = Math.floor( difference_in_seconds / ( 60 * 60 * 24 ) );
						var exact_days = difference_in_seconds % ( 60 * 60 * 24 ) === 0;
						var hours_left = Math.floor((difference_in_seconds % (60 * 60 * 24)) / (60 * 60));
						var minutes_left = Math.floor((difference_in_seconds % (60 * 60)) / 60);
						var seconds_left = difference_in_seconds % 60;
						if ( exact_days && days_left >= 1 ) { #>
							<?php
							/* translators: Days left */
							printf( __( '%sd left', 'buddyboss-pro' ), '{{{days_left}}}' );
							?>
						<# } else if ( days_left >= 1 ) { #>
							<?php
							/* translators: Hours, minutes left */
							printf( __( '%sd %sh %sm left', 'buddyboss-pro' ), '{{{days_left}}}', '{{{hours_left}}}', '{{{minutes_left}}}' );
							?>
						<# } else if ( hours_left > 0 ) { #>
							<?php
							/* translators: Hours, minutes left */
							printf( __( '%sh %sm left', 'buddyboss-pro' ), '{{{hours_left}}}', '{{{minutes_left}}}' );
							?>
						<# } else if ( minutes_left > 0 ) { #>
							<?php
							/* translators: Minutes, seconds left */
							printf( __( '%sm left', 'buddyboss-pro' ), '{{{minutes_left}}}' )
							?>
						<# } else { #>
							<?php
							/* translators: Seconds left */
							printf( __( '%ss left', 'buddyboss-pro' ), '{{{seconds_left}}}' )
							?>
						<# }
					} #>
				</span>
			</div>
		</div>
	</div>
</script>

