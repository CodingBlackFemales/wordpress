<?php
/**
 * View: PayPal Standard - Migration Instructions.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-paypal-standard__migration-instructions">
	<p>
	<?php
		echo esc_html(
			sprintf(
				// translators: 1: Courses label, 2: Groups label.
				__( 'Your %1$s and %2$s that need updates are:', 'learndash' ),
				learndash_get_custom_label( 'courses' ),
				learndash_get_custom_label( 'groups' )
			)
		);
		?>
	</p>
</div>
