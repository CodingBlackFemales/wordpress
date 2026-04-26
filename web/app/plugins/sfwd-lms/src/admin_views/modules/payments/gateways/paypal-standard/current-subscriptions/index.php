<?php
/**
 * View: PayPal Standard - Current Subscriptions.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this                Current instance of template engine rendering this template.
 * @var int      $current_page        Current page number.
 * @var int      $total_items         Total items.
 * @var string   $paypal_account_link PayPal account link.
 * @var array<string,{
 *    'name' => string,
 *    'email' => string,
 *    'subscriptions' => array<string>,
 *    'paypal_subscription_ids' => array<string>,
 *    'migration_status' => string,
 * }>            $subscriptions List of subscriptions.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( empty( $subscriptions ) ) {
	return;
}

$all_user_emails = implode( ', ', array_column( $subscriptions, 'email' ) );

?>
<table
	class="learndash-settings-table ld-paypal-standard__current-subscriptions widefat striped"
	cellspacing="0"
>
	<?php
	$this::show_admin_template(
		'modules/payments/gateways/paypal-standard/current-subscriptions/table-header',
		[
			'all_user_emails' => $all_user_emails,
		]
	);
	?>

	<tbody>
		<?php foreach ( $subscriptions as $subscription ) : ?>
			<?php
			$this::show_admin_template(
				'modules/payments/gateways/paypal-standard/current-subscriptions/table-row',
				[
					'subscription'        => $subscription,
					'paypal_account_link' => $paypal_account_link,
				]
			);
			?>
		<?php endforeach; ?>
	</tbody>
</table>

<?php
$this::show_admin_template(
	'modules/payments/gateways/paypal-standard/pagination',
	[
		'current_page'   => $current_page,
		'items_per_page' => 10,
		'total_items'    => $total_items,
	]
);
