<?php
/**
 * View: Topic Sidebar.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;
?>
<aside class="ld-layout__sidebar">
	<div class="ld-layout__sidebar__content">
		<?php $this->template( 'components/course-overview' ); ?>
	</div>
</aside>
