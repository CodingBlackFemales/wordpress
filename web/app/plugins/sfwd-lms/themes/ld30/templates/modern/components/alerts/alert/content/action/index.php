<?php
/**
 * View: Alert Action.
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

if ( ! $alert->get_action_type() ) {
	return;
}

$action_classes = [
	'ld-alert__action',
	'ld-alert__action--' . esc_attr( $alert->get_action_type() ),
];

?>
<div class="<?php echo esc_attr( implode( ' ', $action_classes ) ); ?>">
	<?php $this->template( "modern/components/alerts/alert/content/action/{$alert->get_action_type()}" ); ?>
</div>
