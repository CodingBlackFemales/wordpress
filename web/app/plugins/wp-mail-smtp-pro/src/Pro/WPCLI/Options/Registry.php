<?php

namespace WPMailSMTP\Pro\WPCLI\Options;

/**
 * Registers Pro-only WP-CLI args by hooking the Lite filter.
 *
 * @since 4.9.0
 */
class Registry {

	/**
	 * Hook the filter.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_filter( 'wp_mail_smtp_wpcli_options_registry_get_args', [ $this, 'register' ] );
	}

	/**
	 * Append Pro args to the base array.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Base args from Lite.
	 *
	 * @return array
	 */
	public function register( array $args ) {

		return array_merge(
			$args,
			$this->logs_args(),
			$this->optimized_sending_args(),
			$this->rate_limit_args(),
			$this->controls_args(),
			$this->alerts_args()
		);
	}

	/**
	 * Email Logs settings.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function logs_args() {

		return [
			[
				'flag'        => 'logs.enabled',
				'type'        => 'bool',
				'description' => __( 'Enable email logging.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'logs.log_email_content',
				'type'        => 'bool',
				'description' => __( 'Store full email content (subject + body) in each log entry. Required to resend emails.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'logs.save_attachments',
				'type'        => 'bool',
				'description' => __( 'Save sent attachments to the uploads folder alongside each log entry.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'logs.open_email_tracking',
				'type'        => 'bool',
				'description' => __( 'Track when recipients open logged emails.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'logs.click_link_tracking',
				'type'        => 'bool',
				'description' => __( 'Track when recipients click links in logged emails.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'logs.retention_seconds',
				'type'         => 'int',
				'storage_path' => 'logs.log_retention_period',
				'description'  => __( 'Email log retention in seconds (e.g. 86400 = 1 day, 604800 = 1 week; empty/0 = forever).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Optimized Email Sending toggle.
	 *
	 * Stored under the shared `general` group; the toggle is Pro-gated at runtime.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function optimized_sending_args() {

		return [
			[
				'flag'         => 'general.optimize_sending',
				'type'         => 'bool',
				'storage_path' => 'general.optimize_email_sending_enabled',
				'description'  => __( 'Send emails via a background queue instead of inline with the page request.', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Rate Limiting settings.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function rate_limit_args() {

		return [
			[
				'flag'        => 'rate_limit.enabled',
				'type'        => 'bool',
				'description' => __( 'Enable per-interval rate limiting on outgoing email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'rate_limit.minute',
				'type'        => 'int',
				'description' => __( 'Maximum emails per minute (empty/0 = unlimited).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'rate_limit.hour',
				'type'        => 'int',
				'description' => __( 'Maximum emails per hour (empty/0 = unlimited).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'rate_limit.day',
				'type'        => 'int',
				'description' => __( 'Maximum emails per day (empty/0 = unlimited).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'rate_limit.week',
				'type'        => 'int',
				'description' => __( 'Maximum emails per week (empty/0 = unlimited).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'        => 'rate_limit.month',
				'type'        => 'int',
				'description' => __( 'Maximum emails per month (empty/0 = unlimited).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Email Controls — toggles that suppress specific WordPress core notifications.
	 *
	 * Operator-facing flag `control.block_*` maps to the `control.dis_*` storage key.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function controls_args() {

		return [
			// Comments.
			[
				'flag'         => 'control.block_comment_moderation_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_comments_awaiting_moderation',
				'description'  => __( 'Suppress the WP "comment awaiting moderation" email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_comment_published_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_comments_published',
				'description'  => __( 'Suppress the WP "comment published" email to the post author.', 'wp-mail-smtp-pro' ),
			],

			// Change of admin email.
			[
				'flag'         => 'control.block_admin_email_change_attempt_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_admin_email_attempt',
				'description'  => __( 'Suppress the "site admin email change attempted" email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_admin_email_changed_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_admin_email_changed',
				'description'  => __( 'Suppress the "site admin email changed" notice to the old address.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_network_admin_email_change_attempt_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_admin_email_network_attempt',
				'description'  => __( 'Suppress the "network admin email change attempted" email (multisite).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_network_admin_email_changed_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_admin_email_network_changed',
				'description'  => __( 'Suppress the "network admin email changed" notice (multisite).', 'wp-mail-smtp-pro' ),
			],

			// Change of user email or password.
			[
				'flag'         => 'control.block_password_reset_request_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_user_details_password_reset_request',
				'description'  => __( 'Suppress the "password reset requested" email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_password_reset_admin_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_user_details_password_reset',
				'description'  => __( 'Suppress the "password was reset" admin notification.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_password_changed_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_user_details_password_changed',
				'description'  => __( 'Suppress the "password changed" email to the user.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_user_email_change_attempt_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_user_details_email_change_attempt',
				'description'  => __( 'Suppress the "user requested email change" confirmation email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_user_email_changed_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_user_details_email_changed',
				'description'  => __( 'Suppress the "user email changed" notification.', 'wp-mail-smtp-pro' ),
			],

			// Personal data requests.
			[
				'flag'         => 'control.block_personal_data_confirmed_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_personal_data_user_confirmed',
				'description'  => __( 'Suppress the "user confirmed data export/erasure request" admin email.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_personal_data_erased_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_personal_data_erased_data',
				'description'  => __( 'Suppress the "admin erased your personal data" email to the requester.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_personal_data_export_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_personal_data_sent_export_link',
				'description'  => __( 'Suppress the "your data export is ready" email to the requester (blocks export delivery).', 'wp-mail-smtp-pro' ),
			],

			// Automatic updates.
			[
				'flag'         => 'control.block_plugin_auto_update_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_auto_updates_plugin_status',
				'description'  => __( 'Suppress background auto-update result emails for plugins.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_theme_auto_update_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_auto_updates_theme_status',
				'description'  => __( 'Suppress background auto-update result emails for themes.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_core_auto_update_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_auto_updates_status',
				'description'  => __( 'Suppress background auto-update result emails for WP core.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_auto_update_debug_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_auto_updates_full_log',
				'description'  => __( 'Suppress the full auto-update log email (development builds only).', 'wp-mail-smtp-pro' ),
			],

			// New user.
			[
				'flag'         => 'control.block_new_user_admin_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_user_created_to_admin',
				'description'  => __( 'Suppress the "new user registered" email to the site admin.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_new_user_welcome_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_user_created_to_user',
				'description'  => __( 'Suppress the "your account was created" email to the new user.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_network_user_site_invite_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_user_invited_to_site_network',
				'description'  => __( 'Suppress the "you were invited to a site" email (multisite).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_network_new_user_admin_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_user_created_network',
				'description'  => __( 'Suppress the "new user account created" email to the network admin (multisite).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_network_user_activated_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_user_added_activated_network',
				'description'  => __( 'Suppress the "your account was added/activated" email to the user (multisite).', 'wp-mail-smtp-pro' ),
			],

			// New site (multisite).
			[
				'flag'         => 'control.block_new_site_registered_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_site_user_registered_site_network',
				'description'  => __( 'Suppress the "user registered for a new site" email to the site admin (multisite).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_new_site_network_admin_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_site_user_added_activated_site_in_network_to_admin',
				'description'  => __( 'Suppress the "site activated/added" email to the network admin (multisite).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'control.block_new_site_site_admin_email',
				'type'         => 'bool',
				'storage_path' => 'control.dis_new_site_user_added_activated_site_in_network_to_site',
				'description'  => __( 'Suppress the "site activated/added" email to the site admin (multisite).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Alert provider settings (primary connection only).
	 *
	 * Friendly channel flags map via `storage_path` to the WPMS layout
	 * `alert_<channel>.connections[0].<field>`.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alerts_args() {

		return array_merge(
			$this->alert_events_args(),
			$this->alert_email_args(),
			$this->alert_slack_args(),
			$this->alert_discord_args(),
			$this->alert_teams_args(),
			$this->alert_webhook_args(),
			$this->alert_twilio_args(),
			$this->alert_whatsapp_args(),
			$this->alert_push_args()
		);
	}

	/**
	 * Alert trigger events (which conditions raise an alert).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_events_args() {

		return [
			[
				'flag'         => 'alert.events.email_hard_bounced',
				'type'         => 'bool',
				'storage_path' => 'alert_events.email_hard_bounced',
				'description'  => __( 'Raise alerts when a logged email hard-bounces (requires email logging and a mailer that reports bounces).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Email alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_email_args() {

		$req = [ 'alert.email.enabled' => true ];

		return [
			[
				'flag'         => 'alert.email.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_email.enabled',
				'description'  => __( 'Enable email alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.email.send_to',
				'type'         => 'email',
				'storage_path' => 'alert_email.connections.0.send_to',
				'required_if'  => $req,
				'description'  => __( 'Recipient address for failure alerts (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Slack alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_slack_args() {

		$req = [ 'alert.slack.enabled' => true ];

		return [
			[
				'flag'         => 'alert.slack.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_slack_webhook.enabled',
				'description'  => __( 'Enable Slack alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.slack.webhook_url',
				'type'         => 'string',
				'storage_path' => 'alert_slack_webhook.connections.0.webhook_url',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Slack incoming-webhook URL (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Discord alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_discord_args() {

		$req = [ 'alert.discord.enabled' => true ];

		return [
			[
				'flag'         => 'alert.discord.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_discord_webhook.enabled',
				'description'  => __( 'Enable Discord alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.discord.webhook_url',
				'type'         => 'string',
				'storage_path' => 'alert_discord_webhook.connections.0.webhook_url',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Discord incoming-webhook URL (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Microsoft Teams alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_teams_args() {

		$req = [ 'alert.teams.enabled' => true ];

		return [
			[
				'flag'         => 'alert.teams.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_teams_webhook.enabled',
				'description'  => __( 'Enable Microsoft Teams alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.teams.webhook_url',
				'type'         => 'string',
				'storage_path' => 'alert_teams_webhook.connections.0.webhook_url',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Microsoft Teams incoming-webhook URL (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Custom webhook alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_webhook_args() {

		$req = [ 'alert.webhook.enabled' => true ];

		return [
			[
				'flag'         => 'alert.webhook.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_custom_webhook.enabled',
				'description'  => __( 'Enable custom webhook alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.webhook.webhook_url',
				'type'         => 'string',
				'storage_path' => 'alert_custom_webhook.connections.0.webhook_url',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Custom webhook URL (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Twilio SMS alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_twilio_args() {

		$req = [ 'alert.twilio.enabled' => true ];

		return [
			[
				'flag'         => 'alert.twilio.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_twilio_sms.enabled',
				'description'  => __( 'Enable Twilio SMS alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.twilio.account_sid',
				'type'         => 'string',
				'storage_path' => 'alert_twilio_sms.connections.0.account_sid',
				'required_if'  => $req,
				'description'  => __( 'Twilio Account SID (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.twilio.auth_token',
				'type'         => 'string',
				'storage_path' => 'alert_twilio_sms.connections.0.auth_token',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Twilio Auth Token (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.twilio.from_phone_number',
				'type'         => 'string',
				'storage_path' => 'alert_twilio_sms.connections.0.from_phone_number',
				'required_if'  => $req,
				'description'  => __( 'Twilio sending phone number in E.164 format (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.twilio.to_phone_number',
				'type'         => 'string',
				'storage_path' => 'alert_twilio_sms.connections.0.to_phone_number',
				'required_if'  => $req,
				'description'  => __( 'Recipient phone number in E.164 format (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * WhatsApp alert channel.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_whatsapp_args() {

		$req = [ 'alert.whatsapp.enabled' => true ];

		return [
			[
				'flag'         => 'alert.whatsapp.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_whatsapp.enabled',
				'description'  => __( 'Enable WhatsApp alerts.', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.whatsapp.access_token',
				'type'         => 'string',
				'storage_path' => 'alert_whatsapp.connections.0.access_token',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'WhatsApp Cloud API permanent access token (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.whatsapp.business_account_id',
				'type'         => 'string',
				'storage_path' => 'alert_whatsapp.connections.0.whatsapp_business_id',
				'required_if'  => $req,
				'description'  => __( 'WhatsApp Business Account ID (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.whatsapp.phone_number_id',
				'type'         => 'string',
				'storage_path' => 'alert_whatsapp.connections.0.phone_number_id',
				'required_if'  => $req,
				'description'  => __( 'WhatsApp business phone-number ID (primary connection).', 'wp-mail-smtp-pro' ),
			],
			[
				'flag'         => 'alert.whatsapp.to_phone_number',
				'type'         => 'string',
				'storage_path' => 'alert_whatsapp.connections.0.to_phone_number',
				'required_if'  => $req,
				'description'  => __( 'Recipient phone number, digits only, no spaces or symbols (primary connection).', 'wp-mail-smtp-pro' ),
			],
		];
	}

	/**
	 * Push-notification alert channel.
	 *
	 * Only the enable toggle is exposed; subscriptions are per-device (browser-only).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function alert_push_args() {

		return [
			[
				'flag'         => 'alert.push.enabled',
				'type'         => 'bool',
				'storage_path' => 'alert_push_notifications.enabled',
				'description'  => __( 'Enable browser push-notification alerts (subscriptions are added per-device from the admin UI).', 'wp-mail-smtp-pro' ),
			],
		];
	}
}
