<?php

namespace WPMailSMTP\Pro\Alerts\Providers\Push;

use WPMailSMTP\Pro\Alerts\AbstractOptions;

/**
 * Class Options.
 *
 * @since 4.4.0
 */
class Options extends AbstractOptions {

	/**
	 * Provider slug.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	const SLUG = 'push_notifications';

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 */
	public function __construct() {

		$description = wp_kses(
			sprintf(
			/* translators: %s - Documentation link. */
				__( 'To receive push notifications on this device, you\'ll need to allow our plugin to send notifications via this browser. <a href="%s" target="_blank" rel="noopener noreferrer">Read our documentation on setting up Push Notification alerts</a>.', 'wp-mail-smtp-pro' ),
				esc_url(
					wp_mail_smtp()->get_utm_url(
						'https://wpmailsmtp.com/docs/setting-up-email-alerts/#push-notifications',
						[
							'medium'  => 'Alerts Settings',
							'content' => 'Push notifications Documentation',
						]
					)
				)
			),
			[
				'a' => [
					'href'   => [],
					'rel'    => [],
					'target' => [],
				],
			]
		);

		if ( ! wp_mail_smtp()->pro->get_license()->is_valid() ) {
			ob_start();
			?>
			<div class="notice notice-error inline">
				<p>
					<?php
					echo wp_kses(
						sprintf(
						/* translators: %s - Plugin general settings page link. */
							__( 'This integration will not work without a valid WP Mail SMTP license key. <a href="%s">Please verify your license key</a>.', 'wp-mail-smtp-pro' ),
							esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url() )
						),
						[
							'a' => [
								'href' => [],
							],
						]
					);
					?>
				</p>
			</div>
			<?php
			$description .= ob_get_clean();
		}

		parent::__construct(
			[
				'slug'                => self::SLUG,
				'title'               => esc_html__( 'Push Notification', 'wp-mail-smtp-pro' ),
				'description'         => $description,
				'add_connection_text' => esc_html__( 'Enable Push Notifications on This Device', 'wp-mail-smtp-pro' ),
			]
		);
	}

	/**
	 * Output the provider options.
	 *
	 * @since 4.4.0
	 */
	public function display_options() {

		$slug       = $this->get_slug();
		$public_key = $this->options->get( $this->get_group(), 'public_key' );
		?>
		<input type="hidden"
					 name="wp-mail-smtp[alert_<?php echo esc_attr( $slug ); ?>][public_key]"
					 id="wp-mail-smtp-push-notifications-public-key"
					 value="<?php echo esc_attr( $public_key ); ?>">

		<div class="wp-mail-smtp-setting-row" id="wp-mail-smtp-setting-row-alert-push_notifications-subscriptions">
			<div class="wp-mail-smtp-setting-row">
				<div class="wp-mail-smtp-setting-field">
					<?php echo wp_mail_smtp()->prepare_loader(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>

		<div class="wp-mail-smtp-setting-row">
			<div class="wp-mail-smtp-setting-field">
				<div class="notice inline notice-inline wp-mail-smtp-notice notice-error wp-mail-smtp-push-notifications-notice"
						 id="wp-mail-smtp-push-notifications-subscription-notice"
						 style="display: none;">
					<p></p>
				</div>

				<button class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish"
								id="wp-mail-smtp-setting-alert-push_notifications-subscribe"
								data-provider="<?php echo esc_attr( $this->get_slug() ); ?>"
								disabled>
					<span><?php echo esc_html( $this->get_add_connection_text() ); ?></span>
					<?php echo wp_mail_smtp()->prepare_loader( 'white', 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Get single connection options.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $connection Connection settings.
	 * @param string $i          Connection index.
	 *
	 * @return string
	 */
	public function get_connection_options( $connection, $i ) {

		$slug  = $this->get_slug();
		$id    = isset( $connection['id'] ) ? $connection['id'] : '';
		$label = isset( $connection['label'] ) ? $connection['label'] : '';

		ob_start();
		?>
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text" data-subscription-id="<?php echo esc_attr( $id ); ?>">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-user-agent-<?php echo esc_attr( $i ); ?>" data-current-label="<?php echo esc_attr__( 'current', 'wp-mail-smtp-pro' ); ?>">
					<?php esc_html_e( 'Connection Name', 'wp-mail-smtp-pro' ); ?>
				</label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php
				printf(
					'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][label]" type="text" value="%3$s" id="wp-mail-smtp-setting-alert-%1$s-user-agent-%2$s" spellcheck="false"/>',
					esc_attr( $slug ),
					esc_attr( $i ),
					esc_attr( $label )
				);
				?>
				<?php
				printf(
					'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][id]" type="hidden" value="%3$s"/>',
					esc_attr( $slug ),
					esc_attr( $i ),
					esc_attr( $id )
				);
				?>
				<span class="js-wp-mail-smtp-setting-alert-push_notifications-remove-subscription">
					<i class="dashicons dashicons-trash"></i>
					<?php echo wp_mail_smtp()->prepare_loader( '', 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
