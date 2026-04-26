<?php
/**
 * View: Group Header - Alerts.
 *
 * @since 4.22.0
 * @version 4.22.0
 * @deprecated 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

_deprecated_file( __FILE__, '4.24.0', 'themes/ld30/templates/modern/components/alerts' );

?>
<div class="ld-alerts">
	<?php $this->template( 'modern/group/alerts/certificate' ); ?>

	<?php $this->template( 'modern/group/alerts/progress' ); ?>
</div>
