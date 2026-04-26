<?php
/**
 * Provider for initializing documentation for endpoints that are not part of the learndash/v1 REST API.
 *
 * This service provider handles the registration of OpenAPI documentation providers for endpoints
 * that need to be migrated to the learndash/v1 namespace, and are not part of the learndash/v1 REST API.
 *
 * It ensures these endpoints remain discoverable and well-documented through the OpenAPI
 * specification format via learndash/v1/docs.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider for initializing OpenAPI documentation for endpoints that are not part of the learndash/v1 REST API.
 *
 * @since 4.25.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.25.2
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Endpoints\Provider::class );

		$this->hooks();
	}

	/**
	 * Adds the common schemas to the OpenAPI documentation.
	 *
	 * @since 4.25.2
	 *
	 * @param array<string,array<string,mixed>> $schemas The common schemas.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function add_common_schemas( array $schemas ): array {
		$schemas['LDLMS_v2_Assignment']                = Schemas\Assignment::get_schema();
		$schemas['LDLMS_v2_Course']                    = Schemas\Course::get_schema();
		$schemas['LDLMS_v2_Essay']                     = Schemas\Essay::get_schema();
		$schemas['LDLMS_v2_Group']                     = Schemas\Group::get_schema();
		$schemas['LDLMS_v2_Lesson']                    = Schemas\Lesson::get_schema();
		$schemas['LDLMS_v2_Question']                  = Schemas\Question::get_schema();
		$schemas['LDLMS_v2_Quiz_Statistic']            = Schemas\Quiz_Statistic::get_schema();
		$schemas['LDLMS_v2_Quiz']                      = Schemas\Quiz::get_schema();
		$schemas['LDLMS_v2_Quiz_Statistic_Question']   = Schemas\Quiz_Statistic_Question::get_schema();
		$schemas['LDLMS_v2_Topic']                     = Schemas\Topic::get_schema();
		$schemas['LDLMS_v2_User_Course_Progress_Exam'] = Schemas\User_Course_Progress_Exam::get_schema();
		$schemas['LDLMS_v2_User_Course_Progress_Step'] = Schemas\User_Course_Progress_Step::get_schema();
		$schemas['LDLMS_v2_User_Course_Progress']      = Schemas\User_Course_Progress::get_schema();
		$schemas['LDLMS_v2_User']                      = Schemas\WP_User::get_schema();

		return $schemas;
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.2
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_rest_v1_common_schemas',
			$this->container->callback(
				self::class,
				'add_common_schemas'
			)
		);
	}
}
