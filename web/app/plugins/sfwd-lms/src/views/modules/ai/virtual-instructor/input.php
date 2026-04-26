<?php
/**
 * View: Virtual Instructor Form Input.
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
<input
	class="ld-virtual-instructor-chatbox__form-input"
	type="text"
	placeholder="<?php esc_attr_e( 'Type your message here...', 'learndash' ); ?>"
	autocomplete="off"
	maxlength="<?php echo esc_attr( (string) $max_length ); ?>"
/>
