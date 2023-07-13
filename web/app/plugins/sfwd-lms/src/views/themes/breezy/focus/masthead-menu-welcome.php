<?php
/**
 * View: Focus Mode Masthead Menu Welcome.
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
<span class="ld-text ld-user-welcome-text">
	<?php
	echo sprintf(
		// Translators: Focus mode welcome name placeholder.
		esc_html_x( 'Hello, %s!', 'Focus mode welcome placeholder', 'learndash' ),
		wp_kses_post(
			/**
			 * Filters focus mode user welcome name.
			 *
			 * @since 4.6.0
			 *
			 * @param string  $user_name User nice name.
			 * @param WP_User $user      User.
			 *
			 * @ignore
			 */
			apply_filters( 'learndash_focus_mode_welcome_name', $user->user_nicename, $user )
		)
	);
	?>
</span>
