<?php

namespace WPMailSMTP\Pro\Admin\Pages;

use WPMailSMTP\Admin\Pages\TestTab as TestTabLite;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Alerts\Loader as AlertsLoader;

/**
 * Pro variant of the Email Test page.
 *
 * @since 4.9.0
 */
class TestTab extends TestTabLite {

	/**
	 * Render the Pro variant of the success banner.
	 *
	 * @since 4.9.0
	 *
	 * @inheritdoc
	 */
	protected function display_success_banner() {

		if ( ! $this->is_setup_done() ) {
			$this->display_success_banner_setup_pending();

			return;
		}

		$this->display_success_banner_setup_done();
	}

	/**
	 * Render the Pro success banner when one or more of {Email Log,
	 * Backup Connection, Alerts} are not yet configured. Each remaining
	 * feature is presented as a setup tile; configured ones render in a
	 * "Complete!" state with strikethrough text.
	 *
	 * @since 4.9.0
	 */
	private function display_success_banner_setup_pending() {

		$assets_url = wp_mail_smtp()->pro->assets_url;

		$options = Options::init();

		// When SendLayer is the primary mailer, recommending SendLayer as the
		// backup undermines the redundancy goal — swap to a generic
		// backup-mailer tile.
		$is_sendlayer_primary = $options->get( 'mail', 'mailer' ) === 'sendlayer';

		// Anchor matches the Backup Connection section id in the Pro Backup
		// Connection settings (Settings → General).
		$backup_settings_url = wp_mail_smtp()->get_admin()->get_admin_page_url() . '#wp-mail-smtp-setting-row-backup_connection';

		$backup_tile = $this->build_backup_tile( $is_sendlayer_primary, $backup_settings_url, $assets_url );

		$tiles = [
			$backup_tile,
			[
				'icon'     => $assets_url . '/images/test-success/icon-alerts.svg',
				'title'    => esc_html__( 'Get alerted for failed emails with Alerts', 'wp-mail-smtp-pro' ),
				'body'     => esc_html__( 'Be the first to know when emails fail, with details to fix fast', 'wp-mail-smtp-pro' ),
				'url'      => add_query_arg( 'tab', 'alerts', wp_mail_smtp()->get_admin()->get_admin_page_url() ),
				'complete' => $this->is_any_alert_provider_enabled(),
			],
			[
				'icon'     => $assets_url . '/images/test-success/icon-email-log.svg',
				'title'    => esc_html__( 'Never miss an email with Email Logging', 'wp-mail-smtp-pro' ),
				'body'     => esc_html__( 'Keep a record of every email sent from your site for easy tracking', 'wp-mail-smtp-pro' ),
				'url'      => add_query_arg( 'tab', 'logs', wp_mail_smtp()->get_admin()->get_admin_page_url() ),
				'complete' => (bool) $options->get( 'logs', 'enabled' ),
			],
		];
		?>
		<div class="wpms-test-email-success-banner wp-mail-smtp-test-success-banner wp-mail-smtp-test-success-banner--pro-setup-pending">
			<?php $this->display_success_banner_dismiss(); ?>

			<div class="wpms:flex wpms:flex-col wpms:gap-md wpms:p-md">
				<div class="wpms:flex wpms:flex-col wpms:gap-sm">
					<div class="wpms-test-email-success-banner__heading">
						<span aria-hidden="true" class="wpms:icon-[fa6-solid--circle-check] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
						<h2>
							<?php esc_html_e( 'Test email sent successfully! Check your inbox to confirm delivery.', 'wp-mail-smtp-pro' ); ?>
						</h2>
					</div>
					<p class="wpms:m-[0]! wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
						<?php esc_html_e( 'Setup these features already available in your WP Mail SMTP Pro plugin to get full value!', 'wp-mail-smtp-pro' ); ?>
					</p>
				</div>

				<div class="wpms:flex wpms:flex-wrap wpms:items-stretch wpms:gap-md">
					<?php foreach ( $tiles as $tile ) : ?>
						<div class="wpms-test-email-success-banner__card<?php echo $tile['complete'] ? ' wpms:opacity-70' : ''; ?>">
							<div class="wpms:flex wpms:items-start wpms:gap-[12px] wpms:w-full">
								<div class="wpms:flex wpms:items-center wpms:justify-center wpms:w-[60px] wpms:h-[60px] wpms:bg-[#f6f6f6] wpms:border wpms:border-surface-divider wpms:rounded-[4px] wpms:shrink-0 wpms:overflow-hidden">
									<img src="<?php echo esc_url( $tile['icon'] ); ?>" alt="" class="wpms:h-[32px] wpms:w-auto wpms:max-w-[40px]">
								</div>
								<div class="wpms:flex wpms:flex-col wpms:flex-1 wpms:min-w-[0]">
									<h3 class="wpms:m-[0]! wpms:p-[0]! wpms:text-sm! wpms:leading-5! wpms:font-medium! wpms:text-primary<?php echo $tile['complete'] ? ' wpms:line-through' : ''; ?>">
										<?php echo esc_html( $tile['title'] ); ?>
									</h3>
									<p class="wpms:m-[0]! wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
										<?php echo esc_html( $tile['body'] ); ?>
									</p>
								</div>
							</div>
							<?php if ( $tile['complete'] ) : ?>
								<button type="button" disabled class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-light-grey wpms:inline-flex! wpms:items-center wpms:gap-[4px]! wpms:self-start wpms:mt-auto! wpms:opacity-50 wpms:cursor-not-allowed">
									<span><?php esc_html_e( 'Complete!', 'wp-mail-smtp-pro' ); ?></span>
									<span aria-hidden="true" class="wpms:icon-[fa6-solid--circle-check] wpms:text-success wpms:w-[12px] wpms:h-[12px]"></span>
								</button>
							<?php elseif ( ! empty( $tile['quick_connect'] ) ) : ?>
								<button type="button"
									class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-light-grey js-wp-mail-smtp-sendlayer-quick-connect-btn wpms:self-start wpms:mt-auto! wpms:focus:outline-none! wpms:focus:shadow-none!"
									data-mode="backup_mailer"
									data-utm-content="Test Email Success - Backup Mailer Tile">
									<?php esc_html_e( 'Setup Now', 'wp-mail-smtp-pro' ); ?>
								</button>
							<?php else : ?>
								<a href="<?php echo esc_url( $tile['url'] ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-light-grey wpms:self-start wpms:mt-auto!">
									<?php esc_html_e( 'Setup Now', 'wp-mail-smtp-pro' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php $this->display_success_pro_tip_strip(); ?>
		</div>
		<?php
	}

	/**
	 * Render the Pro success banner when all of Email Log, Backup
	 * Connection, and Alerts are configured. Splits internally by
	 * cross-sell pool size: 1-3 candidates render the populated
	 * cross-sell tiles, an empty pool collapses to a slim 1-line strip.
	 *
	 * Covers two layouts that share identical markup and differ only in
	 * pool size: the "Pro Setup Done" layout with 3 tiles, and the "Pro
	 * One Installed" layout with 2 tiles (one catalog plugin already on
	 * the site → filtered out by get_cross_sell_recommendations()).
	 *
	 * @since 4.9.0
	 */
	private function display_success_banner_setup_done() {

		$recommendations = $this->get_cross_sell_recommendations();

		if ( empty( $recommendations ) ) {
			$this->display_success_banner_slim();

			return;
		}
		?>
		<div class="wpms-test-email-success-banner wp-mail-smtp-test-success-banner wp-mail-smtp-test-success-banner--pro-setup-done">
			<?php $this->display_success_banner_dismiss(); ?>

			<div class="wpms:flex wpms:flex-col wpms:gap-md wpms:p-md">
				<div class="wpms:flex wpms:flex-col wpms:gap-sm">
					<div class="wpms-test-email-success-banner__heading">
						<span aria-hidden="true" class="wpms:icon-[fa6-solid--circle-check] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
						<h2>
							<?php esc_html_e( 'Test email sent successfully! Check your inbox to confirm delivery.', 'wp-mail-smtp-pro' ); ?>
						</h2>
					</div>
					<p class="wpms:m-[0]! wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
						<?php esc_html_e( 'Add more power to your site with these tested and trusted WordPress plugins!', 'wp-mail-smtp-pro' ); ?>
					</p>
				</div>

				<div class="wpms:flex wpms:flex-wrap wpms:items-stretch wpms:gap-md">
					<?php foreach ( $recommendations as $product ) : ?>
						<div class="wpms-test-email-success-banner__card">
							<div class="wpms:flex wpms:items-start wpms:gap-[12px] wpms:w-full">
								<?php if ( ! empty( $product['framed_icon'] ) ) : ?>
									<div class="wpms:flex wpms:items-center wpms:justify-center wpms:w-[60px] wpms:h-[60px] wpms:bg-surface-background-light wpms:border wpms:border-surface-divider wpms:rounded-[4px] wpms:shrink-0 wpms:overflow-hidden">
										<img src="<?php echo esc_url( $product['icon'] ); ?>" alt="" class="wpms:h-[32px] wpms:w-auto wpms:max-w-[40px]">
									</div>
								<?php else : ?>
									<img src="<?php echo esc_url( $product['icon'] ); ?>" alt="" class="wpms:w-[60px] wpms:h-[60px] wpms:shrink-0">
								<?php endif; ?>
								<div class="wpms:flex wpms:flex-col wpms:flex-1 wpms:min-w-[0]">
									<h3 class="wpms:m-[0]! wpms:p-[0]! wpms:text-sm! wpms:leading-5! wpms:font-medium! wpms:text-primary">
										<?php echo esc_html( $product['title'] ); ?>
									</h3>
									<p class="wpms:m-[0]! wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
										<?php echo esc_html( $product['desc'] ); ?>
									</p>
								</div>
							</div>
							<div class="wpms-test-email-success-banner__card-actions wpms:flex wpms:items-center wpms:flex-wrap wpms:gap-[12px] wpms:mt-auto!">
								<button type="button"
									class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-light-grey js-wp-mail-smtp-plugin-install-btn status-download"
									data-plugin="<?php echo esc_attr( $product['install_url'] ); ?>"
									data-settings-url="<?php echo esc_url( $product['settings_page_url'] ); ?>">
									<?php
									printf(
										/* translators: %s - product name (e.g. WPConsent). */
										esc_html__( 'Install %s', 'wp-mail-smtp-pro' ),
										esc_html( $product['name'] )
									);
									?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the no-cross-sell variant used when all Pro features are set
	 * up and every cross-sell candidate is already installed. Uses WP's
	 * default success-notice styling instead of a custom banner — there's
	 * nothing left to surface beyond the success acknowledgement itself.
	 *
	 * @since 4.9.0
	 */
	private function display_success_banner_slim() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Test email sent successfully! Check your inbox to confirm delivery.', 'wp-mail-smtp-pro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Whether the Pro setup checklist {Email Log, Backup Connection,
	 * at least one Alert provider} is fully satisfied. Drives the
	 * setup-pending vs setup-done branch in the banner resolver.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_setup_done() {

		$options = Options::init();

		if ( empty( $options->get( 'logs', 'enabled' ) ) ) {
			return false;
		}

		if ( ! $this->is_backup_connection_configured() ) {
			return false;
		}

		return $this->is_any_alert_provider_enabled();
	}

	/**
	 * Whether at least one Alert provider is enabled in settings.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_any_alert_provider_enabled() {

		$options       = Options::init();
		$alerts_loader = new AlertsLoader();

		foreach ( array_keys( $alerts_loader->get_providers() ) as $provider_slug ) {
			if ( $options->get( 'alert_' . $provider_slug, 'enabled' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a Backup Connection is selected in settings AND the connections
	 * manager can actually resolve it.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_backup_connection_configured() {

		$connection_id = Options::init()->get( 'backup_connection', 'connection_id' );

		if ( empty( $connection_id ) ) {
			return false;
		}

		$backup_connection = wp_mail_smtp()->get_connections_manager()->get_connection( $connection_id, false );

		return ! empty( $backup_connection );
	}

	/**
	 * Build the backup-mailer tile shown on the Pro Setup Pending success banner.
	 *
	 * Three states drive icon / copy / CTA shape:
	 *
	 * - SendLayer is primary  → generic "Setup a Backup Mailer" tile linking
	 *   to the backup-connection settings anchor.
	 * - SendLayer is in additional connections (not yet backup) → SL-branded
	 *   tile linking to the same anchor so the user picks SL from the radio
	 *   list.
	 * - SendLayer is absent entirely → SL-branded tile whose CTA fires the
	 *   SendLayer Quick Connect flow in `backup_mailer` mode. One OAuth
	 *   round-trip provisions a new additional connection and assigns it as
	 *   the backup connection.
	 *
	 * @since 4.9.0
	 *
	 * @param bool   $is_sendlayer_primary Whether SendLayer is the primary mailer.
	 * @param string $backup_settings_url  Backup connection settings anchor URL.
	 * @param string $assets_url           Pro assets URL.
	 *
	 * @return array
	 */
	private function build_backup_tile( $is_sendlayer_primary, $backup_settings_url, $assets_url ) {

		$is_complete = $this->is_backup_connection_configured();

		if ( $is_sendlayer_primary || $is_complete ) {
			return [
				'icon'          => $assets_url . '/images/test-success/icon-feature-backup-connection.svg',
				'title'         => esc_html__( 'Setup a Backup Mailer', 'wp-mail-smtp-pro' ),
				'body'          => esc_html__( 'Setup a secondary email provider in case your primary provider fails', 'wp-mail-smtp-pro' ),
				'url'           => $backup_settings_url,
				'complete'      => $is_complete,
				'quick_connect' => false,
			];
		}

		// SL is neither primary nor configured as backup → always offer Quick
		// Connect's backup_mailer mode. An existing SL additional connection
		// may be misconfigured or reserved for something else, so we create a
		// dedicated "SendLayer (backup)" connection regardless.
		return [
			'icon'          => $assets_url . '/images/test-success/icon-backup-connection.svg',
			'title'         => esc_html__( 'Get a Free Backup mailer connection', 'wp-mail-smtp-pro' ),
			'body'          => esc_html__( 'Setup SendLayer, our recommended mailer, as a backup for free', 'wp-mail-smtp-pro' ),
			'url'           => $backup_settings_url,
			'complete'      => $is_complete,
			'quick_connect' => true,
		];
	}
}
