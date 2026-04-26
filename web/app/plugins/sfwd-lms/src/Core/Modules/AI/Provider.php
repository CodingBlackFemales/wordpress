<?php
/**
 * AI module provider class.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

use LearnDash\Core\Modules\Experiments\Experiment;
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
		$this->container->singleton( Quiz_Creation\Repository::class );

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
		// Course outline hooks.
		add_action( 'admin_menu', $this->container->callback( Course_Outline::class, 'register_page' ) );
		add_action( 'admin_head', $this->container->callback( Course_Outline::class, 'add_scripts' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Course_Outline::class, 'enqueue_admin_scripts' ) );
		add_action( 'admin_post_' . Course_Outline::$slug, $this->container->callback( Course_Outline::class, 'init' ) );
		add_filter( 'learndash_header_buttons', $this->container->callback( Course_Outline::class, 'add_header_buttons' ) );
		foreach ( Course_Outline::$ajax_actions as $key => $action ) {
			add_action( 'wp_ajax_' . $action, $this->container->callback( Course_Outline::class, 'handle_ajax_request' ) );
		}

		// Quiz creation hooks.

		add_action( 'admin_menu', $this->container->callback( Quiz_Creation\View::class, 'register_page' ) );
		add_action( 'admin_head', $this->container->callback( Quiz_Creation\View::class, 'remove_submenu_item' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Quiz_Creation\View::class, 'enqueue_admin_scripts' ) );
		add_filter( 'learndash_header_buttons', $this->container->callback( Quiz_Creation\View::class, 'add_header_buttons' ) );
		add_filter( 'learndash_ajax_send_response', $this->container->callback( Quiz_Creation\View::class, 'filter_quiz_search' ), 10, 2 );

		add_action( 'admin_post_' . Quiz_Creation::$slug, $this->container->callback( Quiz_Creation::class, 'init' ) );

		// Register experiments.

		add_filter( 'learndash_experiments', $this->container->callback( self::class, 'register_experiments' ) );
	}

	/**
	 * Registers experiment.
	 *
	 * @since 4.13.0
	 *
	 * @param Experiment[] $experiments Existing experiments.
	 *
	 * @return Experiment[] Returned experiments.
	 */
	public function register_experiments( array $experiments ): array {
		$experiments[] = new Virtual_Instructor\Experiment();

		return $experiments;
	}
}
