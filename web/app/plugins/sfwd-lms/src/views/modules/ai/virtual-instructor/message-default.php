<?php
/**
 * View: Virtual Instructor default message.
 *
 * @since 4.13.0
 * @version 4.15.1
 *
 * @var Chat_Message $message Chat message object.
 * @var Template     $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Template\Template;
?>
<div class="ld-virtual-instructor-chatbox__message ld-virtual-instructor-chatbox__message--<?php echo esc_attr( $message->role ); ?>">
	<p class="ld-virtual-instructor-chatbox__message-text">
		<span class="ld-virtual-instructor-chatbox__message-content">
			<?php echo esc_html( $message->content ); ?>
		</span>
	</p>
</div>


