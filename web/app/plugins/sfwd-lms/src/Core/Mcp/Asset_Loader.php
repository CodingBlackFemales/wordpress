<?php
/**
 * MCP Server asset loader.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mcp;

use StellarWP\Learndash\StellarWP\Assets\Asset;

/**
 * MCP Server asset loader.
 *
 * @since 5.0.0
 */
class Asset_Loader {
	/**
	 * Script handle for integrating LearnDash MCP server into Elementor's Angie SDK.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	private const ANGIE_HANDLE = 'ld-mcp-angie';

	/**
	 * Asset group for the MCP server.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	private const ASSET_GROUP = 'mcp';

	/**
	 * Register the LearnDash MCP server into Elementor's Angie SDK if the Angie plugin
	 * is activated and the user has access.
	 *
	 * @action init
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		Asset::add( self::ANGIE_HANDLE, 'angie.js' )
			->add_to_group( self::ASSET_GROUP )
			->use_asset_file()
			->set_dependencies( 'angie-app' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_path( 'src/assets/dist/js/mcp/', false )
			->add_localize_script(
				'LearnDashMcpServerOptions',
				[
					'adminUrl' => admin_url(),
				]
			)
			->set_condition(
				static fn(): bool => defined( 'ANGIE_VERSION' ) && current_user_can( 'use_angie' )
			)
			->register();
	}
}
