<?php
/**
 * View: Course Sidebar.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<aside class="ld-layout__sidebar">
	<?php $this->template( 'modern/course/enrollment' ); ?>

	<?php $this->template( 'modern/course/details' ); ?>
</aside>
