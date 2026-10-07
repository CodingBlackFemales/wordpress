<?php
/**
 * Template: enrollment status setting section.
 *
 * @since 5.2.0
 * @version 5.2.0
 *
 * @var string                              $nonce_field           Nonce field.
 * @var array<string, array<string, mixed>> $setting_option_fields Setting option fields.
 * @var array<string, string>               $order_statuses        Order statuses.
 * @var array<string, string>               $subscription_statuses Subscription statuses.
 * @var string                              $prefix_order          Order status setting prefix.
 * @var string                              $prefix_subscription   Subscription status setting prefix.
 *
 * @package LearnDash\WooCommerce
 */

// cSpell:ignore sretroactive -- s is part of the placeholder.

?>
<?php echo wp_kses( $nonce_field, wp_kses_allowed_html( 'data' ) ); ?>
<div class="sfwd sfwd_options">
	<table class="learndash-settings-table learndash-settings-enrollment-status learndash-settings-order-enrollment-status widefat striped">
		<thead>
		<tr>
			<th class="col-name-label">
				<?php esc_html_e( 'Order Status', 'learndash' ); ?>
			</th>
			<th class="col-name-enabled">
				<?php esc_html_e( 'Access', 'learndash' ); ?>
			</th>
		<tr>
		</thead>

		<tbody>
			<?php foreach ( $order_statuses as $status => $label ) : ?>
			<tr>
				<td class="col-name-label">
					<div class="learndash-listing_label">
						<strong>
							<?php echo esc_html( $label ); ?>
						</strong>
					</div>
				</td>
				<td class="col-name-enabled col-valign-middle">
					<?php
					$key = $prefix_order . $status;

					if (
						isset( $setting_option_fields[ $key ]['display_callback'] )
						&& is_callable( $setting_option_fields[ $key ]['display_callback'] )
					) {
						call_user_func(
							$setting_option_fields[ $key ]['display_callback'],
							$setting_option_fields[ $key ]
						);
					}
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="sfwd sfwd_options">
	<table class="learndash-settings-table learndash-settings-enrollment-status learndash-settings-subscription-enrollment-status widefat striped">
		<thead>
		<tr>
			<th class="col-name-label">
				<?php esc_html_e( 'Subscription Status', 'learndash' ); ?>
			</th>
			<th class="col-name-enabled">
				<?php esc_html_e( 'Access', 'learndash' ); ?>
			</th>
		<tr>
		</thead>

		<tbody>
			<?php foreach ( $subscription_statuses as $status => $label ) : ?>
			<tr>
				<td class="col-name-label">
					<div class="learndash-listing_label">
						<strong>
							<?php echo esc_html( $label ); ?>
						</strong>
					</div>
				</td>
				<td class="col-name-enabled col-valign-middle">
					<?php
					$key = $prefix_subscription . $status;

					if (
						isset( $setting_option_fields[ $key ]['display_callback'] )
						&& is_callable( $setting_option_fields[ $key ]['display_callback'] )
					) {
						call_user_func(
							$setting_option_fields[ $key ]['display_callback'],
							$setting_option_fields[ $key ]
						);
					}
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<p class="description">
	<?php
	printf(
		// Translators: %1$s and %2$s are <strong> tags, %3$s and %4$s are <a> tags.
		esc_html__(
			'%1$sNote:%2$s These settings apply only to new WooCommerce orders created after saving. If you want these settings to apply to any past orders, you’ll need to run the %3$sretroactive tool%4$s.',
			'learndash'
		),
		'<strong>',
		'</strong>',
		'<a href="https://www.learndash.com/support/docs/add-ons/woocommerce/#retroactive_course_access_tool" target="_blank" rel="noopener noreferrer">',
		'</a>'
	);
	?>
</p>
