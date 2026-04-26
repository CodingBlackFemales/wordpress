<?php
/**
 * View: Group Sidebar.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<aside class="ld-layout__sidebar">
	<?php $this->template( 'modern/group/enrollment' ); ?>

	<?php $this->template( 'modern/group/details' ); ?>
</aside>
