<?php
/**
 * LearnDash LD30 Quiz Assets class.
 *
 * @since 4.21.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Quiz;

use LDLMS_Post_Types;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * LearnDash LD30 Quiz Assets class.
 *
 * @since 4.21.3
 */
class Assets {
	/**
	 * Asset Group name.
	 *
	 * @since 4.21.3
	 *
	 * @var string
	 */
	private const GROUP = 'learndash-quiz';

	/**
	 * Registers scripts that can be enqueued.
	 *
	 * @since 4.21.3
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		Asset::add( 'learndash-sortable-script', 'js/modules/forms/sortable.js' )
			->add_to_group( self::GROUP )
			->add_dependency( 'learndash-main' )
			->add_dependency( 'wp-i18n' )
			->set_path( 'src/assets/dist' )
			->set_condition(
				static function () {
					return is_singular(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ )
					);
				}
			)
			->register();
	}
}
