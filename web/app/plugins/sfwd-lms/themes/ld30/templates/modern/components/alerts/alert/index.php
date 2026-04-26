<?php
/**
 * View: Alert.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Alert    $alert Alert object.
 * @var Template $this  Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Alerts\Alert;
use LearnDash\Core\Template\Template;

$alert_classes = [
	'ld-alert',
	'ld-alert--modern',
	'ld-alert--' . $alert->get_type(),
];

if ( ! empty( $alert->get_action_type() ) ) {
	$alert_classes[] = 'ld-alert--action-' . $alert->get_action_type();
}

?>
<div class="<?php echo esc_attr( implode( ' ', $alert_classes ) ); ?>">
	<?php $this->template( 'modern/components/alerts/alert/icon' ); ?>

	<?php $this->template( 'modern/components/alerts/alert/content' ); ?>

	<?php $this->template( 'modern/components/alerts/alert/action' ); ?>
</div>
