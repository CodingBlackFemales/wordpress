<?php
/**
 * View an Order.
 *
 * @package LifterLMS/Templates
 *
 * @since 3.0.0
 * @since 3.33.0 Pass the current order object instance as param for all the actions and filters, plus redundant check on order existence removed.
 * @since 3.35.0 Access `$_GET` data via `llms_filter_input()`.
 * @version 3.35.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order ) {
	return _e( 'Invalid Order.', 'buddyboss-theme' );
}

$gateway      = $order->get_gateway();
$order_status = $order->get( 'status' );

llms_print_notices();
?>

<div class="llms-sd-section llms-view-order<?php echo apply_filters( 'llms_sd_stacked_order_layout', false, $order ) ? ' llms-stack-cols' : ''; ?>">

	<h2 class="order-title">
		<?php printf( __( 'Order #%d', 'buddyboss-theme' ), $order->get( 'id' ) ); ?>
		<span class="llms-status <?php echo $order_status; ?>"><?php echo $order->get_status_name(); ?></span>
	</h2>

	<?php do_action( 'lifterlms_before_view_order_table', $order ); ?>

	<section class="order-primary">

		<table class="orders-table">
			<tbody>
				<tr>
					<th><?php _e( 'Status', 'buddyboss-theme' ); ?></th>
					<td><?php echo $order->get_status_name(); ?></td>
				</tr>

				<tr>
					<th><?php _e( 'Access Plan', 'buddyboss-theme' ); ?></th>
					<td><?php echo $order->get( 'plan_title' ); ?></td>
				</tr>

				<tr>
					<th><?php _e( 'Product', 'buddyboss-theme' ); ?></th>
					<td><a href="<?php echo get_permalink( $order->get( 'product_id' ) ); ?>"><?php echo $order->get( 'product_title' ); ?></a></td>
				</tr>

				<?php if ( $order->has_trial() ) : ?>
					<?php if ( $order->has_coupon() && $order->get( 'coupon_amount_trial' ) ) : ?>
						<tr>
							<th><?php _e( 'Original Total', 'buddyboss-theme' ); ?></th>
							<td><?php echo $order->get_price( 'trial_original_total' ); ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Coupon Discount', 'buddyboss-theme' ); ?></th>
							<td>
								<?php echo $order->get_coupon_amount( 'trial' ); ?>
								(<?php echo llms_price( $order->get_price( 'coupon_value_trial', array(), 'float' ) * - 1 ); ?>)
								[<a href="<?php echo get_edit_post_link( $order->get( 'coupon_id' ) ); ?>"><?php echo $order->get( 'coupon_code' ); ?></a>]
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<th><?php _e( 'Trial Total', 'buddyboss-theme' ); ?></th>
						<td>
							<?php echo $order->get_price( 'trial_total' ); ?>
							<?php printf( _n( 'for %1$d %2$s', 'for %1$d %2$ss', $order->get( 'trial_length' ), 'buddyboss-theme' ), $order->get( 'trial_length' ), $order->get( 'trial_period' ) ); ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $order->has_discount() ) : ?>
					<tr>
						<th><?php _e( 'Original Total', 'buddyboss-theme' ); ?></th>
						<td><?php echo $order->get_price( 'original_total' ); ?></td>
					</tr>

					<?php if ( $order->has_sale() ) : ?>
						<tr>
							<th><?php _e( 'Sale Discount', 'buddyboss-theme' ); ?></th>
							<td>
								<?php echo $order->get_price( 'sale_price' ); ?>
								(<?php echo llms_price( $order->get_price( 'sale_value', array(), 'float' ) * -1 ); ?>)
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( $order->has_coupon() ) : ?>
						<tr>
							<th><?php _e( 'Coupon Discount', 'buddyboss-theme' ); ?></th>
							<td>
								<?php echo $order->get_coupon_amount( 'regular' ); ?>
								(<?php echo llms_price( $order->get_price( 'coupon_value', array(), 'float' ) * - 1 ); ?>)
								[<a href="<?php echo get_edit_post_link( $order->get( 'coupon_id' ) ); ?>"><?php echo $order->get( 'coupon_code' ); ?></a>]
							</td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>

				<tr>
					<th><?php _e( 'Total', 'buddyboss-theme' ); ?></th>
					<td>
						<?php echo $order->get_price( 'total' ); ?>
						<?php if ( $order->is_recurring() ) : ?>
							<?php
							// phpcs:disable WordPress.WP.I18n.MissingSingularPlaceholder -- We don't output the number in the singular so it throws a CS error but it's working as we want it to.
							printf( _n( 'Every %2$s', 'Every %1$d %2$ss', $order->get( 'billing_frequency' ), 'buddyboss-theme' ), $order->get( 'billing_frequency' ), $order->get( 'billing_period' ) );
							// phpcs:enable WordPress.WP.I18n.MissingSingularPlaceholder
							?>
							<?php if ( $order->get( 'billing_cycle' ) > 0 ) : ?>
								<?php printf( _n( 'for %1$d %2$s', 'for %1$d %2$ss', $order->get( 'billing_cycle' ), 'buddyboss-theme' ), $order->get( 'billing_cycle' ), $order->get( 'billing_period' ) ); ?>
							<?php endif; ?>
						<?php else : ?>
							<?php _e( 'One-time', 'buddyboss-theme' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th><?php _e( 'Payment Method', 'buddyboss-theme' ); ?></th>
					<td>
						<?php if ( is_wp_error( $gateway ) ) : ?>
							<?php echo $order->get( 'payment_gateway' ); ?>
						<?php else : ?>
							<?php echo $gateway->get_title(); ?>
						<?php endif; ?>
						<?php do_action( 'lifterlms_view_order_after_payment_method', $order ); ?>
					</td>
				</tr>

				<tr>
					<th><?php _e( 'Start Date', 'buddyboss-theme' ); ?></th>
					<td><?php echo $order->get_date( 'date', get_option('date_format') ); ?></td>
				</tr>
				<?php if ( $order->is_recurring() ) : ?>
					<tr>
						<th><?php _e( 'Last Payment Date', 'buddyboss-theme' ); ?></th>
						<td><?php echo $order->get_last_transaction_date( 'llms-txn-succeeded', 'any', get_option('date_format') ); ?></td>
					</tr>

					<?php if ( 'llms-pending-cancel' !== $order_status ) : ?>
						<tr>
							<th><?php _e( 'Next Payment Date', 'buddyboss-theme' ); ?></th>
							<td>
								<?php if ( $order->has_scheduled_payment() ) : ?>
									<?php echo $order->get_next_payment_due_date( get_option('date_format') ); ?>
								<?php else : ?>
									&ndash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( ! $order->is_recurring() || 'lifetime' !== $order->get( 'access_expiration' ) || 'llms-pending-cancel' === $order_status ) : ?>
				<tr>
					<th><?php _e( 'Expiration Date', 'buddyboss-theme' ); ?></th>
					<td><?php echo $order->get_access_expiration_date( get_option('date_format') ); ?></td>
				</tr>
				<?php endif; ?>

				<?php do_action( 'lifterlms_view_order_table_body', $order ); ?>
			</tbody>
		</table>
	</section>

	<aside class="order-secondary">

		<?php if ( $order->is_recurring() ) : ?>

			<?php if ( isset( $_GET['confirm-switch'] ) || 'llms-active' === $order_status || $order->can_resubscribe() ) : ?>

				<?php
				llms_get_template(
					'checkout/form-switch-source.php',
					array(
						'confirm' => bb_theme_filter_input_string( INPUT_GET, 'confirm-switch' ),
						'order'   => $order,
					)
				);
				?>

			<?php endif; ?>

			<?php if ( apply_filters( 'llms_allow_subscription_cancellation', true, $order ) && in_array( $order_status, array( 'llms-active', 'llms-on-hold' ) ) ) : ?>

				<form action="" id="llms-cancel-subscription-form" method="POST">

					<?php
					llms_form_field(
						array(
							'columns'     => 12,
							'classes'     => 'llms-button-secondary',
							'id'          => 'llms_cancel_subscription',
							'value'       => __( 'Cancel Subscription', 'buddyboss-theme' ),
							'last_column' => true,
							'required'    => false,
							'type'        => 'submit',
						)
					);
					?>

					<?php wp_nonce_field( 'llms_cancel_subscription', '_cancel_sub_nonce' ); ?>
					<input name="order_id" type="hidden" value="<?php echo $order->get( 'id' ); ?>">

				</form>

			<?php endif; ?>

		<?php endif; ?>

	</aside>

	<div class="clear"></div>

	<?php
		/**
		 * Displays order transactions.
		 *
		 * @since Unknown
		 *
		 * @param LLMS_Order $order        The order being displayed.
		 * @param array      $transactions Result array from {@see LLMS_Order::get_transactions()}.
		 */
		do_action( 'llms_view_order_transactions', $order, $transactions );

		/**
		 * Action run after the display of order information.
		 *
		 * @since Unknown
		 *
		 * @param LLMS_Order $order The order being displayed.
		 */
		do_action( 'lifterlms_after_view_order_table', $order );
	?>

</div>
