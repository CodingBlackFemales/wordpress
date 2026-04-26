<?php
/**
 * Answer DTO for AI modules.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Answer DTO class.
 *
 * @since 4.8.0
 */
class Answer extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'id'         => 'string',
		'title'      => 'string',
		'is_correct' => 'bool',
		'params'     => 'array',
	];

	/**
	 * Answer ID used internally to identify an answer.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Answer text.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Whether an answer is correct or not. Default false.
	 *
	 * @since 4.8.0
	 *
	 * @var bool
	 */
	public $is_correct = false;

	/**
	 * Property to store multiple question type-specific parameters.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, mixed>
	 */
	public $params = [];
}
