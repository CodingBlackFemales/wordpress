<?php
/**
 * View: Virtual Instructor chatbox.
 *
 * @since 4.13.0
 * @version 4.13.0
 *
 * @var int                $max_length Maximum length of the message.
 * @var Virtual_Instructor $model      Virtual Instructor model instance.
 * @var Chat_Message[]     $messages   Messages.
 * @var Template           $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Virtual_Instructor;
use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Template\Template;
?>
<div class="ld-virtual-instructor-chatbox ld-virtual-instructor-chatbox--close">
	<?php $this->template( 'modules/ai/virtual-instructor/header', [ 'model', $model ] ); ?>

	<?php $this->template( 'modules/ai/virtual-instructor/body', [ 'messages' => $messages ] ); ?>

	<?php $this->template( 'modules/ai/virtual-instructor/form', [ 'max_length' => $max_length ] ); ?>
</div>
