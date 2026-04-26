<?php
/**
 * View: Alerts.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Alerts   $alerts Alerts.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Alerts\Alerts;
use LearnDash\Core\Template\Template;

if ( $alerts->is_empty() ) {
	return;
}

?>
<div class="ld-alerts">
	<?php foreach ( $alerts as $alert ) : ?>
		<?php $this->template( 'modern/components/alerts/alert', [ 'alert' => $alert ] ); ?>
	<?php endforeach; ?>
</div>
