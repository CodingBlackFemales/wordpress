<?php
/**
 * View: PayPal Standard - Current Subscriptions Table Header.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var string $all_user_emails All user emails.
 *
 * @package LearnDash\Core
 */

?>
<thead>
	<tr>
		<th>
			<?php esc_html_e( 'User Display Name', 'learndash' ); ?>
		</th>
		<th>
			<?php esc_html_e( 'Email', 'learndash' ); ?>
			<button
				class="ld-paypal-standard__current-subscriptions--copy-all-emails learndash-copy-text"
				data-tooltip="<?php esc_attr_e( 'Copy All User Emails', 'learndash' ); ?>"
				data-tooltip-default="<?php esc_attr_e( 'Copy All User Emails', 'learndash' ); ?>"
				data-tooltip-success="<?php esc_attr_e( 'Copied!', 'learndash' ); ?>"
				data-text="<?php echo esc_attr( $all_user_emails ); ?>"
			>
				<?php esc_html_e( 'Copy All User Emails', 'learndash' ); ?>
			</button>
		</th>
		<th colspan="2">
			<?php esc_html_e( 'Subscription Name', 'learndash' ); ?>
		</th>
		<th>
			<?php esc_html_e( 'PayPal Subscription ID', 'learndash' ); ?>
		</th>
		<th>
			<?php esc_html_e( 'Migration Status', 'learndash' ); ?>
		</th>
	</tr>
</thead>
