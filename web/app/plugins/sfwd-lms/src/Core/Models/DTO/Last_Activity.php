<?php
/**
 * Last Activity DTO.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\DTO;

use Learndash_DTO;

/**
 * Last Activity DTO class.
 *
 * @since 4.24.0
 */
class Last_Activity extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.24.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'completed_timestamp' => 'int',
		'course_id'           => 'int',
		'post_id'             => 'int',
		'started_timestamp'   => 'int',
	];

	/**
	 * Completed timestamp.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	public $completed_timestamp = 0;

	/**
	 * Course ID.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	public $course_id = 0;

	/**
	 * Post ID.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Started timestamp.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	public $started_timestamp = 0;
}
