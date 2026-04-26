<?php
/**
 * View: Alert Action Button.
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

$button_icon = $alert->get_button_icon();
if ( empty( $button_icon ) ) {
	$button_icon = is_rtl() ? 'caret-left' : 'caret-right';
}

?>
<a
	class="ld-alert__button"
	href="<?php echo esc_url( $alert->get_link_url() ); ?>"
	target="<?php echo esc_attr( $alert->get_link_target() ); ?>"
>
	<?php echo esc_html( $alert->get_link_text() ); ?>

	<?php
	$this->template(
		"components/icons/{$button_icon}",
		[
			'classes'        => [ 'ld-alert__button-icon' ],
			'is_aria_hidden' => true,
		]
	);
	?>
</a>


