<?php
/**
 * View: Lesson Content.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Models\Lesson $lesson Lesson model.
 * @var WP_User       $user   User.
 * @var Template      $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;
?>
<main class="ld-layout__content">
	<?php if ( $lesson->is_content_visible( $user ) ) : ?>
		<?php // TODO: Implement a locked view. ?>
		<?php $this->template( 'components/tabs', [ 'is_locked' => $lesson->is_locked() ] ); ?>

		<?php $this->template( 'components/steps' ); // TODO: Must be moved to the content tab (same for every post type). ?>
	<?php endif; ?>
</main>
