<?php
/**
 * MCP Server service provider.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mcp;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * MCP Server service provider.
 *
 * @since 5.0.0
 */
final class Provider extends ServiceProvider {
	/**
	 * Register container bindings.
	 *
	 * @since 5.0.0
	 */
	public function register(): void {
		$this->container->singleton( Asset_Loader::class );

		add_action(
			'init',
			$this->container->callback( Asset_Loader::class, 'register_scripts' )
		);
	}
}
