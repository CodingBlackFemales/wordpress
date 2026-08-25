<?php
/**
 * Plugin settings page (WP Settings API).
 *
 * Provides two fields:
 *  - Google OAuth client secret (JSON, stored AES-256-GCM encrypted).
 *  - CBF shared Drive folder ID (plain string).
 *
 * @class   Admin\SettingsPage
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

use CodingBlackFemales\SlidesImporter\Crypto;
use CodingBlackFemales\SlidesImporter\Install;
use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page class.
 *
 * Registered under LearnDash menu → "Slides Importer" → "Settings".
 * Capability: manage_options (admin-only).
 */
final class SettingsPage {

	const PAGE_SLUG    = 'cbf-slides-importer-settings';
	const OPTION_GROUP = 'cbf_si_settings';
	const SECTION_ID   = 'cbf_si_google';

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}


	/**
	 * Add the settings page under the LearnDash menu.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'learndash-lms',
			esc_html__( 'Slides Importer Settings', 'cbf-slides-importer' ),
			esc_html__( 'Slides Importer', 'cbf-slides-importer' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}


	/**
	 * Register settings, section, and fields with the WP Settings API.
	 */
	public static function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			Install::CLIENT_SECRET_OPTION,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_client_secret' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			Install::FOLDER_ID_OPTION,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_section(
			self::SECTION_ID,
			esc_html__( 'Google API Configuration', 'cbf-slides-importer' ),
			array( __CLASS__, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'cbf_si_client_secret',
			esc_html__( 'OAuth Client Secret JSON', 'cbf-slides-importer' ),
			array( __CLASS__, 'render_client_secret_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID
		);

		add_settings_field(
			'cbf_si_folder_id',
			esc_html__( 'Shared Drive Folder ID', 'cbf-slides-importer' ),
			array( __CLASS__, 'render_folder_id_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID
		);
	}


	/**
	 * Sanitize and encrypt the client secret JSON before storing.
	 *
	 * If the field is blank (admin did not change it), the existing stored
	 * value is preserved by returning the current option.
	 *
	 * @param  mixed $value Raw submitted value.
	 * @return string       Encrypted ciphertext, or empty string on failure.
	 */
	public static function sanitize_client_secret( mixed $value ): string {
		// wp_unslash first (WP adds slashes to POST data), then trim whitespace.
		// Do NOT use sanitize_textarea_field here — it encodes quotes and angle
		// brackets which breaks JSON parsing.
		$value = trim( wp_unslash( (string) $value ) );

		if ( $value === '' ) {
			// Preserve existing value when field is left blank.
			return (string) get_option( Install::CLIENT_SECRET_OPTION, '' );
		}

		// Validate that it is parseable JSON and contains expected keys.
		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			add_settings_error(
				Install::CLIENT_SECRET_OPTION,
				'cbf_si_invalid_json',
				esc_html__( 'Client secret must be valid JSON.', 'cbf-slides-importer' )
			);
			return (string) get_option( Install::CLIENT_SECRET_OPTION, '' );
		}

		// Accept both "web" (Web Application) and "installed" (Desktop) credential types.
		$cred = $decoded['web'] ?? $decoded['installed'] ?? null;
		if ( ! $cred || empty( $cred['client_id'] ) || empty( $cred['client_secret'] ) ) {
			add_settings_error(
				Install::CLIENT_SECRET_OPTION,
				'cbf_si_invalid_secret_structure',
				esc_html__( 'Client secret JSON must contain a "web" or "installed" key with client_id and client_secret fields.', 'cbf-slides-importer' )
			);
			return (string) get_option( Install::CLIENT_SECRET_OPTION, '' );
		}

		// Re-encode to normalise whitespace (strips stray newlines/spaces).
		$value = wp_json_encode( $decoded );

		$encrypted = Crypto::encrypt( $value );
		if ( is_wp_error( $encrypted ) ) {
			add_settings_error(
				Install::CLIENT_SECRET_OPTION,
				'cbf_si_encrypt_error',
				sprintf(
					/* translators: error message */
					esc_html__( 'Could not encrypt client secret: %s', 'cbf-slides-importer' ),
					esc_html( $encrypted->get_error_message() )
				)
			);
			return (string) get_option( Install::CLIENT_SECRET_OPTION, '' );
		}

		return $encrypted;
	}


	/** Section description. */
	public static function render_section_description(): void {
		echo '<p>' . esc_html__( 'Configure the Google OAuth application credentials and the shared Drive folder that editors will browse.', 'cbf-slides-importer' ) . '</p>';
	}


	/** Render the client secret textarea field. */
	public static function render_client_secret_field(): void {
		$stored = get_option( Install::CLIENT_SECRET_OPTION, '' );
		$has    = ! empty( $stored );
		?>
		<textarea
			id="cbf_si_client_secret"
			name="<?php echo esc_attr( Install::CLIENT_SECRET_OPTION ); ?>"
			rows="8"
			cols="60"
			class="large-text code"
			placeholder="<?php esc_attr_e( 'Paste the contents of your client_secret.json here', 'cbf-slides-importer' ); ?>"
		></textarea>
		<?php if ( $has ) : ?>
			<p class="description" style="color: green;">
				&#10003; <?php esc_html_e( 'A client secret is already stored. Leave blank to keep it unchanged.', 'cbf-slides-importer' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Download this from Google Cloud Console → Credentials → OAuth 2.0 Client IDs.', 'cbf-slides-importer' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}


	/** Render the Drive folder ID input field. */
	public static function render_folder_id_field(): void {
		$value = get_option( Install::FOLDER_ID_OPTION, '' );
		?>
		<input
			type="text"
			id="cbf_si_folder_id"
			name="<?php echo esc_attr( Install::FOLDER_ID_OPTION ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			class="regular-text"
			placeholder="e.g. 1A2B3C4D5E6F..."
		/>
		<p class="description">
			<?php esc_html_e( 'The Google Drive folder ID that the file picker will open. Editors will only be able to browse within this folder.', 'cbf-slides-importer' ); ?>
		</p>
		<?php
	}


	/**
	 * Render the full settings page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cbf-slides-importer' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CBF Slides Importer — Settings', 'cbf-slides-importer' ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
