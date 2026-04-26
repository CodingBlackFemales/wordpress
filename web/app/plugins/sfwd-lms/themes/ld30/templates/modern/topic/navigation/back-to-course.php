<?php
/**
 * View: Topic Navigation Back to Course.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Step     $progression The progression object.
 * @var WP_User  $user        WP_User object.
 * @var Template $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Step;
use LearnDash\Core\Template\Template;

$classes = [
	'ld-navigation__back-to-course',
	// If the user is not logged in, we don't show the progress area, which affects the layout for the back to course button.
	! $user->exists() ? 'ld-navigation__back-to-course--no-progress' : '',
];

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<a
		href="<?php echo esc_url( $progression->get_back_to_course_url() ); ?>"
		class="ld-navigation__back-to-course-link"
	>
		<?php
		$this->template( 'components/icons/course', [ 'is_aria_hidden' => true ] );
		?>

		<?php
		echo esc_html( $progression->get_back_to_course_label() );
		?>
	</a>
</div>
