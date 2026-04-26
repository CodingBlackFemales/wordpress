<?php
/**
 * Process DTO for Quiz AI modules.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

use Learndash_DTO;

/**
 * Process DTO class.
 *
 * @since 4.8.0
 */
class Process extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'is_success' => 'bool',
		'message'    => 'string',
	];

	/**
	 * Whether a process is successful or not. Default false.
	 *
	 * @since 4.8.0
	 *
	 * @var bool
	 */
	public $is_success = false;

	/**
	 * Returned message for user.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $message = '';
}
