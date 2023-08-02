<?php
/**
 * View: Focus Mode.
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

if ( ! have_posts() ) :
	$this->template(
		'components/alert',
		array(
			'type'    => 'warning',
			'icon'    => 'alert',
			'message' => esc_html__( 'No content found at this address', 'learndash' ),
		)
	);
else :
	// @phpstan-ignore-next-line -- It's fine that it's always true.
	while ( have_posts() ) :
		the_post();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<?php $this->template( 'focus/head' ); ?>
			</head>

			<body <?php body_class(); ?>>
				<?php $this->template( 'focus/body' ); ?>

				<?php learndash_load_login_modal_html(); ?>

				<?php wp_footer(); ?>
			</body>
		</html>
	<?php endwhile; ?>
<?php endif; ?>
