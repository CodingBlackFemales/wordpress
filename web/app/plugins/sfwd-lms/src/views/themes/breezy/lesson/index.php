<?php
/**
 * View: Lesson Page.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var bool     $is_enrolled An indicator if the user is enrolled in the course.
 * @var Template $this        Current Instance of template engine rendering this template.
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
<div class="<?php learndash_the_wrapper_class( null, 'ld-lesson ' . ( $is_enrolled ? 'ld-lesson--enrolled' : '' ) ); ?>">
	<?php $this->template( 'lesson/header' ); ?>

	<?php $this->template( 'lesson/sidebar' ); ?>

	<?php $this->template( 'lesson/content' ); ?>

	<?php learndash_load_login_modal_html(); // TODO: Move out and call via a hook. ?>
</div>
