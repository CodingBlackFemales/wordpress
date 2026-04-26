<?php
/**
 * View: Presenter Mode.
 *
 * @since 4.23.0
 * @version 4.23.0
 *
 * @var string   $course_id        The current Course ID.
 * @var string   $icon_position    The position of the presenter mode icon.
 * @var string   $sidebar_position The position of the focus mode sidebar.
 * @var Template $this             Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$classes = [
	'ld-presenter-mode',
	'ld-presenter-mode--position-' . $icon_position,
	'ld-presenter-mode--focus-mode-sidebar-position-' . $sidebar_position,
];

if ( is_admin_bar_showing() ) {
	$classes[] = 'ld-presenter-mode--admin-bar-visible';
}

?>
<div
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	data-course_id="<?php echo esc_attr( $course_id ); ?>"
>
	<?php $this->template( 'focus/components/presenter-mode/button' ); ?>
</div>
