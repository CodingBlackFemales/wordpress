<?php

/**
 * New/Edit Topic
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<?php if ( ! bbp_is_single_forum() ) : ?>

<div id="bbpress-forums">

	<?php bbp_breadcrumb(); ?>

<?php endif; ?>

<?php if ( bbp_is_topic_edit() ) : ?>

	<?php bbp_topic_tag_list( bbp_get_topic_id() ); ?>

<?php endif; ?>

<?php if ( bbp_current_user_can_access_create_topic_form() ) : ?>

	<?php do_action( 'bbp_theme_before_topic_form_notices' ); ?>

	<?php if ( ! bbp_is_topic_edit() && bbp_is_forum_closed() ) : ?>

		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'This forum is marked as closed to new discussions, however your posting capabilities still allow you to do so.', 'buddyboss-theme' ); ?></p>
		</div>

	<?php endif; ?>

	<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form <?php echo ( bbp_is_single_forum() ? 'bb-modal bb-modal-box' : '' ); ?>">

		<form id="new-post" name="new-post" method="post" action="<?php bbp_is_topic_edit() ? bbp_topic_edit_url() : the_permalink(); ?>">

			<?php do_action( 'bbp_theme_before_topic_form' ); ?>

			<fieldset class="bbp-form">

				<?php do_action( 'bbp_template_notices' ); ?>

				<div>

					<?php bbp_get_template_part( 'form', 'anonymous' ); ?>

					<?php do_action( 'bbp_theme_before_topic_form_title' ); ?>

					<div class="bbp_topic_title_wrapper flex">

						<div class="new_topic_title_avatar">
							<?php
							global $current_user;

							if ( is_user_logged_in() ) {
								wp_get_current_user();
								echo get_avatar( $current_user->ID, 64 );
							}
							?>
						</div>
						<div class="new_topic_title">
						  <label for="bbp_topic_title"><?php esc_html_e( 'Discussion Title', 'buddyboss-theme' ); ?></label>
						  <input required type="text" id="bbp_topic_title" value="<?php bbp_form_topic_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_title" maxlength="<?php bbp_title_max_length(); ?>" placeholder="<?php esc_html_e( 'Discussion Title', 'buddyboss-theme' ); ?>" />
						</div>

						<a href="#" id="bbp-close-btn" class="js-modal-close bb-model-close-button">
							<span class="bb-icon bb-icon-close"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Cancel', 'buddyboss-theme' ); ?></span>
						</a>
					</div>

					<?php do_action( 'bbp_theme_after_topic_form_title' ); ?>

					<?php do_action( 'bbp_theme_before_topic_form_content' ); ?>

					<?php bbp_the_content( array( 'context' => 'topic' ) ); ?>

					<?php do_action( 'bbp_theme_after_topic_form_content' ); ?>

					<?php if ( ! ( bbp_use_wp_editor() || current_user_can( 'unfiltered_html' ) ) ) : ?>

						<p class="form-allowed-tags">
							<label><?php _e( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:', 'buddyboss-theme' ); ?></label><br />
							<code><?php bbp_allowed_tags(); ?></code>
						</p>

					<?php endif; ?>

					<?php bbp_get_template_part( 'form', 'attachments' ); ?>

					<?php if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) ) : ?>

						<?php do_action( 'bbp_theme_before_topic_form_tags' ); ?>

						<?php
						$get_topic_id = bbp_get_topic_id();
						$get_the_tags = isset( $get_topic_id ) && ! empty( $get_topic_id ) ? bbp_get_topic_tag_names( $get_topic_id ) : array();
						?>

						<p class="bbp_topic_tags_wrapper">
							<input type="hidden" value="<?php echo ( ! empty( $get_the_tags ) ) ? esc_attr( $get_the_tags ) : ''; ?>" name="bbp_topic_tags" class="bbp_topic_tags" id="bbp_topic_tags" >
							<select name="bbp_topic_tags_dropdown[]" class="bbp_topic_tags_dropdown" id="bbp_topic_tags_dropdown" placeholder="<?php esc_html_e( 'Type one or more tags, comma separated', 'buddyboss-theme' ); ?>" autocomplete="off" multiple="multiple" style="width: 100%" tabindex="<?php bbp_tab_index(); ?>">
								<?php
								if ( ! empty( $get_the_tags ) ) {
									$get_the_tags = explode( ',', $get_the_tags );
									foreach ( $get_the_tags as $single_tag ) {
										$single_tag = trim( $single_tag );
										?>
										<option selected="selected" value="<?php echo esc_attr( $single_tag ); ?>"><?php echo esc_html( $single_tag ); ?></option>
										<?php
									}
								}
								?>
							</select>
						</p>

						<?php do_action( 'bbp_theme_after_topic_form_tags' ); ?>

					<?php endif; ?>


					<?php if ( bbp_allow_revisions() && bbp_is_topic_edit() ) : ?>

						<div class="bb-form_rev_wrapper flex">

							<?php do_action( 'bbp_theme_before_topic_form_revisions' ); ?>

							<fieldset class="bbp-form">
								<div class="flex">
									<legend>
										<input name="bbp_log_topic_edit" id="bbp_log_topic_edit" class="bs-styled-checkbox" type="checkbox" value="1" <?php bbp_form_topic_log_edit(); ?> tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_log_topic_edit"><?php esc_html_e( 'Keep a log of this edit:', 'buddyboss-theme' ); ?></label>
									</legend>

									<div>
										<label for="bbp_topic_edit_reason"><?php printf( __( 'Optional reason for editing:', 'buddyboss-theme' ), bbp_get_current_user_name() ); ?></label>
										<input type="text" value="<?php bbp_form_topic_edit_reason(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_edit_reason" id="bbp_topic_edit_reason" placeholder="<?php esc_html_e( 'Optional reason for editing', 'buddyboss-theme' ); ?>" />
									</div>
								</div>
							</fieldset>

							<?php do_action( 'bbp_theme_after_topic_form_revisions' ); ?>

						</div>

					<?php endif; ?>


					<div class="bb-form-select-fields flex">

						<?php if ( ! bbp_is_single_forum() ) : ?>
							<div class="bbp_topic_form_forum_wrapper">
							<?php do_action( 'bbp_theme_before_topic_form_forum' ); ?>

								<label for="bbp_forum_id"><?php esc_html_e( 'Forum:', 'buddyboss-theme' ); ?></label>
								<?php
									bbp_dropdown(
										array(
											'show_none' => __( '(No Forum)', 'buddyboss-theme' ),
											'selected'  => bbp_get_form_topic_forum(),
										)
									);
								?>

							<?php do_action( 'bbp_theme_after_topic_form_forum' ); ?>
							</div>
						<?php endif; ?>


						<?php if ( current_user_can( 'moderate' ) ) : ?>
							<div class="bbp_topic_form_type_wrapper">
							<?php do_action( 'bbp_theme_before_topic_form_type' ); ?>

							<label for="bbp_stick_topic"><?php esc_html_e( 'Type:', 'buddyboss-theme' ); ?></label>

							<?php bbp_form_topic_type_dropdown(); ?>

							<?php do_action( 'bbp_theme_after_topic_form_type' ); ?>
							</div>
						<?php endif; ?>


						<?php
						if (
							(
								(
									function_exists( 'bb_is_enabled_subscription' ) &&
									bb_is_enabled_subscription( 'forum' )
								) ||
								(
									! function_exists( 'bb_is_enabled_subscription' ) &&
									bbp_is_subscriptions_active()
								)
							) &&
							! bbp_is_anonymous() &&
							(
								! bbp_is_topic_edit() ||
								( bbp_is_topic_edit() && ! bbp_is_topic_anonymous() )
							)
						) :
							if (
								! function_exists( 'bb_enabled_legacy_email_preference' ) ||
								(
									function_exists( 'bb_enabled_legacy_email_preference' ) &&
									(
										bb_enabled_legacy_email_preference() ||
										( ! bb_enabled_legacy_email_preference() && bb_get_modern_notification_admin_settings_is_enabled( 'bb_forums_subscribed_discussion' ) )
									)
								)
							) {
								?>
								<div class="bbp_topic_subscription_wrapper">
								<?php do_action( 'bbp_theme_before_topic_form_subscriptions' ); ?>

								<input name="bbp_topic_subscription" id="bbp_topic_subscription" class="bs-styled-checkbox" type="checkbox" value="bbp_subscribe" <?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />

								<?php
								if ( bbp_is_topic_edit() && ( bbp_get_topic_author_id() !== bbp_get_current_user_id() ) ) :
									if (
										(
											function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference()
										) ||
										(
											function_exists( 'bp_is_active' ) &&
											! bp_is_active( 'notifications' )
										)
									) {
										?>
										<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify the author of replies via email', 'buddyboss-theme' ); ?></label>
										<?php
									} else {
										?>
										<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify the author of replies', 'buddyboss-theme' ); ?></label>
										<?php
									}
								else :
									if (
										(
											function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference()
										) ||
										(
											function_exists( 'bp_is_active' ) &&
											! bp_is_active( 'notifications' )
										)
									) {
										?>
										<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify me of new replies by email', 'buddyboss-theme' ); ?></label>
										<?php
									} else {
										?>
										<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify me of new replies', 'buddyboss-theme' ); ?></label>
										<?php
									}
								endif;
								?>

								<?php do_action( 'bbp_theme_after_topic_form_subscriptions' ); ?>
								</div>
							<?php } ?>
						<?php endif; ?>

						<?php do_action( 'bbp_theme_before_topic_form_submit_wrapper' ); ?>

						<div class="bbp-submit-wrapper">

							<?php do_action( 'bbp_theme_before_topic_form_submit_button' ); ?>

							<button type="button" tabindex="<?php bbp_tab_index(); ?>" id="bb_topic_discard_draft" name="bb_topic_discard_draft" class="button outline discard small bb_discard_topic_reply_draft"><?php esc_html_e( 'Discard Draft', 'buddyboss-theme' ); ?></button>

							<button type="button" tabindex="<?php bbp_tab_index(); ?>" id="bbp_topic_submit" name="bbp_topic_submit" class="button submit small"><?php esc_html_e( 'Post', 'buddyboss-theme' ); ?></button>

							<?php do_action( 'bbp_theme_after_topic_form_submit_button' ); ?>

						</div>

						<?php do_action( 'bbp_theme_after_topic_form_submit_wrapper' ); ?>


					</div>

				</div>

				<?php bbp_topic_form_fields(); ?>

			</fieldset>

			<?php do_action( 'bbp_theme_after_topic_form' ); ?>

		</form>
	</div>

<?php elseif ( bbp_is_forum_closed() ) : ?>

	<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php printf( __( 'The forum "%s" is closed to new discussions and replies.', 'buddyboss-theme' ), bbp_get_forum_title() ); ?></p>
		</div>
	</div>

<?php else : ?>

	<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php is_user_logged_in() ? esc_html_e( 'You cannot create new discussions.', 'buddyboss-theme' ) : esc_html_e( 'You must be logged in to create new discussions.', 'buddyboss-theme' ); ?></p>
		</div>
	</div>

<?php endif; ?>

<?php if ( ! bbp_is_single_forum() ) : ?>

</div>

<?php endif; ?>
