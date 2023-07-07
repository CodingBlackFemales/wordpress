<?php
/**
 * A factory class for creating step mappers from models.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Factories;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Mappers;

// TODO: Add tests.

/**
 * A factory class for creating step mappers from models.
 *
 * @since 4.6.0
 */
class Step_Mapper_Factory {
	/**
	 * Creates a step mapper from a model.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Post $model The model to create a mapper for.
	 *
	 * @throws InvalidArgumentException If the model class is not supported.
	 *
	 * @return Mappers\Steps\Mapper
	 */
	public static function create( Models\Post $model ): Mappers\Steps\Mapper {
		switch ( get_class( $model ) ) {
			case Models\Course::class:
				return new Mappers\Steps\Course( $model );
			case Models\Group::class:
				return new Mappers\Steps\Group( $model );
			case Models\Lesson::class:
				return new Mappers\Steps\Lesson( $model );
			case Models\Topic::class:
				return new Mappers\Steps\Topic( $model );
			case Models\Quiz::class:
				return new Mappers\Steps\Quiz( $model );
			default:
				throw new InvalidArgumentException( 'Invalid post class' );
		}
	}
}
