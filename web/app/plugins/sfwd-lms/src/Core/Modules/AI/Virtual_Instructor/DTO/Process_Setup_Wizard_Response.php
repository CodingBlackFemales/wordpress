<?php
/**
 * LearnDash AI Virtual Instructor Process Setup Wizard Response DTO.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\DTO;

use Learndash_DTO;

/**
 * Process setup wizard response DTO.
 *
 * @since 4.13.0
 */
class Process_Setup_Wizard_Response extends Learndash_DTO {
	/**
	 * Property types cast.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'status'  => 'string',
		'message' => 'string',
	];

	/**
	 * Status.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Status message of the process.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public $message;
}
