<?php
/**
 * Forms - Privacy Policy and Terms Agreement form elements.
 *
 * @version 4.20.2
 *
 * @since 4.20.2
 *
 * @var array{ terms_page: int, privacy_page: int } $terms_settings     The post IDs for the terms and privacy page.
 * @var bool                                        $is_terms_enabled   Flag whether the terms feature is enabled.
 * @var bool                                        $is_privacy_enabled Flag whether the privacy feature is enabled.
 *
 * @package LearnDash\Core
 */

$privacy_label = esc_html( LearnDash_Custom_Label::get_label( 'privacy_policy' ) );
$terms_label   = esc_html( LearnDash_Custom_Label::get_label( 'terms_of_service' ) );

$privacy_error_message = sprintf(
	/* translators: placeholder: %1$s = Privacy Policy label */
	esc_attr( 'Please indicate that you have reviewed and agree to %1$s' ),
	$privacy_label
);
$terms_error_message = sprintf(
	/* translators: placeholder: %1$s = Terms of Service label */
	esc_attr( 'Please indicate that you have reviewed and agree to %1$s' ),
	$terms_label
);

?>
<div class="ld-terms-checkboxes">
	<?php if ( $is_terms_enabled ) : ?>
		<div class="ld-form__field-wrapper">
			<label for="ld-terms-checkbox">
				<input
					data-learndash-validate-error="<?php echo $terms_error_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is escaped above. ?>"
					id="ld-terms-checkbox"
					name="terms_checkbox"
					required="required"
					type="checkbox"
				/>
				<span class="ld-terms-checkboxes__terms-text">
					<?php
					printf(
						/* translators: placeholder: %1$s = opening anchor, %2$s = Terms of Service label, %3$s = closing anchor */
						esc_html__( 'I have reviewed and agree to %1$s%2$s%3$s *', 'learndash' ),
						'<a href="' . esc_url( (string) get_permalink( $terms_settings['terms_page'] ) ) . '" target="_blank">',
						$terms_label, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is escaped above.
						'</a>'
					);
					?>
				</span>
			</label>
		</div>
	<?php endif; ?>
	<?php if ( $is_privacy_enabled ) : ?>
		<div class="ld-form__field-wrapper">
			<label for="ld-privacy-checkbox">
				<input
					data-learndash-validate-error="<?php echo $privacy_error_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is escaped above. ?>"
					id="ld-privacy-checkbox"
					name="privacy_checkbox"
					required="required"
					type="checkbox"
				/>
				<span class="ld-terms-checkboxes__terms-text">
					<?php
					printf(
						/* translators: %1$s = the opening anchor tag, %2$s = Privacy Policy label, %3$s = the closing anchor tag */
						esc_html_x(
							'I have reviewed and agree to %1$s%2$s%3$s *',
							'This is the label for the Privacy checkbox',
							'learndash'
						),
						'<a href="' . esc_url( (string) get_permalink( $terms_settings['privacy_page'] ) ) . '" target="_blank">',
						$privacy_label, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is escaped above.
						'</a>'
					);
					?>
				</span>
			</label>
		</div>
	<?php endif; ?>
</div>
