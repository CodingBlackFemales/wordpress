<?php
/**
 * View: Order Subscription Details - Table Body - Field: Status.
 *
 * @since 4.25.0
 * @version 4.25.3
 *
 * @var Subscription $subscription Subscription object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Utilities\Cast;

?>
<div class="ld-order-items__item-data ld-order-items__item-data--last-child" role="cell">
	<div class="ld-order-subscription__details-value">
		<div class="ld-order-subscription__details-status">
			<?php echo esc_html( $subscription->get_status_label() ); ?>
		</div>

		<?php if ( $subscription->is_canceled() ) : ?>
			<div class="ld-order-subscription__details-cancellation-reason">
				<?php
				printf(
					/* translators: 1: Cancellation reason. 2: Cancellation date. */
					esc_html__( '%1$s on %2$s', 'learndash' ),
					esc_html( $subscription->get_cancellation_reason_description() ),
					esc_html( learndash_adjust_date_time_display( Cast::to_int( $subscription->get_cancellation_date() ), 'F j, Y' ) )
				);
				?>
			</div>
		<?php endif; ?>

		<?php if ( $subscription->can_be_cancelled() ) : ?>
			<div class="ld-order-subscription__details-actions">
				<a
					class="ld-order-subscription__details-action ld-order-subscription__details-action--cancel"
					href="<?php echo esc_url( $subscription->get_cancel_url() ); ?>"
				>
					<?php esc_html_e( 'Cancel Subscription', 'learndash' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>
