<?php

namespace WPMailSMTP\Pro\Alerts\Providers\WhatsApp;

use WPMailSMTP\Pro\Alerts\AbstractOptions;

/**
 * Class Options. WhatsApp alert settings.
 *
 * @since 4.5.0
 */
class Options extends AbstractOptions {

	/**
	 * Provider slug.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	const SLUG = 'whatsapp';

	/**
	 * Constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {

		$description = wp_kses(
			sprintf(
			/* translators: %1$s - Documentation link. */
				__( 'Enter your WhatsApp Cloud API credentials to receive alerts when email sending fails. You\'ll need to create a Meta developer account, set up a WhatsApp Business Platform account, and register the "wp_mail_smtp_alert" template in the Meta developer portal. <a href="%1$s" target="_blank" rel="noopener noreferrer">Read our documentation on setting up WhatsApp alerts</a>.', 'wp-mail-smtp-pro' ),
				esc_url(
					wp_mail_smtp()->get_utm_url(
						'https://wpmailsmtp.com/docs/setting-up-email-alerts/#whatsapp',
						[
							'medium'  => 'Alerts Settings',
							'content' => 'WhatsApp Documentation',
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

		parent::__construct(
			[
				'slug'                => self::SLUG,
				'title'               => esc_html__( 'WhatsApp', 'wp-mail-smtp-pro' ),
				'description'         => $description,
				'add_connection_text' => esc_html__( 'Add Another Connection', 'wp-mail-smtp-pro' ),
			]
		);
	}

	/**
	 * Output the provider options.
	 *
	 * @since 4.5.0
	 */
	public function display_options() {

		$connections = $this->options->get( $this->get_group(), 'connections' );

		if ( empty( $connections ) ) {
			$connections = [
				[
					'access_token'         => '',
					'phone_number_id'      => '',
					'to_phone_number'      => '',
					'whatsapp_business_id' => '',
				],
			];
		}

		foreach ( $connections as $i => $connection ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_connection_options( $connection, $i );
		}
	}

	/**
	 * Get single connection options.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $connection Connection settings.
	 * @param string $i          Connection index.
	 *
	 * @return string
	 */
	public function get_connection_options( $connection, $i ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$slug  = $this->get_slug();
		$group = $this->get_group();

		$access_token         = isset( $connection['access_token'] ) ? $connection['access_token'] : '';
		$phone_number_id      = isset( $connection['phone_number_id'] ) ? $connection['phone_number_id'] : '';
		$to_phone_number      = isset( $connection['to_phone_number'] ) ? $connection['to_phone_number'] : '';
		$whatsapp_business_id = isset( $connection['whatsapp_business_id'] ) ? $connection['whatsapp_business_id'] : '';

		ob_start();
		?>
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-connection-options">
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-access-token-<?php echo esc_attr( $i ); ?>">
						<?php esc_html_e( 'Access Token', 'wp-mail-smtp-pro' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					printf(
						'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][access_token]" type="password" value="%3$s" id="wp-mail-smtp-setting-alert-%1$s-access-token-%2$s" spellcheck="false" %4$s %5$s/>',
						esc_attr( $slug ),
						esc_attr( $i ),
						esc_attr( $access_token ),
						disabled( true, $this->options->is_const_defined( $group, 'connections' ), false ),
						$this->options->get( $group, 'enabled' ) ? 'required' : ''
					);
					?>

					<?php if ( $this->options->is_const_defined( $group, 'connections' ) ) : ?>
						<p class="desc">
							<?php
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $this->options->get_const_set_message( 'WPMS_ALERT_WHATSAPP_ACCESS_TOKEN' );
							?>
						</p>
					<?php endif; ?>
					<p class="desc">
						<?php
						printf(
						/* translators: %s - URL to Facebook Developers page. */
							esc_html__( 'Your permanent access token with WhatsApp Business Messaging permission. Generate it in the %1$sMeta App Dashboard%2$s under "User Token Generator" or "System User" section.', 'wp-mail-smtp-pro' ),
							'<a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
			</div>

			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-whatsapp-business-id-<?php echo esc_attr( $i ); ?>">
						<?php esc_html_e( 'WhatsApp Business Account ID', 'wp-mail-smtp-pro' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					printf(
						'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][whatsapp_business_id]" type="text" value="%3$s" id="wp-mail-smtp-setting-alert-%1$s-whatsapp-business-id-%2$s" spellcheck="false" %4$s %5$s/>',
						esc_attr( $slug ),
						esc_attr( $i ),
						esc_attr( $whatsapp_business_id ),
						disabled( true, $this->options->is_const_defined( $group, 'connections' ), false ),
						$this->options->get( $group, 'enabled' ) ? 'required' : ''
					);
					?>

					<?php if ( $this->options->is_const_defined( $group, 'connections' ) ) : ?>
						<p class="desc">
							<?php
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $this->options->get_const_set_message( 'WPMS_ALERT_WHATSAPP_BUSINESS_ID' );
							?>
						</p>
					<?php endif; ?>
					<p class="desc">
						<?php
						printf(
						/* translators: %1$s - opening link tag, %2$s - closing link tag. */
							esc_html__( 'Your WhatsApp Business Account ID from the Meta Business Platform. Find it in the %1$sWhatsApp Accounts section%2$s of your Meta Business Manager.', 'wp-mail-smtp-pro' ),
							'<a href="https://business.facebook.com/settings/whatsapp-business-accounts/" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
			</div>

			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-phone-number-id-<?php echo esc_attr( $i ); ?>">
						<?php esc_html_e( 'Phone Number ID', 'wp-mail-smtp-pro' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					printf(
						'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][phone_number_id]" type="text" value="%3$s" id="wp-mail-smtp-setting-alert-%1$s-phone-number-id-%2$s" spellcheck="false" %4$s %5$s/>',
						esc_attr( $slug ),
						esc_attr( $i ),
						esc_attr( $phone_number_id ),
						disabled( true, $this->options->is_const_defined( $group, 'connections' ), false ),
						$this->options->get( $group, 'enabled' ) ? 'required' : ''
					);
					?>

					<?php if ( $this->options->is_const_defined( $group, 'connections' ) ) : ?>
						<p class="desc">
							<?php
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $this->options->get_const_set_message( 'WPMS_ALERT_WHATSAPP_PHONE_NUMBER_ID' );
							?>
						</p>
					<?php endif; ?>
					<p class="desc">
						<?php
						printf(
						/* translators: %1$s - opening link tag, %2$s - closing link tag. */
							esc_html__( 'Your business phone number ID from the WhatsApp Cloud API. This is a numeric value found in the %1$sWhatsApp Manager → API Setup screen%2$s when you click on your business phone number.', 'wp-mail-smtp-pro' ),
							'<a href="https://business.facebook.com/wa/manage/phone-numbers/" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
			</div>

			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-to-phone-number-<?php echo esc_attr( $i ); ?>">
						<?php esc_html_e( 'To Phone Number', 'wp-mail-smtp-pro' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					printf(
						'<input name="wp-mail-smtp[alert_%1$s][connections][%2$s][to_phone_number]" type="text" value="%3$s" id="wp-mail-smtp-setting-alert-%1$s-to-phone-number-%2$s" spellcheck="false" %4$s %5$s/>',
						esc_attr( $slug ),
						esc_attr( $i ),
						esc_attr( $to_phone_number ),
						disabled( true, $this->options->is_const_defined( $group, 'connections' ), false ),
						$this->options->get( $group, 'enabled' ) ? 'required' : ''
					);
					?>

					<?php if ( $this->options->is_const_defined( $group, 'connections' ) ) : ?>
						<p class="desc">
							<?php
							//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $this->options->get_const_set_message( 'WPMS_ALERT_WHATSAPP_TO_PHONE_NUMBER' );
							?>
						</p>
					<?php endif; ?>
					<p class="desc">
						<?php esc_html_e( 'The phone number where you want to receive alerts. Should be numeric only without any spaces or special characters (e.g., 12345678901).', 'wp-mail-smtp-pro' ); ?>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $connection['access_token'] ) && ! empty( $connection['phone_number_id'] ) && ! empty( $connection['whatsapp_business_id'] ) ) : ?>
				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text" style="min-height: 47px;">
					<div class="wp-mail-smtp-setting-label">
						<label for="wp-mail-smtp-setting-alert-<?php echo esc_attr( $slug ); ?>-template-status-<?php echo esc_attr( $i ); ?>" class="wp-mail-smtp-setting-label-with-tooltip">
							<?php esc_html_e( 'WhatsApp Message Template Status', 'wp-mail-smtp-pro' ); ?>

							<span class="wp-mail-smtp-tooltip wp-mail-smtp-tooltip-with-icon">
								<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/font-awesome/info-circle.svg' ); ?>" width="15" height="15" alt="info"/>
								<span class="wp-mail-smtp-tooltip-text wp-mail-smtp-tooltip-small-text">
									<?php esc_html_e( 'To send WhatsApp alerts, a message template must be created. We generate it automatically for you, so you don\'t need to do anything, but it must be approved by the WhatsApp team before it can be used. This review process can take up to 24–48 hours, though it\'s usually completed sooner.', 'wp-mail-smtp-pro' ); ?>
								</span>
							</span>
						</label>
					</div>
					<div class="wp-mail-smtp-setting-field">
						<?php $this->display_template_status( $connection ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Display template status with appropriate UI.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection WhatsApp connection settings.
	 * @param bool  $force      Whether to force a fresh check bypassing cache. Default false.
	 */
	public function display_template_status( $connection, $force = false ) {  //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Only check status if we have connection details.
		if ( empty( $connection['access_token'] ) || empty( $connection['phone_number_id'] ) || empty( $connection['whatsapp_business_id'] ) ) {
			return;
		}

		$handler         = new Handler();
		$template_status = $handler->check_template_status( $connection, $force );

		if ( ! empty( $template_status['error'] ) ) {
			$this->display_status_message(
				esc_html__( 'Status Check Failed', 'wp-mail-smtp-pro' ),
				'cancel-red'
			);
			$this->display_status_notice(
				esc_html__( 'The template status check failed. Please try again later.', 'wp-mail-smtp-pro' ),
				'error'
			);

			return;
		}

		if ( $template_status['exists'] ) {
			$status_text = $template_status['status'];

			if ( $status_text === 'APPROVED' ) {
				$this->display_status_message(
					esc_html__( 'Approved', 'wp-mail-smtp-pro' ),
					'check-circle-dark-green'
				);
				$this->display_recheck_status( $connection );
			} elseif ( $status_text === 'REJECTED' ) {
				$this->display_status_message(
					esc_html__( 'Rejected', 'wp-mail-smtp-pro' ),
					'cancel-red'
				);
				$this->display_recheck_status( $connection );
				$this->display_status_notice(
					esc_html__( 'The template has been rejected. Please review the template and resubmit it.', 'wp-mail-smtp-pro' ),
					'error'
				);
			} elseif ( $status_text === 'PENDING' ) {
				$this->display_status_message(
					esc_html__( 'Pending', 'wp-mail-smtp-pro' ),
					'clock-orange',
				);
				$this->display_recheck_status( $connection );
				$this->display_status_notice(
					esc_html__( 'The template is pending approval. Please check back later.', 'wp-mail-smtp-pro' ),
					'warning'
				);
			} else {
				$this->display_status_message( $status_text );
				$this->display_recheck_status( $connection );
			}

			return;
		} else {
			$this->display_status_message(
				esc_html__( 'Not Found', 'wp-mail-smtp-pro' ),
				'cancel-red'
			);
			$this->display_recheck_status( $connection );
		}
	}

	/**
	 * Display status message with appropriate styling and icon.
	 *
	 * @since 4.5.0
	 *
	 * @param string $status_text The text to display for the status.
	 * @param string $icon_slug   The slug of the icon to display.
	 */
	private function display_status_message( $status_text, $icon_slug = 'clock-orange' ) {

		echo '<div class="wp-mail-smtp-setting-status-label wp-mail-smtp-setting-status-label-' . esc_attr( str_replace( ' ', '-', strtolower( $status_text ) ) ) . '">';
		echo esc_html( $status_text );
		echo '<img src="' . esc_url( wp_mail_smtp()->assets_url . '/images/font-awesome/' . $icon_slug . '.svg' ) . '" alt="" /> ';
		echo '</div>';
	}

	/**
	 * Display recheck status button.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection WhatsApp connection settings.
	 */
	private function display_recheck_status( $connection ) {

		echo '<a href="#" class="wp-mail-smtp-whatsapp-recheck-status" data-connection-id="' .
				 esc_attr( hash( 'sha256', wp_json_encode( $connection ) ) ) . '">' .
				 '<span>' . esc_html__( 'Recheck Status', 'wp-mail-smtp-pro' ) . '</span>' .
				 '<img src="' . esc_url( wp_mail_smtp()->assets_url . '/images/font-awesome/arrow-rotate-right-purple.svg' ) . '" alt="arrow-rotate-right-purple" />' .
				 '</a>';
	}

	/**
	 * Display status notice.
	 *
	 * @since 4.5.0
	 *
	 * @param string $message     The message to display.
	 * @param string $notice_type The type of notice to display (error, warning, info, etc.).
	 */
	private function display_status_notice( $message, $notice_type = 'warning' ) {

		echo '<div class="notice inline notice-inline wp-mail-smtp-notice notice-' . esc_attr( $notice_type ) . '">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '</div>';
	}
}
