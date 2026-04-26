<?php
/**
 * View: Order Payment Details Table Body.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template    $this        Current instance of template engine rendering this template.
 * @var Transaction $transaction Transaction object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Transaction;

?>

<div class="ld-order-details__tbody" role="rowgroup">
	<div role="row">
		<span role="cell">
			<?php echo wp_kses_post( $transaction->get_gateway_label() ); ?>
		</span>
	</div>

	<?php if ( ! empty( $transaction->get_gateway_customer_id() ) ) : ?>
		<div role="row">
			<span role="cell">
				<?php
				echo wp_kses(
					sprintf(
						// translators: placeholders: Gateway Customer ID followed by a button to copy the Customer ID to the clipboard.
						__( 'Customer ID: %1$s %2$s', 'learndash' ),
						$transaction->get_gateway_customer_id(),
						Template::get_admin_template(
							'common/copy-text',
							[
								'text'            => $transaction->get_gateway_customer_id(),
								'tooltip_default' => __( 'Copy Customer ID', 'learndash' ),
							]
						)
					),
					[
						'button' => [
							'class'                => true,
							'data-tooltip'         => true,
							'data-tooltip-default' => true,
							'data-tooltip-success' => true,
							'data-text'            => true,
						],
						'span'   => [
							'class'       => true,
							'aria-hidden' => true,
						],
					]
				);
				?>
			</span>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $transaction->get_gateway_transaction_id() ) && ! $transaction->is_subscription() ) : ?>
		<div role="row">
			<span role="cell">
				<?php
				echo wp_kses(
					sprintf(
						// translators: placeholders: Gateway Transaction ID followed by a button to copy the Customer ID to the clipboard.
						__( 'Session ID: %1$s %2$s', 'learndash' ),
						$transaction->get_gateway_transaction_id(),
						Template::get_admin_template(
							'common/copy-text',
							[
								'text'            => $transaction->get_gateway_transaction_id(),
								'tooltip_default' => __( 'Copy Session ID', 'learndash' ),
							]
						)
					),
					[
						'button' => [
							'class'                => true,
							'data-tooltip'         => true,
							'data-tooltip-default' => true,
							'data-tooltip-success' => true,
							'data-text'            => true,
						],
						'span'   => [
							'class'       => true,
							'aria-hidden' => true,
						],
					]
				);
				?>
			</span>
		</div>
	<?php endif; ?>
</div>
