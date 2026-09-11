<?php
/**
 * Main importer admin page.
 *
 * Renders the React/JS SPA shell. All interaction is driven by the
 * REST API (cbf-si/v1/) — this PHP file only provides the WP admin
 * page wrapper and page-scoped data passed to the JS bundle.
 *
 * @class   Admin\ImporterPage
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

use CodingBlackFemales\SlidesImporter\Google\DriveClient;
use CodingBlackFemales\SlidesImporter\Google\OAuthClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer admin page class.
 *
 * Registered under LearnDash menu → "Slides Importer".
 * Capability: cbf_slides_import.
 */
final class ImporterPage {

	const PAGE_SLUG = 'cbf-slides-importer';

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}


	/**
	 * Add the importer page under the LearnDash top-level menu.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'learndash-lms',
			esc_html__( 'Slides Importer', 'cbf-slides-importer' ),
			esc_html__( 'Slides Importer', 'cbf-slides-importer' ),
			'cbf_slides_import',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}


	/**
	 * Render the page shell.
	 *
	 * The #cbf-si-app div is the React mount point. The JS bundle
	 * bootstraps the full UI from there.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'cbf_slides_import' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cbf-slides-importer' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Slides Importer', 'cbf-slides-importer' ); ?></h1>
			<?php Tabs::render( self::PAGE_SLUG ); ?>
			<?php self::render_account_panel(); ?>

			<div id="cbf-si-app"></div>
		</div>
		<?php
	}


	/**
	 * Show which Google account is connected, and how to change it.
	 *
	 * The connection is per WordPress user — the token lives in that user's
	 * meta — so this belongs on the screen every importer can reach rather than
	 * on the administrators-only Settings tab.
	 *
	 * Naming the account matters more than it sounds: until this existed the
	 * only evidence of which account was connected was whether an import
	 * happened to work, and a staging deployment was authorised as the wrong
	 * account without anyone noticing.
	 */
	private static function render_account_panel(): void {
		if ( ! OAuthClient::has_token( get_current_user_id() ) ) {
			self::render_disconnected_panel();
			return;
		}

		$account = DriveClient::account( get_current_user_id() );
		?>
		<div class="notice notice-success inline" style="margin:16px 0;padding:10px 12px;">
			<p style="margin:0 0 10px;">
				<?php if ( is_wp_error( $account ) ) : ?>
					<strong><?php esc_html_e( 'Google Drive is connected.', 'cbf-slides-importer' ); ?></strong>
					<?php echo ' ' . esc_html( $account->get_error_message() ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Connected to Google Drive as', 'cbf-slides-importer' ); ?>
					<strong><?php echo esc_html( self::account_label( $account ) ); ?></strong>
				<?php endif; ?>
			</p>
			<p style="margin:0;display:flex;gap:8px;flex-wrap:wrap;">
				<a href="<?php echo esc_url( OAuthBridge::begin_url() ); ?>" class="button">
					<?php esc_html_e( 'Use a different account', 'cbf-slides-importer' ); ?>
				</a>
				<a href="<?php echo esc_url( OAuthBridge::revoke_url() ); ?>" class="button">
					<?php esc_html_e( 'Disconnect', 'cbf-slides-importer' ); ?>
				</a>
			</p>
		</div>
		<?php
	}


	/**
	 * Prompt an importer who has not connected an account yet.
	 */
	private static function render_disconnected_panel(): void {
		$disconnected = isset( $_GET['google'] ) && $_GET['google'] === 'disconnected'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="notice notice-warning inline" style="margin:16px 0;padding:10px 12px;">
			<p style="margin:0 0 10px;">
				<?php if ( $disconnected ) : ?>
					<?php esc_html_e( 'Your Google account has been disconnected. Connect one to import from Drive.', 'cbf-slides-importer' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'You need to authorise Google Drive access before you can import from Drive.', 'cbf-slides-importer' ); ?>
				<?php endif; ?>
			</p>
			<p style="margin:0;">
				<a href="<?php echo esc_url( OAuthBridge::begin_url() ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect Google Drive', 'cbf-slides-importer' ); ?>
				</a>
			</p>
		</div>
		<?php
	}


	/**
	 * How to name the connected account.
	 *
	 * The address is what distinguishes two accounts belonging to one person,
	 * so it is never dropped in favour of the display name alone.
	 *
	 * @param  array $account { name: string, email: string }
	 * @return string
	 */
	private static function account_label( array $account ): string {
		if ( $account['email'] === '' ) {
			return $account['name'];
		}

		if ( $account['name'] === '' ) {
			return $account['email'];
		}

		return $account['name'] . ' (' . $account['email'] . ')';
	}
}
