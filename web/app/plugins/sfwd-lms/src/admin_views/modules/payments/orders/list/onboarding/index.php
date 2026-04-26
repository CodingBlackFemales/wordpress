<?php
/**
 * Orders onboarding template.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 *
 * cSpell:ignore spayments sregistration // Conflict with translation formatted strings.
 */

use LearnDash\Core\Template\Template;

?>

<section class="ld-onboarding-screen ld-orders-onboarding">
	<div class="notice ld-notice ld-notice--info inline">
		<h2>
			<?php
			printf(
				// Translators: %s: Order label.
				esc_html__( 'How To Enable %s Management', 'learndash' ),
				esc_html(
					learndash_get_custom_label( 'order' )
				)
			);
			?>
		</h2>
		<p>
			<?php
			printf(
				// Translators: %s: Order label.
				esc_html__( 'The %s Management functionality is optional and accessible only to supported configurations.', 'learndash' ),
				esc_html(
					learndash_get_custom_label( 'order' )
				)
			);
			?>
		</p>
		<ol>
			<li>
				<?php
				printf(
					// Translators: %1$s: HTML link opening tag with LearnDash general settings URL, %2$s: HTML link closing tag.
					esc_html__( 'Enable the LearnDash 3.0 Template in your %1$sLearnDash General Settings%2$s.', 'learndash' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=learndash_lms_settings' ) ) . '">',
					'</a>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					// Translators: %1$s: HTML link opening tag with WordPress general settings URL, %2$s: HTML link closing tag.
					esc_html__( 'Toggle on Registration by going to your %1$sWordPress General Settings%2$s.', 'learndash' ),
					'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">',
					'</a>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					// Translators: %1$s: HTML link opening tag with LearnDash payment settings URL, %2$s: HTML link closing tag.
					esc_html__( 'If you would like to enable Payments, go to the LearnDash Settings and find %1$spayments%2$s to connect to your payment gateway.', 'learndash' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=learndash_lms_payments' ) ) . '">',
					'</a>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					// Translators: %1$s: HTML link opening tag with LearnDash registration settings URL, %2$s: HTML link closing tag.
					esc_html__( 'Create a registration page using the LearnDash Registration Block, Shortcode, or Widget and assign it in your %1$sregistration page settings%2$s.', 'learndash' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=learndash_lms_registration' ) ) . '">',
					'</a>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					// Translators: %s: Course label.
					esc_html__( 'Make a test purchase on a %s with the Access Mode set to one that requires a purchase, such as Buy Now or Recurring. ', 'learndash' ),
					esc_html(
						learndash_get_custom_label_lower( 'course' )
					)
				);
				?>
			</li>
			<li>
				<?php
				printf(
					// Translators: %s: Orders label.
					esc_html__( 'View %s here 🥳', 'learndash' ),
					esc_html(
						learndash_get_custom_label_lower( 'orders' )
					)
				);
				?>
			</li>
		</ol>
	</div>
</section>
