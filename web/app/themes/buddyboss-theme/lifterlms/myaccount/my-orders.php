<?php
/**
 * Order History List
 *
 * @package LifterLMS/Templates
 *
 * @since    3.0.0
 * @version  3.17.6
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="llms-sd-section llms-my-orders">

	<?php if ( ! $orders || ! $orders['orders'] ) : ?>
		<div class="llms-sd-section__blank">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/my-order-history.svg" alt="Orders" />
			<p><?php _e( 'No orders found.', 'buddyboss-theme' ); ?></p>
		</div>
	<?php else : ?>

		<table class="orders-table">
			<thead>
				<tr>
					<td><?php _e( 'Order', 'buddyboss-theme' ); ?></td>
					<td><?php _e( 'Date', 'buddyboss-theme' ); ?></td>
					<td><?php _e( 'Expires', 'buddyboss-theme' ); ?></td>
					<td><?php _e( 'Next Payment', 'buddyboss-theme' ); ?></td>
					<td></td>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $orders['orders'] as $order ) : ?>
				<tr class="llms-order-item <?php echo $order->get( 'status' ); ?>" id="llms-order-<?php $order->get( 'id' ); ?>">
					<td data-label="<?php _e( 'Order', 'buddyboss-theme' ); ?>: ">
						<a href="<?php echo $order->get_view_link(); ?>">#<?php echo $order->get( 'id' ); ?></a>
						<span class="llms-status <?php echo $order->get( 'status' ); ?>"><?php echo $order->get_status_name(); ?></span>
					</td>
					<td data-label="<?php _e( 'Date', 'buddyboss-theme' ); ?>: "><?php echo $order->get_date( 'date', get_option('date_format') ); ?></td>
					<td data-label="<?php _e( 'Expires', 'buddyboss-theme' ); ?>: ">
						<?php if ( $order->is_recurring() && 'lifetime' === $order->get( 'access_expiration' ) ) : ?>
							&ndash;
						<?php else : ?>
							<?php echo $order->get_access_expiration_date( get_option('date_format') ); ?>
						<?php endif; ?>
					</td>
					<td data-label="<?php _e( 'Next Payment', 'buddyboss-theme' ); ?>: ">
						<?php if ( $order->has_scheduled_payment() ) : ?>
							<?php echo $order->get_next_payment_due_date( get_option('date_format') ); ?>
						<?php else : ?>
							&ndash;
						<?php endif; ?>
					</td>
					<td>
						<a class="llms-button-primary small" href="<?php echo $order->get_view_link(); ?>"><?php _e( 'View', 'buddyboss-theme' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $orders['orders'] ) : ?>
			<footer class="llms-sd-pagination llms-my-orders-pagination">
				<?php if ( $orders['page'] > 1 ) : ?>
					<a href="
					<?php
					echo add_query_arg(
						array(
							'opage' => $orders['page'] - 1,
						)
					);
					?>
					"><?php _e( 'Back', 'buddyboss-theme' ); ?></a>
				<?php endif; ?>

				<?php if ( $orders['page'] < $orders['pages'] ) : ?>
					<a href="
					<?php
					echo add_query_arg(
						array(
							'opage' => $orders['page'] + 1,
						)
					);
					?>
					"><?php _e( 'Next', 'buddyboss-theme' ); ?></a>
				<?php endif; ?>
			</footer>
		<?php endif; ?>

	<?php endif; ?>
</div>
