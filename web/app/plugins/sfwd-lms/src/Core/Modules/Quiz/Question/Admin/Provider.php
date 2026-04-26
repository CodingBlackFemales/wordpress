<?php
/**
 * Question Admin Provider class.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Quiz\Question\Admin;

use LDLMS_Post_Types;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Question Admin screen functionality.
 *
 * @since 4.21.4
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.21.4
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
	 * @since 4.21.4
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		$post_type = learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION );

		// Edit.

		add_action(
			'admin_init',
			$this->container->callback(
				Edit::class,
				'register_admin_notices'
			)
		);

		// Listing.

		add_filter(
			'manage_edit-' . $post_type . '_columns',
			$this->container->callback(
				Listing::class,
				'remove_title_column'
			)
		);
		add_filter(
			'manage_edit-' . $post_type . '_sortable_columns',
			$this->container->callback(
				Listing::class,
				'add_sortable_column'
			)
		);

		add_filter(
			'list_table_primary_column',
			$this->container->callback(
				Listing::class,
				'set_primary_column'
			),
			50,
			2
		);
	}
}
