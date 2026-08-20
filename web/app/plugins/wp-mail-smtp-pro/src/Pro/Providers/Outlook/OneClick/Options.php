<?php

namespace WPMailSMTP\Pro\Providers\Outlook\OneClick;

use WPMailSMTP\Admin\ConnectionSettings;
use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Helpers\UI;
use WPMailSMTP\Pro\Providers\Outlook\Options as ManualSetupOptions;
use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 4.3.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 4.3.0
	 */
	const SLUG = 'outlook';

	/**
	 * Manual setup options.
	 *
	 * @since 4.3.0
	 *
	 * @var ManualSetupOptions
	 */
	private $manual_setup_options;

	/**
	 * Outlook Options constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		$this->manual_setup_options = new ManualSetupOptions( $connection );

		parent::__construct(
			[
				'logo_url'    => $this->manual_setup_options->get_logo_url(),
				'slug'        => self::SLUG,
				'title'       => $this->manual_setup_options->get_title(),
				'description' => $this->manual_setup_options->get_description(),
				'notices'     => $this->manual_setup_options->get_notices(),
				'php'         => $this->manual_setup_options->get_php_version(),
				'supports'    => $this->manual_setup_options->get_supports(),
			],
			$connection
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 4.3.0
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}

		$one_click_setup_enabled = $this->connection_options->get( 'outlook', 'one_click_setup_enabled' );
		?>

		<div class="wp-mail-smtp-setting-row">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-one_click_setup_enabled">
					<?php esc_html_e( 'One-Click Setup', 'wp-mail-smtp-pro' ); ?>
				</label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php
				UI::toggle(
					[
						'name'    => 'wp-mail-smtp[' . esc_attr( $this->get_slug() ) . '][one_click_setup_enabled]',
						'id'      => 'wp-mail-smtp-setting-' . esc_attr( $this->get_slug() ) . '-one_click_setup_enabled',
						'checked' => $one_click_setup_enabled,
					]
				);
				?>
				<p class="desc">
					<?php esc_html_e( 'Provides a quick and easy way to connect to Microsoft that doesn\'t require creating your own app.', 'wp-mail-smtp-pro' ); ?>
				</p>
			</div>
		</div>

		<div class="wp-mail-smtp-mailer-option__group wp-mail-smtp-mailer-option__group--outlook-custom" <?php echo $one_click_setup_enabled === true ? 'style="display: none;"' : ''; ?>>
			<?php $this->manual_setup_options->display_options(); ?>
		</div>

		<div class="wp-mail-smtp-mailer-option__group wp-mail-smtp-mailer-option__group--outlook-one_click_setup" <?php echo $one_click_setup_enabled !== true ? 'style="display: none;"' : ''; ?>>
			<?php if ( ! wp_mail_smtp()->get_pro()->get_license()->is_valid() ) : ?>
				<!-- License notice. -->
				<div class="wp-mail-smtp-setting-row" style="margin-top: -10px;">
					<div class="wp-mail-smtp-setting-field">
						<?php $this->display_license_notice(); ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Authorization button. -->
			<div id="wp-mail-smtp-setting-row-outlook-one-click-setup-authorize" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
				<div class="wp-mail-smtp-setting-label">
					<label><?php esc_html_e( 'Authorization', 'wp-mail-smtp-pro' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php $this->display_auth_setting_action(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output license notice if it's not valid.
	 *
	 * @since 4.3.0
	 */
	private function display_license_notice() {

		$license                 = wp_mail_smtp()->get_pro()->get_license();
		$one_click_setup_enabled = $this->connection_options->get( 'outlook', 'one_click_setup_enabled' );
		?>
		<?php if ( $license->is_expired() ) : ?>
			<?php if ( $one_click_setup_enabled ) : ?>
				<p class="inline-notice inline-error">
					<?php
					printf(
						wp_kses( /* translators: %1$s - WPMailSMTP.com renew URL. */
							__( 'One-Click Setup requires an active license. <a href="%1$s" target="_blank" rel="noopener noreferrer">Renew your license</a> and reconnect your authorization.', 'wp-mail-smtp-pro' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url(
							$license->get_renewal_link(
								[
									'medium'  => 'outlook-one-click-setting-notice',
									'content' => 'Renew your license',
								]
							)
						)
					);
					?>
				</p>
			<?php else : ?>
				<p class="inline-notice inline-info">
					<?php esc_html_e( 'One-Click Setup requires an active license. You can renew your license above to proceed with this One-Click Setup.', 'wp-mail-smtp-pro' ); ?>
				</p>
			<?php endif; ?>
		<?php elseif ( ! $license->is_valid() ) : ?>
			<?php if ( $one_click_setup_enabled ) : ?>
				<p class="inline-notice inline-error">
					<?php esc_html_e( 'One-Click Setup requires an active license. Verify your license above to proceed with this One-Click Setup.', 'wp-mail-smtp-pro' ); ?>
				</p>
			<?php else : ?>
				<p class="inline-notice inline-info">
					<?php esc_html_e( 'One-Click Setup requires an active license. You can verify your license above to proceed with this One-Click Setup.', 'wp-mail-smtp-pro' ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Display either an "Allow..." or "Remove..." button.
	 *
	 * @since 4.3.0
	 */
	protected function display_auth_setting_action() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Do the processing on the fly, as having ajax here is too complicated.
		$this->process_provider_remove();

		$auth    = new Auth( $this->connection );
		$license = wp_mail_smtp()->get_pro()->get_license();
		?>
		<?php if ( $this->connection->get_mailer_slug() === self::SLUG ) : ?>
			<?php if ( $auth->is_auth_required() ) : ?>
				<a href="<?php echo esc_url( $auth->get_auth_url() ); ?>" class="wp-mail-smtp-sign-in-btn<?php echo ! $license->is_valid() ? ' wp-mail-smtp-sign-in-btn--disabled' : ''; ?>">
					<div class="wp-mail-smtp-sign-in-btn__icon">
						<svg width="46" height="46" viewBox="0 0 46 46" version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g><g><path class="wp-mail-smtp-sign-in-icon__border" d="M43,5c0,-1.104 -0.896,-2 -2,-2l-36,0c-1.104,0 -2,0.896 -2,2l0,36c0,1.104 0.896,2 2,2l36,0c1.104,0 2,-0.896 2,-2l0,-36Z" fill="#4285f4"/><g><path class="wp-mail-smtp-sign-in-icon__bg" d="M42,5c0,-0.552 -0.448,-1 -1,-1l-36,0c-0.552,0 -1,0.448 -1,1l0,36c0,0.552 0.448,1 1,1l36,0c0.552,0 1,-0.448 1,-1l0,-36Z" fill="#fff"/></g><rect class="wp-mail-smtp-sign-in-icon__symbol" x="14" y="14" width="8.556" height="8.556" fill="#f25022"/><rect class="wp-mail-smtp-sign-in-icon__symbol" x="23.444" y="14" width="8.556" height="8.556" fill="#7fba00"/><rect class="wp-mail-smtp-sign-in-icon__symbol" x="14" y="23.444" width="8.556" height="8.556" fill="#00a4ef"/><rect class="wp-mail-smtp-sign-in-icon__symbol" x="23.444" y="23.444" width="8.556" height="8.556" fill="#ffb900"/></g></svg>
					</div>
					<div class="wp-mail-smtp-sign-in-btn__text">
						<?php esc_html_e( 'Sign in with Microsoft', 'wp-mail-smtp-pro' ); ?>
					</div>
				</a>
			<?php elseif ( $auth->is_reauth_required() && $license->is_valid() ) : ?>
				<div class="wp-mail-smtp-connected-row">
					<a href="<?php echo esc_url( $auth->get_auth_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">
						<?php esc_html_e( 'Reconnect', 'wp-mail-smtp-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( $this->get_remove_connection_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-red js-wp-mail-smtp-provider-remove">
						<?php esc_html_e( 'Remove OAuth Connection', 'wp-mail-smtp-pro' ); ?>
					</a>
					<div class="wp-mail-smtp-connected-row__info">
						<?php
						$user = $auth->get_user_info();

						if ( ! empty( $user['email'] ) && ! empty( $user['name'] ) ) {
							printf(
							/* translators: %s - Display name and email, as received from Microsoft API. */
								esc_html__( 'Connected as %s', 'wp-mail-smtp-pro' ),
								'<code>' . esc_html( $user['name'] . ' <' . $user['email'] . '>' ) . '</code>'
							);
						}
						?>
					</div>
				</div>
				<p class="inline-notice inline-error">
					<?php esc_html_e( 'Your Microsoft account connection has expired. Please reconnect your account.', 'wp-mail-smtp-pro' ); ?>
				</p>
			<?php else : ?>
				<div class="wp-mail-smtp-connected-row">
					<a href="<?php echo esc_url( $this->get_remove_connection_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-red js-wp-mail-smtp-provider-remove">
						<?php esc_html_e( 'Remove OAuth Connection', 'wp-mail-smtp-pro' ); ?>
					</a>
					<div class="wp-mail-smtp-connected-row__info">
						<?php
						$user = $auth->get_user_info();

						if ( ! empty( $user['email'] ) && ! empty( $user['name'] ) ) {
							printf(
							/* translators: %s - Display name and email, as received from Microsoft API. */
								esc_html__( 'Connected as %s', 'wp-mail-smtp-pro' ),
								'<code>' . esc_html( $user['name'] . ' <' . $user['email'] . '>' ) . '</code>'
							);
						}
						?>
					</div>
				</div>
				<p class="desc">
					<?php esc_html_e( 'You can also send emails with different From Email addresses, by disabling the Force From Email setting and using registered aliases throughout your WordPress site as the From Email addresses.', 'wp-mail-smtp-pro' ); ?>
				</p>
				<p class="desc">
					<?php esc_html_e( 'Removing the OAuth connection will give you an ability to redo the OAuth connection or link to another Microsoft account.', 'wp-mail-smtp-pro' ); ?>
				</p>
			<?php endif; ?>
		<?php else : ?>
			<p class="inline-notice inline-error">
				<?php esc_html_e( 'You need to save settings before you can proceed.', 'wp-mail-smtp-pro' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get OAuth connection remove URL.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	private function get_remove_connection_url() {

		return wp_nonce_url( ( new ConnectionSettings( $this->connection ) )->get_admin_page_url(), 'outlook_one_click_setup_remove', 'outlook_one_click_setup_remove_nonce' ) . '#wp-mail-smtp-setting-row-outlook-one-click-setup-authorize';
	}

	/**
	 * Remove Provider OAuth connection.
	 *
	 * @since 4.3.0
	 */
	public function process_provider_remove() {

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return;
		}

		if (
			! isset( $_GET['outlook_one_click_setup_remove_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_GET['outlook_one_click_setup_remove_nonce'] ), 'outlook_one_click_setup_remove' )
		) {
			return;
		}

		if ( $this->connection->get_mailer_slug() !== $this->get_slug() ) {
			return;
		}

		$old_opt = $this->connection_options->get_all_raw();

		unset( $old_opt[ $this->get_slug() ]['one_click_setup_credentials'] );
		unset( $old_opt[ $this->get_slug() ]['one_click_setup_user_details'] );

		$this->connection_options->set( $old_opt );

		( new Auth( $this->connection ) )->get_client()->disconnect();
	}
}
