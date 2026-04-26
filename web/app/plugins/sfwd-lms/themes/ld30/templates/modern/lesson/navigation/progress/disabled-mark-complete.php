<?php
/**
 * View: Lesson Navigation Progress area - Disabled Mark Complete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$classes = [
	'learndash_mark_complete_button',
	'ld-navigation__progress-mark-complete-button',
	'ld-navigation__progress-mark-complete-button--lesson',
	'ld--ignore-inline-css',
];
?>
<button class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" disabled>
	<?php
	$this->template(
		'components/icons/check-2',
		[
			'is_aria_hidden' => true,
			'classes'        => [
				'ld-navigation__icon',
				'ld-navigation__icon--disabled',
			],
		]
	);
	?>

	<?php echo esc_html( LearnDash_Custom_Label::get_label( 'button_mark_complete' ) ); ?>
</button>
