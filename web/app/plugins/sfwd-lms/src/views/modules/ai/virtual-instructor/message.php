<?php
/**
 * View: Virtual Instructor Message.
 *
 * @since 4.13.0
 * @version 4.13.0
 *
 * @var Chat_Message $message Chat message object.
 * @var Template     $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Template\Template;

$message->is_error
	? $this->template( 'modules/ai/virtual-instructor/message-error', [ 'message' => $message ] )
	: $this->template( 'modules/ai/virtual-instructor/message-default', [ 'message' => $message ] );
