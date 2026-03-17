<?php
/**
 * ReadyLaunch - Groups - Zoom Settings Edit.
 *
 * @since 2.7.70
 *
 * @version 1.0.0
 *
 * @package BuddyBoss\Platform\Pro\Integrations\Zoom
 * @subpackage Templates\BuddyPress\Groups\Single
 */

defined( 'ABSPATH' ) || exit;

$group_id        = $args['group_id'];
$checked         = $args['checked'];
$connection_type = $args['connection_type'];
$account_id      = $args['account_id'];
$client_id       = $args['client_id'];
$client_secret   = $args['client_secret'];
$s2s_api_email   = $args['s2s_api_email'];
$secret_token    = $args['secret_token'];
$account_emails  = $args['account_emails'];
$bb_group_zoom   = $args['bb_group_zoom'];
$notice_exists   = $args['notice_exists'];
$current_tab     = $args['current_tab'];

?>

<div class="bb-group-zoom-settings-container">

	<?php if ( ! empty( $notice_exists ) ) { ?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback <?php echo esc_attr( $notice_exists['type'] ); ?>-notice">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php echo esc_html( $notice_exists['message'] ); ?></p>
			</div>
		</div>
		<?php
		delete_transient( 'bb_group_zoom_notice_' . $group_id );
	}
	?>

	<div class="bb-section-title-wrap">
		<h4 class="bb-section-title bb-section-main">
			<i class="bb-icon-rf bb-icon-brand-zoom"></i>
			<?php esc_html_e( 'Zoom', 'buddyboss-pro' ); ?>
		</h4>
		<?php if ( 'site' !== $connection_type ) { ?>
			<a href="#bp-zoom-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" class="bb-wizard-button show-zoom-instructions" id="bp-zoom-group-show-instructions">
				<?php esc_html_e( 'Setup Guide', 'buddyboss-pro' ); ?>
			</a>
		<?php } ?>
	</div>

	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Zoom', 'buddyboss-pro' ); ?></legend>
		<p class="bb-section-info"><?php esc_html_e( 'Create and sync Zoom meetings and webinars directly within this group by connecting your Zoom account.', 'buddyboss-pro' ); ?></p>

		<div class="field-group">
			<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
				<input type="checkbox" name="bp-edit-group-zoom" id="bp-edit-group-zoom" class="bs-styled-checkbox" value="1" <?php checked( $checked ); ?> />
				<label for="bp-edit-group-zoom"><span><?php esc_html_e( 'Yes, I want to connect this group to Zoom.', 'buddyboss-pro' ); ?></span></label>
			</p>
		</div>
	</fieldset>

	<div class="bb-zoom-setting-tab <?php echo ! $checked ? 'bp-hide' : ''; ?>">
		<div class="bb-zoom-setting-tabs">
			<input type="hidden" class="tab-selected" name="bb-zoom-tab" value="<?php echo esc_attr( $current_tab ); ?>">
			<ul role="tablist" aria-label="<?php echo esc_attr( 'Zoom settings tabs' ); ?>">
				<li>
					<a href="#bp-group-zoom-settings-authentication" class="<?php echo ( 's2s' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tab" aria-selected="<?php echo esc_attr( ( 's2s' === $current_tab ) ); ?>" aria-controls="panel-1" id="tab-1" data-value="s2s"><?php esc_html_e( 'Authentication', 'buddyboss-pro' ); ?></a>
				</li>
				<li>
					<a href="#bp-group-zoom-settings-additional" class="<?php echo ( 'permissions' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tab" aria-selected="<?php echo esc_attr( ( 'permissions' === $current_tab ) ); ?>" aria-controls="bp-group-zoom-settings-additional" id="tab-2" data-value="permissions"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></a>
				</li>
			</ul>
		</div><!-- .bb-zoom-setting-tabs -->
		<div class="bb-zoom-setting-content">

			<div id="bp-group-zoom-settings-authentication" class="bb-zoom-setting-content-tab bp-group-zoom-settings-authentication <?php echo ( 's2s' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tabpanel" aria-labelledby="tab-1">
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Authentication', 'buddyboss-pro' ); ?></legend>

					<?php
					if ( 'site' === $connection_type ) {
						bb_zoom_group_display_feedback_notice(
							esc_html__( "This group has been connected to the site's Zoom account by a site administrator.", 'buddyboss-pro' ),
							'success'
						);
					} else {
						?>
						<p class="group-setting-label bb-zoom-setting-description">
							<?php
							printf(
							/* translators: Added bold HTML tag. */
								esc_html__( 'To connect your Zoom account to this group, create a %s app in your Zoom account and enter the information in the fields below.', 'buddyboss-pro' ),
								sprintf(
								/* translators: OAuth app name. */
									'<strong>%s</strong>',
									esc_html__( 'Server-to-Server OAuth', 'buddyboss-pro' )
								)
							);
							?>
						</p>

						<div class="bb-group-zoom-s2s-notice bb-group-zoom-s2s-notice-form">
							<?php
							if ( ! empty( $bb_group_zoom ) ) {
								$errors   = $bb_group_zoom['zoom_errors'] ?? array();
								$warnings = $bb_group_zoom['zoom_warnings'] ?? array();
								$success  = $bb_group_zoom['zoom_success'] ?? '';

								if ( ! empty( $errors ) ) {
									$error_message = array();
									foreach ( $errors as $error ) {
										$error_message[] = esc_html( $error->get_error_message() );
									}
									bb_zoom_group_display_feedback_notice( $error_message );
									$bb_group_zoom['zoom_errors'] = array();
								} elseif ( ! empty( $warnings ) ) {
									$warning_message = array();
									foreach ( $warnings as $warning ) {
										$warning_message[] = $warning->get_error_message();
									}
									bb_zoom_group_display_feedback_notice( $warning_message, 'warning' );
									$bb_group_zoom['zoom_warnings'] = array();
								} elseif ( ! empty( $success ) ) {
									bb_zoom_group_display_feedback_notice( $success, 'success' );
									$bb_group_zoom['zoom_success'] = '';
								}

								groups_update_groupmeta( $group_id, 'bb-group-zoom', $bb_group_zoom );
							}
							?>
						</div>

						<div class="bb-field-wrap">
							<label for="bb-group-zoom-s2s-account-id" class="group-setting-label"><?php esc_html_e( 'Account ID', 'buddyboss-pro' ); ?></label>
							<div class="bp-input-wrap">
								<div class="password-toggle">
									<input type="password" name="bb-group-zoom-s2s-account-id" id="bb-group-zoom-s2s-account-id" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $account_id ); ?>" />
									<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
										<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
									</button>
								</div>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Account ID from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>

						<div class="bb-field-wrap">
							<label for="bb-group-zoom-s2s-client-id" class="group-setting-label"><?php esc_html_e( 'Client ID', 'buddyboss-pro' ); ?></label>
							<div class="bp-input-wrap">
								<div class="password-toggle">
									<input type="password" name="bb-group-zoom-s2s-client-id" id="bb-group-zoom-s2s-client-id" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $client_id ); ?>" />
									<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
										<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
									</button>
								</div>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client ID from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>

						<div class="bb-field-wrap">
							<label for="bb-group-zoom-s2s-client-secret" class="group-setting-label"><?php esc_html_e( 'Client Secret', 'buddyboss-pro' ); ?></label>
							<div class="bp-input-wrap">
								<div class="password-toggle">
									<input type="password" name="bb-group-zoom-s2s-client-secret" id="bb-group-zoom-s2s-client-secret" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $client_secret ); ?>" />
									<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
										<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
									</button>
								</div>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client Secret from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>

						<div class="bb-field-wrap bb-zoom_account-email">
							<label for="bb-group-zoom-s2s-api-email" class="group-setting-label"><?php esc_html_e( 'Account Email', 'buddyboss-pro' ); ?>
								<span class="bb-icon-f bb-icon-spinner animate-spin"></span></label>
							<div class="bp-input-wrap">
								<?php
								$is_disabled_email = 'is-disabled';
								if ( 1 < count( $account_emails ) ) {
									$is_disabled_email = '';
								}
								?>
								<select name="bb-group-zoom-s2s-api-email" id="bb-group-zoom-s2s-api-email" class="<?php echo esc_attr( $is_disabled_email ); ?>">
									<?php
									if ( ! empty( $account_emails ) ) {
										foreach ( $account_emails as $email_key => $email_label ) {
											echo '<option value="' . esc_attr( $email_key ) . '" ' . selected( $s2s_api_email, $email_key, false ) . '>' . esc_attr( $email_label ) . '</option>';
										}
									} else {
										echo '<option value="">- ' . esc_html__( 'Select a Zoom account', 'buddyboss-pro' ) . ' -</option>';
									}
									?>
								</select>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Select the Zoom account to sync Zoom meetings and webinars from.', 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>

						<div class="bb-field-wrap">
							<label for="bb-group-zoom-s2s-secret-token" class="group-setting-label"><?php esc_html_e( 'Secret Token', 'buddyboss-pro' ); ?></label>
							<div class="bp-input-wrap">
								<div class="password-toggle">
									<input type="password" name="bb-group-zoom-s2s-secret-token" id="bb-group-zoom-s2s-secret-token" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $secret_token ); ?>" />
									<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
										<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
									</button>
								</div>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Enter the Secret Token from the Features section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>

						<div class="bb-field-wrap">
							<label for="bb-group-zoom-s2s-notification-url" class="group-setting-label"><?php esc_html_e( 'Notification URL', 'buddyboss-pro' ); ?></label>
							<div class="bp-input-wrap">
								<div class="copy-toggle">
									<input type="text" name="bb-group-zoom-s2s-notification-url" id="bb-group-zoom-s2s-notification-url" class="zoom-group-instructions-main-input is-disabled" value="<?php echo esc_url( trailingslashit( bp_get_root_domain() ) . '?zoom_webhook=1&group_id=' . $group_id ); ?>" />
									<span role="button" class="bb-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss-pro' ); ?>">
										<i class="bb-icon-f bb-icon-copy"></i>
									</span>
								</div>
								<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Use as the Event notification endpoint URL when configuring Event Subscriptions in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
									<i class="bb-icon-rf bb-icon-question"></i>
								</span>
							</div>
						</div>
					<?php } ?>
				</fieldset>
			</div>

			<div id="bp-group-zoom-settings-additional" class="bb-zoom-setting-content-tab group-settings-selections <?php echo ( 'permissions' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tabpanel" aria-labelledby="tab-2">
				<fieldset class="radio group-media">
					<legend class="screen-reader-text"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></legend>
					<p class="group-setting-label bb-zoom-setting-description"><?php esc_html_e( 'Which members of this group are allowed to create, edit and delete Zoom meetings?', 'buddyboss-pro' ); ?></p>

					<?php $zoom_status = bp_zoom_group_get_manager( $group_id ); ?>
					<select name="bp-group-zoom-manager" id="bp-group-zoom-manager">
						<option value="admins" <?php selected( $zoom_status, 'admins' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss-pro' ); ?></option>
						<option value="mods" <?php selected( $zoom_status, 'mods' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss-pro' ); ?></option>
						<option value="members" <?php selected( $zoom_status, 'members' ); ?>><?php esc_html_e( 'All group members', 'buddyboss-pro' ); ?></option>
					</select>

					<p class="group-setting-label bb-zoom-setting-description"><?php esc_html_e( 'The Zoom account connected to this group will be assigned as the default host for every meeting and webinar, regardless of which member they are created by.', 'buddyboss-pro' ); ?></p>
				</fieldset>
			</div><!-- #bp-group-zoom-settings-additional -->

		</div><!-- .bb-zoom-setting-content -->

	</div> <!-- .bb-zoom-setting-tab -->

	<div class="bp-zoom-group-button-wrap">

		<button type="submit" class="bb-save-settings"><?php esc_html_e( 'Save Changes', 'buddyboss-pro' ); ?></button>

		<div id="bp-zoom-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" class="bzm-white-popup bp-zoom-group-show-instructions mfp-hide">
			<header class="bb-zm-model-header"><?php esc_html_e( 'Setup guide', 'buddyboss-pro' ); ?></header>

			<div class="bp-step-nav-main">

				<div class="bp-step-nav">
					<ul>
						<li class="selected"><a href="#step-1"><?php esc_html_e( 'Zoom Login', 'buddyboss-pro' ); ?></a>
						</li>
						<li><a href="#step-2"><?php esc_html_e( 'Create App', 'buddyboss-pro' ); ?></a></li>
						<li><a href="#step-3"><?php esc_html_e( 'App Information', 'buddyboss-pro' ); ?></a></li>
						<li><a href="#step-4"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></a></li>
						<li><a href="#step-5"><?php esc_html_e( 'Permissions', 'buddyboss-pro' ); ?></a></li>
						<li><a href="#step-6"><?php esc_html_e( 'Activation', 'buddyboss-pro' ); ?></a></li>
						<li><a href="#step-7"><?php esc_html_e( 'Credentials', 'buddyboss-pro' ); ?></a></li>
					</ul>
				</div> <!-- .bp-step-nav -->

				<div class="bp-step-blocks">

					<div class="bp-step-block selected" id="step-1">
						<div id="zoom-instruction-container">
							<p>
								<?php
								esc_html_e( 'To use Zoom, we will need you to create an "app" in your Zoom account and connect it to this group so we can sync meeting data with Zoom. This should only take a few minutes if you already have a Zoom account. Note that cloud recordings and alternate hosts will only work if you have a "Pro" or "Business" Zoom account.', 'buddyboss-pro' );
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-sign_in.png' ) ); ?>" />
							</div>
							<p>
								<?php
								printf(
								/* translators: 1: marketplace link, 2: Sign In, 3: Sign Up. */
									esc_html__( 'Start by going to the %1$s and clicking the %2$s link in the titlebar. You can sign in using your existing Zoom credentials. If you do not yet have a Zoom account, just click the %3$s link in the titlebar. Once you have successfully signed into Zoom App Marketplace you can move to the next step.', 'buddyboss-pro' ),
									'<a href="https://marketplace.zoom.us/" target="_blank">' . esc_html__( 'Zoom App Marketplace', 'buddyboss-pro' ) . '</a>',
									'"<strong>' . esc_html__( 'Sign In', 'buddyboss-pro' ) . '</strong>"',
									'"<strong>' . esc_html__( 'Sign Up', 'buddyboss-pro' ) . '</strong>"',
								);
								?>
							</p>
						</div>
					</div>

					<div class="bp-step-block" id="step-2">
						<div id="zoom-instruction-container">
							<?php /* translators: %s is build app link in zoom. */ ?>
							<p>
								<?php
								printf(
								/* translators: 1: Build app link in zoom, 2: Titles. */
									esc_html__( 'Once you are signed into Zoom App Marketplace, you need to %1$s. You can always find the Build App link by going to %2$s from the titlebar.', 'buddyboss-pro' ),
									'<a href="https://marketplace.zoom.us/develop/create" target="_blank">' . esc_html__( 'build an app', 'buddyboss-pro' ) . '</a>',
									'"<strong>' . esc_html__( 'Develop', 'buddyboss-pro' ) . '</strong>" &#8594; "<strong>' . esc_html__( 'Build App', 'buddyboss-pro' ) . '</strong>"'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-build_app.png' ) ); ?>" />
							</div>
							<p>
								<?php
								printf(
								/* translators: 1: App Type, 2: Action name. */
									esc_html__( 'On the next page, select the %1$s option as the app type and click the %2$s button.', 'buddyboss-pro' ),
									'<strong>' . esc_html__( 'Server-to-Server OAuth', 'buddyboss-pro' ) . '</strong>',
									'"<strong>' . esc_html__( 'Create', 'buddyboss-pro' ) . '</strong>"'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-app_type.png' ) ); ?>" />
							</div>
							<p>
								<?php
								printf(
								/* translators: 1: Create App, 2: Action name. */
									esc_html__( 'After clicking %1$s you will get a popup asking you to enter an App Name. Enter any name that will remind you the app is being used for this website. Then click the %2$s button.', 'buddyboss-pro' ),
									'"<strong>' . esc_html__( 'Create App', 'buddyboss-pro' ) . '</strong>"',
									'"<strong>' . esc_html__( 'Create', 'buddyboss-pro' ) . '</strong>"'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-app_name.png' ) ); ?>" />
							</div>
						</div>
					</div>

					<div class="bp-step-block" id="step-3">
						<div id="zoom-instruction-container">
							<p><?php esc_html_e( 'With the app created, the first step is to fill in your Basic and Developer Contact Information. This information is mandatory before you can activate your app.', 'buddyboss-pro' ); ?></p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-app_information.png' ) ); ?>" />
							</div>
						</div>
					</div>

					<div class="bp-step-block" id="step-4">
						<div id="zoom-instruction-container">
							<p><?php esc_html_e( 'We now need to configure the event notifications by Zoom on the Feature tab. This step is necessary to allow meeting updates from Zoom to automatically sync back into your group.', 'buddyboss-pro' ); ?></p>
							<p>
								<i><?php esc_html_e( 'Note that within the group on this site, you can also click the "Sync" button at any time to force a manual sync.', 'buddyboss-pro' ); ?></i>
							</p>
							<p>
								<?php
								printf(
								/* translators: 1: copy, 2: Secret Token. */
									esc_html__( 'Firstly you need to %1$s your %2$s and insert it below', 'buddyboss-pro' ),
									'<strong>' . esc_html__( 'copy', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Secret Token', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>

							<div class="bb-group-zoom-settings-container">
								<div class="bb-field-wrap">
									<label for="bb-group-zoom-s2s-secret-token-popup" class="group-setting-label"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-zoom-s2s-secret-token-popup" id="bb-group-zoom-s2s-secret-token-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $secret_token ); ?>">
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
									</div>
								</div>
							</div>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-token.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: Add Event Subscription. */
									esc_html__( 'Next we need to enable Event Subscriptions and select %s', 'buddyboss-pro' ),
									'<strong>+' . esc_html__( 'Add Event Subscription', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-event_subscription.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: Event notification endpoint URL. */
									esc_html__( 'For the Subscription name, you can add any name. You should then use the Notification URL below and copy it into the %s', 'buddyboss-pro' ),
									'<strong>' . esc_html__( 'Event notification endpoint URL', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="bb-group-zoom-settings-container">
								<div class="bb-field-wrap">
									<label for="bb-group-zoom-s2s-notification-url-popup" class="group-setting-label"><?php esc_html_e( 'Notification URL', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="copy-toggle">
											<input type="text" name="bb-group-zoom-s2s-notification-url-popup" id="bb-group-zoom-s2s-notification-url-popup" class="zoom-group-instructions-cloned-input is-disabled" value="<?php echo esc_url( trailingslashit( bp_get_root_domain() ) . '?zoom_webhook=1&group_id=' . $group_id ); ?>" />
											<span role="button" class="bb-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss-pro' ); ?>">
												<i class="bb-icon-f bb-icon-copy"></i>
											</span>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Use as the Event notification endpoint URL when configuring Event Subscriptions in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>
							</div>
							<p>
								<?php
								printf(
								/* translators: Validate. */
									esc_html__( 'Click %s.', 'buddyboss-pro' ),
									'<strong>' . esc_html__( 'Validate', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-event_notification.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: Add Event Subscription. */
									esc_html__( 'After that, you need to add Events for the app to subscribe to. Click %s and now add the follower permissions under each section', 'buddyboss-pro' ),
									'<strong>+' . esc_html__( 'Add Events', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Meeting', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li><?php esc_html_e( 'Start Meeting', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'End Meeting', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'Meeting has been updated', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'Meeting has been deleted', 'buddyboss-pro' ); ?></li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_meetings.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Webinar', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li><?php esc_html_e( 'Start Webinar', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'End Webinar', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'Webinar has been updated', 'buddyboss-pro' ); ?></li>
								<li><?php esc_html_e( 'Webinar has been deleted', 'buddyboss-pro' ); ?></li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_webinars.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Recording', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li><?php esc_html_e( 'All Recordings have completed', 'buddyboss-pro' ); ?></li>
							</ul>
							<p>
								<?php
								printf(
								/* translators: 1: 9 scopes added, 2: Done. */
									esc_html__( 'At this point, you should see that you have %1$s.Once all these have been enabled, click %2$s.', 'buddyboss-pro' ),
									'<strong>' . esc_html__( '9 scopes added', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Done', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_recordings.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: 1: Save, 2: Continue. */
									esc_html__( 'Click %1$s and then %2$s to the next step.', 'buddyboss-pro' ),
									'<strong>' . esc_html__( 'Save', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Continue', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_save.png' ) ); ?>" />
							</div>
						</div>
					</div>

					<div class="bp-step-block" id="step-5">
						<div id="zoom-instruction-container">
							<p><?php esc_html_e( 'Now we add the appropriate account permissions from the Scopes tab. Click +Add Scopes and add the following permissions under each scope type', 'buddyboss-pro' ); ?></p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Meeting', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li>
									<?php esc_html_e( 'View all user meetings', 'buddyboss-pro' ); ?>
								</li>
								<ul>
									<li><?php esc_html_e( 'View a meeting - meeting:read:meeting:admin', 'buddyboss-pro' ); ?></li>
									<li><?php esc_html_e( 'View a past meeting\'s instances - meeting:read:list_past_instances:admin', 'buddyboss-pro' ); ?></li>
									<li><?php esc_html_e( 'View a meeting\'s invitation - meeting:read:invitation:admin', 'buddyboss-pro' ); ?></li>
								</ul>
								<li>
									<?php esc_html_e( 'View and manage all user meetings', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'Delete a meeting - meeting:delete:meeting:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Update a meeting - meeting:update:meeting:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Create a meeting for a user - meeting:write:meeting:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_meetings.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_meetings_2.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_meetings_3.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_meetings_4.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_meetings_5.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Webinar', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li>
									<?php esc_html_e( 'View all user Webinars', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'View a past webinar\'s instances - webinar:read:list_past_instances:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View a webinar - webinar:read:webinar:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
								<li>
									<?php esc_html_e( 'View and manage all user Webinars', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'Delete a webinar - webinar:delete:webinar:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Update a webinar - webinar:update:webinar:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Create a webinar for a user - webinar:write:webinar:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_webinars.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_webinars_2.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_webinars_3.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_webinars_4.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_webinars_5.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Recording', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li>
									<?php esc_html_e( 'View all user recordings', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'list account recording - cloud_recording:read:list_account_recordings:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Returns all of a meeting\'s recordings - cloud_recording:read:list_recording_files:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Lists all cloud recordings for a user - cloud_recording:read:list_user_recordings:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_recordings.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'User', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li>
									<?php esc_html_e( 'View all user information', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'View users - user:read:list_users:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View a user - user:read:user:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View a user\'s settings - user:read:settings:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
								<li>
									<?php esc_html_e( 'View and manage sub account\'s user information', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'View a user\'s settings - user:read:settings:master', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
								<li>
									<?php esc_html_e( 'View users information and manage users', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'Create a user - user:write:user:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Delete a user - user:delete:user:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Update a user - user:update:user:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_users.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_users_2.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_users_3.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_users_4.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_users_5.png' ) ); ?>" />
							</div>

							<h3><?php esc_html_e( 'Report', 'buddyboss-pro' ); ?></h3>
							<ul>
								<li>
									<?php esc_html_e( 'View report data', 'buddyboss-pro' ); ?>
									<ul>
										<li><?php esc_html_e( 'View meeting detail reports - report:read:meeting:admin', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View webinar detail reports - report:read:webinar:admin', 'buddyboss-pro' ); ?></li>
									</ul>
								</li>
							</ul>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_reports.png' ) ); ?>" />
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-scope_reports_2.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: 1: 8 scopes added, 2: Done, 3: Continue. */
									esc_html__( 'At this point, you should see that you have %1$s. Once all these have been enabled, click %2$s and then %3$s to the last step.', 'buddyboss-pro' ),
									'<strong>' . esc_html__( '23 scopes added', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Done', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Continue', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
						</div>
					</div>

					<div class="bp-step-block" id="step-6">
						<div id="zoom-instruction-container">
							<p>
								<?php
								printf(
								/* translators: Activate your app. */
									esc_html__( 'With all the previous steps completed, your app should now be ready for activation. Click %s. we can now activate your app.', 'buddyboss-pro' ),
									'<strong>"' . esc_html__( 'Activate your app', 'buddyboss-pro' ) . '"</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-activate.png' ) ); ?>" />
							</div>

							<p>
								<?php
								printf(
								/* translators: Your app is activated on the account. */
									esc_html__( 'You should see a message that says %s. At this point we are now ready to head to the final task of the setup.', 'buddyboss-pro' ),
									'<strong>"' . esc_html__( 'Your app is activated on the account', 'buddyboss-pro' ) . '"</strong>'
								);
								?>
							</p>
							<div class="wizard-img">
								<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-activated.png' ) ); ?>" />
							</div>
						</div>
					</div>

					<div class="bp-step-block" id="step-7">
						<div id="zoom-instruction-container">
							<p>
								<?php
								printf(
								/* translators: 1 - App Credentials, 2 - Account ID, 3 - Client ID, 4 - Client Secret. */
									esc_html__( 'Once you get to the %1$s page, copy the %2$s, %3$s and %4$s and paste them into the fields in the form below.', 'buddyboss-pro' ),
									'"<strong>' . esc_html__( 'App Credentials', 'buddyboss-pro' ) . '</strong>"',
									'<strong>' . esc_html__( 'Account ID', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Client ID', 'buddyboss-pro' ) . '</strong>',
									'<strong>' . esc_html__( 'Client Secret', 'buddyboss-pro' ) . '</strong>'
								);
								?>
							</p>
							<p><?php esc_html_e( 'If multiple zoom users are available, you will then need to select the email address of the associated account for this group.', 'buddyboss-pro' ); ?></p>

							<div class="bb-group-zoom-settings-container bb-group-zoom-wizard-credentials">
								<div class="bb-group-zoom-s2s-notice bb-group-zoom-s2s-notice-popup">
								</div>
								<div class="bb-field-wrap">
									<label for="bb-group-zoom-s2s-account-id-popup" class="group-setting-label">
										<?php esc_html_e( 'Account ID', 'buddyboss-pro' ); ?>
									</label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-zoom-s2s-account-id-popup" id="bb-group-zoom-s2s-account-id-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $account_id ); ?>" />
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Account ID from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-zoom-s2s-client-id-popup" class="group-setting-label">
										<?php esc_html_e( 'Client ID', 'buddyboss-pro' ); ?>
									</label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-zoom-s2s-client-id-popup" id="bb-group-zoom-s2s-client-id-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $client_id ); ?>" />
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client ID from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-zoom-s2s-client-secret-popup" class="group-setting-label">
										<?php esc_html_e( 'Client Secret', 'buddyboss-pro' ); ?>
									</label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-zoom-s2s-client-secret-popup" id="bb-group-zoom-s2s-client-secret-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $client_secret ); ?>" />
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client Secret from the App Credentials section in your Zoom app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap bb-zoom_account-email">
									<label for="bb-group-zoom-s2s-api-email-popup" class="group-setting-label">
										<?php esc_html_e( 'Account Email', 'buddyboss-pro' ); ?>
										<span class="bb-icon-f bb-icon-spinner animate-spin"></span>
									</label>
									<div class="bp-input-wrap">
										<?php
										$is_disabled_email = 'is-disabled';
										if ( 1 < count( $account_emails ) ) {
											$is_disabled_email = '';
										}
										?>
										<select name="bb-group-zoom-s2s-api-email-popup" id="bb-group-zoom-s2s-api-email-popup" class="zoom-group-instructions-cloned-input <?php echo esc_attr( $is_disabled_email ); ?>">
											<?php
											if ( ! empty( $account_emails ) ) {
												foreach ( $account_emails as $email_key => $email_label ) {
													echo '<option value="' . esc_attr( $email_key ) . '" ' . selected( $s2s_api_email, $email_key, false ) . '>' . esc_attr( $email_label ) . '</option>';
												}
											} else {
												echo '<option value="">- ' . esc_html__( 'Select a Zoom account', 'buddyboss-pro' ) . ' -</option>';
											}
											?>
										</select>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Select the Zoom account to sync Zoom meetings and webinars from.', 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

							</div><!-- .bb-group-zoom-settings-container -->

							<p>
								<?php
								printf(
								/* translators: Save. */
									esc_html__( 'Make sure to click the %s button on this tab to save the data you entered. You have now successfully connected Zoom to your group.', 'buddyboss-pro' ),
									'"<strong>' . esc_html__( 'Save', 'buddyboss-pro' ) . '</strong>"'
								);
								?>
							</p>
						</div>
					</div>

				</div> <!-- .bp-step-blocks -->

				<div class="bp-step-actions">
					<span class="bp-step-prev button small" style="display: none;"><i class="bb-icon-l bb-icon-angle-left"></i>&nbsp;<?php esc_html_e( 'Previous', 'buddyboss-pro' ); ?></span>
					<span class="bp-step-next button small"><?php esc_html_e( 'Next', 'buddyboss-pro' ); ?>&nbsp;<i class="bb-icon-l bb-icon-angle-right"></i></span>

					<span class="save-settings button small"><?php esc_html_e( 'Save', 'buddyboss-pro' ); ?></span>

				</div> <!-- .bp-step-actions -->

			</div> <!-- .bp-step-nav-main -->

		</div>

	</div>

	<?php wp_nonce_field( 'groups_edit_save_zoom' ); ?>
</div>
