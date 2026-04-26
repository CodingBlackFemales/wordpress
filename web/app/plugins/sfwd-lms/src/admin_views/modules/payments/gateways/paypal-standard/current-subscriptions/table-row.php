<?php
/**
 * View: PayPal Standard - Current Subscriptions Table Row.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this                Current instance of template engine rendering this template.
 * @var string   $paypal_account_link PayPal account link.
 * @var array<string,{
 *    'name' => string,
 *    'email' => string,
 *    'subscriptions' => array<string>,
 *    'paypal_subscription_ids' => array<string>,
 *    'migration_status' => string,
 * }>            $subscription Current subscription data.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

// Ensure $subscription is defined.
if ( ! isset( $subscription ) ) {
	return;
}

?>
<tr>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--name"><?php echo esc_html( $subscription['name'] ); ?></td>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--email">
		<a href="mailto:<?php echo esc_attr( $subscription['email'] ); ?>">
			<?php echo esc_html( $subscription['email'] ); ?>
		</a>
		<?php
			$this::show_admin_template(
				'common/copy-text',
				[
					'text'            => $subscription['email'],
					'tooltip_default' => esc_html__( 'Copy Email', 'learndash' ),
				]
			);
			?>
	</td>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--count">
		<?php echo count( $subscription['subscriptions'] ); ?>
	</td>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--subscriptions">
		<ul class="ld-paypal-standard__current-subscriptions-cell--subscriptions-list">
		<?php foreach ( $subscription['subscriptions'] as $subscription_name ) : ?>
			<li><?php echo esc_html( $subscription_name ); ?></li>
		<?php endforeach; ?>
		</ul>
	</td>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--paypal-ids">
		<ul class="ld-paypal-standard__current-subscriptions-cell--paypal-ids-list">
		<?php foreach ( $subscription['paypal_subscription_ids'] as $paypal_subscription_id ) : ?>
			<li>
				<a href="<?php echo esc_url( $paypal_account_link . $paypal_subscription_id ); ?>" target="_blank">
					<?php echo esc_html( $paypal_subscription_id ); ?>
					<span class="dashicons dashicons-external ld-paypal-standard__current-subscriptions-icon" aria-hidden="true"></span>
				</a>
			</li>
		<?php endforeach; ?>
		</ul>
	</td>
	<td class="ld-paypal-standard__current-subscriptions-cell ld-paypal-standard__current-subscriptions-cell--migration-status">
		<?php
		$this::show_admin_template(
			'modules/payments/gateways/paypal-standard/current-subscriptions/migration-status',
			[
				'migration_status' => $subscription['migration_status'],
			]
		);
		?>
	</td>
</tr>
