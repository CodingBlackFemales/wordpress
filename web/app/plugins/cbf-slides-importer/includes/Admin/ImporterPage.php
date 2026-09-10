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

		$is_authed  = OAuthClient::has_token( get_current_user_id() );
		// OAuthBridge::begin_url() returns a nonce-protected admin-post URL that
		// redirects the browser to Google. The REST /auth/begin endpoint is
		// reserved for the JS SPA (which sends X-WP-Nonce in the request header).
		$auth_url   = OAuthBridge::begin_url();
		$revoke_url = rest_url( 'cbf-si/v1/auth/revoke' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Slides Importer', 'cbf-slides-importer' ); ?></h1>
			<?php Tabs::render( self::PAGE_SLUG ); ?>

			<?php if ( ! $is_authed ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'You need to authorise Google Drive access before you can import from Drive.', 'cbf-slides-importer' ); ?>
						<a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary" style="margin-left:8px;">
							<?php esc_html_e( 'Connect Google Drive', 'cbf-slides-importer' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div
				id="cbf-si-app"
				data-authed="<?php echo esc_attr( $is_authed ? '1' : '0' ); ?>"
				data-revoke-url="<?php echo esc_url( $revoke_url ); ?>"
			></div>
		</div>
		<?php
	}
}
