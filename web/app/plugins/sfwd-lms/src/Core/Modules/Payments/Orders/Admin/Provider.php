<?php
/**
 * LearnDash Payments Admin Provider class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Orders\Admin;

use LDLMS_Post_Types;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Payments Admin screen functionality.
 *
 * @since 4.19.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.19.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.19.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'add_meta_boxes',
			$this->container->callback(
				Edit::class,
				'remove_meta_boxes'
			)
		);

		add_action(
			'add_meta_boxes',
			$this->container->callback(
				Edit::class,
				'rename_publish_metabox'
			)
		);

		add_action(
			'add_meta_boxes',
			$this->container->callback(
				Edit::class,
				'add_meta_boxes'
			)
		);

		add_action(
			'post_submitbox_minor_actions',
			$this->container->callback(
				Edit::class,
				'add_order_actions'
			)
		);

		add_action(
			'admin_notices',
			$this->container->callback(
				Edit::class,
				'resend_invoice'
			)
		);

		add_action(
			'edit_form_advanced',
			$this->container->callback(
				Edit::class,
				'add_test_mode_indicator_after_metaboxes'
			)
		);

		add_filter(
			'learndash_header_data',
			$this->container->callback(
				Edit::class,
				'update_learndash_header_title'
			)
		);

		add_filter(
			'admin_title',
			$this->container->callback(
				Edit::class,
				'update_title_tag'
			),
			10,
			2
		);

		add_action(
			'current_screen',
			$this->container->callback(
				Edit::class,
				'register_scripts'
			)
		);

		add_action(
			'wp_ajax_meta-box-order',
			$this->container->callback(
				Edit::class,
				'prevent_saving_metabox_order'
			),
			0 // Needs to be a higher priority than the default hook.
		);

		add_filter(
			'display_post_states',
			$this->container->callback(
				Edit::class,
				'hide_post_states'
			),
			10,
			2
		);

		$post_type = learndash_get_post_type_slug( LDLMS_Post_Types::TRANSACTION );

		// List page hooks.

		add_action( 'current_screen', $this->container->callback( Listing::class, 'register_scripts' ) );

		add_filter( "manage_{$post_type}_posts_columns", $this->container->callback( Listing::class, 'manage_posts_columns' ) );
		add_filter( "manage_edit-{$post_type}_sortable_columns", $this->container->callback( Listing::class, 'manage_sortable_columns' ) );

		add_action( 'restrict_manage_posts', $this->container->callback( Listing::class, 'restrict_manage_posts' ), 10, 2 );
		add_filter( 'learndash_listing_reset_button_url', $this->container->callback( Listing::class, 'add_test_mode_parameter_on_reset_button' ), 10, 2 );
		add_filter( "views_edit-{$post_type}", $this->container->callback( Listing::class, 'add_test_mode_parameter_on_views' ), 10, 1 );

		add_action( 'pre_get_posts', $this->container->callback( Listing::class, 'filter_posts' ) );
		add_action( 'posts_fields', $this->container->callback( Listing::class, 'filter_posts_fields' ), 10, 2 );
		add_action( 'posts_join', $this->container->callback( Listing::class, 'filter_posts_join' ), 10, 2 );
		add_action( 'posts_where', $this->container->callback( Listing::class, 'filter_posts_where' ), 10, 2 );
		add_action( 'posts_orderby', $this->container->callback( Listing::class, 'filter_posts_orderby' ), 10, 2 );
		add_action( 'posts_search', $this->container->callback( Listing::class, 'filter_posts_search' ), 10, 2 );

		add_filter( 'wp_count_posts', $this->container->callback( Listing::class, 'filter_wp_count_posts' ), 10, 2 );

		add_action( 'admin_init', $this->container->callback( Listing::class, 'register_notices' ) );

		add_filter( 'disable_months_dropdown', $this->container->callback( Listing::class, 'disable_months_dropdown' ), 10, 2 );
	}
}
