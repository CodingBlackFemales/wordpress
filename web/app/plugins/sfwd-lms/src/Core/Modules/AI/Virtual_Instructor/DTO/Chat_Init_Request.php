<?php
/**
 * Chat Init Request DTO.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\DTO;

use Learndash_DTO;

/**
 * Chat Init Request DTO.
 *
 * @since 4.13.0
 */
class Chat_Init_Request extends Learndash_DTO {
	/**
	 * DTO properties.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'model_id'  => 'int',
		'course_id' => 'int',
		'user_id'   => 'int',
	];

	/**
	 * Virtual Instructor model post ID.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	public int $model_id;

	/**
	 * Course ID.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	public int $course_id;

	/**
	 * User ID.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	public int $user_id;
}
