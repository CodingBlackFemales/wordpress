<?php
/**
 * View: Virtual Instructor Form.
 *
 * @since 4.13.0
 * @version 4.13.0
 *
 * @var int      $max_length Maximum length of the message.
 * @var Template $this       Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<form class="ld-virtual-instructor-chatbox__form" id="ld-virtual-instructor-chatbox__form">
	<?php $this->template( 'modules/ai/virtual-instructor/input', [ 'max_length' => $max_length ] ); ?>

	<?php $this->template( 'modules/ai/virtual-instructor/button' ); ?>
</form>
