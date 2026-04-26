<?php
/**
 * Request DTO for Quiz AI module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Request DTO class.
 *
 * This class is used to collect arguments to build Quiz creation AI request
 * parameters to AI service provider.
 *
 * @since 4.8.0
 */
class Request extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'question_type'            => 'string',
		'total_questions_per_type' => 'int',
		'quiz_title'               => 'string',
		'quiz_idea'                => 'string',
	];

	/**
	 * Question type.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $question_type = '';

	/**
	 * Total questions per questions type requested by user.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $total_questions_per_type = 0;

	/**
	 * Quiz title.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $quiz_title = '';

	/**
	 * Brief description about quiz idea.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $quiz_idea = '';
}
