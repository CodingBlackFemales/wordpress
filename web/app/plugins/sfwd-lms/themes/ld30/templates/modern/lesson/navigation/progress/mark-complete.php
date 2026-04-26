<?php
/**
 * View: Lesson Navigation Progress area - Mark Complete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Lesson   $lesson                        The lesson model.
 * @var bool     $automatic_progression_enabled Whether automatic progression is enabled.
 * @var Template $this                          Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Str;

$mark_complete_html = learndash_mark_complete(
	$lesson->get_post(),
	! $automatic_progression_enabled
		? [
			'form' => [
				'action' => '#ld-navigation__next-link',
			],
		]
		: []
);

$is_disabled = empty( $mark_complete_html ) || Str::contains( $mark_complete_html, 'disabled' );

$classes = [
	'ld-navigation__progress-mark-complete',
	'ld-tooltip ld-tooltip--modern',
];

?>
<div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
	<span
		class="ld-tooltip__text"
		role="tooltip"
		style="<?php echo ! $is_disabled ? 'display: none;' : ''; ?>"
	>
		<?php
		printf(
			// translators: placeholder: lesson label.
			esc_html__( 'Finish the required activity to complete this %s.', 'learndash' ),
			esc_html( learndash_get_custom_label_lower( LDLMS_Post_Types::LESSON ) )
		);
		?>
	</span>

	<?php if ( ! empty( $mark_complete_html ) ) : ?>
		<?php echo $mark_complete_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output. ?>
	<?php else : ?>
		<?php $this->template( 'modern/lesson/navigation/progress/disabled-mark-complete' ); ?>
	<?php endif; ?>
</div>
