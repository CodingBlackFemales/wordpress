<?php
/**
 * AI module provider class.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

use LearnDash\Core\Services\ChatGPT;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for AI modules.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function register(): void {
		$options        = get_option( 'learndash_ai_integrations' );
		$openai_api_key = is_array( $options ) && ! empty( $options['openai_api_key'] ) ? $options['openai_api_key'] : '';

		$this->container->when( ChatGPT::class )
			->needs( '$api_key' )
			->give( $openai_api_key );

		$this->container->singleton( Course_Outline::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_footer-edit.php', $this->container->callback( Course_Outline::class, 'add_button' ), 20 );

		add_action( 'admin_menu', $this->container->callback( Course_Outline::class, 'register_page' ) );
		add_action( 'admin_head', $this->container->callback( Course_Outline::class, 'add_scripts' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Course_Outline::class, 'enqueue_admin_scripts' ) );
		add_action( 'admin_post_' . Course_Outline::$slug, $this->container->callback( Course_Outline::class, 'init' ) );
		foreach ( Course_Outline::$ajax_actions as $key => $action ) {
			add_action( 'wp_ajax_' . $action, $this->container->callback( Course_Outline::class, 'handle_ajax_request' ) );
		}
	}
}
