<?php
/**
 * View: Virtual Instructor chatbox body.
 *
 * @since 4.13.0
 * @version 4.13.0
 *
 * @var Chat_Message[] $messages Messages.
 * @var Template       $this     Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Template\Template;
?>
<div class="ld-virtual-instructor-chatbox__body">
	<?php $this->template( 'modules/ai/virtual-instructor/messages', [ 'messages' => $messages ] ); ?>

	<div class="ld-virtual-instructor-chatbox__loader ld-virtual-instructor-chatbox__loader--hidden"></div>
</div>
