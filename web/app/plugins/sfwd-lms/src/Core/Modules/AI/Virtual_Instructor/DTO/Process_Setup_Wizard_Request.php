<?php
/**
 * LearnDash AI Virtual Instructor Process Setup Wizard Request DTO.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\DTO;

use Learndash_DTO;

/**
 * Process setup wizard request DTO.
 *
 * @since 4.13.0
 */
class Process_Setup_Wizard_Request extends Learndash_DTO {
	/**
	 * Property types cast.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'openai_api_key'       => 'string',
		'banned_words'         => 'string',
		'error_message'        => 'string',
		'custom_instructions'  => 'string',
		'name'                 => 'string',
		'apply_to_all_courses' => 'bool',
		'course_ids'           => 'array',
		'apply_to_all_groups'  => 'bool',
		'group_ids'            => 'array',
	];

	/**
	 * OpenAI API key.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $openai_api_key;

	/**
	 * Banned words.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $banned_words;

	/**
	 * Error message.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $error_message;

	/**
	 * Name.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Custom instructions.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $custom_instruction;

	/**
	 * Apply to all courses.
	 *
	 * @since 4.13.0
	 *
	 * @var bool
	 */
	public $apply_to_all_courses;

	/**
	 * Course IDs.
	 *
	 * @since 4.13.0
	 *
	 * @var array<int>
	 */
	public $course_ids;

	/**
	 * Apply to all groups.
	 *
	 * @since 4.13.0
	 *
	 * @var bool
	 */
	public $apply_to_all_groups;

	/**
	 * Group IDs.
	 *
	 * @since 4.13.0
	 *
	 * @var array<int>
	 */
	public $group_ids;
}
