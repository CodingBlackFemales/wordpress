<?php
/**
 * View: Focus Mode Masthead Mobile Menu Trigger.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
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

?>
<div class="ld-mobile-nav">
	<a href="#" class="ld-trigger-mobile-nav" aria-label="<?php esc_attr_e( 'Menu', 'learndash' ); ?>">
		<span class="bar-1"></span>
		<span class="bar-2"></span>
		<span class="bar-3"></span>
	</a>
</div>
