<?php
/**
 * View: Focus Mode Body.
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

?>
<div class="<?php learndash_the_wrapper_class(); ?>">
	<div class="ld-focus ld-focus-initial-transition">
		<?php $this->template( 'focus/sidebar' ); ?>

		<div class="ld-focus-main">
			<?php $this->template( 'focus/masthead' ); ?>

			<div class="ld-focus-content">
				<?php $this->template( 'focus/title' ); ?>

				<?php $this->template( 'focus/content' ); ?>

				<?php $this->template( 'focus/pagination' ); ?>

				<?php $this->template( 'focus/comments' ); ?>
			</div>
		</div>
	</div>
</div>
