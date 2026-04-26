<?php
/**
 * Handles plugin initialization.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;

/**
 * Plugin initialization class.
 *
 * @since 4.16.0
 */
class Initialization {
	/**
	 * Option name for the initialization.
	 *
	 * @since 4.16.0
	 *
	 * @var string
	 */
	public static $option_name = 'learndash_initialized';

	/**
	 * Gets the initialization option.
	 *
	 * @since 4.16.0
	 *
	 * @return bool|null
	 */
	public function get_option() {
		$option = get_option( static::$option_name, null );

		if (
			$option !== null
			&& ! is_bool( $option )
		) {
			$option = Cast::to_bool( $option );
		}

		return $option;
	}

	/**
	 * Determines if the plugin has been initialized.
	 *
	 * @since 4.16.0
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return Cast::to_bool( $this->get_option() );
	}

	/**
	 * Determines if the plugin is a new install.
	 *
	 * A site is considered a new install if the static::$option_name is not set AND there are no courses.
	 *
	 * @since 4.16.0
	 *
	 * @return bool
	 */
	public function is_new_install(): bool {
		$is_new_install = false;
		$is_initialized = $this->is_initialized();

		if ( ! $is_initialized ) {
			$has_course = DB::get_var(
				DB::table( 'posts' )
					->select( '1' )
					->where(
						'post_type',
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE )
					)
					->limit( 1 )
					->getSQL()
			);

			$is_new_install = Cast::to_int( $has_course ) !== 1;
		}

		/**
		 * Filters whether the site is a new install.
		 *
		 * @since 4.16.0
		 *
		 * @param bool $is_new_install Whether the site is a new install.
		 * @param bool $is_initialized Whether the site has been initialized.
		 */
		return apply_filters( 'learndash_initialization_is_new_install', $is_new_install, $is_initialized );
	}

	/**
	 * Runs the initialization if necessary.
	 *
	 * If the site is a new install, we fire the `learndash_initialization_new_install` action and
	 * set the static::$option_name to true. Otherwise, we just verify that the static::$option_name
	 * option exists and if not, we set it to true.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! $this->is_new_install() ) {
			if ( $this->get_option() === null ) {
				update_option( static::$option_name, true );
			}

			return;
		}

		update_option( static::$option_name, true );

		/**
		 * Fires on LearnDash new install initialization.
		 *
		 * @since 4.16.0
		 */
		do_action( 'learndash_initialization_new_install' );
	}
}
