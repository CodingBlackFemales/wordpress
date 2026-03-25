<?php
/**
 * ReadyLaunch - The template for displaying activity poll form
 *
 * @since   2.7.50
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-poll-form">
	<#
	var poll_id = ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.id ) ? data.poll.id : 0;
	var activity_id = ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.item_id ) ? data.poll.item_id : 0;
	var total_options = ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.options ) ? data.poll.options.length : 0;
	#>
	<div class="bb-activity-poll_modal poll_form">
		<div class="bb-action-popup<# if ( 0 !== activity_id ) { #> edit-activity-poll<# } #>" id="bb-activity-poll-form_modal" style="display: none" <# if( 0 !== poll_id ) { #> data-poll_id="{{poll_id}}" <# } #> <# if( 0 !== activity_id ) { #> data-activity_id="{{activity_id}}" <# } #>>
			<transition name="modal">
				<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
					<div class="bb-rl-modal-wrapper">
						<div class="bb-rl-modal-container">
							<header class="bb-rl-modal-header">
								<h4>
									<span class="target_name">
										<# if ( 0 !== poll_id || 0 !== activity_id ) { #>
											<?php echo esc_html__( 'Edit poll', 'buddyboss-pro' ); ?>
										<# } else { #>
											<?php echo esc_html__( 'Add poll', 'buddyboss-pro' ); ?>
										<# } #>
									</span>
								</h4>
								<a class="bb-rl-modal-close-button bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
									<span class="bb-icons-rl-x"></span>
								</a>
							</header>
							<div class="bb-action-popup-content">
								<div id="message-feedabck" class="bp-messages bp-feedback bb-rl-notice bb-rl-notice--error">
									<span class="bb-icons-rl-fill" aria-hidden="true"></span>
									<p></p>
								</div>
								<label><?php esc_html_e( 'Ask a Question', 'buddyboss-pro' ); ?></label>
								<div class="input-field">
									<input type="text" name="bb-poll-question-field" class="bb-poll-question-field" placeholder="<?php esc_html_e( 'Enter your question...', 'buddyboss-pro' ); ?>" value="{{! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.question ) ? data.poll.question : ''}}">
								</div>
								<label><?php esc_html_e( 'Options', 'buddyboss-pro' ); ?></label>
								<div class="input-field bb-poll-question_options">
									<# if( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.options ) ) { #>
										<# _.each( data.poll.options, function( option, index ) { #>
											<div class="bb-poll_option_draggable">
												<#
												var fieldDisable = '';
												if( ! _.isUndefined( option.user_id ) && option.user_id ) {
													if( parseInt( option.user_id ) !== parseInt( data.poll.user_id ) ) {
														fieldDisable = 'disabled';
													} else if( 0 !== parseInt( option.total_votes ) ) {
														fieldDisable = 'disabled';
													}
												}
												#>
												<input type="text" name="bb-poll-question-option[{{index}}]" class="bb-poll-question_option" placeholder="<?php esc_html_e( 'Option', 'buddyboss-pro' ); ?>" value="{{option.option_title}}" maxlength="50" data-opt_id="<# if ( option.id ) { #>{{option.id}}<# } else { #> {{index}} <# } #>" {{fieldDisable}}>
												<a href="#" class="bb-poll-edit-option_remove" aria-label="<?php esc_attr_e( 'Remove option', 'buddyboss-pro' ); ?>"><span class="bb-icon-l bb-icon-times"></span></a>
											</div>
										<# }) #>
									<# } else { #>
										<div class="bb-poll_option_draggable">
											<input type="text" name="bb-poll-question-option[0]" class="bb-poll-question_option" placeholder="<?php esc_html_e( 'Option', 'buddyboss-pro' ); ?>" maxlength="50" data-opt_id="1">
										</div>
										<div class="bb-poll_option_draggable">
											<input type="text" name="bb-poll-question-option[1]" class="bb-poll-question_option" placeholder="<?php esc_html_e( 'Option', 'buddyboss-pro' ); ?>" maxlength="50" data-opt_id="2">
										</div>
									<# } #>
								</div>
								<div class="input-field <# if ( total_options >= 10 ) { #> bp-hide <# } #>">
									<button class="bb-rl-button bb-rl-button--secondaryFill bb-poll-option_add">
										<span class="bb-icons-rl-plus"></span> <?php esc_html_e( 'Add Option', 'buddyboss-pro' ); ?>
									</button>
								</div>
								<label><?php echo esc_html__( 'Poll Duration', 'buddyboss-pro' ); ?></label>
								<div class="input-field bb-rl-select-wrap bb-rl-mb-20">
									<select name="bb-poll-duration" class="bb-poll_duration" <# if ( 0 !== activity_id ) { #> disabled <# } #>>
										<option value="1"<# if ( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.duration ) && 1 === data.poll.duration ) { #> selected <# } #>><?php esc_html_e( '1 Day', 'buddyboss-pro' ); ?></option>
										<option value="3"
										<# if (
											(
												! _.isUndefined( data.poll ) &&
												! _.isUndefined( data.poll.duration ) &&
												3 === data.poll.duration
											) ||
											(
												_.isUndefined( data.poll ) ||
												! data.poll.duration
											)
										) { #> selected <# } #>><?php esc_html_e( '3 Days', 'buddyboss-pro' ); ?></option>
										<option value="7"<# if ( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.duration ) && 7 === data.poll.duration ) { #> selected <# } #>><?php esc_html_e( '1 Week', 'buddyboss-pro' ); ?></option>
										<option value="14"<# if ( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.duration ) && 14 === data.poll.duration ) { #> selected <# } #>><?php esc_html_e( '2 Weeks', 'buddyboss-pro' ); ?></option>
									</select>
								</div>
								<div class="input-field">
									<label>
										<div class="bb-rl-checkbox-wrap">
											<input type="checkbox" name="bb-poll-allow-multiple-answer" id="bb-poll-allow-multiple-answer" class="bb-rl-styled-checkbox"
											<# if ( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.allow_multiple_options ) && data.poll.allow_multiple_options ) { #> checked <# } #>>
											<label for="bb-poll-allow-multiple-answer">
												<?php esc_html_e( 'Allow user to choose multiple answers', 'buddyboss-pro' ); ?>
											</label>
										</div>
									</label>
								</div>
								<div class="input-field">
									<label>
										<div class="bb-rl-checkbox-wrap">
											<input type="checkbox" name="bb-poll-allow-new-option" id="bb-poll-allow-new-option" class="bb-rl-styled-checkbox"
											<# if ( ! _.isUndefined( data.poll ) && ! _.isUndefined( data.poll.allow_new_option ) && data.poll.allow_new_option ) { #> checked <# } #>>
											<label for="bb-poll-allow-new-option">
												<?php esc_html_e( 'Allow user to add new options', 'buddyboss-pro' ); ?>
											</label>
										</div>
									</label>
								</div>
							</div><!-- .bb-action-popup-content -->
							<footer class="bb-rl-model-footer">
								<a href="#" class="bb-rl-button bb-rl-button--secondaryFill bb-activity-poll-cancel"><?php esc_html_e( 'Back', 'buddyboss-pro' ); ?></a>
								<a class="bb-rl-button bb-rl-button--brandFill bb-activity-poll-submit" href="#" disabled><?php esc_html_e( 'Save and Continue', 'buddyboss-pro' ); ?></a>
							</footer>
						</div>
					</div>
				</div>
			</transition>
		</div><!-- .bb-action-popup -->
	</div><!-- .bb-activity-poll_modal -->
</script>
