<?php
/**
 * View: Topic Navigation Progress area - Mark Complete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic                         The topic model.
 * @var bool     $automatic_progression_enabled Whether automatic progression is enabled.
 * @var Template $this                          Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Str;

$mark_complete_html = learndash_mark_complete(
	$topic->get_post(),
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
	$is_disabled ? 'ld-tooltip ld-tooltip--modern' : '',
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
			// translators: placeholder: topic label.
			esc_html__( 'Finish the required activity to complete this %s.', 'learndash' ),
			esc_html( learndash_get_custom_label_lower( LDLMS_Post_Types::TOPIC ) )
		);
		?>
	</span>

	<?php if ( ! empty( $mark_complete_html ) ) : ?>
		<?php echo $mark_complete_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output. ?>
	<?php else : ?>
		<?php $this->template( 'modern/topic/navigation/progress/disabled-mark-complete' ); ?>
	<?php endif; ?>
</div>
