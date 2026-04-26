<?php
/**
 * Chat Response DTO.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\DTO;

use Learndash_DTO;

/**
 * Chat Response DTO.
 *
 * @since 4.13.0
 */
class Chat_Response extends Learndash_DTO {
	/**
	 * DTO properties.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'html' => 'string',
	];

	/**
	 * HTML elements.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public string $html;
}
