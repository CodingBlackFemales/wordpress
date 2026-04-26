<?php
/**
 * Test Orders onboarding template.
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
				esc_html__( 'No Test %s Yet', 'learndash' ),
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
					// Translators: placeholders: %1$s: orders label, %2$s: course label, %3$s: group label, %4$s Orders label.
					esc_html__( 'To view test %1$s here, enable test mode in your payment settings and purchase a %2$s or %3$s using a test card. Test %1$s made before LearnDash - LMS version 4.19.0 will appear as regular %1$s in the Customer %4$s tab.', 'learndash' ),
					learndash_get_custom_label_lower( 'orders' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'group' ),
					learndash_get_custom_label( 'orders' )
				)
			);
			?>
		</p>
	</div>
</section>
