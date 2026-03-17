<?php
/**
 * The template for displaying activity poll view.
 *
 * @since 2.6.00
 *
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-poll-view">
	<# if ( data && data.poll && Object.keys( data.poll ).length ) {
		var poll_end_date_timestamp = parseInt( data.poll.vote_disabled_date, 10 );
		var current_timestamp = Math.floor( new Date().getTime() / 1000 );
		var poll_closed = poll_end_date_timestamp < current_timestamp;
	#>
		<div class="bb-activity-poll_block poll_view" data-poll_id='{{data.poll.id}}' <# if( ! _.isUndefined( data.poll.item_id ) ) { #> data-activity_id='{{data.poll.item_id}}' <# } #>>
			<div class="bb-activity-poll_header">
				<h3>{{{data.poll.question}}}</h3>
				<div class="bb-activity-poll-options-wrap">
					<span class="bb-activity-poll-options-action" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More Options', 'buddyboss-pro' ); ?>">
						<i class="bb-icon-f bb-icon-ellipsis-h"></i>
					</span>
					<div class="bb-activity-poll-action-options">
						<# if (
								(
									'group' === data.object &&
									(
										! _.isUndefined( data.poll.edit_poll ) &&
										true === data.poll.edit_poll
									) ||
									(
										! _.isUndefined( data.can_create_poll_activity ) &&
										true === data.can_create_poll_activity
									)
								) &&
								! poll_closed
							) { #>
							<a href="#" class="bb-activity-poll-action-option bb-activity-poll-action-edit">
								<span class="bp-screen-reader-text"><?php esc_html_e( 'Edit Poll', 'buddyboss-pro' ); ?></span>
								<span><i class="bb-icon-l bb-icon-edit"></i> <?php esc_html_e( 'Edit', 'buddyboss-pro' ); ?></span>
							</a>
						<# } #>
						<a href="#" class="bb-activity-poll-action-option bb-activity-poll-action-delete">
							<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete Poll', 'buddyboss-pro' ); ?></span>
							<span><i class="bb-icon-l bb-icon-trash"></i> <?php esc_html_e( 'Delete', 'buddyboss-pro' ); ?></span>
						</a>
					</div>
				</div>
			</div><!-- .bb-activity-poll_header -->

			<div class="bb-activity-poll_content">
				<div class="bb-activity-poll-options">
					<# if ( data.poll.options && data.poll.options.length > 0 ) {
						_.each( data.poll.options, function( option, index ) {
							var optionUserId = parseInt( option.user_id );
							var dateRecorded = new Date( option.date_recorded ).getTime();
							var datUpdated = new Date( option.date_updated ).getTime();
							var pollUserID = parseInt( data.poll.user_id );
							#>
							<div class="bb-activity-poll-option">
								<#
								var field_name = 'radio';
								if ( data.poll.allow_multiple_options ) {
									field_name = 'checkbox';
								}
								#>
								<div class="bp-{{field_name}}-wrap bb-option-field-wrap">
									<#
									if ( ! poll_closed ) {
										if ( optionUserId !== pollUserID ) {
											if ( BP_Nouveau.activity.params.user_id === optionUserId ) { #>
												<span class="bb-activity-poll-option-note">
													<?php _e( 'Added by you', 'buddyboss-pro' ); ?>
												</span>
											<# } else {
												if ( ! _.isUndefined( option.user_data ) && ! _.isUndefined( option.user_data.user_domain ) ) { #>
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
										} #>
										<input type="{{field_name}}" class="bs-styled-{{field_name}} bb-option-input-wrap" id="bb-activity-poll-option-{{index}}" name="bb-activity-poll-option-{{index}}" value="{{option.option_title}}" data-opt_id="<# if ( option.id ) { #>{{option.id}}<# } else { #> {{index}} <# } #>"/>
									<# } #>
									<label for="bb-activity-poll-option-{{index}}"><span>{{{option.option_title}}}</span></label>
								</div>
							</div>
						<# })
					}
					if ( ! poll_closed && data.poll.allow_new_option && data.poll.options.length < 10 ) { #>
					<div class="bb-activity-poll-option bb-activity-poll-new-option">
						<span class="bb-icon-f bb-icon-plus"></span>
						<input type="text" class="bb-activity-poll-new-option-input" placeholder="<?php esc_html_e( 'Add Option', 'buddyboss-pro' ); ?>" maxlength="50"/>
					</div>
					<# } #>
				</div>
				<div class="bb-activity-poll-footer">
					<# var total_votes = ! _.isUndefined( data.poll.total_votes ) ? parseInt( data.poll.total_votes ) : 0; #>
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
								/* translators: Days left */
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
			</div><!-- .bb-activity-poll_content -->
		</div><!-- .bb-activity-poll_block -->
	<# } #>
</script>
