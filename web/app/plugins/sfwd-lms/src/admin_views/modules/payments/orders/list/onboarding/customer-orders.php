<?php
/**
 * Customer Orders onboarding template.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>

<section class="ld-onboarding-screen ld-orders-onboarding">
	<div class="notice ld-notice ld-notice--no-left-border inline">
		<h2>
			<?php
			printf(
				// Translators: placeholders: Orders label.
				esc_html__( 'No Customer %s Yet', 'learndash' ),
				esc_html(
					learndash_get_custom_label( 'orders' )
				)
			);
			?>
		</h2>
		<p>
			<?php
			echo esc_html(
				sprintf(
					// Translators: placeholders: %1$s: orders label, %2$s: Orders label.
					esc_html__( 'You have no live %1$s. To view test %1$s, go to the Test %2$s tab, or make a real purchase to see Customer %2$s here.', 'learndash' ),
					learndash_get_custom_label_lower( 'orders' ),
					learndash_get_custom_label_lower( 'orders' ),
					learndash_get_custom_label( 'orders' ),
					learndash_get_custom_label( 'orders' )
				)
			);
			?>
		</p>
	</div>
</section>
