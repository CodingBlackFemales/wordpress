<?php
/**
 * View: Topic Navigation Progress area - Completed.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-navigation__progress-completed">
	<div class="ld-navigation__progress-completed-action">
		<?php
		$this->template(
			'components/icons/lesson-complete',
			[
				'is_aria_hidden' => true,
				'classes'        => [
					'ld-navigation__icon',
					'ld-navigation__icon--lesson-complete',
				],
			]
		);
		?>
		<span class="ld-navigation__label ld-navigation__label--completed">
			<?php
			printf(
				// translators: %s: Topic label.
				esc_html__( '%s Marked Complete', 'learndash' ),
				esc_html( LearnDash_Custom_Label::get_label( LDLMS_Post_Types::TOPIC ) )
			);
			?>
		</span>
	</div>
	<?php $this->template( 'modern/topic/navigation/progress/mark-incomplete' ); ?>
</div>
