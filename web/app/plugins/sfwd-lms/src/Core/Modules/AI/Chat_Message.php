<?php
/**
 * Chat message object class file.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

/**
 * Chat message object class.
 *
 * @since 4.13.0
 */
class Chat_Message {
	/**
	 * Message text content.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public string $content;

	/**
	 * AI provider author role.
	 *
	 * For now, we only support ChatGPT as AI provider. It accepts 3 roles: 'system', 'assistant', and 'user'. In the future, we may support other AI providers that accept different roles.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public string $role;

	/**
	 * Is error message.
	 *
	 * @since 4.13.0
	 *
	 * @var bool
	 */
	public bool $is_error = false;

	/**
	 * Message constructor.
	 *
	 * @param string $content  Message text.
	 * @param string $role     AI provider author role.
	 *
	 * @since 4.13.0
	 */
	public function __construct( string $content, string $role = '' ) {
		$this->content = $content;
		$this->role    = $role;
	}

	/**
	 * Mark message as error.
	 *
	 * @since 4.13.0
	 *
	 * @return self
	 */
	public function mark_as_error(): self {
		$this->is_error = true;
		$this->role     = '';

		return $this;
	}
}
