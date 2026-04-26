<?php
/**
 * Virtual Instructor AI module provider class.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Virtual Instructor AI module.
 *
 * @since 4.13.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Repository::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		$post_type = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );

		// Admin hooks.

		add_filter( 'learndash_post_args', $this->container->callback( Admin::class, 'register_post_type' ) );
		add_filter( 'bulk_post_updated_messages', $this->container->callback( Admin::class, 'filter_bulk_post_updated_messages' ), 10, 2 );
		add_filter( 'learndash_submenu', $this->container->callback( Admin::class, 'register_submenu' ) );
		add_action( 'learndash_admin_menu', $this->container->callback( Admin::class, 'register_submenu_items' ) );
		add_action( 'add_meta_boxes', $this->container->callback( Admin::class, 'manage_meta_boxes' ) );
		add_filter( 'manage_' . $post_type . '_posts_columns', $this->container->callback( Admin::class, 'manage_posts_columns' ) );
		add_action( 'manage_' . $post_type . '_posts_custom_column', $this->container->callback( Admin::class, 'manage_posts_custom_column' ), 10, 2 );
		// High priority to make sure it's fired last.
		add_filter( 'enter_title_here', $this->container->callback( Admin::class, 'change_title_placeholder' ), 100, 2 );
		add_filter( 'get_sample_permalink_html', $this->container->callback( Admin::class, 'filter_get_sample_permalink_html' ), 10, 5 );
		// Priority must be after the initial user capabilities registration at priority 10.
		add_action( 'init', $this->container->callback( Admin::class, 'register_user_capabilities' ), 20 );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Admin::class, 'enqueue_scripts' ) );
		add_action( 'learndash_post_setting_updated', $this->container->callback( Admin::class, 'update_setting' ), 10, 3 );

		// Admin AJAX.

		add_action( 'wp_ajax_' . AJAX\Process_Setup_Wizard::$action, $this->container->callback( AJAX\Process_Setup_Wizard::class, 'handle_request' ) );

		// Setting pages and sections.

		add_action( 'learndash_settings_pages_init', [ Settings\Page::class, 'add_page_instance' ] );
		add_action( 'learndash_settings_sections_init', [ Settings\Page_Section::class, 'add_section_instance' ] );

		// Individual virtual instructor post edit class, and settings metaboxes.

		add_action( 'admin_init', [ Settings\Post::class, 'init' ] );
		add_filter( 'learndash_post_settings_metaboxes_init_' . $post_type, [ Settings\Post_Metabox::class, 'add_meta_box_instance' ] );

		// Frontend.

		add_action( 'wp_footer', $this->container->callback( Frontend::class, 'output_chatbox_wrapper' ) );
		add_action( 'wp_enqueue_scripts', $this->container->callback( Frontend::class, 'enqueue_scripts' ) );

		// Chatbox AJAX.

		add_action( 'wp_ajax_' . AJAX\Chat_Init::$action, $this->container->callback( AJAX\Chat_Init::class, 'handle_request' ) );
		add_action( 'wp_ajax_' . AJAX\Chat_Input::$action, $this->container->callback( AJAX\Chat_Input::class, 'handle_request' ) );
		add_action( 'wp_ajax_' . AJAX\Chat_Send::$action, $this->container->callback( AJAX\Chat_Send::class, 'handle_request' ) );
	}
}
