<?php
/**
 * Template: guest checkout enabled warning.
 *
 * @since 5.2.0
 * @version 5.2.0
 *
 * @var string $nonce               Nonce.
 * @var string $notice_id           Notice ID.
 * @var string $dismisser_classname Dismisser class name.
 * @var string $setting_page_url    Setting page URL.
 * @var string $course_label        Course label.
 * @var string $group_label         Group label.
 *
 * @package LearnDash\WooCommerce
 */

// cSpell:ignore sguest - s is part of a placeholder.

?>
<div class="notice notice-warning is-dismissible <?php echo esc_attr( $dismisser_classname ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-id="<?php echo esc_attr( $notice_id ); ?>">
	<p>
		<strong>
			<?php esc_html_e( 'LearnDash LMS - WooCommerce', 'learndash' ); ?>:
		</strong>
		<span>
			<?php
			printf(
				// translators: %1$s: opening "a" tag, %2$s: closing "a" tag, %3$s: Course, %4$s: Group.
				esc_html__(
					'We\'ve noticed that you have WooCommerce %1$sguest checkout%2$s enabled. Please note that purchasing a product with an associated LearnDash %3$s or %4$s still requires an account to ensure successful enrollment.',
					'learndash'
				),
				'<a href="https://woocommerce.com/document/configuring-woocommerce-settings/#accounts-and-privacy-settings" target="_blank">',
				'</a>',
				esc_html( $course_label ),
				esc_html( $group_label ),
			);
			?>
		</span>
	</p>
</div>
