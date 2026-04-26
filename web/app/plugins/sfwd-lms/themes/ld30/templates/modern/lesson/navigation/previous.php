<?php
/**
 * View: Lesson Navigation Previous.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Step     $progression The progression object.
 * @var Template $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Step;
use LearnDash\Core\Template\Template;

?>
<div class="ld-navigation__previous">
	<a
		class="ld-navigation__previous-link"
		href="<?php echo esc_url( $progression->get_previous_url() ); ?>"
	>

		<?php
		$this->template(
			'components/icons/caret-' . esc_attr( is_rtl() ? 'right' : 'left' ),
			[ 'is_aria_hidden' => true ]
		);
		?>

		<span class="ld-navigation__label ld-navigation__label--previous ld-navigation__label--short">
			<?php echo esc_html( $progression->get_previous_short_label() ); ?>
		</span>

		<span class="ld-navigation__label ld-navigation__label--previous ld-navigation__label--long">
			<?php echo esc_html( $progression->get_previous_label() ); ?>
		</span>

	</a>
</div>
