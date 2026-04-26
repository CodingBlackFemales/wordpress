<?php
/**
 * View: Alert Icon.
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

if ( ! $alert->get_icon() ) {
	return;
}

?>
<div class="ld-alert__icon ld-alert__icon--<?php echo esc_attr( $alert->get_type() ); ?>">
	<?php
	$this->template(
		'components/icons/' . $alert->get_icon(),
		[
			'classes'        => [ 'ld-alert__icon-svg' ],
			'is_aria_hidden' => true,
		]
	);
	?>
</div>
