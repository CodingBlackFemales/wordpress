<?php
/**
 * Quiz DTO for AI modules.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Quiz DTO class.
 *
 * @since 4.8.0
 */
class Quiz extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'id'               => 'int',
		'title'            => 'string',
		'course_id'        => 'int',
		'lesson_id'        => 'int',
		'topic_id'         => 'int',
		'parent_id'        => 'int',
		'parent_post_type' => 'string',
	];

	/**
	 * Quiz ID.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Quiz title.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Course ID.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $course_id = 0;

	/**
	 * Lesson ID.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $lesson_id = 0;

	/**
	 * Topic ID.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $topic_id = 0;

	/**
	 * Parent ID.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Parent post type.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $parent_post_type = '';
}
