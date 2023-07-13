<?php
/**
 * View: Focus Mode Masthead Menu.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
 * @var Template    $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Interfaces\Course_Step;
use LearnDash\Core\Template\Template;

if ( $user->ID <= 0 ) {
	return;
}
?>
<div class="ld-user-menu">
	<?php $this->template( 'focus/masthead-menu-welcome' ); ?>

	<?php $this->template( 'focus/masthead-menu-avatar' ); ?>

	<?php $this->template( 'focus/masthead-menu-items' ); ?>
</div>
