<?php
/**
 * View: Topic Navigation Progress area - Mark Complete timer area.
 *
 * This template is called from a filter.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var string   $timer_html The current timer HTML.
 * @var Template $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-navigation__progress-timer">
	<?php
	$this->template(
		'components/icons/clock',
		[
			'is_aria_hidden' => true,
			'classes'        => [
				'ld-navigation__icon',
				'ld-navigation__icon--progress-timer',
			],
		]
	);
	?>
	<span
		class="ld-navigation__progress-timer-label"
		data-timer-complete-label="<?php esc_attr_e( 'All done!', 'learndash' ); ?>"
	>
		<?php esc_html_e( 'You can continue in', 'learndash' ); ?>
	</span>

	<?php echo $timer_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output. ?>
</div>
