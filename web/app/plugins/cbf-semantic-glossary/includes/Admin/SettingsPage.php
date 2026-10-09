<?php
/**
 * Glossary settings screen.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Admin;

use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsPage.
 */
final class SettingsPage {

	/**
	 * Page slug.
	 */
	const SLUG = 'cbf-glossary-settings';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
	}


	/**
	 * Add Glossary › Settings.
	 */
	public static function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . PostType::NAME,
			__( 'Glossary Settings', 'cbf-semantic-glossary' ),
			__( 'Settings', 'cbf-semantic-glossary' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}


	/**
	 * Register the option.
	 */
	public static function register_setting(): void {
		register_setting(
			self::SLUG,
			Settings::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( Settings::class, 'sanitise' ),
				'default'           => Settings::defaults(),
			)
		);
	}


	/**
	 * Render the page.
	 */
	public static function render(): void {
		$settings = Settings::all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Glossary Settings', 'cbf-semantic-glossary' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::SLUG ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatic glossary', 'cbf-semantic-glossary' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[auto_append]" value="1" <?php checked( $settings['auto_append'] ); ?>>
								<?php esc_html_e( 'Append a glossary to posts that reference terms but have no Glossary block', 'cbf-semantic-glossary' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'A Glossary block placed in a post always takes precedence. Run `wp glossary block ensure` to add blocks to existing posts in bulk.', 'cbf-semantic-glossary' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="glossary-index-min"><?php esc_html_e( 'A–Z index', 'cbf-semantic-glossary' ); ?></label></th>
						<td>
							<input type="number" id="glossary-index-min" name="<?php echo esc_attr( Settings::OPTION ); ?>[index_min_entries]" value="<?php echo esc_attr( (string) $settings['index_min_entries'] ); ?>" min="1" max="<?php echo esc_attr( (string) Settings::INDEX_MIN_LIMIT ); ?>" step="1" class="small-text" aria-describedby="glossary-index-min-help">
							<?php esc_html_e( 'entries or more', 'cbf-semantic-glossary' ); ?>
							<p class="description" id="glossary-index-min-help"><?php esc_html_e( 'A glossary shows an A–Z index once it has at least this many entries. Applies to automatic glossaries and to Glossary blocks that do not set their own minimum.', 'cbf-semantic-glossary' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
