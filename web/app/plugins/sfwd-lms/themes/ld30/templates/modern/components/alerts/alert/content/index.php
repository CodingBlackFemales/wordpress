<?php
/**
 * View: Alert Content.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this  Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-alert__content">
	<?php $this->template( 'modern/components/alerts/alert/content/message' ); ?>

	<?php $this->template( 'modern/components/alerts/alert/content/action' ); ?>
</div>
