<?php
/**
 * View: Alert Message.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Alert    $alert Alert object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Alerts\Alert;
use LearnDash\Core\Template\Template;

if ( empty( $alert->get_message() ) ) {
	return;
}

?>
<span class="ld-alert__message">
	<?php echo esc_html( $alert->get_message() ); ?>
</span>
