<?php
/**
 * Course Reviews module provider.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Reviews\Admin;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Course Reviews module provider.
 *
 * @since 4.25.1
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.25.1
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.25.1
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
			static function ( $metaboxes = [] ) {
				if ( ! isset( $metaboxes['learndash-course-reviews'] ) ) {
					$metaboxes['learndash-course-reviews'] = Metabox::add_metabox_instance();
				}

				return $metaboxes;
			}
		);

		add_filter(
			'learndash_metabox_save_fields_learndash-course-reviews',
			$this->container->callback( Metabox::class, 'filter_saved_fields' ),
			30,
			3
		);
	}
}
