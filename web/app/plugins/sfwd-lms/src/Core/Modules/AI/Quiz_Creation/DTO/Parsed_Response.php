<?php
/**
 * Parsed Response DTO for Quiz AI module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Parsed response DTO class.
 *
 * @since 4.8.0
 */
class Parsed_Response extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'questions'  => 'array',
		'answers'    => 'array',
		'is_success' => 'bool',
		'message'    => 'string',
	];

	/**
	 * Questions list.
	 *
	 * The array key is question key from the parsed string. E.g. "a", "b", "c",
	 * "1", "2", "3", and so on.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, Question>
	 */
	public $questions = [];

	/**
	 * Question answer options, not necessarily correct answers. In key value pair.
	 *
	 * @since 4.8.0
	 *
	 * @var array<Answer[]>
	 */
	public $answers = [];

	/**
	 * Whether a response is successfully parsed or not. Default true.
	 *
	 * @since 4.8.0
	 *
	 * @var bool
	 */
	public $is_success = true;

	/**
	 * Status message of parsing result.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $message = '';
}
