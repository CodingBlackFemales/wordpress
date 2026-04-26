<?php
/**
 * View: Alert Action Link.
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

if (
	! $alert->get_link_text()
	|| ! $alert->get_link_url()
) {
	return;
}

?>
<div class="ld-alert__link-container">
	<span class="ld-alert__link-separator"></span>

	<a
		class="ld-alert__link"
		href="<?php echo esc_url( $alert->get_link_url() ); ?>"
		target="<?php echo esc_attr( $alert->get_link_target() ); ?>"
	>
		<?php echo esc_html( $alert->get_link_text() ); ?>
	</a>
</div>
