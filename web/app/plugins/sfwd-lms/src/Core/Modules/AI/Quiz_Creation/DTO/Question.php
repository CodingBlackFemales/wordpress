<?php
/**
 * Question DTO for AI modules.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Question DTO class.
 *
 * @since 4.8.0
 */
class Question extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'type'    => 'string',
		'title'   => 'string',
		'answers' => 'array',
	];

	/**
	 * Question type.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * Question title.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Question answer options, not necessarily correct answers.
	 *
	 * @since 4.8.0
	 *
	 * @var Answer[]
	 */
	public $answers = [];
}
